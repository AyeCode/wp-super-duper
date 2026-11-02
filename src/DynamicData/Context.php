<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * What a tag resolves against: the post, the viewing user and the queried object.
 *
 * Each is looked up on first use, so a text with only {site_title} in it never
 * touches the post or user. Pass a post ID to resolve against a specific post,
 * as the editor preview does.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Context {

	private ?int $post_id;

	/** @var \WP_Post|null|false False until looked up. */
	private $post = false;

	/** @var \WP_User|null|false False until looked up. */
	private $user = false;

	/** @var object|null|false False until looked up. */
	private $queried = false;

	/**
	 * @param int|null $post_id Post to resolve against, or null for the global post.
	 */
	public function __construct( ?int $post_id = null ) {
		$this->post_id = $post_id;
	}

	public function post(): ?\WP_Post {
		if ( false === $this->post ) {
			$post       = get_post( $this->post_id );
			$this->post = $post instanceof \WP_Post ? $post : null;
		}

		return $this->post;
	}

	/**
	 * The logged in user, or null for a visitor.
	 */
	public function user(): ?\WP_User {
		if ( false === $this->user ) {
			$user       = wp_get_current_user();
			$this->user = $user->exists() ? $user : null;
		}

		return $this->user;
	}

	public function queried_object(): ?object {
		if ( false === $this->queried ) {
			$this->queried = get_queried_object();
		}

		return $this->queried;
	}

	/**
	 * The queried term on a taxonomy archive, otherwise null.
	 */
	public function term(): ?\WP_Term {
		$object = $this->queried_object();

		return $object instanceof \WP_Term ? $object : null;
	}
}
