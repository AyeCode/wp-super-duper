<?php
/**
 * WP Super Duper Gutenberg Block Editor Inline Styles.
 *
 * @deprecated 3.1.0 Logic moved to AyeCode\SuperDuper\Assets\BlockEditorAssets.
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'super_duper_inline_editor_styles' ) ) {
	/**
	 * Adds an inline style to the main editor stylesheet.
	 *
	 * @deprecated 3.1.0 Use AyeCode\SuperDuper\Assets\BlockEditorAssets::inline_editor_styles instead.
	 *
	 * @return void
	 */
	function super_duper_inline_editor_styles() {
		_deprecated_function( __FUNCTION__, '3.1.0', 'AyeCode\\SuperDuper\\Assets\\BlockEditorAssets::inline_editor_styles' );

		\AyeCode\SuperDuper\Assets\BlockEditorAssets::inline_editor_styles();
	}
}
