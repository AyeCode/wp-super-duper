<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for flex / float layout field definitions.
 *
 * Single-field methods take only $overwrite = [].
 * Group methods take $prefix = 'default_key' and $overwrite = [].
 *
 * @version 3.0.4-beta
 */
final class FlexFields {

	// -------------------------------------------------------------------------
	// Single-field methods
	// -------------------------------------------------------------------------

	/**
	 * Flex align-items select field (single breakpoint).
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function align_items( array $overwrite = [] ): array {
		$device_size = self::_device_size( $overwrite );

		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Vertical Align Items', 'ayecode-connect' ),
			'options'         => [
				''                                         => __( 'Default', 'ayecode-connect' ),
				'align-items' . $device_size . '-start'    => 'align-items-start',
				'align-items' . $device_size . '-end'      => 'align-items-end',
				'align-items' . $device_size . '-center'   => 'align-items-center',
				'align-items' . $device_size . '-baseline' => 'align-items-baseline',
				'align-items' . $device_size . '-stretch'  => 'align-items-stretch',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => ' ( ( [%container%]=="row" ) || ( [%display%]=="d-flex" || [%display_md%]=="d-md-flex" || [%display_lg%]=="d-lg-flex" ) ) ',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_flex_align_items_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Flex justify-content select field (single breakpoint).
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function justify_content( array $overwrite = [] ): array {
		$device_size = self::_device_size( $overwrite );

		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Justify content', 'ayecode-connect' ),
			'options'         => [
				''                                            => __( 'Default', 'ayecode-connect' ),
				'justify-content' . $device_size . '-start'   => 'justify-content-start',
				'justify-content' . $device_size . '-end'     => 'justify-content-end',
				'justify-content' . $device_size . '-center'  => 'justify-content-center',
				'justify-content' . $device_size . '-between' => 'justify-content-between',
				'justify-content' . $device_size . '-stretch' => 'justify-content-around',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '( ( [%container%]=="row" ) || ( [%display%]=="d-flex" || [%display_md%]=="d-md-flex" || [%display_lg%]=="d-lg-flex" ) ) ',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_flex_justify_content_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Flex align-self select field (single breakpoint).
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function align_self( array $overwrite = [] ): array {
		$device_size = self::_device_size( $overwrite );

		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Align Self', 'ayecode-connect' ),
			'options'         => [
				''                                         => __( 'Default', 'ayecode-connect' ),
				'align-items' . $device_size . '-start'    => 'align-items-start',
				'align-items' . $device_size . '-end'      => 'align-items-end',
				'align-items' . $device_size . '-center'   => 'align-items-center',
				'align-items' . $device_size . '-baseline' => 'align-items-baseline',
				'align-items' . $device_size . '-stretch'  => 'align-items-stretch',
			],
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => ' [%container%]=="col" ',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_flex_align_self_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Flex order select field (single breakpoint).
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function order( array $overwrite = [] ): array {
		$device_size = self::_device_size( $overwrite );

		$options = [ '' => __( 'Default', 'ayecode-connect' ) ];
		for ( $i = 0; $i <= 5; $i++ ) {
			$options[ 'order' . $device_size . '-' . $i ] = $i;
		}

		$defaults = [
			'type'            => 'select',
			'title'           => __( 'Flex Order', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => ' [%container%]=="col" ',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_flex_order_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Flex wrap select field (single breakpoint).
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function flex_wrap( array $overwrite = [] ): array {
		$device_size = self::_device_size( $overwrite );

		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Flex wrap', 'ayecode-connect' ),
			'options'  => [
				''                                      => __( 'Default', 'ayecode-connect' ),
				'flex' . $device_size . '-nowrap'       => 'nowrap',
				'flex' . $device_size . '-wrap'         => 'wrap',
				'flex' . $device_size . '-wrap-reverse' => 'wrap-reverse',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_flex_wrap_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Float select field (single breakpoint).
	 *
	 * Pass 'device_type' => 'Tablet' or 'Desktop' in $overwrite for responsive variants.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return array
	 */
	public static function float( array $overwrite = [] ): array {
		$device_size = self::_device_size( $overwrite );

		$defaults = [
			'type'     => 'select',
			'title'    => __( 'Float', 'ayecode-connect' ),
			'options'  => [
				''                                => __( 'Default', 'ayecode-connect' ),
				'float' . $device_size . '-start' => 'left',
				'float' . $device_size . '-end'   => 'right',
				'float' . $device_size . '-none'  => 'none',
			],
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		];

		$input = wp_parse_args( $overwrite, $defaults );

		$clean = $overwrite;
		unset( $clean['device_type'] );
		if ( empty( $clean ) ) {
			$input['block_component'] = 'sd_get_float_input' . $device_size;
		}

		return $input;
	}

	// -------------------------------------------------------------------------
	// Group methods (responsive mobile / tablet / desktop)
	// -------------------------------------------------------------------------

	/**
	 * Return responsive align-items fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'flex_align_items' → 'flex_align_items', '_md', '_lg').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function align_items_group( string $prefix = 'flex_align_items', array $overwrite = [] ): array {
		return self::_responsive_group( [ self::class, 'align_items' ], $prefix, $overwrite );
	}

	/**
	 * Return responsive justify-content fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'flex_justify_content').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function justify_content_group( string $prefix = 'flex_justify_content', array $overwrite = [] ): array {
		return self::_responsive_group( [ self::class, 'justify_content' ], $prefix, $overwrite );
	}

	/**
	 * Return responsive align-self fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'flex_align_self').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function align_self_group( string $prefix = 'flex_align_self', array $overwrite = [] ): array {
		return self::_responsive_group( [ self::class, 'align_self' ], $prefix, $overwrite );
	}

	/**
	 * Return responsive order fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'flex_order').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function order_group( string $prefix = 'flex_order', array $overwrite = [] ): array {
		return self::_responsive_group( [ self::class, 'order' ], $prefix, $overwrite );
	}

	/**
	 * Return responsive flex-wrap fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'flex_wrap').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function flex_wrap_group( string $prefix = 'flex_wrap', array $overwrite = [] ): array {
		return self::_responsive_group( [ self::class, 'flex_wrap' ], $prefix, $overwrite );
	}

	/**
	 * Return responsive float fields keyed by argument name.
	 *
	 * @param string $prefix   Base key (default 'float').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return array<string, array>
	 */
	public static function float_group( string $prefix = 'float', array $overwrite = [] ): array {
		return self::_responsive_group( [ self::class, 'float' ], $prefix, $overwrite );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Derive the CSS device-size suffix ('-md' / '-lg' / '') from device_type in $overwrite.
	 *
	 * @param array $overwrite Field config that may contain 'device_type'.
	 * @return string '' | '-md' | '-lg'
	 */
	private static function _device_size( array $overwrite ): string {
		if ( empty( $overwrite['device_type'] ) ) {
			return '';
		}
		if ( 'Tablet' === $overwrite['device_type'] ) {
			return '-md';
		}
		if ( 'Desktop' === $overwrite['device_type'] ) {
			return '-lg';
		}
		return '';
	}

	/**
	 * Build three responsive fields (mobile / tablet / desktop) using a single-field callable.
	 *
	 * @param callable $factory   A single-field factory method (must accept $overwrite).
	 * @param string   $prefix    Base key — mobile gets no suffix, tablet '_md', desktop '_lg'.
	 * @param array    $overwrite Per-field base config (device_type is injected per breakpoint).
	 * @return array<string, array>
	 */
	private static function _responsive_group( callable $factory, string $prefix, array $overwrite ): array {
		return [
			$prefix          => $factory( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) ),
			$prefix . '_md'  => $factory( array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ) ),
			$prefix . '_lg'  => $factory( array_merge( $overwrite, [ 'device_type' => 'Desktop' ] ) ),
		];
	}
}
