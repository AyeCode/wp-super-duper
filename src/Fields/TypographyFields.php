<?php

namespace AyeCode\SuperDuper\Fields;

use AyeCode\SuperDuper\Helpers\ColorOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for typography-related field definitions.
 *
 * Single-field methods take only $overwrite = [].
 * Group methods take $prefix = '' / $prefix = 'key' and $overwrite = [].
 *
 * @version 3.0.4-beta
 */
final class TypographyFields {

	// -------------------------------------------------------------------------
	// Single-field methods
	// -------------------------------------------------------------------------

	/**
	 * Font size select field (no custom-size option).
	 *
	 * Use font_size_group() to get the paired custom-size field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_size( array $overwrite = [] ): array {
		return self::_font_size_field( false, $overwrite );
	}

	/**
	 * Custom font size number input.
	 *
	 * When used standalone the element_require condition must be set via $overwrite.
	 * Use font_size_group() for the auto-wired pair.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_size_custom( array $overwrite = [] ): array {
		return self::_font_size_custom_field( '', $overwrite );
	}

	/**
	 * Font weight / appearance select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_weight( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Appearance', 'ayecode-connect' ),
			'options'  => [
				''                                => __( 'Inherit', 'ayecode-connect' ),
				'font-weight-bold'                => 'bold',
				'font-weight-bolder'              => 'bolder',
				'font-weight-normal'              => 'normal',
				'font-weight-light'               => 'light',
				'font-weight-lighter'             => 'lighter',
				'font-italic'                     => 'italic',
				'font-weight-bold font-italic'    => 'bold italic',
				'font-weight-bolder font-italic'  => 'bolder italic',
				'font-weight-normal font-italic'  => 'normal italic',
				'font-weight-light font-italic'   => 'light italic',
				'font-weight-lighter font-italic' => 'lighter italic',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_font_weight_input';
		}

		return $input;
	}

	/**
	 * Letter case (text-transform) select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_case( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Letter case', 'ayecode-connect' ),
			'options'  => [
				''                => __( 'Default', 'ayecode-connect' ),
				'text-lowercase'  => __( 'lowercase', 'ayecode-connect' ),
				'text-uppercase'  => __( 'UPPERCASE', 'ayecode-connect' ),
				'text-capitalize' => __( 'Capitalize', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_font_case_input';
		}

		return $input;
	}

	/**
	 * Font italic select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_italic( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Font italic', 'ayecode-connect' ),
			'options'  => [
				''            => __( 'No', 'ayecode-connect' ),
				'font-italic' => __( 'Yes', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_font_italic_input';
		}

		return $input;
	}

	/**
	 * Line height number input.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function line_height( array $overwrite = [] ): array {
		$defaults = [
			'type'              => 'number',
			'title'             => __( 'Font Line Height', 'ayecode-connect' ),
			'default'           => '',
			'placeholder'       => '1.75',
			'custom_attributes' => [
				'step' => '0.1',
				'min'  => '0',
				'max'  => '100',
			],
			'desc_tip'          => true,
			'group'             => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_font_line_height_input';
		}

		return $input;
	}

	/**
	 * Text justify checkbox.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function text_justify( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'checkbox',
			'title'    => __( 'Text justify', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_text_justify_input';
		}

		return $input;
	}

	/**
	 * Text align select field.
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function text_align( array $overwrite = [] ): array {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( 'Tablet' === $overwrite['device_type'] ) {
				$device_size = '-md';
			} elseif ( 'Desktop' === $overwrite['device_type'] ) {
				$device_size = '-lg';
			}
		}

		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Text align', 'ayecode-connect' ),
			'options'  => [
				''                                 => __( 'Default', 'ayecode-connect' ),
				'text' . $device_size . '-start'  => __( 'Start', 'ayecode-connect' ),
				'text' . $device_size . '-end'    => __( 'End', 'ayecode-connect' ),
				'text' . $device_size . '-center' => __( 'Center', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_text_align_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Text color select field (no custom-color option).
	 *
	 * Use text_color_group() to get the paired custom-color picker.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function text_color( array $overwrite = [] ): array {
		return self::_text_color_field( false, $overwrite );
	}

	/**
	 * Custom color picker field.
	 *
	 * When used standalone, element_require must be set via $overwrite.
	 * Use text_color_group() for the auto-wired pair.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function text_color_custom( array $overwrite = [] ): array {
		return self::_custom_color_field( '', $overwrite );
	}

	// -------------------------------------------------------------------------
	// Group methods
	// -------------------------------------------------------------------------

	/**
	 * Return font size + custom size fields keyed by argument name.
	 *
	 * @param string $prefix   Base key used for both fields (default 'font_size' → 'font_size', 'font_size_custom').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array> [$prefix => [...], $prefix . '_custom' => [...]]
	 */
	public static function font_size_group( string $prefix = 'font_size', array $overwrite = [] ): array {
		return [
			$prefix              => self::_font_size_field( true, $overwrite ),
			$prefix . '_custom'  => self::_font_size_custom_field( $prefix, $overwrite ),
		];
	}

	/**
	 * Return responsive text-align fields keyed by argument name (mobile, tablet, desktop).
	 *
	 * @param string $prefix   Base key (default 'text_align' → 'text_align', 'text_align_md', 'text_align_lg').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function text_align_group( string $prefix = 'text_align', array $overwrite = [] ): array {
		return [
			$prefix          => self::text_align( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) ),
			$prefix . '_md'  => self::text_align( array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ) ),
			$prefix . '_lg'  => self::text_align( array_merge( $overwrite, [ 'device_type' => 'Desktop' ] ) ),
		];
	}

	/**
	 * Return text color select + custom color picker fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'text_color' → 'text_color', 'text_color_custom').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array> [$prefix => [...], $prefix . '_custom' => [...]]
	 */
	public static function text_color_group( string $prefix = 'text_color', array $overwrite = [] ): array {
		return [
			$prefix             => self::_text_color_field( true, $overwrite ),
			$prefix . '_custom' => self::_custom_color_field( $prefix, $overwrite ),
		];
	}

	// -------------------------------------------------------------------------
	// Private helpers (shared logic between single + group variants)
	// -------------------------------------------------------------------------

	/**
	 * Build a font-size select field definition.
	 *
	 * @param bool  $has_custom Whether to include the "Custom size" option.
	 * @param array $overwrite  Field config overrides.
	 * @return array
	 */
	private static function _font_size_field( bool $has_custom, array $overwrite ): array {
		// Numeric key 0 matches the legacy array structure — intentional.
		$options    = [];
		$options[]  = __( 'Inherit from parent', 'ayecode-connect' );
		$options   += [
			'fs-base'   => 'fs-base (body default)',
			'fs-6'      => 'fs-6',
			'fs-5'      => 'fs-5',
			'fs-4'      => 'fs-4',
			'fs-3'      => 'fs-3',
			'fs-2'      => 'fs-2',
			'fs-1'      => 'fs-1',
			'fs-lg'     => 'fs-lg',
			'fs-sm'     => 'fs-sm',
			'fs-xs'     => 'fs-xs',
			'fs-xxs'    => 'fs-xxs',
			'h6'        => 'h6',
			'h5'        => 'h5',
			'h4'        => 'h4',
			'h3'        => 'h3',
			'h2'        => 'h2',
			'h1'        => 'h1',
			'display-1' => 'display-1',
			'display-2' => 'display-2',
			'display-3' => 'display-3',
			'display-4' => 'display-4',
			'display-5' => 'display-5',
			'display-6' => 'display-6',
		];

		if ( $has_custom ) {
			$options['custom'] = __( 'Custom size', 'ayecode-connect' );
		}

		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Font size', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = $has_custom ? 'sd_get_font_size_input_has_custom' : 'sd_get_font_size_input';
		}

		return $input;
	}

	/**
	 * Build a custom font-size number input.
	 *
	 * @param string $parent_key The field key of the paired size select (for element_require). Empty = no condition.
	 * @param array  $overwrite  Field config overrides.
	 * @return array
	 */
	private static function _font_size_custom_field( string $parent_key, array $overwrite ): array {
		$defaults = [
			'type'              => 'number',
			'title'             => __( 'Font size (rem)', 'ayecode-connect' ),
			'default'           => '',
			'placeholder'       => '1.25',
			'custom_attributes' => [
				'step' => '0.1',
				'min'  => '0',
				'max'  => '100',
			],
			'desc_tip'          => true,
			'group'             => 'typography',
		];

		if ( $parent_key ) {
			$defaults['element_require'] = '[%' . $parent_key . '%]=="custom"';
		}

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$suffix                   = $parent_key ? '_' . $parent_key : '';
			$input['block_component'] = 'sd_get_font_custom_size_input' . $suffix;
		}

		return $input;
	}

	/**
	 * Build a text-color select field definition.
	 *
	 * @param bool  $has_custom Whether to include the "Custom color" option.
	 * @param array $overwrite  Field config overrides.
	 * @return array
	 */
	private static function _text_color_field( bool $has_custom, array $overwrite ): array {
		$options = [ '' => __( 'None', 'ayecode-connect' ) ] + ColorOptions::aui( [ 'core', 'emphasis' ], false );

		if ( $has_custom ) {
			$options['custom'] = __( 'Custom color', 'ayecode-connect' );
		}

		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Text color', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = $has_custom ? 'sd_get_text_color_input_has_custom' : 'sd_get_text_color_input';
		}

		return $input;
	}

	/**
	 * Build a custom color picker field.
	 *
	 * @param string $parent_key The field key of the paired color select (for element_require). Empty = no condition.
	 * @param array  $overwrite  Field config overrides.
	 * @return array
	 */
	private static function _custom_color_field( string $parent_key, array $overwrite ): array {
		$defaults = [
			'type'        => 'color',
			'title'       => __( 'Custom color', 'ayecode-connect' ),
			'default'     => '',
			'placeholder' => '',
			'desc_tip'    => true,
			'group'       => 'typography',
		];

		if ( $parent_key ) {
			$defaults['element_require'] = '[%' . $parent_key . '%]=="custom"';
		}

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$suffix                   = $parent_key ? '_' . $parent_key : '';
			$input['block_component'] = 'sd_get_custom_color_input' . $suffix;
		}

		return $input;
	}
}
