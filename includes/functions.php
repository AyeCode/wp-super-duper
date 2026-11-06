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
 *                               Defaults to block and shortcode. Widget support is opt-in because
 *                               it is the only type that forces the class to be instantiated on
 *                               every page load; block and shortcode stay fully lazy. Must match
 *                               the 'output_types' key the class declares in its $options array.
 * @param string   $file_path    Absolute path to the class file. Required when the class is
 *                               not PSR-4 autoloadable (i.e. most non-Composer plugins).
 *                               Use __DIR__ . '/path/to/class-file.php'.
 */
function ayecode_sd_register( string $base_id, string $class_name, array $output_types = [ 'block', 'shortcode' ], string $file_path = '' ): void {
	\AyeCode\SuperDuper\Registry::register( $base_id, $class_name, $output_types, $file_path );
}

/**
 * Return color options for use in block field definitions.
 *
 * Replaces the old sd_aui_colors() boolean-flag signature with a clean type array.
 * Pass 'none' to prepend an empty "— None —" option.
 *
 * Examples:
 *   ayecode_get_sd_colors()                                         — core colors, flat
 *   ayecode_get_sd_colors( ['none', 'core', 'outline'] )           — with None prepended
 *   ayecode_get_sd_colors( ['core', 'outline', 'branding'] )       — with branding appended
 *   ayecode_get_sd_colors( ['core', 'subtle', 'emphasis'] )        — adaptive dark-mode colors
 *   ayecode_get_sd_colors( ['core', 'outline', 'branding'], false ) — as optgroups
 *
 * @param array $types   Types to include: 'none', 'transparent', 'core', 'subtle', 'emphasis',
 *                       'outline', 'outline_btn_text', 'branding'. Defaults to ['core'].
 * @param bool  $flatten When true returns a flat key => label array. False returns optgroups (default).
 * @return array
 */
function ayecode_get_sd_colors( array $types = [ 'core' ], bool $flatten = false ): array {
	return \AyeCode\SuperDuper\Helpers\ColorOptions::aui( $types, $flatten );
}

/**
 * Build AUI classes from settings.
 *
 * This needs to be kept in sync with the JS version in src/Assets/BlockEditorAssets.php
 *
 * @param array $args
 * @return string
 */
function sd_build_aui_class( $args ) {
	return \AyeCode\SuperDuper\Utils::build_aui_class( $args );
}

/**
 * Build Style output from arguments.
 *
 * This needs to be kept in sync with the JS version in src/Assets/BlockEditorAssets.php
 *
 * @param array $args
 * @return string
 */
function sd_build_aui_styles( $args ) {
	return \AyeCode\SuperDuper\Utils::build_aui_styles( $args );
}

/**
 * Build the hover styles from args.
 *
 * @param array  $args
 * @param bool   $is_preview
 * @param string $inner Optional selector for an element inside the block, such as ' .btn'.
 * @return string
 */
function sd_build_hover_styles( $args, $is_preview = false, $inner = '' ) {
	return \AyeCode\SuperDuper\Utils::build_hover_styles( $args, $is_preview, $inner );
}

/**
 * Try to get a CSS color variable for a given value.
 *
 * @param string $var
 * @return mixed|string
 */
function sd_get_color_from_var( $var ) {
	return \AyeCode\SuperDuper\Utils::get_color_from_var( $var );
}

/**
 * Sanitize single or multiple HTML classes.
 *
 * @param string|array $classes
 * @param string       $sep
 * @return string
 */
function sd_sanitize_html_classes( $classes, $sep = ' ' ) {
	return \AyeCode\SuperDuper\Utils::sanitize_html_classes( $classes, $sep );
}

/**
 * Get the current URL as a raw (un-HTML-escaped) string,
 * safe for redirects, DB storage, HTTP requests, etc.
 */
function ayecode_get_current_url( $with_query_string = true ) {
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
	} elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) ) {
		$request_uri = wp_unslash( $_SERVER['SCRIPT_NAME'] );
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$request_uri .= '?' . wp_unslash( $_SERVER['QUERY_STRING'] );
		}
	} else {
		$request_uri = '/';
	}

	if ( ! $with_query_string ) {
		$request_uri = wp_parse_url( $request_uri, PHP_URL_PATH );
	}

	// esc_url_raw sanitizes (strips invalid protocols, control chars, etc.)
	// without HTML-encoding ampersands — correct for non-display contexts.
	return esc_url_raw( home_url( $request_uri ) );
}

// -----------------------------------------------------------------------------
// Output attribute helpers
//
// Together with sd_build_aui_class() and sd_build_aui_styles() above these are
// the public surface a block calls from output(). The *_attr helpers return a
// finished attribute (or nothing); the two builders return the bare value.
// All of them share one spacing convention: no leading space, a trailing space
// when there is output, so they drop straight into an opening tag.
// -----------------------------------------------------------------------------

/**
 * Build a finished, escaped class attribute from the block settings.
 *
 * @param array  $args          Settings array from a widget/shortcode instance.
 * @param string $extra_classes Optional. Space-separated classes to add before the built ones.
 * @return string `class="..." ` or an empty string.
 */
function sd_build_aui_class_attr( $args, $extra_classes = '' ) {
	return \AyeCode\SuperDuper\Utils::build_aui_class_attr( $args, $extra_classes );
}

/**
 * Build a finished, escaped style attribute from the block settings.
 *
 * Do not pass the result through any other sanitizer.
 *
 * @param array $args Settings array from a widget/shortcode instance.
 * @return string `style="..." ` or an empty string.
 */
function sd_build_aui_style_attr( $args ) {
	return \AyeCode\SuperDuper\Utils::build_aui_style_attr( $args );
}

/**
 * Build the class and style attributes for a block's wrapper element.
 *
 *     '<div ' . sd_build_aui_wrapper_attrs( $args, 'card' ) . '>'
 *
 * @param array  $args          Settings array from a widget/shortcode instance.
 * @param string $extra_classes Optional. Space-separated classes to add before the built ones.
 * @return string The attributes with a trailing space, or an empty string.
 */
function sd_build_aui_wrapper_attrs( $args, $extra_classes = '' ) {
	return \AyeCode\SuperDuper\Utils::build_aui_wrapper_attrs( $args, $extra_classes );
}

/**
 * Build an escaped HTML attributes string from the 'custom', 'new_window' and 'nofollow' settings.
 *
 * Meant for the link or inner element, not the wrapper. Event handler attributes
 * and href are dropped.
 *
 * @param array $args Settings array (typically from shortcode/widget output).
 * @return string Space-separated, escaped `key="value" ` pairs.
 */
function sd_build_attributes_string_escaped( $args ) {
	return \AyeCode\SuperDuper\Utils::build_attributes_string_escaped( $args );
}

/**
 * Parse a custom attributes string (`key|value,key|value`) into an array.
 *
 * @param string $attributes_string
 * @param string $delimiter
 * @return array
 */
function sd_parse_custom_attributes( $attributes_string, $delimiter = ',' ) {
	return \AyeCode\SuperDuper\Utils::parse_custom_attributes( $attributes_string, $delimiter );
}

/**
 * Replace the dynamic data variables in a string.
 *
 * @param string|null $text The input string containing variables.
 * @return string The text with variables replaced, which may include HTML.
 */
function sd_replace_variables( $text ) {
	return \AyeCode\SuperDuper\Utils::replace_variables( (string) $text );
}

/**
 * Build a shortcode string from a name, attributes and content.
 *
 * @param string $name    Name of the shortcode. Required.
 * @param array  $args    Optional. Shortcode attributes.
 * @param string $content Optional. Content to enclose.
 * @return string Shortcode string, or an empty string.
 */
function sd_build_shortcode( $name, $args = [], $content = '' ) {
	return \AyeCode\SuperDuper\Utils::build_shortcode( $name, $args, $content );
}

/**
 * Get the shortcode slug from a string such as `[bs_alert type="info"]`.
 *
 * @param string $str
 * @return string
 */
function sd_get_shortcode_slug( $str ) {
	return \AyeCode\SuperDuper\Utils::get_shortcode_slug( $str );
}

/**
 * Get the template page options for a select field.
 *
 * @param array $args
 * @return array
 */
function sd_template_page_options( $args = [] ) {
	return \AyeCode\SuperDuper\Utils::template_page_options( $args );
}

/**
 * Get the template part options for a select field.
 *
 * @param array $args
 * @return array
 */
function sd_template_part_options( $args = [] ) {
	return \AyeCode\SuperDuper\Utils::template_part_options( $args );
}

/**
 * Get a template part by slug.
 *
 * @param string $slug
 * @return array
 */
function sd_get_template_part_by_slug( $slug ) {
	return \AyeCode\SuperDuper\Utils::get_template_part_by_slug( $slug );
}

/**
 * Get the rendered content of a page.
 *
 * @param int $page_id
 * @return string
 */
function sd_get_page_content( $page_id ) {
	return \AyeCode\SuperDuper\Utils::get_page_content( $page_id );
}

/**
 * Get the rendered content of a template part.
 *
 * @param array $template_part
 * @return string
 */
function sd_get_template_part_content( $template_part ) {
	return \AyeCode\SuperDuper\Utils::get_template_part_content( $template_part );
}

/**
 * Page names (global $pagenow) on which widget registration is skipped.
 *
 * @return array
 */
function sd_pagenow_exclude() {
	return \AyeCode\SuperDuper\Utils::pagenow_exclude();
}

/**
 * Widget class names that are never registered.
 *
 * @return array
 */
function sd_widget_exclude() {
	return \AyeCode\SuperDuper\Utils::widget_exclude();
}
