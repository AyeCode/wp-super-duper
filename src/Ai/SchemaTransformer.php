<?php

namespace AyeCode\SuperDuper\Ai;

use AyeCode\SuperDuper\Utils;

/**
 * Transforms a block's compiled `get_arguments()` array into a native,
 * LLM-facing attribute schema.
 *
 * Native means: attribute keys and values are exactly what the registered block
 * accepts, because the consumer inserts with Gutenberg `createBlock()`, which
 * matches attributes against the block's registered definitions and silently
 * drops anything else. So this transformer does NOT compress — no responsive
 * collapse, no enum normalization. It keeps values authentic and instead cuts
 * size by tiering: every attribute is `content`, `style`, or dropped, and the
 * catalog ships content-tier only.
 *
 * Pure and stateless.
 */
class SchemaTransformer {

	/**
	 * Attribute names dropped wholesale — editor/runtime plumbing an AI must
	 * never emit, image internals (the AI uses the URL path only), and the
	 * visibility/animation features held back for a later phase.
	 */
	const DROP_ARGS = array(
		'sd_shortcode',
		'sd_shortcode_close',
		'styleid',
		'metadata_name',
		'anchor',
		'visibility_conditions',
		'hover_animations',
		'bg_image',
		'bg_image_id',
		'bg_image_xy',
	);

	/**
	 * Field types with no authorable AI representation.
	 */
	const DROP_TYPES = array(
		'hidden',
		'notice',
		'image_xy',
		'_tab_marker',
		'visibility_conditions',
	);

	/**
	 * Field `group` values that make an attribute authoring-critical (content
	 * tier). These are the shared layout/content groups produced across products
	 * by the Fields\* factories, plus product content groups. Everything not
	 * here (and not dropped) is style tier.
	 */
	const CONTENT_GROUPS = array( 'content', 'link', 'container', 'title', 'image', 'fields' );

	/**
	 * Attribute names forced to content tier regardless of their group, because
	 * they are semantic authoring choices.
	 */
	const CONTENT_NAMES = array( 'html_tag', 'title_tag', 'text', 'title', 'description', 'send_to', 'email_name', 'sent_message' );

	/**
	 * Transform one block's arguments.
	 *
	 * @param array $arguments Result of $instance->get_arguments().
	 * @return array{attrs: array, requires: array} `attrs` keyed by native
	 *         attribute name; `requires` holds raw element_require expressions
	 *         server-side for the future validate gate.
	 */
	public static function transform( array $arguments ): array {
		$names    = array_keys( $arguments );
		$attrs    = array();
		$requires = array();

		foreach ( $arguments as $name => $arg ) {
			if ( self::is_dropped( $name, $arg ) ) {
				continue;
			}

			$schema = self::transform_argument( $name, $arg, $names );

			if ( null === $schema ) {
				continue;
			}

			if ( ! empty( $arg['element_require'] ) && is_string( $arg['element_require'] ) ) {
				$requires[ $name ] = $arg['element_require'];

				$described = RequireExpression::to_schema( $arg['element_require'] );
				if ( null !== $described ) {
					$schema = array_merge( $schema, $described );
				}
			}

			$attrs[ $name ] = $schema;
		}

		return array(
			'attrs'    => $attrs,
			'requires' => $requires,
		);
	}

	// -------------------------------------------------------------------------
	// Per-argument transform
	// -------------------------------------------------------------------------

	/**
	 * Whether an argument is dropped entirely.
	 *
	 * @param string $name
	 * @param array  $arg
	 * @return bool
	 */
	private static function is_dropped( string $name, array $arg ): bool {
		if ( in_array( $name, self::DROP_ARGS, true ) ) {
			return true;
		}

		if ( in_array( $arg['type'] ?? '', self::DROP_TYPES, true ) ) {
			return true;
		}

		// The bg_image family is deferred; catch product-specific extras.
		if ( 0 === strpos( $name, 'bg_image' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Transform a single argument into its native schema entry.
	 *
	 * @param string   $name
	 * @param array    $arg
	 * @param string[] $all_names All argument names on the block.
	 * @return array|null
	 */
	private static function transform_argument( string $name, array $arg, array $all_names ) {
		$type   = $arg['type'] ?? 'text';
		$schema = array();

		switch ( $type ) {
			case 'select':
				$options = self::flatten_options( $arg['options'] ?? array() );
				if ( empty( $options['enum'] ) ) {
					return null;
				}
				$schema['json_type']   = ! empty( $arg['multiple'] ) ? 'array' : 'string';
				$schema['enum']        = $options['enum'];
				$schema['enum_labels'] = $options['labels'];
				if ( '' !== $options['note'] ) {
					$schema['note'] = $options['note'];
				}
				break;

			case 'checkbox':
				// Checkboxes are string '' / '1' (or the registered on-value), never
				// boolean — a boolean would be coerced to true/false the block rejects.
				$on                    = isset( $arg['value'] ) && '' !== (string) $arg['value'] ? (string) $arg['value'] : '1';
				$schema['json_type']   = 'string';
				$schema['enum']        = array( '', $on );
				$schema['enum_labels'] = array(
					''  => __( 'No', 'ayecode-connect' ),
					$on => __( 'Yes', 'ayecode-connect' ),
				);
				break;

			case 'number':
			case 'range':
				$bounds              = self::number_bounds( $arg );
				$schema['json_type'] = ( isset( $bounds['step'] ) && self::is_whole( $bounds['step'] ) ) ? 'integer' : 'number';
				$schema              = array_merge( $schema, $bounds );
				break;

			case 'color':
				$schema['json_type'] = 'string';
				$schema['format']    = 'hex';
				break;

			case 'gradient':
				$schema['json_type'] = 'string';
				$schema['format']    = 'css-gradient';
				break;

			case 'text':
			case 'textarea':
			case 'iconpicker':
			case 'image':
			default:
				$schema['json_type'] = 'string';
				break;
		}

		$schema['tier'] = self::tier_of( $name, $arg, $all_names );

		// Token-enum attributes carry the class prefix their value is rendered
		// with (mt "3" → mt-3), so the reverse index can rebuild the class from a
		// value without duplicating build_aui_class's prefix table.
		$prefix = ClassMap::prefix_for( $name );
		if ( null !== $prefix ) {
			$schema['class_prefix'] = $prefix;
		}

		if ( isset( $arg['default'] ) && '' !== $arg['default'] && null !== $arg['default'] ) {
			$schema['default'] = is_scalar( $arg['default'] ) ? (string) $arg['default'] : $arg['default'];
		}

		if ( ! empty( $arg['title'] ) ) {
			$schema['label'] = wp_strip_all_tags( (string) $arg['title'] );
		}

		// A description only when it documents behaviour beyond the label.
		if ( ! empty( $arg['ai-desc'] ) ) {
			$schema['description'] = (string) $arg['ai-desc'];
		}

		// A placeholder that documents a format (not a sentence) — e.g. a URL example.
		if ( ! empty( $arg['placeholder'] ) && ! self::looks_like_sentence( $arg['placeholder'] ) ) {
			$schema['hint'] = (string) $arg['placeholder'];
		}

		if ( 'css_class' === $name ) {
			$schema['note'] = 'extra Bootstrap utility classes; use sparingly';
		}

		return $schema;
	}

	/**
	 * Decide an attribute's tier: content (authoring-critical) or style.
	 *
	 * Responsive breakpoint variants (`*_md` / `*_lg` whose base exists) are
	 * demoted to style so the content catalog stays lean.
	 *
	 * @param string   $name
	 * @param array    $arg
	 * @param string[] $all_names
	 * @return string 'content' | 'style'
	 */
	public static function tier_of( string $name, array $arg, array $all_names ): string {
		// A responsive breakpoint variant (mt_md, text_align_lg) whose base
		// attribute also exists is demoted to style, keeping the catalog lean.
		foreach ( array( '_md', '_lg' ) as $suffix ) {
			if ( substr( $name, -strlen( $suffix ) ) === $suffix ) {
				$base = substr( $name, 0, -strlen( $suffix ) );
				if ( in_array( $base, $all_names, true ) ) {
					return 'style';
				}
			}
		}

		if ( in_array( $name, self::CONTENT_NAMES, true ) ) {
			return 'content';
		}

		$group = $arg['group'] ?? '';
		if ( in_array( $group, self::CONTENT_GROUPS, true ) ) {
			return 'content';
		}

		return 'style';
	}

	/**
	 * Recursively flatten a (possibly grouped) options array into an ordered,
	 * de-duplicated enum of native string keys, keeping the labels.
	 *
	 * @param array $options
	 * @return array{enum: array, labels: array, note: string}
	 */
	private static function flatten_options( array $options ): array {
		$enum   = array();
		$labels = array();
		$note   = '';

		foreach ( $options as $key => $label ) {
			if ( is_array( $label ) ) {
				if ( is_string( $key ) && false !== stripos( $key, 'dark mode' ) ) {
					$note = '-subtle and -emphasis variants adapt to dark mode';
				}
				$sub = self::flatten_options( $label );
				foreach ( $sub['enum'] as $sub_key ) {
					$enum[]             = $sub_key;
					$labels[ $sub_key ] = $sub['labels'][ $sub_key ];
				}
				continue;
			}

			$key            = (string) $key;
			$enum[]         = $key;
			$labels[ $key ] = wp_strip_all_tags( (string) $label );
		}

		$enum = array_values( array_unique( $enum ) );

		return array(
			'enum'   => $enum,
			'labels' => $labels,
			'note'   => $note,
		);
	}

	/**
	 * Pull min/max/step out of a number field's custom_attributes.
	 *
	 * @param array $arg
	 * @return array
	 */
	private static function number_bounds( array $arg ): array {
		$bounds = array();

		$raw = $arg['custom_attributes'] ?? '';
		if ( is_array( $raw ) ) {
			$attributes = $raw;
		} elseif ( is_string( $raw ) && '' !== $raw ) {
			$attributes = Utils::parse_custom_attributes( $raw );
		} else {
			$attributes = array();
		}

		foreach ( array( 'min', 'max', 'step' ) as $key ) {
			if ( isset( $attributes[ $key ] ) && '' !== $attributes[ $key ] && is_numeric( $attributes[ $key ] ) ) {
				$bounds[ $key ] = 0 + $attributes[ $key ];
			}
		}

		return $bounds;
	}

	/**
	 * Whether a numeric value is a whole number.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private static function is_whole( $value ): bool {
		return is_numeric( $value ) && floor( (float) $value ) === (float) $value;
	}

	/**
	 * Heuristic: does a string read as prose rather than a format example?
	 *
	 * @param string $text
	 * @return bool
	 */
	private static function looks_like_sentence( string $text ): bool {
		return substr_count( trim( $text ), ' ' ) > 1;
	}
}
