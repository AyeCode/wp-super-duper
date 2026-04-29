<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for common utility field definitions (class, anchor, name, visibility).
 *
 * @version 3.0.4-beta
 */
final class CommonFields {

	/**
	 * Build a CSS class field definition.
	 *
	 * @param string $type      The field key (default 'css_class').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function class_input( $type = 'css_class', $overwrite = array() ) {
		$defaults = array(
			'type'     => 'text',
			'title'    => __( 'Additional CSS class(es)', 'ayecode-connect' ),
			'desc'     => __( 'Separate multiple classes with spaces.', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'advanced',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_class_input';
		}

		return $input;
	}

	/**
	 * Build an HTML anchor field definition.
	 *
	 * @param string $type      The field key (default 'anchor').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function anchor_input( $type = 'anchor', $overwrite = array() ) {
		$defaults = array(
			'type'     => 'text',
			'title'    => __( 'HTML anchor', 'ayecode-connect' ),
			'desc'     => __( 'Enter a word or two — without spaces — to make a unique web address just for this block, called an "anchor." Then, you\'ll be able to link directly to this section of your page.', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'advanced',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_anchor_input';
		}

		return $input;
	}

	/**
	 * Build a custom block-name field definition.
	 *
	 * @param string $type      The field key (default 'metadata_name').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function custom_name_input( $type = 'metadata_name', $overwrite = array() ) {
		$defaults = array(
			'type'     => 'text',
			'title'    => __( 'Block Name', 'ayecode-connect' ),
			'desc'     => __( 'Set a custom name for this block', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'advanced',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_custom_name_input';
		}

		return $input;
	}

	/**
	 * Build a block-visibility-conditions field definition.
	 *
	 * @param string $type      The field key (default 'visibility_conditions').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function visibility_conditions_input( $type = 'visibility_conditions', $overwrite = array() ) {
		$defaults = array(
			'type'         => 'visibility_conditions',
			'title'        => __( 'Block Visibility', 'ayecode-connect' ),
			'button_title' => __( 'Set Block Visibility', 'ayecode-connect' ),
			'default'      => '',
			'desc_tip'     => true,
			'group'        => 'visibility-conditions',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_visibility_conditions_input';
		}

		return $input;
	}

	/**
	 * Build an "open in new window" checkbox field definition.
	 *
	 * @param string $type      The field key (default 'target').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function new_window_input( $type = 'target', $overwrite = array() ) {
		$defaults = array(
			'type'     => 'checkbox',
			'title'    => __( 'Open in new window', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'link',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_new_window_input';
		}

		return $input;
	}

	/**
	 * Build a "add nofollow" checkbox field definition.
	 *
	 * @param string $type      The field key (default 'nofollow').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function nofollow_input( $type = 'nofollow', $overwrite = array() ) {
		$defaults = array(
			'type'     => 'checkbox',
			'title'    => __( 'Add nofollow', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'link',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_nofollow_input';
		}

		return $input;
	}

	/**
	 * Build a custom-attributes text field definition.
	 *
	 * @param string $type      The field key (default 'attributes').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function attributes_input( $type = 'attributes', $overwrite = array() ) {
		$defaults = array(
			'type'        => 'text',
			'title'       => __( 'Custom Attributes', 'ayecode-connect' ),
			'value'       => '',
			'default'     => '',
			'placeholder' => 'key|value,key2|value2',
			'desc_tip'    => true,
			'group'       => 'link',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_attributes_input';
		}

		return $input;
	}

	/**
	 * Build a title HTML-tag select field definition.
	 *
	 * Note: intentionally uses the 'geodirectory' text domain to match the original source.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function title_tag_input( $overwrite = array() ) {
		$defaults = array(
			'title'    => __( 'Title HTML tag', 'geodirectory' ),
			'desc'     => __( 'Set the HTML tag for the title.', 'geodirectory' ),
			'type'     => 'select',
			'options'  => array(
				''   => __( 'Default (theme widget default)', 'geodirectory' ),
				'h1' => 'h1',
				'h2' => 'h2',
				'h3' => 'h3',
				'h4' => 'h4',
				'h5' => 'h5',
				'h6' => 'h6',
			),
			'default'  => '',
			'desc_tip' => true,
			'advanced' => false,
			'group'    => 'title',
		);

		return wp_parse_args( $overwrite, $defaults );
	}

	/**
	 * Build an HTML wrapper-element select field definition.
	 *
	 * Note: intentionally uses the 'geodirectory' text domain to match the original source.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function html_tag_input( $overwrite = array() ) {
		$defaults = array(
			'title'    => __( 'HTML Element', 'geodirectory' ),
			'desc'     => __( 'The <div> element should only be used if the block is a design element with no semantic meaning.', 'geodirectory' ),
			'type'     => 'select',
			'options'  => array(
				''        => __( 'Default (<div>)', 'geodirectory' ),
				'header'  => '<header>',
				'main'    => '<main>',
				'section' => '<section>',
				'article' => '<article>',
				'aside'   => '<aside>',
				'footer'  => '<footer>',
			),
			'default'  => '',
			'desc_tip' => true,
			'advanced' => false,
			'group'    => 'advanced',
		);

		return wp_parse_args( $overwrite, $defaults );
	}

	/**
	 * Build the standard set of title configuration inputs.
	 *
	 * Provides tag, size, alignment, colour, border, margins and padding inputs,
	 * all grouped under 'title' and gated behind a require on widget_title_tag.
	 *
	 * @return array Array of title input field definitions.
	 */
	public static function title_inputs() {
		$arguments = array();

		$arguments['widget_title_tag']        = self::title_tag_input();
		$arguments['widget_title_size_class'] = \AyeCode\SuperDuper\Fields\TypographyFields::font_size_input(
			'font_size',
			array(
				'element_require' => '[%widget_title_tag%]!=""',
				'group'           => 'title',
				'row'             => array(
					'key'  => 'title-attr',
					'open' => true,
				),
			)
		);
		$arguments['widget_title_align_class'] = \AyeCode\SuperDuper\Fields\TypographyFields::text_align_input(
			'text_align',
			array(
				'element_require' => '[%widget_title_tag%]!=""',
				'group'           => 'title',
				'row'             => array(
					'key' => 'title-attr',
				),
			)
		);
		$arguments['widget_title_color_class'] = \AyeCode\SuperDuper\Fields\TypographyFields::text_color_input(
			'text_color',
			array(
				'element_require' => '[%widget_title_tag%]!=""',
				'group'           => 'title',
				'row'             => array(
					'key'   => 'title-attr',
					'close' => true,
				),
			)
		);

		// Border.
		$arguments['widget_title_border_class'] = \AyeCode\SuperDuper\Fields\StyleFields::border_input(
			'type',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'row'             => array(
					'key'  => 'title-border',
					'open' => true,
				),
			)
		);
		$arguments['widget_title_border_color_class'] = \AyeCode\SuperDuper\Fields\StyleFields::border_input(
			'border',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'row'             => array(
					'key'   => 'title-border',
					'close' => true,
				),
			)
		);

		// Margins.
		$arguments['widget_title_mt_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::margin_input(
			'mt',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-top',
				'row'             => array(
					'title'           => __( 'Margins', 'geodirectory' ),
					'desc_tip'        => true,
					'key'             => 'title-margins',
					'open'            => true,
					'class'           => 'text-center',
					'element_require' => '[%widget_title_tag%]!=""',
				),
			)
		);
		$arguments['widget_title_mr_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::margin_input(
			'mr',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-right',
				'row'             => array(
					'key' => 'title-margins',
				),
			)
		);
		$arguments['widget_title_mb_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::margin_input(
			'mb',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-bottom',
				'row'             => array(
					'key' => 'title-margins',
				),
			)
		);
		$arguments['widget_title_ml_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::margin_input(
			'ml',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-left',
				'row'             => array(
					'key'   => 'title-margins',
					'close' => true,
				),
			)
		);

		// Padding.
		$arguments['widget_title_pt_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::padding_input(
			'pt',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-top',
				'row'             => array(
					'title'           => __( 'Padding', 'geodirectory' ),
					'desc_tip'        => true,
					'key'             => 'title-padding',
					'open'            => true,
					'class'           => 'text-center',
					'element_require' => '[%widget_title_tag%]!=""',
				),
			)
		);
		$arguments['widget_title_pr_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::padding_input(
			'pr',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-right',
				'row'             => array(
					'key' => 'title-padding',
				),
			)
		);
		$arguments['widget_title_pb_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::padding_input(
			'pb',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-bottom',
				'row'             => array(
					'key' => 'title-padding',
				),
			)
		);
		$arguments['widget_title_pl_class'] = \AyeCode\SuperDuper\Fields\SpacingFields::padding_input(
			'pl',
			array(
				'group'           => 'title',
				'element_require' => '[%widget_title_tag%]!=""',
				'icon'            => 'box-left',
				'row'             => array(
					'key'   => 'title-padding',
					'close' => true,
				),
			)
		);

		return $arguments;
	}
}
