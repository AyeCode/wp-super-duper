<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * Replaces dynamic data tags in a string with their escaped values.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renderer {

	/** Object cache group for tag values kept between requests. */
	const CACHE_GROUP = 'sd_dynamic_data';

	/** @var array<string, mixed> Values of cached tags already resolved in this request. */
	private static array $memo = [];

	/**
	 * Replace every tag in a string.
	 *
	 * Text that is not a registered tag, or a tag given something it cannot take,
	 * is left exactly as written.
	 *
	 * @param string       $text    Text that may contain tags.
	 * @param Context|null $context What to resolve against; the current request when null.
	 * @return string
	 */
	public static function render( string $text, ?Context $context = null ): string {
		if ( false === strpos( $text, '{' ) ) {
			return $text;
		}

		$found = Grammar::find( $text );
		if ( ! $found ) {
			return $text;
		}

		$context = $context ?? new Context();

		// Back to front, so earlier offsets stay valid as lengths change.
		foreach ( array_reverse( $found ) as $match ) {
			$text = substr_replace( $text, self::render_tag( $match['tag'], $match['raw'], $context ), $match['offset'], $match['length'] );
		}

		return $text;
	}

	/**
	 * Render one parsed tag.
	 *
	 * @param array   $parsed  Output of Grammar::parse().
	 * @param string  $raw     The tag as written.
	 * @param Context $context What to resolve against.
	 * @return string Escaped output.
	 */
	public static function render_tag( array $parsed, string $raw, Context $context ): string {
		$def      = Registry::get( $parsed['name'] );
		$resolved = $def ? Registry::resolve_args( $def, $parsed ) : null;

		if ( ! $def || ! $resolved ) {
			/**
			 * Filter the output for text that looks like a tag but is not one that can render.
			 *
			 * Return '' to remove unknown tags instead of leaving them visible.
			 *
			 * @param string  $raw     The text as written.
			 * @param array   $parsed  Parsed name, primary and args.
			 * @param Context $context Render context.
			 */
			return (string) apply_filters( 'sd_dynamic_data_unknown_tag', $raw, $parsed, $context );
		}

		$args      = $resolved['args'];
		$modifiers = $resolved['modifiers'];
		$value     = null;

		if ( ! self::missing_required( $def, $args ) ) {
			// The definition is the third argument, so one method can serve several tags
			// by name. Callbacks that take two parameters are unaffected.
			$value = self::value( $def, $args, $context );
		}

		$fallback = isset( $modifiers['fallback'] ) ? (string) $modifiers['fallback'] : '';

		if ( null === $value || false === $value || '' === $value || ! is_scalar( $value ) ) {
			return esc_html( $fallback );
		}

		$returns = $def['returns'];

		if ( ! empty( $modifiers['words'] ) && $modifiers['words'] > 0 ) {
			$value   = wp_trim_words( wp_strip_all_tags( (string) $value ), (int) $modifiers['words'], '&hellip;' );
			$returns = 'text';
		}

		if ( 'number' === $returns && is_numeric( $value ) && ! empty( $modifiers['round'] ) && $modifiers['round'] > 0 ) {
			$value = floor( $value / $modifiers['round'] ) * $modifiers['round'];
		}

		$output = self::escape( $value, $returns );

		if ( '' === $output ) {
			return esc_html( $fallback );
		}

		if ( 'number' === $returns && ! empty( $modifiers['thousands'] ) ) {
			$point  = strpos( $output, '.' );
			$output = esc_html( number_format_i18n( (float) $output, false === $point ? 0 : strlen( $output ) - $point - 1 ) );
		}

		if ( ! empty( $modifiers['link'] ) && $def['link'] ) {
			$url = (string) call_user_func( $def['link'], $args, $context, $def );

			if ( '' !== $url ) {
				$output = sprintf(
					'<a href="%s"%s>%s</a>',
					esc_url( $url ),
					! empty( $modifiers['new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '',
					$output
				);
			}
		}

		/**
		 * Filter a tag's final, escaped output.
		 *
		 * @param string  $output  Escaped output.
		 * @param array   $parsed  Parsed name, primary and args.
		 * @param array   $args    Resolved args.
		 * @param Context $context Render context.
		 */
		return (string) apply_filters( 'sd_dynamic_data_output', $output, $parsed, $args, $context );
	}

	/**
	 * A tag's raw value, through the cache when the tag asks for one.
	 *
	 * A tag with a `cache` key is resolved once per request for the same args, and
	 * with a cache above 0 the value is kept that many seconds between requests:
	 * in the object cache where the site has one, otherwise in a transient.
	 *
	 * @param array   $def     Normalized tag definition.
	 * @param array   $args    Resolved args.
	 * @param Context $context What to resolve against.
	 * @return mixed
	 */
	private static function value( array $def, array $args, Context $context ) {
		if ( null === $def['cache'] ) {
			// The definition is the third argument, so one method can serve several tags
			// by name. Callbacks that take two parameters are unaffected.
			return call_user_func( $def['callback'], $args, $context, $def );
		}

		$key = self::cache_key( $def, $args, $context );

		if ( array_key_exists( $key, self::$memo ) ) {
			return self::$memo[ $key ];
		}

		/**
		 * Filter how long a cached tag's value is kept between requests.
		 *
		 * Return 0 to resolve it once per request only.
		 *
		 * @param int    $ttl  Seconds, from the tag's `cache` key.
		 * @param string $name Tag name.
		 * @param array  $args Resolved args.
		 */
		$ttl = (int) apply_filters( 'sd_dynamic_data_cache_ttl', $def['cache'], $def['name'], $args );

		if ( $ttl > 0 ) {
			$hit = wp_using_ext_object_cache() ? wp_cache_get( $key, self::CACHE_GROUP ) : get_transient( 'sd_dd_' . $key );

			// Wrapped, so a stored empty value is still a hit.
			if ( is_array( $hit ) && array_key_exists( 'v', $hit ) ) {
				self::$memo[ $key ] = $hit['v'];

				return $hit['v'];
			}
		}

		$value              = call_user_func( $def['callback'], $args, $context, $def );
		$value              = is_scalar( $value ) ? $value : null;
		self::$memo[ $key ] = $value;

		// An editor preview may resolve against a demo object, which must not be kept.
		if ( $ttl > 0 && ! \AyeCode\SuperDuper\Utils::is_preview() ) {
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $key, [ 'v' => $value ], self::CACHE_GROUP, $ttl );
			} else {
				set_transient( 'sd_dd_' . $key, [ 'v' => $value ], $ttl );
			}
		}

		return $value;
	}

	/**
	 * Drop a cached tag's value, for an addon that knows when its number changed.
	 *
	 * @param string       $name    Tag name.
	 * @param array        $args    Args as written, key => value. Defaults are filled in.
	 * @param Context|null $context What the tag resolved against; the current request when null.
	 */
	public static function forget( string $name, array $args = [], ?Context $context = null ): void {
		$def      = Registry::get( $name );
		$resolved = $def ? Registry::resolve_args(
			$def,
			[
				'name'    => $name,
				'primary' => null,
				'args'    => $args,
			]
		) : null;

		if ( ! $resolved || null === $def['cache'] ) {
			return;
		}

		$key = self::cache_key( $def, $resolved['args'], $context ?? new Context() );

		unset( self::$memo[ $key ] );
		wp_cache_delete( $key, self::CACHE_GROUP );
		delete_transient( 'sd_dd_' . $key );
	}

	/**
	 * The key a cached tag's value is stored under: its name, its args and
	 * whatever its `vary` says the value depends on.
	 */
	private static function cache_key( array $def, array $args, Context $context ): string {
		$vary = $def['vary'];

		if ( is_callable( $vary ) ) {
			$vary = (string) call_user_func( $vary, $args, $context );
		} else {
			$ids = [];
			foreach ( $vary as $object ) {
				switch ( $object ) {
					case 'post':
						$ids[] = 'p' . ( $context->post() ? $context->post()->ID : 0 );
						break;
					case 'term':
						$ids[] = 't' . ( $context->term() ? $context->term()->term_id : 0 );
						break;
					case 'user':
						$ids[] = 'u' . ( $context->user() ? $context->user()->ID : 0 );
						break;
				}
			}
			$vary = implode( ',', $ids );
		}

		return md5( $def['name'] . '|' . wp_json_encode( $args ) . '|' . $vary );
	}

	/**
	 * Escape a value for the kind of data the tag says it returns.
	 *
	 * @param string|int|float|bool $value   Raw value.
	 * @param string                $returns text | html | url | number.
	 */
	public static function escape( $value, string $returns ): string {
		switch ( $returns ) {
			case 'html':
				return wp_kses_post( (string) $value );

			case 'url':
				return esc_url( (string) $value );

			case 'number':
				return is_numeric( $value ) ? (string) ( $value + 0 ) : '';

			default:
				return esc_html( (string) $value );
		}
	}

	private static function missing_required( array $def, array $args ): bool {
		foreach ( $def['args'] as $arg ) {
			if ( $arg['required'] && ( null === $args[ $arg['key'] ] || '' === $args[ $arg['key'] ] ) ) {
				return true;
			}
		}

		return false;
	}
}
