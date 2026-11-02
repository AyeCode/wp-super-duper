<?php
/**
 * User and term meta tags, and the allowlist that guards user meta.
 */

use AyeCode\SuperDuper\DynamicData\Context;
use AyeCode\SuperDuper\DynamicData\CoreTags;
use AyeCode\SuperDuper\DynamicData\Renderer;

$author_id = wp_insert_user(
	array(
		'user_login' => 'sd_dd_author_' . wp_rand(),
		'user_pass'  => wp_generate_password(),
		'first_name' => 'Ada',
		'role'       => 'author',
	)
);
update_user_meta( $author_id, 'billing_phone', '555-0100' );
update_user_meta( $author_id, 'favourite', 'tea' );

$post_id = wp_insert_post(
	array(
		'post_title'  => 'Meta test',
		'post_status' => 'publish',
		'post_author' => $author_id,
	)
);

$term    = wp_insert_term( 'SD DD term ' . wp_rand(), 'category' );
$term_id = (int) $term['term_id'];
update_term_meta( $term_id, 'colour', 'teal' );
update_term_meta( $term_id, '_private', 'hidden' );

$ctx    = new Context( $post_id );
$render = static fn( string $text ) => Renderer::render( $text, $ctx );

SD_DD_Test::group( 'meta/author' );

SD_DD_Test::same( 'allowlisted key', 'Ada', $render( '{author_meta:first_name}' ) );
SD_DD_Test::same( 'unprotected personal key refused', 'n/a', $render( "{author_meta:billing_phone @fallback:'n/a'}" ) );
SD_DD_Test::same( 'other keys refused by default', '', $render( '{author_meta:favourite}' ) );

add_filter( 'sd_dynamic_data_meta_key_allowed', $allow = static fn( $ok, $key, $type ) => 'user' === $type && 'favourite' === $key ? true : $ok, 10, 3 );
SD_DD_Test::same( 'key opted in by filter', 'tea', $render( '{author_meta:favourite}' ) );
SD_DD_Test::same( 'source lists opted in key', true, in_array( 'favourite', array_column( CoreTags::source_user_meta_keys(), 'value' ), true ) );
remove_filter( 'sd_dynamic_data_meta_key_allowed', $allow, 10 );

$keys = array_column( CoreTags::source_user_meta_keys(), 'value' );
SD_DD_Test::same( 'source lists allowlist', array( 'first_name', 'last_name', 'nickname', 'description' ), $keys );

SD_DD_Test::group( 'meta/user' );

wp_set_current_user( $author_id );
SD_DD_Test::same( 'current user allowlisted key', 'Ada', Renderer::render( '{user_meta:first_name}', new Context() ) );
SD_DD_Test::same( 'current user personal key refused', '', Renderer::render( '{user_meta:billing_phone}', new Context() ) );
wp_set_current_user( 0 );
SD_DD_Test::same( 'visitor gets fallback', 'Guest', Renderer::render( "{user_meta:first_name @fallback:Guest}", new Context() ) );

SD_DD_Test::group( 'meta/term' );

// Context reads the term from the main query, so point the query at it.
$GLOBALS['wp_query'] = new WP_Query( array( 'cat' => $term_id ) );
$GLOBALS['wp_query']->queried_object    = get_term( $term_id );
$GLOBALS['wp_query']->queried_object_id = $term_id;
$term_ctx = new Context();

SD_DD_Test::same( 'term meta', 'teal', Renderer::render( '{term_meta:colour}', $term_ctx ) );
SD_DD_Test::same( 'protected term meta refused', '', Renderer::render( '{term_meta:_private}', $term_ctx ) );
SD_DD_Test::same( 'term meta link', '<a href="' . esc_url( get_term_link( $term_id ) ) . '">teal</a>', Renderer::render( '{term_meta:colour @link}', $term_ctx ) );
SD_DD_Test::same( 'source lists term key, not protected one', array( true, false ), array( in_array( 'colour', array_column( CoreTags::source_term_meta_keys(), 'value' ), true ), in_array( '_private', array_column( CoreTags::source_term_meta_keys(), 'value' ), true ) ) );

$GLOBALS['wp_query'] = new WP_Query();

wp_delete_post( $post_id, true );
wp_delete_term( $term_id, 'category' );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $author_id );
