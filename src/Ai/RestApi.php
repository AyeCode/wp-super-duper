<?php

namespace AyeCode\SuperDuper\Ai;

/**
 * REST routes for the AI schema API.
 *
 * Namespace: ayecode/v3, sub-path /ai
 *   GET  /ai/catalog
 *   GET  /ai/schemas?blocks=a,b,c
 *   GET  /ai/vocabulary
 *   POST /ai/convert
 *   POST /ai/render      (dev-gated)
 *
 * These sit alongside the internal /blocks endpoints (AyeCode\SuperDuper\RestApi)
 * and never touch them. See docs/dev/ai-api.md for the response contract.
 */
class RestApi {

	const NAMESPACE = 'ayecode/v3';
	const BASE      = 'ai';

	/**
	 * Register REST routes. Hooked to rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/catalog',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'get_catalog' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'context' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'description'       => __( 'Filter to a single context: page, site, archive, single.', 'ayecode-connect' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/schemas',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'get_schemas' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'blocks'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __( 'Comma-separated block ids.', 'ayecode-connect' ),
					),
					'raw_compare' => array(
						'required' => false,
						'type'     => 'boolean',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/vocabulary',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( static::class, 'get_vocabulary' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'context' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'description'       => __( 'Filter to a single context: page, site, archive, single.', 'ayecode-connect' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/convert',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( static::class, 'post_convert' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'html' => array(
						'required'    => true,
						'type'        => 'string',
						// No sanitize_callback: the converter sanitizes the HTML
						// itself and must receive the raw markup, not a stripped copy.
						'description' => __( 'Bootstrap 5.3 HTML fragment to convert.', 'ayecode-connect' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . self::BASE . '/render',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( static::class, 'post_render' ),
				'permission_callback' => array( static::class, 'permission_check' ),
				'args'                => array(
					'blocks' => array(
						'required'    => true,
						'type'        => 'array',
						'description' => __( 'Native block tree to render to HTML.', 'ayecode-connect' ),
					),
				),
			)
		);
	}

	/**
	 * Permission check for all AI endpoints.
	 *
	 * @return bool|\WP_Error
	 */
	public static function permission_check() {
		$cap = apply_filters( 'ayecode_sd_rest_ai_capability', 'edit_pages' );

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
	 * GET /ai/catalog
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function get_catalog( \WP_REST_Request $request ): \WP_REST_Response {
		$context = (string) $request->get_param( 'context' );

		return new \WP_REST_Response( Catalog::catalog( $context ), 200 );
	}

	/**
	 * GET /ai/schemas
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_schemas( \WP_REST_Request $request ) {
		$requested = self::parse_block_list( (string) $request->get_param( 'blocks' ) );

		if ( empty( $requested ) ) {
			return new \WP_Error(
				'rest_missing_blocks',
				__( 'Provide one or more block ids in the blocks parameter.', 'ayecode-connect' ),
				array( 'status' => 400 )
			);
		}

		$unknown = array_values( array_filter( $requested, static fn( $id ) => ! Catalog::has_block( $id ) ) );

		if ( ! empty( $unknown ) ) {
			return new \WP_Error(
				'rest_unknown_blocks',
				sprintf(
					/* translators: %s: comma-separated list of block ids. */
					__( 'Unknown block ids: %s.', 'ayecode-connect' ),
					implode( ', ', $unknown )
				),
				array(
					'status'  => 400,
					'unknown' => $unknown,
				)
			);
		}

		$response = Catalog::schemas( $requested );

		if ( $request->get_param( 'raw_compare' ) ) {
			$response['debug'] = self::raw_compare( $requested, $response );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * GET /ai/vocabulary
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function get_vocabulary( \WP_REST_Request $request ): \WP_REST_Response {
		$context = (string) $request->get_param( 'context' );

		return new \WP_REST_Response( Vocabulary::vocabulary( $context ), 200 );
	}

	/**
	 * POST /ai/convert
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function post_convert( \WP_REST_Request $request ) {
		$html = (string) $request->get_param( 'html' );

		if ( '' === trim( $html ) ) {
			return new \WP_Error(
				'rest_missing_html',
				__( 'Provide an HTML fragment in the html parameter.', 'ayecode-connect' ),
				array( 'status' => 400 )
			);
		}

		$result = Converter::convert( $html );

		// Empty attribute maps must serialize as {} not [], so createBlock and the
		// block markup see an object; PHP arrays encode empty as [].
		$result['blocks'] = self::objectify_attributes( $result['blocks'] );

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * POST /ai/render — dev-gated front-end render of a native tree.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function post_render( \WP_REST_Request $request ) {
		if ( ! Renderer::is_enabled() ) {
			return new \WP_Error(
				'rest_render_disabled',
				__( 'The render endpoint is disabled. Enable it with the ayecode_sd_rest_ai_render_enabled filter.', 'ayecode-connect' ),
				array( 'status' => 404 )
			);
		}

		$blocks = (array) $request->get_param( 'blocks' );

		return new \WP_REST_Response( array( 'html' => Renderer::render( $blocks ) ), 200 );
	}

	/**
	 * Recursively cast each block's `attributes` to an object so an empty map
	 * encodes as {} rather than [].
	 *
	 * @param array $blocks
	 * @return array
	 */
	private static function objectify_attributes( array $blocks ): array {
		foreach ( $blocks as &$block ) {
			$block['attributes'] = (object) ( $block['attributes'] ?? array() );
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::objectify_attributes( $block['innerBlocks'] );
			}
		}
		unset( $block );

		return $blocks;
	}

	/**
	 * Split and normalize a comma-separated block list.
	 *
	 * @param string $raw
	 * @return string[]
	 */
	private static function parse_block_list( string $raw ): array {
		$ids = array_map( 'trim', explode( ',', $raw ) );
		$ids = array_filter( array_map( 'sanitize_key', $ids ) );

		return array_values( array_unique( $ids ) );
	}

	/**
	 * A rough raw-vs-transformed token comparison for the ?raw_compare=1 debug view.
	 *
	 * @param string[] $block_ids
	 * @param array    $response
	 * @return array
	 */
	private static function raw_compare( array $block_ids, array $response ): array {
		$estimate = static fn( $data ) => (int) ceil( strlen( (string) wp_json_encode( $data ) ) / 4 );

		$rows              = array();
		$transformed_total = $estimate( $response );

		foreach ( $block_ids as $base_id ) {
			$instance = \AyeCode\SuperDuper\Registry::get_instance_public( $base_id );
			if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
				continue;
			}

			$rows[ $base_id ] = array(
				'raw_tokens'         => $estimate( $instance->get_arguments() ),
				'transformed_tokens' => $estimate( $response['blocks'][ $base_id ] ?? array() ),
			);
		}

		return array(
			'per_block'                => $rows,
			'transformed_total_tokens' => $transformed_total,
			'note'                     => 'Token estimates are chars/4; common groups counted once in the total.',
		);
	}
}
