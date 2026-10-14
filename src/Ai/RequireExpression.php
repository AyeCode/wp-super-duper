<?php

namespace AyeCode\SuperDuper\Ai;

/**
 * Parser and evaluator for the legacy `element_require` expression syntax.
 *
 * This is a deliberate port of the editor's own implementation —
 * parseLegacyRequireString() and executeLogicObject() in
 * assets/js/super-duper-block-editor.js. The editor decides which fields a user
 * can actually see, so it is the reference: any divergence here would mean the
 * AI schema documents a grammar the editor does not enforce.
 *
 * That includes reproducing the parser's known limitations. It splits on
 * && / || flatly with no precedence, strips only a single leading and trailing
 * paren, and silently discards terms it cannot match (bare negation such as
 * `![%foo%]`). Those quirks are preserved but recorded — see the `issues` key
 * returned by parse() — so callers can tell an exact parse from a lossy one
 * instead of trusting all of them equally.
 */
class RequireExpression {

	/**
	 * A term did not match the condition pattern and was dropped.
	 * Bare negation (`![%foo%]`) is the common cause.
	 */
	const ISSUE_DROPPED_TERM = 'dropped_term';

	/** Both && and || appear; the flat parse keeps only the last operator seen. */
	const ISSUE_MIXED_OPERATORS = 'mixed_operators';

	/** Parens remain after the single outer strip, so grouping was lost. */
	const ISSUE_INTERIOR_PARENS = 'interior_parens';

	/**
	 * Parse an expression into the editor's structured logic form.
	 *
	 * @param string $expression Raw element_require string.
	 * @return array|null {
	 *     @type string $operator   'AND' or 'OR'.
	 *     @type array  $conditions List of ['attribute', 'compare', 'value'].
	 *     @type bool   $exact      True when nothing was lost or flattened.
	 *     @type array  $issues     Any ISSUE_* constants that applied.
	 * }
	 * Null when the expression is empty or yields no terms.
	 */
	public static function parse( string $expression ): ?array {
		if ( '' === trim( $expression ) ) {
			return null;
		}

		// Strip one leading and one trailing paren, then the [% %] placeholders.
		// Mirrors: .replace(/^[(]|[)]$/g, '').replace(/\[%|%]/g, '')
		$cleaned = preg_replace( '/^\(|\)$/', '', trim( $expression ) );
		$cleaned = str_replace( array( '[%', '%]' ), '', $cleaned );

		// Split on the boolean operators, keeping them as tokens.
		$parts = preg_split( '/(&&|\|\|)/', $cleaned, -1, PREG_SPLIT_DELIM_CAPTURE );
		$parts = array_filter( array_map( 'trim', (array) $parts ), static fn( $part ) => '' !== $part );

		if ( empty( $parts ) ) {
			return null;
		}

		$issues     = array();
		$conditions = array();
		$operator   = 'AND';
		$seen_and   = false;
		$seen_or    = false;

		// Grouping the parser cannot honour: any paren surviving the outer strip.
		if ( false !== strpos( $cleaned, '(' ) || false !== strpos( $cleaned, ')' ) ) {
			$issues[] = self::ISSUE_INTERIOR_PARENS;
		}

		foreach ( $parts as $part ) {
			if ( '&&' === $part ) {
				$operator = 'AND';
				$seen_and = true;
				continue;
			}

			if ( '||' === $part ) {
				$operator = 'OR';
				$seen_or  = true;
				continue;
			}

			// Mirrors: /^(\w+)\s*([!=<>]+)?\s*(.*)?$/
			// A leading '!' is not a word character, so negated terms fall through
			// here and are dropped — exactly as the editor drops them.
			if ( ! preg_match( '/^(\w+)\s*([!=<>]+)?\s*(.*)?$/', $part, $match ) ) {
				$issues[] = self::ISSUE_DROPPED_TERM;
				continue;
			}

			$compare = isset( $match[2] ) && '' !== $match[2] ? $match[2] : '';

			if ( '' === $compare ) {
				$conditions[] = array(
					'attribute' => $match[1],
					'compare'   => 'exists',
					'value'     => null,
				);
				continue;
			}

			$conditions[] = array(
				'attribute' => $match[1],
				'compare'   => str_replace( array( '==', '!=' ), array( '===', '!==' ), $compare ),
				'value'     => self::cast_value( $match[3] ?? '' ),
			);
		}

		if ( $seen_and && $seen_or ) {
			$issues[] = self::ISSUE_MIXED_OPERATORS;
		}

		if ( empty( $conditions ) ) {
			// Every term was dropped. The editor treats this as "always show".
			return array(
				'operator'   => $operator,
				'conditions' => array(),
				'exact'      => false,
				'issues'     => array_values( array_unique( array_merge( $issues, array( self::ISSUE_DROPPED_TERM ) ) ) ),
			);
		}

		$issues = array_values( array_unique( $issues ) );

		return array(
			'operator'   => $operator,
			'conditions' => $conditions,
			'exact'      => empty( $issues ),
			'issues'     => $issues,
		);
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
	 * Only unambiguous parses are described to the model — a lossy parse would
	 * teach it a rule the validator does not actually apply. Callers fall back
	 * to evaluating the original expression in that case.
	 *
	 * @param string $expression Raw element_require string.
	 * @return array|null ['requires' => [...]] or ['requires_any' => [...]], null when not describable.
	 */
	public static function to_schema( string $expression ): ?array {
		$parsed = self::parse( $expression );

		if ( null === $parsed || ! $parsed['exact'] || empty( $parsed['conditions'] ) ) {
			return null;
		}

		$clauses = array();

		foreach ( $parsed['conditions'] as $condition ) {
			// Only plain equality and truthiness translate cleanly into the
			// attribute => value shape the schema uses.
			if ( 'exists' === $condition['compare'] ) {
				$clauses[] = array( $condition['attribute'] => '*' );
				continue;
			}

			if ( '===' !== $condition['compare'] ) {
				return null;
			}

			$clauses[] = array( $condition['attribute'] => $condition['value'] );
		}

		if ( 'OR' === $parsed['operator'] && count( $clauses ) > 1 ) {
			return array( 'requires_any' => $clauses );
		}

		return array( 'requires' => array_merge( ...$clauses ) );
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

		$results = array();

		foreach ( $logic['conditions'] as $condition ) {
			// Nested logic arrays carry their own operator.
			$results[] = isset( $condition['operator'] )
				? self::execute( $condition, $attributes )
				: self::check_condition( $condition, $attributes );
		}

		if ( 'OR' === ( $logic['operator'] ?? 'AND' ) ) {
			return in_array( true, $results, true );
		}

		return ! in_array( false, $results, true );
	}

	/**
	 * Test a single condition.
	 *
	 * @param array $condition  ['attribute', 'compare', 'value'].
	 * @param array $attributes Attribute values keyed by name.
	 * @return bool
	 */
	private static function check_condition( array $condition, array $attributes ): bool {
		$actual  = $attributes[ $condition['attribute'] ] ?? null;
		$compare = $condition['compare'] ?? '';
		$value   = $condition['value'] ?? null;

		// Comparing against "" or false also matches unset/null, so a field
		// gated on `[%foo%]==""` shows before the attribute has ever been set.
		if ( '===' === $compare && ( '' === $value || false === $value ) ) {
			return false === $actual || '' === $actual || null === $actual;
		}

		if ( '!==' === $compare && ( '' === $value || false === $value ) ) {
			return false !== $actual && '' !== $actual && null !== $actual;
		}

		switch ( $compare ) {
			case '===':
				return self::identical( $actual, $value );
			case '!==':
				return ! self::identical( $actual, $value );
			case '>':
				return $actual > $value;
			case '<':
				return $actual < $value;
			case '>=':
				return $actual >= $value;
			case '<=':
				return $actual <= $value;
			case 'exists':
				return self::is_truthy( $actual );
			default:
				return false;
		}
	}

	/**
	 * Strict equality with JavaScript's single numeric type accounted for.
	 *
	 * PHP separates int and float where JS does not, so 5 and 5.0 must compare
	 * equal here or every numeric condition would fail against a parsed value.
	 *
	 * @param mixed $actual
	 * @param mixed $expected
	 * @return bool
	 */
	private static function identical( $actual, $expected ): bool {
		if ( ( is_int( $actual ) || is_float( $actual ) ) && ( is_int( $expected ) || is_float( $expected ) ) ) {
			return (float) $actual === (float) $expected;
		}

		return $actual === $expected;
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
			return 0 != $value; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- NaN/0.0 parity with JS.
		}

		return true;
	}

	/**
	 * Coerce a literal from the expression into a PHP value.
	 *
	 * Mirrors the editor's castValue(): booleans, then numbers, then a string
	 * with any surrounding quotes removed.
	 *
	 * @param string $raw
	 * @return bool|float|string
	 */
	private static function cast_value( string $raw ) {
		$raw = trim( $raw );

		if ( 'true' === $raw ) {
			return true;
		}

		if ( 'false' === $raw ) {
			return false;
		}

		// JS: !isNaN(parseFloat(val)) && isFinite(val) — a quoted "5" stays a
		// string because isFinite('"5"') is false.
		if ( '' !== $raw && is_numeric( $raw ) ) {
			return (float) $raw;
		}

		return preg_replace( '/^[\'"]|[\'"]$/', '', $raw );
	}
}
