<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * Dynamic data tag grammar.
 *
 *   {name}
 *   {name:primary value}
 *   {name @key:'value' @key:value @flag}
 *   {name:primary value @key:'value' @flag}
 *
 * - name is lowercase [a-z0-9_] immediately after the opening brace.
 * - The primary value runs to the first whitespace-then-@ or the closing brace,
 *   so colons, spaces and commas need no quoting: {current_date:g:i A}.
 * - Named values are quoted with single quotes when they contain whitespace,
 *   quotes, braces or @. Inside quotes, \' is a quote and \\ a backslash.
 *   Double quotes are never used so a tag can sit inside a shortcode attribute.
 * - Anything that does not fit is not a tag and is left in the text untouched.
 *
 * The block editor parses the same grammar in JS. Both are held to
 * tests/fixtures/dynamic-data-grammar.json, so change that file with this one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Grammar {

	/** Longest string, braces included, that is still considered a tag. */
	const MAX_TAG_LENGTH = 1024;

	/**
	 * Find every well formed tag in a string.
	 *
	 * Malformed candidates are skipped rather than consumed, so a stray brace
	 * never swallows a real tag that follows it.
	 *
	 * @param string $text Text that may contain tags.
	 * @return array<int, array{offset: int, length: int, raw: string, tag: array}>
	 */
	public static function find( string $text ): array {
		$found  = array();
		$length = strlen( $text );
		$pos    = 0;

		while ( true ) {
			$start = strpos( $text, '{', $pos );
			if ( false === $start ) {
				break;
			}

			$end = self::scan_to_close( $text, $start, $length );

			if ( null !== $end ) {
				$raw    = substr( $text, $start, $end - $start + 1 );
				$parsed = self::parse( $raw );

				if ( null !== $parsed ) {
					$found[] = array(
						'offset' => $start,
						'length' => $end - $start + 1,
						'raw'    => $raw,
						'tag'    => $parsed,
					);
					$pos     = $end + 1;
					continue;
				}
			}

			$pos = $start + 1;
		}

		return $found;
	}

	/**
	 * Parse a single tag, braces included.
	 *
	 * @param string $raw For example "{post_date:F j, Y @link}".
	 * @return array{name: string, primary: ?string, args: array<string, string|true>}|null
	 *         Null when the string is not a well formed tag.
	 */
	public static function parse( string $raw ): ?array {
		if ( strlen( $raw ) < 3 || '{' !== $raw[0] || '}' !== substr( $raw, -1 ) ) {
			return null;
		}

		$inner = substr( $raw, 1, -1 );
		$len   = strlen( $inner );

		if ( ! preg_match( '/^[a-z0-9_]+/', $inner, $m ) ) {
			return null;
		}

		$name    = $m[0];
		$i       = strlen( $name );
		$primary = null;
		$args    = array();

		if ( $i < $len && ':' === $inner[ $i ] ) {
			++$i;

			if ( $i < $len && "'" === $inner[ $i ] ) {
				$quoted = self::read_quoted( $inner, $i, $len );
				if ( null === $quoted ) {
					return null;
				}
				list( $primary, $i ) = $quoted;
			} else {
				$stop = self::find_arg_start( $inner, $i, $len );
				$part = substr( $inner, $i, $stop - $i );

				// Braces and quotes outside a quoted value mean this is not a tag,
				// most likely CSS, JSON or a code sample.
				if ( preg_match( "/[{}']|[\r\n]/", $part ) ) {
					return null;
				}

				$primary = trim( $part );
				$i       = $stop;
			}
		}

		while ( $i < $len ) {
			if ( ! ctype_space( $inner[ $i ] ) ) {
				return null;
			}

			while ( $i < $len && ctype_space( $inner[ $i ] ) ) {
				++$i;
			}

			if ( $i >= $len ) {
				break;
			}

			if ( '@' !== $inner[ $i ] || ! preg_match( '/^[a-z0-9_]+/', substr( $inner, $i + 1 ), $km ) ) {
				return null;
			}

			$key = $km[0];
			$i  += 1 + strlen( $key );

			if ( $i < $len && ':' === $inner[ $i ] ) {
				++$i;

				if ( $i < $len && "'" === $inner[ $i ] ) {
					$quoted = self::read_quoted( $inner, $i, $len );
					if ( null === $quoted ) {
						return null;
					}
					list( $value, $i ) = $quoted;
				} else {
					$start = $i;
					while ( $i < $len && ! ctype_space( $inner[ $i ] ) ) {
						if ( false !== strpos( "{}'@", $inner[ $i ] ) ) {
							return null;
						}
						++$i;
					}
					$value = substr( $inner, $start, $i - $start );
				}

				$args[ $key ] = $value;
			} else {
				$args[ $key ] = true;
			}
		}

		return array(
			'name'    => $name,
			'primary' => $primary,
			'args'    => $args,
		);
	}

	/**
	 * Build the shortest tag string that parses back to the given parts.
	 *
	 * @param string                    $name    Tag name.
	 * @param string|null               $primary Primary value, or null for none.
	 * @param array<string, string|bool> $args   Named args; true is a flag, false or null is omitted.
	 * @return string
	 */
	public static function build( string $name, ?string $primary = null, array $args = array() ): string {
		$out = '{' . $name;

		if ( null !== $primary && '' !== $primary ) {
			$out .= ':' . ( self::primary_needs_quotes( $primary ) ? self::quote( $primary ) : $primary );
		}

		foreach ( $args as $key => $value ) {
			if ( null === $value || false === $value ) {
				continue;
			}

			$out .= ' @' . $key;

			if ( true !== $value ) {
				$value = (string) $value;
				$out  .= ':' . ( preg_match( "/^[^\\s{}'@]+$/", $value ) ? $value : self::quote( $value ) );
			}
		}

		return $out . '}';
	}

	/**
	 * Find the closing brace of a candidate tag, honoring quoted values.
	 *
	 * @param string $text   Full text.
	 * @param int    $start  Offset of the opening brace.
	 * @param int    $length strlen( $text ).
	 * @return int|null Offset of the closing brace, or null if there is none.
	 */
	private static function scan_to_close( string $text, int $start, int $length ): ?int {
		// A tag must open with a name character; this rejects "{ " and "{{" cheaply.
		if ( $start + 1 >= $length || ! preg_match( '/[a-z0-9_]/', $text[ $start + 1 ] ) ) {
			return null;
		}

		$in_quote = false;

		// An unterminated quote would otherwise scan to the end of the text for
		// every brace that precedes it.
		$limit = min( $length, $start + self::MAX_TAG_LENGTH );

		for ( $i = $start + 1; $i < $limit; $i++ ) {
			$c = $text[ $i ];

			if ( $in_quote ) {
				if ( '\\' === $c ) {
					++$i;
				} elseif ( "'" === $c ) {
					$in_quote = false;
				}
				continue;
			}

			if ( "'" === $c ) {
				$in_quote = true;
			} elseif ( '}' === $c ) {
				return $i;
			} elseif ( '{' === $c || "\n" === $c || "\r" === $c ) {
				return null;
			}
		}

		return null;
	}

	/**
	 * Read a single quoted value starting at the opening quote.
	 *
	 * @return array{0: string, 1: int}|null The unescaped value and the offset after the closing quote.
	 */
	private static function read_quoted( string $s, int $i, int $len ): ?array {
		$value = '';

		for ( ++$i; $i < $len; $i++ ) {
			$c = $s[ $i ];

			if ( '\\' === $c && $i + 1 < $len ) {
				$value .= $s[ ++$i ];
			} elseif ( "'" === $c ) {
				return array( $value, $i + 1 );
			} else {
				$value .= $c;
			}
		}

		return null;
	}

	/**
	 * Offset where the named args begin (whitespace followed by @), or the end.
	 */
	private static function find_arg_start( string $s, int $i, int $len ): int {
		if ( preg_match( '/\s+@/', $s, $m, PREG_OFFSET_CAPTURE, $i ) ) {
			return $m[0][1];
		}

		return $len;
	}

	private static function primary_needs_quotes( string $value ): bool {
		return "'" === $value[0]
			|| trim( $value ) !== $value
			|| (bool) preg_match( "/[{}'\\r\\n]|\\s@/", $value );
	}

	private static function quote( string $value ): string {
		return "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value ) . "'";
	}
}
