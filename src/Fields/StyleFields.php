<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for style-related field definitions (border, shadow, background, display).
 *
 * @version 3.0.4-beta
 */
final class StyleFields {

	/**
	 * Build a border field definition.
	 *
	 * @param string $type      The border property: 'border', 'rounded', 'rounded_size', 'width', 'opacity', 'type'.
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function border_input( $type = 'border', $overwrite = array() ) {

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Border', 'ayecode-connect' ),
			'options'  => array(),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		// title
		if ( 'rounded' === $type ) {
			$defaults['title']           = __( 'Border radius type', 'ayecode-connect' );
			$defaults['options']         = array(
				''               => __( 'Default', 'ayecode-connect' ),
				'rounded'        => 'rounded',
				'rounded-top'    => 'rounded-top',
				'rounded-right'  => 'rounded-right',
				'rounded-bottom' => 'rounded-bottom',
				'rounded-left'   => 'rounded-left',
			);
			$defaults['element_require'] = '([%border%]&&[%border%]!="0")';
		} elseif ( 'rounded_size' === $type ) {
			$defaults['title'] = __( 'Border radius size', 'ayecode-connect' );

			$defaults['options'] = array(
				''       => __( 'Default', 'ayecode-connect' ),
				'0'      => '0',
				'1'      => '1',
				'2'      => '2',
				'3'      => '3',
				'4'      => '4',
				'circle' => 'circle',
				'pill'   => 'pill',
			);

			$defaults['element_require'] = '([%border%]&&[%border%]!="0")';
		} elseif ( 'width' === $type ) {
			$defaults['title']           = __( 'Border width', 'ayecode-connect' );
			$defaults['options']         = array(
				''         => __( 'Default', 'ayecode-connect' ),
				'border-2' => '2',
				'border-3' => '3',
				'border-4' => '4',
				'border-5' => '5',
			);
			$defaults['element_require'] = '([%border%]&&[%border%]!="0")';
		} elseif ( 'opacity' === $type ) {
			$defaults['title']           = __( 'Border opacity', 'ayecode-connect' );
			$defaults['options']         = array(
				''                  => __( 'Default', 'ayecode-connect' ),
				'border-opacity-75' => '75%',
				'border-opacity-50' => '50%',
				'border-opacity-25' => '25%',
				'border-opacity-10' => '10%',
			);
			$defaults['element_require'] = '([%border%]&&[%border%]!="0")';
		} elseif ( 'type' === $type ) {
			$defaults['title']           = __( 'Border show', 'ayecode-connect' );
			$defaults['options']         = array(
				'border'          => __( 'Full (set color to show)', 'ayecode-connect' ),
				'border-top'      => __( 'Top', 'ayecode-connect' ),
				'border-bottom'   => __( 'Bottom', 'ayecode-connect' ),
				'border-left'     => __( 'Left', 'ayecode-connect' ),
				'border-right'    => __( 'Right', 'ayecode-connect' ),
				'border-top-0'    => __( '-Top', 'ayecode-connect' ),
				'border-bottom-0' => __( '-Bottom', 'ayecode-connect' ),
				'border-left-0'   => __( '-Left', 'ayecode-connect' ),
				'border-right-0'  => __( '-Right', 'ayecode-connect' ),
			);
			$defaults['element_require'] = '([%border%]&&[%border%]!="0")';
		} else {
			$defaults['title']   = __( 'Border color', 'ayecode-connect' );
			$defaults['options'] = array(
				                       ''  => __( 'Default', 'ayecode-connect' ),
				                       '0' => __( 'None', 'ayecode-connect' ),
			                       ) + sd_aui_colors( false, false, false, false, true );
		}

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = $type ? 'sd_get_border_input_' . $type : 'sd_get_border_input';
		}

		return $input;
	}

	/**
	 * Build a shadow field definition.
	 *
	 * @param string $type      The field key (default 'shadow').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function shadow_input( $type = 'shadow', $overwrite = array() ) {
		$options = array(
			''          => __( 'None', 'ayecode-connect' ),
			'shadow-sm' => __( 'Small', 'ayecode-connect' ),
			'shadow'    => __( 'Regular', 'ayecode-connect' ),
			'shadow-lg' => __( 'Large', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Shadow', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_shadow_input';
		}

		return $input;
	}

	/**
	 * Build a set of background field definitions (color, gradient, image).
	 *
	 * @param string     $type                  The base field key (default 'bg').
	 * @param array      $overwrite             Overrides for the main background select.
	 * @param array|bool $overwrite_color        Overrides for the custom color picker; false to omit.
	 * @param array|bool $overwrite_gradient     Overrides for the gradient picker; false to omit.
	 * @param array|bool $overwrite_image        Overrides for the image picker; false to omit.
	 * @param bool       $include_button_colors  Whether to include button-specific colours in the palette.
	 * @return array<string, array>
	 */
	public static function background_inputs( $type = 'bg', $overwrite = array(), $overwrite_color = array(), $overwrite_gradient = array(), $overwrite_image = array(), $include_button_colors = false ) {
		$color_options = $include_button_colors ? sd_aui_colors( false, true, true, true, true ) : sd_aui_colors( false, false, false, false, true );

		$options = array(
			           ''            => __( 'None', 'ayecode-connect' ),
			           'transparent' => __( 'Transparent', 'ayecode-connect' ),
		           ) + $color_options;

		if ( false !== $overwrite_color ) {
			$options['custom-color'] = __( 'Custom Color', 'ayecode-connect' );
		}

		if ( false !== $overwrite_gradient ) {
			$options['custom-gradient'] = __( 'Custom Gradient', 'ayecode-connect' );
		}

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Background Color', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
			'clears_on_change' => array(
				'custom-color'    => array( 'bg_gradient' ),
				'custom-gradient' => array( 'bg_color' ),
				'default_case'    => array( 'bg_color', 'bg_gradient' ),
			),
		);

		if ( $overwrite !== false ) {
			$input[ $type ] = wp_parse_args( $overwrite, $defaults );
		}

		if ( $overwrite_color !== false ) {
			$input[ $type . '_color' ] = wp_parse_args(
				$overwrite_color,
				array(
					'type'            => 'color',
					'title'           => __( 'Custom color', 'ayecode-connect' ),
					'placeholder'     => '',
					'desc_tip'        => true,
					'group'           => 'wrapper-styles',
					'element_require' => '[%' . $type . '%]=="custom-color"',
				)
			);
		}

		if ( $overwrite_gradient !== false ) {
			$input[ $type . '_gradient' ] = wp_parse_args(
				$overwrite_gradient,
				array(
					'type'            => 'gradient',
					'title'           => __( 'Custom gradient', 'ayecode-connect' ),
					'placeholder'     => '',
					'default'         => '',
					'desc_tip'        => true,
					'group'           => 'wrapper-styles',
					'element_require' => '[%' . $type . '%]=="custom-gradient"',
				)
			);

			$input[ $type . '_on_text' ] = array(
				'type'            => 'checkbox',
				'title'           => __( 'Background on text', 'ayecode-connect' ),
				'default'         => '',
				'value'           => '1',
				'desc_tip'        => false,
				'desc'            => __( 'This will show the background on the text.', 'ayecode-connect' ),
				'group'           => 'wrapper-styles',
				'element_require' => '[%' . $type . '%]=="custom-gradient"',
			);
		}

		if ( $overwrite_image !== false ) {
			$input[ $type . '_image_fixed' ] = array(
				'type'            => 'checkbox',
				'title'           => __( 'Fixed background', 'ayecode-connect' ),
				'default'         => '',
				'desc_tip'        => true,
				'group'           => ! empty( $overwrite_image['group'] ) ? $overwrite_image['group'] : 'wrapper-styles',
				'element_require' => '( [%' . $type . '%]=="" || [%' . $type . '%]=="custom-color" || [%' . $type . '%]=="custom-gradient" || [%' . $type . '%]=="transparent" )',
			);

			$input[ $type . '_image_use_featured' ] = array(
				'type'            => 'checkbox',
				'title'           => __( 'Use featured image', 'ayecode-connect' ),
				'default'         => '',
				'desc_tip'        => true,
				'group'           => ! empty( $overwrite_image['group'] ) ? $overwrite_image['group'] : 'wrapper-styles',
				'element_require' => '( [%' . $type . '%]=="" || [%' . $type . '%]=="custom-color" || [%' . $type . '%]=="custom-gradient" || [%' . $type . '%]=="transparent" )',
			);

			$input[ $type . '_image' ] = wp_parse_args(
				$overwrite_image,
				array(
					'type'        => 'image',
					'title'       => __( 'Custom image', 'ayecode-connect' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
					'focalpoint'  => true,
					'group'       => 'wrapper-styles',
				)
			);

			$input[ $type . '_image_id' ] = wp_parse_args(
				$overwrite_image,
				array(
					'type'        => 'hidden',
					'hidden_type' => 'number',
					'title'       => '',
					'placeholder' => '',
					'default'     => '',
					'group'       => 'wrapper-styles',
				)
			);

			$input[ $type . '_image_xy' ] = wp_parse_args(
				$overwrite_image,
				array(
					'type'        => 'image_xy',
					'title'       => '',
					'placeholder' => '',
					'default'     => '',
					'group'       => 'wrapper-styles',
				)
			);
		}

		return $input;
	}

	/**
	 * Build a display field definition.
	 *
	 * @param string $type      The field key (default 'display').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function display_input( $type = 'display', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
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
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Display', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_display_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a single background-colour field definition (no gradient/image support).
	 *
	 * For a full background group with colour, gradient and image use background_inputs().
	 *
	 * @param string $type      The field key (default 'bg').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function background_input( $type = 'bg', $overwrite = array() ) {
		$options = array(
			           ''            => __( 'None', 'ayecode-connect' ),
			           'transparent' => __( 'Transparent', 'ayecode-connect' ),
		           ) + \AyeCode\SuperDuper\Fields\ColorFields::aui_colors( false, false, false, false, true );

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Background color', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_background_input';
		}

		return $input;
	}

	/**
	 * Build an opacity field definition.
	 *
	 * @param string $type      The field key (default 'opacity').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function opacity_input( $type = 'opacity', $overwrite = array() ) {
		$options = array(
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
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Opacity', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_opacity_input';
		}

		return $input;
	}

	/**
	 * Build a hover-animations (multiple select) field definition.
	 *
	 * @param string $type      The field key (default 'hover_animations').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function hover_animations_input( $type = 'hover_animations', $overwrite = array() ) {
		$options = array(
			''                 => __( 'none', 'ayecode-connect' ),
			'hover-zoom'       => __( 'Zoom', 'ayecode-connect' ),
			'hover-shadow'     => __( 'Shadow', 'ayecode-connect' ),
			'hover-move-up'    => __( 'Move up', 'ayecode-connect' ),
			'hover-move-down'  => __( 'Move down', 'ayecode-connect' ),
			'hover-move-left'  => __( 'Move left', 'ayecode-connect' ),
			'hover-move-right' => __( 'Move right', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'multiple' => true,
			'title'    => 'hover-animations',
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'hover-animations',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_hover_animations_input';
		}

		return $input;
	}

	/**
	 * Build a hover-icon-animation field definition.
	 *
	 * @param string $type      The field key (default 'hover_icon_animation').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function hover_icon_animation_input( $type = 'hover_icon_animation', $overwrite = array() ) {
		$options = array(
			''                    => __( 'none', 'ayecode-connect' ),
			'animate-shake'       => __( 'Shake', 'ayecode-connect' ),
			'animate-pulse'       => __( 'Pulse', 'ayecode-connect' ),
			'animate-rotate'      => __( 'Rotate', 'ayecode-connect' ),
			'animate-scale'       => __( 'Scale', 'ayecode-connect' ),
			'animate-slide-end'   => __( 'Slide end', 'ayecode-connect' ),
			'animate-slide-start' => __( 'Slide start', 'ayecode-connect' ),
			'animate-slide-up'    => __( 'Slide up', 'ayecode-connect' ),
			'animate-slide-down'  => __( 'Slide down', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'multiple' => false,
			'title'    => __( 'Icon Hover Animations', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'hover-animations',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_hover_icon_animation_input';
		}

		return $input;
	}

	/**
	 * Build a z-index field definition.
	 *
	 * @param string $type      The field key (default 'zindex').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function zindex_input( $type = 'zindex', $overwrite = array() ) {
		$options = array(
			''          => __( 'Default', 'ayecode-connect' ),
			'zindex-0'  => '0',
			'zindex-1'  => '1',
			'zindex-5'  => '5',
			'zindex-10' => '10',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Z-index', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_zindex_input';
		}

		return $input;
	}

	/**
	 * Build an overflow field definition.
	 *
	 * @param string $type      The field key (default 'overflow').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function overflow_input( $type = 'overflow', $overwrite = array() ) {
		$options = array(
			''                 => __( 'Default', 'ayecode-connect' ),
			'overflow-auto'    => __( 'Auto', 'ayecode-connect' ),
			'overflow-hidden'  => __( 'Hidden', 'ayecode-connect' ),
			'overflow-visible' => __( 'Visible', 'ayecode-connect' ),
			'overflow-scroll'  => __( 'Scroll', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Overflow', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_overflow_input';
		}

		return $input;
	}

	/**
	 * Build a scrollbars-style field definition.
	 *
	 * @param string $type      The field key (default 'scrollbars').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function scrollbars_input( $type = 'scrollbars', $overwrite = array() ) {
		$options = array(
			''               => __( 'Default', 'ayecode-connect' ),
			'scrollbars-ios' => __( 'IOS Style', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Scrollbars', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_scrollbars_input';
		}

		return $input;
	}
}
