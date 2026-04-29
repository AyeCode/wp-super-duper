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
	$this_version = '3.0.5-beta';

	$this_path = dirname( __FILE__ );

	// PSR-4 namespace prefix — maps AyeCode\SuperDuper\ to src/.
	$prefix = 'AyeCode\\SuperDuper\\';

	// Fully-qualified Loader class — instantiated in Step 3 after SPL autoloader is live.
	$loader_class = 'AyeCode\\SuperDuper\\Loader';

	// Hook and priority at which the Loader class is instantiated (used in Phase 3).
	$loader_hook     = 'plugins_loaded';
	$loader_priority = 10;

	// Constants defined only if this version wins the negotiation.
	// Note: SUPER_DUPER_INCLUDES_PATH is intentionally omitted here — it is defined
	// inside includes/loader.php (guarded by class_exists) to keep the path relative
	// to the includes/ directory itself.
	$winning_constants = array(
		'SUPER_DUPER_VER'        => $this_version,
		'SUPER_DUPER_PLUGIN_URL' => plugin_dir_url( __FILE__ ),
	);

	// -------------------------------------------------------------------------
	// DO NOT EDIT BELOW THIS LINE. CORE PACKAGE NEGOTIATION LOGIC.
	// -------------------------------------------------------------------------

	/**
	 * Step 0: Early Claim (Direct-load time)
	 *
	 * Older bundled copies of this package use SUPER_DUPER_VER as a "loaded"
	 * guard and define the class at direct-load time (before plugins_loaded).
	 * If we are the first copy to run, we claim the constant and bootstrap
	 * immediately so those older copies see the constant already defined and
	 * bail out — preventing their inferior class definition from winning.
	 *
	 * This block is a no-op when another copy has already claimed the constant.
	 * The plugins_loaded steps below still run to handle negotiation between
	 * multiple copies that all use this new package-loader format.
	 */
	if ( ! defined( 'SUPER_DUPER_VER' ) ) {
		foreach ( $winning_constants as $name => $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
		if ( ! defined( 'SUPER_DUPER_INCLUDES_PATH' ) ) {
			define( 'SUPER_DUPER_INCLUDES_PATH', $this_path . '/includes/' );
		}

		// Register PSR-4 SPL autoloader for AyeCode\SuperDuper\ → src/.
		if ( ! empty( $prefix ) ) {
			spl_autoload_register( function ( $class ) use ( $prefix, $this_path ) {
				if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
					return;
				}
				$relative_class = substr( $class, strlen( $prefix ) );
				$file           = $this_path . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';
				if ( file_exists( $file ) ) {
					require $file;
				}
			} );
		}

		// Load global function files (cannot be autoloaded — define global sd_* functions).
		if ( ! function_exists( 'sd_get_margin_input' ) ) {
			require_once SUPER_DUPER_INCLUDES_PATH . 'functions.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'helpers.php';
		}

		// Load old (global-name) trait files so plugins referencing WP_Super_Duper_Initializer
		// etc. continue to work.
		if ( ! trait_exists( 'WP_Super_Duper_Utilities' ) ) {
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-utilities.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-page-builders.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-shortcode-inserter.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-widget-form.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-output-handler.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-gutenberg-block.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-initializer.php';
		}

		// Map WP_Super_Duper → AyeCode\SuperDuper\SuperDuper (or widget variant).
		if ( ! class_exists( 'WP_Super_Duper' ) ) {
			if ( defined( 'SUPER_DUPER_LOAD_WIDGET' ) && SUPER_DUPER_LOAD_WIDGET === true ) {
				class_alias( 'AyeCode\\SuperDuper\\SuperDuperWidget', 'WP_Super_Duper' );
			} else {
				class_alias( 'AyeCode\\SuperDuper\\SuperDuper', 'WP_Super_Duper' );
			}
		}
	}

	/**
	 * Step 1: Version Negotiation (Priority 1)
	 *
	 * Every installed copy of this package registers itself. The highest version
	 * wins and its path is stored as the canonical source for Steps 2 and 3.
	 */
	add_action( 'plugins_loaded', function () use ( $registry_key, $this_version, $this_path ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || version_compare( $this_version, $GLOBALS[ $registry_key ]['version'], '>' ) ) {
			$GLOBALS[ $registry_key ] = array(
				'version' => $this_version,
				'path'    => $this_path,
			);
		}
	}, 1 );

	/**
	 * Step 2: Class Loading (Priority 2)
	 *
	 * Only the winning version loads the framework classes. Losing copies bail
	 * out early, preventing duplicate class definitions.
	 *
	 * Constants are defined here (before the require) because the base class uses
	 * SUPER_DUPER_VER as a property default, which PHP resolves at parse time.
	 *
	 * Phase 2 bridge: requires includes/loader.php directly.
	 * Phase 3 will replace this with an SPL PSR-4 autoloader.
	 */
	add_action( 'plugins_loaded', function () use ( $registry_key, $this_path, $prefix, $winning_constants ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || $GLOBALS[ $registry_key ]['path'] !== $this_path ) {
			return;
		}

		// Define constants before requiring any class files that use them as property defaults.
		foreach ( $winning_constants as $name => $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		// Register PSR-4 SPL autoloader for AyeCode\SuperDuper\ → src/.
		if ( ! empty( $prefix ) ) {
			spl_autoload_register( function ( $class ) use ( $prefix, $this_path ) {
				if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
					return;
				}
				$relative_class = substr( $class, strlen( $prefix ) );
				$file           = $this_path . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';
				if ( file_exists( $file ) ) {
					require $file;
				}
			} );
		}

		// Load global function files that cannot be autoloaded (they define global sd_* functions).
		if ( ! defined( 'SUPER_DUPER_INCLUDES_PATH' ) ) {
			define( 'SUPER_DUPER_INCLUDES_PATH', $this_path . '/includes/' );
		}
		if ( ! function_exists( 'sd_get_margin_input' ) ) {
			require_once SUPER_DUPER_INCLUDES_PATH . 'functions.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'helpers.php';
		}

		// Load the old (global-name) trait files so that any plugin that directly
		// references WP_Super_Duper_Initializer (etc.) continues to work.
		if ( ! trait_exists( 'WP_Super_Duper_Utilities' ) ) {
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-utilities.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-page-builders.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-shortcode-inserter.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-widget-form.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-output-handler.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-gutenberg-block.php';
			require_once SUPER_DUPER_INCLUDES_PATH . 'traits/trait-initializer.php';
		}

		// Map WP_Super_Duper → AyeCode\SuperDuper\SuperDuper (and widget variant)
		// so all existing `class MyWidget extends WP_Super_Duper` code continues to work.
		if ( ! class_exists( 'WP_Super_Duper' ) ) {
			// Decide which class to alias based on the SUPER_DUPER_LOAD_WIDGET flag.
			if ( defined( 'SUPER_DUPER_LOAD_WIDGET' ) && SUPER_DUPER_LOAD_WIDGET === true ) {
				class_alias( 'AyeCode\\SuperDuper\\SuperDuperWidget', 'WP_Super_Duper' );
			} else {
				class_alias( 'AyeCode\\SuperDuper\\SuperDuper', 'WP_Super_Duper' );
			}
		}

		// NOTE: The Bricks element class alias (Super_Duper_Bricks_Element → BricksElement)
		// is NOT set up here because BricksElement extends \Bricks\Element, which only
		// exists when Bricks Builder is installed and active. The alias is registered
		// lazily in load_bricks_element_class(), which is only called when Bricks is present.

	}, 2 );

	/**
	 * Step 3: Loader Class Initialization (Configurable Hook/Priority)
	 *
	 * Instantiates the Loader class for the winning version.
	 * Populated in Phase 3 when $loader_class is set.
	 */
	add_action( $loader_hook, function () use ( $registry_key, $this_path, $loader_class, $winning_constants ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || $GLOBALS[ $registry_key ]['path'] !== $this_path ) {
			return;
		}

		// Constants are already defined in Step 2; this loop is a no-op until Phase 3
		// replaces Step 2 with a pure SPL autoloader (no constant definitions there).
		foreach ( $winning_constants as $name => $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		// Phase 3: $loader_class will be set to 'AyeCode\SuperDuper\Loader' and
		// class_exists() will trigger the SPL autoloader registered in Step 2.
		if ( ! empty( $loader_class ) && class_exists( $loader_class ) ) {
			new $loader_class();
		}
	}, $loader_priority );

} )();
