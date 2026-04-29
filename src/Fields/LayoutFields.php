<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for layout-related field definitions (container, position).
 *
 * @version 3.0.4-beta
 */
final class LayoutFields {

	/**
	 * Build a container-class field definition.
	 *
	 * @param string $type      The field key (default 'container').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function container_class_input( $type = 'container', $overwrite = array() ) {
		$options = array(
			'container'                                                       => __( 'container (default)', 'ayecode-connect' ),
			'container-sm'                                                    => 'container-sm',
			'container-md'                                                    => 'container-md',
			'container-lg'                                                    => 'container-lg',
			'container-xl'                                                    => 'container-xl',
			'container-xxl'                                                   => 'container-xxl',
			'container-fluid'                                                 => 'container-fluid',
			'row'                                                             => 'row',
			'col'                                                             => 'col',
			'card'                                                            => 'card',
			'card-header'                                                     => 'card-header',
			'card-img-top'                                                    => 'card-img-top',
			'card-body'                                                       => 'card-body',
			'card-footer'                                                     => 'card-footer',
			'list-group'                                                      => 'list-group',
			'list-group list-group-flush'                                     => 'list-group list-group-flush',
			'list-group list-group-numbered'                                  => 'list-group list-group-numbered',
			'list-group list-group-flush list-group-numbered'                 => 'list-group list-group-flush list-group-numbered',
			'list-group list-group-horizontal'                                => 'list-group list-group-horizontal',
			'list-group list-group-horizontal list-group-numbered'            => 'list-group list-group-horizontal list-group-numbered',
			'list-group-item'                                                 => 'list-group-item',
			''                                                                => __( 'no container class', 'ayecode-connect' ),
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Type', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'container',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_container_class_input';
		}

		return $input;
	}

	/**
	 * Build a sticky-offset (top or bottom) field definition.
	 *
	 * @param string $type      'top' or 'bottom'.
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function sticky_offset_input( $type = 'top', $overwrite = array() ) {
		$defaults = array(
			'type'            => 'number',
			'title'           => __( 'Sticky offset', 'ayecode-connect' ),
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '[%position%]=="sticky" || [%position%]=="sticky-top"',
		);

		if ( $type === 'top' ) {
			$defaults['title'] = __( 'Top offset', 'ayecode-connect' );
			$defaults['icon']  = 'box-top';
			$defaults['row']   = array(
				'title' => __( 'Sticky offset', 'ayecode-connect' ),
				'key'   => 'sticky-offset',
				'open'  => true,
				'class' => 'text-center',
			);
		} elseif ( $type === 'bottom' ) {
			$defaults['title'] = __( 'Bottom offset', 'ayecode-connect' );
			$defaults['icon']  = 'box-bottom';
			$defaults['row']   = array(
				'key'   => 'sticky-offset',
				'close' => true,
			);
		}

		$input = wp_parse_args( $overwrite, $defaults );

		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = $type ? 'sd_get_sticky_offset_input_' . $type : 'sd_get_sticky_offset_input';
		}

		return $input;
	}

	/**
	 * Build a position-class field definition.
	 *
	 * @param string $type      The field key (default 'position').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function position_class_input( $type = 'position', $overwrite = array() ) {
		$options = array(
			''                  => __( 'Default', 'ayecode-connect' ),
			'position-static'   => 'static',
			'position-relative' => 'relative',
			'position-absolute' => 'absolute',
			'position-fixed'    => 'fixed',
			'position-sticky'   => 'sticky',
			'fixed-top'         => 'fixed-top',
			'fixed-bottom'      => 'fixed-bottom',
			'sticky-top'        => 'sticky-top',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Position', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_position_class_input';
		}

		return $input;
	}

	/**
	 * Build a column-width (col-N) field definition.
	 *
	 * @param string $type      The field key (default 'col').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function col_input( $type = 'col', $overwrite = array() ) {
		$options = array(
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
		);

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Column width', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'container',
			'element_require' => '[%container%]=="col"',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_col_input';
		}

		return $input;
	}

	/**
	 * Build a row-columns field definition.
	 *
	 * @param string $type      The field key (default 'row_cols').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function row_cols_input( $type = 'row_cols', $overwrite = array() ) {
		$options = array(
			''  => __( 'auto', 'ayecode-connect' ),
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
		);

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Row columns', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'container',
			'element_require' => '[%container%]=="row"',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_row_cols_input';
		}

		return $input;
	}

	/**
	 * Build an absolute-position field definition.
	 *
	 * @param string $type      The field key (default 'absolute_position').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function absolute_position_input( $type = 'absolute_position', $overwrite = array() ) {
		$options = array(
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
		);

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Absolute Position', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '[%position%]=="position-absolute"',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_absolute_position_input';
		}

		return $input;
	}

	/**
	 * Build a width field definition (single breakpoint).
	 *
	 * @param string $type      The field key (default 'width').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function width_input( $type = 'width', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                               => __( 'Default', 'ayecode-connect' ),
			'w' . $device_size . '-25'       => '25%',
			'w' . $device_size . '-50'       => '50%',
			'w' . $device_size . '-75'       => '75%',
			'w' . $device_size . '-100'      => '100%',
			'w' . $device_size . '-auto'     => 'auto',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Width', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_width_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a height field definition (single breakpoint).
	 *
	 * @param string $type      The field key (default 'height').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function height_input( $type = 'height', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                           => __( 'Default', 'ayecode-connect' ),
			'h' . $device_size . '-25'   => '25%',
			'h' . $device_size . '-50'   => '50%',
			'h' . $device_size . '-75'   => '75%',
			'h' . $device_size . '-100'  => '100%',
			'h' . $device_size . '-auto' => 'auto',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Height', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_height_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a max-height field definition.
	 *
	 * @param string $type      The field key (default 'max_height').
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function max_height_input( $type = 'max_height', $overwrite = array() ) {
		$defaults = array(
			'type'        => 'text',
			'title'       => __( 'Max height', 'ayecode-connect' ),
			'value'       => '',
			'default'     => '',
			'placeholder' => '',
			'desc_tip'    => true,
			'group'       => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_max_height_input';
		}

		return $input;
	}
}
