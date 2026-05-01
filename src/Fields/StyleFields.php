<?php

namespace AyeCode\SuperDuper\Fields;

use AyeCode\SuperDuper\Helpers\ColorOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for style-related field definitions
 * (border, shadow, background, display, opacity, hover, z-index, overflow, scrollbars).
 *
 * Single-field methods take only $overwrite = [].
 * Group methods take $prefix = '' and $overwrite = [].
 *
 * @version 3.0.4-beta
 */
final class StyleFields {

	// -------------------------------------------------------------------------
	// Border — single-field methods
	// -------------------------------------------------------------------------

	/**
	 * Border color select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_show( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Border color', 'ayecode-connect' ),
			'options'  => [ '' => __( 'Default', 'ayecode-connect' ), '0' => __( 'None', 'ayecode-connect' ) ] + ColorOptions::aui( [ 'core', 'subtle' ] ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_border_input_border';
		}

		return $input;
	}

	/**
	 * Border show/sides select field (full, top, bottom, etc.).
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_style( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Border show', 'ayecode-connect' ),
			'options'         => [
				'border'          => __( 'Full (set color to show)', 'ayecode-connect' ),
				'border-top'      => __( 'Top', 'ayecode-connect' ),
				'border-bottom'   => __( 'Bottom', 'ayecode-connect' ),
				'border-left'     => __( 'Left', 'ayecode-connect' ),
				'border-right'    => __( 'Right', 'ayecode-connect' ),
				'border-top-0'    => __( '-Top', 'ayecode-connect' ),
				'border-bottom-0' => __( '-Bottom', 'ayecode-connect' ),
				'border-left-0'   => __( '-Left', 'ayecode-connect' ),
				'border-right-0'  => __( '-Right', 'ayecode-connect' ),
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '([%border%]&&[%border%]!="0")',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'], $clean['element_require'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_border_input_type';
		}

		return $input;
	}

	/**
	 * Border width select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_width( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Border width', 'ayecode-connect' ),
			'options'         => [
				''         => __( 'Default', 'ayecode-connect' ),
				'border-2' => '2',
				'border-3' => '3',
				'border-4' => '4',
				'border-5' => '5',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '([%border%]&&[%border%]!="0")',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'], $clean['element_require'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_border_input_width';
		}

		return $input;
	}

	/**
	 * Border opacity select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_opacity( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Border opacity', 'ayecode-connect' ),
			'options'         => [
				''                  => __( 'Default', 'ayecode-connect' ),
				'border-opacity-75' => '75%',
				'border-opacity-50' => '50%',
				'border-opacity-25' => '25%',
				'border-opacity-10' => '10%',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '([%border%]&&[%border%]!="0")',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'], $clean['element_require'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_border_input_opacity';
		}

		return $input;
	}

	/**
	 * Border radius type select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_radius( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Border radius type', 'ayecode-connect' ),
			'options'         => [
				''               => __( 'Default', 'ayecode-connect' ),
				'rounded'        => 'rounded',
				'rounded-top'    => 'rounded-top',
				'rounded-right'  => 'rounded-right',
				'rounded-bottom' => 'rounded-bottom',
				'rounded-left'   => 'rounded-left',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '([%border%]&&[%border%]!="0")',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'], $clean['element_require'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_border_input_rounded';
		}

		return $input;
	}

	/**
	 * Border radius size select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_radius_size( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Border radius size', 'ayecode-connect' ),
			'options'         => [
				''       => __( 'Default', 'ayecode-connect' ),
				'0'      => '0',
				'1'      => '1',
				'2'      => '2',
				'3'      => '3',
				'4'      => '4',
				'circle' => 'circle',
				'pill'   => 'pill',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '([%border%]&&[%border%]!="0")',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'], $clean['element_require'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_border_input_rounded_size';
		}

		return $input;
	}

	// -------------------------------------------------------------------------
	// Single-field methods
	// -------------------------------------------------------------------------

	/**
	 * Shadow select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function shadow( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Shadow', 'ayecode-connect' ),
			'options'  => [
				''          => __( 'None', 'ayecode-connect' ),
				'shadow-sm' => __( 'Small', 'ayecode-connect' ),
				'shadow'    => __( 'Regular', 'ayecode-connect' ),
				'shadow-lg' => __( 'Large', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_shadow_input';
		}

		return $input;
	}

	/**
	 * Display select field.
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function display( array $overwrite = [] ): array {
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
			'title'    => __( 'Display', 'ayecode-connect' ),
			'options'  => [
				''                                   => __( 'Default', 'ayecode-connect' ),
				'd' . $device_size . '-none'         => 'none',
				'd' . $device_size . '-inline'       => 'inline',
				'd' . $device_size . '-inline-block' => 'inline-block',
				'd' . $device_size . '-block'        => 'block',
				'd' . $device_size . '-table'        => 'table',
				'd' . $device_size . '-table-cell'   => 'table-cell',
				'd' . $device_size . '-table-row'    => 'table-row',
				'd' . $device_size . '-flex'         => 'flex',
				'd' . $device_size . '-inline-flex'  => 'inline-flex',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_display_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Single background color select field (no gradient/image support).
	 *
	 * For a full background group with color, gradient, and image, use background_group().
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function background( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Background color', 'ayecode-connect' ),
			'options'  => ColorOptions::aui( [ 'none', 'transparent', 'core', 'subtle' ] ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_background_input';
		}

		return $input;
	}

	/**
	 * Opacity select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function opacity( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Opacity', 'ayecode-connect' ),
			'options'  => [
				''            => __( 'Default', 'ayecode-connect' ),
				'opacity-10'  => '10%',
				'opacity-15'  => '15%',
				'opacity-25'  => '25%',
				'opacity-35'  => '35%',
				'opacity-40'  => '40%',
				'opacity-50'  => '50%',
				'opacity-60'  => '60%',
				'opacity-65'  => '65%',
				'opacity-70'  => '70%',
				'opacity-75'  => '75%',
				'opacity-80'  => '80%',
				'opacity-90'  => '90%',
				'opacity-100' => '100%',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_opacity_input';
		}

		return $input;
	}

	/**
	 * Hover animations multi-select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function hover_animation( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'multiple' => true,
			'title'    => 'hover-animations',
			'options'  => [
				''                 => __( 'none', 'ayecode-connect' ),
				'hover-zoom'       => __( 'Zoom', 'ayecode-connect' ),
				'hover-shadow'     => __( 'Shadow', 'ayecode-connect' ),
				'hover-move-up'    => __( 'Move up', 'ayecode-connect' ),
				'hover-move-down'  => __( 'Move down', 'ayecode-connect' ),
				'hover-move-left'  => __( 'Move left', 'ayecode-connect' ),
				'hover-move-right' => __( 'Move right', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'hover-animations',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_hover_animations_input';
		}

		return $input;
	}

	/**
	 * Hover icon animation select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function hover_icon_animation( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'multiple' => false,
			'title'    => __( 'Icon Hover Animations', 'ayecode-connect' ),
			'options'  => [
				''                    => __( 'none', 'ayecode-connect' ),
				'animate-shake'       => __( 'Shake', 'ayecode-connect' ),
				'animate-pulse'       => __( 'Pulse', 'ayecode-connect' ),
				'animate-rotate'      => __( 'Rotate', 'ayecode-connect' ),
				'animate-scale'       => __( 'Scale', 'ayecode-connect' ),
				'animate-slide-end'   => __( 'Slide end', 'ayecode-connect' ),
				'animate-slide-start' => __( 'Slide start', 'ayecode-connect' ),
				'animate-slide-up'    => __( 'Slide up', 'ayecode-connect' ),
				'animate-slide-down'  => __( 'Slide down', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'hover-animations',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_hover_icon_animation_input';
		}

		return $input;
	}

	/**
	 * Z-index select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function zindex( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Z-index', 'ayecode-connect' ),
			'options'  => [
				''          => __( 'Default', 'ayecode-connect' ),
				'zindex-0'  => '0',
				'zindex-1'  => '1',
				'zindex-5'  => '5',
				'zindex-10' => '10',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_zindex_input';
		}

		return $input;
	}

	/**
	 * Overflow select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function overflow( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Overflow', 'ayecode-connect' ),
			'options'  => [
				''                 => __( 'Default', 'ayecode-connect' ),
				'overflow-auto'    => __( 'Auto', 'ayecode-connect' ),
				'overflow-hidden'  => __( 'Hidden', 'ayecode-connect' ),
				'overflow-visible' => __( 'Visible', 'ayecode-connect' ),
				'overflow-scroll'  => __( 'Scroll', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_overflow_input';
		}

		return $input;
	}

	/**
	 * Scrollbars style select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function scrollbars( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Scrollbars', 'ayecode-connect' ),
			'options'  => [
				''               => __( 'Default', 'ayecode-connect' ),
				'scrollbars-ios' => __( 'IOS Style', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_scrollbars_input';
		}

		return $input;
	}

	// -------------------------------------------------------------------------
	// Group methods
	// -------------------------------------------------------------------------

	/**
	 * Return all six border field definitions keyed by argument name.
	 *
	 * When $prefix is set, dependent fields' element_require conditions are updated
	 * to reference the prefixed border key.
	 *
	 * @param string $prefix   Optional prefix for all field keys (e.g. 'card_' → 'card_border', …).
	 * @param array  $overwrite Per-field overwrite config applied to every field.
	 * @return array<string, array>
	 */
	public static function border_group( string $prefix = '', array $overwrite = [] ): array {
		$border_key = $prefix . 'border';
		$er         = '([%' . $border_key . '%]&&[%' . $border_key . '%]!="0")';

		// Dependent fields need to reference the (possibly prefixed) border key.
		$dep_overwrite = array_merge( $overwrite, [ 'element_require' => $er ] );

		return [
			$prefix . 'border'         => self::border_show( $overwrite ),
			$prefix . 'border_type'    => self::border_style( $dep_overwrite ),
			$prefix . 'border_width'   => self::border_width( $dep_overwrite ),
			$prefix . 'border_opacity' => self::border_opacity( $dep_overwrite ),
			$prefix . 'rounded'        => self::border_radius( $dep_overwrite ),
			$prefix . 'rounded_size'   => self::border_radius_size( $dep_overwrite ),
		];
	}

	/**
	 * Return all background field definitions (color, custom color, gradient, and image).
	 *
	 * @param string     $prefix         Key prefix / base key (default 'bg' → keys: bg, bg_color, bg_gradient, …).
	 * @param array      $overwrite      Overrides for the main background select.
	 * @param array|bool $overwrite_color    Overrides for the custom color picker; false to omit.
	 * @param array|bool $overwrite_gradient Overrides for the gradient picker; false to omit.
	 * @param array|bool $overwrite_image    Overrides for the image picker; false to omit.
	 * @param bool       $include_button_colors Whether to include button-specific colors in the palette.
	 * @return array<string, array>
	 */
	public static function background_group( string $prefix = 'bg', array $overwrite = [], $overwrite_color = [], $overwrite_gradient = [], $overwrite_image = [], bool $include_button_colors = false ): array {
		$color_types = $include_button_colors
			? [ 'none', 'transparent', 'core', 'outline', 'outline_btn_text' ]
			: [ 'none', 'transparent', 'core', 'subtle' ];

		$options = ColorOptions::aui( $color_types );

		if ( false !== $overwrite_color ) {
			$options['custom-color'] = __( 'Custom Color', 'ayecode-connect' );
		}

		if ( false !== $overwrite_gradient ) {
			$options['custom-gradient'] = __( 'Custom Gradient', 'ayecode-connect' );
		}

		$main_defaults = [
			'type'             => 'select',
			'title'            => __( 'Background Color', 'ayecode-connect' ),
			'options'          => $options,
			'default'          => '',
			'desc_tip'         => true,
			'group'            => 'wrapper-styles',
			'clears_on_change' => [
				'custom-color'    => [ $prefix . '_gradient' ],
				'custom-gradient' => [ $prefix . '_color' ],
				'default_case'    => [ $prefix . '_color', $prefix . '_gradient' ],
			],
		];

		$fields = [];

		if ( false !== $overwrite ) {
			$fields[ $prefix ] = wp_parse_args( $overwrite, $main_defaults );
		}

		if ( false !== $overwrite_color ) {
			$fields[ $prefix . '_color' ] = wp_parse_args(
				$overwrite_color,
				[
					'type'            => 'color',
					'title'           => __( 'Custom color', 'ayecode-connect' ),
					'placeholder'     => '',
					'desc_tip'        => true,
					'group'           => 'wrapper-styles',
					'element_require' => '[%' . $prefix . '%]=="custom-color"',
				]
			);
		}

		if ( false !== $overwrite_gradient ) {
			$fields[ $prefix . '_gradient' ] = wp_parse_args(
				$overwrite_gradient,
				[
					'type'            => 'gradient',
					'title'           => __( 'Custom gradient', 'ayecode-connect' ),
					'placeholder'     => '',
					'default'         => '',
					'desc_tip'        => true,
					'group'           => 'wrapper-styles',
					'element_require' => '[%' . $prefix . '%]=="custom-gradient"',
				]
			);

			$fields[ $prefix . '_on_text' ] = [
				'type'            => 'checkbox',
				'title'           => __( 'Background on text', 'ayecode-connect' ),
				'default'         => '',
				'value'           => '1',
				'desc_tip'        => false,
				'desc'            => __( 'This will show the background on the text.', 'ayecode-connect' ),
				'group'           => 'wrapper-styles',
				'element_require' => '[%' . $prefix . '%]=="custom-gradient"',
			];
		}

		if ( false !== $overwrite_image ) {
			$img_group = ! empty( $overwrite_image['group'] ) ? $overwrite_image['group'] : 'wrapper-styles';
			$not_image = '( [%' . $prefix . '%]=="" || [%' . $prefix . '%]=="custom-color" || [%' . $prefix . '%]=="custom-gradient" || [%' . $prefix . '%]=="transparent" )';

			$fields[ $prefix . '_image_fixed' ] = [
				'type'            => 'checkbox',
				'title'           => __( 'Fixed background', 'ayecode-connect' ),
				'default'         => '',
				'desc_tip'        => true,
				'group'           => $img_group,
				'element_require' => $not_image,
			];

			$fields[ $prefix . '_image_use_featured' ] = [
				'type'            => 'checkbox',
				'title'           => __( 'Use featured image', 'ayecode-connect' ),
				'default'         => '',
				'desc_tip'        => true,
				'group'           => $img_group,
				'element_require' => $not_image,
			];

			$fields[ $prefix . '_image' ] = wp_parse_args(
				$overwrite_image,
				[
					'type'        => 'image',
					'title'       => __( 'Custom image', 'ayecode-connect' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
					'focalpoint'  => true,
					'group'       => 'wrapper-styles',
				]
			);

			$fields[ $prefix . '_image_id' ] = wp_parse_args(
				$overwrite_image,
				[
					'type'        => 'hidden',
					'hidden_type' => 'number',
					'title'       => '',
					'placeholder' => '',
					'default'     => '',
					'group'       => 'wrapper-styles',
				]
			);

			$fields[ $prefix . '_image_xy' ] = wp_parse_args(
				$overwrite_image,
				[
					'type'        => 'image_xy',
					'title'       => '',
					'placeholder' => '',
					'default'     => '',
					'group'       => 'wrapper-styles',
				]
			);
		}

		return $fields;
	}
}
