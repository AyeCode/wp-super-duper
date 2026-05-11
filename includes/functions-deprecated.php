<?php
/**
 * Deprecated global helper functions.
 *
 * These functions are soft-deprecated since 3.1.0. They exist solely for
 * backward compatibility with consuming plugins that have not yet migrated to
 * the Fields\* static classes, Utils::* methods, or the BlockArguments builder.
 *
 * Do not add new functions here. Use the PSR-4 classes in src/ instead.
 */

/**
 * Return an array of global $pagenow page names that should be used to exclude register_widgets.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::pagenow_exclude()
 * @return mixed|void
 */
function sd_pagenow_exclude() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::pagenow_exclude' );
	return \AyeCode\SuperDuper\Utils::pagenow_exclude();
}

/**
 * Return an array of widget class names that should be excluded.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::widget_exclude()
 * @return mixed|void
 */
function sd_widget_exclude() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::widget_exclude' );
	return \AyeCode\SuperDuper\Utils::widget_exclude();
}

/**
 * A helper function for margin inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\SpacingFields::margin()
 * @param string $type
 * @param array $overwrite
 * @param bool $include_negatives
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\SpacingFields::padding()
 * @param string $type
 * @param array $overwrite
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::border_show()
 * @param string $type
 * @param array $overwrite
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::shadow()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_shadow_input( $type = 'shadow', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::shadow' );
	return \AyeCode\SuperDuper\Fields\StyleFields::shadow( $overwrite );
}

/**
 * A helper function for background inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::background()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_background_input( $type = 'bg', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::background' );
	return \AyeCode\SuperDuper\Fields\StyleFields::background( $overwrite );
}

/**
 * A function to get the opacity options.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::opacity()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_opacity_input( $type = 'opacity', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::opacity' );
	return \AyeCode\SuperDuper\Fields\StyleFields::opacity( $overwrite );
}

/**
 * A helper function for a set of background inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::background_group()
 * @param string $type
 * @param array $overwrite
 * @param array $overwrite_color
 * @param array $overwrite_gradient
 * @param array $overwrite_image
 * @param bool  $include_button_colors
 * @return array
 */
function sd_get_background_inputs( $type = 'bg', $overwrite = array(), $overwrite_color = array(), $overwrite_gradient = array(), $overwrite_image = array(), $include_button_colors = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::background_group' );
	$field_overwrites = [];
	if ( false === $overwrite_color ) {
		$field_overwrites['color'] = false;
	} elseif ( ! empty( $overwrite_color ) ) {
		$field_overwrites['color'] = $overwrite_color;
	}
	if ( false === $overwrite_gradient ) {
		$field_overwrites['gradient'] = false;
	} elseif ( ! empty( $overwrite_gradient ) ) {
		$field_overwrites['gradient'] = $overwrite_gradient;
	}
	if ( false === $overwrite_image ) {
		$field_overwrites['image'] = false;
	} elseif ( ! empty( $overwrite_image ) ) {
		$field_overwrites['image'] = $overwrite_image;
	}
	return \AyeCode\SuperDuper\Fields\StyleFields::background_group( $type, $overwrite, $field_overwrites, $include_button_colors );
}

/**
 * A helper function for a set of inputs for the shape divider.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\ShapeFields::divider_group()
 * @param string $type
 * @param array $overwrite
 * @param array $overwrite_color
 * @param array $overwrite_gradient
 * @param array $overwrite_image
 * @return array
 */
function sd_get_shape_divider_inputs( $type = 'sd', $overwrite = array(), $overwrite_color = array(), $overwrite_gradient = array(), $overwrite_image = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\ShapeFields::divider_group' );
	return \AyeCode\SuperDuper\Fields\ShapeFields::divider_group( $type, $overwrite, $overwrite_color, [], $overwrite_image );
}

/**
 * Get the element require string.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::element_require()
 * @param $args
 * @param $key
 * @param $type
 * @return string
 */
function sd_get_element_require_string( $args, $key, $type ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::element_require' );
	return \AyeCode\SuperDuper\Utils::element_require( $args, $key, $type );
}

/**
 * A helper function for text color inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::text_color()
 * @param string $type
 * @param array $overwrite
 * @param bool $has_custom
 * @param bool $emphasis
 * @return array
 */
function sd_get_text_color_input( $type = 'text_color', $overwrite = array(), $has_custom = false, $emphasis = true ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_color' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_color( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::text_color_group()
 */
function sd_get_text_color_input_group( $type = 'text_color', $overwrite = array(), $overwrite_custom = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_color_group' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_color_group( $type, $overwrite, $overwrite_custom );
}

/**
 * A helper function for custom color.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::text_color_custom()
 * @param string $type
 * @param array $overwrite
 * @param string $parent_type
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::col()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_col_input( $type = 'col', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::col' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::col( $overwrite );
}

/**
 * A helper function for row columns inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::row_cols()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_row_cols_input( $type = 'row_cols', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::row_cols' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::row_cols( $overwrite );
}

/**
 * A helper function for text align inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::text_align()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_text_align_input( $type = 'text_align', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::text_align' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::text_align( $overwrite );
}

/**
 * A helper function for display inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::display()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_display_input( $type = 'display', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::display' );
	return \AyeCode\SuperDuper\Fields\StyleFields::display( $overwrite );
}

/**
 * A helper function for text justify inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::text_justify()
 * @param string $type
 * @param array $overwrite
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
 * @deprecated 3.1.0 Use ayecode_get_sd_colors()
 * @param array $types   Array of types to include: 'core', 'subtle', 'emphasis', 'outline', 'branding'.
 * @param bool  $flatten If true, returns a flat array (key => label) instead of optgroups.
 * @return array
 */
function sd_get_aui_colors( $types = array(), $flatten = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'ayecode_get_sd_colors' );
	return \AyeCode\SuperDuper\Helpers\ColorOptions::aui( $types, $flatten );
}

/**
 * LEGACY WRAPPER
 * Keeps the old function signature alive but routes logic to ColorOptions::aui().
 * Always returns a flat array to maintain backward compatibility.
 *
 * @deprecated 3.1.0 Use ayecode_get_sd_colors()
 * @param bool $include_branding
 * @param bool $include_outlines
 * @param bool $outline_button_only_text
 * @param bool $include_translucent
 * @param bool $include_subtle
 * @param bool $include_emphasis
 * @return array
 */
function sd_aui_colors( $include_branding = false, $include_outlines = false, $outline_button_only_text = false, $include_translucent = false, $include_subtle = false, $include_emphasis = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'ayecode_get_sd_colors' );
	$types = [ 'core' ];
	if ( $include_outlines ) {
		$types[] = 'outline';
	}
	if ( $outline_button_only_text ) {
		$types[] = 'outline_btn_text';
	}
	if ( $include_subtle || $include_translucent ) {
		$types[] = 'subtle';
	}
	if ( $include_emphasis ) {
		$types[] = 'emphasis';
	}
	if ( $include_branding ) {
		$types[] = 'branding';
	}
	return \AyeCode\SuperDuper\Helpers\ColorOptions::aui( $types, true );
}

/**
 * Get the AUI branding colors.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Helpers\ColorOptions::branding()
 * @return array
 */
function sd_aui_branding_colors() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Helpers\\ColorOptions::branding' );
	return \AyeCode\SuperDuper\Helpers\ColorOptions::branding();
}

/**
 * A helper function for container class.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::container()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_container_class_input( $type = 'container', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::container' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::container( $overwrite );
}

/**
 * A helper function for position class.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::position()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_position_class_input( $type = 'position', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::position' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::position( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::absolute_position()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_absolute_position_input( $type = 'absolute_position', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::absolute_position' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::absolute_position( $overwrite );
}

/**
 * A helper function for sticky offset input.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::sticky_offset()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_sticky_offset_input( $type = 'top', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::sticky_offset' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::sticky_offset( $type, $overwrite );
}

/**
 * A helper function for font size.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::font_size()
 * @param string $type
 * @param array $overwrite
 * @param bool $has_custom
 * @return array
 */
function sd_get_font_size_input( $type = 'font_size', $overwrite = array(), $has_custom = false ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_size' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_size( $overwrite );
}

/**
 * A helper function for custom font size.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::font_size_custom()
 * @param string $type
 * @param array $overwrite
 * @param string $parent_type
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
 * A helper function for font line height.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::line_height()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_font_line_height_input( $type = 'font_line_height', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::line_height' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::line_height( $overwrite );
}

/**
 * A helper function for font size inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::font_size_group()
 * @param string $type
 * @param array $overwrite
 * @param array $overwrite_custom
 * @return array
 */
function sd_get_font_size_input_group( $type = 'font_size', $overwrite = array(), $overwrite_custom = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_size_group' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_size_group( $type, $overwrite, $overwrite_custom );
}

/**
 * A helper function for font weight.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::font_weight()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_font_weight_input( $type = 'font_weight', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_weight' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_weight( $overwrite );
}

/**
 * A helper function for font case class.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::font_case()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_font_case_input( $type = 'font_weight', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_case' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_case( $overwrite );
}

/**
 * A helper function for font italic.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\TypographyFields::font_italic()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_font_italic_input( $type = 'font_italic', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\TypographyFields::font_italic' );
	return \AyeCode\SuperDuper\Fields\TypographyFields::font_italic( $overwrite );
}

/**
 * A helper function for the anchor input.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::anchor()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_anchor_input( $type = 'anchor', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::anchor' );
	return \AyeCode\SuperDuper\Fields\CommonFields::anchor( $overwrite );
}

/**
 * A helper function for the class input.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::css_class()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_class_input( $type = 'css_class', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::css_class' );
	return \AyeCode\SuperDuper\Fields\CommonFields::css_class( $overwrite );
}

/**
 * A helper function for the metadata name input.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::metadata_name()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_custom_name_input( $type = 'metadata_name', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::metadata_name' );
	return \AyeCode\SuperDuper\Fields\CommonFields::metadata_name( $overwrite );
}

/**
 * A helper function for hover animation inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::hover_animation()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_hover_animations_input( $type = 'hover_animations', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::hover_animation' );
	return \AyeCode\SuperDuper\Fields\StyleFields::hover_animation( $overwrite );
}

/**
 * Get hover icon animations input settings.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::hover_icon_animation()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_hover_icon_animation_input( $type = 'hover_icon_animation', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::hover_icon_animation' );
	return \AyeCode\SuperDuper\Fields\StyleFields::hover_icon_animation( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::align_items()
 */
function sd_get_flex_align_items_input( $type = 'align-items', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_items' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_items( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::align_items_group()
 */
function sd_get_flex_align_items_input_group( $type = 'flex_align_items', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_items_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_items_group( $type, $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::justify_content()
 */
function sd_get_flex_justify_content_input( $type = 'flex_justify_content', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::justify_content' );
	return \AyeCode\SuperDuper\Fields\FlexFields::justify_content( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::justify_content_group()
 */
function sd_get_flex_justify_content_input_group( $type = 'flex_justify_content', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::justify_content_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::justify_content_group( $type, $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::align_self()
 */
function sd_get_flex_align_self_input( $type = 'flex_align_self', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_self' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_self( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::align_self_group()
 */
function sd_get_flex_align_self_input_group( $type = 'flex_align_self', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::align_self_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::align_self_group( $type, $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::order()
 */
function sd_get_flex_order_input( $type = 'flex_order', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::order' );
	return \AyeCode\SuperDuper\Fields\FlexFields::order( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::order_group()
 */
function sd_get_flex_order_input_group( $type = 'flex_order', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::order_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::order_group( $type, $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::flex_wrap_group()
 */
function sd_get_flex_wrap_group( $type = 'flex_wrap', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::flex_wrap_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::flex_wrap_group( $type, $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::flex_wrap()
 */
function sd_get_flex_wrap_input( $type = 'flex_wrap', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::flex_wrap' );
	return \AyeCode\SuperDuper\Fields\FlexFields::flex_wrap( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::float_group()
 */
function sd_get_float_group( $type = 'float', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::float_group' );
	return \AyeCode\SuperDuper\Fields\FlexFields::float_group( $type, $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\FlexFields::float()
 */
function sd_get_float_input( $type = 'float', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\FlexFields::float' );
	return \AyeCode\SuperDuper\Fields\FlexFields::float( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::zindex()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_zindex_input( $type = 'zindex', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::zindex' );
	return \AyeCode\SuperDuper\Fields\StyleFields::zindex( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::overflow()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_overflow_input( $type = 'overflow', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::overflow' );
	return \AyeCode\SuperDuper\Fields\StyleFields::overflow( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::max_height()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_max_height_input( $type = 'max_height', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::max_height' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::max_height( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\StyleFields::scrollbars()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_scrollbars_input( $type = 'scrollbars', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\StyleFields::scrollbars' );
	return \AyeCode\SuperDuper\Fields\StyleFields::scrollbars( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::new_window()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_new_window_input( $type = 'target', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::new_window' );
	return \AyeCode\SuperDuper\Fields\CommonFields::new_window( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::nofollow()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_nofollow_input( $type = 'nofollow', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::nofollow' );
	return \AyeCode\SuperDuper\Fields\CommonFields::nofollow( $overwrite );
}

/**
 * A helper function for width inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::width()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_width_input( $type = 'width', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::width' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::width( $overwrite );
}

/**
 * A helper function for height inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\LayoutFields::height()
 * @param string $type
 * @param array $overwrite
 * @return array
 */
function sd_get_height_input( $type = 'height', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\LayoutFields::height' );
	return \AyeCode\SuperDuper\Fields\LayoutFields::height( $overwrite );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::attributes()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_attributes_input( $type = 'attributes', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::attributes' );
	return \AyeCode\SuperDuper\Fields\CommonFields::attributes( $overwrite );
}

/**
 * A helper function for title tag inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::title_tag()
 * @param array $overwrite
 * @return array
 */
function sd_get_title_tag_input( $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::title_tag' );
	return \AyeCode\SuperDuper\Fields\CommonFields::title_tag( $overwrite );
}

/**
 * A helper function for HTML tag inputs.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::html_tag()
 * @param array $overwrite
 * @return array
 */
function sd_get_html_tag_input( $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::html_tag' );
	return \AyeCode\SuperDuper\Fields\CommonFields::html_tag( $overwrite );
}

/**
 * Get title input arguments for widgets/blocks.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::title_group()
 * @return array
 */
function sd_get_title_inputs(): array {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::title_group' );
	return \AyeCode\SuperDuper\Fields\CommonFields::title_group();
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::build_attributes_string_escaped()
 * @param $args
 * @return string
 */
function sd_build_attributes_string_escaped( $args ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_attributes_string_escaped' );
	return \AyeCode\SuperDuper\Utils::build_attributes_string_escaped( $args );
}

/**
 * @info borrowed from elementor
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::parse_custom_attributes()
 * @param $attributes_string
 * @param $delimiter
 * @return array
 */
function sd_parse_custom_attributes( $attributes_string, $delimiter = ',' ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::parse_custom_attributes' );
	return \AyeCode\SuperDuper\Utils::parse_custom_attributes( $attributes_string, $delimiter );
}

/**
 * Keys that are used for the class builder.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::get_class_build_keys()
 */
function sd_get_class_build_keys() {
	//_deprecated_function( 'sd_get_class_build_keys', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_class_build_keys' );
	return \AyeCode\SuperDuper\Utils::get_class_build_keys();
}

/**
 * This is a placeholder function for the visibility conditions input.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Fields\CommonFields::visibility_conditions()
 * @param $type
 * @param $overwrite
 * @return array
 */
function sd_get_visibility_conditions_input( $type = 'visibility_conditions', $overwrite = array() ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Fields\\CommonFields::visibility_conditions' );
	return \AyeCode\SuperDuper\Fields\CommonFields::visibility_conditions( $overwrite );
}

/**
 * Get a array of user roles.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::user_roles_options()
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::visibility_rules_options()
 * @return array Rule options.
 */
function sd_visibility_rules_options() {
	//_deprecated_function( 'sd_visibility_rules_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_rules_options' );
	return \AyeCode\SuperDuper\Utils::visibility_rules_options();
}

/**
 * Get visibility GD field options.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::visibility_gd_field_options()
 * @return array
 */
function sd_visibility_gd_field_options() {
	//_deprecated_function( 'sd_visibility_gd_field_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_gd_field_options' );
	return \AyeCode\SuperDuper\Utils::visibility_gd_field_options();
}

/**
 * Get visibility GD post standard field options.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::visibility_gd_standard_field_options()
 * @return array
 */
function sd_visibility_gd_standard_field_options( $post_type = '' ) {
	//_deprecated_function( 'sd_visibility_gd_standard_field_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_gd_standard_field_options' );
	return \AyeCode\SuperDuper\Utils::visibility_gd_standard_field_options( $post_type );
}

/**
 * Get visibility GD post standard fields.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::visibility_gd_standard_fields()
 * @return array
 */
function sd_visibility_gd_standard_fields( $post_type = '' ) {
	//_deprecated_function( 'sd_visibility_gd_standard_fields', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_gd_standard_fields' );
	return \AyeCode\SuperDuper\Utils::visibility_gd_standard_fields( $post_type );
}

/**
 * Get visibility field conditions options.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::visibility_field_condition_options()
 * @return array
 */
function sd_visibility_field_condition_options() {
	//_deprecated_function( 'sd_visibility_field_condition_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_field_condition_options' );
	return \AyeCode\SuperDuper\Utils::visibility_field_condition_options();
}

/**
 * Get visibility conditions output options.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::visibility_output_options()
 * @return array Template type options.
 */
function sd_visibility_output_options() {
	//_deprecated_function( 'sd_visibility_output_options', '3.1.0', 'AyeCode\\SuperDuper\\Utils::visibility_output_options' );
	return \AyeCode\SuperDuper\Utils::visibility_output_options();
}

/**
 * Get the template page options.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::template_page_options()
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::template_part_options()
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::get_template_part_by_slug()
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
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::render_block()
 * @param string   $block_content The block content.
 * @param array    $block         The full block, including name and attributes.
 * @param WP_Block $instance      The block instance.
 */
function sd_render_block( $block_content, $block, $instance = '' ) {
	//_deprecated_function( 'sd_render_block', '3.1.0', 'AyeCode\\SuperDuper\\Utils::render_block' );
	return \AyeCode\SuperDuper\Utils::render_block( $block_content, $block, $instance );
}
add_filter( 'render_block', 'sd_render_block', 9, 3 );

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::get_page_content()
 */
function sd_get_page_content( $page_id ) {
	//_deprecated_function( 'sd_get_page_content', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_page_content' );
	return \AyeCode\SuperDuper\Utils::get_page_content( $page_id );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::get_template_part_content()
 */
function sd_get_template_part_content( $template_part ) {
	//_deprecated_function( 'sd_get_template_part_content', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_template_part_content' );
	return \AyeCode\SuperDuper\Utils::get_template_part_content( $template_part );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::block_parse_rules()
 */
function sd_block_parse_rules( $attrs ) {
	//_deprecated_function( 'sd_block_parse_rules', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_parse_rules' );
	return \AyeCode\SuperDuper\Utils::block_parse_rules( $attrs );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::block_check_rules()
 */
function sd_block_check_rules( $rules ) {
	//_deprecated_function( 'sd_block_check_rules', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_check_rules' );
	return \AyeCode\SuperDuper\Utils::block_check_rules( $rules );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::block_check_rule()
 */
function sd_block_check_rule( $match, $rule ) {
	//_deprecated_function( 'sd_block_check_rule', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_check_rule' );
	return \AyeCode\SuperDuper\Utils::block_check_rule( $match, $rule );
}
add_filter( 'sd_block_check_rule', 'sd_block_check_rule', 10, 2 );

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::block_check_rule_gd_field()
 */
function sd_block_check_rule_gd_field( $rule ) {
	//_deprecated_function( 'sd_block_check_rule_gd_field', '3.1.0', 'AyeCode\\SuperDuper\\Utils::block_check_rule_gd_field' );
	return \AyeCode\SuperDuper\Utils::block_check_rule_gd_field( $rule );
}

/**
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::gd_field_rule_search()
 */
function sd_gd_field_rule_search( $search, $post_type, $rule, $field = array(), $gd_post = array() ) {
	//_deprecated_function( 'sd_gd_field_rule_search', '3.1.0', 'AyeCode\\SuperDuper\\Utils::gd_field_rule_search' );
	return \AyeCode\SuperDuper\Utils::gd_field_rule_search( $search, $post_type, $rule, $field, $gd_post );
}

if ( ! function_exists( 'sd_blocks_render_blocks' ) ) {
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
	function sd_blocks_render_blocks( $block_content, $parsed_block, $thiss = array() ) {

		// Check hide block visibility conditions.
		if ( ! empty( $parsed_block ) && ! empty( $parsed_block['attrs']['visibility_conditions'] ) && $block_content && strpos( strrev( $block_content ), strrev( ' sd-block-has-rule sd-block-hide-rule"></div>' ) ) === 0 && ! empty( $thiss ) && $thiss->name ) {
			$match_content = '<div class="' . esc_attr( wp_get_block_default_classname( $thiss->name ) ) . ' sd-block-has-rule sd-block-hide-rule"></div>';

			// Return empty content to hide block.
			if ( $block_content == $match_content ) {
				return '';
			}
		}

		// Check if it's a nested block that needs to be wrapped.
		if ( ! empty( $parsed_block['attrs']['sd_shortcode_close'] ) ) {
			$content = isset( $parsed_block['attrs']['html'] ) ? $parsed_block['attrs']['html'] : $block_content;

			$block_content = sd_build_shortcode( $parsed_block['attrs']['sd_shortcode'], $parsed_block['attrs'], $content );

			$block_content = do_shortcode( $block_content );

		} elseif ( ! empty( $parsed_block['attrs']['sd_shortcode'] ) ) {

			$shortcode = sd_build_shortcode( $parsed_block['attrs']['sd_shortcode'], $parsed_block['attrs'] );

			// Maybe replace dynamic data for non-dynamic blocks.
			if ( ! empty( $block_content ) ) {
				$block_content = sd_replace_variables( $block_content );
			}

			$has_warp = false;
			if ( $block_content && strpos( trim( $block_content ), '<div class="wp-block-' ) === 0 ) {
				$parts = explode( '></', $block_content );
				if ( count( $parts ) === 2 ) {
					$block_content = $parts[0] . '>' . $shortcode . '</' . $parts[1];
					$has_warp      = true;
				}
			}
			if ( ! $has_warp ) {
				// Add the shortcode if it's not a wrapped block.
				$block_content .= $shortcode;
			}

			$block_content = do_shortcode( $block_content );
		}

		return $block_content;
	}
}

add_filter( 'render_block', 'sd_blocks_render_blocks', 10, 3 );

/**
 * Retrieves the shortcode slug from a given string.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::get_shortcode_slug()
 * @param string $str The input string from which to extract the shortcode slug.
 * @return string The extracted shortcode slug.
 */
function sd_get_shortcode_slug( $str ) {
	//_deprecated_function( 'sd_get_shortcode_slug', '3.1.0', 'AyeCode\\SuperDuper\\Utils::get_shortcode_slug' );
	return \AyeCode\SuperDuper\Utils::get_shortcode_slug( $str );
}

/**
 * Builds a shortcode string based on provided name, attributes, and content.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::build_shortcode()
 * @param string $name    Name of the shortcode. Required.
 * @param array  $args    Optional. Array of attributes for the shortcode. Default empty array.
 * @param string $content Optional. Content to be enclosed within the shortcode. Default empty string.
 * @return string Shortcode string if successful, otherwise an empty string.
 */
function sd_build_shortcode( $name, $args = array(), $content = '' ) {
	//_deprecated_function( 'sd_build_shortcode', '3.1.0', 'AyeCode\\SuperDuper\\Utils::build_shortcode' );
	return \AyeCode\SuperDuper\Utils::build_shortcode( $name, $args, $content );
}

/**
 * Finds and replaces a comprehensive set of dynamic data variables.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::replace_variables()
 * @param string|null $text The input string containing variables.
 * @return string The text with variables replaced, which may include HTML.
 */
function sd_replace_variables( $text ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::replace_variables' );
	return \AyeCode\SuperDuper\Utils::replace_variables( (string) $text );
}
