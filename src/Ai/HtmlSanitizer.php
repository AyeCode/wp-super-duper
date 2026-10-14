<?php

namespace AyeCode\SuperDuper\Ai;

/**
 * Pre-parse sanitization for /ai/convert input.
 *
 * The HTML is model output relayed by an authenticated editor, but it is treated
 * as untrusted: executable and stateful content is stripped before the string is
 * ever handed to DOMDocument. Script and style blocks are removed here (content
 * and all); event-handler attributes, `javascript:` URLs and inline `style=`
 * attributes are handled by the converter as it walks the tree, so it can report
 * each drop against the element it belonged to.
 *
 * Pure and stateless.
 */
class HtmlSanitizer {

	/**
	 * Strip `<script>` and `<style>` elements, including their contents, from a
	 * raw HTML string.
	 *
	 * @param string $html
	 * @return string
	 */
	public static function strip_dangerous_blocks( string $html ): string {
		$html = (string) preg_replace( '#<script\b[^>]*>.*?</script\s*>#is', '', $html );
		$html = (string) preg_replace( '#<style\b[^>]*>.*?</style\s*>#is', '', $html );

		// Unclosed tags a model might emit — remove the opening tag defensively.
		$html = (string) preg_replace( '#<(script|style)\b[^>]*>#i', '', $html );

		return $html;
	}

	/**
	 * Whether an attribute name is a DOM event handler (onclick, onerror, …).
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function is_event_handler( string $name ): bool {
		return 0 === stripos( $name, 'on' );
	}

	/**
	 * Whether a URL value uses a scheme that must never be emitted
	 * (javascript:, vbscript:, data: for anything but images).
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_dangerous_url( string $url ): bool {
		$trimmed = strtolower( trim( $url ) );
		// Ignore whitespace/entities a model might slip between the scheme chars.
		$trimmed = preg_replace( '/\s+/', '', $trimmed );

		if ( 0 === strpos( $trimmed, 'javascript:' ) || 0 === strpos( $trimmed, 'vbscript:' ) ) {
			return true;
		}

		// Allow data: image URIs (placeholder art), reject other data: payloads.
		if ( 0 === strpos( $trimmed, 'data:' ) && 0 !== strpos( $trimmed, 'data:image/' ) ) {
			return true;
		}

		return false;
	}
}
