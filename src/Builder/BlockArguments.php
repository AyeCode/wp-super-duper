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
		$this->fields['mt'] = SpacingFields::margin_input( 'mt', $overwrite, $include_negatives );
		$this->fields['mr'] = SpacingFields::margin_input( 'mr', $overwrite, $include_negatives );
		$this->fields['mb'] = SpacingFields::margin_input( 'mb', $overwrite, $include_negatives );
		$this->fields['ml'] = SpacingFields::margin_input( 'ml', $overwrite, $include_negatives );
		return $this;
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

		// Tablet (_md) variants
		foreach ( [ 'mt', 'mr', 'mb', 'ml' ] as $side ) {
			$md_type                  = $side . '_md';
			$this->fields[ $md_type ] = SpacingFields::margin_input( $side, array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ), $include_negatives );
		}

		// Desktop (_lg) variants
		foreach ( [ 'mt', 'mr', 'mb', 'ml' ] as $side ) {
			$lg_type      = $side . '_lg';
			$lg_overwrite = array_merge( $overwrite, [ 'device_type' => 'Desktop' ] );
			if ( 'mb' === $side && '' !== $mb_lg_default ) {
				$lg_overwrite = array_merge( $lg_overwrite, [ 'default' => $mb_lg_default ] );
			}
			$this->fields[ $lg_type ] = SpacingFields::margin_input( $side, $lg_overwrite, $include_negatives );
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
		$this->fields['pt'] = SpacingFields::padding_input( 'pt', $overwrite );
		$this->fields['pr'] = SpacingFields::padding_input( 'pr', $overwrite );
		$this->fields['pb'] = SpacingFields::padding_input( 'pb', $overwrite );
		$this->fields['pl'] = SpacingFields::padding_input( 'pl', $overwrite );
		return $this;
	}

	/**
	 * Add responsive padding fields (mobile, tablet, desktop breakpoints).
	 *
	 * @param array $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_responsive_paddings( array $overwrite = [] ): self {
		$this->add_padding( array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) );

		// Tablet (_md) variants
		foreach ( [ 'pt', 'pr', 'pb', 'pl' ] as $side ) {
			$md_type                  = $side . '_md';
			$this->fields[ $md_type ] = SpacingFields::padding_input( $side, array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ) );
		}

		// Desktop (_lg) variants
		foreach ( [ 'pt', 'pr', 'pb', 'pl' ] as $side ) {
			$lg_type                  = $side . '_lg';
			$this->fields[ $lg_type ] = SpacingFields::padding_input( $side, array_merge( $overwrite, [ 'device_type' => 'Desktop' ] ) );
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
		$this->fields[ $prefix . 'border' ]         = StyleFields::border_input( 'border', $overwrite );
		$this->fields[ $prefix . 'border_type' ]    = StyleFields::border_input( 'type', $overwrite );
		$this->fields[ $prefix . 'border_width' ]   = StyleFields::border_input( 'width', $overwrite );
		$this->fields[ $prefix . 'border_opacity' ] = StyleFields::border_input( 'opacity', $overwrite );
		$this->fields[ $prefix . 'rounded' ]        = StyleFields::border_input( 'rounded', $overwrite );
		$this->fields[ $prefix . 'rounded_size' ]   = StyleFields::border_input( 'rounded_size', $overwrite );
		return $this;
	}

	/**
	 * Add shadow field.
	 *
	 * @param string $prefix   Optional prefix for the field key.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_shadow_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'shadow' ] = StyleFields::shadow_input( 'shadow', $overwrite );
		return $this;
	}

	/**
	 * Add background fields (bg color, gradient, image).
	 *
	 * @param string     $prefix         Optional prefix for all field keys (e.g. 'card_' → card_bg, card_bg_color, …).
	 * @param array      $overwrite      Per-field overwrite config.
	 * @param bool       $include_image  Whether to include the image picker fields. Default true.
	 * @return static
	 */
	public function add_background_group( string $prefix = '', array $overwrite = [], bool $include_image = true ): self {
		return $this->add_fields( StyleFields::background_inputs( $prefix . 'bg', $overwrite, [], [], $include_image ? [] : false ) );
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
		$this->fields[ $prefix . 'display' ] = StyleFields::display_input( 'display', array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) );
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
		$this->fields[ $prefix . 'display' ]    = StyleFields::display_input( 'display', array_merge( $overwrite, [ 'device_type' => 'Mobile' ] ) );
		$this->fields[ $prefix . 'display_md' ] = StyleFields::display_input( 'display', array_merge( $overwrite, [ 'device_type' => 'Tablet' ] ) );
		$this->fields[ $prefix . 'display_lg' ] = StyleFields::display_input( 'display', array_merge( $overwrite, [ 'device_type' => 'Desktop' ] ) );
		return $this;
	}

	/**
	 * Add typography fields: font size, font weight, font case, italic, line height, text justify, text alignment, text color.
	 *
	 * @param string $prefix   Optional prefix for all field keys (e.g. 'heading_' → heading_font_size, …).
	 * @param array  $overwrite Per-group overwrite config (applied to all typography fields).
	 * @return static
	 */
	public function add_typography_group( string $prefix = '', array $overwrite = [] ): self {
		return $this->add_fields( TypographyFields::text_color_input_group( $prefix . 'text_color', $overwrite ) )
		            ->add_fields( TypographyFields::font_size_input_group( $prefix . 'font_size', $overwrite ) )
					->add_field( $prefix . 'font_weight', TypographyFields::font_weight_input( $prefix . 'font_weight', $overwrite ) )
					->add_field( $prefix . 'font_case', TypographyFields::font_case_input( $prefix . 'font_case', $overwrite ) )
					->add_field( $prefix . 'font_italic', TypographyFields::font_italic_input( $prefix . 'font_italic', $overwrite ) )
					->add_field( $prefix . 'font_line_height', TypographyFields::font_line_height_input( $prefix . 'font_line_height', $overwrite ) )
					->add_field( $prefix . 'text_justify', TypographyFields::text_justify_input( $prefix . 'text_justify', $overwrite ) )
					->add_fields( TypographyFields::text_align_input_group( $prefix . 'text_align', array_merge( $overwrite, [ 'element_require' => '[%' . $prefix . 'text_justify%]==""' ] ) ) );
	}

	/**
	 * Add layout/container fields (container width and position class).
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_layout_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'container' ] = LayoutFields::container_class_input( 'container', $overwrite );
		$this->fields[ $prefix . 'position' ]  = LayoutFields::position_class_input( 'position', $overwrite );
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
		return $this->add_fields( TypographyFields::text_color_input_group( $prefix . 'text_color', $overwrite ) );
	}

	/**
	 * Add sticky-offset fields (top and bottom offsets, shown when position is sticky).
	 *
	 * @param string $prefix   Optional prefix for field keys.
	 * @param array  $overwrite Per-field overwrite config.
	 * @return static
	 */
	public function add_sticky_offset_group( string $prefix = '', array $overwrite = [] ): self {
		$this->fields[ $prefix . 'sticky_offset_top' ]    = LayoutFields::sticky_offset_input( 'top', $overwrite );
		$this->fields[ $prefix . 'sticky_offset_bottom' ] = LayoutFields::sticky_offset_input( 'bottom', $overwrite );
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
		$this->fields[ $prefix . 'visibility_conditions' ] = CommonFields::visibility_conditions_input( 'visibility_conditions', $overwrite );
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
		$this->fields[ $prefix . 'css_class' ]     = CommonFields::class_input( 'css_class', $overwrite );
		$this->fields[ $prefix . 'anchor' ]        = CommonFields::anchor_input( 'anchor', $overwrite );
		$this->fields[ $prefix . 'metadata_name' ] = CommonFields::custom_name_input( 'metadata_name', $overwrite );
		return $this;
	}
}
