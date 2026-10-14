<?php
/**
 * WP Super Duper Visibility Conditions Modal Script.
 *
 * @deprecated 3.1.0 Logic moved to AyeCode\SuperDuper\Assets\BlockEditorAssets.
 * @version 1.2.39
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sd_get_visibility_conditions_script' ) ) {
	/**
	 * Outputs the JavaScript for the VisibilityConditionsModal React component.
	 *
	 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Assets\BlockEditorAssets::visibility_conditions_script instead.
	 *
	 * @return string
	 */
	function sd_get_visibility_conditions_script() {
		_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Assets\\BlockEditorAssets::visibility_conditions_script' );

		return \AyeCode\SuperDuper\Assets\BlockEditorAssets::visibility_conditions_script();
	}
}
