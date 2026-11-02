<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * Replaces dynamic data tags in a string with their escaped values.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renderer {

	/**
	 * Replace every tag in a string.
	 *
	 * Text that is not a registered tag, or a tag given something it cannot take,
	 * is left exactly as written.
	 *
	 * @param string       $text    Text that may contain tags.
	 * @param Context|null $context What to resolve against; the current request when null.
	 * @return string
	 */
	public static function render( string $text, ?Context $context = null ): string {
		if ( false === strpos( $text, '{' ) ) {
			return $text;
		}

		$found = Grammar::find( $text );
		if ( ! $found ) {
			return $text;
		}

		$context = $context ?? new Context();

		// Back to front, so earlier offsets stay valid as lengths change.
		foreach ( array_reverse( $found ) as $match ) {
			$text = substr_replace( $text, self::render_tag( $match['tag'], $match['raw'], $context ), $match['offset'], $match['length'] );
		}

		return $text;
	}

	/**
	 * Render one parsed tag.
	 *
	 * @param array   $parsed  Output of Grammar::parse().
	 * @param string  $raw     The tag as written.
	 * @param Context $context What to resolve against.
	 * @return string Escaped output.
	 */
	public static function render_tag( array $parsed, string $raw, Context $context ): string {
		$def      = Registry::get( $parsed['name'] );
		$resolved = $def ? Registry::resolve_args( $def, $parsed ) : null;

		if ( ! $def || ! $resolved ) {
			/**
			 * Filter the output for text that looks like a tag but is not one that can render.
			 *
			 * Return '' to remove unknown tags instead of leaving them visible.
			 *
			 * @param string  $raw     The text as written.
			 * @param array   $parsed  Parsed name, primary and args.
			 * @param Context $context Render context.
			 */
			return (string) apply_filters( 'sd_dynamic_data_unknown_tag', $raw, $parsed, $context );
		}

		$args      = $resolved['args'];
		$modifiers = $resolved['modifiers'];
		$value     = null;

		if ( ! self::missing_required( $def, $args ) ) {
			$value = call_user_func( $def['callback'], $args, $context );
		}

		$fallback = isset( $modifiers['fallback'] ) ? (string) $modifiers['fallback'] : '';

		if ( null === $value || false === $value || '' === $value || ! is_scalar( $value ) ) {
			return esc_html( $fallback );
		}

		$returns = $def['returns'];

		if ( ! empty( $modifiers['words'] ) && $modifiers['words'] > 0 ) {
			$value   = wp_trim_words( wp_strip_all_tags( (string) $value ), (int) $modifiers['words'], '&hellip;' );
			$returns = 'text';
		}

		$output = self::escape( $value, $returns );

		if ( '' === $output ) {
			return esc_html( $fallback );
		}

		if ( ! empty( $modifiers['link'] ) && $def['link'] ) {
			$url = (string) call_user_func( $def['link'], $args, $context );

			if ( '' !== $url ) {
				$output = sprintf(
					'<a href="%s"%s>%s</a>',
					esc_url( $url ),
					! empty( $modifiers['new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '',
					$output
				);
			}
		}

		/**
		 * Filter a tag's final, escaped output.
		 *
		 * @param string  $output  Escaped output.
		 * @param array   $parsed  Parsed name, primary and args.
		 * @param array   $args    Resolved args.
		 * @param Context $context Render context.
		 */
		return (string) apply_filters( 'sd_dynamic_data_output', $output, $parsed, $args, $context );
	}

	/**
	 * Escape a value for the kind of data the tag says it returns.
	 *
	 * @param string|int|float|bool $value   Raw value.
	 * @param string                $returns text | html | url | number.
	 */
	public static function escape( $value, string $returns ): string {
		switch ( $returns ) {
			case 'html':
				return wp_kses_post( (string) $value );

			case 'url':
				return esc_url( (string) $value );

			case 'number':
				return is_numeric( $value ) ? (string) ( $value + 0 ) : '';

			default:
				return esc_html( (string) $value );
		}
	}

	private static function missing_required( array $def, array $args ): bool {
		foreach ( $def['args'] as $arg ) {
			if ( $arg['required'] && ( null === $args[ $arg['key'] ] || '' === $args[ $arg['key'] ] ) ) {
				return true;
			}
		}

		return false;
	}
}
