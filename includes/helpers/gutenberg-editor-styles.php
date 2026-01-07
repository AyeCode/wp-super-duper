<?php
/**
 * WP Super Duper Gutenberg Block Editor Universal Script Helper
 *
 * This file contains the universal JavaScript logic that is shared across all
 * Super Duper blocks, such as utility functions and auto-recovery scripts.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Adds an inline style to the main editor stylesheet.
 */
function super_duper_inline_editor_styles() {

	echo "
    <style>
        .bsui label.components-base-control__label {
            width: 100%;
        }
    </style>
    ";
}

add_action( 'admin_head', 'super_duper_inline_editor_styles' );
