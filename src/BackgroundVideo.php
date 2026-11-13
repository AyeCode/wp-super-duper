<?php

namespace AyeCode\SuperDuper;

/**
 * The video of a block's background: a media library file, YouTube or Vimeo.
 *
 * Nothing of the video is requested while the page loads. The markup carries where
 * it is, and assets/js/background-media.js attaches it once the page has loaded and
 * the block is in view, then fades it in over the image when it is playing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BackgroundVideo {

	/**
	 * Handle of the script that loads and plays the videos.
	 */
	const SCRIPT = 'sd-background-media';

	/**
	 * Meta of an imported preview image: the video it is of, as `type:id`.
	 */
	const POSTER_META = '_sd_video_poster';

	/**
	 * Work out what a video URL points at.
	 *
	 * @param string $url A YouTube or Vimeo page, share or embed URL, or the URL of a video file.
	 * @return array `type` (youtube, vimeo or file) with `id` and, for an unlisted Vimeo
	 *               video, `hash`; or with `url` and `mime` for a file. Empty when it is not a video.
	 */
	public static function parse( string $url ): array {
		$url = trim( html_entity_decode( $url, ENT_QUOTES ) );

		if ( '' === $url ) {
			return [];
		}

		if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $match ) ) {
			return [
				'type' => 'youtube',
				'id'   => $match[1],
			];
		}

		if ( preg_match( '~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)(?:/([a-f0-9]+))?(?:[^#]*[?&]h=([a-f0-9]+))?~i', $url, $match ) ) {
			return [
				'type' => 'vimeo',
				'id'   => $match[1],
				'hash' => ! empty( $match[2] ) ? $match[2] : ( $match[3] ?? '' ),
			];
		}

		$file = wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ), wp_get_mime_types() );
		$url  = esc_url_raw( $url );

		if ( '' === $url || 0 !== strpos( (string) $file['type'], 'video/' ) ) {
			return [];
		}

		return [
			'type' => 'file',
			'url'  => $url,
			'mime' => $file['type'],
		];
	}

	/**
	 * The provider's preview image of a YouTube or Vimeo video, for a block with no image of its own.
	 *
	 * Finding it takes a request to the provider, so the answer is kept: a week when
	 * found, an hour when the provider could not be reached.
	 *
	 * @param array $video A video from parse().
	 * @return string Image URL, or an empty string when there is none.
	 */
	public static function poster( array $video ): string {
		$type = $video['type'] ?? '';
		$url  = '';

		if ( 'youtube' === $type || 'vimeo' === $type ) {
			$key = 'sd_bgv_' . md5( $type . ':' . $video['id'] . ':' . ( $video['hash'] ?? '' ) );
			$url = get_transient( $key );

			if ( false === $url ) {
				$found = 'youtube' === $type ? self::youtube_poster( $video ) : self::vimeo_poster( $video );
				$url   = (string) $found;

				set_transient( $key, $url, null === $found ? HOUR_IN_SECONDS : WEEK_IN_SECONDS );
			}
		}

		/**
		 * Filter the provider preview image shown behind a background video.
		 *
		 * It is loaded from the provider's servers. Return an empty string to show none.
		 *
		 * @param string $url   The image URL.
		 * @param array  $video The video, from BackgroundVideo::parse().
		 */
		return esc_url_raw( (string) apply_filters( 'sd_background_video_poster', (string) $url, $video ) );
	}

	/**
	 * YouTube's 1280 wide preview, as WebP, which only videos uploaded at 720p or more have.
	 * The small one every video has is the fallback, as it has no black bars.
	 *
	 * @return string|null Null when YouTube could not be reached.
	 */
	private static function youtube_poster( array $video ): ?string {
		$large    = 'https://i.ytimg.com/vi_webp/' . $video['id'] . '/maxresdefault.webp';
		$response = wp_safe_remote_head( $large, [ 'timeout' => 3 ] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		return 200 === (int) wp_remote_retrieve_response_code( $response ) ? $large : 'https://i.ytimg.com/vi/' . $video['id'] . '/mqdefault.jpg';
	}

	/**
	 * Vimeo's preview, which only its oEmbed endpoint knows the address of.
	 *
	 * @return string|null Null when Vimeo could not be reached.
	 */
	private static function vimeo_poster( array $video ): ?string {
		$page     = 'https://vimeo.com/' . $video['id'] . ( ! empty( $video['hash'] ) ? '/' . $video['hash'] : '' );
		$response = wp_safe_remote_get( 'https://vimeo.com/api/oembed.json?width=1920&url=' . rawurlencode( $page ), [ 'timeout' => 3 ] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		// A private video answers with an error and has no preview to show.
		if ( ! is_array( $data ) || empty( $data['thumbnail_url'] ) ) {
			return '';
		}

		$base = self::vimeo_base( (string) $data['thumbnail_url'] );

		return '' !== $base ? $base . '-d_1280' : (string) $data['thumbnail_url'];
	}

	/**
	 * A Vimeo preview address without its size, which any width can then be added to.
	 * With no file extension Vimeo answers in the best format the browser takes.
	 *
	 * @return string Empty when the address is not one of Vimeo's sized previews.
	 */
	private static function vimeo_base( string $url ): string {
		return preg_match( '~^(https://i\.vimeocdn\.com/video/[^?#]+?)-d_\d+(?:x\d+)?(?:\.[a-z]+)?(?:[?#].*)?$~i', $url, $match ) ? $match[1] : '';
	}

	/**
	 * The other widths a provider preview comes in, for the browser to pick from.
	 *
	 * @param string $url A preview from poster().
	 * @return string A srcset, or an empty string when the preview has one size only.
	 */
	public static function poster_srcset( string $url ): string {
		if ( preg_match( '~^https://i\.ytimg\.com/vi(_webp)?/([A-Za-z0-9_-]{11})/maxresdefault\.(jpg|webp)$~', $url, $match ) ) {
			// The sizes between these two have black bars.
			return 'https://i.ytimg.com/vi' . $match[1] . '/' . $match[2] . '/mqdefault.' . $match[3] . ' 320w, ' . $url . ' 1280w';
		}

		$base = self::vimeo_base( $url );

		if ( '' === $base ) {
			return '';
		}

		$srcset = [];
		foreach ( [ 640, 960, 1280, 1920 ] as $width ) {
			$srcset[] = $base . '-d_' . $width . ' ' . $width . 'w';
		}

		return implode( ', ', $srcset );
	}

	/**
	 * Copy the provider's preview image of a YouTube or Vimeo video into the media library.
	 *
	 * The image is then the block's own: served from this site in every size, which
	 * loads faster than the provider's. A video imported before gives the same image.
	 *
	 * @param array $video A video from parse().
	 * @return array|\WP_Error `id` and `url` of the attachment.
	 */
	public static function import_poster( array $video ) {
		$type = $video['type'] ?? '';

		if ( 'youtube' !== $type && 'vimeo' !== $type ) {
			return new \WP_Error( 'sd_video_poster_none', __( 'Only a YouTube or Vimeo video has a preview image to import.', 'ayecode-connect' ), [ 'status' => 400 ] );
		}

		$key      = $type . ':' . $video['id'];
		$existing = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::POSTER_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( $existing ) {
			return [
				'id'  => (int) $existing[0],
				'url' => (string) wp_get_attachment_url( $existing[0] ),
			];
		}

		$source = self::poster_source( $video );

		if ( '' === $source ) {
			return new \WP_Error( 'sd_video_poster_none', __( 'This video has no large preview image to import.', 'ayecode-connect' ), [ 'status' => 404 ] );
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$temp = download_url( $source, 30 );

		if ( is_wp_error( $temp ) ) {
			return new \WP_Error( 'sd_video_poster_download', __( 'The preview image could not be downloaded.', 'ayecode-connect' ), [ 'status' => 502 ] );
		}

		$id = media_handle_sideload(
			[
				'name'     => sanitize_file_name( $type . '-' . $video['id'] . '.' . pathinfo( (string) wp_parse_url( $source, PHP_URL_PATH ), PATHINFO_EXTENSION ) ),
				'tmp_name' => $temp,
			],
			0
		);

		if ( is_wp_error( $id ) ) {
			wp_delete_file( $temp );

			return new \WP_Error( 'sd_video_poster_upload', $id->get_error_message(), [ 'status' => 500 ] );
		}

		update_post_meta( $id, self::POSTER_META, $key );

		return [
			'id'  => (int) $id,
			'url' => (string) wp_get_attachment_url( $id ),
		];
	}

	/**
	 * The largest file of a provider preview, in a format this server can resize.
	 *
	 * @return string Empty when the video has no preview large enough to be a background.
	 */
	private static function poster_source( array $video ): string {
		$url  = self::poster( $video );
		$webp = wp_image_editor_supports( [ 'mime_type' => 'image/webp' ] );

		if ( preg_match( '~^https://i\.ytimg\.com/vi(?:_webp)?/([A-Za-z0-9_-]{11})/maxresdefault\.(?:jpg|webp)$~', $url, $match ) ) {
			return $webp ? 'https://i.ytimg.com/vi_webp/' . $match[1] . '/maxresdefault.webp' : 'https://i.ytimg.com/vi/' . $match[1] . '/maxresdefault.jpg';
		}

		$base = self::vimeo_base( $url );

		return '' !== $base ? $base . '-d_1920' . ( $webp ? '.webp' : '.jpg' ) : '';
	}

	/**
	 * POST /ayecode/v3/background-video/poster
	 *
	 * @param \WP_REST_Request $request With `url`, the video's address.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_import_poster( \WP_REST_Request $request ) {
		$image = self::import_poster( self::parse( (string) $request->get_param( 'url' ) ) );

		return is_wp_error( $image ) ? $image : rest_ensure_response( $image );
	}

	/**
	 * Whether the current user may import a preview image.
	 *
	 * @return bool|\WP_Error
	 */
	public static function rest_permission_check() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You do not have permission to upload files.', 'ayecode-connect' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * The address a YouTube or Vimeo video plays from, muted, looping and without controls.
	 */
	private static function embed_url( array $video ): string {
		if ( 'youtube' === $video['type'] ) {
			// Looping a single video needs it named as its own playlist.
			return 'https://www.youtube-nocookie.com/embed/' . $video['id'] . '?autoplay=1&mute=1&loop=1&playlist=' . $video['id'] . '&controls=0&playsinline=1&rel=0&disablekb=1&fs=0&iv_load_policy=3&enablejsapi=1';
		}

		return 'https://player.vimeo.com/video/' . $video['id'] . '?background=1&autoplay=1&muted=1&loop=1&autopause=0&dnt=1' . ( ! empty( $video['hash'] ) ? '&h=' . $video['hash'] : '' );
	}

	/**
	 * The element the video plays in. It is empty and invisible until the script starts it.
	 *
	 * @param array  $video    A video from parse().
	 * @param array  $args     The block's arguments; bg_video_hide_mobile leaves it out on small screens.
	 * @param string $classes  Classes that make it fill the wrapper.
	 * @param string $position Optional. The focal point as an object-position value; top left when empty.
	 * @return string HTML.
	 */
	public static function element( array $video, array $args, string $classes, string $position = '' ): string {
		if ( empty( $video['type'] ) ) {
			return '';
		}

		// Cropped from the same point as the image under it, so nothing shifts when it fades in.
		$position = '' !== $position ? $position : '0% 0%';
		$style    = 'border-radius:inherit;transition:opacity .6s ease;';
		$mobile   = ! empty( $args['bg_video_hide_mobile'] ) ? ' data-mobile="0"' : '';

		self::enqueue();

		if ( 'file' === $video['type'] ) {
			$style .= 'object-position:' . $position . ';';

			return '<video class="bg-media-video ' . esc_attr( $classes ) . ' object-fit-cover opacity-0" style="' . esc_attr( $style ) . '" data-src="' . esc_url( $video['url'] ) . '"' . $mobile . ' muted loop playsinline preload="none" disablepictureinpicture disableremoteplayback aria-hidden="true" tabindex="-1"></video>';
		}

		// The player goes in as an iframe sized, in the box's own units, to cover it.
		return '<div class="bg-media-video ' . esc_attr( $classes ) . ' overflow-hidden opacity-0" style="' . esc_attr( $style . 'container-type:size;' ) . '" data-embed="' . esc_url( self::embed_url( $video ) ) . '" data-provider="' . esc_attr( $video['type'] ) . '" data-position="' . esc_attr( $position ) . '"' . $mobile . ' aria-hidden="true"></div>';
	}

	/**
	 * Load the script on a page that shows a video.
	 */
	public static function enqueue(): void {
		if ( wp_script_is( self::SCRIPT, 'enqueued' ) || ! defined( 'SUPER_DUPER_PLUGIN_URL' ) ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT,
			SUPER_DUPER_PLUGIN_URL . 'assets/js/background-media.js',
			[],
			defined( 'SUPER_DUPER_VER' ) ? SUPER_DUPER_VER : false,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
	}
}
