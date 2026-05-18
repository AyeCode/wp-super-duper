<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper Utility Class
 *
 * Standalone, stateless static methods used throughout the framework.
 * Supersedes the global helper functions defined in includes/helpers.php
 * and includes/functions.php.
 */
class Utils {

	/**
	 * Checks if the current request is for any page builder's preview mode.
	 *
	 * @return bool True if a builder preview is detected, false otherwise.
	 */
	public static function is_preview(): bool {
		// Check for Elementor
		if ( isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor' ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor_ajax' ) ) {
			return true;
		}
		// Check for Divi
		if ( isset( $_REQUEST['et_fb'] ) || isset( $_REQUEST['et_pb_preview'] ) ) {
			return true;
		}
		// Check for Beaver Builder
		if ( isset( $_REQUEST['fl_builder'] ) ) {
			return true;
		}
		// Check for SiteOrigin
		if ( ! empty( $_REQUEST['siteorigin_panels_live_editor'] ) ) {
			return true;
		}
		// Check for Cornerstone
		if ( ! empty( $_REQUEST['cornerstone_preview'] ) || basename( $_SERVER['REQUEST_URI'] ) == 'cornerstone-endpoint' ) {
			return true;
		}
		// Check for Fusion Builder (Avada)
		if ( ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] ) ) {
			return true;
		}
		// Check for Oxygen
		if ( ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( substr( $_REQUEST['action'], 0, 11 ) === 'oxy_render_' || substr( $_REQUEST['action'], 0, 10 ) === 'ct_render_' ) ) ) {
			return true;
		}
		// Check for Kallyas Zion
		if ( function_exists( 'znhg_kallyas_theme_config' ) && ! empty( $_REQUEST['zn_pb_edit'] ) ) {
			return true;
		}
		// Check for Bricks Builder
		if ( function_exists( 'bricks_is_builder' ) && ( bricks_is_builder() || bricks_is_builder_call() ) ) {
			return true;
		}
		// Check for Gutenberg AJAX render
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'super_duper_output_shortcode' ) {
			return true;
		}

		return false;
	}

	/**
	 * Converts boolean-like strings in an array to actual booleans.
	 *
	 * @param array $options The array to process.
	 * @return array The processed array.
	 */
	public static function string_to_bool( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}
		foreach ( $options as $key => $val ) {
			if ( $val === 'false' ) {
				$options[ $key ] = false;
			} elseif ( $val === 'true' ) {
				$options[ $key ] = true;
			}
		}
		return $options;
	}

	/**
	 * Encodes special characters and shortcode tags for safe transport.
	 *
	 * @param string $content The content to encode.
	 * @return string The encoded content.
	 */
	public static function encode_shortcodes( string $content ): string {
		$trans   = [
			'&#91;'      => '&#091;',
			'&#93;'      => '&#093;',
			'&amp;#91;'  => '&#091;',
			'&amp;#93;'  => '&#093;',
			'&lt;'       => '&0lt;',
			'&gt;'       => '&0gt;',
			'&amp;lt;'   => '&0lt;',
			'&amp;gt;'   => '&0gt;',
		];
		$content = strtr( $content, $trans );

		$trans   = [
			'['  => '&#91;',
			']'  => '&#93;',
			'<'  => '&lt;',
			'>'  => '&gt;',
			'"'  => '&quot;',
			"'"  => '&#39;',
		];
		return strtr( $content, $trans );
	}

	/**
	 * Decodes special characters and shortcode tags back to their original form.
	 *
	 * @param string $content The content to decode.
	 * @return string The decoded content.
	 */
	public static function decode_shortcodes( string $content ): string {
		$trans   = [
			'&#91;'      => '[',
			'&#93;'      => ']',
			'&amp;#91;'  => '[',
			'&amp;#93;'  => ']',
			'&lt;'       => '<',
			'&gt;'       => '>',
			'&amp;lt;'   => '<',
			'&amp;gt;'   => '>',
			'&quot;'     => '"',
			'&apos;'     => "'",
		];
		$content = strtr( $content, $trans );

		$trans   = [
			'&#091;'     => '&#91;',
			'&#093;'     => '&#093;',
			'&amp;#091;' => '&#91;',
			'&amp;#093;' => '&#093;',
			'&0lt;'      => '&lt;',
			'&0gt;'      => '&gt;',
			'&amp;0lt;'  => '&lt;',
			'&amp;0gt;'  => '&gt;',
		];
		return strtr( $content, $trans );
	}

	/**
	 * Return the list of wp-admin page filenames where widget registration should be skipped.
	 *
	 * @return array
	 */
	public static function pagenow_exclude() {
		return apply_filters(
			'sd_pagenow_exclude',
			array(
				'upload.php',
				'edit-comments.php',
				'edit-tags.php',
				'index.php',
				'media-new.php',
				'options-discussion.php',
				'options-writing.php',
				'edit.php',
				'themes.php',
				'users.php',
			)
		);
	}

	/**
	 * Return widget class names that should be excluded from registration.
	 *
	 * @return array
	 */
	public static function widget_exclude() {
		return apply_filters( 'sd_widget_exclude', array() );
	}

	/**
	 * Parse a "key|value,key2|value2" attributes string into an associative array.
	 *
	 * Borrowed from Elementor; strips unsafe attributes (on*, href).
	 *
	 * @param string $attributes_string The raw attributes string.
	 * @param string $delimiter         Pair delimiter (default ',').
	 * @return array
	 */
	public static function parse_custom_attributes( $attributes_string, $delimiter = ',' ) {
		$attributes = explode( $delimiter, $attributes_string );
		$result     = array();

		foreach ( $attributes as $attribute ) {
			$attr_key_value = explode( '|', $attribute );

			$attr_key = mb_strtolower( $attr_key_value[0] );

			// Remove any not-allowed characters.
			preg_match( '/[-_a-z0-9]+/', $attr_key, $attr_key_matches );

			if ( empty( $attr_key_matches[0] ) ) {
				continue;
			}

			$attr_key = $attr_key_matches[0];

			// Avoid Javascript events and unescaped href.
			if ( 'href' === $attr_key || 'on' === substr( $attr_key, 0, 2 ) ) {
				continue;
			}

			$attr_value = isset( $attr_key_value[1] ) ? trim( $attr_key_value[1] ) : '';

			$result[ $attr_key ] = $attr_value;
		}

		return $result;
	}

	/**
	 * Build an escaped HTML attributes string from a settings array.
	 *
	 * Handles 'custom', 'new_window' and 'nofollow' keys.
	 *
	 * @param array $args Settings array (typically from shortcode/widget output).
	 * @return string Space-separated, escaped HTML attribute pairs ready to embed.
	 */
	public static function build_attributes_string_escaped( $args ) {
		$attributes     = array();
		$string_escaped = '';

		if ( ! empty( $args['custom'] ) ) {
			$attributes = self::parse_custom_attributes( $args['custom'] );
		}

		// new window
		if ( ! empty( $args['new_window'] ) ) {
			$attributes['target'] = '_blank';
		}

		// nofollow
		if ( ! empty( $args['nofollow'] ) ) {
			$attributes['rel'] = isset( $attributes['rel'] ) ? $attributes['rel'] . ' nofollow' : 'nofollow';
		}

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $key => $val ) {
				$string_escaped .= esc_attr( $key ) . '="' . esc_attr( $val ) . '" ';
			}
		}

		return $string_escaped;
	}

	/**
	 * Build a Bootstrap utility class string from a settings array.
	 *
	 * Converts individual field values (margins, padding, colours, display, flex,
	 * border, background, etc.) into a single space-separated CSS class string.
	 * This needs to be kept in sync with the JS version in includes/helpers/gutenberg-block-helpers.php
	 *
	 * @param array $args Settings array from a widget/shortcode instance.
	 * @return string
	 */
	public static function build_aui_class( $args ) {

		$classes = array();

		$p_ml = 'ms-';
		$p_mr = 'me-';
		$p_pl = 'ps-';
		$p_pr = 'pe-';


		// margins.
		if ( isset( $args['mt'] ) && $args['mt'] !== '' ) {
			$classes[] = 'mt-' . sanitize_html_class( $args['mt'] );
			$mt        = $args['mt'];
		} else {
			$mt = null;
		}
		if ( isset( $args['mr'] ) && $args['mr'] !== '' ) {
			$classes[] = $p_mr . sanitize_html_class( $args['mr'] );
			$mr        = $args['mr'];
		} else {
			$mr = null;
		}
		if ( isset( $args['mb'] ) && $args['mb'] !== '' ) {
			$classes[] = 'mb-' . sanitize_html_class( $args['mb'] );
			$mb        = $args['mb'];
		} else {
			$mb = null;
		}
		if ( isset( $args['ml'] ) && $args['ml'] !== '' ) {
			$classes[] = $p_ml . sanitize_html_class( $args['ml'] );
			$ml        = $args['ml'];
		} else {
			$ml = null;
		}

		// margins tablet.
		if ( isset( $args['mt_md'] ) && $args['mt_md'] !== '' ) {
			$classes[] = 'mt-md-' . sanitize_html_class( $args['mt_md'] );
			$mt_md     = $args['mt_md'];
		} else {
			$mt_md = null;
		}
		if ( isset( $args['mr_md'] ) && $args['mr_md'] !== '' ) {
			$classes[] = $p_mr . 'md-' . sanitize_html_class( $args['mr_md'] );
			$mt_md     = $args['mr_md'];
		} else {
			$mr_md = null;
		}
		if ( isset( $args['mb_md'] ) && $args['mb_md'] !== '' ) {
			$classes[] = 'mb-md-' . sanitize_html_class( $args['mb_md'] );
			$mt_md     = $args['mb_md'];
		} else {
			$mb_md = null;
		}
		if ( isset( $args['ml_md'] ) && $args['ml_md'] !== '' ) {
			$classes[] = $p_ml . 'md-' . sanitize_html_class( $args['ml_md'] );
			$mt_md     = $args['ml_md'];
		} else {
			$ml_md = null;
		}

		// margins desktop.
		if ( isset( $args['mt_lg'] ) && $args['mt_lg'] !== '' ) {
			if ( $mt == null && $mt_md == null ) {
				$classes[] = 'mt-' . sanitize_html_class( $args['mt_lg'] );
			} else {
				$classes[] = 'mt-lg-' . sanitize_html_class( $args['mt_lg'] );
			}
		}
		if ( isset( $args['mr_lg'] ) && $args['mr_lg'] !== '' ) {
			if ( $mr == null && $mr_md == null ) {
				$classes[] = $p_mr . sanitize_html_class( $args['mr_lg'] );
			} else {
				$classes[] = $p_mr . 'lg-' . sanitize_html_class( $args['mr_lg'] );
			}
		}
		if ( isset( $args['mb_lg'] ) && $args['mb_lg'] !== '' ) {
			if ( $mb == null && $mb_md == null ) {
				$classes[] = 'mb-' . sanitize_html_class( $args['mb_lg'] );
			} else {
				$classes[] = 'mb-lg-' . sanitize_html_class( $args['mb_lg'] );
			}
		}
		if ( isset( $args['ml_lg'] ) && $args['ml_lg'] !== '' ) {
			if ( $ml == null && $ml_md == null ) {
				$classes[] = $p_ml . sanitize_html_class( $args['ml_lg'] );
			} else {
				$classes[] = $p_ml . 'lg-' . sanitize_html_class( $args['ml_lg'] );
			}
		}

		// padding.
		if ( isset( $args['pt'] ) && $args['pt'] !== '' ) {
			$classes[] = 'pt-' . sanitize_html_class( $args['pt'] );
			$pt        = $args['pt'];
		} else {
			$pt = null;
		}
		if ( isset( $args['pr'] ) && $args['pr'] !== '' ) {
			$classes[] = $p_pr . sanitize_html_class( $args['pr'] );
			$pr        = $args['pr'];
		} else {
			$pr = null;
		}
		if ( isset( $args['pb'] ) && $args['pb'] !== '' ) {
			$classes[] = 'pb-' . sanitize_html_class( $args['pb'] );
			$pb        = $args['pb'];
		} else {
			$pb = null;
		}
		if ( isset( $args['pl'] ) && $args['pl'] !== '' ) {
			$classes[] = $p_pl . sanitize_html_class( $args['pl'] );
			$pl        = $args['pl'];
		} else {
			$pl = null;
		}

		// padding tablet.
		if ( isset( $args['pt_md'] ) && $args['pt_md'] !== '' ) {
			$classes[] = 'pt-md-' . sanitize_html_class( $args['pt_md'] );
			$pt_md     = $args['pt_md'];
		} else {
			$pt_md = null;
		}
		if ( isset( $args['pr_md'] ) && $args['pr_md'] !== '' ) {
			$classes[] = $p_pr . 'md-' . sanitize_html_class( $args['pr_md'] );
			$pr_md     = $args['pr_md'];
		} else {
			$pr_md = null;
		}
		if ( isset( $args['pb_md'] ) && $args['pb_md'] !== '' ) {
			$classes[] = 'pb-md-' . sanitize_html_class( $args['pb_md'] );
			$pb_md     = $args['pb_md'];
		} else {
			$pb_md = null;
		}
		if ( isset( $args['pl_md'] ) && $args['pl_md'] !== '' ) {
			$classes[] = $p_pl . 'md-' . sanitize_html_class( $args['pl_md'] );
			$pl_md     = $args['pl_md'];
		} else {
			$pl_md = null;
		}

		// padding desktop.
		if ( isset( $args['pt_lg'] ) && $args['pt_lg'] !== '' ) {
			if ( $pt == null && $pt_md == null ) {
				$classes[] = 'pt-' . sanitize_html_class( $args['pt_lg'] );
			} else {
				$classes[] = 'pt-lg-' . sanitize_html_class( $args['pt_lg'] );
			}
		}
		if ( isset( $args['pr_lg'] ) && $args['pr_lg'] !== '' ) {
			if ( $pr == null && $pr_md == null ) {
				$classes[] = $p_pr . sanitize_html_class( $args['pr_lg'] );
			} else {
				$classes[] = $p_pr . 'lg-' . sanitize_html_class( $args['pr_lg'] );
			}
		}
		if ( isset( $args['pb_lg'] ) && $args['pb_lg'] !== '' ) {
			if ( $pb == null && $pb_md == null ) {
				$classes[] = 'pb-' . sanitize_html_class( $args['pb_lg'] );
			} else {
				$classes[] = 'pb-lg-' . sanitize_html_class( $args['pb_lg'] );
			}
		}
		if ( isset( $args['pl_lg'] ) && $args['pl_lg'] !== '' ) {
			if ( $pl == null && $pl_md == null ) {
				$classes[] = $p_pl . sanitize_html_class( $args['pl_lg'] );
			} else {
				$classes[] = $p_pl . 'lg-' . sanitize_html_class( $args['pl_lg'] );
			}
		}

		// row cols, mobile, tablet, desktop
		if ( ! empty( $args['row_cols'] ) && $args['row_cols'] !== '' ) {
			$classes[] = sanitize_html_class( 'row-cols-' . $args['row_cols'] );
			$row_cols  = $args['row_cols'];
		} else {
			$row_cols = null;
		}
		if ( ! empty( $args['row_cols_md'] ) && $args['row_cols_md'] !== '' ) {
			$classes[]   = sanitize_html_class( 'row-cols-md-' . $args['row_cols_md'] );
			$row_cols_md = $args['row_cols_md'];
		} else {
			$row_cols_md = null;
		}
		if ( ! empty( $args['row_cols_lg'] ) && $args['row_cols_lg'] !== '' ) {
			if ( $row_cols == null && $row_cols_md == null ) {
				$classes[] = sanitize_html_class( 'row-cols-' . $args['row_cols_lg'] );
			} else {
				$classes[] = sanitize_html_class( 'row-cols-lg-' . $args['row_cols_lg'] );
			}
		}

		// columns, mobile, tablet, desktop
		if ( ! empty( $args['col'] ) && $args['col'] !== '' ) {
			$classes[] = sanitize_html_class( 'col-' . $args['col'] );
			$col       = $args['col'];
		} else {
			$col = null;
		}
		if ( ! empty( $args['col_md'] ) && $args['col_md'] !== '' ) {
			$classes[] = sanitize_html_class( 'col-md-' . $args['col_md'] );
			$col_md    = $args['col_md'];
		} else {
			$col_md = null;
		}
		if ( ! empty( $args['col_lg'] ) && $args['col_lg'] !== '' ) {
			if ( $col == null && $col_md == null ) {
				$classes[] = sanitize_html_class( 'col-' . $args['col_lg'] );
			} else {
				$classes[] = sanitize_html_class( 'col-lg-' . $args['col_lg'] );
			}
		}

		// border
		if ( isset( $args['border'] ) && ( $args['border'] == 'none' || $args['border'] === '0' || $args['border'] === 0 ) ) {
			$classes[] = 'border-0';
		} elseif ( ! empty( $args['border'] ) ) {
			$border_class = 'border';
			if ( ! empty( $args['border_type'] ) && strpos( $args['border_type'], '-0' ) === false ) {
				$border_class = '';
			}
			$classes[] = $border_class . ' border-' . sanitize_html_class( $args['border'] );
		}

		// border radius type
		if ( ! empty( $args['rounded'] ) ) {
			$classes[] = sanitize_html_class( $args['rounded'] );
		}

		// border radius size BS4
		if ( isset( $args['rounded_size'] ) && in_array( $args['rounded_size'], array( 'sm', 'lg' ) ) ) {
			$classes[] = 'rounded-' . sanitize_html_class( $args['rounded_size'] );
			// if we set a size then we need to remove "rounded" if set
			if ( ( $key = array_search( 'rounded', $classes ) ) !== false ) {
				unset( $classes[ $key ] );
			}
		} else {
			// border radius size, mobile, tablet, desktop
			if ( isset( $args['rounded_size'] ) && $args['rounded_size'] !== '' ) {
				$classes[]    = sanitize_html_class( 'rounded-' . $args['rounded_size'] );
				$rounded_size = $args['rounded_size'];
			} else {
				$rounded_size = null;
			}
			if ( isset( $args['rounded_size_md'] ) && $args['rounded_size_md'] !== '' ) {
				$classes[]       = sanitize_html_class( 'rounded-md-' . $args['rounded_size_md'] );
				$rounded_size_md = $args['rounded_size_md'];
			} else {
				$rounded_size_md = null;
			}
			if ( isset( $args['rounded_size_lg'] ) && $args['rounded_size_lg'] !== '' ) {
				if ( $rounded_size == null && $rounded_size_md == null ) {
					$classes[] = sanitize_html_class( 'rounded-' . $args['rounded_size_lg'] );
				} else {
					$classes[] = sanitize_html_class( 'rounded-lg-' . $args['rounded_size_lg'] );
				}
			}
		}

		// background
		if ( ! empty( $args['bg'] ) ) {
			$classes[] = 'bg-' . sanitize_html_class( $args['bg'] );
		}

		// background image fixed bg_image_fixed this helps fix a iOS bug
		if ( ! empty( $args['bg_image_fixed'] ) ) {
			$classes[] = 'bg-image-fixed';
		}

		// text_color
		if ( ! empty( $args['text_color'] ) ) {
			$classes[] = 'text-' . sanitize_html_class( $args['text_color'] );
		}

		// text_align
		if ( ! empty( $args['text_justify'] ) ) {
			$classes[] = 'text-justify';
		} else {
			if ( ! empty( $args['text_align'] ) ) {
				$classes[]  = sanitize_html_class( $args['text_align'] );
				$text_align = $args['text_align'];
			} else {
				$text_align = null;
			}
			if ( ! empty( $args['text_align_md'] ) && $args['text_align_md'] !== '' ) {
				$classes[]     = sanitize_html_class( $args['text_align_md'] );
				$text_align_md = $args['text_align_md'];
			} else {
				$text_align_md = null;
			}
			if ( ! empty( $args['text_align_lg'] ) && $args['text_align_lg'] !== '' ) {
				if ( $text_align == null && $text_align_md == null ) {
					$classes[] = sanitize_html_class( str_replace( '-lg', '', $args['text_align_lg'] ) );
				} else {
					$classes[] = sanitize_html_class( $args['text_align_lg'] );
				}
			}
		}

		// display
		if ( ! empty( $args['display'] ) ) {
			$classes[] = sanitize_html_class( $args['display'] );
			$display   = $args['display'];
		} else {
			$display = null;
		}
		if ( ! empty( $args['display_md'] ) && $args['display_md'] !== '' ) {
			$classes[]  = sanitize_html_class( $args['display_md'] );
			$display_md = $args['display_md'];
		} else {
			$display_md = null;
		}
		if ( ! empty( $args['display_lg'] ) && $args['display_lg'] !== '' ) {
			if ( $display == null && $display_md == null ) {
				$classes[] = sanitize_html_class( str_replace( '-lg', '', $args['display_lg'] ) );
			} else {
				$classes[] = sanitize_html_class( $args['display_lg'] );
			}
		}

		// bgtus - background transparent until scroll
		if ( ! empty( $args['bgtus'] ) ) {
			$classes[] = sanitize_html_class( 'bg-transparent-until-scroll' );
		}

		// cscos - change color scheme on scroll
		if ( ! empty( $args['bgtus'] ) && ! empty( $args['cscos'] ) ) {
			$classes[] = sanitize_html_class( 'color-scheme-flip-on-scroll' );
		}

		// hover animations
		if ( ! empty( $args['hover_animations'] ) ) {
			$classes[] = self::sanitize_html_classes( str_replace( ',', ' ', $args['hover_animations'] ) );
		}

		if ( ! empty( $args['hover_icon_animation'] ) ) {
			$classes[] = sanitize_html_class( $args['hover_icon_animation'] );
		}

		// absolute_position
		if ( ! empty( $args['absolute_position'] ) ) {
			if ( 'top-left' === $args['absolute_position'] ) {
				$classes[] = 'start-0 top-0';
			} elseif ( 'top-center' === $args['absolute_position'] ) {
				$classes[] = 'start-50 top-0 translate-middle';
			} elseif ( 'top-right' === $args['absolute_position'] ) {
				$classes[] = 'end-0 top-0';
			} elseif ( 'center-left' === $args['absolute_position'] ) {
				$classes[] = 'start-0 top-50';
			} elseif ( 'center' === $args['absolute_position'] ) {
				$classes[] = 'start-50 top-50 translate-middle';
			} elseif ( 'center-right' === $args['absolute_position'] ) {
				$classes[] = 'end-0 top-50';
			} elseif ( 'bottom-left' === $args['absolute_position'] ) {
				$classes[] = 'start-0 bottom-0';
			} elseif ( 'bottom-center' === $args['absolute_position'] ) {
				$classes[] = 'start-50 bottom-0 translate-middle';
			} elseif ( 'bottom-right' === $args['absolute_position'] ) {
				$classes[] = 'end-0 bottom-0';
			}
		}

		// build classes from build keys
		$build_keys = self::get_class_build_keys();
		if ( ! empty( $build_keys ) ) {
			foreach ( $build_keys as $key ) {
				if ( substr( $key, -4 ) == '-MTD' ) {
					$k = str_replace( '-MTD', '', $key );

					// Mobile, Tablet, Desktop
					if ( ! empty( $args[ $k ] ) && $args[ $k ] !== '' ) {
						$classes[] = sanitize_html_class( $args[ $k ] );
						$v         = $args[ $k ];
					} else {
						$v = null;
					}
					if ( ! empty( $args[ $k . '_md' ] ) && $args[ $k . '_md' ] !== '' ) {
						$classes[] = sanitize_html_class( $args[ $k . '_md' ] );
						$v_md      = $args[ $k . '_md' ];
					} else {
						$v_md = null;
					}
					if ( ! empty( $args[ $k . '_lg' ] ) && $args[ $k . '_lg' ] !== '' ) {
						if ( $v == null && $v_md == null ) {
							$classes[] = sanitize_html_class( str_replace( '-lg', '', $args[ $k . '_lg' ] ) );
						} else {
							$classes[] = sanitize_html_class( $args[ $k . '_lg' ] );
						}
					}
				} else {
					if ( $key == 'font_size' && ! empty( $args[ $key ] ) && $args[ $key ] == 'custom' ) {
						continue;
					}
					if ( ! empty( $args[ $key ] ) ) {
						$classes[] = self::sanitize_html_classes( $args[ $key ] );
					}
				}
			}
		}

		if ( ! empty( $classes ) ) {
			$classes = array_unique( array_filter( array_map( 'trim', $classes ) ) );
		}

		return implode( ' ', $classes );
	}

	/**
	 * Replaces dynamic variable placeholders in a text string.
	 *
	 * Supports: {post_title}, {user_display_name}, {site_title}, etc.
	 * Full syntax: {variable_name:filter:option|fallback_text}
	 *
	 * @param string $text The text containing placeholders.
	 * @return string The text with placeholders replaced.
	 */
	public static function replace_variables( string $text ): string {
		if ( strpos( $text, '{' ) === false ) {
			return $text;
		}

		$pattern = '/\{([a-zA-Z0-9_:]+)(?:\|([^}]+))?\}/';

		return preg_replace_callback(
			$pattern,
			static function ( $matches ) {
				$parts    = explode( ':', $matches[1] );
				$var_name = array_shift( $parts );
				$filters  = $parts;
				$fallback = isset( $matches[2] ) ? $matches[2] : '';

				static $cache = [];
				if ( ! isset( $cache['post'] ) ) {
					$cache['post'] = get_post();
				}
				if ( ! isset( $cache['user'] ) ) {
					$cache['user'] = wp_get_current_user();
				}

				$raw_value = null;

				switch ( true ) {
					case strpos( $var_name, 'post_' ) === 0:
						if ( $cache['post'] ) {
							switch ( $var_name ) {
								case 'post_title': $raw_value = get_the_title( $cache['post'] ); break;
								case 'post_id': $raw_value = $cache['post']->ID; break;
								case 'post_url': $raw_value = get_permalink( $cache['post'] ); break;
								case 'post_slug': $raw_value = $cache['post']->post_name; break;
								case 'post_date': $raw_value = get_the_date( '', $cache['post'] ); break;
								case 'post_modified_date': $raw_value = get_the_modified_date( '', $cache['post'] ); break;
								case 'post_excerpt': $raw_value = get_the_excerpt( $cache['post'] ); break;
								case 'post_content': $raw_value = apply_filters( 'the_content', $cache['post']->post_content ); break;
								case 'post_status': $raw_value = get_post_status( $cache['post'] ); break;
								case 'post_type': $raw_value = get_post_type( $cache['post'] ); break;
								case 'comment_count': $raw_value = get_comments_number( $cache['post'] ); break;
								case 'comments_link': $raw_value = get_comments_link( $cache['post'] ); break;
								case 'post_terms':
									$taxonomy  = ! empty( $filters ) && ! is_numeric( $filters[0] ) ? $filters[0] : 'category';
									$terms     = wp_get_post_terms( $cache['post']->ID, $taxonomy, [ 'fields' => 'names' ] );
									if ( ! is_wp_error( $terms ) ) {
										$raw_value = implode( ', ', $terms );
									}
									break;
							}
						}
						break;

					case strpos( $var_name, 'featured_image_' ) === 0:
						if ( $cache['post'] && has_post_thumbnail( $cache['post'] ) ) {
							$thumb_id = get_post_thumbnail_id( $cache['post'] );
							switch ( $var_name ) {
								case 'featured_image_url': $raw_value = get_the_post_thumbnail_url( $cache['post'], 'full' ); break;
								case 'featured_image_id': $raw_value = $thumb_id; break;
								case 'featured_image_alt': $raw_value = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ); break;
								case 'featured_image_title': $raw_value = get_the_title( $thumb_id ); break;
								case 'featured_image_caption': $raw_value = get_the_post_thumbnail_caption( $cache['post'] ); break;
							}
						}
						break;

					case strpos( $var_name, 'author_' ) === 0:
						if ( $cache['post'] ) {
							$author_id = $cache['post']->post_author;
							switch ( $var_name ) {
								case 'author_name': $raw_value = get_the_author_meta( 'display_name', $author_id ); break;
								case 'author_id': $raw_value = $author_id; break;
								case 'author_bio': $raw_value = get_the_author_meta( 'description', $author_id ); break;
								case 'author_url': $raw_value = get_author_posts_url( $author_id ); break;
								case 'author_website': $raw_value = get_the_author_meta( 'user_url', $author_id ); break;
								case 'author_email': $raw_value = get_the_author_meta( 'user_email', $author_id ); break;
								case 'author_profile_picture_url': $raw_value = get_avatar_url( $author_id ); break;
							}
						}
						break;

					case strpos( $var_name, 'user_' ) === 0:
						if ( $cache['user'] && $cache['user']->exists() ) {
							$user_id = $cache['user']->ID;
							switch ( $var_name ) {
								case 'user_display_name': $raw_value = $cache['user']->display_name; break;
								case 'user_id': $raw_value = $user_id; break;
								case 'user_email': $raw_value = $cache['user']->user_email; break;
								case 'user_first_name': $raw_value = $cache['user']->user_firstname; break;
								case 'user_last_name': $raw_value = $cache['user']->user_lastname; break;
								case 'user_website': $raw_value = $cache['user']->user_url; break;
								case 'user_profile_picture_url': $raw_value = get_avatar_url( $user_id ); break;
							}
						}
						break;

					case strpos( $var_name, 'term_' ) === 0 || strpos( $var_name, 'archive_' ) === 0:
						$queried_object = get_queried_object();
						if ( $queried_object instanceof \WP_Term ) {
							switch ( $var_name ) {
								case 'term_name': $raw_value = $queried_object->name; break;
								case 'term_description': $raw_value = $queried_object->description; break;
								case 'term_url': $raw_value = get_term_link( $queried_object ); break;
								case 'term_id': $raw_value = $queried_object->term_id; break;
								case 'term_post_count': $raw_value = $queried_object->count; break;
							}
						}
						switch ( $var_name ) {
							case 'archive_title': $raw_value = get_the_archive_title(); break;
							case 'archive_description': $raw_value = get_the_archive_description(); break;
							case 'archive_url': if ( $queried_object ) { $raw_value = get_term_link( $queried_object ); } break;
						}
						break;

					default:
						switch ( $var_name ) {
							case 'site_title': $raw_value = get_bloginfo( 'name' ); break;
							case 'site_tagline': $raw_value = get_bloginfo( 'description' ); break;
							case 'site_url': $raw_value = home_url(); break;
							case 'admin_email': $raw_value = get_bloginfo( 'admin_email' ); break;
							case 'wp_version': $raw_value = get_bloginfo( 'version' ); break;
							case 'current_date': $raw_value = wp_date( get_option( 'date_format' ) ); break;
							case 'current_time': $raw_value = wp_date( get_option( 'time_format' ) ); break;
							case 'query_results_count':
								global $wp_query;
								$raw_value = $wp_query->found_posts;
								break;
							case 'request_parameter':
								if ( ! empty( $filters[0] ) && isset( $_REQUEST[ $filters[0] ] ) ) {
									$raw_value = sanitize_text_field( $_REQUEST[ $filters[0] ] );
								}
								break;
						}
						break;
				}

				if ( $raw_value === null ) {
					$raw_value = apply_filters( 'sd_dynamic_data_replace_variable', null, $var_name, $filters, $cache['post'] );
				}

				if ( empty( $raw_value ) && ! is_numeric( $raw_value ) && ! empty( $fallback ) ) {
					return esc_html( $fallback );
				}

				$final_value = $raw_value;
				$is_html     = false;

				if ( ! empty( $filters ) ) {
					if ( in_array( 'link', $filters, true ) ) {
						$link_url = '';
						if ( strpos( $var_name, 'post_' ) === 0 && $cache['post'] ) {
							$link_url = get_permalink( $cache['post'] );
						} elseif ( strpos( $var_name, 'author_' ) === 0 && $cache['post'] ) {
							$link_url = get_author_posts_url( $cache['post']->post_author );
						} elseif ( strpos( $var_name, 'term_' ) === 0 && get_queried_object() instanceof \WP_Term ) {
							$link_url = get_term_link( get_queried_object() );
						}

						if ( ! empty( $link_url ) ) {
							$target_attr = in_array( 'newTab', $filters, true ) ? ' target="_blank" rel="noopener noreferrer"' : '';
							$final_value = sprintf( '<a href="%s"%s>%s</a>', esc_url( $link_url ), $target_attr, esc_html( $raw_value ) );
							$is_html     = true;
						}
					}

					foreach ( $filters as $filter ) {
						if ( is_numeric( $filter ) ) {
							if ( is_string( $final_value ) ) {
								$final_value = wp_trim_words( strip_tags( $final_value ), (int) $filter, '...' );
							}
						} elseif ( ! in_array( $filter, [ 'link', 'newTab' ], true ) ) {
							if ( in_array( $var_name, [ 'post_date', 'post_modified_date', 'current_date' ], true ) && is_string( $final_value ) ) {
								$final_value = wp_date( $filter, strtotime( $final_value ) );
							}
						}
					}
				}

				return $is_html ? $final_value : ( is_scalar( $final_value ) ? esc_html( $final_value ) : '' );
			},
			$text
		);
	}

	// -------------------------------------------------------------------------
	// Style helpers
	// -------------------------------------------------------------------------

	/**
	 * Build an inline CSS style string from a widget/shortcode arguments array.
	 *
	 * This needs to be kept in sync with the JS version in includes/helpers/gutenberg-block-helpers.php
	 *
	 * @param array $args Widget arguments.
	 * @return string Inline style attribute value (e.g. "background-color:#fff;font-size:1.2rem;").
	 */
	public static function build_aui_styles( $args ) {
		$styles = array();

		if ( ! empty( $args['bg'] ) && $args['bg'] !== '' ) {
			if ( $args['bg'] == 'custom-color' ) {
				$styles['background-color'] = $args['bg_color'];
			} elseif ( $args['bg'] == 'custom-gradient' ) {
				$styles['background-image'] = $args['bg_gradient'];

				if ( ! empty( $args['bg_on_text'] ) && $args['bg_on_text'] ) {
					$styles['background-clip']         = 'text';
					$styles['-webkit-background-clip'] = 'text';
					$styles['text-fill-color']         = 'transparent';
					$styles['-webkit-text-fill-color'] = 'transparent';
				}
			}
		}

		if ( ! empty( $args['bg_image'] ) && $args['bg_image'] !== '' ) {
			$hasImage = true;
			if ( ! empty( $styles['background-color'] ) && $args['bg'] == 'custom-color' ) {
				$styles['background-image']      = 'url(' . $args['bg_image'] . ')';
				$styles['background-blend-mode'] = 'overlay';
			} elseif ( ! empty( $styles['background-image'] ) && $args['bg'] == 'custom-gradient' ) {
				$styles['background-image'] .= ',url(' . $args['bg_image'] . ')';
			} elseif ( ! empty( $args['bg'] ) && $args['bg'] != '' && $args['bg'] != 'transparent' ) {
				$hasImage = false;
			} else {
				$styles['background-image'] = 'url(' . $args['bg_image'] . ')';
			}

			if ( $hasImage ) {
				$styles['background-size'] = 'cover';

				if ( ! empty( $args['bg_image_fixed'] ) && $args['bg_image_fixed'] ) {
					$styles['background-attachment'] = 'fixed';
				}
			}

			if ( $hasImage && ! empty( $args['bg_image_xy'] ) && ! empty( $args['bg_image_xy']['x'] ) ) {
				$styles['background-position'] = ( $args['bg_image_xy']['x'] * 100 ) . '% ' . ( $args['bg_image_xy']['y'] * 100 ) . '%';
			}
		}

		if ( ! empty( $args['sticky_offset_top'] ) && $args['sticky_offset_top'] !== '' ) {
			$styles['top'] = absint( $args['sticky_offset_top'] );
		}

		if ( ! empty( $args['sticky_offset_bottom'] ) && $args['sticky_offset_bottom'] !== '' ) {
			$styles['bottom'] = absint( $args['sticky_offset_bottom'] );
		}

		if ( ! empty( $args['font_size_custom'] ) && $args['font_size_custom'] !== '' ) {
			$styles['font-size'] = (float) $args['font_size_custom'] . 'rem';
		}

		if ( ! empty( $args['text_color_custom'] ) && $args['text_color_custom'] !== '' ) {
			$styles['color'] = esc_attr( $args['text_color_custom'] );
		}

		if ( ! empty( $args['font_line_height'] ) && $args['font_line_height'] !== '' ) {
			$styles['line-height'] = esc_attr( $args['font_line_height'] );
		}

		if ( ! empty( $args['max_height'] ) && $args['max_height'] !== '' ) {
			$styles['max-height'] = esc_attr( $args['max_height'] );
		}

		$style_string = '';
		if ( ! empty( $styles ) ) {
			foreach ( $styles as $key => $val ) {
				$style_string .= esc_attr( $key ) . ':' . esc_attr( $val ) . ';';
			}
		}

		return $style_string;
	}

	/**
	 * Build hover CSS rules from args and enqueue them via wp_add_inline_style().
	 *
	 * @param array $args       Widget arguments.
	 * @param bool  $is_preview Whether rendering inside the block editor preview.
	 * @return string Always returns an empty string (CSS is enqueued, not returned).
	 */
	public static function build_hover_styles( $args, $is_preview = false ) {
		$rules = '';

		if ( ! empty( $args['styleid'] ) ) {
			$styleid = $is_preview ? 'html .editor-styles-wrapper .' . esc_attr( $args['styleid'] ) : 'html .' . esc_attr( $args['styleid'] );

			if ( ! empty( $args['text_color_hover'] ) ) {
				$key    = 'custom' === $args['text_color_hover'] && ! empty( $args['text_color_hover_custom'] ) ? 'text_color_hover_custom' : 'text_color_hover';
				$color  = self::get_color_from_var( $args[ $key ] );
				$rules .= $styleid . ':hover {color: ' . $color . ' !important;} ';
			}

			if ( ! empty( $args['bg_hover'] ) ) {
				if ( 'custom-gradient' === $args['bg_hover'] ) {
					$color  = $args['bg_hover_gradient'];
					$rules .= $styleid . ':hover {background-image: ' . $color . ' !important;} ';
					$rules .= $styleid . '.btn:hover {border-color: transparent !important;} ';
				} else {
					$key    = 'custom-color' === $args['bg_hover'] ? 'bg_hover_color' : 'bg_hover';
					$color  = self::get_color_from_var( $args[ $key ] );
					$rules .= $styleid . ':hover {background: ' . $color . ' !important;} ';
					$rules .= $styleid . '.btn:hover {border-color: ' . $color . ' !important;} ';
				}
			}
		}

		if ( ! $rules ) {
			return '';
		}
//
//		$handle = wp_style_is( 'super-duper-universal-block-editor', 'registered' )
//			? 'super-duper-universal-block-editor'
//			: 'sd-hover-styles';
//
//		if ( ! wp_style_is( $handle, 'registered' ) ) {
//			wp_register_style( $handle, false, array(), false, false );
//		}
//
//		wp_add_inline_style( $handle, $rules );

//		$rules = 'body{display:none;};';
//		echo '###'.$rules;exit;
//		wp_add_inline_style( 'ayecode-ui', $rules );

		return '<style>'.$rules.'</style>';
	}

	/**
	 * Convert a colour value to a CSS variable reference where applicable.
	 *
	 * @param string $var Colour slug or hex value.
	 * @return string CSS variable string or original hex value.
	 */
	public static function get_color_from_var( $var ) {
		if ( strpos( $var, '#' ) === false ) {
			$var = defined( 'BLOCKSTRAP_BLOCKS_VERSION' )
				? 'var(--wp--preset--color--' . esc_attr( $var ) . ')'
				: 'var(--' . esc_attr( $var ) . ')';
		}

		return $var;
	}

	/**
	 * Sanitize one or more space-separated HTML class names.
	 *
	 * @param string|array $classes Space-separated class string or array of class names.
	 * @param string       $sep     Separator used when $classes is a string (default ' ').
	 * @return string Sanitized, space-separated class string.
	 */
	public static function sanitize_html_classes( $classes, $sep = ' ' ) {
		$return = '';

		if ( ! is_array( $classes ) ) {
			$classes = explode( $sep, $classes );
		}

		if ( ! empty( $classes ) ) {
			foreach ( $classes as $class ) {
				$return .= sanitize_html_class( $class ) . ' ';
			}
		}

		return $return;
	}

	/**
	 * Return the list of argument keys used when building the Bootstrap utility class string.
	 *
	 * Keys suffixed with "-MTD" are treated as responsive (mobile/tablet/desktop) variants.
	 *
	 * @return array
	 */
	public static function get_class_build_keys() {
		$keys = array(
			'container',
			'position',
			'flex_direction',
			'shadow',
			'rounded',
			'nav_style',
			'horizontal_alignment',
			'nav_fill',
			'width',
			'font_weight',
			'font_size',
			'font_case',
			'css_class',
			'flex_align_items-MTD',
			'flex_justify_content-MTD',
			'flex_align_self-MTD',
			'flex_order-MTD',
			'styleid',
			'border_opacity',
			'border_width',
			'border_type',
			'opacity',
			'zindex',
			'flex_wrap-MTD',
			'h100',
			'overflow',
			'scrollbars',
			'float-MTD',
			'height-MTD',
			'width-MTD',
		);

		return apply_filters( 'sd_class_build_keys', $keys );
	}

	// -------------------------------------------------------------------------
	// Visibility / roles helpers
	// -------------------------------------------------------------------------

	/**
	 * Return an array of WordPress user role slugs => labels.
	 *
	 * @param array $exclude Role slugs to exclude from the result.
	 * @return array
	 */
	public static function user_roles_options( $exclude = array() ) {
		$user_roles = array();

		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . '/wp-admin/includes/user.php';
		}

		$roles = get_editable_roles();

		foreach ( $roles as $role => $data ) {
			if ( ! ( ! empty( $exclude ) && in_array( $role, $exclude ) ) ) {
				$user_roles[ esc_attr( $role ) ] = translate_user_role( $data['name'] );
			}
		}

		$user_roles['logged_out'] = __( 'Guest (logged out)', 'ayecode-connect' );

		return apply_filters( 'sd_user_roles_options', $user_roles );
	}

	/**
	 * Return the visibility condition rule type options.
	 *
	 * @return array
	 */
	public static function visibility_rules_options() {
		$options = array(
			'logged_in'   => __( 'Logged In', 'ayecode-connect' ),
			'logged_out'  => __( 'Logged Out', 'ayecode-connect' ),
			'post_author' => __( 'Post Author', 'ayecode-connect' ),
			'user_roles'  => __( 'Specific User Roles', 'ayecode-connect' ),
		);

		if ( class_exists( 'GeoDirectory' ) ) {
			$options['gd_field'] = __( 'GD Field', 'ayecode-connect' );
		}

		return apply_filters( 'sd_visibility_rules_options', $options );
	}

	/**
	 * Return GeoDirectory custom field options for visibility conditions.
	 *
	 * @return array
	 */
	public static function visibility_gd_field_options() {
		$fields = geodir_post_custom_fields( '', 'all', 'all', 'none' );

		$keys = array();
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( apply_filters( 'geodir_badge_field_skip_key', false, $field ) ) {
					continue;
				}

				$keys[ $field['htmlvar_name'] ] = $field['htmlvar_name'] . ' ( ' . __( $field['admin_title'], 'geodirectory' ) . ' )';

				if ( $field['htmlvar_name'] == 'address' && ( $address_fields = geodir_post_meta_address_fields( '' ) ) ) {
					foreach ( $address_fields as $_field => $args ) {
						if ( $_field != 'map_directions' && $_field != 'street' ) {
							$keys[ $_field ] = $_field . ' ( ' . $args['frontend_title'] . ' )';
						}
					}
				}
			}
		}

		$standard_fields = self::visibility_gd_standard_field_options();

		if ( ! empty( $standard_fields ) ) {
			foreach ( $standard_fields as $key => $option ) {
				$keys[ $key ] = $option;
			}
		}

		$options = apply_filters( 'geodir_badge_field_keys', $keys );

		return apply_filters( 'sd_visibility_gd_field_options', $options );
	}

	/**
	 * Return GeoDirectory standard post field options for visibility conditions.
	 *
	 * @param string $post_type Optional post type slug.
	 * @return array
	 */
	public static function visibility_gd_standard_field_options( $post_type = '' ) {
		$fields  = self::visibility_gd_standard_fields( $post_type );
		$options = array();

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['frontend_title'] ) ) {
				$options[ $key ] = $key . ' ( ' . $field['frontend_title'] . ' )';
			}
		}

		return apply_filters( 'sd_visibility_gd_standard_field_options', $options, $fields );
	}

	/**
	 * Return GeoDirectory standard post meta fields (excluding events and post_link).
	 *
	 * @param string $post_type Optional post type slug.
	 * @return array
	 */
	public static function visibility_gd_standard_fields( $post_type = '' ) {
		$standard_fields = geodir_post_meta_standard_fields( $post_type );
		$fields          = array();

		foreach ( $standard_fields as $key => $field ) {
			if ( $key != 'post_link' && strpos( $key, 'event' ) === false && ! empty( $field['frontend_title'] ) ) {
				$fields[ $key ] = $field;
			}
		}

		return apply_filters( 'sd_visibility_gd_standard_fields', $fields );
	}

	/**
	 * Return the comparison condition options for visibility field rules.
	 *
	 * @return array
	 */
	public static function visibility_field_condition_options() {
		$options = array(
			'is_empty'        => __( 'is empty', 'ayecode-connect' ),
			'is_not_empty'    => __( 'is not empty', 'ayecode-connect' ),
			'is_equal'        => __( 'is equal', 'ayecode-connect' ),
			'is_not_equal'    => __( 'is not equal', 'ayecode-connect' ),
			'is_greater_than' => __( 'is greater than', 'ayecode-connect' ),
			'is_less_than'    => __( 'is less than', 'ayecode-connect' ),
			'is_contains'     => __( 'is contains', 'ayecode-connect' ),
			'is_not_contains' => __( 'is not contains', 'ayecode-connect' ),
		);

		return apply_filters( 'sd_visibility_field_condition_options', $options );
	}

	/**
	 * Return visibility output action options.
	 *
	 * @return array
	 */
	public static function visibility_output_options() {
		$options = array(
			''              => __( 'Show Block', 'ayecode-connect' ),
			'hide'          => __( 'Hide Block', 'ayecode-connect' ),
			'message'       => __( 'Show Custom Message', 'ayecode-connect' ),
			'page'          => __( 'Show Page Content', 'ayecode-connect' ),
			'template_part' => __( 'Show Template Part', 'ayecode-connect' ),
		);

		return apply_filters( 'sd_visibility_output_options', $options );
	}

	// -------------------------------------------------------------------------
	// Template / page helpers
	// -------------------------------------------------------------------------

	/** @var array|null In-process cache for template_page_options(). */
	private static $tmpl_page_options = null;

	/** @var array|null In-process cache for template_part_options(). */
	private static $tmpl_part_options = null;

	/** @var array In-process cache for get_template_part_by_slug(). */
	private static $tmpl_part_by_slug = array();

	/**
	 * Return an array of published page options for use in a select field.
	 *
	 * @param array $args {
	 *   @type bool   $nocache       Skip cache and re-query. Default false.
	 *   @type bool   $with_slug     Append slug to label. Default false.
	 *   @type string $default_label Label for the empty option. Default 'Select Page…'.
	 * }
	 * @return array
	 */
	public static function template_page_options( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'nocache'       => false,
			'with_slug'     => false,
			'default_label' => __( 'Select Page...', 'ayecode-connect' ),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( self::$tmpl_page_options !== null && empty( $args['nocache'] ) ) {
			return self::$tmpl_page_options;
		}

		$exclude_pages = array();
		if ( $page_on_front = get_option( 'page_on_front' ) ) {
			$exclude_pages[] = $page_on_front;
		}
		if ( $page_for_posts = get_option( 'page_for_posts' ) ) {
			$exclude_pages[] = $page_for_posts;
		}

		$exclude_pages_placeholders = '';
		if ( ! empty( $exclude_pages ) ) {
			$exclude_pages_placeholders = implode( ',', array_fill( 0, count( $exclude_pages ), '%d' ) );
		}

		$sql = "SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish'";

		if ( ! empty( $exclude_pages ) ) {
			$sql .= " AND ID NOT IN ($exclude_pages_placeholders)";
		}

		$sql .= ' ORDER BY post_title ASC';

		$limit = (int) apply_filters( 'sd_template_page_options_limit', 500, $args );
		if ( $limit > 0 ) {
			$sql .= ' LIMIT ' . $limit;
		}

		$pages = $exclude_pages_placeholders
			? $wpdb->get_results( $wpdb->prepare( $sql, ...$exclude_pages ) )
			: $wpdb->get_results( $sql );

		$options = ! empty( $args['default_label'] ) ? array( '' => $args['default_label'] ) : array();

		if ( ! empty( $pages ) ) {
			foreach ( $pages as $page ) {
				$title                  = ! empty( $args['with_slug'] )
					? $page->post_title . ' (' . $page->post_name . ')'
					: $page->post_title . ' (#' . $page->ID . ')';
				$options[ $page->ID ] = $title;
			}
		}

		self::$tmpl_page_options = $options;

		return apply_filters( 'sd_template_page_options', $options, $args );
	}

	/**
	 * Return an array of published block template-part options for use in a select field.
	 *
	 * @param array $args Reserved for future use / filter context.
	 * @return array
	 */
	public static function template_part_options( $args = array() ) {
		if ( self::$tmpl_part_options !== null ) {
			return self::$tmpl_part_options;
		}

		$options = array( '' => __( 'Select Template Part...', 'ayecode-connect' ) );

		$parts = get_block_templates( array(), 'wp_template_part' );

		if ( ! empty( $parts ) ) {
			foreach ( $parts as $part ) {
				$options[ $part->slug ] = $part->title . ' (#' . $part->slug . ')';
			}
		}

		self::$tmpl_part_options = $options;

		return apply_filters( 'sd_template_part_options', $options, $args );
	}

	/**
	 * Return the block template-part object for a given slug.
	 *
	 * @param string $slug Template-part slug.
	 * @return object|array Template part object, or empty array if not found.
	 */
	public static function get_template_part_by_slug( $slug ) {
		if ( isset( self::$tmpl_part_by_slug[ $slug ] ) ) {
			return self::$tmpl_part_by_slug[ $slug ];
		}

		$template_query = get_block_templates( array( 'slug__in' => array( $slug ) ), 'wp_template_part' );
		$query_post     = ! empty( $template_query ) ? $template_query[0] : array();
		$template_part  = ! empty( $query_post ) && $query_post->status == 'publish' ? $query_post : array();

		self::$tmpl_part_by_slug[ $slug ] = $template_part;

		return apply_filters( 'sd_get_template_part_by_slug', $template_part, $slug );
	}

	/**
	 * Return the rendered content of a page by ID.
	 *
	 * @param int $page_id Post ID of the page.
	 * @return string Rendered HTML content.
	 */
	public static function get_page_content( $page_id ) {
		$content = $page_id > 0 ? get_post_field( 'post_content', (int) $page_id ) : '';

		$bypass_content = apply_filters( 'sd_bypass_page_content', '', $content, $page_id );
		if ( $bypass_content ) {
			return $bypass_content;
		}

		$content = do_shortcode( $content );

		if ( function_exists( 'do_blocks' ) ) {
			$content = do_blocks( $content );
		}

		return apply_filters( 'sd_get_page_content', $content, $page_id );
	}

	/**
	 * Return the rendered content of a block template-part by slug.
	 *
	 * @param string $template_part Template-part slug.
	 * @return string Rendered HTML content.
	 */
	public static function get_template_part_content( $template_part ) {
		$template_part_post = $template_part ? self::get_template_part_by_slug( $template_part ) : array();
		$content            = ! empty( $template_part_post ) ? $template_part_post->content : '';

		$bypass_content = apply_filters( 'sd_bypass_template_part_content', '', $content, $template_part );
		if ( $bypass_content ) {
			return $bypass_content;
		}

		$content = do_shortcode( $content );

		if ( function_exists( 'do_blocks' ) ) {
			$content = do_blocks( $content );
		}

		return apply_filters( 'sd_get_template_part_content', $content, $template_part );
	}

	// -------------------------------------------------------------------------
	// Block visibility / rule evaluation
	// -------------------------------------------------------------------------

	/**
	 * Filter callback on `render_block` — applies block visibility conditions.
	 *
	 * @param string   $block_content The rendered block content.
	 * @param array    $block         The parsed block array.
	 * @param mixed    $instance      WP_Block instance.
	 * @return string
	 */
	public static function render_block( $block_content, $block, $instance = '' ) {
		if ( empty( $block['attrs']['visibility_conditions'] ) ) {
			return $block_content;
		}

		$attributes  = json_decode( $block['attrs']['visibility_conditions'], true );
		$rules       = ! empty( $attributes ) ? self::block_parse_rules( $attributes ) : array();
		$valid_rules = self::visibility_rules_options();

		if ( ! empty( $rules ) ) {
			foreach ( $rules as $key => $rule ) {
				if ( ! isset( $valid_rules[ $rule['type'] ] ) ) {
					unset( $rules[ $key ] );
				}
			}
		}

		if ( empty( $rules ) ) {
			return $block_content;
		}

		$check_rules    = null;
		$_block_content = $block_content;

		if ( ! empty( $rules ) && ( ! empty( $attributes['output'] ) || ! empty( $attributes['outputN'] ) ) ) {
			$check_rules = self::block_check_rules( $rules );

			if ( $check_rules ) {
				$output_condition = ! empty( $attributes['output'] ) ? $attributes['output'] : array();
			} else {
				$output_condition = ! empty( $attributes['outputN'] ) ? $attributes['outputN'] : array();
			}

			if ( ! empty( $output_condition ) && ! empty( $output_condition['type'] ) ) {
				$valid_type = false;
				$content    = '';

				switch ( $output_condition['type'] ) {
					case 'hide':
						$valid_type = true;
						$content    = '';
						break;
					case 'message':
						$valid_type = true;
						if ( isset( $output_condition['message'] ) ) {
							$content = $output_condition['message'] != '' ? __( stripslashes( $output_condition['message'] ), 'ayecode-connect' ) : $output_condition['message'];
							if ( ! empty( $output_condition['message_type'] ) ) {
								$content = aui()->alert( array(
									'type'    => $output_condition['message_type'],
									'content' => $content,
								) );
							}
						}
						break;
					case 'page':
						$valid_type = true;
						$page_id    = ! empty( $output_condition['page'] ) ? absint( $output_condition['page'] ) : 0;
						$content    = self::get_page_content( $page_id );
						break;
					case 'template_part':
						$valid_type    = true;
						$template_part = ! empty( $output_condition['template_part'] ) ? $output_condition['template_part'] : '';
						$content       = self::get_template_part_content( $template_part );
						break;
					default:
						break;
				}

				if ( $valid_type ) {
					$block_content = '<div class="' . esc_attr( wp_get_block_default_classname( $instance->name ) ) . ' sd-block-has-rule' . ( $output_condition['type'] == 'hide' ? ' sd-block-hide-rule' : '' ) . '">' . $content . '</div>';
				}
			}
		}

		return apply_filters( 'sd_render_block_visibility_content', $block_content, $_block_content, $attributes, $block, $instance, $check_rules );
	}

	/**
	 * Parse a visibility_conditions attribute array into a flat list of rule arrays.
	 *
	 * @param array $attrs Decoded visibility_conditions JSON.
	 * @return array
	 */
	public static function block_parse_rules( $attrs ) {
		$rules = array();

		if ( ! empty( $attrs ) && is_array( $attrs ) ) {
			$attrs_keys = array_keys( $attrs );

			for ( $i = 1; $i <= count( $attrs_keys ); $i++ ) {
				if ( ! empty( $attrs[ 'rule' . $i ] ) && is_array( $attrs[ 'rule' . $i ] ) ) {
					$rules[] = $attrs[ 'rule' . $i ];
				}
			}
		}

		return apply_filters( 'sd_block_parse_rules', $rules, $attrs );
	}

	/**
	 * Evaluate all visibility rules and return the combined boolean result.
	 *
	 * @param array $rules List of rule arrays from block_parse_rules().
	 * @return bool True if all rules pass (block should be shown/acted on).
	 */
	public static function block_check_rules( $rules ) {
		if ( ! ( is_array( $rules ) && ! empty( $rules ) ) ) {
			return true;
		}

		$match = true;
		foreach ( $rules as $rule ) {
			$match = apply_filters( 'sd_block_check_rule', true, $rule );

			if ( ! $match ) {
				break;
			}
		}

		return apply_filters( 'sd_block_check_rules', $match, $rules );
	}

	/**
	 * Filter callback on `sd_block_check_rule` — evaluates a single rule.
	 *
	 * @param bool  $match Current match state.
	 * @param array $rule  Single rule array with 'type' key.
	 * @return bool
	 */
	public static function block_check_rule( $match, $rule ) {
		global $post;

		if ( $match && ! empty( $rule['type'] ) ) {
			switch ( $rule['type'] ) {
				case 'logged_in':
					$match = (bool) is_user_logged_in();
					break;
				case 'logged_out':
					$match = ! is_user_logged_in();
					break;
				case 'post_author':
					if ( ! empty( $post ) && $post->post_type != 'page' && ! empty( $post->post_author ) && is_user_logged_in() ) {
						$match = (int) $post->post_author === (int) get_current_user_id();
					} else {
						$match = false;
					}
					break;
				case 'user_roles':
					$match = false;

					if ( ! empty( $rule['user_roles'] ) ) {
						$user_roles = is_scalar( $rule['user_roles'] ) ? explode( ',', $rule['user_roles'] ) : $rule['user_roles'];

						if ( is_array( $user_roles ) ) {
							$user_roles = array_filter( array_map( 'trim', $user_roles ) );
						}

						if ( ! empty( $user_roles ) && is_array( $user_roles ) ) {
							if ( is_user_logged_in() && ( $current_user = wp_get_current_user() ) ) {
								$current_user_roles = $current_user->roles;

								foreach ( $user_roles as $role ) {
									if ( in_array( $role, $current_user_roles ) ) {
										$match = true;
									}
								}
							} else {
								if ( in_array( 'logged_out', $user_roles ) ) {
									$match = true;
								}
							}
						}
					}
					break;
				case 'gd_field':
					$match = self::block_check_rule_gd_field( $rule );
					break;
				default:
					$match = apply_filters( 'sd_block_check_custom_rule', $match, $rule );
					break;
			}
		}

		return $match;
	}

	/**
	 * Evaluate a GeoDirectory field visibility rule.
	 *
	 * @param array $rule Rule array containing 'field', 'condition', 'search'.
	 * @return bool
	 */
	public static function block_check_rule_gd_field( $rule ) {
		global $gd_post;

		$match_found = false;

		if ( class_exists( 'GeoDirectory' ) && ! empty( $gd_post->ID ) && ! empty( $rule['field'] ) && ! empty( $rule['condition'] ) ) {
			$args                    = array();
			$args['block_visibility'] = true;
			$args['key']             = $rule['field'];
			$args['condition']       = $rule['condition'];
			$args['search']          = isset( $rule['search'] ) ? $rule['search'] : '';

			if ( $args['key'] == 'street' ) {
				$args['key'] = 'address';
			}

			$match_field  = $_match_field = $args['key'];

			if ( $match_field == 'address' ) {
				$match_field = 'street';
			} elseif ( $match_field == 'post_images' ) {
				$match_field = 'featured_image';
			}

			$find_post      = $gd_post;
			$find_post_keys = ! empty( $find_post ) ? array_keys( (array) $find_post ) : array();

			if ( ! empty( $find_post->ID ) && ! in_array( 'post_category', $find_post_keys ) ) {
				$find_post      = geodir_get_post_info( (int) $find_post->ID );
				$find_post_keys = ! empty( $find_post ) ? array_keys( (array) $find_post ) : array();
			}

			if ( $match_field === '' || ( ! empty( $find_post_keys ) && ( in_array( $match_field, $find_post_keys ) || in_array( $_match_field, $find_post_keys ) ) ) ) {
				$address_fields  = array( 'street2', 'neighbourhood', 'city', 'region', 'country', 'zip', 'latitude', 'longitude' );
				$field           = array();
				$empty_field     = false;
				$standard_fields = self::visibility_gd_standard_fields();

				if ( $match_field && ! in_array( $match_field, array_keys( $standard_fields ) ) && ! in_array( $match_field, $address_fields ) ) {
					$package_id = geodir_get_post_package_id( $find_post->ID, $find_post->post_type );
					$fields     = geodir_post_custom_fields( $package_id, 'all', $find_post->post_type, 'none' );

					foreach ( $fields as $field_info ) {
						if ( $match_field == $field_info['htmlvar_name'] || $_match_field == $field_info['htmlvar_name'] ) {
							$field = $field_info;
							break;
						}
					}

					if ( empty( $field ) ) {
						$empty_field = true;
					}
				}

				if ( in_array( $match_field, $address_fields ) && ( $address_fields = geodir_post_meta_address_fields( '' ) ) ) {
					if ( ! empty( $address_fields[ $match_field ] ) ) {
						$field = $address_fields[ $match_field ];
					}
				} elseif ( in_array( $match_field, array_keys( $standard_fields ) ) ) {
					if ( ! empty( $standard_fields[ $match_field ] ) ) {
						$field = $standard_fields[ $match_field ];
					}
				}

				$search      = self::gd_field_rule_search( $args['search'], $find_post->post_type, $rule, $field, $find_post );
				$is_date     = ( ! empty( $field['type'] ) && $field['type'] == 'datepicker' ) || in_array( $match_field, array( 'post_date', 'post_modified' ) );
				$is_date     = apply_filters( 'geodir_post_badge_is_date', $is_date, $match_field, $field, $args, $find_post );
				$match_value = isset( $find_post->{$match_field} ) && empty( $empty_field ) ? esc_attr( trim( $find_post->{$match_field} ) ) : '';
				$match_found = $match_field === '' ? true : false;

				if ( ! $match_found ) {
					if ( ( $match_field == 'post_date' || $match_field == 'post_modified' ) && ( empty( $args['condition'] ) || $args['condition'] == 'is_greater_than' || $args['condition'] == 'is_less_than' ) ) {
						if ( strpos( $search, '+' ) === false && strpos( $search, '-' ) === false ) {
							$search = '+' . $search;
						}
						$the_time   = $match_field == 'post_modified' ? get_the_modified_date( 'Y-m-d', $find_post ) : get_the_time( 'Y-m-d', $find_post );
						$until_time = strtotime( $the_time . ' ' . $search . ' days' );
						$now_time   = strtotime( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) );
						if ( ( empty( $args['condition'] ) || $args['condition'] == 'is_less_than' ) && $until_time > $now_time ) {
							$match_found = true;
						} elseif ( $args['condition'] == 'is_greater_than' && $until_time < $now_time ) {
							$match_found = true;
						}
					} else {
						switch ( $args['condition'] ) {
							case 'is_equal':        $match_found = (bool) ( $search != '' && $match_value == $search ); break;
							case 'is_not_equal':    $match_found = (bool) ( $search != '' && $match_value != $search ); break;
							case 'is_greater_than': $match_found = (bool) ( $search != '' && is_numeric( $search ) && is_numeric( $match_value ) && $match_value > $search ); break;
							case 'is_less_than':    $match_found = (bool) ( $search != '' && is_numeric( $search ) && is_numeric( $match_value ) && $match_value < $search ); break;
							case 'is_empty':        $match_found = (bool) ( $match_value === '' || $match_value === false || $match_value === '0' || is_null( $match_value ) ); break;
							case 'is_not_empty':    $match_found = (bool) ( $match_value !== '' && $match_value !== false && $match_value !== '0' && ! is_null( $match_value ) ); break;
							case 'is_contains':     $match_found = (bool) ( $search != '' && stripos( $match_value, $search ) !== false ); break;
							case 'is_not_contains': $match_found = (bool) ( $search != '' && stripos( $match_value, $search ) === false ); break;
						}
					}
				}

				$match_found = apply_filters( 'geodir_post_badge_check_match_found', $match_found, $args, $find_post );
			} else {
				$field   = array();
				$search  = self::gd_field_rule_search( $args['search'], $find_post->post_type, $rule, $field, $find_post );

				$match_value = '';
				$match_found = $match_field === '' ? true : false;

				if ( ! $match_found ) {
					switch ( $args['condition'] ) {
						case 'is_equal':        $match_found = (bool) ( $search != '' && $match_value == $search ); break;
						case 'is_not_equal':    $match_found = (bool) ( $search != '' && $match_value != $search ); break;
						case 'is_greater_than': $match_found = false; break;
						case 'is_less_than':    $match_found = false; break;
						case 'is_empty':        $match_found = true; break;
						case 'is_not_empty':    $match_found = false; break;
						case 'is_contains':     $match_found = false; break;
						case 'is_not_contains': $match_found = false; break;
					}
				}

				$match_found = apply_filters( 'geodir_post_badge_check_match_found_empty', $match_found, $args, $find_post );
			}
		}

		return $match_found;
	}

	/**
	 * Resolve dynamic search tokens in a GD field visibility rule.
	 *
	 * @param string $search    Raw search value (may contain tokens like 'date_today').
	 * @param string $post_type Post type slug.
	 * @param array  $rule      Full rule array.
	 * @param array  $field     Field definition array.
	 * @param mixed  $gd_post   GeoDirectory post object.
	 * @return string Resolved search value.
	 */
	public static function gd_field_rule_search( $search, $post_type, $rule, $field = array(), $gd_post = array() ) {
		global $post;

		if ( ! $search ) {
			return $search;
		}

		$orig_search = $search;
		$_search     = strtolower( $search );

		if ( ! empty( $rule['field'] ) && $rule['field'] == 'post_author' ) {
			if ( $search == 'current_user' ) {
				$search = is_user_logged_in() ? (int) get_current_user_id() : - 1;
			} elseif ( $search == 'current_author' ) {
				$search = ( ! empty( $post ) && $post->post_type != 'page' && isset( $post->post_author ) ) ? absint( $post->post_author ) : - 1;
			}
		} elseif ( $_search == 'date_today' ) {
			$search = date( 'Y-m-d' );
		} elseif ( $_search == 'date_tomorrow' ) {
			$search = date( 'Y-m-d', strtotime( '+1 day' ) );
		} elseif ( $_search == 'date_yesterday' ) {
			$search = date( 'Y-m-d', strtotime( '-1 day' ) );
		} elseif ( $_search == 'time_his' ) {
			$search = date( 'H:i:s' );
		} elseif ( $_search == 'time_hi' ) {
			$search = date( 'H:i' );
		} elseif ( $_search == 'datetime_now' ) {
			$search = date( 'Y-m-d H:i:s' );
		} elseif ( strpos( $_search, 'datetime_after_' ) === 0 ) {
			$_searches = explode( 'datetime_after_', $_search, 2 );
			$search    = ! empty( $_searches[1] ) ? date( 'Y-m-d H:i:s', strtotime( '+ ' . str_replace( '_', ' ', $_searches[1] ) ) ) : date( 'Y-m-d H:i:s' );
		} elseif ( strpos( $_search, 'datetime_before_' ) === 0 ) {
			$_searches = explode( 'datetime_before_', $_search, 2 );
			$search    = ! empty( $_searches[1] ) ? date( 'Y-m-d H:i:s', strtotime( '- ' . str_replace( '_', ' ', $_searches[1] ) ) ) : date( 'Y-m-d H:i:s' );
		} elseif ( strpos( $_search, 'date_after_' ) === 0 ) {
			$_searches = explode( 'date_after_', $_search, 2 );
			$search    = ! empty( $_searches[1] ) ? date( 'Y-m-d', strtotime( '+ ' . str_replace( '_', ' ', $_searches[1] ) ) ) : date( 'Y-m-d' );
		} elseif ( strpos( $_search, 'date_before_' ) === 0 ) {
			$_searches = explode( 'date_before_', $_search, 2 );
			$search    = ! empty( $_searches[1] ) ? date( 'Y-m-d', strtotime( '- ' . str_replace( '_', ' ', $_searches[1] ) ) ) : date( 'Y-m-d' );
		}

		return apply_filters( 'sd_gd_field_rule_search', $search, $post_type, $rule, $orig_search );
	}

	// -------------------------------------------------------------------------
	// Field helpers
	// -------------------------------------------------------------------------

	/**
	 * Build an element_require conditional expression for shape-divider fields.
	 *
	 * Returns a parenthesised OR expression over every shape whose supported-feature
	 * list contains $key. Moved from ShapeFields::element_require_string().
	 *
	 * @param array  $args Array of shape => supported-keys pairs.
	 * @param string $key  The feature key to check (e.g. 'flip', 'invert').
	 * @param string $type The field-prefix used in the expression (e.g. 'sd').
	 * @return string
	 */
	public static function element_require( array $args, string $key, string $type ): string {
		$requires = [];

		foreach ( $args as $shape => $supported ) {
			if ( in_array( $key, $supported, true ) ) {
				$requires[] = '[%' . $type . '%]=="' . $shape . '"';
			}
		}

		return $requires ? '(' . implode( ' || ', $requires ) . ')' : '';
	}

	// -------------------------------------------------------------------------
	// Shortcode helpers
	// -------------------------------------------------------------------------

	/**
	 * Extract the shortcode tag slug from a shortcode string or bare tag name.
	 *
	 * @param string $str Shortcode string (e.g. "[bs_alert text='Hi']") or bare tag.
	 * @return string The tag slug.
	 */
	public static function get_shortcode_slug( $str ) {
		if ( isset( $str[0] ) && $str[0] === '[' ) {
			$str = substr( $str, 1 );
		}

		return strtok( $str, ' ' );
	}

	/**
	 * Build a shortcode string from a tag name, attributes array, and optional content.
	 *
	 * @param string $name    Shortcode tag name (or full "[tag …]" string — slug is extracted).
	 * @param array  $args    Attribute key/value pairs.
	 * @param string $content Content to wrap between opening and closing tags.
	 * @return string The compiled shortcode string, or '' if $name is empty.
	 */
	public static function build_shortcode( $name, $args = array(), $content = '' ) {
		if ( ! $name ) {
			return '';
		}

		$name       = self::get_shortcode_slug( $name );
		$attributes = '';

		if ( ! empty( $args ) ) {
			unset( $args['content'], $args['sd_shortcode'], $args['sd_shortcode_close'] );

			foreach ( $args as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				if ( ! empty( $value ) ) {
					$value = wp_unslash( $value );
				}
				$attributes .= ' ' . esc_attr( sanitize_title_with_dashes( $key ) ) . "='" . esc_attr( $value ) . "' ";
			}
		}

		$shortcode = $attributes
			? '[' . esc_attr( $name ) . ' ' . $attributes . ']'
			: '[' . esc_attr( $name ) . ']';

		if ( ! empty( $content ) ) {
			$shortcode .= $content;
			$shortcode .= '[/' . esc_attr( $name ) . ']';
		}

		return $shortcode;
	}
}
