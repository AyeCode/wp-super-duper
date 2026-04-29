<?php

namespace AyeCode\SuperDuper\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static factory methods for AUI colour palette definitions.
 *
 * @version 3.0.4-beta
 */
final class ColorFields {

	/**
	 * Legacy wrapper — keeps the old boolean signature alive but routes to get_aui_colors().
	 * Always returns a flat array for backward compatibility.
	 *
	 * @deprecated 3.1.0 Use self::get_aui_colors() instead.
	 *
	 * @param bool $include_branding         Ignored (branding colours dropped in BS 5.3).
	 * @param bool $include_outlines         Include outline colour variants.
	 * @param bool $outline_button_only_text Label outlines as "button only".
	 * @param bool $include_translucent      Include translucent/subtle colours (legacy alias).
	 * @param bool $include_subtle           Include subtle colour variants.
	 * @param bool $include_emphasis         Include emphasis colour variants.
	 * @return array Flat associative array of colour key => label.
	 */
	public static function aui_colors( $include_branding = false, $include_outlines = false, $outline_button_only_text = false, $include_translucent = false, $include_subtle = false, $include_emphasis = false ) {
		$types = array( 'core' );

		// Branding is dropped in >= 5.3, so we ignore $include_branding.

		if ( $include_outlines ) {
			$types[] = 'outline';
		}

		if ( $outline_button_only_text ) {
			$types[] = 'outline_btn_text';
		}

		// Map both old translucent and new subtle to the 'subtle' group.
		if ( $include_subtle || $include_translucent ) {
			$types[] = 'subtle';
		}

		if ( $include_emphasis ) {
			$types[] = 'emphasis';
		}

		return self::get_aui_colors( $types, true );
	}

	/**
	 * Modern typed version — accepts an array of type slugs and returns grouped or flat colour data.
	 *
	 * Usage: ColorFields::get_aui_colors( ['core', 'subtle', 'outline'] );
	 *
	 * @param array $types   Slugs to include: 'core', 'subtle', 'emphasis', 'outline', 'outline_btn_text'.
	 * @param bool  $flatten When true returns a flat key => label array; otherwise returns optgroups.
	 * @return array
	 */
	public static function get_aui_colors( $types = array(), $flatten = false ) {
		if ( empty( $types ) ) {
			$types = array( 'core' );
		}

		$grouped_colors = array();

		// 1. Core Colors (Standard Backgrounds).
		if ( in_array( 'core', $types ) ) {
			$grouped_colors[ __( 'Standard Colors', 'ayecode-connect' ) ] = array(
				'primary'   => __( 'Primary', 'ayecode-connect' ),
				'secondary' => __( 'Secondary', 'ayecode-connect' ),
				'success'   => __( 'Success', 'ayecode-connect' ),
				'danger'    => __( 'Danger', 'ayecode-connect' ),
				'warning'   => __( 'Warning', 'ayecode-connect' ),
				'info'      => __( 'Info', 'ayecode-connect' ),
				'light'     => __( 'Light', 'ayecode-connect' ),
				'dark'      => __( 'Dark', 'ayecode-connect' ),
				'black'     => __( 'Black', 'ayecode-connect' ),
				'white'     => __( 'White', 'ayecode-connect' ),
			);
		}

		// 2. Subtle Colors (Replaces Translucent).
		if ( in_array( 'subtle', $types ) ) {
			$grouped_colors[ __( 'Subtle Colors - adapts to dark mode', 'ayecode-connect' ) ] = array(
				'primary-subtle'   => __( 'Primary Subtle', 'ayecode-connect' ),
				'secondary-subtle' => __( 'Secondary Subtle', 'ayecode-connect' ),
				'success-subtle'   => __( 'Success Subtle', 'ayecode-connect' ),
				'danger-subtle'    => __( 'Danger Subtle', 'ayecode-connect' ),
				'warning-subtle'   => __( 'Warning Subtle', 'ayecode-connect' ),
				'info-subtle'      => __( 'Info Subtle', 'ayecode-connect' ),
				'light-subtle'     => __( 'Light Subtle', 'ayecode-connect' ),
				'dark-subtle'      => __( 'Dark Subtle', 'ayecode-connect' ),
				'body-secondary'   => __( 'Body Secondary', 'ayecode-connect' ),
				'body-tertiary'    => __( 'Body Tertiary', 'ayecode-connect' ),
				'body'             => __( 'Body', 'ayecode-connect' ),
			);
		}

		// 3. Emphasis (Text Colors).
		if ( in_array( 'emphasis', $types ) ) {
			$grouped_colors[ __( 'Emphasis - adapts to dark mode', 'ayecode-connect' ) ] = array(
				'primary-emphasis'   => __( 'Primary Emphasis', 'ayecode-connect' ),
				'secondary-emphasis' => __( 'Secondary Emphasis', 'ayecode-connect' ),
				'success-emphasis'   => __( 'Success Emphasis', 'ayecode-connect' ),
				'danger-emphasis'    => __( 'Danger Emphasis', 'ayecode-connect' ),
				'warning-emphasis'   => __( 'Warning Emphasis', 'ayecode-connect' ),
				'info-emphasis'      => __( 'Info Emphasis', 'ayecode-connect' ),
				'light-emphasis'     => __( 'Light Emphasis', 'ayecode-connect' ),
				'dark-emphasis'      => __( 'Dark Emphasis', 'ayecode-connect' ),
				'body-secondary'     => __( 'Body Secondary', 'ayecode-connect' ),
				'body-tertiary'      => __( 'Body Tertiary', 'ayecode-connect' ),
				'body'               => __( 'Body', 'ayecode-connect' ),
			);
		}

		// 4. Outlines.
		if ( in_array( 'outline', $types ) ) {
			$btn_suffix  = in_array( 'outline_btn_text', $types );
			$group_label = __( 'Outlines', 'ayecode-connect' );

			if ( $btn_suffix ) {
				$grouped_colors[ $group_label ] = array(
					'outline-primary'   => __( 'Primary outline (button only)', 'ayecode-connect' ),
					'outline-secondary' => __( 'Secondary outline (button only)', 'ayecode-connect' ),
					'outline-success'   => __( 'Success outline (button only)', 'ayecode-connect' ),
					'outline-danger'    => __( 'Danger outline (button only)', 'ayecode-connect' ),
					'outline-warning'   => __( 'Warning outline (button only)', 'ayecode-connect' ),
					'outline-info'      => __( 'Info outline (button only)', 'ayecode-connect' ),
					'outline-light'     => __( 'Light outline (button only)', 'ayecode-connect' ),
					'outline-dark'      => __( 'Dark outline (button only)', 'ayecode-connect' ),
					'outline-white'     => __( 'White outline (button only)', 'ayecode-connect' ),
				);
			} else {
				$grouped_colors[ $group_label ] = array(
					'outline-primary'   => __( 'Primary outline', 'ayecode-connect' ),
					'outline-secondary' => __( 'Secondary outline', 'ayecode-connect' ),
					'outline-success'   => __( 'Success outline', 'ayecode-connect' ),
					'outline-danger'    => __( 'Danger outline', 'ayecode-connect' ),
					'outline-warning'   => __( 'Warning outline', 'ayecode-connect' ),
					'outline-info'      => __( 'Info outline', 'ayecode-connect' ),
					'outline-light'     => __( 'Light outline', 'ayecode-connect' ),
					'outline-dark'      => __( 'Dark outline', 'ayecode-connect' ),
					'outline-white'     => __( 'White outline', 'ayecode-connect' ),
				);
			}
		}

		if ( $flatten ) {
			$flat_colors = array();
			foreach ( $grouped_colors as $group ) {
				foreach ( $group as $key => $label ) {
					$flat_colors[ $key ] = $label;
				}
			}
			return apply_filters( 'sd_get_aui_colors_flat', $flat_colors, $types );
		}

		return apply_filters( 'sd_get_aui_colors', $grouped_colors, $types );
	}

	/**
	 * Return the AUI branding/social colours.
	 *
	 * @return array
	 */
	public static function branding_colors() {
		return array(
			'facebook'  => __( 'Facebook', 'ayecode-connect' ),
			'twitter'   => __( 'Twitter', 'ayecode-connect' ),
			'instagram' => __( 'Instagram', 'ayecode-connect' ),
			'linkedin'  => __( 'Linkedin', 'ayecode-connect' ),
			'flickr'    => __( 'Flickr', 'ayecode-connect' ),
			'github'    => __( 'GitHub', 'ayecode-connect' ),
			'youtube'   => __( 'YouTube', 'ayecode-connect' ),
			'wordpress' => __( 'WordPress', 'ayecode-connect' ),
			'google'    => __( 'Google', 'ayecode-connect' ),
			'yahoo'     => __( 'Yahoo', 'ayecode-connect' ),
			'vkontakte' => __( 'Vkontakte', 'ayecode-connect' ),
		);
	}
}
