<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper Registry
 *
 * Stores block/shortcode/widget registrations without instantiating classes.
 * On the frontend and on admin-ajax, classes are only instantiated when actually
 * needed. On admin screens and REST requests all registered classes are built up
 * front, because the block editor and page-builder integrations hook from inside
 * a block's constructor. See request_needs_all_blocks().
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
	 *                               Defaults to block and shortcode; widget is opt-in.
	 * @param string   $file_path    Absolute path to the class file. Required if the class is
	 *                               not already loaded or PSR-4 autoloadable.
	 */
	public static function register( string $base_id, string $class_name, array $output_types = [ 'block', 'shortcode' ], string $file_path = '' ): void {
		if ( empty( $output_types ) ) {
			$output_types = [ 'block', 'shortcode' ];
		}

		self::$entries[ $base_id ] = [
			'class_name'   => $class_name,
			'output_types' => $output_types,
			'file_path'    => $file_path,
		];
	}

	/**
	 * Boot the registry. Hooked to widgets_init at priority 99.
	 *
	 * Eager (admin screens, REST): instantiates every registered class so block editor
	 * configuration, widget admin forms and page-builder elements are all available.
	 *
	 * Lazy (frontend, admin-ajax): registers lightweight shortcode closures for
	 * non-widget classes. Widget-type classes go to register_widget(), which builds
	 * them immediately — that is WordPress's behaviour, not a lazy path.
	 */
	public static function boot(): void {
		/**
		 * Filter every registered block/shortcode/widget before booting.
		 *
		 * Runs after all plugins have registered and before anything is instantiated or
		 * hooked, so it is the single point at which any entry from any plugin can be
		 * removed or altered — a global or context-specific off switch.
		 *
		 * @since 3.0.9
		 *
		 * @param array<string, array{class_name: string, output_types: string[], file_path: string}> $entries Registered entries keyed by base_id.
		 */
		self::$entries = apply_filters( 'ayecode_sd_registered_blocks', self::$entries );

		$eager = self::request_needs_all_blocks();

		foreach ( self::$entries as $base_id => $entry ) {
			$class_name   = $entry['class_name'];
			$output_types = $entry['output_types'];
			$needs_widget = in_array( 'widget', $output_types, true );

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
	 * Whether this request has to have every registered class instantiated up front.
	 *
	 * Admin screens still do: the block editor, the Classic Editor shortcode button and
	 * the Fusion/Bricks element registrations are all hooked from inside a block's
	 * constructor, so nothing would register if the classes were never built.
	 *
	 * Admin screens with no use for blocks at all are skipped via Utils::pagenow_exclude().
	 *
	 * admin-ajax.php is the exception. is_admin() is true there, which previously made
	 * every AJAX request on the site — WooCommerce cart fragments, Heartbeat, any other
	 * plugin's — build all registered blocks for nothing. Our own three AJAX actions now
	 * resolve the single block they need through this Registry, and third-party handlers
	 * that render a shortcode are served by the lazy closures registered in boot().
	 *
	 * @return bool
	 */
	private static function request_needs_all_blocks(): bool {
		// Must be tested before is_admin(), which is also true on admin-ajax.php.
		if ( wp_doing_ajax() ) {
			/**
			 * Filter whether an AJAX request should instantiate every registered block.
			 *
			 * Escape hatch for a handler that depends on a hook registered from a block
			 * constructor rather than going through the Registry.
			 *
			 * @since 3.0.10
			 *
			 * @param bool $eager Defaults to false.
			 */
			return (bool) apply_filters( 'ayecode_sd_eager_on_ajax', false );
		}

		if ( is_admin() ) {
			// Utils::pagenow_exclude() is the long-standing list of admin screens that have
			// no use for Super Duper at all — Dashboard, Users, Media, Comments and friends.
			// It predates the registry and was written to skip widget registration; skipping
			// the whole instantiation pass is the same intent applied one level up. The
			// editor screens (post.php, post-new.php, widgets.php, customize.php) are
			// deliberately absent from it, so they stay eager and keep the block editor,
			// the Classic Editor button and the page-builder element hooks working.
			global $pagenow;

			return ! in_array( $pagenow, \AyeCode\SuperDuper\Utils::pagenow_exclude(), true );
		}

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Route a block-preview AJAX request to the correct class instance.
	 *
	 * Fires at priority 1 on wp_ajax_super_duper_output_shortcode. Instantiates
	 * only the one class required for the preview, then delegates to its own
	 * render_shortcode() handler which handles auth, output, and wp_die().
	 */
	public static function handle_ajax_shortcode(): void {
		// Authenticate before touching the registry: resolving an entry loads a class
		// file and runs its constructor, so it must not happen on an unverified request.
		// render_shortcode() checks again, which is harmless and keeps it safe standalone.
		check_ajax_referer( 'super_duper_output_shortcode', '_ajax_nonce', true );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( -1, 403 );
		}

		$base_id = sanitize_key( wp_unslash( $_POST['shortcode'] ?? '' ) );
		if ( isset( self::$entries[ $base_id ] ) ) {
			self::get_instance( $base_id )->render_shortcode();
			// render_shortcode() calls wp_die(), so execution stops here.
		}
		// Unknown base_id: fall through to handlers registered by eagerly-loaded blocks.
	}

	/**
	 * Return all registered entries keyed by base_id.
	 *
	 * @return array<string, array{class_name: string, output_types: string[], file_path: string}>
	 */
	public static function get_entries(): array {
		return self::$entries;
	}

	/**
	 * Get the cached instance for a given base_id, or null if not registered.
	 *
	 * @param string $base_id The block base ID.
	 * @return object|null
	 */
	public static function get_instance_public( string $base_id ): ?object {
		if ( ! isset( self::$entries[ $base_id ] ) ) {
			return null;
		}
		return self::get_instance( $base_id );
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
