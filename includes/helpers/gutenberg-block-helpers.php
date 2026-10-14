<?php
/**
 * WP Super Duper Gutenberg Block Editor Universal Script Helper.
 *
 * @deprecated 3.1.0 Logic moved to AyeCode\SuperDuper\Assets\BlockEditorAssets.
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sd_get_block_editor_global_js' ) ) {
	/**
	 * Outputs the universal JavaScript functions for the block editor.
	 *
	 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Assets\BlockEditorAssets::block_editor_global_js instead.
	 *
	 * @return string
	 */
	function sd_get_block_editor_global_js() {
		_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Assets\\BlockEditorAssets::block_editor_global_js' );

		return \AyeCode\SuperDuper\Assets\BlockEditorAssets::block_editor_global_js();
	}
}
