<?php
/**
 * Renderer against a throwaway post.
 */

use AyeCode\SuperDuper\DynamicData\Context;
use AyeCode\SuperDuper\DynamicData\Registry;
use AyeCode\SuperDuper\DynamicData\Renderer;

$post_id = wp_insert_post(
	array(
		'post_title'   => 'Fish & Chips',
		'post_content' => 'Content with {post_content} inside it.',
		'post_excerpt' => 'one two three four five six',
		'post_status'  => 'publish',
		'post_date'    => '2026-03-04 13:05:00',
	)
);
update_post_meta( $post_id, 'price', '12.50' );
update_post_meta( $post_id, '_secret', 'hidden' );
update_post_meta( $post_id, 'list', array( 'a', 'b' ) );

$ctx    = new Context( $post_id );
$render = static fn( string $text ) => Renderer::render( $text, $ctx );
$link   = esc_url( get_permalink( $post_id ) );

SD_DD_Test::group( 'render/basics' );

SD_DD_Test::same( 'title is escaped', 'Fish &amp; Chips', $render( '{post_title}' ) );
SD_DD_Test::same( 'surrounding text kept', 'Hi Fish &amp; Chips!', $render( 'Hi {post_title}!' ) );
SD_DD_Test::same( 'number', (string) $post_id, $render( '{post_id}' ) );
SD_DD_Test::same( 'link', '<a href="' . $link . '">Fish &amp; Chips</a>', $render( '{post_title @link}' ) );
SD_DD_Test::same( 'link new tab', '<a href="' . $link . '" target="_blank" rel="noopener noreferrer">Fish &amp; Chips</a>', $render( '{post_title @link @new_tab}' ) );
SD_DD_Test::same( 'words', 'one two three&hellip;', $render( '{post_excerpt @words:3}' ) );

SD_DD_Test::group( 'render/dates' );

SD_DD_Test::same( 'post date letter', '2026', $render( '{post_date:Y}' ) );
SD_DD_Test::same( 'post date with colon', '13:05', $render( '{post_date:H:i}' ) );
SD_DD_Test::same( 'post date with comma', 'March 4, 2026', $render( '{post_date:F j, Y}' ) );
SD_DD_Test::same( 'post date slashes', '04/03/2026', $render( '{post_date:d/m/Y}' ) );
SD_DD_Test::same( 'named format', '1:05 PM', $render( "{post_date @format:'g:i A'}" ) );
SD_DD_Test::same( 'named beats shorthand', '2026', $render( "{post_date:H:i @format:Y}" ) );
SD_DD_Test::same( 'current year', wp_date( 'Y' ), $render( '{current_date:Y}' ) );
SD_DD_Test::same( 'site default', wp_date( get_option( 'date_format' ) ), $render( '{current_date}' ) );

SD_DD_Test::group( 'render/meta' );

SD_DD_Test::same( 'post meta', '12.50', $render( '{post_meta:price}' ) );
SD_DD_Test::same( 'protected meta blocked', 'n/a', $render( "{post_meta:_secret @fallback:'n/a'}" ) );
SD_DD_Test::same( 'array meta empty', '', $render( '{post_meta:list}' ) );
SD_DD_Test::same( 'missing required key renders fallback', 'x', $render( "{post_meta @fallback:x}" ) );

add_filter( 'sd_dynamic_data_meta_key_allowed', $allow = static fn( $ok, $key ) => '_secret' === $key ? true : $ok, 10, 2 );
SD_DD_Test::same( 'protected meta opted in', 'hidden', $render( '{post_meta:_secret}' ) );
remove_filter( 'sd_dynamic_data_meta_key_allowed', $allow, 10 );

SD_DD_Test::group( 'render/fallback' );

SD_DD_Test::same( 'missing meta fallback', 'POA', $render( "{post_meta:nope @fallback:'POA'}" ) );
SD_DD_Test::same( 'fallback escaped', '&lt;b&gt;', $render( "{post_meta:nope @fallback:'<b>'}" ) );
SD_DD_Test::same( 'empty without fallback', '[]', $render( '[{post_meta:nope}]' ) );

SD_DD_Test::group( 'render/untouched' );

SD_DD_Test::same( 'unknown tag', '{nope_tag}', $render( '{nope_tag}' ) );
SD_DD_Test::same( 'css', '.a{color:red}', $render( '.a{color:red}' ) );
SD_DD_Test::same( 'primary on tag without one', '{post_id:postId}', $render( '{post_id:postId}' ) );
SD_DD_Test::same( 'json', '{"a":1}', $render( '{"a":1}' ) );
SD_DD_Test::same( 'unknown args ignored', 'Fish &amp; Chips', $render( '{post_title @future:1}' ) );

add_filter( 'sd_dynamic_data_unknown_tag', '__return_empty_string' );
SD_DD_Test::same( 'unknown tag removed by filter', '[]', $render( '[{nope_tag}]' ) );
remove_filter( 'sd_dynamic_data_unknown_tag', '__return_empty_string' );

SD_DD_Test::group( 'render/content' );

$content = $render( '{post_content}' );
SD_DD_Test::same( 'content does not recurse', true, false !== strpos( $content, 'Content with' ) && substr_count( $content, 'Content with' ) === 1 );

SD_DD_Test::group( 'registry' );

add_action(
	'sd_dynamic_data_register',
	static function () {
		Registry::register(
			'test_shout',
			array(
				'label'    => 'Shout',
				'args'     => array(
					array(
						'key'     => 'times',
						'type'    => 'number',
						'default' => 1,
						'primary' => true,
					),
				),
				'callback' => static fn( array $args ) => str_repeat( 'hey', (int) $args['times'] ),
			)
		);
	}
);
// The action only fires on first load, which has already happened above.
do_action( 'sd_dynamic_data_register' );
SD_DD_Test::same( 'addon tag', 'heyheyhey', $render( '{test_shout:3}' ) );
SD_DD_Test::same( 'number arg rejects text', 'hey', $render( '{test_shout:lots}' ) );

$schema = Registry::get_client_schema();
$names  = array_column( $schema['tags'], 'name' );
SD_DD_Test::same( 'schema lists core tags', true, in_array( 'post_meta', $names, true ) && in_array( 'current_date', $names, true ) );
SD_DD_Test::same( 'schema has no callbacks', false, strpos( wp_json_encode( $schema ), 'callback' ) !== false );
$date = $schema['tags'][ array_search( 'current_date', $names, true ) ];
SD_DD_Test::same( 'date presets resolved for the editor', true, is_array( $date['args'][0]['options'] ) && count( $date['args'][0]['options'] ) > 3 );
SD_DD_Test::same( 'url tags take no link modifier', array( 'fallback' ), $schema['tags'][ array_search( 'post_url', $names, true ) ]['modifiers'] );

wp_delete_post( $post_id, true );

$modifiers_of = static fn( string $name ) => $schema['tags'][ array_search( $name, $names, true ) ]['modifiers'];
SD_DD_Test::same( 'dates take no word limit', array( 'link', 'new_tab', 'fallback' ), $modifiers_of( 'post_date' ) );
SD_DD_Test::same( 'fallback is listed last', array( 'words', 'link', 'new_tab', 'fallback' ), $modifiers_of( 'post_title' ) );
