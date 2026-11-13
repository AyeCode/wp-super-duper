<?php

namespace AyeCode\SuperDuper;

/**
 * Turns a block's background image from a CSS background into a real image.
 *
 * A CSS background is found late (after the stylesheet is parsed and the element
 * laid out), always loads the full size file and cannot be lazy loaded. An <img>
 * is found by the preload scanner, picks a size from its srcset, takes
 * fetchpriority and loading, and is what image CDNs and optimizers rewrite.
 *
 * apply() works on finished markup, so the editor and the saved block are left
 * alone: the image declarations come off the wrapper's inline style and the image
 * goes in as the wrapper's first child, behind the content. A gradient or a
 * blended color that sat over the image becomes an overlay element over it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BackgroundMedia {

	/**
	 * Class the wrapper takes. AyeCode UI makes it the containing block and the
	 * stacking context the layer sits at the bottom of.
	 */
	const WRAP_CLASS = 'bg-media-wrap';

	/**
	 * Declarations build_aui_styles() writes for the image, which the layer replaces.
	 */
	const IMAGE_PROPERTIES = [ 'background-image', 'background-size', 'background-position', 'background-attachment', 'background-blend-mode' ];

	/**
	 * Classes that fill the wrapper and sit under its content. The spacing resets are
	 * for wrappers that style every child, such as a row's gutters.
	 */
	const FILL_CLASSES = 'position-absolute top-0 start-0 w-100 h-100 m-0 p-0 z-n1 pe-none';

	/**
	 * Move the background image of a block's wrapper into an image layer.
	 *
	 * @param string $html  The block markup; its first tag is the wrapper.
	 * @param array  $args  The block's arguments, with the bg, bg_color, bg_gradient,
	 *                      bg_image, bg_image_id, bg_image_xy, bg_image_fixed,
	 *                      bg_image_priority, bg_video and bg_video_hide_mobile keys.
	 * @param array  $image Optional. `id` and/or `url` of the image to show in place of
	 *                      the one in $args, for a block that resolves its own (a featured image).
	 * @return string The markup, unchanged when there is no image to show.
	 */
	public static function apply( string $html, array $args, array $image = [] ): string {
		if ( '' === $html || ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() ) {
			return $html;
		}

		$saved = (string) $tags->get_attribute( 'style' );

		// An untouched focal point is the editor's default, which is not saved with the
		// block's arguments: the position the editor wrote into the style is all there is.
		if ( '' === self::position( $args ) ) {
			$args['bg_image_xy'] = self::style_position( $saved );
		}

		$layer = self::layer( $args, $image );

		/**
		 * Filter whether a block's background image is output as an image layer.
		 *
		 * Return false to keep the CSS background, for a site whose own CSS styles it.
		 *
		 * @param bool  $use  Whether to use the layer.
		 * @param array $args The block's arguments.
		 */
		if ( '' === $layer || ! apply_filters( 'sd_background_media', true, $args ) ) {
			return $html;
		}

		// A color that blended with the image moves to the layer with it.
		$style = self::without_image( $saved, '' !== self::blend_color( $args ) ? [ 'background-color' ] : [] );
		if ( '' === $style ) {
			$tags->remove_attribute( 'style' );
		} else {
			$tags->set_attribute( 'style', $style );
		}
		$tags->add_class( self::WRAP_CLASS );

		$html = $tags->get_updated_html();

		// The end of the wrapper's opening tag, which is where the layer goes.
		$start = strpos( $html, '<' . strtolower( (string) $tags->get_tag() ) );
		$start = false === $start ? stripos( $html, '<' . $tags->get_tag() ) : $start;
		$end   = false === $start ? false : self::tag_end( $html, $start );

		if ( false === $end ) {
			return $html;
		}

		return substr( $html, 0, $end + 1 ) . $layer . substr( $html, $end + 1 );
	}

	/**
	 * The media layer for a set of arguments: the image, over it the video, and over both the gradient.
	 *
	 * @param array $args  The block's arguments.
	 * @param array $image Optional. `id` and/or `url` to show in place of the one in $args.
	 * @return string HTML, or an empty string when the settings show no image or video.
	 */
	public static function layer( array $args, array $image = [] ): string {
		$bg = isset( $args['bg'] ) ? (string) $args['bg'] : '';

		// A named color covers the image, exactly as build_aui_styles() has it.
		if ( ! in_array( $bg, [ '', 'transparent', 'custom-color', 'custom-gradient' ], true ) ) {
			return '';
		}

		$video    = BackgroundVideo::parse( (string) ( $args['bg_video'] ?? '' ) );
		$position = self::position( $args );

		// A video with no image of the block's own under it has no top left to keep to.
		if ( '' === $position && $video && ! self::has_image( $args, $image ) ) {
			$position = '50% 50%';
		}

		$img = self::image( $args, $image, $video, $position );

		if ( '' === $img && ! $video ) {
			return '';
		}

		$has_image = '' !== $img;

		// The video goes over the image, which shows until it is playing.
		if ( $video ) {
			$img .= BackgroundVideo::element( $video, $args, self::FILL_CLASSES, $position );
		}

		$overlay = '';

		if ( 'custom-gradient' === $bg ) {
			$gradient = Utils::sanitize_css_gradient( $args['bg_gradient'] ?? '' );
			$overlay  = '' !== $gradient ? 'background-image:' . $gradient . ';' : '';
		} elseif ( '' !== self::blend_color( $args ) ) {
			// background-blend-mode: overlay blended the image onto the color. With the
			// color on top, hard-light is the same blend seen from the other side. A video
			// with no image under it is simply tinted.
			$overlay = 'background-color:' . self::blend_color( $args ) . ';' . ( $has_image ? 'mix-blend-mode:hard-light;' : '' );
		}

		if ( '' !== $overlay ) {
			$img .= '<div class="bg-media-overlay ' . self::FILL_CLASSES . '" style="' . esc_attr( $overlay . 'border-radius:inherit;' ) . '" aria-hidden="true"></div>';
		}

		return $img;
	}

	/**
	 * The image of the layer.
	 *
	 * @param array  $args     The block's arguments.
	 * @param array  $image    `id` and/or `url` to show in place of the one in $args.
	 * @param array  $video    The block's video, whose preview stands in when there is no image.
	 * @param string $position The focal point as an object-position value.
	 * @return string HTML, or an empty string when there is no image.
	 */
	private static function image( array $args, array $image, array $video, string $position ): string {
		$id  = isset( $image['id'] ) ? absint( $image['id'] ) : ( isset( $image['url'] ) ? 0 : absint( $args['bg_image_id'] ?? 0 ) );
		$url = isset( $image['url'] ) ? (string) $image['url'] : ( isset( $image['id'] ) ? '' : (string) ( $args['bg_image'] ?? '' ) );

		if ( $id && ! wp_attachment_is_image( $id ) ) {
			$id = 0;
		}

		if ( ! $id && '' === Utils::sanitize_css_url( $url ) ) {
			$url = $video ? BackgroundVideo::poster( $video ) : '';

			if ( '' === $url ) {
				return '';
			}

			$srcset = BackgroundVideo::poster_srcset( $url );
		}

		// A video fills the block and scrolls with it, so the image under it does the same.
		$fixed   = ! empty( $args['bg_image_fixed'] ) && ! $video;
		$classes = $fixed ? 'position-fixed top-0 start-0 w-100 vh-100 m-0 p-0 object-fit-cover' : self::FILL_CLASSES . ' object-fit-cover';
		$style   = $fixed ? '' : 'border-radius:inherit;';
		// With no focal point a CSS background starts at the top left, which is what the
		// editor shows and what existing designs were built on. An image centers itself.
		$style .= 'object-position:' . ( '' !== $position ? $position : '0% 0%' ) . ';';

		$attr = [
			'class'    => 'bg-media-image ' . $classes,
			'alt'      => '',
			'decoding' => 'async',
			'style'    => $style,
		];

		$priority = isset( $args['bg_image_priority'] ) ? (string) $args['bg_image_priority'] : '';

		// Left out, core decides: the first large image of the page is prioritized and
		// the ones further down are lazy loaded.
		if ( 'high' === $priority ) {
			$attr['fetchpriority'] = 'high';
			$attr['loading']       = 'eager';
		} elseif ( 'lazy' === $priority ) {
			$attr['loading'] = 'lazy';
		}

		$sizes = self::sizes( $args, $fixed );
		if ( '' !== $sizes ) {
			$attr['sizes'] = $sizes;
		}

		if ( $id ) {
			$img = wp_get_attachment_image( $id, 'full', false, $attr );
		} else {
			if ( ! empty( $srcset ) ) {
				// A provider's preview comes in a few widths of its own.
				$attr = [
					'srcset' => $srcset,
					'sizes'  => '' !== $sizes ? $sizes : '100vw',
				] + $attr;
			} else {
				unset( $attr['sizes'] );
			}

			$img = '<img src="' . esc_url( $url ) . '"';
			foreach ( $attr as $name => $value ) {
				$img .= ' ' . $name . '="' . esc_attr( $value ) . '"';
			}
			$img .= ' />';
		}

		// A fixed image is pinned to the viewport and shows through the layer's box,
		// which clip-path cuts it to. Unlike background-attachment this works on iOS.
		if ( '' !== $img && $fixed ) {
			$img = '<div class="bg-media-fixed ' . self::FILL_CLASSES . '" style="clip-path:inset(0);">' . $img . '</div>';
		}

		return $img;
	}

	/**
	 * Whether the block has an image of its own, not a video's preview.
	 */
	private static function has_image( array $args, array $image ): bool {
		if ( ! empty( $image['id'] ) || ! empty( $image['url'] ) ) {
			return true;
		}

		return ! $image && ( ! empty( $args['bg_image_id'] ) || '' !== Utils::sanitize_css_url( (string) ( $args['bg_image'] ?? '' ) ) );
	}

	/**
	 * The background-position of an inline style as a focal point, "x,y" in fractions.
	 */
	private static function style_position( string $style ): string {
		foreach ( self::declarations( $style ) as $declaration ) {
			if ( preg_match( '/^\s*background-position\s*:\s*(-?[\d.]+)%\s+(-?[\d.]+)%\s*$/i', $declaration, $match ) ) {
				return ( (float) $match[1] / 100 ) . ',' . ( (float) $match[2] / 100 );
			}
		}

		return '';
	}

	/**
	 * The custom color that blends with the image, when the settings have one.
	 */
	private static function blend_color( array $args ): string {
		return 'custom-color' === ( $args['bg'] ?? '' ) ? Utils::sanitize_css_color( $args['bg_color'] ?? '' ) : '';
	}

	/**
	 * The focal point as an object-position value.
	 */
	private static function position( array $args ): string {
		$xy = $args['bg_image_xy'] ?? null;

		// A block's render gets the object flattened to "x,y"; a typed shortcode may carry JSON.
		if ( is_string( $xy ) && '' !== $xy ) {
			$parts = explode( ',', $xy );
			$xy    = 2 === count( $parts ) && is_numeric( $parts[0] ) && is_numeric( $parts[1] )
				? [
					'x' => $parts[0],
					'y' => $parts[1],
				]
				: json_decode( html_entity_decode( $xy, ENT_QUOTES ), true );
		}

		// 0 is the left edge, a real position, so only a missing x means no focal point.
		if ( ! is_array( $xy ) || ! isset( $xy['x'] ) || ! is_numeric( $xy['x'] ) ) {
			return '';
		}

		return ( (float) $xy['x'] * 100 ) . '% ' . ( (float) ( $xy['y'] ?? 0 ) * 100 ) . '%';
	}

	/**
	 * The sizes attribute: how wide the image is shown, so the browser can pick from the srcset.
	 *
	 * A block cannot know its own width, so this is the widest it can be. A lazy
	 * loaded image is measured by the browser instead, which core asks for with "auto".
	 */
	private static function sizes( array $args, bool $fixed ): string {
		$sizes = '';

		if ( $fixed ) {
			$sizes = '100vw';
		} elseif ( 'container' === ( $args['container'] ?? '' ) ) {
			// Bootstrap's widest boxed container.
			$sizes = '(max-width: 1320px) 100vw, 1320px';
		}

		/**
		 * Filter the sizes attribute of a background image.
		 *
		 * @param string $sizes The sizes attribute; empty for core's default of the full viewport width.
		 * @param array  $args  The block's arguments.
		 */
		return (string) apply_filters( 'sd_background_media_sizes', $sizes, $args );
	}

	/**
	 * An inline style without the declarations the image layer replaces.
	 *
	 * A gradient with no image under it is not part of the layer, but apply() only
	 * runs when there is an image, where the gradient moves to the overlay.
	 *
	 * @param string   $style Inline style.
	 * @param string[] $also  Other properties to remove.
	 */
	public static function without_image( string $style, array $also = [] ): string {
		$kept   = [];
		$remove = array_merge( self::IMAGE_PROPERTIES, $also );

		foreach ( self::declarations( $style ) as $declaration ) {
			$property = strtolower( trim( (string) strstr( $declaration, ':', true ) ) );

			if ( '' !== $property && ! in_array( $property, $remove, true ) ) {
				$kept[] = trim( $declaration );
			}
		}

		return $kept ? implode( ';', $kept ) . ';' : '';
	}

	/**
	 * Split an inline style into declarations. A ";" inside brackets or quotes, as in
	 * url(data:image/png;base64,...), does not end one.
	 *
	 * @return string[]
	 */
	private static function declarations( string $style ): array {
		$out     = [];
		$current = '';
		$depth   = 0;
		$quote   = '';
		$length  = strlen( $style );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $style[ $i ];

			if ( '' !== $quote ) {
				$quote = $char === $quote ? '' : $quote;
			} elseif ( '"' === $char || "'" === $char ) {
				$quote = $char;
			} elseif ( '(' === $char ) {
				++$depth;
			} elseif ( ')' === $char ) {
				$depth = max( 0, $depth - 1 );
			} elseif ( ';' === $char && 0 === $depth ) {
				$out[]   = $current;
				$current = '';
				continue;
			}

			$current .= $char;
		}

		$out[] = $current;

		return array_filter( $out, static fn( $declaration ) => '' !== trim( $declaration ) );
	}

	/**
	 * The offset of the ">" that closes the tag starting at $start, skipping any inside a quoted value.
	 *
	 * @return int|false
	 */
	private static function tag_end( string $html, int $start ) {
		$quote  = '';
		$length = strlen( $html );

		for ( $i = $start; $i < $length; $i++ ) {
			$char = $html[ $i ];

			if ( '' !== $quote ) {
				$quote = $char === $quote ? '' : $quote;
			} elseif ( '"' === $char || "'" === $char ) {
				$quote = $char;
			} elseif ( '>' === $char ) {
				return $i;
			}
		}

		return false;
	}
}
