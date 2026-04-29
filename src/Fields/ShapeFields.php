<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for shape-divider-related field definitions.
 *
 * @version 3.0.4-beta
 */
final class ShapeFields {

	/**
	 * Build the element_require expression for a shape-divider key.
	 *
	 * Equivalent to the former sd_get_element_require_string() global function.
	 *
	 * @param array  $args Array of shape => supported-keys pairs.
	 * @param string $key  The feature key to check (e.g. 'flip', 'invert').
	 * @param string $type The field-prefix used in the require expression (e.g. 'sd').
	 * @return string
	 */
	public static function element_require_string( $args, $key, $type ) {
		$output   = '';
		$requires = array();

		if ( ! empty( $args ) ) {
			foreach ( $args as $t => $k ) {
				if ( in_array( $key, $k ) ) {
					$requires[] = '[%' . $type . '%]=="' . $t . '"';
				}
			}

			if ( ! empty( $requires ) ) {
				$output = '(' . implode( ' || ', $requires ) . ')';
			}
		}

		return $output;
	}

	/**
	 * Build a full set of shape-divider field definitions.
	 *
	 * @param string $type             Base field key (default 'sd').
	 * @param array  $overwrite        Overrides for the main shape select.
	 * @param array  $overwrite_color  Overrides for position/colour fields.
	 * @param array  $overwrite_gradient Overrides for the width range.
	 * @param array  $overwrite_image  Unused; retained for signature compatibility.
	 * @return array<string, array>
	 */
	public static function divider_inputs( $type = 'sd', $overwrite = array(), $overwrite_color = array(), $overwrite_gradient = array(), $overwrite_image = array() ) {
		$options = array(
			''                      => __( 'None', 'ayecode-connect' ),
			'mountains'             => __( 'Mountains', 'ayecode-connect' ),
			'drops'                 => __( 'Drops', 'ayecode-connect' ),
			'clouds'                => __( 'Clouds', 'ayecode-connect' ),
			'zigzag'                => __( 'Zigzag', 'ayecode-connect' ),
			'pyramids'              => __( 'Pyramids', 'ayecode-connect' ),
			'triangle'              => __( 'Triangle', 'ayecode-connect' ),
			'triangle-asymmetrical' => __( 'Triangle Asymmetrical', 'ayecode-connect' ),
			'tilt'                  => __( 'Tilt', 'ayecode-connect' ),
			'opacity-tilt'          => __( 'Opacity Tilt', 'ayecode-connect' ),
			'opacity-fan'           => __( 'Opacity Fan', 'ayecode-connect' ),
			'curve'                 => __( 'Curve', 'ayecode-connect' ),
			'curve-asymmetrical'    => __( 'Curve Asymmetrical', 'ayecode-connect' ),
			'waves'                 => __( 'Waves', 'ayecode-connect' ),
			'wave-brush'            => __( 'Wave Brush', 'ayecode-connect' ),
			'waves-pattern'         => __( 'Waves Pattern', 'ayecode-connect' ),
			'arrow'                 => __( 'Arrow', 'ayecode-connect' ),
			'split'                 => __( 'Split', 'ayecode-connect' ),
			'book'                  => __( 'Book', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Type', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'shape-divider',
		);

		$input[ $type ] = wp_parse_args( $overwrite, $defaults );

		$input[ $type . '_notice' ] = array(
			'type'            => 'notice',
			'desc'            => __( 'Parent element must be position `relative`', 'ayecode-connect' ),
			'status'          => 'warning',
			'group'           => 'shape-divider',
			'element_require' => '[%' . $type . '%]!=""',
		);

		$input[ $type . '_position' ] = wp_parse_args(
			$overwrite_color,
			array(
				'type'            => 'select',
				'title'           => __( 'Position', 'ayecode-connect' ),
				'options'         => array(
					'top'    => __( 'Top', 'ayecode-connect' ),
					'bottom' => __( 'Bottom', 'ayecode-connect' ),
				),
				'desc_tip'        => true,
				'group'           => 'shape-divider',
				'element_require' => '[%' . $type . '%]!=""',
			)
		);

		$color_options = array(
			               ''            => __( 'None', 'ayecode-connect' ),
			               'transparent' => __( 'Transparent', 'ayecode-connect' ),
		               ) + \AyeCode\SuperDuper\Fields\ColorFields::aui_colors( false, false, false, false, true )
		               + array(
			               'custom-color' => __( 'Custom Color', 'ayecode-connect' ),
		               );

		$input[ $type . '_color' ] = wp_parse_args(
			$overwrite_color,
			array(
				'type'            => 'select',
				'title'           => __( 'Color', 'ayecode-connect' ),
				'options'         => $color_options,
				'desc_tip'        => true,
				'group'           => 'shape-divider',
				'element_require' => '[%' . $type . '%]!=""',
			)
		);

		$input[ $type . '_custom_color' ] = wp_parse_args(
			$overwrite_color,
			array(
				'type'            => 'color',
				'title'           => __( 'Custom color', 'ayecode-connect' ),
				'placeholder'     => '',
				'default'         => '#0073aa',
				'desc_tip'        => true,
				'group'           => 'shape-divider',
				'element_require' => '[%' . $type . '_color%]=="custom-color" && [%' . $type . '%]!=""',
			)
		);

		$input[ $type . '_width' ] = wp_parse_args(
			$overwrite_gradient,
			array(
				'type'              => 'range',
				'title'             => __( 'Width', 'ayecode-connect' ),
				'placeholder'       => '',
				'default'           => '200',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min' => 100,
					'max' => 300,
				),
				'group'             => 'shape-divider',
				'element_require'   => '[%' . $type . '%]!=""',
			)
		);

		$input[ $type . '_height' ] = array(
			'type'              => 'range',
			'title'             => __( 'Height', 'ayecode-connect' ),
			'default'           => '100',
			'desc_tip'          => true,
			'custom_attributes' => array(
				'min' => 0,
				'max' => 500,
			),
			'group'             => 'shape-divider',
			'element_require'   => '[%' . $type . '%]!=""',
		);

		$requires = array(
			'mountains'             => array( 'flip' ),
			'drops'                 => array( 'flip', 'invert' ),
			'clouds'                => array( 'flip', 'invert' ),
			'zigzag'                => array(),
			'pyramids'              => array( 'flip', 'invert' ),
			'triangle'              => array( 'invert' ),
			'triangle-asymmetrical' => array( 'flip', 'invert' ),
			'tilt'                  => array( 'flip' ),
			'opacity-tilt'          => array( 'flip' ),
			'opacity-fan'           => array(),
			'curve'                 => array( 'invert' ),
			'curve-asymmetrical'    => array( 'flip', 'invert' ),
			'waves'                 => array( 'flip', 'invert' ),
			'wave-brush'            => array( 'flip' ),
			'waves-pattern'         => array( 'flip' ),
			'arrow'                 => array( 'invert' ),
			'split'                 => array( 'invert' ),
			'book'                  => array( 'invert' ),
		);

		$input[ $type . '_flip' ] = array(
			'type'            => 'checkbox',
			'title'           => __( 'Flip', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'shape-divider',
			'element_require' => self::element_require_string( $requires, 'flip', 'sd' ),
		);

		$input[ $type . '_invert' ] = array(
			'type'            => 'checkbox',
			'title'           => __( 'Invert', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'shape-divider',
			'element_require' => self::element_require_string( $requires, 'invert', 'sd' ),
		);

		$input[ $type . '_btf' ] = array(
			'type'            => 'checkbox',
			'title'           => __( 'Bring to front', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'shape-divider',
			'element_require' => '[%' . $type . '%]!=""',
		);

		return $input;
	}
}
