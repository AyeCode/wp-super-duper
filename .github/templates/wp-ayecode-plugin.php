<?php
/**
 * Plugin Name: WP AyeCode [Package Name]
 * Plugin URI: https://ayecode.io/
 * Description: [Short description of what this package does.]
 * Version: 1.0.0
 * Author: AyeCode Ltd
 * Author URI: https://ayecode.io/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ayecode-connect
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package WP_AyeCode_[PackageName]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// When bumping the version, update it in:
// 1. The Plugin Name header above.
// 2. package-loader.php ($this_version)
// 3. composer.json

// 1. Manually boot the package loader so the framework works as a standalone plugin.
require_once __DIR__ . '/package-loader.php';

// 2. Standalone dev-testing hook — fires AFTER the framework has booted at priority 10.
// Uncomment when needed for local development or testing only.
// add_action( 'plugins_loaded', function () {
// 	// Add any standalone-only bootstrap code here.
// }, 20 );
