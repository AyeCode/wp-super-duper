<?php
/**
 * AyeCode Package Loader (v1.0.0)
 *
 * Handles version negotiation and bootstrapping for the WP Super Duper package.
 * Shared across all copies of the package (standalone plugin install + composer dependency).
 *
 * Do NOT edit below the configuration block.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// AyeCode Package Loader (v1.0.0)
( function () {
	// -------------------------------------------------------------------------
	// CONFIGURATION — update these values when bumping the version.
	// -------------------------------------------------------------------------

	// A unique key used to register this package in the global version registry.
	$registry_key = 'ayecode_super_duper_registry';

	// Must match the Version header in wp-super-duper.php. This drives negotiation.
	$this_version = '3.0.8-beta';

	$this_path = dirname( __FILE__ );

	// PSR-4 namespace prefix — maps AyeCode\SuperDuper\ to src/.
	$prefix = 'AyeCode\\SuperDuper\\';

	// Fully-qualified Loader class — instantiated in Step 3 after SPL autoloader is live.
	$loader_class = 'AyeCode\\SuperDuper\\Loader';

	// Hook and priority at which the Loader class is instantiated.
	$loader_hook     = 'plugins_loaded';
	$loader_priority = 10;

	// Constants to define ONLY if this package version wins the negotiation.
	$winning_constants = array(
		'SUPER_DUPER_VER'           => $this_version,
		'SUPER_DUPER_PLUGIN_URL'    => plugin_dir_url( __FILE__ ),
		'SUPER_DUPER_INCLUDES_PATH' => $this_path . '/includes/',
	);

	// -------------------------------------------------------------------------
	// DO NOT EDIT BELOW THIS LINE. CORE PACKAGE NEGOTIATION LOGIC.
	// -------------------------------------------------------------------------

	/**
	 * Step 1: Version Negotiation (Priority 1)
	 *
	 * Every installed copy of this package registers itself. The highest version
	 * wins and its path is stored as the canonical source for Steps 2 and 3.
	 */
	add_action( 'plugins_loaded', function () use ( $registry_key, $this_version, $this_path ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || version_compare( $this_version, $GLOBALS[ $registry_key ]['version'], '>' ) ) {
			$GLOBALS[ $registry_key ] = [
				'version' => $this_version,
				'path'    => $this_path,
			];
		}
	}, 1 );

	/**
	 * Step 2: Lazy Loading Registration (Priority 2)
	 *
	 * Only the winning version registers an SPL autoloader. Losing copies bail
	 * out early, preventing duplicate class definitions.
	 */
	add_action( 'plugins_loaded', function () use ( $registry_key, $this_path, $prefix ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || $GLOBALS[ $registry_key ]['path'] !== $this_path ) {
			return;
		}

		$base_dir = $this_path . '/src/';

		spl_autoload_register( function ( $class ) use ( $prefix, $base_dir ) {
			if ( strpos( $class, $prefix ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require $file;
			}
		}, true, true );

	}, 2 );

	/**
	 * Step 3: Package Initialization (Configurable Hook/Priority)
	 *
	 * Defines constants and boots the Loader class, but only for the winning
	 * version. All other copies have already bailed in Step 2.
	 */
	if ( ! empty( $loader_class ) ) {
		add_action( $loader_hook, function () use ( $registry_key, $this_path, $loader_class, $winning_constants ) {
			if ( empty( $GLOBALS[ $registry_key ] ) || $GLOBALS[ $registry_key ]['path'] !== $this_path ) {
				return;
			}

			foreach ( $winning_constants as $name => $value ) {
				if ( ! defined( $name ) ) {
					define( $name, $value );
				}
			}

			// class_exists() triggers the autoloader registered in Step 2.
			if ( class_exists( $loader_class ) ) {
				new $loader_class();
			}
		}, $loader_priority );
	}

} )();
