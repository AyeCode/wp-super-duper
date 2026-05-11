<?php
/**
 * Plugin Name: WP Super Duper
 * Plugin URI: https://ayecode.io/
 * Description: A WordPress framework to build a widget, shortcode and Gutenberg block all from a single class.
 * Version: 3.0.7-beta
 * Author: AyeCode Ltd
 * Author URI: https://ayecode.io/
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: ayecode-connect
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package WP_Super_Duper
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// When bumping the version, update it in:
// 1. The Plugin Name header above.
// 2. package-loader.php ($this_version)
// 3. composer.json

// Boot the package loader so the framework works as a standalone plugin.
require_once __DIR__ . '/package-loader.php';
