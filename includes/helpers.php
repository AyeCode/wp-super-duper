<?php
/**
 * WP Super Duper Helper Functions
 *
 * This file contains standalone, stateless functions that are used throughout
 * the framework. Moving them out of the class structure improves performance
 * as they are loaded only once and are not tied to object instances.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if the current request is for any page builder's preview mode.
 *
 * @return bool True if a builder preview is detected, false otherwise.
 */
function sd_is_preview() {
	// Check for Elementor
	if ( isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor' ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor_ajax' ) ) {
		return true;
	}
	// Check for Divi
	if ( isset( $_REQUEST['et_fb'] ) || isset( $_REQUEST['et_pb_preview'] ) ) {
		return true;
	}
	// Check for Beaver Builder
	if ( isset( $_REQUEST['fl_builder'] ) ) {
		return true;
	}
	// Check for SiteOrigin
	if ( ! empty( $_REQUEST['siteorigin_panels_live_editor'] ) ) {
		return true;
	}
	// Check for Cornerstone
	if ( ! empty( $_REQUEST['cornerstone_preview'] ) || basename( $_SERVER['REQUEST_URI'] ) == 'cornerstone-endpoint' ) {
		return true;
	}
	// Check for Fusion Builder (Avada)
	if ( ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] ) ) {
		return true;
	}
	// Check for Oxygen
	if ( ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( substr( $_REQUEST['action'], 0, 11 ) === "oxy_render_" || substr( $_REQUEST['action'], 0, 10 ) === "ct_render_" ) ) ) {
		return true;
	}
	// Check for Kallyas Zion
	if ( function_exists( 'znhg_kallyas_theme_config' ) && ! empty( $_REQUEST['zn_pb_edit'] ) ) {
		return true;
	}
	// Check for Bricks Builder
	if ( function_exists( 'bricks_is_builder' ) && ( bricks_is_builder() || bricks_is_builder_call() ) ) {
		return true;
	}
	// Check for Gutenberg AJAX render
	if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'super_duper_output_shortcode' ) {
		return true;
	}

	return false;
}

/**
 * Converts boolean-like strings in an array to actual booleans.
 *
 * @param array $options The array to process.
 * @return array The processed array.
 */
function sd_string_to_bool( $options ) {
	if ( ! is_array( $options ) ) {
		return $options;
	}
	foreach ( $options as $key => $val ) {
		if ( $val === 'false' ) {
			$options[ $key ] = false;
		} elseif ( $val === 'true' ) {
			$options[ $key ] = true;
		}
	}
	return $options;
}

/**
 * Encodes special characters and shortcode tags for safe transport.
 *
 * This is useful for passing content with shortcodes inside attributes.
 *
 * @param string $content The content to encode.
 * @return string The encoded content.
 */
function sd_encode_shortcodes( $content ) {
	// Avoids existing encoded tags.
	$trans   = [
		'&#91;' => '&#091;',
		'&#93;' => '&#093;',
		'&amp;#91;' => '&#091;',
		'&amp;#93;' => '&#093;',
		'&lt;' => '&0lt;',
		'&gt;' => '&0gt;',
		'&amp;lt;' => '&0lt;',
		'&amp;gt;' => '&0gt;',
	];
	$content = strtr( $content, $trans );

	$trans   = [
		'[' => '&#91;',
		']' => '&#93;',
		'<' => '&lt;',
		'>' => '&gt;',
		'"' => '&quot;',
		"'" => '&#39;',
	];
	$content = strtr( $content, $trans );

	return $content;
}

/**
 * Decodes special characters and shortcode tags back to their original form.
 *
 * @param string $content The content to decode.
 * @return string The decoded content.
 */
function sd_decode_shortcodes( $content ) {
	$trans   = [
		'&#91;' => '[',
		'&#93;' => ']',
		'&amp;#91;' => '[',
		'&amp;#93;' => ']',
		'&lt;' => '<',
		'&gt;' => '>',
		'&amp;lt;' => '<',
		'&amp;gt;' => '>',
		'&quot;' => '"',
		'&apos;' => "'",
	];
	$content = strtr( $content, $trans );

	// Restore intentionally double-encoded entities.
	$trans   = [
		'&#091;' => '&#91;',
		'&#093;' => '&#093;',
		'&amp;#091;' => '&#91;',
		'&amp;#093;' => '&#093;',
		'&0lt;' => '&lt;',
		'&0gt;' => '&gt;',
		'&amp;0lt;' => '&lt;',
		'&amp;0gt;' => '&gt;',
	];
	$content = strtr( $content, $trans );

	return $content;
}
