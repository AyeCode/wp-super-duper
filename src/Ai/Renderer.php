<?php

namespace AyeCode\SuperDuper\Ai;

use AyeCode\SuperDuper\Registry;

/**
 * Renders a native block tree to front-end HTML for the fidelity harness.
 *
 * The HTML-first pipeline is scored by round-tripping HTML → blocks → HTML and
 * comparing, so this needs the front end of a converted tree without a browser.
 *
 * SuperDuper blocks are not registered as server-rendered Gutenberg blocks —
 * `do_blocks()` on their comment markup produces nothing — so the tree is
 * rendered directly through each block's own `shortcode_output()`, bottom-up: a
 * block's children are rendered first and passed as its content, exactly as a
 * nested layout renders on the front end. Because it executes block render code
 * on posted input it is dev/CI-only: gated behind the AI capability AND a filter
 * that defaults off in production.
 */
class Renderer {

	/** @var array<string, string>|null block name => base_id lookup. */
	private static ?array $name_map = null;

	/**
	 * Whether the render endpoint is enabled. Off unless explicitly turned on.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) apply_filters( 'ayecode_sd_rest_ai_render_enabled', false );
	}

	/**
	 * Render a native block tree to front-end HTML.
	 *
	 * @param array $blocks Native `{name, attributes, innerBlocks}` tree.
	 * @return string
	 */
	public static function render( array $blocks ): string {
		$parts = array();

		foreach ( $blocks as $block ) {
			$parts[] = self::render_node( (array) $block );
		}

		// Separate siblings with a newline so adjacent blocks do not fuse their
		// text ("…support" + "Contact…" → "supportContact") once whitespace
		// between tags is collapsed.
		return implode( "\n", $parts );
	}

	/**
	 * Render one block node and its descendants.
	 *
	 * @param array $block
	 * @return string
	 */
	private static function render_node( array $block ): string {
		$name    = (string) ( $block['name'] ?? '' );
		$attrs   = (array) ( $block['attributes'] ?? array() );
		$base_id = self::name_map()[ $name ] ?? '';

		$parts = array();
		foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $child ) {
			$parts[] = self::render_node( (array) $child );
		}
		$children = implode( "\n", $parts );

		// An unknown block is transparent — emit its children so the surrounding
		// structure still renders.
		if ( '' === $base_id ) {
			return $children;
		}

		$instance = Registry::get_instance_public( $base_id );
		if ( null === $instance || ! method_exists( $instance, 'shortcode_output' ) ) {
			return $children;
		}

		// Containers pass their rendered children as content; content blocks fall
		// back to their own text/content attribute.
		$content = '' !== $children
			? $children
			: (string) ( $attrs['text'] ?? $attrs['content'] ?? '' );

		return (string) $instance->shortcode_output( $attrs, $content );
	}

	/**
	 * Serialize a native block tree to Gutenberg block-comment markup. Retained
	 * for callers that want the editor-insertable markup (the front-end render
	 * path uses render() above, since these blocks have no server render callback).
	 *
	 * @param array $blocks
	 * @return string
	 */
	public static function serialize( array $blocks ): string {
		$markup = '';

		foreach ( $blocks as $block ) {
			$name = (string) ( $block['name'] ?? '' );
			if ( '' === $name ) {
				continue;
			}

			$attributes = (array) ( $block['attributes'] ?? array() );
			$inner      = (array) ( $block['innerBlocks'] ?? array() );
			$json       = empty( $attributes ) ? '' : ' ' . wp_json_encode( $attributes );

			if ( empty( $inner ) ) {
				$markup .= '<!-- wp:' . $name . $json . ' /-->' . "\n";
				continue;
			}

			$markup .= '<!-- wp:' . $name . $json . ' -->' . "\n";
			$markup .= self::serialize( $inner );
			$markup .= '<!-- /wp:' . $name . ' -->' . "\n";
		}

		return $markup;
	}

	/**
	 * Build (once) the full-block-name → base_id lookup across the registry.
	 *
	 * @return array<string, string>
	 */
	private static function name_map(): array {
		if ( null !== self::$name_map ) {
			return self::$name_map;
		}

		self::$name_map = array();

		foreach ( array_keys( Registry::get_entries() ) as $base_id ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance ) {
				continue;
			}

			$name = Catalog::block_name( $instance->options ?? array() );
			if ( '' !== $name ) {
				self::$name_map[ $name ] = $base_id;
			}
		}

		return self::$name_map;
	}
}
