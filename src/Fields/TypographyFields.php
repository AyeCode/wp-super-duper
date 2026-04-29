<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for typography-related field definitions.
 *
 * @version 3.0.4-beta
 */
final class TypographyFields {

	/**
	 * Build a font-size + custom-size field group.
	 *
	 * @param string     $type             The base field key (default 'font_size').
	 * @param array      $overwrite        Overrides for the size select.
	 * @param array|bool $overwrite_custom Overrides for the custom-size input; false to omit.
	 * @return array<string, array>
	 */
	public static function font_size_input_group( $type = 'font_size', $overwrite = array(), $overwrite_custom = array() ) {
		$inputs = array();

		if ( $overwrite !== false ) {
			$inputs[ $type ] = self::font_size_input( $type, $overwrite, true );
		}

		if ( $overwrite_custom !== false ) {
			$custom            = $type . '_custom';
			$inputs[ $custom ] = self::font_custom_size_input( $custom, $overwrite_custom, $type );
		}

		return $inputs;
	}

	/**
	 * Build a font-weight / appearance field definition.
	 *
	 * @param string $type      The field key (default 'font_weight').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_weight_input( $type = 'font_weight', $overwrite = array() ) {
		$options = array(
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
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Appearance', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_font_weight_input';
		}

		return $input;
	}

	/**
	 * Build a font-case (text-transform) field definition.
	 *
	 * @param string $type      The field key (default 'font_weight').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_case_input( $type = 'font_weight', $overwrite = array() ) {
		$options = array(
			''                => __( 'Default', 'ayecode-connect' ),
			'text-lowercase'  => __( 'lowercase', 'ayecode-connect' ),
			'text-uppercase'  => __( 'UPPERCASE', 'ayecode-connect' ),
			'text-capitalize' => __( 'Capitalize', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Letter case', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_font_case_input';
		}

		return $input;
	}

	/**
	 * Build a font-italic field definition.
	 *
	 * @param string $type      The field key (default 'font_italic').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_italic_input( $type = 'font_italic', $overwrite = array() ) {
		$options = array(
			''            => __( 'No', 'ayecode-connect' ),
			'font-italic' => __( 'Yes', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Font italic', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_font_italic_input';
		}

		return $input;
	}

	/**
	 * Build a text-align field definition.
	 *
	 * @param string $type      The field key (default 'text_align').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function text_align_input( $type = 'text_align', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                                  => __( 'Default', 'ayecode-connect' ),
			'text' . $device_size . '-start'    => __( 'Start', 'ayecode-connect' ),
			'text' . $device_size . '-end'      => __( 'End', 'ayecode-connect' ),
			'text' . $device_size . '-center'   => __( 'Center', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Text align', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_text_align_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive text-align field group (mobile, tablet, desktop).
	 *
	 * Returns three fields keyed as `text_align`, `text_align_md`, `text_align_lg`.
	 *
	 * @param string $type      The base field key (default 'text_align').
	 * @param array  $overwrite Overrides applied to all three breakpoint fields.
	 * @return array<string, array>
	 */
	public static function text_align_input_group( $type = 'text_align', $overwrite = array() ) {
		$devices = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		$inputs = array();
		foreach ( $devices as $suffix => $device ) {
			$key             = $type . $suffix;
			$field_overwrite = $device ? array_merge( $overwrite, [ 'device_type' => $device ] ) : $overwrite;
			$inputs[ $key ]  = self::text_align_input( $type, $field_overwrite );
		}

		return $inputs;
	}

	/**
	 * Build a font-line-height field definition.
	 *
	 * @param string $type      The field key (default 'font_line_height').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function font_line_height_input( $type = 'font_line_height', $overwrite = array() ) {
		$defaults = array(
			'type'              => 'number',
			'title'             => __( 'Font Line Height', 'ayecode-connect' ),
			'default'           => '',
			'placeholder'       => '1.75',
			'custom_attributes' => array(
				'step' => '0.1',
				'min'  => '0',
				'max'  => '100',
			),
			'desc_tip'          => true,
			'group'             => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_font_line_height_input';
		}

		return $input;
	}

	/**
	 * Build a text-justify (checkbox) field definition.
	 *
	 * @param string $type      The field key (default 'text_justify').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function text_justify_input( $type = 'text_justify', $overwrite = array() ) {
		$defaults = array(
			'type'     => 'checkbox',
			'title'    => __( 'Text justify', 'ayecode-connect' ),
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_text_justify_input';
		}

		return $input;
	}

	/**
	 * Build a text-color + custom-color field group.
	 *
	 * @param string     $type             The base field key (default 'text_color').
	 * @param array      $overwrite        Overrides for the color select.
	 * @param array|bool $overwrite_custom Overrides for the custom color picker; false to omit.
	 * @return array<string, array>
	 */
	public static function text_color_input_group( $type = 'text_color', $overwrite = array(), $overwrite_custom = array() ) {
		$inputs = array();

		if ( $overwrite !== false ) {
			$inputs[ $type ] = self::text_color_input( $type, $overwrite, true );
		}

		if ( $overwrite_custom !== false ) {
			$custom            = $type . '_custom';
			$inputs[ $custom ] = self::custom_color_input( $custom, $overwrite_custom, $type );
		}

		return $inputs;
	}

	/**
	 * Build a text-colour field definition.
	 *
	 * @param string $type       The field key (default 'text_color').
	 * @param array  $overwrite  Field config overrides.
	 * @param bool   $has_custom Whether to include a "Custom color" option.
	 * @param bool   $emphasis   Whether to include emphasis colour variants.
	 * @return array
	 */
	public static function text_color_input( $type = 'text_color', $overwrite = array(), $has_custom = false, $emphasis = true ) {
		$options = array(
			           '' => __( 'None', 'ayecode-connect' ),
		           ) + \AyeCode\SuperDuper\Fields\ColorFields::aui_colors( false, false, false, false, false, true );

		if ( $has_custom ) {
			$options['custom'] = __( 'Custom color', 'ayecode-connect' );
		}

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Text color', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$function_name  = $has_custom ? '_has_custom' : '';
			$function_name .= $emphasis ? '' : '_no_emphasis';
			$input['block_component'] = 'sd_get_text_color_input' . $function_name;
		}

		return $input;
	}

	/**
	 * Build a custom colour-picker field definition.
	 *
	 * Typically paired with text_color_input() when $has_custom is true.
	 *
	 * @param string $type        The field key (default 'color_custom').
	 * @param array  $overwrite   Field config overrides.
	 * @param string $parent_type When set, adds an element_require rule pointing at the parent.
	 * @return array
	 */
	public static function custom_color_input( $type = 'color_custom', $overwrite = array(), $parent_type = '' ) {
		$defaults = array(
			'type'        => 'color',
			'title'       => __( 'Custom color', 'ayecode-connect' ),
			'default'     => '',
			'placeholder' => '',
			'desc_tip'    => true,
			'group'       => 'typography',
		);

		if ( $parent_type ) {
			$defaults['element_require'] = '[%' . $parent_type . '%]=="custom"';
		}

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$function_name            = $parent_type ? '_' . $parent_type : '';
			$input['block_component'] = 'sd_get_custom_color_input' . $function_name;
		}

		return $input;
	}

	/**
	 * Build a font-size field definition.
	 *
	 * @param string $type       The field key (default 'font_size').
	 * @param array  $overwrite  Field config overrides.
	 * @param bool   $has_custom Whether to include a "Custom size" option.
	 * @return array
	 */
	public static function font_size_input( $type = 'font_size', $overwrite = array(), $has_custom = false ) {

		$options[] = __( 'Inherit from parent', 'ayecode-connect' );
		// responsive font sizes
		$options['fs-base'] = 'fs-base (body default)';
		$options['fs-6']    = 'fs-6';
		$options['fs-5']    = 'fs-5';
		$options['fs-4']    = 'fs-4';
		$options['fs-3']    = 'fs-3';
		$options['fs-2']    = 'fs-2';
		$options['fs-1']    = 'fs-1';

		// custom
		$options['fs-lg']  = 'fs-lg';
		$options['fs-sm']  = 'fs-sm';
		$options['fs-xs']  = 'fs-xs';
		$options['fs-xxs'] = 'fs-xxs';


		$options = $options + array(
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
		);

		$options['display-5'] = 'display-5';
		$options['display-6'] = 'display-6';


		if ( $has_custom ) {
			$options['custom'] = __( 'Custom size', 'ayecode-connect' );
		}

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Font size', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'typography',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$function_name            = $has_custom ? '_has_custom' : '';
			$input['block_component'] = 'sd_get_font_size_input' . $function_name;
		}

		return $input;
	}

	/**
	 * Build a custom font-size (numeric rem) field definition.
	 *
	 * @param string $type        The field key (default 'font_size_custom').
	 * @param array  $overwrite   Field config overrides.
	 * @param string $parent_type When set, adds an element_require rule pointing at the parent.
	 * @return array
	 */
	public static function font_custom_size_input( $type = 'font_size_custom', $overwrite = array(), $parent_type = '' ) {
		$defaults = array(
			'type'              => 'number',
			'title'             => __( 'Font size (rem)', 'ayecode-connect' ),
			'default'           => '',
			'placeholder'       => '1.25',
			'custom_attributes' => array(
				'step' => '0.1',
				'min'  => '0',
				'max'  => '100',
			),
			'desc_tip'          => true,
			'group'             => 'typography',
		);

		if ( $parent_type ) {
			$defaults['element_require'] = '[%' . $parent_type . '%]=="custom"';
		}

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$function_name            = $parent_type ? '_' . $parent_type : '';
			$input['block_component'] = 'sd_get_font_custom_size_input' . $function_name;
		}

		return $input;
	}
}
