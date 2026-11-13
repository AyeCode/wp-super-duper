<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * The tags Super Duper ships with, and the option sources they use.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoreTags {

	/** A word limit means nothing on a date. */
	const DATE_MODIFIERS = [ 'link', 'new_tab', 'fallback' ];

	/**
	 * User meta keys a tag may print without code opting more in. User meta is an
	 * allowlist rather than a protected-key check because plugins keep personal data
	 * there under unprotected keys, such as WooCommerce's billing_phone.
	 */
	const USER_META_ALLOWLIST = [ 'first_name', 'last_name', 'nickname', 'description' ];

	/**
	 * Post IDs whose content is being rendered by {post_content}, so a post that
	 * contains the tag does not render itself forever.
	 *
	 * @var array<int, true>
	 */
	private static array $rendering_content = [];

	public static function register(): void {
		Registry::register_group( 'post', __( 'Post', 'ayecode-connect' ), 10 );
		Registry::register_group( 'featured_image', __( 'Featured Image', 'ayecode-connect' ), 20 );
		Registry::register_group( 'author', __( 'Post Author', 'ayecode-connect' ), 30 );
		Registry::register_group( 'user', __( 'Current User', 'ayecode-connect' ), 40 );
		Registry::register_group( 'archive', __( 'Archive & Term', 'ayecode-connect' ), 50 );
		Registry::register_group( 'site', __( 'Site', 'ayecode-connect' ), 60 );
		Registry::register_group( 'request', __( 'Request', 'ayecode-connect' ), 70 );

		self::register_post_tags();
		self::register_featured_image_tags();
		self::register_author_tags();
		self::register_user_tags();
		self::register_archive_tags();
		self::register_site_tags();
		self::register_meta_tags();
		self::register_count_tags();

		Registry::register(
			'request_parameter',
			[
				'label'    => __( 'Request Parameter', 'ayecode-connect' ),
				'group'    => 'request',
				'args'     => [
					[
						'key'         => 'key',
						'label'       => __( 'Parameter name', 'ayecode-connect' ),
						'primary'     => true,
						'required'    => true,
						'placeholder' => 'ref',
					],
				],
				'callback' => [ static::class, 'request_parameter' ],
			]
		);

		Registry::register_source( 'post_meta_keys', [ static::class, 'source_post_meta_keys' ] );
		Registry::register_source( 'taxonomies', [ static::class, 'source_taxonomies' ] );
		Registry::register_source( 'image_sizes', [ static::class, 'source_image_sizes' ] );
		Registry::register_source( 'user_meta_keys', [ static::class, 'source_user_meta_keys' ] );
		Registry::register_source( 'term_meta_keys', [ static::class, 'source_term_meta_keys' ] );
		Registry::register_source( 'post_types', [ static::class, 'source_post_types' ] );
		Registry::register_source( 'user_roles', [ static::class, 'source_user_roles' ] );
	}

	// -------------------------------------------------------------------------
	// Post
	// -------------------------------------------------------------------------

	private static function register_post_tags(): void {
		$link = [ static::class, 'post_link' ];

		// Simple fields are served by post_field(), which switches on the tag name
		// the renderer passes. No closure per field: a closure costs about 1 KB of
		// request memory each, and this registry loads on every page with a tag.
		$simple = [
			'post_title'    => [ __( 'Post Title', 'ayecode-connect' ), 'text' ],
			'post_id'       => [ __( 'Post ID', 'ayecode-connect' ), 'number' ],
			'post_url'      => [ __( 'Post URL', 'ayecode-connect' ), 'url' ],
			'post_slug'     => [ __( 'Post Slug', 'ayecode-connect' ), 'text' ],
			'post_excerpt'  => [ __( 'Post Excerpt', 'ayecode-connect' ), 'text' ],
			'post_status'   => [ __( 'Post Status', 'ayecode-connect' ), 'text' ],
			'post_type'     => [ __( 'Post Type', 'ayecode-connect' ), 'text' ],
			'comment_count' => [ __( 'Comment Count', 'ayecode-connect' ), 'number' ],
			'comments_link' => [ __( 'Comments Link', 'ayecode-connect' ), 'url' ],
		];

		foreach ( $simple as $name => list( $label, $returns ) ) {
			Registry::register(
				$name,
				[
					'label'    => $label,
					'group'    => 'post',
					'returns'  => $returns,
					'link'     => $link,
					'callback' => [ static::class, 'post_field' ],
				]
			);
		}

		Registry::register(
			'post_content',
			[
				'label'    => __( 'Post Content', 'ayecode-connect' ),
				'group'    => 'post',
				'returns'  => 'html',
				'callback' => [ static::class, 'post_content' ],
			]
		);

		Registry::register(
			'post_date',
			[
				'label'     => __( 'Post Date', 'ayecode-connect' ),
				'group'     => 'post',
				'args'      => [ self::date_format_arg() ],
				'modifiers' => self::DATE_MODIFIERS,
				'link'      => $link,
				'callback'  => [ static::class, 'post_date' ],
			]
		);

		Registry::register(
			'post_modified_date',
			[
				'label'     => __( 'Post Modified Date', 'ayecode-connect' ),
				'group'     => 'post',
				'args'      => [ self::date_format_arg() ],
				'modifiers' => self::DATE_MODIFIERS,
				'link'      => $link,
				'callback'  => [ static::class, 'post_modified_date' ],
			]
		);

		Registry::register(
			'post_terms',
			[
				'label'    => __( 'Post Terms', 'ayecode-connect' ),
				'group'    => 'post',
				'args'     => [
					[
						'key'     => 'taxonomy',
						'label'   => __( 'Taxonomy', 'ayecode-connect' ),
						'type'    => 'select',
						'source'  => 'taxonomies',
						'default' => 'category',
						'primary' => true,
					],
					[
						'key'     => 'separator',
						'label'   => __( 'Separator', 'ayecode-connect' ),
						'default' => ', ',
					],
				],
				'callback' => [ static::class, 'post_terms' ],
			]
		);

		Registry::register(
			'post_meta',
			[
				'label'       => __( 'Post Meta', 'ayecode-connect' ),
				'group'       => 'post',
				'description' => __( 'A custom field saved on the post. Protected keys (starting with an underscore) are not available.', 'ayecode-connect' ),
				'args'        => [ self::key_arg( 'post_meta_keys' ) ],
				'link'        => $link,
				'callback'    => [ static::class, 'post_meta' ],
			]
		);
	}

	/**
	 * Link callback for the post tags: the permalink.
	 *
	 * @return string
	 */
	public static function post_link( array $args, Context $context ): string {
		return $context->post() ? (string) get_permalink( $context->post() ) : '';
	}

	/**
	 * The simple post tags, by the tag name the renderer passes.
	 *
	 * @param array   $args    Resolved args.
	 * @param Context $context Render context.
	 * @param array   $def     The normalized tag definition, with 'name'.
	 * @return mixed
	 */
	public static function post_field( array $args, Context $context, array $def = [] ) {
		$post = $context->post();
		if ( ! $post ) {
			return null;
		}

		switch ( $def['name'] ?? '' ) {
			case 'post_title':
				return get_the_title( $post );
			case 'post_id':
				return $post->ID;
			case 'post_url':
				return get_permalink( $post );
			case 'post_slug':
				return $post->post_name;
			case 'post_excerpt':
				return get_the_excerpt( $post );
			case 'post_status':
				return get_post_status( $post );
			case 'post_type':
				return get_post_type( $post );
			case 'comment_count':
				return get_comments_number( $post );
			case 'comments_link':
				return get_comments_link( $post );
		}

		return null;
	}

	/**
	 * {post_date format}
	 *
	 * @return string|null
	 */
	public static function post_date( array $args, Context $context ) {
		return $context->post() ? get_the_date( $args['format'], $context->post() ) : null;
	}

	/**
	 * {post_modified_date format}
	 *
	 * @return string|null
	 */
	public static function post_modified_date( array $args, Context $context ) {
		return $context->post() ? get_the_modified_date( $args['format'], $context->post() ) : null;
	}

	/**
	 * {request_parameter key}
	 *
	 * @return string|null
	 */
	public static function request_parameter( array $args ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only display of a query var.
		return isset( $_REQUEST[ $args['key'] ] ) && is_scalar( $_REQUEST[ $args['key'] ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $args['key'] ] ) ) : null;
	}

	/**
	 * {post_content}
	 *
	 * @return string|null
	 */
	public static function post_content( array $args, Context $context ) {
		$post = $context->post();
		if ( ! $post || isset( self::$rendering_content[ $post->ID ] ) ) {
			return null;
		}

		self::$rendering_content[ $post->ID ] = true;
		$content                              = apply_filters( 'the_content', $post->post_content );
		unset( self::$rendering_content[ $post->ID ] );

		return $content;
	}

	/**
	 * {post_terms:category @separator:' / '}
	 *
	 * @return string|null
	 */
	public static function post_terms( array $args, Context $context ) {
		$post = $context->post();

		// Only public taxonomies, so a private one cannot be listed on the frontend.
		if ( ! $post || ! taxonomy_exists( $args['taxonomy'] ) || ! is_taxonomy_viewable( $args['taxonomy'] ) ) {
			return null;
		}

		$terms = get_the_terms( $post, $args['taxonomy'] );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		return implode( (string) $args['separator'], wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * {post_meta:price}
	 *
	 * @return string|int|float|null
	 */
	public static function post_meta( array $args, Context $context ) {
		$post = $context->post();

		return $post ? self::meta_value( 'post', $post->ID, (string) $args['key'] ) : null;
	}

	/**
	 * Whether a meta key may be printed by a tag.
	 *
	 * Anyone who can edit a block can type any key, so protected keys stay hidden
	 * unless code opts them in.
	 */
	public static function is_meta_key_allowed( string $key, string $object_type ): bool {
		/**
		 * Filter whether a meta key may be printed by a dynamic data tag.
		 *
		 * @param bool   $allowed     Defaults to false for protected keys, and for user
		 *                            meta to whether the key is in USER_META_ALLOWLIST.
		 * @param string $key         Meta key.
		 * @param string $object_type post, user or term.
		 */
		$allowed = 'user' === $object_type
			? in_array( $key, self::USER_META_ALLOWLIST, true )
			: ! is_protected_meta( $key, $object_type );

		return (bool) apply_filters( 'sd_dynamic_data_meta_key_allowed', $allowed, $key, $object_type );
	}

	/**
	 * A single meta value a tag may print, or null.
	 *
	 * @param string $object_type post, user or term.
	 * @return string|int|float|null
	 */
	public static function meta_value( string $object_type, int $object_id, string $key ) {
		if ( ! $object_id || '' === $key || ! self::is_meta_key_allowed( $key, $object_type ) ) {
			return null;
		}

		$value = get_metadata( $object_type, $object_id, $key, true );

		return is_scalar( $value ) ? $value : null;
	}

	// -------------------------------------------------------------------------
	// User and term meta
	// -------------------------------------------------------------------------

	private static function register_meta_tags(): void {
		Registry::register(
			'author_meta',
			[
				'label'       => __( 'Author Meta', 'ayecode-connect' ),
				'group'       => 'author',
				'description' => __( 'A field saved on the post author\'s profile. Only first name, last name, nickname and bio unless more keys are allowed in code.', 'ayecode-connect' ),
				'args'        => [ self::key_arg( 'user_meta_keys' ) ],
				'link'        => [ static::class, 'author_link' ],
				'callback'    => [ static::class, 'author_meta' ],
			]
		);

		Registry::register(
			'user_meta',
			[
				'label'       => __( 'User Meta', 'ayecode-connect' ),
				'group'       => 'user',
				'description' => __( 'A field saved on the logged in visitor\'s profile. Only first name, last name, nickname and bio unless more keys are allowed in code.', 'ayecode-connect' ),
				'args'        => [ self::key_arg( 'user_meta_keys' ) ],
				'callback'    => [ static::class, 'user_meta' ],
			]
		);

		Registry::register(
			'term_meta',
			[
				'label'       => __( 'Term Meta', 'ayecode-connect' ),
				'group'       => 'archive',
				'description' => __( 'A field saved on the term being viewed. Protected keys (starting with an underscore) are not available.', 'ayecode-connect' ),
				'args'        => [ self::key_arg( 'term_meta_keys' ) ],
				'link'        => [ static::class, 'term_link' ],
				'callback'    => [ static::class, 'term_meta' ],
			]
		);
	}

	/**
	 * A meta key arg fed by one of the option sources.
	 */
	private static function key_arg( string $source ): array {
		return [
			'key'      => 'key',
			'label'    => __( 'Meta key', 'ayecode-connect' ),
			'type'     => 'combobox',
			'source'   => $source,
			'primary'  => true,
			'required' => true,
		];
	}

	/**
	 * {author_meta key}
	 *
	 * @return mixed
	 */
	public static function author_meta( array $args, Context $context ) {
		return $context->post() ? self::meta_value( 'user', (int) $context->post()->post_author, (string) $args['key'] ) : null;
	}

	/**
	 * {user_meta key}
	 *
	 * @return mixed
	 */
	public static function user_meta( array $args, Context $context ) {
		return $context->user() ? self::meta_value( 'user', $context->user()->ID, (string) $args['key'] ) : null;
	}

	/**
	 * {term_meta key}
	 *
	 * @return mixed
	 */
	public static function term_meta( array $args, Context $context ) {
		return $context->term() ? self::meta_value( 'term', $context->term()->term_id, (string) $args['key'] ) : null;
	}

	// -------------------------------------------------------------------------
	// Featured image
	// -------------------------------------------------------------------------

	private static function register_featured_image_tags(): void {
		Registry::register(
			'featured_image_url',
			[
				'label'    => __( 'Image URL', 'ayecode-connect' ),
				'group'    => 'featured_image',
				'returns'  => 'url',
				'args'     => [
					[
						'key'     => 'size',
						'label'   => __( 'Size', 'ayecode-connect' ),
						'type'    => 'select',
						'source'  => 'image_sizes',
						'default' => 'full',
						'primary' => true,
					],
				],
				'callback' => [ static::class, 'featured_image_url' ],
			]
		);

		$simple = [
			'featured_image_id'      => [ __( 'Image ID', 'ayecode-connect' ), 'number' ],
			'featured_image_alt'     => [ __( 'Alt Text', 'ayecode-connect' ), 'text' ],
			'featured_image_title'   => [ __( 'Title', 'ayecode-connect' ), 'text' ],
			'featured_image_caption' => [ __( 'Caption', 'ayecode-connect' ), 'text' ],
		];

		foreach ( $simple as $name => list( $label, $returns ) ) {
			Registry::register(
				$name,
				[
					'label'    => $label,
					'group'    => 'featured_image',
					'returns'  => $returns,
					'callback' => [ static::class, 'featured_image_field' ],
				]
			);
		}
	}

	/**
	 * The featured image attachment id of the context post, or 0.
	 */
	private static function thumbnail_id( Context $context ): int {
		return $context->post() && has_post_thumbnail( $context->post() ) ? (int) get_post_thumbnail_id( $context->post() ) : 0;
	}

	/**
	 * {featured_image_url size}
	 *
	 * @return string|false|null
	 */
	public static function featured_image_url( array $args, Context $context ) {
		$id = self::thumbnail_id( $context );
		return $id ? wp_get_attachment_image_url( $id, $args['size'] ) : null;
	}

	/**
	 * The simple featured image tags, by the tag name the renderer passes.
	 *
	 * @return mixed
	 */
	public static function featured_image_field( array $args, Context $context, array $def = [] ) {
		$id = self::thumbnail_id( $context );
		if ( ! $id ) {
			return null;
		}

		switch ( $def['name'] ?? '' ) {
			case 'featured_image_id':
				return $id;
			case 'featured_image_alt':
				return get_post_meta( $id, '_wp_attachment_image_alt', true );
			case 'featured_image_title':
				return get_the_title( $id );
			case 'featured_image_caption':
				return wp_get_attachment_caption( $id );
		}

		return null;
	}

	// -------------------------------------------------------------------------
	// Post author
	// -------------------------------------------------------------------------

	private static function register_author_tags(): void {
		$simple = [
			'author_name'    => [ __( 'Author Name', 'ayecode-connect' ), 'text' ],
			'author_id'      => [ __( 'Author ID', 'ayecode-connect' ), 'number' ],
			'author_email'   => [ __( 'Author Email', 'ayecode-connect' ), 'text' ],
			'author_bio'     => [ __( 'Author Bio', 'ayecode-connect' ), 'text' ],
			'author_website' => [ __( 'Author Website', 'ayecode-connect' ), 'url' ],
			'author_url'     => [ __( 'Author Posts URL', 'ayecode-connect' ), 'url' ],
		];

		foreach ( $simple as $name => list( $label, $returns ) ) {
			Registry::register(
				$name,
				[
					'label'    => $label,
					'group'    => 'author',
					'returns'  => $returns,
					'link'     => [ static::class, 'author_link' ],
					'callback' => [ static::class, 'author_field' ],
				]
			);
		}

		Registry::register(
			'author_profile_picture_url',
			[
				'label'    => __( 'Author Profile Picture URL', 'ayecode-connect' ),
				'group'    => 'author',
				'returns'  => 'url',
				'args'     => [ self::avatar_size_arg() ],
				'callback' => [ static::class, 'author_profile_picture_url' ],
			]
		);
	}

	/**
	 * The author id of the context post, or 0.
	 */
	private static function author_id( Context $context ): int {
		return $context->post() ? (int) $context->post()->post_author : 0;
	}

	/**
	 * Link callback for the author tags: the author's posts page.
	 *
	 * @return string
	 */
	public static function author_link( array $args, Context $context ): string {
		$id = self::author_id( $context );
		return $id ? (string) get_author_posts_url( $id ) : '';
	}

	/**
	 * The simple author tags, by the tag name the renderer passes.
	 *
	 * @return mixed
	 */
	public static function author_field( array $args, Context $context, array $def = [] ) {
		$id = self::author_id( $context );
		if ( ! $id ) {
			return null;
		}

		switch ( $def['name'] ?? '' ) {
			case 'author_name':
				return get_the_author_meta( 'display_name', $id );
			case 'author_id':
				return $id;
			case 'author_email':
				return get_the_author_meta( 'user_email', $id );
			case 'author_bio':
				return get_the_author_meta( 'description', $id );
			case 'author_website':
				return get_the_author_meta( 'user_url', $id );
			case 'author_url':
				return get_author_posts_url( $id );
		}

		return null;
	}

	/**
	 * {author_profile_picture_url size}
	 *
	 * @return string|false|null
	 */
	public static function author_profile_picture_url( array $args, Context $context ) {
		$id = self::author_id( $context );
		return $id ? get_avatar_url( $id, [ 'size' => (int) $args['size'] ] ) : null;
	}

	// -------------------------------------------------------------------------
	// Current user
	// -------------------------------------------------------------------------

	private static function register_user_tags(): void {
		$simple = [
			'user_display_name' => [ __( 'Display Name', 'ayecode-connect' ), 'text' ],
			'user_id'           => [ __( 'User ID', 'ayecode-connect' ), 'number' ],
			'user_email'        => [ __( 'Email', 'ayecode-connect' ), 'text' ],
			'user_first_name'   => [ __( 'First Name', 'ayecode-connect' ), 'text' ],
			'user_last_name'    => [ __( 'Last Name', 'ayecode-connect' ), 'text' ],
			'user_website'      => [ __( 'Website', 'ayecode-connect' ), 'url' ],
		];

		foreach ( $simple as $name => list( $label, $returns ) ) {
			Registry::register(
				$name,
				[
					'label'    => $label,
					'group'    => 'user',
					'returns'  => $returns,
					'callback' => [ static::class, 'user_field' ],
				]
			);
		}

		Registry::register(
			'user_profile_picture_url',
			[
				'label'    => __( 'Profile Picture URL', 'ayecode-connect' ),
				'group'    => 'user',
				'returns'  => 'url',
				'args'     => [ self::avatar_size_arg() ],
				'callback' => [ static::class, 'user_profile_picture_url' ],
			]
		);
	}

	/**
	 * The simple current user tags, by the tag name the renderer passes.
	 *
	 * @return mixed
	 */
	public static function user_field( array $args, Context $context, array $def = [] ) {
		$user = $context->user();
		if ( ! $user ) {
			return null;
		}

		switch ( $def['name'] ?? '' ) {
			case 'user_display_name':
				return $user->display_name;
			case 'user_id':
				return $user->ID;
			case 'user_email':
				return $user->user_email;
			case 'user_first_name':
				return $user->first_name;
			case 'user_last_name':
				return $user->last_name;
			case 'user_website':
				return $user->user_url;
		}

		return null;
	}

	/**
	 * {user_profile_picture_url size}
	 *
	 * @return string|false|null
	 */
	public static function user_profile_picture_url( array $args, Context $context ) {
		return $context->user() ? get_avatar_url( $context->user()->ID, [ 'size' => (int) $args['size'] ] ) : null;
	}

	// -------------------------------------------------------------------------
	// Archive and term
	// -------------------------------------------------------------------------

	private static function register_archive_tags(): void {
		Registry::register(
			'archive_title',
			[
				'label'    => __( 'Archive Title', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'html',
				'callback' => [ static::class, 'archive_title' ],
			]
		);

		Registry::register(
			'archive_description',
			[
				'label'    => __( 'Archive Description', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'html',
				'callback' => [ static::class, 'archive_description' ],
			]
		);

		Registry::register(
			'archive_url',
			[
				'label'    => __( 'Archive URL', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'url',
				'callback' => [ static::class, 'archive_url' ],
			]
		);

		$simple = [
			'term_name'        => [ __( 'Term Name', 'ayecode-connect' ), 'text' ],
			'term_id'          => [ __( 'Term ID', 'ayecode-connect' ), 'number' ],
			'term_description' => [ __( 'Term Description', 'ayecode-connect' ), 'html' ],
			'term_post_count'  => [ __( 'Term Post Count', 'ayecode-connect' ), 'number' ],
		];

		foreach ( $simple as $name => list( $label, $returns ) ) {
			Registry::register(
				$name,
				[
					'label'    => $label,
					'group'    => 'archive',
					'returns'  => $returns,
					'link'     => [ static::class, 'term_link' ],
					'callback' => [ static::class, 'term_field' ],
				]
			);
		}

		Registry::register(
			'term_url',
			[
				'label'    => __( 'Term URL', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'url',
				'callback' => [ static::class, 'term_link' ],
			]
		);

		Registry::register(
			'query_results_count',
			[
				'label'    => __( 'Query Results Count', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'number',
				'callback' => [ static::class, 'query_results_count' ],
			]
		);
	}

	/**
	 * Link callback for the term tags, and the {term_url} value.
	 *
	 * @return string
	 */
	public static function term_link( array $args, Context $context ): string {
		$link = $context->term() ? get_term_link( $context->term() ) : '';
		return is_wp_error( $link ) ? '' : (string) $link;
	}

	/**
	 * {archive_title}
	 *
	 * @return string
	 */
	public static function archive_title() {
		return get_the_archive_title();
	}

	/**
	 * {archive_description}
	 *
	 * @return string
	 */
	public static function archive_description() {
		return get_the_archive_description();
	}

	/**
	 * {archive_url}
	 *
	 * @return string|null
	 */
	public static function archive_url( array $args, Context $context ) {
		if ( $context->term() ) {
			return self::term_link( $args, $context );
		}
		if ( is_post_type_archive() ) {
			return get_post_type_archive_link( get_query_var( 'post_type' ) );
		}
		if ( is_author() ) {
			return get_author_posts_url( get_queried_object_id() );
		}
		return null;
	}

	/**
	 * The simple term tags, by the tag name the renderer passes.
	 *
	 * @return mixed
	 */
	public static function term_field( array $args, Context $context, array $def = [] ) {
		$term = $context->term();
		if ( ! $term ) {
			return null;
		}

		switch ( $def['name'] ?? '' ) {
			case 'term_name':
				return $term->name;
			case 'term_id':
				return $term->term_id;
			case 'term_description':
				return term_description( $term );
			case 'term_post_count':
				return $term->count;
		}

		return null;
	}

	/**
	 * {query_results_count}
	 *
	 * @return int|null
	 */
	public static function query_results_count() {
		global $wp_query;
		return $wp_query ? (int) $wp_query->found_posts : null;
	}

	// -------------------------------------------------------------------------
	// Site
	// -------------------------------------------------------------------------

	private static function register_site_tags(): void {
		$simple = [
			'site_title'   => [ __( 'Site Title', 'ayecode-connect' ), 'text' ],
			'site_tagline' => [ __( 'Site Tagline', 'ayecode-connect' ), 'text' ],
			'site_url'     => [ __( 'Site URL', 'ayecode-connect' ), 'url' ],
			'admin_email'  => [ __( 'Admin Email', 'ayecode-connect' ), 'text' ],
			'wp_version'   => [ __( 'WordPress Version', 'ayecode-connect' ), 'text' ],
		];

		foreach ( $simple as $name => list( $label, $returns ) ) {
			Registry::register(
				$name,
				[
					'label'    => $label,
					'group'    => 'site',
					'returns'  => $returns,
					'callback' => [ static::class, 'site_field' ],
				]
			);
		}

		Registry::register(
			'current_date',
			[
				'label'     => __( 'Current Date', 'ayecode-connect' ),
				'group'     => 'site',
				'args'      => [ self::date_format_arg() ],
				'modifiers' => self::DATE_MODIFIERS,
				'callback'  => [ static::class, 'current_date' ],
			]
		);

		Registry::register(
			'current_time',
			[
				'label'     => __( 'Current Time', 'ayecode-connect' ),
				'group'     => 'site',
				'args'      => [ self::date_format_arg( true ) ],
				'modifiers' => self::DATE_MODIFIERS,
				'callback'  => [ static::class, 'current_time' ],
			]
		);
	}

	/**
	 * The simple site tags, by the tag name the renderer passes.
	 *
	 * @return mixed
	 */
	public static function site_field( array $args, Context $context, array $def = [] ) {
		switch ( $def['name'] ?? '' ) {
			case 'site_title':
				return get_bloginfo( 'name' );
			case 'site_tagline':
				return get_bloginfo( 'description' );
			case 'site_url':
				return home_url( '/' );
			case 'admin_email':
				return get_bloginfo( 'admin_email' );
			case 'wp_version':
				return get_bloginfo( 'version' );
		}

		return null;
	}

	/**
	 * {current_date format}
	 *
	 * @return string
	 */
	public static function current_date( array $args ) {
		return wp_date( '' !== $args['format'] ? $args['format'] : get_option( 'date_format' ) );
	}

	/**
	 * {current_time format}
	 *
	 * @return string
	 */
	public static function current_time( array $args ) {
		return wp_date( '' !== $args['format'] ? $args['format'] : get_option( 'time_format' ) );
	}

	// -------------------------------------------------------------------------
	// Counts
	// -------------------------------------------------------------------------

	/**
	 * Counts sit in the group of the thing they count. The ones WordPress keeps a
	 * running total for are read as they are; the ones that need a query set
	 * `cache`, so the renderer runs them once and keeps the number.
	 */
	private static function register_count_tags(): void {
		Registry::register(
			'post_count',
			[
				'label'       => __( 'Post Count', 'ayecode-connect' ),
				'group'       => 'site',
				'description' => __( 'Published posts of a post type.', 'ayecode-connect' ),
				'returns'     => 'number',
				'args'        => [ self::post_type_arg( true ) ],
				'callback'    => [ static::class, 'post_count' ],
			]
		);

		Registry::register(
			'term_count',
			[
				'label'       => __( 'Term Count', 'ayecode-connect' ),
				'group'       => 'site',
				'description' => __( 'Terms in a taxonomy, such as the number of categories.', 'ayecode-connect' ),
				'returns'     => 'number',
				'args'        => [
					[
						'key'     => 'taxonomy',
						'label'   => __( 'Taxonomy', 'ayecode-connect' ),
						'type'    => 'select',
						'source'  => 'taxonomies',
						'default' => 'category',
						'primary' => true,
					],
					[
						'key'   => 'hide_empty',
						'label' => __( 'Only terms with posts', 'ayecode-connect' ),
						'type'  => 'toggle',
					],
				],
				'cache'       => 10 * MINUTE_IN_SECONDS,
				'callback'    => [ static::class, 'term_count' ],
			]
		);

		Registry::register(
			'user_count',
			[
				'label'       => __( 'User Count', 'ayecode-connect' ),
				'group'       => 'site',
				'description' => __( 'Registered users, optionally of one role.', 'ayecode-connect' ),
				'returns'     => 'number',
				'args'        => [
					[
						'key'         => 'role',
						'label'       => __( 'Role', 'ayecode-connect' ),
						'type'        => 'select',
						'source'      => 'user_roles',
						'primary'     => true,
						'placeholder' => __( 'All users', 'ayecode-connect' ),
					],
				],
				// Counting by role reads every user's capabilities.
				'cache'       => HOUR_IN_SECONDS,
				'callback'    => [ static::class, 'user_count' ],
			]
		);

		Registry::register(
			'site_comment_count',
			[
				'label'       => __( 'Site Comment Count', 'ayecode-connect' ),
				'group'       => 'site',
				'description' => __( 'Approved comments across the site.', 'ayecode-connect' ),
				'returns'     => 'number',
				'callback'    => [ static::class, 'site_comment_count' ],
			]
		);

		Registry::register(
			'author_post_count',
			[
				'label'    => __( 'Author Post Count', 'ayecode-connect' ),
				'group'    => 'author',
				'returns'  => 'number',
				'args'     => [ self::post_type_arg() ],
				'link'     => [ static::class, 'author_link' ],
				'cache'    => 0,
				'vary'     => [ static::class, 'vary_author' ],
				'callback' => [ static::class, 'author_post_count' ],
			]
		);

		Registry::register(
			'user_post_count',
			[
				'label'    => __( 'User Post Count', 'ayecode-connect' ),
				'group'    => 'user',
				'returns'  => 'number',
				'args'     => [ self::post_type_arg() ],
				'cache'    => 0,
				'vary'     => [ 'user' ],
				'callback' => [ static::class, 'user_post_count' ],
			]
		);
	}

	private static function post_type_arg( bool $primary = false ): array {
		return [
			'key'     => 'post_type',
			'label'   => __( 'Post Type', 'ayecode-connect' ),
			'type'    => 'select',
			'source'  => 'post_types',
			'default' => 'post',
			'primary' => $primary,
		];
	}

	/**
	 * Whether a post type's numbers may be printed: only one visitors can view.
	 */
	private static function is_countable_post_type( string $post_type ): bool {
		$object = get_post_type_object( $post_type );

		return $object && is_post_type_viewable( $object );
	}

	/**
	 * {post_count:post_type}
	 *
	 * @return int|null
	 */
	public static function post_count( array $args ) {
		if ( ! self::is_countable_post_type( (string) $args['post_type'] ) ) {
			return null;
		}

		$counts = wp_count_posts( $args['post_type'] );
		$status = 'attachment' === $args['post_type'] ? 'inherit' : 'publish';

		return (int) ( $counts->$status ?? 0 );
	}

	/**
	 * {term_count:taxonomy @hide_empty}
	 *
	 * @return int|null
	 */
	public static function term_count( array $args ) {
		$taxonomy = get_taxonomy( (string) $args['taxonomy'] );
		if ( ! $taxonomy || ! is_taxonomy_viewable( $taxonomy ) ) {
			return null;
		}

		$count = wp_count_terms(
			[
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => ! empty( $args['hide_empty'] ),
			]
		);

		return is_wp_error( $count ) ? null : (int) $count;
	}

	/**
	 * {user_count:role}
	 *
	 * @return int|null
	 */
	public static function user_count( array $args ) {
		$role = (string) $args['role'];

		if ( '' === $role ) {
			return (int) get_user_count();
		}

		if ( ! wp_roles()->is_role( $role ) ) {
			return null;
		}

		$counts = count_users();

		return (int) ( $counts['avail_roles'][ $role ] ?? 0 );
	}

	/**
	 * {site_comment_count}
	 *
	 * @return int
	 */
	public static function site_comment_count() {
		return (int) wp_count_comments()->approved;
	}

	/**
	 * Cache key part for a tag about the post author, so every post by one
	 * author shares a value.
	 */
	public static function vary_author( array $args, Context $context ): string {
		return 'a' . self::author_id( $context );
	}

	/**
	 * {author_post_count @post_type}
	 *
	 * @return int|null
	 */
	public static function author_post_count( array $args, Context $context ) {
		$author_id = self::author_id( $context );

		if ( ! $author_id || ! self::is_countable_post_type( (string) $args['post_type'] ) ) {
			return null;
		}

		return (int) count_user_posts( $author_id, $args['post_type'], true );
	}

	/**
	 * {user_post_count @post_type}
	 *
	 * @return int|null
	 */
	public static function user_post_count( array $args, Context $context ) {
		if ( ! $context->user() || ! self::is_countable_post_type( (string) $args['post_type'] ) ) {
			return null;
		}

		return (int) count_user_posts( $context->user()->ID, $args['post_type'], true );
	}

	// -------------------------------------------------------------------------
	// Shared args
	// -------------------------------------------------------------------------

	/**
	 * A date or time format arg, with presets labeled by what they print today.
	 */
	private static function date_format_arg( bool $time = false ): array {
		return [
			'key'     => 'format',
			'label'   => $time ? __( 'Time format', 'ayecode-connect' ) : __( 'Date format', 'ayecode-connect' ),
			'type'    => 'date_format',
			// Built only for the editor, not on every frontend render.
			'options' => [ static::class, $time ? 'time_format_options' : 'date_format_options' ],
			'primary' => true,
			'help'    => __( 'Any PHP date format, for example "D j M" or "g:i A".', 'ayecode-connect' ),
		];
	}

	/**
	 * Date format presets, labeled by what they print today.
	 *
	 * @return array<string, string>
	 */
	public static function date_format_options(): array {
		return self::format_options( false, [ 'F j, Y', 'j F Y', 'Y-m-d', 'm/d/Y', 'd/m/Y', 'D, M j', 'l', 'F', 'Y' ] );
	}

	/**
	 * Time format presets, labeled by what they print now.
	 *
	 * @return array<string, string>
	 */
	public static function time_format_options(): array {
		return self::format_options( true, [ 'g:i a', 'g:i A', 'H:i', 'H:i:s' ] );
	}

	private static function format_options( bool $time, array $formats ): array {
		$options = [
			'' => sprintf(
				/* translators: %s: today's date or time in the site's format. */
				__( 'Site default (%s)', 'ayecode-connect' ),
				wp_date( get_option( $time ? 'time_format' : 'date_format' ) )
			),
		];
		foreach ( $formats as $format ) {
			$options[ $format ] = wp_date( $format );
		}
		return $options;
	}

	private static function avatar_size_arg(): array {
		return [
			'key'     => 'size',
			'label'   => __( 'Size in pixels', 'ayecode-connect' ),
			'type'    => 'number',
			'default' => 96,
			'primary' => true,
		];
	}

	// -------------------------------------------------------------------------
	// Option sources
	// -------------------------------------------------------------------------

	/**
	 * Meta keys in use on a post type, protected keys excluded.
	 *
	 * @param array $params post_type (optional).
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_post_meta_keys( array $params ): array {
		global $wpdb;

		$post_type = isset( $params['post_type'] ) ? sanitize_key( $params['post_type'] ) : '';

		if ( '' !== $post_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Editor only lookup with no API equivalent.
			$keys = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s ORDER BY pm.meta_key LIMIT %d",
					$post_type,
					500
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Editor only lookup with no API equivalent.
			$keys = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT meta_key FROM {$wpdb->postmeta} ORDER BY meta_key LIMIT %d",
					500
				)
			);
		}

		return self::allowed_key_options( $keys, 'post' );
	}

	/**
	 * User meta keys a tag may print.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_user_meta_keys(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Editor only lookup with no API equivalent.
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} ORDER BY meta_key LIMIT %d", 500 ) );

		return self::allowed_key_options( array_merge( self::USER_META_ALLOWLIST, $keys ), 'user' );
	}

	/**
	 * Term meta keys in use, protected keys excluded.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_term_meta_keys(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Editor only lookup with no API equivalent.
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->termmeta} ORDER BY meta_key LIMIT %d", 500 ) );

		return self::allowed_key_options( $keys, 'term' );
	}

	/**
	 * @param string[] $keys        Candidate meta keys.
	 * @param string   $object_type post, user or term.
	 * @return array<int, array{value: string, label: string}>
	 */
	private static function allowed_key_options( array $keys, string $object_type ): array {
		$out = [];
		foreach ( array_unique( $keys ) as $key ) {
			if ( self::is_meta_key_allowed( $key, $object_type ) ) {
				$out[] = [
					'value' => $key,
					'label' => $key,
				];
			}
		}

		return $out;
	}

	/**
	 * Public taxonomies, optionally limited to one post type.
	 *
	 * @param array $params post_type (optional).
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_taxonomies( array $params ): array {
		$post_type  = isset( $params['post_type'] ) ? sanitize_key( $params['post_type'] ) : '';
		$taxonomies = '' !== $post_type ? get_object_taxonomies( $post_type, 'objects' ) : get_taxonomies( [], 'objects' );

		$out = [];
		foreach ( $taxonomies as $taxonomy ) {
			if ( is_taxonomy_viewable( $taxonomy ) ) {
				$out[] = [
					'value' => $taxonomy->name,
					'label' => $taxonomy->labels->singular_name,
				];
			}
		}

		return $out;
	}

	/**
	 * Post types visitors can view.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_post_types(): array {
		$out = [];
		foreach ( get_post_types( [], 'objects' ) as $post_type ) {
			if ( is_post_type_viewable( $post_type ) ) {
				$out[] = [
					'value' => $post_type->name,
					'label' => $post_type->labels->name,
				];
			}
		}

		return $out;
	}

	/**
	 * User roles.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_user_roles(): array {
		$out = [];
		foreach ( wp_roles()->get_names() as $role => $name ) {
			$out[] = [
				'value' => $role,
				'label' => translate_user_role( $name ),
			];
		}

		return $out;
	}

	/**
	 * Registered image sizes plus full.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_image_sizes(): array {
		$out = [
			[
				'value' => 'full',
				'label' => __( 'Full size', 'ayecode-connect' ),
			],
		];

		foreach ( wp_get_registered_image_subsizes() as $name => $size ) {
			$out[] = [
				'value' => $name,
				'label' => sprintf( '%s (%d x %d)', $name, $size['width'], $size['height'] ),
			];
		}

		return $out;
	}
}
