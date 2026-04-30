<?php
/**
 * WP Super Duper Helper Functions
 *
 * @deprecated 3.1.0 These global functions are deprecated. Use the static methods
 *                   in AyeCode\SuperDuper\Utils instead.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if the current request is for any page builder's preview mode.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::is_preview()
 * @return bool True if a builder preview is detected, false otherwise.
 */
function sd_is_preview() {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::is_preview' );
	return \AyeCode\SuperDuper\Utils::is_preview();
}

/**
 * Converts boolean-like strings in an array to actual booleans.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::string_to_bool()
 * @param array $options The array to process.
 * @return array The processed array.
 */
function sd_string_to_bool( $options ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::string_to_bool' );
	return \AyeCode\SuperDuper\Utils::string_to_bool( $options );
}

/**
 * Encodes special characters and shortcode tags for safe transport.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::encode_shortcodes()
 * @param string $content The content to encode.
 * @return string The encoded content.
 */
function sd_encode_shortcodes( $content ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::encode_shortcodes' );
	return \AyeCode\SuperDuper\Utils::encode_shortcodes( (string) $content );
}

/**
 * Decodes special characters and shortcode tags back to their original form.
 *
 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Utils::decode_shortcodes()
 * @param string $content The content to decode.
 * @return string The decoded content.
 */
function sd_decode_shortcodes( $content ) {
	//_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Utils::decode_shortcodes' );
	return \AyeCode\SuperDuper\Utils::decode_shortcodes( (string) $content );
}
