<?php
/**
 * Count tags, the number modifiers and the tag value cache.
 */

use AyeCode\SuperDuper\DynamicData\Context;
use AyeCode\SuperDuper\DynamicData\Registry;
use AyeCode\SuperDuper\DynamicData\Renderer;

$author_id = wp_insert_user(
	[
		'user_login' => 'sd_dd_count_' . wp_generate_password( 6, false ),
		'user_pass'  => wp_generate_password(),
		'role'       => 'author',
	]
);
$post_id   = wp_insert_post(
	[
		'post_title'  => 'Counted',
		'post_status' => 'publish',
		'post_author' => $author_id,
	]
);

$ctx    = new Context( $post_id );
$render = static fn( string $text ) => Renderer::render( $text, $ctx );

SD_DD_Test::group( 'counts/core' );

SD_DD_Test::same( 'post count', (string) wp_count_posts( 'post' )->publish, $render( '{post_count}' ) );
SD_DD_Test::same( 'post count by type', (string) wp_count_posts( 'page' )->publish, $render( '{post_count:page}' ) );
SD_DD_Test::same( 'non viewable post type is empty', 'x', $render( '{post_count:revision @fallback:x}' ) );
SD_DD_Test::same( 'term count', (string) wp_count_terms( [ 'taxonomy' => 'category', 'hide_empty' => false ] ), $render( '{term_count:category}' ) );
SD_DD_Test::same( 'unknown taxonomy is empty', 'x', $render( '{term_count:nope_tax @fallback:x}' ) );
SD_DD_Test::same( 'user count', (string) get_user_count(), $render( '{user_count}' ) );
SD_DD_Test::same( 'user count by role', (string) count_users()['avail_roles']['author'], $render( '{user_count:author}' ) );
SD_DD_Test::same( 'unknown role is empty', 'x', $render( '{user_count:nope_role @fallback:x}' ) );
SD_DD_Test::same( 'site comment count', (string) wp_count_comments()->approved, $render( '{site_comment_count}' ) );
SD_DD_Test::same( 'author post count', '1', $render( '{author_post_count}' ) );
SD_DD_Test::same( 'author post count of another type', '0', $render( '{author_post_count @post_type:page}' ) );

add_action(
	'sd_dynamic_data_register',
	static function () {
		Registry::register(
			'test_big',
			[
				'returns'  => 'number',
				'args'     => [
					[
						'key'     => 'n',
						'primary' => true,
					],
				],
				'callback' => static fn( array $args ) => $args['n'],
			]
		);

		foreach ( [ 'test_memo' => 0, 'test_kept' => 60 ] as $name => $ttl ) {
			Registry::register(
				$name,
				[
					'returns'  => 'number',
					'args'     => [
						[
							'key'     => 'n',
							'primary' => true,
						],
					],
					'cache'    => $ttl,
					'callback' => static function () use ( $name ) {
						$GLOBALS['sd_dd_test_calls'][ $name ] = ( $GLOBALS['sd_dd_test_calls'][ $name ] ?? 0 ) + 1;

						return $GLOBALS['sd_dd_test_calls'][ $name ];
					},
				]
			);
		}
	}
);
do_action( 'sd_dynamic_data_register' );

SD_DD_Test::group( 'counts/modifiers' );

SD_DD_Test::same( 'plain by default', '12345', $render( '{test_big:12345}' ) );
SD_DD_Test::same( 'thousands', number_format_i18n( 12345 ), $render( '{test_big:12345 @thousands}' ) );
SD_DD_Test::same( 'thousands keeps decimals', number_format_i18n( 1234.5, 1 ), $render( '{test_big:1234.5 @thousands}' ) );
SD_DD_Test::same( 'round', '12300', $render( '{test_big:12345 @round:100}' ) );
SD_DD_Test::same( 'round and thousands', number_format_i18n( 12000 ) . '+', $render( '{test_big:12345 @round:1000 @thousands}+' ) );
SD_DD_Test::same( 'round below the step is zero', '0', $render( '{test_big:45 @round:100}' ) );
SD_DD_Test::same( 'text tags take no number modifiers', false, in_array( 'thousands', Registry::get( 'post_title' )['modifiers'], true ) );

SD_DD_Test::group( 'counts/cache' );

SD_DD_Test::same( 'resolved once per request', '1 1', $render( '{test_memo} {test_memo}' ) );
SD_DD_Test::same( 'other args resolve again', '2', $render( '{test_memo:5}' ) );
SD_DD_Test::same( 'memo only writes nothing', 1, $GLOBALS['sd_dd_test_calls']['test_memo'] - 1 );

Renderer::forget( 'test_kept' );
SD_DD_Test::same( 'kept value', '1', $render( '{test_kept}' ) );

// Clear the request memo the way a new request would, leaving the stored value.
$memo = new ReflectionProperty( Renderer::class, 'memo' );
$memo->setAccessible( true );
$memo->setValue( null, [] );
SD_DD_Test::same( 'served from the store on the next request', '1', $render( '{test_kept}' ) );

Renderer::forget( 'test_kept' );
SD_DD_Test::same( 'forget drops it', '2', $render( '{test_kept}' ) );

add_filter( 'sd_dynamic_data_cache_ttl', '__return_zero' );
Renderer::forget( 'test_kept' );
$render( '{test_kept}' );
$memo->setValue( null, [] );
SD_DD_Test::same( 'ttl filter turns the store off', '4', $render( '{test_kept}' ) );
remove_filter( 'sd_dynamic_data_cache_ttl', '__return_zero' );

Renderer::forget( 'test_kept' );
Renderer::forget( 'term_count', [ 'taxonomy' => 'category' ] );
Renderer::forget( 'user_count' );
Renderer::forget( 'user_count', [ 'role' => 'author' ] );
wp_delete_post( $post_id, true );
require_once ABSPATH . 'wp-admin/includes/user.php';
// Also clears an author left behind by a run that died part way.
foreach ( get_users( [ 'search' => 'sd_dd_count_*', 'search_columns' => [ 'user_login' ], 'fields' => 'ID' ] ) as $leftover ) {
	wp_delete_user( (int) $leftover );
}
