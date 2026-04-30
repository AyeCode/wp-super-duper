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
	 * Loads the global function/trait files that cannot be autoloaded, then
	 * ensures WP_Super_Duper resolves to the winning copy's class.
	 */
	public function __construct() {
		$includes = SUPER_DUPER_INCLUDES_PATH;

		// Global sd_* helper functions — cannot be autoloaded.
		if ( ! function_exists( 'sd_get_margin_input' ) ) {
			require_once $includes . 'functions.php';
			require_once $includes . 'helpers.php';
		}

		// Legacy global-name trait files so plugins referencing WP_Super_Duper_Initializer
		// etc. by their old names continue to work.
		if ( ! trait_exists( 'WP_Super_Duper_Utilities' ) ) {
			require_once $includes . 'traits/trait-utilities.php';
			require_once $includes . 'traits/trait-page-builders.php';
			require_once $includes . 'traits/trait-shortcode-inserter.php';
			require_once $includes . 'traits/trait-widget-form.php';
			require_once $includes . 'traits/trait-output-handler.php';
			require_once $includes . 'traits/trait-gutenberg-block.php';
			require_once $includes . 'traits/trait-initializer.php';
		}

		// Map WP_Super_Duper → the winning copy's class so all existing
		// `class MyWidget extends WP_Super_Duper` code continues to work.
		// Step 0 may have set this alias from a losing copy; we cannot change
		// an existing alias, so the guard prevents a duplicate-alias error.
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
	}
}
