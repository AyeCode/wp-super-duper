<?php

// if this file is called directly, abort.
if( ! defined( 'ABSPATH' ) ) exit;

// Only load if not loaded already.
if ( ! defined('SUPER_DUPER_VER' ) ) {

	/**
	 * The single version definition for the framework.
	 */
	define( 'SUPER_DUPER_VER', '3.0.2-beta' );

	/**
	 * The plugin URL definition for the framework.
	 */
	define( 'SUPER_DUPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	// include the loader
	include_once( dirname( __FILE__ ) . "/includes/loader.php" );
}


