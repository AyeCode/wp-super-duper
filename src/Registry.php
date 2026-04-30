<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper Registry
 *
 * Stores block/shortcode/widget registrations without instantiating classes.
 * On the frontend, classes are only instantiated when their shortcode actually
 * fires. On admin and AJAX requests, all registered classes are instantiated
 * eagerly so block editor configuration and AJAX handlers are available.
 *
 * @version 3.0.4-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {

	/**
	 * Registered entries keyed by base_id.
	 *
	 * @var array<string, array{class_name: string, output_types: string[], file_path: string}>
	 */
	private static array $entries = [];

	/**
	 * Cached class instances keyed by base_id.
	 *
	 * @var array<string, object>
	 */
	private static array $instances = [];

	/**
	 * Register a block/shortcode/widget class for lazy loading.
	 *
	 * @param string   $base_id      The shortcode / block base ID.
	 * @param string   $class_name   The fully-qualified class name (no namespace required).
	 * @param string[] $output_types Supported output types: 'block', 'shortcode', 'widget'.
	 * @param string   $file_path    Absolute path to the class file. Required if the class is
	 *                               not already loaded or PSR-4 autoloadable.
	 */
	public static function register( string $base_id, string $class_name, array $output_types = [], string $file_path = '' ): void {
		self::$entries[ $base_id ] = [
			'class_name'   => $class_name,
			'output_types' => $output_types,
			'file_path'    => $file_path,
		];
	}

	/**
	 * Boot the registry. Hooked to widgets_init at priority 99.
	 *
	 * Admin / AJAX: instantiates all registered classes eagerly so block editor
	 * configuration, widget admin forms, and AJAX handlers are all available.
	 *
	 * Frontend: registers lightweight shortcode closures for non-widget classes.
	 * Widget-type classes are passed to register_widget() — WordPress handles
	 * instantiation when a sidebar is rendered.
	 */
	public static function boot(): void {
		$eager = is_admin() || wp_doing_ajax();

		foreach ( self::$entries as $base_id => $entry ) {
			$class_name   = $entry['class_name'];
			$output_types = $entry['output_types'];
			$needs_widget = empty( $output_types ) || in_array( 'widget', $output_types, true );

			if ( $eager ) {
				self::get_instance( $base_id );
				if ( $needs_widget && is_subclass_of( $class_name, 'WP_Widget' ) ) {
					register_widget( $class_name );
				}
				continue;
			}

			// Frontend — lazy loading.
			if ( $needs_widget && is_subclass_of( $class_name, 'WP_Widget' ) ) {
				// WordPress instantiates widget classes at widgets_init priority 100.
				register_widget( $class_name );
			} else {
				add_shortcode( $base_id, static function ( $attrs, $content = '' ) use ( $base_id ) {
					return self::get_instance( $base_id )->shortcode_output( $attrs, $content );
				} );
			}
		}

		// Single AJAX proxy so block preview requests work on any context without
		// needing all classes pre-instantiated.
		add_action( 'wp_ajax_super_duper_output_shortcode', [ self::class, 'handle_ajax_shortcode' ], 1 );
	}

	/**
	 * Route a block-preview AJAX request to the correct class instance.
	 *
	 * Fires at priority 1 on wp_ajax_super_duper_output_shortcode. Instantiates
	 * only the one class required for the preview, then delegates to its own
	 * render_shortcode() handler which handles auth, output, and wp_die().
	 */
	public static function handle_ajax_shortcode(): void {
		$base_id = sanitize_key( wp_unslash( $_POST['shortcode'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( self::$entries[ $base_id ] ) ) {
			self::get_instance( $base_id )->render_shortcode();
			// render_shortcode() calls wp_die(), so execution stops here.
		}
		// Unknown base_id: fall through to handlers registered by eagerly-loaded blocks.
	}

	/**
	 * Get (or create) the cached instance for a given base_id.
	 *
	 * If a file_path was registered and the class is not yet loaded, the file is
	 * required before instantiation so no external autoloader is needed.
	 *
	 * @param string $base_id The block base ID.
	 * @return object The class instance.
	 */
	private static function get_instance( string $base_id ): object {
		if ( ! isset( self::$instances[ $base_id ] ) ) {
			$entry = self::$entries[ $base_id ];
			if ( ! empty( $entry['file_path'] ) && ! class_exists( $entry['class_name'], false ) ) {
				require_once $entry['file_path'];
			}
			$class                       = $entry['class_name'];
			self::$instances[ $base_id ] = new $class();
		}

		return self::$instances[ $base_id ];
	}
}
