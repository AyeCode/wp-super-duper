<?php

namespace AyeCode\SuperDuper\Ai;

/**
 * Parser and evaluator for the `element_require` expression syntax.
 *
 * This is a deliberate port of the editor's own implementation,
 * parseLegacyRequireString() and executeLogicObject() in
 * assets/js/super-duper-block-editor.js. The editor decides which fields a user
 * can actually see, so it is the reference: any divergence here would mean the
 * AI schema documents a grammar the editor does not enforce.
 *
 * The grammar is what the shortcode builder gets from evaluating the string as
 * JavaScript: nested brackets, && binding tighter than ||, ! in front of a term
 * or a bracket group, and loose comparisons so a checkbox passes `=="1"` whether
 * it is a boolean (block editor) or a "1" input (shortcode builder). A term the
 * tokenizer cannot read is skipped rather than hiding the field, and that is the
 * one lossy case: it is recorded in the `issues` key returned by parse() so
 * callers can tell an exact parse from a lossy one.
 */
class RequireExpression {

	/** A token that is not a `[%name%]` placeholder sat where a term should be and was skipped. */
	const ISSUE_DROPPED_TERM = 'dropped_term';

	/**
	 * Parse an expression into the editor's structured logic form.
	 *
	 * @param string $expression Raw element_require string.
	 * @return array|null {
	 *     @type string $operator   'AND' or 'OR'.
	 *     @type array  $conditions Conditions ['attribute', 'compare', 'value', 'negate'?] or nested
	 *                              groups carrying their own 'operator' and 'conditions'.
	 *     @type bool   $exact      True when every term was read.
	 *     @type array  $issues     Any ISSUE_* constants that applied.
	 * }
	 * Null when the expression is empty or yields no terms.
	 */
	public static function parse( string $expression ): ?array {
		$tokens = self::tokenize( $expression );

		if ( empty( $tokens ) ) {
			return null;
		}

		$issues = [];
		$pos    = 0;
		$logic  = self::parse_or( $tokens, $pos, $issues );

		if ( null === $logic ) {
			return [
				'operator'   => 'AND',
				'conditions' => [],
				'exact'      => false,
				'issues'     => [ self::ISSUE_DROPPED_TERM ],
			];
		}

		if ( ! isset( $logic['operator'] ) ) {
			$logic = [
				'operator'   => 'AND',
				'conditions' => [ $logic ],
			];
		}

		$logic['exact']  = empty( $issues );
		$logic['issues'] = array_values( array_unique( $issues ) );

		return $logic;
	}

	/**
	 * Evaluate a requirement against a set of attribute values.
	 *
	 * Accepts either a raw expression string or an already-structured logic
	 * array, mirroring the editor's evaluateRequirement() dispatcher.
	 *
	 * @param string|array|null $requirement Expression string or logic array.
	 * @param array             $attributes  Attribute values keyed by name.
	 * @return bool True when the requirement is satisfied (or absent).
	 */
	public static function evaluate( $requirement, array $attributes ): bool {
		if ( is_array( $requirement ) ) {
			return self::execute( $requirement, $attributes );
		}

		if ( is_string( $requirement ) && '' !== trim( $requirement ) ) {
			return self::execute( self::parse( $requirement ), $attributes );
		}

		return true;
	}

	/**
	 * Reduce a parsed expression to the normalized form used in the AI schema.
	 *
	 * Only a flat chain of plain equality or truthiness terms translates into the
	 * attribute => value shape the schema uses. Nested groups, negation,
	 * inequality and a lossy parse are not described: a rule the model cannot
	 * apply exactly is worse than none, so callers fall back to evaluating the
	 * original expression.
	 *
	 * @param string $expression Raw element_require string.
	 * @return array|null ['requires' => [...]] or ['requires_any' => [...]], null when not describable.
	 */
	public static function to_schema( string $expression ): ?array {
		$parsed = self::parse( $expression );

		if ( null === $parsed || ! $parsed['exact'] || empty( $parsed['conditions'] ) || ! empty( $parsed['negate'] ) ) {
			return null;
		}

		$clauses = [];

		foreach ( $parsed['conditions'] as $condition ) {
			if ( isset( $condition['operator'] ) || ! empty( $condition['negate'] ) ) {
				return null;
			}

			if ( 'exists' === $condition['compare'] ) {
				$clauses[] = [ $condition['attribute'] => '*' ];
				continue;
			}

			if ( '==' !== $condition['compare'] ) {
				return null;
			}

			$clauses[] = [ $condition['attribute'] => $condition['value'] ];
		}

		if ( 'OR' === $parsed['operator'] && count( $clauses ) > 1 ) {
			return [ 'requires_any' => $clauses ];
		}

		return [ 'requires' => array_merge( ...$clauses ) ];
	}

	/**
	 * Split an expression into tokens.
	 *
	 * Mirrors the editor's token pattern: placeholders, the boolean and comparison
	 * operators, brackets, quoted strings, numbers, true/false and any other bare
	 * word (kept as a value so `[%x%]==col` still reads).
	 *
	 * @param string $expression
	 * @return array List of ['type' => 'attr'|'op'|'value', ...].
	 */
	private static function tokenize( string $expression ): array {
		$pattern = '/\s*(\[%([\w-]+)%(:checked)?\]|&&|\|\||===|!==|==|!=|<=|>=|<|>|!|\(|\)|"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\'|-?\d+(?:\.\d+)?|true|false|[^\s()!&|=<>"\']+)/A';
		$tokens  = [];
		$offset  = 0;
		$length  = strlen( $expression );

		while ( $offset < $length && preg_match( $pattern, $expression, $m, 0, $offset ) ) {
			$offset += strlen( $m[0] );
			$raw     = $m[1];

			if ( isset( $m[2] ) && '' !== $m[2] ) {
				$tokens[] = [
					'type'    => 'attr',
					'name'    => $m[2],
					'checked' => ! empty( $m[3] ),
				];
			} elseif ( '"' === $raw[0] || "'" === $raw[0] ) {
				$tokens[] = [
					'type'  => 'value',
					'value' => substr( $raw, 1, -1 ),
				];
			} elseif ( preg_match( '/^-?\d/', $raw ) ) {
				$tokens[] = [
					'type'  => 'value',
					'value' => (float) $raw,
				];
			} elseif ( 'true' === $raw || 'false' === $raw ) {
				$tokens[] = [
					'type'  => 'value',
					'value' => 'true' === $raw,
				];
			} elseif ( preg_match( '/^(&&|\|\||===|!==|==|!=|<=|>=|<|>|!|\(|\))$/', $raw ) ) {
				$tokens[] = [
					'type'  => 'op',
					'value' => $raw,
				];
			} else {
				$tokens[] = [
					'type'  => 'value',
					'value' => $raw,
				];
			}

			if ( '' === $m[0] ) {
				break;
			}
		}

		return $tokens;
	}

	/**
	 * Whether the next token is the given operator.
	 *
	 * @param array  $tokens
	 * @param int    $pos
	 * @param string $op
	 * @return bool
	 */
	private static function is_op( array $tokens, int $pos, string $op ): bool {
		return isset( $tokens[ $pos ] ) && 'op' === $tokens[ $pos ]['type'] && $op === $tokens[ $pos ]['value'];
	}

	/**
	 * or := and ('||' and)*
	 *
	 * @param array $tokens
	 * @param int   $pos    Advanced in place.
	 * @param array $issues Appended in place.
	 * @return array|null A condition, a group, or null when nothing was read.
	 */
	private static function parse_or( array $tokens, int &$pos, array &$issues ): ?array {
		$conditions = [];
		$group      = self::parse_and( $tokens, $pos, $issues );

		if ( null !== $group ) {
			$conditions[] = $group;
		}

		while ( self::is_op( $tokens, $pos, '||' ) ) {
			++$pos;
			$group = self::parse_and( $tokens, $pos, $issues );

			if ( null !== $group ) {
				$conditions[] = $group;
			}
		}

		if ( empty( $conditions ) ) {
			return null;
		}

		return 1 === count( $conditions ) ? $conditions[0] : [
			'operator'   => 'OR',
			'conditions' => $conditions,
		];
	}

	/**
	 * and := term ('&&' term)*
	 *
	 * @param array $tokens
	 * @param int   $pos
	 * @param array $issues
	 * @return array|null
	 */
	private static function parse_and( array $tokens, int &$pos, array &$issues ): ?array {
		$conditions = [];
		$term       = self::parse_term( $tokens, $pos, $issues );

		if ( null !== $term ) {
			$conditions[] = $term;
		}

		while ( self::is_op( $tokens, $pos, '&&' ) ) {
			++$pos;
			$term = self::parse_term( $tokens, $pos, $issues );

			if ( null !== $term ) {
				$conditions[] = $term;
			}
		}

		if ( empty( $conditions ) ) {
			return null;
		}

		return 1 === count( $conditions ) ? $conditions[0] : [
			'operator'   => 'AND',
			'conditions' => $conditions,
		];
	}

	/**
	 * term := '!' term | '(' or ')' | attr (op value)?
	 *
	 * @param array $tokens
	 * @param int   $pos
	 * @param array $issues
	 * @return array|null
	 */
	private static function parse_term( array $tokens, int &$pos, array &$issues ): ?array {
		if ( self::is_op( $tokens, $pos, '!' ) ) {
			++$pos;
			$inner = self::parse_term( $tokens, $pos, $issues );

			if ( null !== $inner ) {
				$inner['negate'] = empty( $inner['negate'] );
			}

			return $inner;
		}

		if ( self::is_op( $tokens, $pos, '(' ) ) {
			++$pos;
			$inner = self::parse_or( $tokens, $pos, $issues );

			if ( self::is_op( $tokens, $pos, ')' ) ) {
				++$pos;
			}

			return $inner;
		}

		if ( ! isset( $tokens[ $pos ] ) ) {
			return null;
		}

		$token = $tokens[ $pos++ ];

		if ( 'attr' !== $token['type'] ) {
			// A stray value or operator: skipped rather than hiding the field over a typo.
			$issues[] = self::ISSUE_DROPPED_TERM;
			return null;
		}

		$condition = [
			'attribute' => $token['name'],
			'compare'   => 'exists',
			'value'     => null,
		];

		if ( $token['checked'] ) {
			return $condition;
		}

		if ( isset( $tokens[ $pos ] ) && 'op' === $tokens[ $pos ]['type'] && preg_match( '/^(===|!==|==|!=|<=|>=|<|>)$/', $tokens[ $pos ]['value'] ) ) {
			$condition['compare'] = str_replace( [ '===', '!==' ], [ '==', '!=' ], $tokens[ $pos ]['value'] );
			++$pos;
			$condition['value'] = '';

			if ( isset( $tokens[ $pos ] ) && 'value' === $tokens[ $pos ]['type'] ) {
				$condition['value'] = $tokens[ $pos++ ]['value'];
			}
		}

		return $condition;
	}

	/**
	 * Run a parsed logic array against attribute values.
	 *
	 * @param array|null $logic      Structured logic from parse().
	 * @param array      $attributes Attribute values keyed by name.
	 * @return bool
	 */
	private static function execute( ?array $logic, array $attributes ): bool {
		if ( empty( $logic['conditions'] ) ) {
			return true;
		}

		$results = [];

		foreach ( $logic['conditions'] as $condition ) {
			// Nested logic arrays carry their own operator.
			$results[] = isset( $condition['operator'] )
				? self::execute( $condition, $attributes )
				: self::check_condition( $condition, $attributes );
		}

		$result = 'OR' === ( $logic['operator'] ?? 'AND' )
			? in_array( true, $results, true )
			: ! in_array( false, $results, true );

		return empty( $logic['negate'] ) ? $result : ! $result;
	}

	/**
	 * Test a single condition.
	 *
	 * Mirrors the editor's checkCondition(): null, true and false on either side
	 * become '', '1' and '' before a loose JavaScript style comparison, so a
	 * checkbox matches `=="1"` and `:checked` whichever form it arrives in.
	 *
	 * @param array $condition  ['attribute', 'compare', 'value', 'negate'?].
	 * @param array $attributes Attribute values keyed by name.
	 * @return bool
	 */
	private static function check_condition( array $condition, array $attributes ): bool {
		$actual   = self::normalize( $attributes[ $condition['attribute'] ] ?? null );
		$expected = self::normalize( $condition['value'] ?? null );
		$compare  = str_replace( [ '===', '!==' ], [ '==', '!=' ], (string) ( $condition['compare'] ?? 'exists' ) );

		switch ( $compare ) {
			case '==':
				$result = self::loose_equal( $actual, $expected );
				break;
			case '!=':
				$result = ! self::loose_equal( $actual, $expected );
				break;
			case '>':
				$result = self::loose_compare( $actual, $expected ) > 0;
				break;
			case '<':
				$result = self::loose_compare( $actual, $expected ) < 0;
				break;
			case '>=':
				$result = self::loose_compare( $actual, $expected ) >= 0;
				break;
			case '<=':
				$result = self::loose_compare( $actual, $expected ) <= 0;
				break;
			case 'exists':
				$result = self::is_truthy( $actual );
				break;
			default:
				$result = false;
		}

		return empty( $condition['negate'] ) ? $result : ! $result;
	}

	/**
	 * Fold null and booleans into the strings the editor compares with.
	 *
	 * @param mixed $value
	 * @return string|int|float
	 */
	private static function normalize( $value ) {
		if ( null === $value || false === $value ) {
			return '';
		}

		if ( true === $value ) {
			return '1';
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		return (string) $value;
	}

	/**
	 * JavaScript loose equality for the string and number values left after normalize().
	 *
	 * Two strings compare as strings. A string against a number is converted the
	 * JavaScript way: blank is 0, a numeric string is its number, anything else
	 * is NaN and never equal. PHP 8 differs on the blank case ('' == 0 is false),
	 * so this is spelled out rather than left to ==.
	 *
	 * @param string|int|float $a
	 * @param string|int|float $b
	 * @return bool
	 */
	private static function loose_equal( $a, $b ): bool {
		if ( is_string( $a ) && is_string( $b ) ) {
			return $a === $b;
		}

		$a = self::to_number( $a );
		$b = self::to_number( $b );

		return null !== $a && null !== $b && $a === $b;
	}

	/**
	 * Ordering for < and >: numeric when both sides read as numbers, string order otherwise.
	 *
	 * @param string|int|float $a
	 * @param string|int|float $b
	 * @return int
	 */
	private static function loose_compare( $a, $b ): int {
		$na = self::to_number( $a );
		$nb = self::to_number( $b );

		if ( null !== $na && null !== $nb && ( ! is_string( $a ) || ! is_string( $b ) || ( '' !== trim( $a ) && '' !== trim( $b ) ) ) ) {
			return $na <=> $nb;
		}

		return strcmp( (string) $a, (string) $b );
	}

	/**
	 * JavaScript Number() for a string or number, null standing in for NaN.
	 *
	 * @param string|int|float $value
	 * @return float|null
	 */
	private static function to_number( $value ): ?float {
		if ( is_int( $value ) || is_float( $value ) ) {
			return (float) $value;
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return 0.0;
		}

		return is_numeric( $value ) ? (float) $value : null;
	}

	/**
	 * JavaScript truthiness.
	 *
	 * Deliberately not PHP's: the string "0" is falsy in PHP and truthy in JS,
	 * and "0" is a real value here (margin and padding sizes, column counts).
	 * Using PHP's rules would hide fields the editor shows.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private static function is_truthy( $value ): bool {
		if ( null === $value || false === $value || '' === $value ) {
			return false;
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return 0.0 !== (float) $value;
		}

		return true;
	}
}
