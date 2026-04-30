<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for spacing-related field definitions (margin and padding).
 *
 * Single-field methods accept $side ('top'|'right'|'bottom'|'left').
 * Group methods return a keyed array of field definitions ready to merge into arguments.
 *
 * @version 3.0.4-beta
 */
final class SpacingFields {

	// -------------------------------------------------------------------------
	// Single-field methods
	// -------------------------------------------------------------------------

	/**
	 * Build a margin field definition for one side.
	 *
	 * @param string $side             'top' | 'right' | 'bottom' | 'left'
	 * @param array  $overwrite        Field config overrides.
	 * @param bool   $include_negatives Whether to include negative margin options.
	 * @return array
	 */
	public static function margin( string $side, array $overwrite = [], bool $include_negatives = true ): array {
		$options = [
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
		];

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

		$side_map = [
			'top'    => [
				'key'   => 'mt',
				'title' => __( 'Margin top', 'ayecode-connect' ),
				'icon'  => 'box-top',
				'row'   => [
					'title' => __( 'Margins', 'ayecode-connect' ),
					'key'   => 'wrapper-margins',
					'open'  => true,
					'class' => 'text-center',
				],
			],
			'right'  => [
				'key'   => 'mr',
				'title' => __( 'Margin right', 'ayecode-connect' ),
				'icon'  => 'box-right',
				'row'   => [ 'key' => 'wrapper-margins' ],
			],
			'bottom' => [
				'key'   => 'mb',
				'title' => __( 'Margin bottom', 'ayecode-connect' ),
				'icon'  => 'box-bottom',
				'row'   => [ 'key' => 'wrapper-margins' ],
			],
			'left'   => [
				'key'   => 'ml',
				'title' => __( 'Margin left', 'ayecode-connect' ),
				'icon'  => 'box-left',
				'row'   => [ 'key' => 'wrapper-margins', 'close' => true ],
			],
		];

		$meta = $side_map[ $side ] ?? $side_map['top'];

		$defaults = [
			'type'     => 'select',
			'title'    => $meta['title'],
			'icon'     => $meta['icon'],
			'row'      => $meta['row'],
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		// Keep row key in sync with group when overridden.
		if ( $input['group'] !== 'wrapper-styles' ) {
			$input['row']['key'] = $input['group'];
		}

		// block_component identifier used by Gutenberg JS — kept stable for backward compat.
		$clean_overwrite = $overwrite;
		unset( $clean_overwrite['device_type'] );
		if ( empty( $clean_overwrite ) && $include_negatives ) {
			$input['block_component'] = 'sd_get_margin_input_' . $meta['key'];
		}

		return $input;
	}

	/**
	 * Build a padding field definition for one side.
	 *
	 * @param string $side      'top' | 'right' | 'bottom' | 'left'
	 * @param array  $overwrite Field config overrides.
	 * @return array
	 */
	public static function padding( string $side, array $overwrite = [] ): array {
		$options = [
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
		];

		$side_map = [
			'top'    => [
				'key'   => 'pt',
				'title' => __( 'Padding top', 'ayecode-connect' ),
				'icon'  => 'box-top',
				'row'   => [
					'title' => __( 'Padding', 'ayecode-connect' ),
					'key'   => 'wrapper-padding',
					'open'  => true,
					'class' => 'text-center',
				],
			],
			'right'  => [
				'key'   => 'pr',
				'title' => __( 'Padding right', 'ayecode-connect' ),
				'icon'  => 'box-right',
				'row'   => [ 'key' => 'wrapper-padding' ],
			],
			'bottom' => [
				'key'   => 'pb',
				'title' => __( 'Padding bottom', 'ayecode-connect' ),
				'icon'  => 'box-bottom',
				'row'   => [ 'key' => 'wrapper-padding' ],
			],
			'left'   => [
				'key'   => 'pl',
				'title' => __( 'Padding left', 'ayecode-connect' ),
				'icon'  => 'box-left',
				'row'   => [ 'key' => 'wrapper-padding', 'close' => true ],
			],
		];

		$meta = $side_map[ $side ] ?? $side_map['top'];

		$defaults = [
			'type'     => 'select',
			'title'    => $meta['title'],
			'icon'     => $meta['icon'],
			'row'      => $meta['row'],
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		// block_component identifier used by Gutenberg JS — kept stable for backward compat.
		$clean_overwrite = $overwrite;
		unset( $clean_overwrite['device_type'] );
		if ( empty( $clean_overwrite ) ) {
			$input['block_component'] = 'sd_get_padding_input_' . $meta['key'];
		}

		return $input;
	}

	// -------------------------------------------------------------------------
	// Group methods
	// -------------------------------------------------------------------------

	/**
	 * Return all four margin field definitions keyed by argument name.
	 *
	 * @param array $overwrite        Per-field overwrite config.
	 * @param bool  $include_negatives Whether to include negative margin options.
	 * @return array<string, array> ['mt' => [...], 'mr' => [...], 'mb' => [...], 'ml' => [...]]
	 */
	public static function margin_group( array $overwrite = [], bool $include_negatives = true ): array {
		return [
			'mt' => self::margin( 'top', $overwrite, $include_negatives ),
			'mr' => self::margin( 'right', $overwrite, $include_negatives ),
			'mb' => self::margin( 'bottom', $overwrite, $include_negatives ),
			'ml' => self::margin( 'left', $overwrite, $include_negatives ),
		];
	}

	/**
	 * Return all four padding field definitions keyed by argument name.
	 *
	 * @param array $overwrite Per-field overwrite config.
	 * @return array<string, array> ['pt' => [...], 'pr' => [...], 'pb' => [...], 'pl' => [...]]
	 */
	public static function padding_group( array $overwrite = [] ): array {
		return [
			'pt' => self::padding( 'top', $overwrite ),
			'pr' => self::padding( 'right', $overwrite ),
			'pb' => self::padding( 'bottom', $overwrite ),
			'pl' => self::padding( 'left', $overwrite ),
		];
	}
}
