<?php

namespace AyeCode\SuperDuper\Ai;

use AyeCode\SuperDuper\Utils;

/**
 * The single source of truth for how block attributes become Bootstrap classes.
 *
 * This is a deliberate mirror of Utils::build_aui_class() — the render code that
 * turns attribute values into CSS classes — read in reverse. The converter
 * (HTML → blocks) needs to know, for every styling attribute, exactly which
 * class a value produces, and build_aui_class is where that truth lives. Any
 * divergence here would make the AI schema advertise a class mapping the front
 * end does not actually emit, so this class must be kept honest against it (see
 * the drift test in tests/ai). It is the counterpart to RequireExpression, which
 * mirrors the editor's field-visibility JS.
 *
 * Three kinds of styling attribute exist:
 *
 *  - Token attrs (`mt`, `bg`, `col_md`): the stored value is a token and the
 *    class is `prefix + value` (`mt` "3" → `mt-3`, `bg` "primary" → `bg-primary`).
 *    The prefix is not guessable — margin-right renders `me-`, not `mr-` — so it
 *    is enumerated in PREFIXES.
 *  - Literal attrs (`text_align`, `display`, `shadow`): the stored value already
 *    IS the class (`text-center`, `d-none`, `shadow-lg`). These come from
 *    Utils::get_class_build_keys() plus the text_align/display families that
 *    build_aui_class handles by hand.
 *  - Exception attrs: a checkbox or special-cased attribute whose class is fixed
 *    and carries no derivable enum (`text_justify` → `text-justify`). Listed in
 *    exceptions() and absolute_positions().
 *
 * Pure and stateless.
 */
class ClassMap {

	/**
	 * Token attribute → class prefix. `class = prefix + value`.
	 *
	 * Mirrors the margin/padding/column/background/text-color blocks of
	 * build_aui_class(). Note the Bootstrap logical-property prefixes: margin and
	 * padding right/left render `me-`/`ms-` and `pe-`/`ps-`, never `mr-`/`ml-`.
	 */
	const PREFIXES = array(
		// Margins.
		'mt'              => 'mt-',
		'mt_md'           => 'mt-md-',
		'mt_lg'           => 'mt-lg-',
		'mr'              => 'me-',
		'mr_md'           => 'me-md-',
		'mr_lg'           => 'me-lg-',
		'mb'              => 'mb-',
		'mb_md'           => 'mb-md-',
		'mb_lg'           => 'mb-lg-',
		'ml'              => 'ms-',
		'ml_md'           => 'ms-md-',
		'ml_lg'           => 'ms-lg-',

		// Padding.
		'pt'              => 'pt-',
		'pt_md'           => 'pt-md-',
		'pt_lg'           => 'pt-lg-',
		'pr'              => 'pe-',
		'pr_md'           => 'pe-md-',
		'pr_lg'           => 'pe-lg-',
		'pb'              => 'pb-',
		'pb_md'           => 'pb-md-',
		'pb_lg'           => 'pb-lg-',
		'pl'              => 'ps-',
		'pl_md'           => 'ps-md-',
		'pl_lg'           => 'ps-lg-',

		// Grid.
		'row_cols'        => 'row-cols-',
		'row_cols_md'     => 'row-cols-md-',
		'row_cols_lg'     => 'row-cols-lg-',
		'col'             => 'col-',
		'col_md'          => 'col-md-',
		'col_lg'          => 'col-lg-',

		// Colour tokens.
		'bg'              => 'bg-',
		'badge_bg'        => 'bg-',
		'text_color'      => 'text-',

		// Border radius size.
		'rounded_size'    => 'rounded-',
		'rounded_size_md' => 'rounded-md-',
		'rounded_size_lg' => 'rounded-lg-',
	);

	/**
	 * Attribute names that must never participate in class mapping even though
	 * they appear in the class-build key set: internal plumbing, the css_class
	 * spillover target itself, and `container`, whose value drives structural
	 * role (row/col/card) and is resolved by the converter, not consumed as a
	 * utility class.
	 */
	const NON_CLASS_KEYS = array( 'styleid', 'css_class', 'container' );

	/**
	 * The class prefix for a token attribute, or null if it is not a token attr.
	 *
	 * @param string $attr
	 * @return string|null
	 */
	public static function prefix_for( string $attr ): ?string {
		return self::PREFIXES[ $attr ] ?? null;
	}

	/**
	 * All token attribute → prefix pairs.
	 *
	 * @return array<string, string>
	 */
	public static function prefixes(): array {
		return self::PREFIXES;
	}

	/**
	 * Attribute names whose select-enum values are literal utility classes
	 * (value === class). Derived from Utils::get_class_build_keys() — expanding
	 * the `-MTD` responsive markers into base/_md/_lg — plus the text_align and
	 * display families that build_aui_class emits outside that list. Non-class
	 * plumbing keys are removed.
	 *
	 * @return string[]
	 */
	public static function literal_attrs(): array {
		$keys = array();

		foreach ( Utils::get_class_build_keys() as $key ) {
			if ( '-MTD' === substr( $key, -4 ) ) {
				$base   = substr( $key, 0, -4 );
				$keys[] = $base;
				$keys[] = $base . '_md';
				$keys[] = $base . '_lg';
				continue;
			}

			$keys[] = $key;
		}

		// Handled by hand in build_aui_class, not present in the build-key list.
		$keys = array_merge(
			$keys,
			array( 'text_align', 'text_align_md', 'text_align_lg', 'display', 'display_md', 'display_lg' )
		);

		$keys = array_diff( $keys, self::NON_CLASS_KEYS );

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Fixed class → [attribute => value] for classes build_aui_class emits from
	 * checkboxes and special-cased attributes with no derivable enum. Only
	 * applied by the converter when the target block actually declares the
	 * attribute.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function exceptions(): array {
		return array(
			'border-0'                    => array( 'border' => '0' ),
			'text-justify'                => array( 'text_justify' => '1' ),
			'bg-transparent-until-scroll' => array( 'bgtus' => '1' ),
			'color-scheme-flip-on-scroll' => array( 'cscos' => '1' ),
		);
	}

	/**
	 * The multi-class combinations build_aui_class emits for `absolute_position`,
	 * keyed by their class set (space-joined, in emit order) → attribute value.
	 * The converter matches these as a group before single-class consumption.
	 *
	 * @return array<string, string>
	 */
	public static function absolute_positions(): array {
		return array(
			'start-0 top-0'                      => 'top-left',
			'start-50 top-0 translate-middle'    => 'top-center',
			'end-0 top-0'                        => 'top-right',
			'start-0 top-50'                     => 'center-left',
			'start-50 top-50 translate-middle'   => 'center',
			'end-0 top-50'                       => 'center-right',
			'start-0 bottom-0'                   => 'bottom-left',
			'start-50 bottom-0 translate-middle' => 'bottom-center',
			'end-0 bottom-0'                     => 'bottom-right',
		);
	}
}
