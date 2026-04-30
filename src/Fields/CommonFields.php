<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for common utility field definitions
 * (CSS class, anchor, name, visibility, link, title, HTML tag).
 *
 * All single-field methods take only $overwrite = [].
 *
 * @version 3.0.4-beta
 */
final class CommonFields {

	// -------------------------------------------------------------------------
	// Single-field methods
	// -------------------------------------------------------------------------

	/**
	 * Additional CSS class(es) text input.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function css_class( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'text',
			'title'    => __( 'Additional CSS class(es)', 'ayecode-connect' ),
			'desc'     => __( 'Separate multiple classes with spaces.', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'advanced',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_class_input';
		}

		return $input;
	}

	/**
	 * HTML anchor ID text input.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function anchor( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'text',
			'title'    => __( 'HTML anchor', 'ayecode-connect' ),
			'desc'     => __( 'Enter a word or two — without spaces — to make a unique web address just for this block, called an "anchor." Then, you\'ll be able to link directly to this section of your page.', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'advanced',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_anchor_input';
		}

		return $input;
	}

	/**
	 * Custom block name text input.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function metadata_name( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'text',
			'title'    => __( 'Block Name', 'ayecode-connect' ),
			'desc'     => __( 'Set a custom name for this block', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'advanced',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_custom_name_input';
		}

		return $input;
	}

	/**
	 * Block visibility conditions field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function visibility_conditions( array $overwrite = [] ): array {
		$defaults = [
			'type'         => 'visibility_conditions',
			'title'        => __( 'Block Visibility', 'ayecode-connect' ),
			'button_title' => __( 'Set Block Visibility', 'ayecode-connect' ),
			'default'      => '',
			'desc_tip'     => true,
			'group'        => 'visibility-conditions',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_visibility_conditions_input';
		}

		return $input;
	}

	/**
	 * Open in new window checkbox.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function new_window( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'checkbox',
			'title'    => __( 'Open in new window', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'link',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_new_window_input';
		}

		return $input;
	}

	/**
	 * Nofollow checkbox.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function nofollow( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'checkbox',
			'title'    => __( 'Add nofollow', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'link',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_nofollow_input';
		}

		return $input;
	}

	/**
	 * Custom HTML attributes text input.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function attributes( array $overwrite = [] ): array {
		$defaults = [
			'type'        => 'text',
			'title'       => __( 'Custom Attributes', 'ayecode-connect' ),
			'value'       => '',
			'default'     => '',
			'placeholder' => 'key|value,key2|value2',
			'desc_tip'    => true,
			'group'       => 'link',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_attributes_input';
		}

		return $input;
	}

	/**
	 * Title HTML tag select field.
	 *
	 * Note: intentionally uses the 'geodirectory' text domain to match the original source.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function title_tag( array $overwrite = [] ): array {
		$defaults = [
			'title'    => __( 'Title HTML tag', 'geodirectory' ),
			'desc'     => __( 'Set the HTML tag for the title.', 'geodirectory' ),
			'type'     => 'select',
			'options'  => [
				''   => __( 'Default (theme widget default)', 'geodirectory' ),
				'h1' => 'h1',
				'h2' => 'h2',
				'h3' => 'h3',
				'h4' => 'h4',
				'h5' => 'h5',
				'h6' => 'h6',
			],
			'default'  => '',
			'desc_tip' => true,
			'advanced' => false,
			'group'    => 'title',
		];

		return wp_parse_args( $overwrite, $defaults );
	}

	/**
	 * HTML wrapper element select field.
	 *
	 * Note: intentionally uses the 'geodirectory' text domain to match the original source.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function html_tag( array $overwrite = [] ): array {
		$defaults = [
			'title'    => __( 'HTML Element', 'geodirectory' ),
			'desc'     => __( 'The <div> element should only be used if the block is a design element with no semantic meaning.', 'geodirectory' ),
			'type'     => 'select',
			'options'  => [
				''        => __( 'Default (<div>)', 'geodirectory' ),
				'header'  => '<header>',
				'main'    => '<main>',
				'section' => '<section>',
				'article' => '<article>',
				'aside'   => '<aside>',
				'footer'  => '<footer>',
			],
			'default'  => '',
			'desc_tip' => true,
			'advanced' => false,
			'group'    => 'advanced',
		];

		return wp_parse_args( $overwrite, $defaults );
	}

	// -------------------------------------------------------------------------
	// Group methods
	// -------------------------------------------------------------------------

	/**
	 * Return the standard set of title configuration fields.
	 *
	 * Provides tag, size, alignment, color, border, margin, and padding fields
	 * all grouped under 'title' and gated behind a require on widget_title_tag.
	 *
	 * @return array<string, array>
	 */
	public static function title_group(): array {
		$er = '[%widget_title_tag%]!=""';

		$arguments = [];

		$arguments['widget_title_tag'] = self::title_tag();

		$arguments['widget_title_size_class'] = TypographyFields::font_size(
			[
				'element_require' => $er,
				'group'           => 'title',
				'row'             => [ 'key' => 'title-attr', 'open' => true ],
			]
		);

		$arguments['widget_title_align_class'] = TypographyFields::text_align(
			[
				'element_require' => $er,
				'group'           => 'title',
				'row'             => [ 'key' => 'title-attr' ],
			]
		);

		$arguments['widget_title_color_class'] = TypographyFields::text_color(
			[
				'element_require' => $er,
				'group'           => 'title',
				'row'             => [ 'key' => 'title-attr', 'close' => true ],
			]
		);

		// Border.
		$arguments['widget_title_border_class'] = StyleFields::border_style(
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-border', 'open' => true ],
			]
		);

		$arguments['widget_title_border_color_class'] = StyleFields::border_show(
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-border', 'close' => true ],
			]
		);

		// Margins.
		$arguments['widget_title_mt_class'] = SpacingFields::margin(
			'top',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [
					'title'           => __( 'Margins', 'geodirectory' ),
					'desc_tip'        => true,
					'key'             => 'title-margins',
					'open'            => true,
					'class'           => 'text-center',
					'element_require' => $er,
				],
			]
		);

		$arguments['widget_title_mr_class'] = SpacingFields::margin(
			'right',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-margins' ],
			]
		);

		$arguments['widget_title_mb_class'] = SpacingFields::margin(
			'bottom',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-margins' ],
			]
		);

		$arguments['widget_title_ml_class'] = SpacingFields::margin(
			'left',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-margins', 'close' => true ],
			]
		);

		// Padding.
		$arguments['widget_title_pt_class'] = SpacingFields::padding(
			'top',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [
					'title'           => __( 'Padding', 'geodirectory' ),
					'desc_tip'        => true,
					'key'             => 'title-padding',
					'open'            => true,
					'class'           => 'text-center',
					'element_require' => $er,
				],
			]
		);

		$arguments['widget_title_pr_class'] = SpacingFields::padding(
			'right',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-padding' ],
			]
		);

		$arguments['widget_title_pb_class'] = SpacingFields::padding(
			'bottom',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-padding' ],
			]
		);

		$arguments['widget_title_pl_class'] = SpacingFields::padding(
			'left',
			[
				'group'           => 'title',
				'element_require' => $er,
				'row'             => [ 'key' => 'title-padding', 'close' => true ],
			]
		);

		return $arguments;
	}
}
