<?php

namespace AyeCode\SuperDuper\Ai;

use AyeCode\SuperDuper\Registry;

/**
 * The prompt-side counterpart of the converter.
 *
 * Where the converter reads Bootstrap HTML back into blocks, this describes
 * everything a model must be told to *produce* convertible HTML: the fixed
 * authoring rules (semantic colour tokens, no inline styles, placeholder image
 * service) plus the list of custom elements — the product blocks that have no
 * native HTML equivalent and are addressed by their base_id as a tag
 * (`<gd-map>`). Native HTML needs no vocabulary; only custom elements do.
 *
 * Generated from the live registry so it tracks whatever products are active.
 */
class Vocabulary {

	/**
	 * Build the /ai/vocabulary payload.
	 *
	 * @param string $context Optional context filter (page/site/archive/single).
	 * @return array
	 */
	public static function vocabulary( string $context = '' ): array {
		return array(
			'schema_hash' => Catalog::schema_hash(),
			'rules'       => self::rules(),
			'elements'    => array_merge( self::core_elements( $context ), self::custom_elements( $context ) ),
		);
	}

	/**
	 * Synthetic vocabulary elements that convert to a core WordPress block rather
	 * than a product block. Currently only <bs-post-content>, the single-post
	 * template's post-body placeholder, offered in single/no context.
	 *
	 * @param string $context
	 * @return array
	 */
	private static function core_elements( string $context = '' ): array {
		if ( '' !== $context && 'single' !== $context ) {
			return array();
		}

		return array(
			array(
				'tag'         => 'bs-post-content',
				'attributes'  => array(),
				'leaf'        => true,
				'description' => __( 'Dynamic body content of the current post — REQUIRED exactly once in any single-post template\'s content area; never write literal article copy instead.', 'ayecode-connect' ),
			),
		);
	}

	/**
	 * The fixed authoring rules a model must follow to emit convertible HTML.
	 *
	 * @return string[]
	 */
	public static function rules(): array {
		return array(
			'Output Bootstrap 5.3 HTML only. No <script>, no <style>, no inline style attributes.',
			'Use semantic color tokens (bg-body, bg-body-tertiary, *-subtle, *-emphasis, text-body-secondary). Never bg-white, text-dark, or hex colors — they break dark mode.',
			'Spacing scale is 0-5 plus py-6/pt-6 style extensions where offered.',
			'Lay out with the Bootstrap grid: a .container wrapping .row wrapping .col-* columns. A column must sit inside a row.',
			'For images use <img> with https://picsum.photos/seed/<word>/W/H placeholders and real alt text.',
			'For product widgets use the custom elements listed below. A leaf element (leaf: true) stays empty — never put content inside it. A non-leaf element (leaf: false) is a container that MUST be filled with its child elements; when it lists "children", use ONLY those tags inside it (e.g. <bs-navbar> holds <bs-navbar-brand> and <bs-nav>, and <bs-nav> holds <bs-nav-item>).',
		);
	}

	/**
	 * The custom-element blocks (those without an `ai-html` claim), each with the
	 * content-tier attributes a model may set on the tag.
	 *
	 * @param string $context
	 * @return array
	 */
	private static function custom_elements( string $context = '' ): array {
		$html_filter = (array) apply_filters( 'ayecode_sd_ai_html', array() );
		$hints       = (array) apply_filters( 'ayecode_sd_ai_hints', array() );
		$elements    = array();

		foreach ( array_keys( Registry::get_entries() ) as $base_id ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
				continue;
			}

			$options = $instance->options ?? array();

			// Claimed native elements are not custom elements.
			$claims = $html_filter[ $base_id ] ?? ( $options['ai-html'] ?? array() );
			if ( ! empty( $claims ) ) {
				continue;
			}

			$declared = $hints[ $base_id ] ?? array();
			$ctx      = Catalog::context_of_public( $options, $declared );
			if ( '' !== $context && $ctx !== $context ) {
				continue;
			}

			$result     = SchemaTransformer::transform( (array) $instance->get_arguments() );
			$attributes = array_filter(
				$result['attrs'],
				static fn( $schema ) => 'content' === ( $schema['tier'] ?? 'style' )
			);

			$entry = array(
				'tag'        => str_replace( '_', '-', $base_id ),
				'attributes' => self::slim_attributes( $attributes ),
				'leaf'       => true,
			);

			// A block that accepts inner blocks is not a leaf: it must be filled with
			// its child elements, not left empty. When the block declares an explicit
			// allowlist, expose the allowed children as custom-element tags so the
			// model knows exactly what may nest inside.
			$inner = Catalog::inner_blocks_of_public( $options );
			if ( 'none' !== $inner ) {
				$entry['leaf'] = false;

				if ( is_array( $inner ) ) {
					$children = array();
					foreach ( Catalog::block_names_to_ids_public( $inner ) as $child_id ) {
						$child_instance = Registry::get_instance_public( $child_id );
						if ( null !== $child_instance ) {
							$children[] = str_replace( '_', '-', $child_id );
						}
					}
					if ( ! empty( $children ) ) {
						$entry['children'] = array_values( array_unique( $children ) );
					}
				}
			}

			$hint = Catalog::hint_of_public( $options, $declared );
			if ( '' !== $hint ) {
				$entry['description'] = $hint;
			}

			$elements[] = $entry;
		}

		return $elements;
	}

	/**
	 * Reduce a transformed attribute map to the compact form used in vocabulary:
	 * json_type plus enum, dropping the editor-only label/tier noise.
	 *
	 * @param array $attributes
	 * @return array
	 */
	private static function slim_attributes( array $attributes ): array {
		$slim = array();

		foreach ( $attributes as $name => $schema ) {
			$entry = array( 'json_type' => $schema['json_type'] ?? 'string' );

			if ( ! empty( $schema['enum'] ) ) {
				$entry['enum'] = $schema['enum'];
			}
			if ( isset( $schema['default'] ) ) {
				$entry['default'] = $schema['default'];
			}

			$slim[ $name ] = $entry;
		}

		return $slim;
	}
}
