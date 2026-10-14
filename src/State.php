<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper request state
 *
 * Holds the per-request "have we already done this" flags that used to live in
 * global variables. They are static rather than instance properties because the
 * guards must be shared across every block instance, and across both SuperDuper
 * and SuperDuperWidget — a static declared inside a trait would be per-using-class
 * and would fire twice.
 *
 * @version 3.0.10-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class State {

	/**
	 * Whether the shared widget admin JS/CSS has been enqueued this request.
	 *
	 * @var bool
	 */
	public static bool $widget_scripts_done = false;

	/**
	 * Whether the "Add Shortcode" media button has already been output this request.
	 *
	 * @var bool
	 */
	public static bool $insert_button_done = false;

	/**
	 * Block editor field components already emitted this request, keyed by component name.
	 *
	 * This is the dedupe that keeps the editor's inline JS from repeating an identical
	 * field config once per block.
	 *
	 * @var array<string, bool>
	 */
	public static array $enqueued_block_components = [];
}
