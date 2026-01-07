<?php
/**
 * WP Super Duper Framework Loader
 *
 * This file is responsible for setting up constants, loading all necessary
 * trait and helper files, and then conditionally loading the correct
 * WP_Super_Duper class shell.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_Super_Duper' ) ) {

	/**
	 * The single version definition for the framework.
	 */
	define( 'SUPER_DUPER_VER', '1.2.25' );

	/**
	 * The path to the includes directory for easier file loading.
	 */
	define( 'SUPER_DUPER_INCLUDES_PATH', __DIR__ . '/' );

	/**
	 * Load all the shared code components.
	 *
	 * The order is important: helpers and utilities first, then the
	 * more complex traits that might depend on them.
	 */
	require_once SUPER_DUPER_INCLUDES_PATH . 'helpers.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-utilities.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-page-builders.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-shortcode-inserter.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-widget-form.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-output-handler.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-gutenberg-block.php';
	require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-initializer.php';

	/**
	 * Conditionally load the correct class shell.
	 *
	 * By default, the base (non-widget) version is loaded for maximum
	 * efficiency. To load the widget-enabled version, the consuming
	 * plugin/theme must define 'SUPER_DUPER_LOAD_WIDGET' as true.
	 */
	if ( defined( 'SUPER_DUPER_LOAD_WIDGET' ) && SUPER_DUPER_LOAD_WIDGET === true ) {
		// Load the class shell that extends WP_Widget.
		require_once SUPER_DUPER_INCLUDES_PATH . 'class-wp-super-duper-widget.php';
	} else {
		// Load the base class shell that does NOT extend WP_Widget.
		require_once SUPER_DUPER_INCLUDES_PATH . 'class-wp-super-duper-base.php';
	}
	//require_once SUPER_DUPER_INCLUDES_PATH . 'class-wp-super-duper-widget.php';
}
