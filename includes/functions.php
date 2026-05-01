<?php
/**
 * A file for common functions.
 */

/**
 * Register a block/shortcode/widget class for lazy loading via the SD Registry.
 *
 * On the frontend, the class file and class itself are not loaded until a
 * shortcode referencing this base_id is actually rendered on the page. On
 * admin and AJAX requests all registered classes are instantiated eagerly so
 * the block editor and AJAX handlers work as expected.
 *
 * @param string   $base_id      The shortcode / block base ID (e.g. 'bs_alert').
 * @param string   $class_name   The class name (e.g. 'BlockStrap_Widget_Alert').
 * @param string[] $output_types Supported output types: 'block', 'shortcode', 'widget'.
 *                               Omit 'widget' for blocks that never appear in sidebar widget areas.
 * @param string   $file_path    Absolute path to the class file. Required when the class is
 *                               not PSR-4 autoloadable (i.e. most non-Composer plugins).
 *                               Use __DIR__ . '/path/to/class-file.php'.
 */
function ayecode_sd_register( string $base_id, string $class_name, array $output_types = [], string $file_path = '' ): void {
	\AyeCode\SuperDuper\Registry::register( $base_id, $class_name, $output_types, $file_path );
}

/**
 * Return an array of global $pagenow page names that should be used to exclude register_widgets.
 *
 * Used to block the loading of widgets on certain wp-admin pages to save on memory.
 *
 * @return mixed|void
 */
function sd_pagenow_exclude() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::pagenow_exclude' );
	return \AyeCode\SuperDuper\Utils::pagenow_exclude();
}


/**
 * Return an array of widget class names that should be excluded.
 *
 * Used to conditionally load widgets code.
 *
 * @return mixed|void
 */
function sd_widget_exclude() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::widget_exclude' );
	return \AyeCode\SuperDuper\Utils::widget_exclude();
}


/**
 * A helper function for margin inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_margin_input( $type = 'mt', $overwrite = array(), $include_negatives = true ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\SpacingFields::margin' );
	$side_map = [ 'mt' => 'top', 'mr' => 'right', 'mb' => 'bottom', 'ml' => 'left' ];
	return \AyeCode\SuperDuper\Fields\SpacingFields::margin( $side_map[ $type ] ?? 'top', $overwrite, $include_negatives );
}

/**
 * A helper function for padding inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_padding_input( $type = 'pt', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\SpacingFields::padding' );
	$side_map = [ 'pt' => 'top', 'pr' => 'right', 'pb' => 'bottom', 'pl' => 'left' ];
	return \AyeCode\SuperDuper\Fields\SpacingFields::padding( $side_map[ $type ] ?? 'top', $overwrite );
}

/**
 * A helper function for border inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_border_input( $type = 'border', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::border_show' );
	switch ( $type ) {
		case 'type':
			return \AyeCode\SuperDuper\Fields\StyleFields::border_style( $overwrite );
		case 'width':
			return \AyeCode\SuperDuper\Fields\StyleFields::border_width( $overwrite );
		case 'opacity':
			return \AyeCode\SuperDuper\Fields\StyleFields::border_opacity( $overwrite );
		case 'rounded':
			return \AyeCode\SuperDuper\Fields\StyleFields::border_radius( $overwrite );
		case 'rounded_size':
			return \AyeCode\SuperDuper\Fields\StyleFields::border_radius_size( $overwrite );
		default:
			return \AyeCode\SuperDuper\Fields\StyleFields::border_show( $overwrite );
	}
}

/**
 * A helper function for shadow inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_shadow_input( $type = 'shadow', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::shadow' );
	return \AyeCode\SuperDuper\Fields\StyleFields::shadow( $overwrite );
}

/**
 * A helper function for background inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_background_input( $type = 'bg', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::background' );
	return \AyeCode\SuperDuper\Fields\StyleFields::background( $overwrite );
}

/**
 * A function to get th opacity options.
 *
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_opacity_input( $type = 'opacity', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::opacity' );
	return \AyeCode\SuperDuper\Fields\StyleFields::opacity( $overwrite );
}

/**
 * A helper function for a set of background inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_background_inputs( $type = 'bg', $overwrite = array(), $overwrite_color = array(), $overwrite_gradient = array(), $overwrite_image = array(), $include_button_colors = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::background_group' );
	return \AyeCode\SuperDuper\Fields\StyleFields::background_group( $type, $overwrite, $overwrite_color, $overwrite_gradient, $overwrite_image, $include_button_colors );
}

/**
 * A helper function for a set of inputs for the shape divider.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_shape_divider_inputs( $type = 'sd', $overwrite = array(), $overwrite_color = array(), $overwrite_gradient = array(), $overwrite_image = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\ShapeFields::divider_group' );
	return \AyeCode\SuperDuper\Fields\ShapeFields::divider_group( $type, $overwrite, $overwrite_color, [], $overwrite_image );
}

/**
 * Get the element require sting.
 *
 * @param $args
 * @param $key
 * @param $type
 *
 * @return string
 */
function sd_get_element_require_string( $args, $key, $type ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::element_require' );
	return \AyeCode\SuperDuper\Utils::element_require( $args, $key, $type );
}

/**
 * A helper function for text color inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_text_color_input( $type = 'text_color', $overwrite = array(), $has_custom = false, $emphasis = true ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_color' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_color( $overwrite );
}

function sd_get_text_color_input_group( $type = 'text_color', $overwrite = array(), $overwrite_custom = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_color_group' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_color_group( $type, $overwrite );
}

/**
 * A helper function for custom color.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_custom_color_input( $type = 'color_custom', $overwrite = array(), $parent_type = '' ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_color_custom' );
	if ( $parent_type && empty( $overwrite['element_require'] ) ) {
		$overwrite['element_require'] = '[%' . $parent_type . '%]=="custom"';
	}
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_color_custom( $overwrite );
}

/**
 * A helper function for column inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_col_input( $type = 'col', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::col' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::col( $overwrite );
}

/**
 * A helper function for row columns inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_row_cols_input( $type = 'row_cols', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::row_cols' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::row_cols( $overwrite );
}

/**
 * A helper function for text align inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_text_align_input( $type = 'text_align', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_align' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_align( $overwrite );
}

/**
 * A helper function for display inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_display_input( $type = 'display', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::display' );
	return \AyeCode\SuperDuper\Fields\StyleFields::display( $overwrite );
}

/**
 * A helper function for text justify inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_text_justify_input( $type = 'text_justify', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_justify' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_justify( $overwrite );
}

/**
 * Modern approach: Accepts an array of types to include.
 * Returns an array grouped by category for use in Select2 or WP Block controls.
 *
 * Usage: sd_get_aui_colors( ['core', 'subtle', 'outline'] );
 *
 * @param array $types   Array of types to include: 'core', 'subtle', 'emphasis', 'outline'.
 * @param bool  $flatten If true, returns a flat array (key => label) instead of optgroups.
 *
 * @return array
 */
function sd_get_aui_colors( $types = array(), $flatten = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\ColorFields::get_aui_colors' );
	return \AyeCode\SuperDuper\Fields\ColorFields::get_aui_colors( $types, $flatten );
}

/**
 * LEGACY WRAPPER
 * Keeps the old function signature alive but routes logic to ColorFields::aui_colors().
 * Always returns a flat array to maintain backward compatibility.
 * @deprecated 3.1.0 Use ColorFields::get_aui_colors() instead.
 */
function sd_aui_colors( $include_branding = false, $include_outlines = false, $outline_button_only_text = false, $include_translucent = false, $include_subtle = false, $include_emphasis = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\ColorFields::aui_colors' );
	return \AyeCode\SuperDuper\Fields\ColorFields::aui_colors( $include_branding, $include_outlines, $outline_button_only_text, $include_translucent, $include_subtle, $include_emphasis );
}

/**
 * Get the AUI branding colors.
 *
 * @return array
 */
function sd_aui_branding_colors() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\ColorFields::branding_colors' );
	return \AyeCode\SuperDuper\Fields\ColorFields::branding_colors();
}

/**
 * A helper function for container class.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_container_class_input( $type = 'container', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::container' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::container( $overwrite );
}

/**
 * A helper function for position class.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_position_class_input( $type = 'position', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::position' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::position( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_absolute_position_input( $type = 'absolute_position', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::absolute_position' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::absolute_position( $overwrite );
}

/**
 * A helper function for sticky offset input.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_sticky_offset_input( $type = 'top', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::sticky_offset' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::sticky_offset( $type, $overwrite );
}

/**
 * A helper function for font size
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_font_size_input( $type = 'font_size', $overwrite = array(), $has_custom = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_size' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_size( $overwrite );
}

/**
 * A helper function for custom font size.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_font_custom_size_input( $type = 'font_size_custom', $overwrite = array(), $parent_type = '' ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_size_custom' );
	if ( $parent_type && empty( $overwrite['element_require'] ) ) {
		$overwrite['element_require'] = '[%' . $parent_type . '%]=="custom"';
	}
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_size_custom( $overwrite );
}

/**
 * A helper function for custom font size.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_font_line_height_input( $type = 'font_line_height', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::line_height' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::line_height( $overwrite );
}

/**
 * A helper function for font size inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_font_size_input_group( $type = 'font_size', $overwrite = array(), $overwrite_custom = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_size_group' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_size_group( $type, $overwrite );
}

/**
 * A helper function for font weight.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_font_weight_input( $type = 'font_weight', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_weight' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_weight( $overwrite );
}

/**
 * A helper function for font case class.
 *
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_font_case_input( $type = 'font_weight', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_case' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_case( $overwrite );
}

/**
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 * @todo remove this as now included above.
 * A helper function for font size
 *
 */
function sd_get_font_italic_input( $type = 'font_italic', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_italic' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_italic( $overwrite );
}

/**
 * A helper function for the anchor input.
 *
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_anchor_input( $type = 'anchor', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::anchor' );
	return \AyeCode\SuperDuper\Fields\CommonFields::anchor( $overwrite );
}

/**
 * A helper function for the class input.
 *
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_class_input( $type = 'css_class', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::css_class' );
	return \AyeCode\SuperDuper\Fields\CommonFields::css_class( $overwrite );
}

/**
 * A helper function for the class input.
 *
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_custom_name_input( $type = 'metadata_name', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::metadata_name' );
	return \AyeCode\SuperDuper\Fields\CommonFields::metadata_name( $overwrite );
}

/**
 * A helper function for font size inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_hover_animations_input( $type = 'hover_animations', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::hover_animation' );
	return \AyeCode\SuperDuper\Fields\StyleFields::hover_animation( $overwrite );
}

/**
 * Get hover icon animations input settings.
 *
 * @param string $type The input type parameter name. Default 'hover_icon_animation'.
 * @param array $overwrite Optional array to override default settings. Default empty array.
 *
 * @return array Array of input settings.
 */
function sd_get_hover_icon_animation_input( $type = 'hover_icon_animation', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::hover_icon_animation' );
	return \AyeCode\SuperDuper\Fields\StyleFields::hover_icon_animation( $overwrite );
}


function sd_get_flex_align_items_input( $type = 'align-items', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_items' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_items( $overwrite );
}

function sd_get_flex_align_items_input_group( $type = 'flex_align_items', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_items_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_items_group( $type, $overwrite );
}

function sd_get_flex_justify_content_input( $type = 'flex_justify_content', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::justify_content' );
	return \AyeCode\SuperDuper\Fields\FlexFields::justify_content( $overwrite );
}

function sd_get_flex_justify_content_input_group( $type = 'flex_justify_content', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::justify_content_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::justify_content_group( $type, $overwrite );
}

function sd_get_flex_align_self_input( $type = 'flex_align_self', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_self' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_self( $overwrite );
}

function sd_get_flex_align_self_input_group( $type = 'flex_align_self', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_self_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_self_group( $type, $overwrite );
}

function sd_get_flex_order_input( $type = 'flex_order', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::order' );
	return \AyeCode\SuperDuper\Fields\FlexFields::order( $overwrite );
}

function sd_get_flex_order_input_group( $type = 'flex_order', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::order_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::order_group( $type, $overwrite );
}

function sd_get_flex_wrap_group( $type = 'flex_wrap', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::flex_wrap_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::flex_wrap_group( $type, $overwrite );
}

function sd_get_flex_wrap_input( $type = 'flex_wrap', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::flex_wrap' );
	return \AyeCode\SuperDuper\Fields\FlexFields::flex_wrap( $overwrite );
}

function sd_get_float_group( $type = 'float', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::float_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::float_group( $type, $overwrite );
}

function sd_get_float_input( $type = 'float', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::float' );
	return \AyeCode\SuperDuper\Fields\FlexFields::float( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_zindex_input( $type = 'zindex', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::zindex' );
	return \AyeCode\SuperDuper\Fields\StyleFields::zindex( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_overflow_input( $type = 'overflow', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::overflow' );
	return \AyeCode\SuperDuper\Fields\StyleFields::overflow( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_max_height_input( $type = 'max_height', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::max_height' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::max_height( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_scrollbars_input( $type = 'scrollbars', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::scrollbars' );
	return \AyeCode\SuperDuper\Fields\StyleFields::scrollbars( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_new_window_input( $type = 'target', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::new_window' );
	return \AyeCode\SuperDuper\Fields\CommonFields::new_window( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_nofollow_input( $type = 'nofollow', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::nofollow' );
	return \AyeCode\SuperDuper\Fields\CommonFields::nofollow( $overwrite );
}

/**
 * A helper function for width inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_width_input( $type = 'width', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::width' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::width( $overwrite );
}

/**
 * A helper function for height inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_height_input( $type = 'height', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::height' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::height( $overwrite );
}

/**
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_attributes_input( $type = 'attributes', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::attributes' );
	return \AyeCode\SuperDuper\Fields\CommonFields::attributes( $overwrite );
}

/**
 * A helper function for title tag inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_title_tag_input( $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::title_tag' );
	return \AyeCode\SuperDuper\Fields\CommonFields::title_tag( $overwrite );
}

/**
 * A helper function for title tag inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function sd_get_html_tag_input( $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::html_tag' );
	return \AyeCode\SuperDuper\Fields\CommonFields::html_tag( $overwrite );
}

/**
 * Get title input arguments for widgets/blocks.
 *
 * Provides a reusable set of title configuration inputs including tag selection,
 * styling options (size, alignment, color), borders, margins, and padding.
 *
 * @since 3.0.0
 *
 * @return array Array of title input arguments.
 */
function sd_get_title_inputs(): array {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::title_group' );
	return \AyeCode\SuperDuper\Fields\CommonFields::title_group();
}

/**
 * @param $args
 *
 * @return string
 */
function sd_build_attributes_string_escaped( $args ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_attributes_string_escaped' );
	return \AyeCode\SuperDuper\Utils::build_attributes_string_escaped( $args );
}

/**
 * @info borrowed from elementor
 *
 * @param $attributes_string
 * @param $delimiter
 *
 * @return array
 */
function sd_parse_custom_attributes( $attributes_string, $delimiter = ',' ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::parse_custom_attributes' );
	return \AyeCode\SuperDuper\Utils::parse_custom_attributes( $attributes_string, $delimiter );
}

/**
 * Build AUI classes from settings.
 *
 * @param $args
 *
 * @return string
 * @todo find best way to use px- py- or general p-
 */
function sd_build_aui_class( $args ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_aui_class' );
	return \AyeCode\SuperDuper\Utils::build_aui_class( $args );
}

/**
 * Build Style output from arguments.
 *
 * @param $args
 *
 * @return string
 */
function sd_build_aui_styles( $args ) {
	//_deprecated_function( 'sd_build_aui_styles', '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_aui_styles' );
	return \AyeCode\SuperDuper\Utils::build_aui_styles( $args );
}

/**
 * Build the hover styles from args.
 *
 * @param $args
 * @param $is_preview
 *
 * @return string
 */
function sd_build_hover_styles( $args, $is_preview = false ) {
	//_deprecated_function( 'sd_build_hover_styles', '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_hover_styles' );
	return \AyeCode\SuperDuper\Utils::build_hover_styles( $args, $is_preview );
}

/**
 * Try to get a CSS color variable for a given value.
 *
 * @param $var
 *
 * @return mixed|string
 */
function sd_get_color_from_var( $var ) {
	//_deprecated_function( 'sd_get_color_from_var', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_color_from_var' );
	return \AyeCode\SuperDuper\Utils::get_color_from_var( $var );
}

/**
 * Sanitize single or multiple HTML classes.
 *
 * @param $classes
 * @param $sep
 *
 * @return string
 */
function sd_sanitize_html_classes( $classes, $sep = ' ' ) {
	//_deprecated_function( 'sd_sanitize_html_classes', '3.1.0', 'AyeCode\\SuperDuper\\Utils::sanitize_html_classes' );
	return \AyeCode\SuperDuper\Utils::sanitize_html_classes( $classes, $sep );
}


/**
 * Keys that are used for the class builder.
 *
 * @return void
 */
function sd_get_class_build_keys() {
	//_deprecated_function( 'sd_get_class_build_keys', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_class_build_keys' );
	return \AyeCode\SuperDuper\Utils::get_class_build_keys();
}

/**
 * This is a placeholder function for the visibility conditions input.
 *
 * @param $type
 * @param $overwrite
 *
 * @return array
 */
function sd_get_visibility_conditions_input( $type = 'visibility_conditions', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::visibility_conditions' );
	return \AyeCode\SuperDuper\Fields\CommonFields::visibility_conditions( $overwrite );
}

/**
 * Get a array of user roles.
 *
 *
 *
 * @param array $exclude An array of roles to exclude from the return array.
 * @return array An array of roles.
 */
function sd_user_roles_options( $exclude = array() ) {
	//_deprecated_function( 'sd_user_roles_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::user_roles_options' );
	return \AyeCode\SuperDuper\Utils::user_roles_options( $exclude );
}

/**
 * Get visibility conditions rule options.
 *
 *
 *
 * @return array Rule options.
 */
function sd_visibility_rules_options() {
	//_deprecated_function( 'sd_visibility_rules_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_rules_options' );
	return \AyeCode\SuperDuper\Utils::visibility_rules_options();
}

/**
 * Get visibility GD field options.
 *
 * @return array
 */
function sd_visibility_gd_field_options() {
	//_deprecated_function( 'sd_visibility_gd_field_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_gd_field_options' );
	return \AyeCode\SuperDuper\Utils::visibility_gd_field_options();
}

/**
 * Get visibility GD post standard field options.
 *
 * @return array
 */
function sd_visibility_gd_standard_field_options( $post_type = '' ) {
	//_deprecated_function( 'sd_visibility_gd_standard_field_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_gd_standard_field_options' );
	return \AyeCode\SuperDuper\Utils::visibility_gd_standard_field_options( $post_type );
}

/**
 * Get visibility GD post standard fields.
 *
 * @return array
 */
function sd_visibility_gd_standard_fields( $post_type = '' ) {
	//_deprecated_function( 'sd_visibility_gd_standard_fields', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_gd_standard_fields' );
	return \AyeCode\SuperDuper\Utils::visibility_gd_standard_fields( $post_type );
}

/**
 * Get visibility field conditions options.
 *
 * @return array
 */
function sd_visibility_field_condition_options() {
	//_deprecated_function( 'sd_visibility_field_condition_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_field_condition_options' );
	return \AyeCode\SuperDuper\Utils::visibility_field_condition_options();
}

/**
 * Get visibility conditions output options.
 *
 *
 *
 * @return array Template type options.
 */
function sd_visibility_output_options() {
	//_deprecated_function( 'sd_visibility_output_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_output_options' );
	return \AyeCode\SuperDuper\Utils::visibility_output_options();
}

/**
 * Get the template page options.
 *
 *
 *
 * @param array $args Array of arguments.
 * @return array Template page options.
 */
function sd_template_page_options( $args = array() ) {
	//_deprecated_function( 'sd_template_page_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::template_page_options' );
	return \AyeCode\SuperDuper\Utils::template_page_options( $args );
}

/**
 * Get the template part options.
 *
 *
 *
 * @param array $args Array of arguments.
 * @return array Template part options.
 */
function sd_template_part_options( $args = array() ) {
	//_deprecated_function( 'sd_template_part_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::template_part_options' );
	return \AyeCode\SuperDuper\Utils::template_part_options( $args );
}

/**
 * Get the template part by slug.
 *
 *
 *
 * @param string $slug Template slug.
 * @return array Template part object.
 */
function sd_get_template_part_by_slug( $slug ) {
	//_deprecated_function( 'sd_get_template_part_by_slug', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_template_part_by_slug' );
	return \AyeCode\SuperDuper\Utils::get_template_part_by_slug( $slug );
}

/**
 * Filters the content of a single block.
 *
 *
 *
 * @param string   $block_content The block content.
 * @param array    $block         The full block, including name and attributes.
 * @param WP_Block $instance      The block instance.
 */
function sd_render_block( $block_content, $block, $instance = '' ) {
	//_deprecated_function( 'sd_render_block', '3.1.0', 'AyeCode\\SuperDuper\\Utils::render_block' );
	return \AyeCode\SuperDuper\Utils::render_block( $block_content, $block, $instance );
}
add_filter( 'render_block', 'sd_render_block', 9, 3 );

function sd_get_page_content( $page_id ) {
	//_deprecated_function( 'sd_get_page_content', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_page_content' );
	return \AyeCode\SuperDuper\Utils::get_page_content( $page_id );
}

function sd_get_template_part_content( $template_part ) {
	//_deprecated_function( 'sd_get_template_part_content', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_template_part_content' );
	return \AyeCode\SuperDuper\Utils::get_template_part_content( $template_part );
}

function sd_block_parse_rules( $attrs ) {
	//_deprecated_function( 'sd_block_parse_rules', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_parse_rules' );
	return \AyeCode\SuperDuper\Utils::block_parse_rules( $attrs );
}

function sd_block_check_rules( $rules ) {
	//_deprecated_function( 'sd_block_check_rules', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_check_rules' );
	return \AyeCode\SuperDuper\Utils::block_check_rules( $rules );
}

function sd_block_check_rule( $match, $rule ) {
	//_deprecated_function( 'sd_block_check_rule', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_check_rule' );
	return \AyeCode\SuperDuper\Utils::block_check_rule( $match, $rule );
}
add_filter( 'sd_block_check_rule', 'sd_block_check_rule', 10, 2 );

function sd_block_check_rule_gd_field( $rule ) {
	//_deprecated_function( 'sd_block_check_rule_gd_field', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_check_rule_gd_field' );
	return \AyeCode\SuperDuper\Utils::block_check_rule_gd_field( $rule );
}

function sd_gd_field_rule_search( $search, $post_type, $rule, $field = array(), $gd_post = array() ) {
	//_deprecated_function( 'sd_gd_field_rule_search', '3.1.0', 'AyeCode\\SuperDuper\\Utils::gd_field_rule_search' );
	return \AyeCode\SuperDuper\Utils::gd_field_rule_search( $search, $post_type, $rule, $field, $gd_post );
}


if(!function_exists('sd_blocks_render_blocks')){
	/**
	 * Add the shortcodes to the block content if set as an attribute.
	 *
	 * We have moved the shortcodes from the block content to a block argument to help prevent broken blocks.
	 *
	 * @param $block_content
	 * @param $parsed_block
	 * @param $thiss
	 * @return mixed|string
	 */
	function sd_blocks_render_blocks($block_content, $parsed_block, $thiss = array() ){

//		if(!empty($parsed_block['blockName'])){
//			print_r( $parsed_block );exit;		}

		// Check hide block visibility conditions.
		if ( ! empty( $parsed_block ) && ! empty( $parsed_block['attrs']['visibility_conditions'] ) && $block_content && strpos( strrev( $block_content ), strrev( ' sd-block-has-rule sd-block-hide-rule"></div>' ) ) === 0 && ! empty( $thiss ) && $thiss->name ) {
			$match_content = '<div class="' . esc_attr( wp_get_block_default_classname( $thiss->name ) ) . ' sd-block-has-rule sd-block-hide-rule"></div>';

			// Return empty content to hide block.
			if ( $block_content == $match_content ) {
				return '';
			}
		}

		// Check if ita a nested block that needs to be wrapped
		if(! empty($parsed_block['attrs']['sd_shortcode_close'])){
			$content = isset($parsed_block['attrs']['html']) ? $parsed_block['attrs']['html'] : $block_content;

			$block_content = sd_build_shortcode($parsed_block['attrs']['sd_shortcode'], $parsed_block['attrs'],$content);


			$block_content = do_shortcode($block_content);

		}elseif(! empty($parsed_block['attrs']['sd_shortcode'])){

			$shortcode = sd_build_shortcode($parsed_block['attrs']['sd_shortcode'], $parsed_block['attrs']);

//			if ( $shortcode === 'bs_alert' ) {
//				print_r( ( $parsed_block ) );exit;
//			}

			// maybe replace dynamic data for non-dynamic blocks  @todo check if this can be abused
			if(!empty($block_content)){
				$block_content = sd_replace_variables( $block_content );
			}

			$has_warp = false;
			if($block_content && strpos(trim($block_content), '<div class="wp-block-') === 0 ){
				$parts = explode('></', $block_content);
				if(count($parts) === 2){
					$block_content = $parts[0].'>'.$shortcode.'</'.$parts[1];
					$has_warp = true;
				}
			}
			if (!$has_warp) {
				// Add the shortcode if its not a wrapped block
				$block_content .= $shortcode;
			}

			$block_content = do_shortcode($block_content);
		}

		return  $block_content;
	}
}

add_filter('render_block', 'sd_blocks_render_blocks',10,3);

/**
 * Retrieves the shortcode slug from a given string.
 *
 * @param string $str The input string from which to extract the shortcode slug.
 *
 * @return string The extracted shortcode slug.
 */
function sd_get_shortcode_slug( $str ) {
	//_deprecated_function( 'sd_get_shortcode_slug', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_shortcode_slug' );
	return \AyeCode\SuperDuper\Utils::get_shortcode_slug( $str );
}

/**
 * Builds a shortcode string based on provided name, attributes, and content.
 *
 * @param string $name Name of the shortcode. Required.
 * @param array $args Optional. Array of attributes for the shortcode. Default empty array.
 * @param string $content Optional. Content to be enclosed within the shortcode. Default empty string.
 *
 * @return string Shortcode string if successful, otherwise an empty string.
 */
function sd_build_shortcode( $name, $args = array(), $content = '' ) {
	//_deprecated_function( 'sd_build_shortcode', '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_shortcode' );
	return \AyeCode\SuperDuper\Utils::build_shortcode( $name, $args, $content );
}


/**
 * Finds and replaces a comprehensive set of dynamic data variables.
 *
 * This function is optimized for performance and is extensible via WordPress filters.
 * It supports advanced syntax for HTML link generation (e.g., {post_title:link:newTab}),
 * fallbacks, and formatting.
 *
 * @param string|null $text The input string containing variables.
 * @return string The text with variables replaced, which may include HTML.
 */
function sd_replace_variables( $text ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::replace_variables' );
	return \AyeCode\SuperDuper\Utils::replace_variables( (string) $text );
}
