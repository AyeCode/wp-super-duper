<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper Loader
 *
 * Instantiated by the package-loader.php negotiation step (Step 3).
 * Registers any global framework-level hooks. Per-widget hooks are
 * registered inside initialize_super_duper() in the Initializer trait.
 *
 * @version 3.0.4-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Loader {

	/**
	 * Boot the framework.
	 *
	 * At this stage all classes are autoloadable and constants are defined.
	 * Loads the global function files that cannot be autoloaded, then ensures
	 * WP_Super_Duper resolves to the winning copy's class.
	 */
	public function __construct() {
		$includes = SUPER_DUPER_INCLUDES_PATH;

		// Core helper functions (build_aui_class, build_aui_styles, etc.) — cannot be autoloaded.
		if ( ! function_exists( 'sd_build_aui_class' ) ) {
			require_once $includes . 'functions.php';
			require_once $includes . 'helpers.php';
		}

		// Deprecated sd_get_* field shims — loaded separately so the core utilities
		// are always available even when a consuming plugin has already loaded these.
		if ( ! function_exists( 'sd_get_margin_input' ) ) {
			require_once $includes . 'functions-deprecated.php';
		}

		// Map WP_Super_Duper → the winning copy's class so all existing
		// `class MyWidget extends WP_Super_Duper` code continues to work.
		// The guard prevents a duplicate-alias error if another copy got here first.
		if ( ! class_exists( 'WP_Super_Duper' ) ) {
			if ( defined( 'SUPER_DUPER_LOAD_WIDGET' ) && SUPER_DUPER_LOAD_WIDGET === true ) {
				class_alias( 'AyeCode\\SuperDuper\\SuperDuperWidget', 'WP_Super_Duper' );
			} else {
				class_alias( 'AyeCode\\SuperDuper\\SuperDuper', 'WP_Super_Duper' );
			}
		}

		// Boot the Registry at widgets_init priority 99 (before WordPress's widget
		// factory at priority 100) so lazy shortcode closures and widget registrations
		// are in place before any rendering begins.
		add_action( 'widgets_init', [ 'AyeCode\\SuperDuper\\Registry', 'boot' ], 99 );

		add_action( 'rest_api_init', [ 'AyeCode\\SuperDuper\\RestApi', 'register_routes' ] );

		// Registered here rather than from a block constructor. On the lazy frontend path
		// a constructor does not run until a shortcode renders during the_content, by which
		// point wp_head has already fired and admin-ajax has already dispatched.
		add_action( 'wp_head', [ 'AyeCode\\SuperDuper\\Utils', 'generator' ], 99 );
		add_action( 'admin_head', [ 'AyeCode\\SuperDuper\\Utils', 'generator' ], 99 );

		add_action( 'wp_ajax_super_duper_get_widget_settings', [ 'AyeCode\\SuperDuper\\SuperDuper', 'get_widget_settings' ] );
		add_action( 'wp_ajax_super_duper_get_picker', [ 'AyeCode\\SuperDuper\\SuperDuper', 'get_picker' ] );
	}
}
