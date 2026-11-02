<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * REST endpoints the dynamic data picker loads on demand.
 *
 * Schema:  GET /ayecode/v3/dynamic-data
 * Options: GET /ayecode/v3/dynamic-data/options/{source}?post_type=post
 * Preview: POST /ayecode/v3/dynamic-data/preview { text, post_id }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestApi {

	const NAMESPACE = 'ayecode/v3';
	const BASE      = 'dynamic-data';

	/**
	 * Register REST routes. Hooked to rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'get_schema' ),
				'permission_callback' => array( static::class, 'can_edit' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/options/(?P<source>[a-z0-9_]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'get_options' ),
				'permission_callback' => array( static::class, 'can_read_source' ),
				'args'                => array(
					'source'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'post_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( static::class, 'preview' ),
				'permission_callback' => array( static::class, 'can_preview' ),
				'args'                => array(
					'text'    => array(
						'required' => true,
						'type'     => 'string',
					),
					'post_id' => array(
						'required' => false,
						'type'     => 'integer',
						'minimum'  => 0,
					),
				),
			)
		);
	}

	/**
	 * @return bool|\WP_Error
	 */
	public static function can_edit() {
		return current_user_can( 'edit_posts' ) ? true : self::forbidden();
	}

	/**
	 * @return bool|\WP_Error
	 */
	public static function can_read_source( \WP_REST_Request $request ) {
		$source = Registry::get_source( (string) $request['source'] );

		if ( ! $source ) {
			return new \WP_Error( 'rest_not_found', __( 'Unknown option source.', 'ayecode-connect' ), array( 'status' => 404 ) );
		}

		return current_user_can( $source['capability'] ) ? true : self::forbidden();
	}

	/**
	 * Previewing against a post renders its data, so it needs edit rights on that post.
	 *
	 * @return bool|\WP_Error
	 */
	public static function can_preview( \WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];

		if ( $post_id ) {
			return current_user_can( 'edit_post', $post_id ) ? true : self::forbidden();
		}

		return self::can_edit();
	}

	/**
	 * Render a tag string the way the frontend would, against the post being edited.
	 */
	public static function preview( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['post_id'];
		$text    = (string) $request['text'];

		// A preview is one tag or a short line, not a page.
		if ( strlen( $text ) > Grammar::MAX_TAG_LENGTH * 4 ) {
			$text = substr( $text, 0, Grammar::MAX_TAG_LENGTH * 4 );
		}

		return rest_ensure_response(
			array(
				'html' => Renderer::render( $text, new Context( $post_id ? $post_id : null ) ),
			)
		);
	}

	public static function get_schema(): \WP_REST_Response {
		return rest_ensure_response( Registry::get_client_schema() );
	}

	public static function get_options( \WP_REST_Request $request ): \WP_REST_Response {
		$source = Registry::get_source( (string) $request['source'] );
		$params = $request->get_query_params();
		unset( $params['source'] );

		$options = array_values( (array) call_user_func( $source['callback'], $params ) );

		return rest_ensure_response( $options );
	}

	private static function forbidden(): \WP_Error {
		return new \WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to access this endpoint.', 'ayecode-connect' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
