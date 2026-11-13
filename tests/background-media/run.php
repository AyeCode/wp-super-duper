<?php
/**
 * Tests for the background image layer.
 *
 * Plain CLI harness like tests/dynamic-data/run.php: boots WordPress and reports.
 *
 * Usage:  php tests/background-media/run.php
 */

use AyeCode\SuperDuper\BackgroundMedia;
use AyeCode\SuperDuper\BackgroundVideo;

if ( 'cli' !== PHP_SAPI ) {
	exit( 'This runner is CLI only.' );
}

$wp_load = '';
$dir     = __DIR__;

while ( '/' !== $dir && '' !== $dir ) {
	if ( file_exists( $dir . '/wp-load.php' ) ) {
		$wp_load = $dir . '/wp-load.php';
		break;
	}
	$dir = dirname( $dir );
}

if ( '' === $wp_load ) {
	exit( 'Could not locate wp-load.php above ' . __DIR__ . "\n" );
}

$_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/image.php';

$pass = 0;
$fail = 0;

$check = static function ( string $name, bool $ok, string $detail = '' ) use ( &$pass, &$fail ): void {
	if ( $ok ) {
		++$pass;
		echo 'PASS  ' . $name . "\n";
		return;
	}

	++$fail;
	echo 'FAIL  ' . $name . ( '' !== $detail ? "\n      " . $detail : '' ) . "\n";
};

$same = static fn( string $name, $expected, $actual ) => $check( $name, $expected === $actual, 'expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
$has  = static fn( string $name, string $needle, string $haystack ) => $check( $name, false !== strpos( $haystack, $needle ), 'missing ' . $needle . ' in ' . $haystack );
$not  = static fn( string $name, string $needle, string $haystack ) => $check( $name, false === strpos( $haystack, $needle ), 'found ' . $needle . ' in ' . $haystack );

// A real attachment, big enough for core to build a srcset from.
$upload = wp_upload_dir();
$file   = trailingslashit( $upload['path'] ) . 'sd-bg-media-test.jpg';
$canvas = imagecreatetruecolor( 2400, 1200 );
imagejpeg( $canvas, $file, 60 );
imagedestroy( $canvas );

$image_id = wp_insert_attachment(
	[
		'post_mime_type' => 'image/jpeg',
		'post_title'     => 'sd bg media test',
		'post_status'    => 'inherit',
	],
	$file
);
wp_update_attachment_metadata( $image_id, wp_generate_attachment_metadata( $image_id, $file ) );
$image_url = wp_get_attachment_url( $image_id );

// Style stripping.
$same( 'image declarations removed', 'padding:1px;color:red;', BackgroundMedia::without_image( 'padding:1px;background-image:url(http://a.test/b.jpg);background-size:cover;background-position:50% 30%;color:red' ) );
$same( 'a semicolon inside url() does not split', 'margin:0;', BackgroundMedia::without_image( 'background-image:url(data:image/svg+xml;base64,PD94bW==);margin:0;' ) );
$same( 'gradient over image removed with it', '', BackgroundMedia::without_image( 'background-image:linear-gradient(135deg,rgba(0,0,0,.5) 0%,#fff 100%),url(http://a.test/b.jpg);background-size:cover' ) );
$same( 'color is kept', 'background-color:#ff0000;', BackgroundMedia::without_image( 'background-color:#ff0000;background-image:url(x.jpg);background-blend-mode:overlay;background-attachment:fixed' ) );

// The layer.
$args  = [
	'bg_image'    => $image_url,
	'bg_image_id' => $image_id,
];
$layer = BackgroundMedia::layer( $args );
$has( 'image from the id', 'src="' . esc_url( $image_url ) . '"', $layer );
$has( 'has a srcset', 'srcset="', $layer );
$has( 'fills the wrapper under the content', 'position-absolute top-0 start-0 w-100 h-100 m-0 p-0 z-n1 pe-none object-fit-cover', $layer );
$has( 'empty alt', 'alt=""', $layer );
$not( 'no overlay without a gradient', 'bg-media-overlay', $layer );

$has( 'top left without a focal point, as a css background', 'object-position:0% 0%', $layer );
$has( 'focal point', 'object-position:30% 70%', BackgroundMedia::layer( $args + [ 'bg_image_xy' => [ 'x' => 0.3, 'y' => 0.7 ] ] ) );
$has( 'focal point as a block render passes it', 'object-position:50% 100%', BackgroundMedia::layer( $args + [ 'bg_image_xy' => '0.5,1' ] ) );
$has( 'focal point from a shortcode', 'object-position:30% 70%', BackgroundMedia::layer( $args + [ 'bg_image_xy' => '{&quot;x&quot;:0.3,&quot;y&quot;:0.7}' ] ) );
$has( 'focal point at the left edge', 'object-position:0% 50%', BackgroundMedia::layer( $args + [ 'bg_image_xy' => '0,0.5' ] ) );

// Video.
$http = [];
add_filter(
	'pre_http_request',
	static function ( $pre, $request, $url ) use ( &$http ) {
		$http[] = $url;

		if ( false !== strpos( $url, 'HASMAXRES01' ) ) {
			return [ 'response' => [ 'code' => 200 ], 'body' => '', 'headers' => [] ];
		}
		if ( false !== strpos( $url, 'vimeo.com/api/oembed' ) ) {
			return [ 'response' => [ 'code' => 200 ], 'body' => '{"thumbnail_url":"https:\\/\\/i.vimeocdn.com\\/video\\/1-abc-d_1280?region=us"}', 'headers' => [] ];
		}

		return [ 'response' => [ 'code' => 404 ], 'body' => '', 'headers' => [] ];
	},
	10,
	3
);

foreach ( [ 'HASMAXRES01', 'NOMAXRES_01' ] as $video_id ) {
	delete_transient( 'sd_bgv_' . md5( 'youtube:' . $video_id . ':' ) );
}
delete_transient( 'sd_bgv_' . md5( 'vimeo:123456:' ) );
delete_transient( 'sd_bgv_' . md5( 'vimeo:123456:abcdef12' ) );

$same( 'youtube watch url', [ 'type' => 'youtube', 'id' => 'HASMAXRES01' ], BackgroundVideo::parse( 'https://www.youtube.com/watch?feature=share&amp;v=HASMAXRES01&amp;t=3' ) );
$same( 'youtube short url', 'HASMAXRES01', BackgroundVideo::parse( 'https://youtu.be/HASMAXRES01?si=x' )['id'] );
$same( 'youtube shorts url', 'HASMAXRES01', BackgroundVideo::parse( 'https://youtube.com/shorts/HASMAXRES01' )['id'] );
$same( 'vimeo url', [ 'type' => 'vimeo', 'id' => '123456', 'hash' => '' ], BackgroundVideo::parse( 'https://vimeo.com/123456' ) );
$same( 'vimeo unlisted url', 'abcdef12', BackgroundVideo::parse( 'https://vimeo.com/123456/abcdef12' )['hash'] );
$same( 'vimeo player url', 'abcdef12', BackgroundVideo::parse( 'https://player.vimeo.com/video/123456?h=abcdef12' )['hash'] );
$same( 'video file', 'video/mp4', BackgroundVideo::parse( 'https://example.com/wp-content/uploads/clip.mp4?ver=2' )['mime'] );
$same( 'an image is not a video', [], BackgroundVideo::parse( 'https://example.com/photo.jpg' ) );
$same( 'a script url is not a video', [], BackgroundVideo::parse( 'javascript:alert(1)//clip.mp4' ) );

$same( 'youtube large preview when it exists', 'https://i.ytimg.com/vi_webp/HASMAXRES01/maxresdefault.webp', BackgroundVideo::poster( BackgroundVideo::parse( 'https://youtu.be/HASMAXRES01' ) ) );
$same( 'youtube small preview otherwise', 'https://i.ytimg.com/vi/NOMAXRES_01/mqdefault.jpg', BackgroundVideo::poster( BackgroundVideo::parse( 'https://youtu.be/NOMAXRES_01' ) ) );
$same( 'vimeo preview without its region', 'https://i.vimeocdn.com/video/1-abc-d_1280', BackgroundVideo::poster( BackgroundVideo::parse( 'https://vimeo.com/123456' ) ) );
$same( 'youtube preview widths', 'https://i.ytimg.com/vi_webp/HASMAXRES01/mqdefault.webp 320w, https://i.ytimg.com/vi_webp/HASMAXRES01/maxresdefault.webp 1280w', BackgroundVideo::poster_srcset( 'https://i.ytimg.com/vi_webp/HASMAXRES01/maxresdefault.webp' ) );
$same( 'the small youtube preview has one width', '', BackgroundVideo::poster_srcset( 'https://i.ytimg.com/vi/NOMAXRES_01/mqdefault.jpg' ) );
$same( 'vimeo preview widths', 'https://i.vimeocdn.com/video/1-abc-d_640 640w, https://i.vimeocdn.com/video/1-abc-d_960 960w, https://i.vimeocdn.com/video/1-abc-d_1280 1280w, https://i.vimeocdn.com/video/1-abc-d_1920 1920w', BackgroundVideo::poster_srcset( 'https://i.vimeocdn.com/video/1-abc-d_1280' ) );
$same( 'another image has no widths', '', BackgroundVideo::poster_srcset( 'https://example.com/photo.jpg' ) );
$check( 'a file has no preview to import', is_wp_error( BackgroundVideo::import_poster( BackgroundVideo::parse( 'https://example.com/clip.mp4' ) ) ) );
$check( 'a video with a small preview only is not imported', is_wp_error( BackgroundVideo::import_poster( BackgroundVideo::parse( 'https://youtu.be/NOMAXRES_01' ) ) ) );
$requests = count( $http );
BackgroundVideo::poster( BackgroundVideo::parse( 'https://vimeo.com/123456' ) );
$same( 'preview lookup is kept', $requests, count( $http ) );
$same( 'a file has no preview', '', BackgroundVideo::poster( BackgroundVideo::parse( 'https://example.com/clip.mp4' ) ) );

$file = BackgroundMedia::layer( $args + [ 'bg_video' => 'https://example.com/clip.mp4', 'bg_image_xy' => '0.5,1' ] );
$check( 'video is after the image', strpos( $file, '<video' ) > strpos( $file, '<img' ), $file );
$has( 'video loads nothing up front', 'preload="none"', $file );
$has( 'video source is deferred', 'data-src="https://example.com/clip.mp4"', $file );
$not( 'video has no src', ' src="https://example.com/clip.mp4"', $file );
$has( 'video starts hidden', 'opacity-0', $file );
$has( 'video takes the focal point', 'object-position:50% 100%;', substr( $file, strpos( $file, '<video' ) ) );
$has( 'video is muted and inline', ' muted loop playsinline', $file );
$not( 'video plays on small screens by default', 'data-mobile', $file );
$has( 'video can be left out on small screens', 'data-mobile="0"', BackgroundMedia::layer( $args + [ 'bg_video' => 'https://example.com/clip.mp4', 'bg_video_hide_mobile' => '1' ] ) );
$check( 'script is enqueued with a video', wp_script_is( BackgroundVideo::SCRIPT, 'enqueued' ) );

$only = BackgroundMedia::layer( [ 'bg_video' => 'https://example.com/clip.mp4' ] );
$check( 'a video file alone has no image', false === strpos( $only, '<img' ) && false !== strpos( $only, '<video' ), $only );

$tube = BackgroundMedia::layer( [ 'bg_video' => 'https://youtu.be/HASMAXRES01', 'bg' => 'custom-color', 'bg_color' => 'rgba(0,0,0,0.5)' ] );
$has( 'youtube preview stands in for the image', '<img src="https://i.ytimg.com/vi_webp/HASMAXRES01/maxresdefault.webp" srcset="https://i.ytimg.com/vi_webp/HASMAXRES01/mqdefault.webp 320w, https://i.ytimg.com/vi_webp/HASMAXRES01/maxresdefault.webp 1280w" sizes="100vw"', $tube );
$has( 'youtube plays from the no cookie domain', 'data-embed="https://www.youtube-nocookie.com/embed/HASMAXRES01?autoplay=1&#038;mute=1&#038;loop=1&#038;playlist=HASMAXRES01', $tube );
$not( 'no iframe in the markup', '<iframe', $tube );
$has( 'player is cropped from the same point as the image', 'data-position="50% 50%"', $tube );
$check( 'overlay is over the video', strpos( $tube, 'bg-media-overlay' ) > strpos( $tube, 'bg-media-video' ), $tube );

$vimeo = BackgroundMedia::layer( [ 'bg_video' => 'https://vimeo.com/123456/abcdef12' ] );
$has( 'vimeo plays as a background', 'data-embed="https://player.vimeo.com/video/123456?background=1', $vimeo );
$has( 'vimeo unlisted hash is passed', 'h=abcdef12', $vimeo );

$tint = BackgroundMedia::layer( [ 'bg_video' => 'https://example.com/clip.mp4', 'bg' => 'custom-color', 'bg_color' => 'rgba(0,0,0,0.5)' ] );
$not( 'a video with no image is tinted, not blended', 'mix-blend-mode', $tint );
$same( 'a named color still shows no layer', '', BackgroundMedia::layer( [ 'bg_video' => 'https://example.com/clip.mp4', 'bg' => 'primary' ] ) );
$has( 'high priority', 'fetchpriority="high"', BackgroundMedia::layer( $args + [ 'bg_image_priority' => 'high' ] ) );
$not( 'high priority is not lazy', 'loading="lazy"', BackgroundMedia::layer( $args + [ 'bg_image_priority' => 'high' ] ) );
$has( 'lazy', 'loading="lazy"', BackgroundMedia::layer( $args + [ 'bg_image_priority' => 'lazy' ] ) );
$has( 'boxed container sizes', '1320px', BackgroundMedia::layer( $args + [ 'container' => 'container' ] ) );

$gradient = BackgroundMedia::layer(
	$args + [
		'bg'          => 'custom-gradient',
		'bg_gradient' => 'linear-gradient(135deg,rgba(0,0,0,0.5) 0%,rgb(255,255,255) 100%)',
	]
);
$has( 'gradient becomes an overlay', 'bg-media-overlay', $gradient );
$has( 'overlay carries the gradient', 'background-image:linear-gradient(135deg,rgba(0,0,0,0.5) 0%,rgb(255,255,255) 100%)', $gradient );
$check( 'overlay is after the image', strpos( $gradient, 'bg-media-overlay' ) > strpos( $gradient, '<img' ) );

$color_args = $args + [
	'bg'       => 'custom-color',
	'bg_color' => 'rgba(0,0,0,0.61)',
];
$has( 'custom color becomes a blended overlay', 'background-color:rgba(0,0,0,0.61);mix-blend-mode:hard-light;', BackgroundMedia::layer( $color_args ) );
$has( 'blended color leaves the wrapper', '<div class="bg-media-wrap" ><img', BackgroundMedia::apply( '<div style="background-color:rgba(0,0,0,0.61);background-image:url(x.jpg);background-blend-mode:overlay">x</div>', $color_args ) );
$same( 'a named color hides the image', '', BackgroundMedia::layer( $args + [ 'bg' => 'primary' ] ) );
$same( 'no image, no layer', '', BackgroundMedia::layer( [ 'bg' => 'custom-gradient' ] ) );

$fixed = BackgroundMedia::layer( $args + [ 'bg_image_fixed' => '1' ] );
$has( 'fixed image is clipped to the wrapper', 'clip-path:inset(0)', $fixed );
$has( 'fixed image is pinned to the viewport', 'position-fixed top-0 start-0 w-100 vh-100', $fixed );
$check( 'fixed image is viewport wide', 1 === preg_match( '/sizes="(auto, )?100vw"/', $fixed ), $fixed );

$external = BackgroundMedia::layer( [ 'bg_image' => 'https://example.com/a b.jpg' ] );
$has( 'url only image', 'src="https://example.com/a%20b.jpg"', $external );
$not( 'url only image has no srcset', 'srcset', $external );
$same( 'unsafe url is refused', '', BackgroundMedia::layer( [ 'bg_image' => 'javascript:alert(1)' ] ) );
$has( 'override by id', 'src="' . esc_url( $image_url ) . '"', BackgroundMedia::layer( [ 'bg_image' => 'https://example.com/a.jpg' ], [ 'id' => $image_id ] ) );
$has( 'override by url drops the id', 'src="https://example.com/o.jpg"', BackgroundMedia::layer( $args, [ 'url' => 'https://example.com/o.jpg' ] ) );
$has( 'a missing attachment falls back to the url', 'src="' . esc_url( $image_url ) . '"', BackgroundMedia::layer( [ 'bg_image' => $image_url, 'bg_image_id' => 999999999 ] ) );

// apply().
$saved = '<section class="wp-block-blockstrap-blockstrap-widget-container container py-5" style="background-image:url(' . $image_url . ');background-size:cover;padding-top:10px" id="a>b"><p>Hello</p></section>';
$out   = BackgroundMedia::apply( $saved, $args );
$has( 'wrapper takes the class', 'class="wp-block-blockstrap-blockstrap-widget-container container py-5 bg-media-wrap"', $out );
$has( 'wrapper keeps other styles', 'style="padding-top:10px;"', $out );
$not( 'wrapper loses the css background', 'background-image', $out );
$check( 'layer is the first child', 1 === preg_match( '/^<section[^<]*><img [^>]+><p>Hello<\/p><\/section>$/', $out ), $out );
$has( 'a > in an attribute does not end the tag early', 'id="a>b"><img', $out );

$bare = BackgroundMedia::apply( '<div style="background-image:url(' . $image_url . ')">x</div>', $args );
$not( 'an emptied style attribute is removed', 'style=""', $bare );
$check( 'class added to a wrapper without one', 1 === preg_match( '/^<div class="bg-media-wrap"\s*><img/', $bare ), $bare );

$same( 'no image leaves the markup alone', '<div style="background-image:linear-gradient(red,blue)">x</div>', BackgroundMedia::apply( '<div style="background-image:linear-gradient(red,blue)">x</div>', [ 'bg' => 'custom-gradient' ] ) );

add_filter( 'sd_background_media', '__return_false' );
$same( 'filter keeps the css background', $saved, BackgroundMedia::apply( $saved, $args ) );
remove_filter( 'sd_background_media', '__return_false' );

$centered = BackgroundMedia::apply( '<div style="background-image:url(https://example.com/a.jpg);background-size:cover;background-position:50% 50%;">x</div>', [ 'bg_image' => 'https://example.com/a.jpg' ] );
$has( 'focal point saved only in the style is kept', 'object-position:50% 50%', $centered );
$has( 'a video alone is centered', 'object-position:50% 50%', BackgroundMedia::layer( [ 'bg_video' => 'https://example.com/clip.mp4' ] ) );
$has( 'a video over an image keeps the top left', 'object-position:0% 0%', substr( $file = BackgroundMedia::layer( $args + [ 'bg_video' => 'https://example.com/clip.mp4' ] ), strpos( $file, '<video' ) ) );

$same( 'global wrapper', $out, sd_background_media( $saved, $args ) );

wp_delete_attachment( $image_id, true );

echo "\n" . $pass . ' passed, ' . $fail . " failed\n";
exit( $fail > 0 ? 1 : 0 );
