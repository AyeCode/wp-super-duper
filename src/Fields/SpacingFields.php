<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for spacing-related field definitions (margin and padding).
 *
 * @version 3.0.4-beta
 */
final class SpacingFields {

	/**
	 * Build a margin field definition for one side (mt, mr, mb, ml).
	 *
	 * @param string $type             The side key: 'mt', 'mr', 'mb', or 'ml'.
	 * @param array  $overwrite        Field config overrides.
	 * @param bool   $include_negatives Whether to include negative margin options.
	 * @return array
	 */
	public static function margin_input( $type = 'mt', $overwrite = array(), $include_negatives = true ) {

		$options = array(
			''     => __( 'None', 'ayecode-connect' ),
			'auto' => __( 'auto', 'ayecode-connect' ),
			'0'    => '0',
			'1'    => '1',
			'2'    => '2',
			'3'    => '3',
			'4'    => '4',
			'5'    => '5',
			'6'    => '6',
			'7'    => '7',
			'8'    => '8',
			'9'    => '9',
			'10'   => '10',
			'11'   => '11',
			'12'   => '12',
		);

		if ( $include_negatives ) {
			$options['n1']  = '-1';
			$options['n2']  = '-2';
			$options['n3']  = '-3';
			$options['n4']  = '-4';
			$options['n5']  = '-5';
			$options['n6']  = '-6';
			$options['n7']  = '-7';
			$options['n8']  = '-8';
			$options['n9']  = '-9';
			$options['n10'] = '-10';
			$options['n11'] = '-11';
			$options['n12'] = '-12';
		}

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Margin top', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		// title
		if ( $type == 'mt' ) {
			$defaults['title'] = __( 'Margin top', 'ayecode-connect' );
			$defaults['icon']  = 'box-top';
			$defaults['row']   = array(
				'title' => __( 'Margins', 'ayecode-connect' ),
				'key'   => 'wrapper-margins',
				'open'  => true,
				'class' => 'text-center',
			);
		} elseif ( $type == 'mr' ) {
			$defaults['title'] = __( 'Margin right', 'ayecode-connect' );
			$defaults['icon']  = 'box-right';
			$defaults['row']   = array(
				'key' => 'wrapper-margins',
			);
		} elseif ( $type == 'mb' ) {
			$defaults['title'] = __( 'Margin bottom', 'ayecode-connect' );
			$defaults['icon']  = 'box-bottom';
			$defaults['row']   = array(
				'key' => 'wrapper-margins',
			);
		} elseif ( $type == 'ml' ) {
			$defaults['title'] = __( 'Margin left', 'ayecode-connect' );
			$defaults['icon']  = 'box-left';
			$defaults['row']   = array(
				'key'   => 'wrapper-margins',
				'close' => true,
			);
		}

		$input = wp_parse_args( $overwrite, $defaults );

		// row key fix
		if ( $input['group'] !== 'wrapper-styles' ) {
			$input['row']['key'] = $input['group'];
		}

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) && $include_negatives ) {
			$input['block_component'] = $type ? 'sd_get_margin_input_' . $type : 'sd_get_margin_input';
		}

		return $input;
	}

	/**
	 * Build a padding field definition for one side (pt, pr, pb, pl).
	 *
	 * @param string $type      The side key: 'pt', 'pr', 'pb', or 'pl'.
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function padding_input( $type = 'pt', $overwrite = array() ) {
		$options = array(
			''   => __( 'None', 'ayecode-connect' ),
			'0'  => '0',
			'1'  => '1',
			'2'  => '2',
			'3'  => '3',
			'4'  => '4',
			'5'  => '5',
			'6'  => '6',
			'7'  => '7',
			'8'  => '8',
			'9'  => '9',
			'10' => '10',
			'11' => '11',
			'12' => '12',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Padding top', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		// title
		if ( $type == 'pt' ) {
			$defaults['title'] = __( 'Padding top', 'ayecode-connect' );
			$defaults['icon']  = 'box-top';
			$defaults['row']   = array(
				'title' => __( 'Padding', 'ayecode-connect' ),
				'key'   => 'wrapper-padding',
				'open'  => true,
				'class' => 'text-center',
			);
		} elseif ( $type == 'pr' ) {
			$defaults['title'] = __( 'Padding right', 'ayecode-connect' );
			$defaults['icon']  = 'box-right';
			$defaults['row']   = array(
				'key' => 'wrapper-padding',
			);
		} elseif ( $type == 'pb' ) {
			$defaults['title'] = __( 'Padding bottom', 'ayecode-connect' );
			$defaults['icon']  = 'box-bottom';
			$defaults['row']   = array(
				'key' => 'wrapper-padding',
			);
		} elseif ( $type == 'pl' ) {
			$defaults['title'] = __( 'Padding left', 'ayecode-connect' );
			$defaults['icon']  = 'box-left';
			$defaults['row']   = array(
				'key'   => 'wrapper-padding',
				'close' => true,
			);
		}

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = $type ? 'sd_get_padding_input_' . $type : 'sd_get_padding_input';
		}

		return $input;
	}
}
