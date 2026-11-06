<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The standard carousel settings and their mapping to aui()->carousel() args.
 *
 * Any block that puts slides on the AyeCode UI carousel adds group() to its fields and
 * hands the saved instance to args(), so every carousel reads and behaves the same.
 *
 * @version 3.0.10-beta
 */
final class CarouselFields {

	/** The default Bootstrap gutter step between slides, and between the items of a stacked slide. */
	private const GAP = 3;

	/** The per device field suffixes and their device_type, in the order Super Duper expects. */
	private const DEVICES = [
		''    => 'Mobile',
		'_md' => 'Tablet',
		'_lg' => 'Desktop',
	];

	/**
	 * The carousel settings, keyed `{prefix}carousel_*`.
	 *
	 * @param string $prefix    Key prefix for blocks with more than one carousel.
	 * @param array  $overwrite Applied to every field: usually `group` and `element_require`.
	 * @return array<string, array>
	 */
	public static function group( string $prefix = '', array $overwrite = [] ): array {
		$k = $prefix . 'carousel_';

		// Checkboxes are tested with the :checked form, which reads the same in the block editor
		// and the shortcode builder.
		$stepping  = '[%' . $k . 'autoplay%]!="scroll" && [%' . $k . 'autoplay%]!="scroll_back"';
		$scrolling = '([%' . $k . 'autoplay%]=="scroll" || [%' . $k . 'autoplay%]=="scroll_back")';
		$peeking   = '[%' . $k . 'peek%:checked]';

		$fields = [];

		$fields[ $k . 'arrows' ] = [
			'type'            => 'select',
			'title'           => __( 'Arrows', 'ayecode-connect' ),
			'desc'            => __( 'Where the previous and next buttons sit.', 'ayecode-connect' ),
			'options'         => [
				''      => __( 'None', 'ayecode-connect' ),
				'edge'  => __( 'On the edges', 'ayecode-connect' ),
				'hover' => __( 'On the edges when hovered', 'ayecode-connect' ),
				'title' => __( 'Beside the title', 'ayecode-connect' ),
			],
			'default'         => 'edge',
			'element_require' => $stepping,
		];

		// The standard margin steps, minus auto: 0 to 12 in from the slide edges, or -1 to -12 to push
		// the arrows out past the edges, over the page.
		$offsets = SpacingFields::margin( 'left' )['options'];
		unset( $offsets['auto'] );
		$offsets[''] = __( 'Default', 'ayecode-connect' );

		$fields[ $k . 'arrow_offset' ] = [
			'type'            => 'select',
			'title'           => __( 'Arrow offset', 'ayecode-connect' ),
			'desc'            => __( 'Margin between the arrows and the slide edges. A negative value pushes them outside the carousel, which needs room around it. Default is 2.', 'ayecode-connect' ),
			'options'         => $offsets,
			'default'         => '',
			'element_require' => '([%' . $k . 'arrows%]=="edge" || [%' . $k . 'arrows%]=="hover") && ' . $stepping,
		];

		$fields[ $k . 'dots' ] = [
			'type'            => 'checkbox',
			'title'           => __( 'Dots', 'ayecode-connect' ),
			'desc'            => __( 'A dot for each page of slides, below the carousel.', 'ayecode-connect' ),
			'value'           => '1',
			'default'         => '',
			'element_require' => $stepping,
		];

		// Forward is towards the next slide, which reads as left in LTR and right in RTL, so the two
		// scroll labels follow the site's direction while the saved values stay direction neutral.
		$fields[ $k . 'autoplay' ] = [
			'type'    => 'select',
			'title'   => __( 'Autoplay', 'ayecode-connect' ),
			'desc'    => __( 'Pauses while hovered and stops once the visitor swipes or clicks. Scrolling has no arrows, dots or steps, it just keeps moving.', 'ayecode-connect' ),
			'options' => [
				''            => __( 'Off', 'ayecode-connect' ),
				'step'        => __( 'Slide every few seconds', 'ayecode-connect' ),
				'scroll'      => is_rtl() ? __( 'Scroll right', 'ayecode-connect' ) : __( 'Scroll left', 'ayecode-connect' ),
				'scroll_back' => is_rtl() ? __( 'Scroll left', 'ayecode-connect' ) : __( 'Scroll right', 'ayecode-connect' ),
			],
			'default' => '',
		];

		$fields[ $k . 'delay' ] = [
			'type'              => 'number',
			'title'             => __( 'Seconds per slide', 'ayecode-connect' ),
			'placeholder'       => '5',
			'default'           => '',
			'custom_attributes' => [
				'min'  => 1,
				'max'  => 60,
				'step' => 1,
			],
			'element_require'   => '[%' . $k . 'autoplay%]=="step"',
		];

		$fields[ $k . 'scroll_speed' ] = [
			'type'              => 'number',
			'title'             => __( 'Scroll speed', 'ayecode-connect' ),
			'desc'              => __( 'Pixels per frame, decimals allowed: 0.2 is a crawl, 2 is a steady drift, 10 is fast.', 'ayecode-connect' ),
			'placeholder'       => '2',
			'default'           => '',
			'custom_attributes' => [
				'min'  => 0.1,
				'max'  => 10,
				'step' => 0.1,
			],
			'element_require'   => $scrolling,
		];

		$fields[ $k . 'loop' ] = [
			'type'            => 'checkbox',
			'title'           => __( 'Loop', 'ayecode-connect' ),
			'desc'            => __( 'Carry on from the first slide after the last one.', 'ayecode-connect' ),
			'value'           => '1',
			'default'         => '1',
			'element_require' => $stepping,
		];

		$fields[ $k . 'scroll' ] = [
			'type'            => 'select',
			'title'           => __( 'Move by', 'ayecode-connect' ),
			'desc'            => __( 'How far the arrows, dots and swipes move.', 'ayecode-connect' ),
			'options'         => [
				''     => __( 'One slide', 'ayecode-connect' ),
				'page' => __( 'A full page', 'ayecode-connect' ),
			],
			'default'         => '',
			'element_require' => $stepping,
		];

		$fields[ $k . 'speed' ] = [
			'type'            => 'select',
			'title'           => __( 'Slide speed', 'ayecode-connect' ),
			'desc'            => __( 'How fast one move plays out.', 'ayecode-connect' ),
			'options'         => self::speeds(),
			'default'         => '',
			'element_require' => $stepping,
		];

		$fields[ $k . 'peek' ] = [
			'type'    => 'checkbox',
			'title'   => __( 'Peek the next slide', 'ayecode-connect' ),
			'desc'    => __( 'Show part of the next slide at the end, so visitors know there is more.', 'ayecode-connect' ),
			'value'   => '1',
			'default' => '',
		];

		$fields[ $k . 'peek_size' ] = [
			'type'              => 'number',
			'title'             => __( 'Peek width (%)', 'ayecode-connect' ),
			'desc'              => __( 'How much of the width the next slide takes.', 'ayecode-connect' ),
			'placeholder'       => '50',
			'default'           => '',
			'custom_attributes' => [
				'min'  => 1,
				'max'  => 50,
				'step' => 1,
			],
			'element_require'   => $peeking,
		];

		$fields[ $k . 'peek_center' ] = [
			'type'            => 'checkbox',
			'title'           => __( 'Peek on both sides', 'ayecode-connect' ),
			'desc'            => __( 'Center the current slide so the previous and next slides show on either side.', 'ayecode-connect' ),
			'value'           => '1',
			'default'         => '',
			'element_require' => $peeking,
		];

		$fields[ $k . 'edge_fade' ] = [
			'type'    => 'checkbox',
			'title'   => __( 'Fade the edges', 'ayecode-connect' ),
			'desc'    => __( 'Soften the slides into the background at the edges instead of cutting them off, on a peek or a continuous scroll.', 'ayecode-connect' ),
			'value'   => '1',
			'default' => '',
		];

		$fields[ $k . 'gap' ] = [
			'type'    => 'select',
			'title'   => __( 'Gap between slides', 'ayecode-connect' ),
			'desc'    => __( 'Also the gap between stacked items. Default is 3.', 'ayecode-connect' ),
			'options' => [
				''  => __( 'Default', 'ayecode-connect' ),
				'0' => '0',
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
			],
			'default' => '',
		];

		$fields[ $k . 'stack' ] = [
			'type'    => 'select',
			'title'   => __( 'Items per column', 'ayecode-connect' ),
			'desc'    => __( 'Stack this many items in each slide, so the carousel shows a wall two or three deep. Hand your slides to stack() first.', 'ayecode-connect' ),
			'options' => [
				''  => '1',
				'2' => '2',
				'3' => '3',
			],
			'default' => '',
		];

		// One setting per device, shown one at a time behind the editor's device switcher. Auto is
		// one on phones, two on tablets and the caller's column count on desktop.
		foreach ( self::DEVICES as $suffix => $device ) {
			$fields[ $k . 'per_view' . $suffix ] = LayoutFields::row_cols(
				[
					'title'           => __( 'Slides per view', 'ayecode-connect' ),
					'desc'            => __( 'How many slides show at once. Auto follows the layout columns: one on phones, up to two on tablets.', 'ayecode-connect' ),
					'device_type'     => $device,
					'element_require' => '',
				]
			);
		}

		foreach ( $fields as $key => $field ) {
			$field['desc_tip'] = true;
			$field['group']    = 'carousel';

			if ( ! isset( $field['advanced'] ) ) {
				$field['advanced'] = false;
			}

			if ( isset( $overwrite['element_require'] ) && ! empty( $field['element_require'] ) ) {
				$field['element_require'] = '(' . $overwrite['element_require'] . ') && ' . $field['element_require'];
				$fields[ $key ]           = array_merge( $field, array_diff_key( $overwrite, [ 'element_require' => '' ] ) );
			} else {
				$fields[ $key ] = array_merge( $field, $overwrite );
			}
		}

		return $fields;
	}

	/**
	 * Map saved settings to aui()->carousel() args.
	 *
	 * Per view comes from the caller, which knows its columns: an int is the desktop count, with
	 * one slide on phones and up to two on tablets unless the per device fields say otherwise.
	 *
	 * @param array     $instance Saved settings.
	 * @param string    $prefix   The prefix given to group().
	 * @param int|array $per_view Slides per view, an int or a breakpoint array.
	 * @return array
	 */
	public static function args( array $instance, string $prefix = '', $per_view = 1 ): array {
		$k = $prefix . 'carousel_';
		$v = static function ( $key, $fallback = '' ) use ( $instance, $k ) {
			return isset( $instance[ $k . $key ] ) && '' !== $instance[ $k . $key ] ? $instance[ $k . $key ] : $fallback;
		};

		$arrows = (string) $v( 'arrows', 'edge' );

		$args = [
			'arrows'   => 'title' === $arrows ? 'external' : ( in_array( $arrows, [ 'edge', 'hover' ], true ) ? $arrows : false ),
			'dots'     => ! empty( $instance[ $k . 'dots' ] ),
			'autoplay' => false,
			'loop'     => ! isset( $instance[ $k . 'loop' ] ) || ! empty( $instance[ $k . 'loop' ] ),
			'scroll'   => 'page' === $v( 'scroll' ) ? 'page' : 1,
			'gap'      => self::gap( $instance, $prefix ),
			'per_view' => self::per_view( $per_view, $v( 'per_view' ), $v( 'per_view_md' ), $v( 'per_view_lg' ) ),
		];

		if ( 'step' === $v( 'autoplay' ) ) {
			$args['autoplay'] = min( 60, max( 1, (int) $v( 'delay', 5 ) ) ) * 1000;
		} elseif ( in_array( $v( 'autoplay' ), [ 'scroll', 'scroll_back' ], true ) ) {
			// A continuous scroll is a marquee: arrows and dots cannot steer it, so they go, and it needs
			// the loop. A negative speed runs it backward.
			$args['auto_scroll'] = min( 10, max( 0.1, round( (float) $v( 'scroll_speed', 2 ), 1 ) ) ) * ( 'scroll_back' === $v( 'autoplay' ) ? -1 : 1 );
			$args['arrows']      = false;
			$args['dots']        = false;
			$args['loop']        = true;
			$args['scroll']      = 1;
		}

		if ( ! empty( $instance[ $k . 'peek' ] ) ) {
			$args['peek'] = min( 50, max( 1, (int) $v( 'peek_size', 50 ) ) );

			if ( ! empty( $instance[ $k . 'peek_center' ] ) ) {
				$args['align'] = 'center';
			}
		}

		if ( ! empty( $instance[ $k . 'edge_fade' ] ) ) {
			$args['edge_fade'] = true;
		}

		if ( '' !== (string) $v( 'arrow_offset' ) ) {
			$args['arrow_offset'] = (string) $v( 'arrow_offset' );
		}

		// Embla's duration is a tween factor, not milliseconds: 25 is its default.
		$durations = [
			'slow' => 40,
			'fast' => 15,
		];
		if ( isset( $durations[ $v( 'speed' ) ] ) ) {
			$args['options'] = [ 'duration' => $durations[ $v( 'speed' ) ] ];
		}

		return $args;
	}

	/**
	 * Group slides into stacked columns when the items per column setting asks for it.
	 *
	 * Each column is a flex column with the same gap as the carousel gutter, so a wall reads evenly
	 * in both directions. Items keep their own markup; a `col` class on them makes the column share
	 * its height between them. A count that does not divide evenly leaves a shorter last column.
	 *
	 * @param array  $slides   The slides as they would go to the carousel.
	 * @param array  $instance Saved settings.
	 * @param string $prefix   The prefix given to group().
	 * @return array The slides, stacked when the setting is above one.
	 */
	public static function stack( array $slides, array $instance, string $prefix = '' ): array {
		$stack = (int) ( $instance[ $prefix . 'carousel_stack' ] ?? 1 );
		if ( $stack < 2 || count( $slides ) < 2 ) {
			return $slides;
		}

		$gap = self::gap( $instance, $prefix );

		return array_map(
			static function ( array $column ) use ( $gap ) {
				return '<div class="col d-flex flex-column gap-' . $gap . '">' . implode( '', $column ) . '</div>';
			},
			array_chunk( $slides, min( 3, $stack ) )
		);
	}

	/**
	 * The gutter step between slides, from the gap setting or the default.
	 *
	 * @param array  $instance Saved settings.
	 * @param string $prefix   The prefix given to group().
	 * @return int 0 to 5.
	 */
	private static function gap( array $instance, string $prefix ): int {
		$gap = $instance[ $prefix . 'carousel_gap' ] ?? '';

		return '' === $gap ? self::GAP : max( 0, min( 5, (int) $gap ) );
	}

	/**
	 * Build the per_view breakpoint array.
	 *
	 * Mobile is the base, tablet is Bootstrap md (768px up) and desktop is lg (992px up), the
	 * same tiers as every other Super Duper device setting.
	 *
	 * @param int|array  $per_view Desktop count, or a breakpoint array to override into.
	 * @param int|string $mobile   Phone override, empty for auto.
	 * @param int|string $tablet   Tablet override, empty for auto.
	 * @param int|string $desktop  Desktop override, empty for auto.
	 * @return int|array
	 */
	private static function per_view( $per_view, $mobile = '', $tablet = '', $desktop = '' ) {
		if ( ! is_array( $per_view ) ) {
			$per_view = max( 1, (int) $per_view );
			$per_view = $per_view > 1 ? [
				'xs' => 1,
				'md' => min( 2, $per_view ),
				'lg' => $per_view,
			] : [ 'xs' => 1 ];
		}

		foreach ( [
			'xs' => $mobile,
			'md' => $tablet,
			'lg' => $desktop,
		] as $breakpoint => $count ) {
			if ( '' !== $count ) {
				$per_view[ $breakpoint ] = max( 1, min( 6, (int) $count ) );
			}
		}

		// Drop tiers that repeat the one below so the class list stays short.
		$last = null;
		foreach ( [ 'xs', 'sm', 'md', 'lg', 'xl', 'xxl' ] as $breakpoint ) {
			if ( ! isset( $per_view[ $breakpoint ] ) ) {
				continue;
			}
			if ( $per_view[ $breakpoint ] === $last ) {
				unset( $per_view[ $breakpoint ] );
				continue;
			}
			$last = $per_view[ $breakpoint ];
		}

		return 1 === count( $per_view ) ? (int) reset( $per_view ) : $per_view;
	}

	/**
	 * Slow, normal, fast.
	 *
	 * @return array
	 */
	private static function speeds(): array {
		return [
			''     => __( 'Normal', 'ayecode-connect' ),
			'slow' => __( 'Slow', 'ayecode-connect' ),
			'fast' => __( 'Fast', 'ayecode-connect' ),
		];
	}
}
