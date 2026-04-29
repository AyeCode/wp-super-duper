<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for flex / float layout field definitions.
 *
 * @version 3.0.4-beta
 */
final class FlexFields {

	/**
	 * Build a flex align-items field definition (single breakpoint).
	 *
	 * @param string $type      The base field key (default 'align-items').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function align_items_input( $type = 'align-items', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                                         => __( 'Default', 'ayecode-connect' ),
			'align-items' . $device_size . '-start'    => 'align-items-start',
			'align-items' . $device_size . '-end'      => 'align-items-end',
			'align-items' . $device_size . '-center'   => 'align-items-center',
			'align-items' . $device_size . '-baseline' => 'align-items-baseline',
			'align-items' . $device_size . '-stretch'  => 'align-items-stretch',
		);

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Vertical Align Items', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => ' ( ( [%container%]=="row" ) || ( [%display%]=="d-flex" || [%display_md%]=="d-md-flex" || [%display_lg%]=="d-lg-flex" ) ) ',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_flex_align_items_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive group (mobile/tablet/desktop) of flex align-items fields.
	 *
	 * @param string $type      The base field key (default 'flex_align_items').
	 * @param array  $overwrite Field config overrides passed to each breakpoint.
	 * @return array<string, array>
	 */
	public static function align_items_group( $type = 'flex_align_items', $overwrite = array() ) {
		$inputs = array();
		$sizes  = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		if ( $overwrite !== false ) {
			foreach ( $sizes as $ds => $dt ) {
				$overwrite['device_type'] = $dt;
				$inputs[ $type . $ds ]    = self::align_items_input( $type, $overwrite );
			}
		}

		return $inputs;
	}

	/**
	 * Build a flex justify-content field definition (single breakpoint).
	 *
	 * @param string $type      The base field key (default 'flex_justify_content').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function justify_content_input( $type = 'flex_justify_content', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                                            => __( 'Default', 'ayecode-connect' ),
			'justify-content' . $device_size . '-start'   => 'justify-content-start',
			'justify-content' . $device_size . '-end'     => 'justify-content-end',
			'justify-content' . $device_size . '-center'  => 'justify-content-center',
			'justify-content' . $device_size . '-between' => 'justify-content-between',
			'justify-content' . $device_size . '-stretch' => 'justify-content-around',
		);

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Justify content', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => '( ( [%container%]=="row" ) || ( [%display%]=="d-flex" || [%display_md%]=="d-md-flex" || [%display_lg%]=="d-lg-flex" ) ) ',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_flex_justify_content_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive group of flex justify-content fields.
	 *
	 * @param string $type      The base field key (default 'flex_justify_content').
	 * @param array  $overwrite Field config overrides passed to each breakpoint.
	 * @return array<string, array>
	 */
	public static function justify_content_group( $type = 'flex_justify_content', $overwrite = array() ) {
		$inputs = array();
		$sizes  = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		if ( $overwrite !== false ) {
			foreach ( $sizes as $ds => $dt ) {
				$overwrite['device_type'] = $dt;
				$inputs[ $type . $ds ]    = self::justify_content_input( $type, $overwrite );
			}
		}

		return $inputs;
	}

	/**
	 * Build a flex align-self field definition (single breakpoint).
	 *
	 * @param string $type      The base field key (default 'flex_align_self').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function align_self_input( $type = 'flex_align_self', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                                         => __( 'Default', 'ayecode-connect' ),
			'align-items' . $device_size . '-start'    => 'align-items-start',
			'align-items' . $device_size . '-end'      => 'align-items-end',
			'align-items' . $device_size . '-center'   => 'align-items-center',
			'align-items' . $device_size . '-baseline' => 'align-items-baseline',
			'align-items' . $device_size . '-stretch'  => 'align-items-stretch',
		);

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Align Self', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => ' [%container%]=="col" ',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_flex_align_self_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive group of flex align-self fields.
	 *
	 * @param string $type      The base field key (default 'flex_align_self').
	 * @param array  $overwrite Field config overrides passed to each breakpoint.
	 * @return array<string, array>
	 */
	public static function align_self_group( $type = 'flex_align_self', $overwrite = array() ) {
		$inputs = array();
		$sizes  = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		if ( $overwrite !== false ) {
			foreach ( $sizes as $ds => $dt ) {
				$overwrite['device_type'] = $dt;
				$inputs[ $type . $ds ]    = self::align_self_input( $type, $overwrite );
			}
		}

		return $inputs;
	}

	/**
	 * Build a flex order field definition (single breakpoint).
	 *
	 * @param string $type      The base field key (default 'flex_order').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function order_input( $type = 'flex_order', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}

		$options = array(
			'' => __( 'Default', 'ayecode-connect' ),
		);

		$i = 0;
		while ( $i <= 5 ) {
			$options[ 'order' . $device_size . '-' . $i ] = $i;
			$i++;
		}

		$defaults = array(
			'type'            => 'select',
			'title'           => __( 'Flex Order', 'ayecode-connect' ),
			'options'         => $options,
			'default'         => '',
			'desc_tip'        => true,
			'group'           => 'wrapper-styles',
			'element_require' => ' [%container%]=="col" ',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_flex_order_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive group of flex order fields.
	 *
	 * @param string $type      The base field key (default 'flex_order').
	 * @param array  $overwrite Field config overrides passed to each breakpoint.
	 * @return array<string, array>
	 */
	public static function order_group( $type = 'flex_order', $overwrite = array() ) {
		$inputs = array();
		$sizes  = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		if ( $overwrite !== false ) {
			foreach ( $sizes as $ds => $dt ) {
				$overwrite['device_type'] = $dt;
				$inputs[ $type . $ds ]    = self::order_input( $type, $overwrite );
			}
		}

		return $inputs;
	}

	/**
	 * Build a flex-wrap field definition (single breakpoint).
	 *
	 * @param string $type      The base field key (default 'flex_wrap').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function wrap_input( $type = 'flex_wrap', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                                      => __( 'Default', 'ayecode-connect' ),
			'flex' . $device_size . '-nowrap'       => 'nowrap',
			'flex' . $device_size . '-wrap'         => 'wrap',
			'flex' . $device_size . '-wrap-reverse' => 'wrap-reverse',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Flex wrap', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_flex_wrap_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive group of flex-wrap fields.
	 *
	 * @param string $type      The base field key (default 'flex_wrap').
	 * @param array  $overwrite Field config overrides passed to each breakpoint.
	 * @return array<string, array>
	 */
	public static function wrap_group( $type = 'flex_wrap', $overwrite = array() ) {
		$inputs = array();
		$sizes  = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		if ( $overwrite !== false ) {
			foreach ( $sizes as $ds => $dt ) {
				$overwrite['device_type'] = $dt;
				$inputs[ $type . $ds ]    = self::wrap_input( $type, $overwrite );
			}
		}

		return $inputs;
	}

	/**
	 * Build a float field definition (single breakpoint).
	 *
	 * @param string $type      The base field key (default 'float').
	 * @param array  $overwrite Field config overrides (use 'device_type' for responsive variants).
	 * @return array
	 */
	public static function float_input( $type = 'float', $overwrite = array() ) {
		$device_size = '';
		if ( ! empty( $overwrite['device_type'] ) ) {
			if ( $overwrite['device_type'] == 'Tablet' ) {
				$device_size = '-md';
			} elseif ( $overwrite['device_type'] == 'Desktop' ) {
				$device_size = '-lg';
			}
		}
		$options = array(
			''                                => __( 'Default', 'ayecode-connect' ),
			'float' . $device_size . '-start' => 'left',
			'float' . $device_size . '-end'   => 'right',
			'float' . $device_size . '-none'  => 'none',
		);

		$defaults = array(
			'type'     => 'select',
			'title'    => __( 'Float', 'ayecode-connect' ),
			'options'  => $options,
			'default'  => '',
			'desc_tip' => true,
			'group'    => 'wrapper-styles',
		);

		$input = wp_parse_args( $overwrite, $defaults );

		// set as block_component
		unset( $overwrite['device_type'] );
		if ( empty( $overwrite ) ) {
			$input['block_component'] = 'sd_get_float_input' . $device_size;
		}

		return $input;
	}

	/**
	 * Build a responsive group of float fields.
	 *
	 * @param string $type      The base field key (default 'float').
	 * @param array  $overwrite Field config overrides passed to each breakpoint.
	 * @return array<string, array>
	 */
	public static function float_group( $type = 'float', $overwrite = array() ) {
		$inputs = array();
		$sizes  = array(
			''    => 'Mobile',
			'_md' => 'Tablet',
			'_lg' => 'Desktop',
		);

		if ( $overwrite !== false ) {
			foreach ( $sizes as $ds => $dt ) {
				$overwrite['device_type'] = $dt;
				$inputs[ $type . $ds ]    = self::float_input( $type, $overwrite );
			}
		}

		return $inputs;
	}
}
