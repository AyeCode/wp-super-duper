<?php

namespace AyeCode\SuperDuper\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color palette option arrays for use inside select / color field definitions.
 *
 * This class returns arrays of option values — it is NOT a field factory.
 * Use the results as the `options` key inside a field definition array.
 *
 * @version 3.0.4-beta
 */
final class ColorOptions {

	/**
	 * Return AUI color options, filtered by type slugs.
	 *
	 * @param array $types   Slugs to include: 'core', 'subtle', 'emphasis', 'outline', 'outline_btn_text', 'branding'.
	 *                       Prepend options: 'none', 'transparent'. Defaults to ['core'] when empty.
	 * @param bool  $flatten When true returns a flat key => label array; otherwise returns optgroups.
	 * @return array
	 */
	public static function aui( array $types = [], bool $flatten = false ): array {
		if ( empty( $types ) ) {
			$types = [ 'core' ];
		}

		$prepend = [];

		if ( in_array( 'none', $types, true ) ) {
			$prepend[''] = __( 'None', 'ayecode-connect' );
		}

		if ( in_array( 'transparent', $types, true ) ) {
			$prepend['transparent'] = __( 'Transparent', 'ayecode-connect' );
		}

		$grouped = [];

		// 1. Core colors (standard backgrounds).
		if ( in_array( 'core', $types, true ) ) {
			$grouped[ __( 'Standard Colors', 'ayecode-connect' ) ] = [
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
			];
		}

		// 2. Subtle colors (replaces translucent — adapts to dark mode).
		if ( in_array( 'subtle', $types, true ) ) {
			$grouped[ __( 'Subtle Colors - adapts to dark mode', 'ayecode-connect' ) ] = [
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
			];
		}

		// 3. Emphasis (text colors — adapts to dark mode).
		if ( in_array( 'emphasis', $types, true ) ) {
			$grouped[ __( 'Emphasis - adapts to dark mode', 'ayecode-connect' ) ] = [
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
			];
		}

		// 4. Outlines.
		if ( in_array( 'outline', $types, true ) ) {
			$btn_suffix = in_array( 'outline_btn_text', $types, true );
			$suffix     = $btn_suffix ? __( ' (button only)', 'ayecode-connect' ) : '';

			$grouped[ __( 'Outlines', 'ayecode-connect' ) ] = [
				'outline-primary'   => __( 'Primary outline', 'ayecode-connect' ) . $suffix,
				'outline-secondary' => __( 'Secondary outline', 'ayecode-connect' ) . $suffix,
				'outline-success'   => __( 'Success outline', 'ayecode-connect' ) . $suffix,
				'outline-danger'    => __( 'Danger outline', 'ayecode-connect' ) . $suffix,
				'outline-warning'   => __( 'Warning outline', 'ayecode-connect' ) . $suffix,
				'outline-info'      => __( 'Info outline', 'ayecode-connect' ) . $suffix,
				'outline-light'     => __( 'Light outline', 'ayecode-connect' ) . $suffix,
				'outline-dark'      => __( 'Dark outline', 'ayecode-connect' ) . $suffix,
				'outline-white'     => __( 'White outline', 'ayecode-connect' ) . $suffix,
			];
		}

		// 5. Branding / social colors.
		if ( in_array( 'branding', $types, true ) ) {
			$grouped[ __( 'Branding Colors', 'ayecode-connect' ) ] = self::branding();
		}

		if ( $flatten ) {
			$flat = [];
			foreach ( $grouped as $group ) {
				foreach ( $group as $key => $label ) {
					$flat[ $key ] = $label;
				}
			}
			return apply_filters( 'sd_get_aui_colors_flat', $prepend + $flat, $types );
		}

		return apply_filters( 'sd_get_aui_colors', $prepend + $grouped, $types );
	}

	/**
	 * Return the AUI branding / social color options.
	 *
	 * @return array
	 */
	public static function branding(): array {
		return [
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
		];
	}
}
