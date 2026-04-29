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
	 * Add any global (non-instance) hooks here.
	 */
	public function __construct() {
		// Currently all framework hooks are registered per-widget instance
		// inside Traits\Initializer::initialize_super_duper().
		// Global hooks go here as the framework grows.
	}
}
