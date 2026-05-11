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
 * @param bool  $flatten When true returns a flat key => label array (default). False returns optgroups.
 * @return array
 */
function ayecode_get_sd_colors( array $types = [ 'core' ], bool $flatten = true ): array {
	return \AyeCode\SuperDuper\Helpers\ColorOptions::aui( $types, $flatten );
}

/**
 * Build AUI classes from settings.
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
 * @param array $args
 * @return string
 */
function sd_build_aui_styles( $args ) {
	return \AyeCode\SuperDuper\Utils::build_aui_styles( $args );
}

/**
 * Build the hover styles from args.
 *
 * @param array $args
 * @param bool  $is_preview
 * @return string
 */
function sd_build_hover_styles( $args, $is_preview = false ) {
	return \AyeCode\SuperDuper\Utils::build_hover_styles( $args, $is_preview );
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
