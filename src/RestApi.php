<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper REST API
 *
 * Exposes registered blocks as a read-only REST endpoint.
 *
 * Index:  GET /ayecode/v3/blocks/
 * Schema: GET /ayecode/v3/blocks/{slug}
 *
 * @version 3.0.8-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestApi {

	const NAMESPACE = 'ayecode/v3';
	const BASE      = 'blocks';

	/**
	 * Register REST routes. Hooked to rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'index_blocks' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'textdomain' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __( 'Filter blocks by textdomain.', 'ayecode-connect' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/(?P<slug>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'get_block' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'slug' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Permission check for all block endpoints.
	 *
	 * @return bool|\WP_Error
	 */
	public static function permission_check() {
		$cap = apply_filters( 'ayecode_sd_rest_blocks_capability', 'edit_posts' );

		if ( ! current_user_can( $cap ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'ayecode-connect' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * GET /ayecode/v3/blocks/
	 *
	 * Returns a summary list of all registered blocks.
	 * Accepts an optional `textdomain` query parameter to filter results.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function index_blocks( \WP_REST_Request $request ): \WP_REST_Response {
		$entries         = Registry::get_entries();
		$filter_domain   = sanitize_text_field( $request->get_param( 'textdomain' ) ?? '' );
		$blocks          = array();

		foreach ( $entries as $base_id => $entry ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance ) {
				continue;
			}

			$options    = $instance->options ?? array();
			$textdomain = $options['textdomain'] ?? '';

			if ( '' !== $filter_domain && $textdomain !== $filter_domain ) {
				continue;
			}

			$widget_ops = $options['widget_ops'] ?? array();

			$blocks[] = array(
				'slug'        => $base_id,
				'name'        => $options['name'] ?? '',
				'description' => $widget_ops['description'] ?? '',
				'textdomain'  => $textdomain,
				'class_name'  => $options['class_name'] ?? $entry['class_name'],
			);
		}

		return new \WP_REST_Response( $blocks, 200 );
	}

	/**
	 * GET /ayecode/v3/blocks/{slug}
	 *
	 * Returns the full schema for a single registered block.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_block( \WP_REST_Request $request ) {
		$slug     = $request->get_param( 'slug' );
		$instance = Registry::get_instance_public( $slug );

		if ( null === $instance ) {
			return new \WP_Error(
				'rest_block_not_found',
				__( 'No block found with that slug.', 'ayecode-connect' ),
				array( 'status' => 404 )
			);
		}

		$options    = $instance->options ?? array();
		$widget_ops = $options['widget_ops'] ?? array();
		$entry      = Registry::get_entries()[ $slug ] ?? array();

		$data = array(
			'slug'             => $slug,
			'name'             => $options['name'] ?? '',
			'description'      => $widget_ops['description'] ?? '',
			'textdomain'       => $options['textdomain'] ?? '',
			'class_name'       => $options['class_name'] ?? $entry['class_name'] ?? '',
			'icon'             => $options['block-icon'] ?? '',
			'category'         => $options['block-category'] ?? '',
			'output_types'     => $options['output_types'] ?? array(),
			'block_group_tabs' => $options['block_group_tabs'] ?? array(),
			'arguments'        => self::prepare_arguments( $instance->get_arguments() ),
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Ensure the arguments array is safe for JSON serialization.
	 *
	 * Strips any PHP closures that cannot be encoded (they should not exist in
	 * field configs, but this guard prevents fatal errors if one slips in via a filter).
	 *
	 * @param array $arguments
	 * @return array
	 */
	private static function prepare_arguments( array $arguments ): array {
		array_walk_recursive(
			$arguments,
			static function ( &$value ): void {
				if ( $value instanceof \Closure ) {
					$value = null;
				}
			}
		);

		return $arguments;
	}
}
