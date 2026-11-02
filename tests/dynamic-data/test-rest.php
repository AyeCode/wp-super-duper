<?php
/**
 * REST endpoints: permissions and the preview render.
 */

$admin   = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$post_id = wp_insert_post(
	array(
		'post_title'  => 'Preview & test',
		'post_status' => 'draft',
		'post_author' => $admin->ID,
	)
);
$contrib = wp_insert_user(
	array(
		'user_login' => 'sd_dd_contrib_' . wp_rand(),
		'user_pass'  => wp_generate_password(),
		'role'       => 'contributor',
	)
);

$preview = static function ( int $user_id, array $body ) {
	wp_set_current_user( $user_id );
	$request = new WP_REST_Request( 'POST', '/ayecode/v3/dynamic-data/preview' );
	$request->set_body_params( $body );
	return rest_do_request( $request );
};

SD_DD_Test::group( 'rest/preview' );

$res = $preview( $admin->ID, array( 'text' => '{post_title} {post_date:Y} {nope}', 'post_id' => $post_id ) );
SD_DD_Test::same( 'renders against the post', 'Preview &amp; test ' . get_the_date( 'Y', $post_id ) . ' {nope}', $res->get_data()['html'] ?? null );

$res = $preview( $admin->ID, array( 'text' => "{post_title @fallback:'none'}" ) );
SD_DD_Test::same( 'no post uses the fallback', 'none', $res->get_data()['html'] ?? null );

SD_DD_Test::same( 'contributor cannot preview a post they cannot edit', 403, $preview( $contrib, array( 'text' => '{post_title}', 'post_id' => $post_id ) )->get_status() );
SD_DD_Test::same( 'contributor can preview without a post', 200, $preview( $contrib, array( 'text' => '{site_title}' ) )->get_status() );
SD_DD_Test::same( 'visitor cannot preview', 401, $preview( 0, array( 'text' => '{site_title}' ) )->get_status() );

SD_DD_Test::group( 'rest/schema' );

wp_set_current_user( 0 );
SD_DD_Test::same( 'visitor cannot read the schema', 401, rest_do_request( new WP_REST_Request( 'GET', '/ayecode/v3/dynamic-data' ) )->get_status() );
wp_set_current_user( $admin->ID );
SD_DD_Test::same( 'unknown source is a 404', 404, rest_do_request( new WP_REST_Request( 'GET', '/ayecode/v3/dynamic-data/options/nope' ) )->get_status() );

wp_set_current_user( 0 );
wp_delete_post( $post_id, true );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $contrib );
