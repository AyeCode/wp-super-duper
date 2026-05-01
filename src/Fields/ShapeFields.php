<?php

namespace AyeCode\SuperDuper\Fields;

use AyeCode\SuperDuper\Helpers\ColorOptions;
use AyeCode\SuperDuper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for shape-divider-related field definitions.
 *
 * Note: element_require conditional expression generation has moved to
 * Utils::element_require() and should be called from there.
 *
 * @version 3.0.4-beta
 */
final class ShapeFields {

	/**
	 * Return all shape-divider field definitions keyed by argument name.
	 *
	 * @param string     $prefix            Base field key (default 'sd' → 'sd', 'sd_position', …).
	 * @param array      $overwrite         Overrides for the main shape select.
	 * @param array|bool $overwrite_color   Overrides for position/color fields. false = keep defaults.
	 * @param array|bool $overwrite_width   Overrides for the width range field.
	 * @param array|bool $overwrite_image   Unused; retained for signature compatibility.
	 * @return array<string, array>
	 */
	public static function divider_group( string $prefix = 'sd', array $overwrite = [], $overwrite_color = [], $overwrite_width = [], $overwrite_image = [] ): array {
		$options = [
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
		];

		$not_empty_er = '[%' . $prefix . '%]!=""';

		$fields = [];

		$fields[ $prefix ] = wp_parse_args(
			$overwrite,
			[
				'type'     => 'select',
				'title'    => __( 'Type', 'ayecode-connect' ),
				'options'  => $options,
				'default'  => '',
				'desc_tip' => true,
				'group'    => 'shape-divider',
			]
		);

		$fields[ $prefix . '_notice' ] = [
			'type'            => 'notice',
			'desc'            => __( 'Parent element must be position `relative`', 'ayecode-connect' ),
			'status'          => 'warning',
			'group'           => 'shape-divider',
			'element_require' => $not_empty_er,
		];

		$fields[ $prefix . '_position' ] = wp_parse_args(
			$overwrite_color !== false ? (array) $overwrite_color : [],
			[
				'type'            => 'select',
				'title'           => __( 'Position', 'ayecode-connect' ),
				'options'         => [
					'top'    => __( 'Top', 'ayecode-connect' ),
					'bottom' => __( 'Bottom', 'ayecode-connect' ),
				],
				'desc_tip'        => true,
				'group'           => 'shape-divider',
				'element_require' => $not_empty_er,
			]
		);

		$color_options = ColorOptions::aui( [ 'none', 'transparent', 'core', 'subtle' ] )
			+ [ 'custom-color' => __( 'Custom Color', 'ayecode-connect' ) ];

		$fields[ $prefix . '_color' ] = wp_parse_args(
			$overwrite_color !== false ? (array) $overwrite_color : [],
			[
				'type'            => 'select',
				'title'           => __( 'Color', 'ayecode-connect' ),
				'options'         => $color_options,
				'desc_tip'        => true,
				'group'           => 'shape-divider',
				'element_require' => $not_empty_er,
			]
		);

		$fields[ $prefix . '_custom_color' ] = wp_parse_args(
			$overwrite_color !== false ? (array) $overwrite_color : [],
			[
				'type'            => 'color',
				'title'           => __( 'Custom color', 'ayecode-connect' ),
				'placeholder'     => '',
				'default'         => '#0073aa',
				'desc_tip'        => true,
				'group'           => 'shape-divider',
				'element_require' => '[%' . $prefix . '_color%]=="custom-color" && [%' . $prefix . '%]!=""',
			]
		);

		$fields[ $prefix . '_width' ] = wp_parse_args(
			$overwrite_width !== false ? (array) $overwrite_width : [],
			[
				'type'              => 'range',
				'title'             => __( 'Width', 'ayecode-connect' ),
				'placeholder'       => '',
				'default'           => '200',
				'desc_tip'          => true,
				'custom_attributes' => [ 'min' => 100, 'max' => 300 ],
				'group'             => 'shape-divider',
				'element_require'   => $not_empty_er,
			]
		);

		$fields[ $prefix . '_height' ] = [
			'type'              => 'range',
			'title'             => __( 'Height', 'ayecode-connect' ),
			'default'           => '100',
			'desc_tip'          => true,
			'custom_attributes' => [ 'min' => 0, 'max' => 500 ],
			'group'             => 'shape-divider',
			'element_require'   => $not_empty_er,
		];

		$requires = [
			'mountains'             => [ 'flip' ],
			'drops'                 => [ 'flip', 'invert' ],
			'clouds'                => [ 'flip', 'invert' ],
			'zigzag'                => [],
			'pyramids'              => [ 'flip', 'invert' ],
			'triangle'              => [ 'invert' ],
			'triangle-asymmetrical' => [ 'flip', 'invert' ],
			'tilt'                  => [ 'flip' ],
			'opacity-tilt'          => [ 'flip' ],
			'opacity-fan'           => [],
			'curve'                 => [ 'invert' ],
			'curve-asymmetrical'    => [ 'flip', 'invert' ],
			'waves'                 => [ 'flip', 'invert' ],
			'wave-brush'            => [ 'flip' ],
			'waves-pattern'         => [ 'flip' ],
			'arrow'                 => [ 'invert' ],
			'split'                 => [ 'invert' ],
			'book'                  => [ 'invert' ],
		];

		$fields[ $prefix . '_flip' ] = [
			'type'            => 'checkbox',
			'title'           => __( 'Flip', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'shape-divider',
			'element_require' => Utils::element_require( $requires, 'flip', $prefix ),
		];

		$fields[ $prefix . '_invert' ] = [
			'type'            => 'checkbox',
			'title'           => __( 'Invert', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'shape-divider',
			'element_require' => Utils::element_require( $requires, 'invert', $prefix ),
		];

		$fields[ $prefix . '_btf' ] = [
			'type'            => 'checkbox',
			'title'           => __( 'Bring to front', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'shape-divider',
			'element_require' => $not_empty_er,
		];

		return $fields;
	}
}
