<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for layout-related field definitions
 * (container, position, col, row, width, height, etc.).
 *
 * Single-field methods take only $overwrite = [].
 * $side param is used where a field logically has a direction ('top' | 'bottom').
 *
 * @version 3.0.4-beta
 */
final class LayoutFields {

	/**
	 * Container class select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function container( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Type', 'ayecode-connect' ),
			'options'  => [
				'container'                                              => __( 'container (default)', 'ayecode-connect' ),
				'container-sm'                                           => 'container-sm',
				'container-md'                                           => 'container-md',
				'container-lg'                                           => 'container-lg',
				'container-xl'                                           => 'container-xl',
				'container-xxl'                                          => 'container-xxl',
				'container-fluid'                                        => 'container-fluid',
				'row'                                                    => 'row',
				'col'                                                    => 'col',
				'card'                                                   => 'card',
				'card-header'                                            => 'card-header',
				'card-img-top'                                           => 'card-img-top',
				'card-body'                                              => 'card-body',
				'card-footer'                                            => 'card-footer',
				'list-group'                                             => 'list-group',
				'list-group list-group-flush'                            => 'list-group list-group-flush',
				'list-group list-group-numbered'                         => 'list-group list-group-numbered',
				'list-group list-group-flush list-group-numbered'        => 'list-group list-group-flush list-group-numbered',
				'list-group list-group-horizontal'                       => 'list-group list-group-horizontal',
				'list-group list-group-horizontal list-group-numbered'   => 'list-group list-group-horizontal list-group-numbered',
				'list-group-item'                                        => 'list-group-item',
				''                                                       => __( 'no container class', 'ayecode-connect' ),
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'container',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_container_class_input';
		}

		return $input;
	}

	/**
	 * Position class select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function position( array $overwrite = [] ): array {
		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Position', 'ayecode-connect' ),
			'options'  => [
				''                  => __( 'Default', 'ayecode-connect' ),
				'position-static'   => 'static',
				'position-relative' => 'relative',
				'position-absolute' => 'absolute',
				'position-fixed'    => 'fixed',
				'position-sticky'   => 'sticky',
				'fixed-top'         => 'fixed-top',
				'fixed-bottom'      => 'fixed-bottom',
				'sticky-top'        => 'sticky-top',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_position_class_input';
		}

		return $input;
	}

	/**
	 * Sticky offset number input for one side.
	 *
	 * @param string $side      'top' | 'bottom'
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function sticky_offset( string $side, array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'number',
			'title'           => __( 'Sticky offset', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '[%position%]=="sticky" || [%position%]=="sticky-top"',
		];

		if ( 'top' === $side ) {
			$defaults['title'] = __( 'Top offset', 'ayecode-connect' );
			$defaults['icon']  = 'box-top';
			$defaults['row']   = [
				'title' => __( 'Sticky offset', 'ayecode-connect' ),
				'key'   => 'sticky-offset',
				'open'  => true,
				'class' => 'text-center',
			];
		} elseif ( 'bottom' === $side ) {
			$defaults['title'] = __( 'Bottom offset', 'ayecode-connect' );
			$defaults['icon']  = 'box-bottom';
			$defaults['row']   = [
				'key'   => 'sticky-offset',
				'close' => true,
			];
		}

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_sticky_offset_input_' . $side;
		}

		return $input;
	}

	/**
	 * Column width select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function col( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Column width', 'ayecode-connect' ),
			'options'         => [
				''     => __( 'Default', 'ayecode-connect' ),
				'auto' => __( 'auto', 'ayecode-connect' ),
				'1'    => '1/12',
				'2'    => '2/12',
				'3'    => '3/12',
				'4'    => '4/12',
				'5'    => '5/12',
				'6'    => '6/12',
				'7'    => '7/12',
				'8'    => '8/12',
				'9'    => '9/12',
				'10'   => '10/12',
				'11'   => '11/12',
				'12'   => '12/12',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'container',
			'element_require' => '[%container%]=="col"',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_col_input';
		}

		return $input;
	}

	/**
	 * Row columns select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function row_cols( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Row columns', 'ayecode-connect' ),
			'options'         => [
				''  => __( 'auto', 'ayecode-connect' ),
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'6' => '6',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'container',
			'element_require' => '[%container%]=="row"',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_row_cols_input';
		}

		return $input;
	}

	/**
	 * Absolute position select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function absolute_position( array $overwrite = [] ): array {
		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Absolute Position', 'ayecode-connect' ),
			'options'         => [
				''              => __( 'Default', 'ayecode-connect' ),
				'top-left'      => 'top-left',
				'top-center'    => 'top-center',
				'top-right'     => 'top-right',
				'center-left'   => 'middle-left',
				'center'        => 'center',
				'center-right'  => 'middle-right',
				'bottom-left'   => 'bottom-left',
				'bottom-center' => 'bottom-center',
				'bottom-right'  => 'bottom-right',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '[%position%]=="position-absolute"',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_absolute_position_input';
		}

		return $input;
	}

	/**
	 * Width select field.
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function width( array $overwrite = [] ): array {
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
			'title'    => __( 'Width', 'ayecode-connect' ),
			'options'  => [
				''                           => __( 'Default', 'ayecode-connect' ),
				'w' . $device_size . '-25'   => '25%',
				'w' . $device_size . '-50'   => '50%',
				'w' . $device_size . '-75'   => '75%',
				'w' . $device_size . '-100'  => '100%',
				'w' . $device_size . '-auto' => 'auto',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_width_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Height select field.
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function height( array $overwrite = [] ): array {
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
			'title'    => __( 'Height', 'ayecode-connect' ),
			'options'  => [
				''                           => __( 'Default', 'ayecode-connect' ),
				'h' . $device_size . '-25'   => '25%',
				'h' . $device_size . '-50'   => '50%',
				'h' . $device_size . '-75'   => '75%',
				'h' . $device_size . '-100'  => '100%',
				'h' . $device_size . '-auto' => 'auto',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_height_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Max height text input.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function max_height( array $overwrite = [] ): array {
		$defaults = [
			'type'        => 'text',
			'title'       => __( 'Max height', 'ayecode-connect' ),
			'value'       => '',
			'default'     => '',
			'placeholder' => '',
			'desc_tip'    => true,
			'group'       => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_max_height_input';
		}

		return $input;
	}
}
