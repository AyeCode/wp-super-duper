<?php

namespace AyeCode\SuperDuper\Builder;

use AyeCode\SuperDuper\Fields\CommonFields;
use AyeCode\SuperDuper\Fields\LayoutFields;
use AyeCode\SuperDuper\Fields\SpacingFields;
use AyeCode\SuperDuper\Fields\StyleFields;
use AyeCode\SuperDuper\Fields\TypographyFields;

/**
 * BlockArguments — Fluent builder for Super Duper widget arguments.
 *
 * Provides a chainable API as the modern alternative to passing an
 * 'arguments' array in the WP_Super_Duper constructor options.
 *
 * Usage:
 *
 *   public function set_arguments(): array {
 *       return ( new BlockArguments() )
 *           ->add_field( 'title', [ 'type' => 'text', 'title' => __( 'Title', 'my-domain' ) ] )
 *           ->add_margins()
 *           ->add_padding()
 *           ->add_border_group()
 *           ->add_shadow()
 *           ->add_visibility_conditions()
 *           ->get();
 *   }
 *
 * @version 3.0.4-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BlockArguments {

	/**
	 * Accumulated field definitions keyed by argument name.
	 *
	 * @var array<string, array>
	 */
	private array $fields = [];

	/** @var string Last group seen via add_field() — inherited by tab marker fields. */
	private string $current_group = '';

	/** @var int Counter for generating unique tab close marker names. */
	private int $tab_counter = 0;

	// -------------------------------------------------------------------------
	// Core API
	// -------------------------------------------------------------------------

	/**
	 * Add an arbitrary field definition.
	 *
	 * @param string $name   The argument name (array key).
	 * @param array  $config The field configuration array.
	 * @return static
	 */
	public function add_field( string $name, array $config ): self {
		$this->fields[ $name ] = $config;
		if ( ! empty( $config['group'] ) ) {
			$this->current_group = $config['group'];
		}
		return $this;
	}

	/**
	 * Merge an array of field definitions.
	 *
	 * Useful when calling existing sd_get_*() helper functions that return
	 * a single field config, or a group that returns multiple.
	 *
	 * @param array<string, array> $fields Key → config pairs.
	 * @return static
	 */
	public function add_fields( array $fields ): self {
		$this->fields = array_merge( $this->fields, $fields );
		return $this;
	}

	/**
	 * Return the compiled arguments array.
	 *
	 * @return array<string, array>
	 */
	public function get(): array {
		return $this->fields;
	}

	// -------------------------------------------------------------------------
	// Field-level tab helpers
	// -------------------------------------------------------------------------

	/**
	 * Open a tab container and its first tab pane within the current group.
	 *
	 * Call this before adding the first field that belongs to the first tab.
	 * The group is inherited automatically from the last add_field() call.
	 *
	 * @param string $key   Unique tab key (used as the tab identifier in JS).
	 * @param string $title Visible tab label.
	 * @param string $class Additional CSS classes for the tab button.
	 * @return static
	 */
	public function open_tabs( string $key, string $title, string $class = '' ): self {
		$marker = '__tab_open_' . $key;
		$this->fields[ $marker ] = [
			'type'  => '_tab_marker',
			'name'  => $marker,
			'group' => $this->current_group,
			'tab'   => [
				'tabs_open' => 1,
				'open'      => 1,
				'key'       => $key,
				'title'     => $title,
				'class'     => $class,
			],
		];
		return $this;
	}

	/**
	 * Open a subsequent tab pane within the already-open tab container.
	 *
	 * @param string $key   Unique tab key.
	 * @param string $title Visible tab label.
	 * @param string $class Additional CSS classes for the tab button.
	 * @return static
	 */
	public function open_tab( string $key, string $title, string $class = '' ): self {
		$marker = '__tab_' . $key;
		$this->fields[ $marker ] = [
			'type'  => '_tab_marker',
			'name'  => $marker,
			'group' => $this->current_group,
			'tab'   => [
				'open'  => 1,
				'key'   => $key,
				'title' => $title,
				'class' => $class,
			],
		];
		return $this;
	}

	/**
	 * Close the current tab pane.
	 *
	 * @return static
	 */
	public function close_tab(): self {
		$marker = '__tab_close_' . ( ++$this->tab_counter );
		$this->fields[ $marker ] = [
			'type'  => '_tab_marker',
			'name'  => $marker,
			'group' => $this->current_group,
			'tab'   => [ 'close' => 1 ],
		];
		return $this;
	}

	/**
	 * Close the current tab pane and the entire tab container.
	 *
	 * @return static
	 */
	public function close_tabs(): self {
		$marker = '__tabs_close_' . ( ++$this->tab_counter );
		$this->fields[ $marker ] = [
			'type'  => '_tab_marker',
			'name'  => $marker,
			'group' => $this->current_group,
			'tab'   => [ 'close' => 1, 'tabs_close' => 1 ],
		];
		return $this;
	}

	// -------------------------------------------------------------------------
	// Design control groups
	// -------------------------------------------------------------------------

	/**
	 * Add margin fields (mt, mr, mb, ml).
	 *
	 * @param array $overwrite Per-field overwrite config merged into every margin input.
	 * @param bool  $include_negatives Whether to include negative margin options.
	 * @return static
	 */
	public function add_margins( array $overwrite = [], bool $include_negatives = true ): self {
		return $this->add_fields( SpacingFields::margin_group( $overwrite, $include_negatives ) );
	}

	/**
	 * Add responsive margin fields (mobile, tablet, desktop breakpoints).
	 *
	 * Adds mt/mr/mb/ml for each breakpoint:
	 *   - base (mobile): mt, mr, mb, ml
	 *   - tablet (_md):  mt_md, mr_md, mb_md, ml_md
	 *   - desktop (_lg): mt_lg, mr_lg, mb_lg, ml_lg
	 *
	 * @param array  $overwrite        Per-field overwrite config.
	 * @param bool   $include_negatives Whether to include negative margin options.
	 * @param string $mb_lg_default    Default value for mb_lg. Pass '' to leave unset.
	 * @return static
	 */
	public function add_responsive_margins( array $overwrite = [], bool $include_negatives = true, string $mb_lg_default = '3' ): self {
		$this->add_margins( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ), $include_negatives );

		$side_map = [ 'mt' => 'top', 'mr' => 'right', 'mb' => 'bottom', 'ml' => 'left' ];

		// Tablet (_md) variants
		foreach ( $side_map as $key => $side ) {
			$this->fields[ $key . '_md' ] = SpacingFields::margin( $side, array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ), $include_negatives );
		}

		// Desktop (_lg) variants
		foreach ( $side_map as $key => $side ) {
			$lg_overwrite = array_merge( $overwrite, [ 'device_type' => 'Desktop' ] );
			if ( 'mb' === $key && '' !== $mb_lg_default ) {
				$lg_overwrite = array_merge( $lg_overwrite, [ 'default' => $mb_lg_default ] );
			}
			$this->fields[ $key . '_lg' ] = SpacingFields::margin( $side, $lg_overwrite, $include_negatives );
		}

		return $this;
	}

	/**
	 * Add padding fields (pt, pr, pb, pl).
	 *
	 * @param array $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_padding( array $overwrite = [] ): self {
		return $this->add_fields( SpacingFields::padding_group( $overwrite ) );
	}

	/**
	 * Add responsive padding fields (mobile, tablet, desktop breakpoints).
	 *
	 * @param array $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_responsive_paddings( array $overwrite = [] ): self {
		$this->add_padding( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) );

		$side_map = [ 'pt' => 'top', 'pr' => 'right', 'pb' => 'bottom', 'pl' => 'left' ];

		// Tablet (_md) variants
		foreach ( $side_map as $key => $side ) {
			$this->fields[ $key . '_md' ] = SpacingFields::padding( $side, array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ) );
		}

		// Desktop (_lg) variants
		foreach ( $side_map as $key => $side ) {
			$this->fields[ $key . '_lg' ] = SpacingFields::padding( $side, array_merge( $overwrite, [ 'device_type' => 'Desktop' ] ) );
		}

		return $this;
	}

	/**
	 * Add border fields (border, border_type, border_width, border_opacity, rounded, rounded_size).
	 *
	 * @param string $prefix   Optional prefix to apply to field keys (e.g. 'card_').
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_border_group( string $prefix = '', array $overwrite = [] ): self {
		return $this->add_fields( StyleFields::border_group( $prefix, $overwrite ) );
	}

	/**
	 * Add shadow field.
	 *
	 * @param string $prefix   Optional prefix for the field key.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_shadow_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'shadow' ] = StyleFields::shadow( $overwrite );
		return $this;
	}

	/**
	 * Add background fields (bg color, gradient, image).
	 *
	 * @param string $prefix          Optional prefix for all field keys (e.g. 'card_' → card_bg, card_bg_color, …).
	 * @param array  $overwrite        Global overwrite applied to every background field.
	 * @param bool   $include_image   Whether to include the image picker fields. Default true.
	 * @param array  $field_overwrites Per-field overwrite map merged on top of $overwrite.
	 *                                 Valid keys: 'color', 'gradient', 'image'.
	 *                                 Pass false as a key value to omit that sub-field.
	 *                                 Example: [ 'color' => [ 'default' => '#ff0000' ] ]
	 * @return static
	 */
	public function add_background_group( string $prefix = '', array $overwrite = [], bool $include_image = true, array $field_overwrites = [] ): self {
		if ( ! $include_image ) {
			$field_overwrites['image'] = false;
		}
		return $this->add_fields( StyleFields::background_group( $prefix . 'bg', $overwrite, $field_overwrites ) );
	}

	/**
	 * Add display field for mobile only (d-none, d-flex, d-grid, etc.).
	 *
	 * Use add_responsive_display_group() to add mobile + tablet + desktop variants.
	 *
	 * @param string $prefix   Optional prefix for the field key.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_display_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'display' ] = StyleFields::display( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) );
		return $this;
	}

	/**
	 * Add responsive display fields (mobile, tablet, desktop breakpoints).
	 *
	 * Adds {prefix}display / {prefix}display_md / {prefix}display_lg.
	 *
	 * @param string $prefix   Optional prefix for all field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_responsive_display_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'display' ]    = StyleFields::display( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) );
		$this->fields[ $prefix . 'display_md' ] = StyleFields::display( array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ) );
		$this->fields[ $prefix . 'display_lg' ] = StyleFields::display( array_merge( $overwrite, [ 'device_type' => 'Desktop' ] ) );
		return $this;
	}

	/**
	 * Add typography fields: font size, font weight, font case, italic, line height, text justify, text alignment, text color.
	 *
	 * @param string $prefix          Optional prefix for all field keys (e.g. 'heading_' → heading_font_size, …).
	 * @param array  $overwrite        Global overwrite applied to every field in the group.
	 * @param array  $field_overwrites Per-field overwrite map merged on top of $overwrite for individual sub-fields.
	 *                                 Valid keys: 'color', 'font_size', 'font_weight', 'font_case', 'font_italic',
	 *                                 'line_height', 'text_justify', 'text_align'.
	 *                                 Example: [ 'font_size' => [ 'default' => 'h2' ], 'color' => [ 'default' => 'primary' ] ]
	 * @return static
	 */
	public function add_typography_group( string $prefix = '', array $overwrite = [], array $field_overwrites = [] ): self {
		$fo = $field_overwrites;

		return $this
			->add_fields( TypographyFields::text_color_group(
				$prefix . 'text_color',
				array_merge( $overwrite, $fo['color'] ?? [] )
			) )
			->add_fields( TypographyFields::font_size_group(
				$prefix . 'font_size',
				array_merge( $overwrite, $fo['font_size'] ?? [] )
			) )
			->add_field( $prefix . 'font_weight',
				TypographyFields::font_weight( array_merge( $overwrite, $fo['font_weight'] ?? [] ) ) )
			->add_field( $prefix . 'font_case',
				TypographyFields::font_case( array_merge( $overwrite, $fo['font_case'] ?? [] ) ) )
			->add_field( $prefix . 'font_italic',
				TypographyFields::font_italic( array_merge( $overwrite, $fo['font_italic'] ?? [] ) ) )
			->add_field( $prefix . 'font_line_height',
				TypographyFields::line_height( array_merge( $overwrite, $fo['line_height'] ?? [] ) ) )
			->add_field( $prefix . 'text_justify',
				TypographyFields::text_justify( array_merge( $overwrite, $fo['text_justify'] ?? [] ) ) )
			->add_fields( TypographyFields::text_align_group(
				$prefix . 'text_align',
				array_merge(
					$overwrite,
					$fo['text_align'] ?? [],
					[ 'element_require' => '[%' . $prefix . 'text_justify%]==""' ] // always last — prevents callers from breaking the text_justify condition
				)
			) );
	}

	/**
	 * Add the position class field (static, relative, absolute, fixed, sticky).
	 *
	 * @param string $prefix   Optional prefix for the field key.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_position( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'position' ] = LayoutFields::position( $overwrite );
		return $this;
	}

	/**
	 * Add layout/container fields (container width and position class).
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_layout_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'container' ] = LayoutFields::container( $overwrite );
		$this->fields[ $prefix . 'position' ]  = LayoutFields::position( $overwrite );
		return $this;
	}

	/**
	 * Add text color group fields.
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_colors_group( string $prefix = '', array $overwrite = [] ): self {
		return $this->add_fields( TypographyFields::text_color_group( $prefix . 'text_color', $overwrite ) );
	}

	/**
	 * Add sticky-offset fields (top and bottom offsets, shown when position is sticky).
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_sticky_offset_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'sticky_offset_top' ]    = LayoutFields::sticky_offset( 'top', $overwrite );
		$this->fields[ $prefix . 'sticky_offset_bottom' ] = LayoutFields::sticky_offset( 'bottom', $overwrite );
		return $this;
	}

	/**
	 * Add a hidden field that stores a value in block attributes with no UI control.
	 *
	 * @param string $name     The argument name (array key).
	 * @param array  $overwrite Field config overrides merged on top of the hidden defaults.
	 * @return static
	 */
	public function add_hidden_field( string $name, array $overwrite = [] ): self {
		$this->fields[ $name ] = CommonFields::hidden( $overwrite );
		return $this;
	}

	/**
	 * Add the style ID hidden field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return static
	 */
	public function add_style_id( array $overwrite = [] ): self {
		$this->fields['styleid'] = CommonFields::style_id( $overwrite );
		return $this;
	}

	/**
	 * Add the icon class text input (with icon picker).
	 *
	 * @param array $overwrite Field config overrides.
	 * @return static
	 */
	public function add_icon_class( array $overwrite = [] ): self {
		$this->fields['icon_class'] = CommonFields::icon_class( $overwrite );
		return $this;
	}

	/**
	 * Add the icon position select field.
	 *
	 * @param array $overwrite Field config overrides.
	 * @return static
	 */
	public function add_icon_position( array $overwrite = [] ): self {
		$this->fields['icon_position'] = CommonFields::icon_position( $overwrite );
		return $this;
	}

	/**
	 * Add icon class and icon position fields together.
	 *
	 * @param array $overwrite Field config overrides applied to both fields.
	 * @return static
	 */
	public function add_icon_group( array $overwrite = [] ): self {
		$this->fields['icon_class']    = CommonFields::icon_class( $overwrite );
		$this->fields['icon_position'] = CommonFields::icon_position( $overwrite );
		return $this;
	}

	/**
	 * Add the block visibility conditions field.
	 *
	 * @param string $prefix   Optional prefix for the field key.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_visibility_conditions( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'visibility_conditions' ] = CommonFields::visibility_conditions( $overwrite );
		return $this;
	}

	/**
	 * Add the Advanced group: CSS class and custom metadata name.
	 *
	 * Use this as the standard "Advanced" tab fields for any block that
	 * needs to expose a CSS class override and a custom metadata label
	 * without the anchor ID field.
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_advanced_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'css_class' ]     = CommonFields::css_class( $overwrite );
		$this->fields[ $prefix . 'metadata_name' ] = CommonFields::metadata_name( $overwrite );
		return $this;
	}

	/**
	 * Add CSS class, anchor ID and custom name fields (common utility fields).
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_class_and_anchor( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'css_class' ]     = CommonFields::css_class( $overwrite );
		$this->fields[ $prefix . 'anchor' ]        = CommonFields::anchor( $overwrite );
		$this->fields[ $prefix . 'metadata_name' ] = CommonFields::metadata_name( $overwrite );
		return $this;
	}
}
