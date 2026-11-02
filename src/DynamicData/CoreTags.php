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
	const DATE_MODIFIERS = array( 'link', 'new_tab', 'fallback' );

	/**
	 * User meta keys a tag may print without code opting more in. User meta is an
	 * allowlist rather than a protected-key check because plugins keep personal data
	 * there under unprotected keys, such as WooCommerce's billing_phone.
	 */
	const USER_META_ALLOWLIST = array( 'first_name', 'last_name', 'nickname', 'description' );

	/**
	 * Post IDs whose content is being rendered by {post_content}, so a post that
	 * contains the tag does not render itself forever.
	 *
	 * @var array<int, true>
	 */
	private static array $rendering_content = array();

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

		Registry::register(
			'request_parameter',
			array(
				'label'    => __( 'Request Parameter', 'ayecode-connect' ),
				'group'    => 'request',
				'args'     => array(
					array(
						'key'         => 'key',
						'label'       => __( 'Parameter name', 'ayecode-connect' ),
						'primary'     => true,
						'required'    => true,
						'placeholder' => 'ref',
					),
				),
				'callback' => static function ( array $args ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only display of a query var.
					return isset( $_REQUEST[ $args['key'] ] ) && is_scalar( $_REQUEST[ $args['key'] ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $args['key'] ] ) ) : null;
				},
			)
		);

		Registry::register_source( 'post_meta_keys', array( static::class, 'source_post_meta_keys' ) );
		Registry::register_source( 'taxonomies', array( static::class, 'source_taxonomies' ) );
		Registry::register_source( 'image_sizes', array( static::class, 'source_image_sizes' ) );
		Registry::register_source( 'user_meta_keys', array( static::class, 'source_user_meta_keys' ) );
		Registry::register_source( 'term_meta_keys', array( static::class, 'source_term_meta_keys' ) );
	}

	// -------------------------------------------------------------------------
	// Post
	// -------------------------------------------------------------------------

	private static function register_post_tags(): void {
		$link = static fn( array $args, Context $c ) => $c->post() ? get_permalink( $c->post() ) : '';

		$simple = array(
			'post_title'    => array( __( 'Post Title', 'ayecode-connect' ), 'text', static fn( \WP_Post $p ) => get_the_title( $p ) ),
			'post_id'       => array( __( 'Post ID', 'ayecode-connect' ), 'number', static fn( \WP_Post $p ) => $p->ID ),
			'post_url'      => array( __( 'Post URL', 'ayecode-connect' ), 'url', static fn( \WP_Post $p ) => get_permalink( $p ) ),
			'post_slug'     => array( __( 'Post Slug', 'ayecode-connect' ), 'text', static fn( \WP_Post $p ) => $p->post_name ),
			'post_excerpt'  => array( __( 'Post Excerpt', 'ayecode-connect' ), 'text', static fn( \WP_Post $p ) => get_the_excerpt( $p ) ),
			'post_status'   => array( __( 'Post Status', 'ayecode-connect' ), 'text', static fn( \WP_Post $p ) => get_post_status( $p ) ),
			'post_type'     => array( __( 'Post Type', 'ayecode-connect' ), 'text', static fn( \WP_Post $p ) => get_post_type( $p ) ),
			'comment_count' => array( __( 'Comment Count', 'ayecode-connect' ), 'number', static fn( \WP_Post $p ) => get_comments_number( $p ) ),
			'comments_link' => array( __( 'Comments Link', 'ayecode-connect' ), 'url', static fn( \WP_Post $p ) => get_comments_link( $p ) ),
		);

		foreach ( $simple as $name => list( $label, $returns, $get ) ) {
			Registry::register(
				$name,
				array(
					'label'    => $label,
					'group'    => 'post',
					'returns'  => $returns,
					'link'     => $link,
					'callback' => static fn( array $args, Context $c ) => $c->post() ? $get( $c->post() ) : null,
				)
			);
		}

		Registry::register(
			'post_content',
			array(
				'label'    => __( 'Post Content', 'ayecode-connect' ),
				'group'    => 'post',
				'returns'  => 'html',
				'callback' => array( static::class, 'post_content' ),
			)
		);

		Registry::register(
			'post_date',
			array(
				'label'     => __( 'Post Date', 'ayecode-connect' ),
				'group'     => 'post',
				'args'      => array( self::date_format_arg() ),
				'modifiers' => self::DATE_MODIFIERS,
				'link'      => $link,
				'callback'  => static fn( array $args, Context $c ) => $c->post() ? get_the_date( $args['format'], $c->post() ) : null,
			)
		);

		Registry::register(
			'post_modified_date',
			array(
				'label'     => __( 'Post Modified Date', 'ayecode-connect' ),
				'group'     => 'post',
				'args'      => array( self::date_format_arg() ),
				'modifiers' => self::DATE_MODIFIERS,
				'link'      => $link,
				'callback'  => static fn( array $args, Context $c ) => $c->post() ? get_the_modified_date( $args['format'], $c->post() ) : null,
			)
		);

		Registry::register(
			'post_terms',
			array(
				'label'    => __( 'Post Terms', 'ayecode-connect' ),
				'group'    => 'post',
				'args'     => array(
					array(
						'key'     => 'taxonomy',
						'label'   => __( 'Taxonomy', 'ayecode-connect' ),
						'type'    => 'select',
						'source'  => 'taxonomies',
						'default' => 'category',
						'primary' => true,
					),
					array(
						'key'     => 'separator',
						'label'   => __( 'Separator', 'ayecode-connect' ),
						'default' => ', ',
					),
				),
				'callback' => array( static::class, 'post_terms' ),
			)
		);

		Registry::register(
			'post_meta',
			array(
				'label'       => __( 'Post Meta', 'ayecode-connect' ),
				'group'       => 'post',
				'description' => __( 'A custom field saved on the post. Protected keys (starting with an underscore) are not available.', 'ayecode-connect' ),
				'args'        => array(
					array(
						'key'      => 'key',
						'label'    => __( 'Meta key', 'ayecode-connect' ),
						'type'     => 'combobox',
						'source'   => 'post_meta_keys',
						'primary'  => true,
						'required' => true,
					),
				),
				'link'        => $link,
				'callback'    => array( static::class, 'post_meta' ),
			)
		);
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
		$key_arg = static fn( string $source ) => array(
			'key'      => 'key',
			'label'    => __( 'Meta key', 'ayecode-connect' ),
			'type'     => 'combobox',
			'source'   => $source,
			'primary'  => true,
			'required' => true,
		);

		Registry::register(
			'author_meta',
			array(
				'label'       => __( 'Author Meta', 'ayecode-connect' ),
				'group'       => 'author',
				'description' => __( 'A field saved on the post author\'s profile. Only first name, last name, nickname and bio unless more keys are allowed in code.', 'ayecode-connect' ),
				'args'        => array( $key_arg( 'user_meta_keys' ) ),
				'link'        => static fn( array $args, Context $c ) => $c->post() ? get_author_posts_url( (int) $c->post()->post_author ) : '',
				'callback'    => static fn( array $args, Context $c ) => $c->post() ? self::meta_value( 'user', (int) $c->post()->post_author, (string) $args['key'] ) : null,
			)
		);

		Registry::register(
			'user_meta',
			array(
				'label'       => __( 'User Meta', 'ayecode-connect' ),
				'group'       => 'user',
				'description' => __( 'A field saved on the logged in visitor\'s profile. Only first name, last name, nickname and bio unless more keys are allowed in code.', 'ayecode-connect' ),
				'args'        => array( $key_arg( 'user_meta_keys' ) ),
				'callback'    => static fn( array $args, Context $c ) => $c->user() ? self::meta_value( 'user', $c->user()->ID, (string) $args['key'] ) : null,
			)
		);

		Registry::register(
			'term_meta',
			array(
				'label'       => __( 'Term Meta', 'ayecode-connect' ),
				'group'       => 'archive',
				'description' => __( 'A field saved on the term being viewed. Protected keys (starting with an underscore) are not available.', 'ayecode-connect' ),
				'args'        => array( $key_arg( 'term_meta_keys' ) ),
				'link'        => static function ( array $args, Context $c ) {
					$link = $c->term() ? get_term_link( $c->term() ) : '';
					return is_wp_error( $link ) ? '' : $link;
				},
				'callback'    => static fn( array $args, Context $c ) => $c->term() ? self::meta_value( 'term', $c->term()->term_id, (string) $args['key'] ) : null,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Featured image
	// -------------------------------------------------------------------------

	private static function register_featured_image_tags(): void {
		$thumb = static fn( Context $c ) => $c->post() && has_post_thumbnail( $c->post() ) ? (int) get_post_thumbnail_id( $c->post() ) : 0;

		Registry::register(
			'featured_image_url',
			array(
				'label'    => __( 'Image URL', 'ayecode-connect' ),
				'group'    => 'featured_image',
				'returns'  => 'url',
				'args'     => array(
					array(
						'key'     => 'size',
						'label'   => __( 'Size', 'ayecode-connect' ),
						'type'    => 'select',
						'source'  => 'image_sizes',
						'default' => 'full',
						'primary' => true,
					),
				),
				'callback' => static function ( array $args, Context $c ) use ( $thumb ) {
					$id = $thumb( $c );
					return $id ? wp_get_attachment_image_url( $id, $args['size'] ) : null;
				},
			)
		);

		$simple = array(
			'featured_image_id'      => array( __( 'Image ID', 'ayecode-connect' ), 'number', static fn( int $id ) => $id ),
			'featured_image_alt'     => array( __( 'Alt Text', 'ayecode-connect' ), 'text', static fn( int $id ) => get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			'featured_image_title'   => array( __( 'Title', 'ayecode-connect' ), 'text', static fn( int $id ) => get_the_title( $id ) ),
			'featured_image_caption' => array( __( 'Caption', 'ayecode-connect' ), 'text', static fn( int $id ) => wp_get_attachment_caption( $id ) ),
		);

		foreach ( $simple as $name => list( $label, $returns, $get ) ) {
			Registry::register(
				$name,
				array(
					'label'    => $label,
					'group'    => 'featured_image',
					'returns'  => $returns,
					'callback' => static function ( array $args, Context $c ) use ( $thumb, $get ) {
						$id = $thumb( $c );
						return $id ? $get( $id ) : null;
					},
				)
			);
		}
	}

	// -------------------------------------------------------------------------
	// Post author
	// -------------------------------------------------------------------------

	private static function register_author_tags(): void {
		$author = static fn( Context $c ) => $c->post() ? (int) $c->post()->post_author : 0;
		$link   = static fn( array $args, Context $c ) => $author( $c ) ? get_author_posts_url( $author( $c ) ) : '';

		$simple = array(
			'author_name'    => array( __( 'Author Name', 'ayecode-connect' ), 'text', static fn( int $id ) => get_the_author_meta( 'display_name', $id ) ),
			'author_id'      => array( __( 'Author ID', 'ayecode-connect' ), 'number', static fn( int $id ) => $id ),
			'author_email'   => array( __( 'Author Email', 'ayecode-connect' ), 'text', static fn( int $id ) => get_the_author_meta( 'user_email', $id ) ),
			'author_bio'     => array( __( 'Author Bio', 'ayecode-connect' ), 'text', static fn( int $id ) => get_the_author_meta( 'description', $id ) ),
			'author_website' => array( __( 'Author Website', 'ayecode-connect' ), 'url', static fn( int $id ) => get_the_author_meta( 'user_url', $id ) ),
			'author_url'     => array( __( 'Author Posts URL', 'ayecode-connect' ), 'url', static fn( int $id ) => get_author_posts_url( $id ) ),
		);

		foreach ( $simple as $name => list( $label, $returns, $get ) ) {
			Registry::register(
				$name,
				array(
					'label'    => $label,
					'group'    => 'author',
					'returns'  => $returns,
					'link'     => $link,
					'callback' => static fn( array $args, Context $c ) => $author( $c ) ? $get( $author( $c ) ) : null,
				)
			);
		}

		Registry::register(
			'author_profile_picture_url',
			array(
				'label'    => __( 'Author Profile Picture URL', 'ayecode-connect' ),
				'group'    => 'author',
				'returns'  => 'url',
				'args'     => array( self::avatar_size_arg() ),
				'callback' => static fn( array $args, Context $c ) => $author( $c ) ? get_avatar_url( $author( $c ), array( 'size' => (int) $args['size'] ) ) : null,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Current user
	// -------------------------------------------------------------------------

	private static function register_user_tags(): void {
		$simple = array(
			'user_display_name' => array( __( 'Display Name', 'ayecode-connect' ), 'text', static fn( \WP_User $u ) => $u->display_name ),
			'user_id'           => array( __( 'User ID', 'ayecode-connect' ), 'number', static fn( \WP_User $u ) => $u->ID ),
			'user_email'        => array( __( 'Email', 'ayecode-connect' ), 'text', static fn( \WP_User $u ) => $u->user_email ),
			'user_first_name'   => array( __( 'First Name', 'ayecode-connect' ), 'text', static fn( \WP_User $u ) => $u->first_name ),
			'user_last_name'    => array( __( 'Last Name', 'ayecode-connect' ), 'text', static fn( \WP_User $u ) => $u->last_name ),
			'user_website'      => array( __( 'Website', 'ayecode-connect' ), 'url', static fn( \WP_User $u ) => $u->user_url ),
		);

		foreach ( $simple as $name => list( $label, $returns, $get ) ) {
			Registry::register(
				$name,
				array(
					'label'    => $label,
					'group'    => 'user',
					'returns'  => $returns,
					'callback' => static fn( array $args, Context $c ) => $c->user() ? $get( $c->user() ) : null,
				)
			);
		}

		Registry::register(
			'user_profile_picture_url',
			array(
				'label'    => __( 'Profile Picture URL', 'ayecode-connect' ),
				'group'    => 'user',
				'returns'  => 'url',
				'args'     => array( self::avatar_size_arg() ),
				'callback' => static fn( array $args, Context $c ) => $c->user() ? get_avatar_url( $c->user()->ID, array( 'size' => (int) $args['size'] ) ) : null,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Archive and term
	// -------------------------------------------------------------------------

	private static function register_archive_tags(): void {
		$term_link = static function ( array $args, Context $c ) {
			$link = $c->term() ? get_term_link( $c->term() ) : '';
			return is_wp_error( $link ) ? '' : $link;
		};

		Registry::register(
			'archive_title',
			array(
				'label'    => __( 'Archive Title', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'html',
				'callback' => static fn() => get_the_archive_title(),
			)
		);

		Registry::register(
			'archive_description',
			array(
				'label'    => __( 'Archive Description', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'html',
				'callback' => static fn() => get_the_archive_description(),
			)
		);

		Registry::register(
			'archive_url',
			array(
				'label'    => __( 'Archive URL', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'url',
				'callback' => static function ( array $args, Context $c ) use ( $term_link ) {
					if ( $c->term() ) {
						return $term_link( $args, $c );
					}
					if ( is_post_type_archive() ) {
						return get_post_type_archive_link( get_query_var( 'post_type' ) );
					}
					if ( is_author() ) {
						return get_author_posts_url( get_queried_object_id() );
					}
					return null;
				},
			)
		);

		$simple = array(
			'term_name'        => array( __( 'Term Name', 'ayecode-connect' ), 'text', static fn( \WP_Term $t ) => $t->name ),
			'term_id'          => array( __( 'Term ID', 'ayecode-connect' ), 'number', static fn( \WP_Term $t ) => $t->term_id ),
			'term_description' => array( __( 'Term Description', 'ayecode-connect' ), 'html', static fn( \WP_Term $t ) => term_description( $t ) ),
			'term_post_count'  => array( __( 'Term Post Count', 'ayecode-connect' ), 'number', static fn( \WP_Term $t ) => $t->count ),
		);

		foreach ( $simple as $name => list( $label, $returns, $get ) ) {
			Registry::register(
				$name,
				array(
					'label'    => $label,
					'group'    => 'archive',
					'returns'  => $returns,
					'link'     => $term_link,
					'callback' => static fn( array $args, Context $c ) => $c->term() ? $get( $c->term() ) : null,
				)
			);
		}

		Registry::register(
			'term_url',
			array(
				'label'    => __( 'Term URL', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'url',
				'callback' => $term_link,
			)
		);

		Registry::register(
			'query_results_count',
			array(
				'label'    => __( 'Query Results Count', 'ayecode-connect' ),
				'group'    => 'archive',
				'returns'  => 'number',
				'callback' => static function () {
					global $wp_query;
					return $wp_query ? (int) $wp_query->found_posts : null;
				},
			)
		);
	}

	// -------------------------------------------------------------------------
	// Site
	// -------------------------------------------------------------------------

	private static function register_site_tags(): void {
		$simple = array(
			'site_title'   => array( __( 'Site Title', 'ayecode-connect' ), 'text', static fn() => get_bloginfo( 'name' ) ),
			'site_tagline' => array( __( 'Site Tagline', 'ayecode-connect' ), 'text', static fn() => get_bloginfo( 'description' ) ),
			'site_url'     => array( __( 'Site URL', 'ayecode-connect' ), 'url', static fn() => home_url( '/' ) ),
			'admin_email'  => array( __( 'Admin Email', 'ayecode-connect' ), 'text', static fn() => get_bloginfo( 'admin_email' ) ),
			'wp_version'   => array( __( 'WordPress Version', 'ayecode-connect' ), 'text', static fn() => get_bloginfo( 'version' ) ),
		);

		foreach ( $simple as $name => list( $label, $returns, $get ) ) {
			Registry::register(
				$name,
				array(
					'label'    => $label,
					'group'    => 'site',
					'returns'  => $returns,
					'callback' => $get,
				)
			);
		}

		Registry::register(
			'current_date',
			array(
				'label'     => __( 'Current Date', 'ayecode-connect' ),
				'group'     => 'site',
				'args'      => array( self::date_format_arg() ),
				'modifiers' => self::DATE_MODIFIERS,
				'callback'  => static fn( array $args ) => wp_date( '' !== $args['format'] ? $args['format'] : get_option( 'date_format' ) ),
			)
		);

		Registry::register(
			'current_time',
			array(
				'label'     => __( 'Current Time', 'ayecode-connect' ),
				'group'     => 'site',
				'args'      => array( self::date_format_arg( true ) ),
				'modifiers' => self::DATE_MODIFIERS,
				'callback'  => static fn( array $args ) => wp_date( '' !== $args['format'] ? $args['format'] : get_option( 'time_format' ) ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Shared args
	// -------------------------------------------------------------------------

	/**
	 * A date or time format arg, with presets labeled by what they print today.
	 */
	private static function date_format_arg( bool $time = false ): array {
		$formats = $time
			? array( 'g:i a', 'g:i A', 'H:i', 'H:i:s' )
			: array( 'F j, Y', 'j F Y', 'Y-m-d', 'm/d/Y', 'd/m/Y', 'D, M j', 'l', 'F', 'Y' );

		// Built only for the editor, not on every frontend render.
		$options = static function () use ( $time, $formats ) {
			$options = array(
				'' => sprintf(
					/* translators: %s: today's date or time in the site's format. */
					__( 'Site default (%s)', 'ayecode-connect' ),
					wp_date( get_option( $time ? 'time_format' : 'date_format' ) )
				),
			);
			foreach ( $formats as $format ) {
				$options[ $format ] = wp_date( $format );
			}
			return $options;
		};

		return array(
			'key'     => 'format',
			'label'   => $time ? __( 'Time format', 'ayecode-connect' ) : __( 'Date format', 'ayecode-connect' ),
			'type'    => 'date_format',
			'options' => $options,
			'primary' => true,
			'help'    => __( 'Any PHP date format, for example "D j M" or "g:i A".', 'ayecode-connect' ),
		);
	}

	private static function avatar_size_arg(): array {
		return array(
			'key'     => 'size',
			'label'   => __( 'Size in pixels', 'ayecode-connect' ),
			'type'    => 'number',
			'default' => 96,
			'primary' => true,
		);
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
		$out = array();
		foreach ( array_unique( $keys ) as $key ) {
			if ( self::is_meta_key_allowed( $key, $object_type ) ) {
				$out[] = array(
					'value' => $key,
					'label' => $key,
				);
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
		$taxonomies = '' !== $post_type ? get_object_taxonomies( $post_type, 'objects' ) : get_taxonomies( array(), 'objects' );

		$out = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( is_taxonomy_viewable( $taxonomy ) ) {
				$out[] = array(
					'value' => $taxonomy->name,
					'label' => $taxonomy->labels->singular_name,
				);
			}
		}

		return $out;
	}

	/**
	 * Registered image sizes plus full.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function source_image_sizes(): array {
		$out = array(
			array(
				'value' => 'full',
				'label' => __( 'Full size', 'ayecode-connect' ),
			),
		);

		foreach ( wp_get_registered_image_subsizes() as $name => $size ) {
			$out[] = array(
				'value' => $name,
				'label' => sprintf( '%s (%d x %d)', $name, $size['width'], $size['height'] ),
			);
		}

		return $out;
	}
}
