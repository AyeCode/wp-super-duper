<?php

namespace AyeCode\SuperDuper\Fields;

use AyeCode\SuperDuper\Helpers\ColorOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @deprecated 3.1.0 Use \AyeCode\SuperDuper\Helpers\ColorOptions instead.
 *
 * Kept as a thin shim to avoid breaking external consumers that call
 * ColorFields::aui_colors() or ColorFields::get_aui_colors() directly.
 */
final class ColorFields {

	/**
	 * @deprecated 3.1.0 Use ColorOptions::aui() instead.
	 */
	public static function aui_colors( $include_branding = false, $include_outlines = false, $outline_button_only_text = false, $include_translucent = false, $include_subtle = false, $include_emphasis = false ): array {
		$types = [ 'core' ];

		if ( $include_outlines ) {
			$types[] = 'outline';
		}

		if ( $outline_button_only_text ) {
			$types[] = 'outline_btn_text';
		}

		if ( $include_subtle || $include_translucent ) {
			$types[] = 'subtle';
		}

		if ( $include_emphasis ) {
			$types[] = 'emphasis';
		}

		return ColorOptions::aui( $types, true );
	}

	/**
	 * @deprecated 3.1.0 Use ColorOptions::aui() instead.
	 */
	public static function get_aui_colors( $types = [], $flatten = false ): array {
		return ColorOptions::aui( (array) $types, (bool) $flatten );
	}

	/**
	 * @deprecated 3.1.0 Use ColorOptions::branding() instead.
	 */
	public static function branding_colors(): array {
		return ColorOptions::branding();
	}
}
