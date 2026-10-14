<?php

namespace AyeCode\SuperDuper\Ai;

use AyeCode\SuperDuper\Registry;

/**
 * Assembles the AI-facing catalog and schema payloads from the block registry.
 *
 * This is where per-block AI metadata is resolved. Nothing here is keyed by a
 * hardcoded block slug: hints, context and nesting are read from options the
 * block itself declares (`ai-hint`, `ai-context`, `ai-nesting`, `ai-save`) or
 * supplied through filters, and everything else is derived from data the block
 * already registers. A block that declares nothing still appears and still has a
 * usable schema — adding a block to any product needs no change here.
 */
class Catalog {

	const CONTEXTS = array( 'page', 'site', 'archive', 'single' );

	/**
	 * Build the /catalog payload.
	 *
	 * @param string $context Optional context filter.
	 * @return array
	 */
	public static function catalog( string $context = '' ): array {
		$hints  = self::hints();
		$blocks = array();

		foreach ( self::block_ids() as $base_id ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance ) {
				continue;
			}

			$options  = $instance->options ?? array();
			$declared = $hints[ $base_id ] ?? array();
			$ctx      = self::context_of( $options, $declared );

			if ( '' !== $context && $ctx !== $context ) {
				continue;
			}

			if ( ! method_exists( $instance, 'get_arguments' ) ) {
				continue;
			}

			$result = SchemaTransformer::transform( (array) $instance->get_arguments() );

			$entry = array(
				'id'           => $base_id,
				'block_name'   => self::block_name( $options ),
				'title'        => self::clean_title( $options['name'] ?? $base_id ),
				'context'      => $ctx,
				'inner_blocks' => self::inner_blocks_of( $options ),
				// The catalog carries only content-tier attributes; style-tier are
				// fetched per block from /schemas for the single-block edit path.
				'attributes'   => self::attrs_for_tier( $result['attrs'], array( 'content' ) ),
			);

			$hint = self::hint_of( $options, $declared );
			if ( '' !== $hint ) {
				$entry['description'] = $hint;
			}

			$html = self::html_of( $base_id, $options );
			if ( ! empty( $html ) ) {
				$entry['html'] = $html;
			}

			$blocks[] = $entry;
		}

		return array(
			'version'     => defined( 'SUPER_DUPER_VER' ) ? SUPER_DUPER_VER : '',
			'schema_hash' => self::schema_hash(),
			'meta'        => array(
				'attributes' => 'Attribute keys and values are native — pass them straight to createBlock. Responsive variants are separate attributes (mt, mt_md, mt_lg). Checkboxes are strings ("" / "1"), never booleans.',
				'delta_rule' => 'Only set attributes that differ from their default; omit the rest.',
				'framework'  => 'bootstrap-5.3',
			),
			'blocks'      => $blocks,
			'patterns'    => array(),
		);
	}

	/**
	 * Build the /schemas payload for a set of block ids.
	 *
	 * One entry per block, keyed by base_id, carrying the full authorable native
	 * schema (content + style tiers) for the single-block edit path. Attributes
	 * use native keys and values so the consumer can hand them straight to
	 * createBlock().
	 *
	 * @param string[] $block_ids
	 * @return array
	 */
	public static function schemas( array $block_ids ): array {
		$hints  = self::hints();
		$blocks = array();

		foreach ( $block_ids as $base_id ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
				continue;
			}

			$options   = $instance->options ?? array();
			$arguments = (array) $instance->get_arguments();
			$result    = SchemaTransformer::transform( $arguments );

			$entry = array(
				'block_name'   => self::block_name( $options ),
				'title'        => self::clean_title( $options['name'] ?? $base_id ),
				'inner_blocks' => self::inner_blocks_of( $options ),
				// The per-block schema carries each attribute's raw field `group`,
				// so a consumer can disambiguate labels that repeat across sections
				// (a "Font size" under `typography` vs a "Font size" under
				// `icon-style`). The bulk /catalog omits it to stay lean.
				'attributes'   => self::with_groups(
					self::attrs_for_tier( $result['attrs'], array( 'content', 'style' ) ),
					$arguments
				),
			);

			$hint = self::hint_of( $options, $hints[ $base_id ] ?? array() );
			if ( '' !== $hint ) {
				$entry['description'] = $hint;
			}

			$nesting = self::nesting_of( $base_id, $options );
			if ( ! empty( $nesting ) ) {
				$entry['nesting'] = $nesting;
			}

			$html = self::html_of( $base_id, $options );
			if ( ! empty( $html ) ) {
				$entry['html'] = $html;
			}

			$blocks[ $base_id ] = $entry;
		}

		return array(
			'blocks'   => $blocks,
			'examples' => self::examples( $block_ids ),
		);
	}

	/**
	 * Filter a transformed attribute map to the requested tiers.
	 *
	 * @param array    $attrs Transformed attributes.
	 * @param string[] $tiers Tiers to keep, e.g. ['content'] or ['content','style'].
	 * @return array
	 */
	private static function attrs_for_tier( array $attrs, array $tiers ): array {
		return array_filter(
			$attrs,
			static fn( $schema ) => in_array( $schema['tier'] ?? 'style', $tiers, true )
		);
	}

	/**
	 * Attach each attribute's raw field `group` (the section it belongs to in the
	 * block form) to its schema, read from the original arguments. Used by
	 * /schemas only, to give ambiguous labels the context /catalog leaves out.
	 *
	 * @param array $attrs     Transformed attributes keyed by name.
	 * @param array $arguments The block's raw get_arguments().
	 * @return array
	 */
	private static function with_groups( array $attrs, array $arguments ): array {
		foreach ( $attrs as $name => &$schema ) {
			$group = $arguments[ $name ]['group'] ?? '';
			if ( is_string( $group ) && '' !== $group ) {
				$schema['group'] = $group;
			}
		}
		unset( $schema );

		return $attrs;
	}

	/**
	 * The inner-block rule for a block: "none" when it takes no children, an
	 * allowlist of native block names when declared, otherwise "any".
	 *
	 * @param array $options
	 * @return string|string[]
	 */
	private static function inner_blocks_of( array $options ) {
		if ( empty( $options['nested-block'] ) ) {
			return 'none';
		}

		$allowed = $options['allowed-blocks'] ?? array();
		if ( ! empty( $allowed ) ) {
			return array_values( (array) $allowed );
		}

		return 'any';
	}

	/**
	 * Public accessor for a block's inner-block rule ('none' | name[] | 'any').
	 *
	 * @param array $options
	 * @return string|string[]
	 */
	public static function inner_blocks_of_public( array $options ) {
		return self::inner_blocks_of( $options );
	}

	/**
	 * Public accessor: translate full block names to base_ids (core names pass
	 * through unchanged).
	 *
	 * @param mixed $names
	 * @return string[]
	 */
	public static function block_names_to_ids_public( $names ): array {
		return self::block_names_to_ids( $names );
	}

	/**
	 * The list of registered block ids.
	 *
	 * @return string[]
	 */
	public static function block_ids(): array {
		return array_keys( Registry::get_entries() );
	}

	/**
	 * Whether a block id is registered.
	 *
	 * @param string $base_id
	 * @return bool
	 */
	public static function has_block( string $base_id ): bool {
		return null !== Registry::get_instance_public( $base_id );
	}

	// -------------------------------------------------------------------------
	// AI metadata resolution
	// -------------------------------------------------------------------------

	/**
	 * Merge declared hints with the filter override.
	 *
	 * A hint entry is either a string (the hint text) or an array with `hint`
	 * and/or `context` keys.
	 *
	 * @return array<string, mixed>
	 */
	private static function hints(): array {
		return (array) apply_filters( 'ayecode_sd_ai_hints', array() );
	}

	/**
	 * The hint text for a block, from its option or a filtered override.
	 *
	 * @param array $options
	 * @param mixed $declared
	 * @return string
	 */
	private static function hint_of( array $options, $declared ): string {
		if ( is_string( $declared ) && '' !== $declared ) {
			return self::normalize_whitespace( $declared );
		}

		if ( is_array( $declared ) && ! empty( $declared['hint'] ) ) {
			return self::normalize_whitespace( (string) $declared['hint'] );
		}

		return isset( $options['ai-hint'] ) ? self::normalize_whitespace( (string) $options['ai-hint'] ) : '';
	}

	/**
	 * The native HTML elements a block claims, from its `ai-html` option or the
	 * `ayecode_sd_ai_html` filter. Selectors are `tag` or `tag.class` strings.
	 *
	 * @param string $base_id
	 * @param array  $options
	 * @return string[]
	 */
	private static function html_of( string $base_id, array $options ): array {
		$filtered = (array) apply_filters( 'ayecode_sd_ai_html', array() );
		$claims   = $filtered[ $base_id ] ?? ( $options['ai-html'] ?? array() );

		return array_values( array_filter( array_map( 'strval', (array) $claims ) ) );
	}

	/**
	 * Public accessor for a block's hint, for the Vocabulary builder.
	 *
	 * @param array $options
	 * @param mixed $declared
	 * @return string
	 */
	public static function hint_of_public( array $options, $declared = array() ): string {
		return self::hint_of( $options, $declared );
	}

	/**
	 * Public accessor for a block's context, for the Vocabulary builder.
	 *
	 * @param array $options
	 * @param mixed $declared
	 * @return string
	 */
	public static function context_of_public( array $options, $declared = array() ): string {
		return self::context_of( $options, $declared );
	}

	/**
	 * The context for a block, defaulting to 'page'.
	 *
	 * @param array $options
	 * @param mixed $declared
	 * @return string
	 */
	private static function context_of( array $options, $declared ): string {
		$context = '';

		if ( is_array( $declared ) && ! empty( $declared['context'] ) ) {
			$context = (string) $declared['context'];
		} elseif ( isset( $options['ai-context'] ) ) {
			$context = (string) $options['ai-context'];
		}

		return in_array( $context, self::CONTEXTS, true ) ? $context : 'page';
	}

	/**
	 * Resolve a block's nesting rules: its declared attribute-conditional rules
	 * plus block-level parent/child constraints derived from the `parent` and
	 * `allowed-blocks` options it already registers.
	 *
	 * @param string $base_id
	 * @param array  $options
	 * @return array
	 */
	private static function nesting_of( string $base_id, array $options ): array {
		$nesting = array();

		$declared = $options['ai-nesting'] ?? null;
		$filtered = apply_filters( 'ayecode_sd_ai_nesting', array() );
		if ( isset( $filtered[ $base_id ] ) ) {
			$declared = $filtered[ $base_id ];
		}

		if ( is_array( $declared ) && ! empty( $declared ) ) {
			$nesting = $declared;
		}

		$parents = self::block_names_to_ids( $options['parent'] ?? array() );
		if ( ! empty( $parents ) ) {
			$nesting['parent_block'] = $parents;
		}

		$children = self::block_names_to_ids( $options['allowed-blocks'] ?? array() );
		if ( ! empty( $children ) ) {
			$nesting['child_blocks'] = $children;
		}

		return $nesting;
	}

	/**
	 * Translate full block names (blockstrap/blockstrap-widget-nav) back to the
	 * base_ids the AI uses (bs_nav). Names with no registered match — core
	 * blocks like core/template-part — are passed through unchanged.
	 *
	 * @param mixed $names
	 * @return string[]
	 */
	private static function block_names_to_ids( $names ): array {
		if ( empty( $names ) ) {
			return array();
		}

		$names = (array) $names;
		$map   = self::name_to_id_map();
		$ids   = array();

		foreach ( $names as $name ) {
			$ids[] = $map[ $name ] ?? $name;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Build a full-block-name → base_id lookup across the registry.
	 *
	 * @return array<string, string>
	 */
	private static function name_to_id_map(): array {
		static $map = null;

		if ( null !== $map ) {
			return $map;
		}

		$map = array();

		foreach ( self::block_ids() as $base_id ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance ) {
				continue;
			}

			$options = $instance->options ?? array();
			$name    = self::block_name( $options );
			if ( '' !== $name ) {
				$map[ $name ] = $base_id;
			}
		}

		return $map;
	}

	/**
	 * Derive a block's full Gutenberg name the same way GutenbergBlock does.
	 *
	 * @param array $options
	 * @return string
	 */
	public static function block_name( array $options ): string {
		$textdomain = $options['textdomain'] ?? '';
		$class_name = $options['class_name'] ?? '';

		if ( '' === $textdomain || '' === $class_name ) {
			return '';
		}

		$class_base = basename( str_replace( '\\', '/', $class_name ) );
		$name       = sanitize_title_with_dashes( $textdomain ) . '/' . sanitize_title_with_dashes( $class_base );

		return str_replace( '_', '-', $name );
	}

	// -------------------------------------------------------------------------
	// Misc
	// -------------------------------------------------------------------------

	/**
	 * Collapse runs of whitespace (including newlines from multi-line PHP
	 * strings) to single spaces, so hints read as one clean line.
	 *
	 * @param string $text
	 * @return string
	 */
	private static function normalize_whitespace( string $text ): string {
		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Strip a short product prefix from a block name ("BS > Container" → "Container").
	 *
	 * @param string $name
	 * @return string
	 */
	private static function clean_title( string $name ): string {
		$stripped = preg_replace( '/^\s*[A-Za-z0-9]{1,6}\s*>\s*/', '', $name );

		return '' !== $stripped ? $stripped : $name;
	}

	/**
	 * Gold-standard example trees. Empty in Phase 1 until authored.
	 *
	 * @param string[] $block_ids
	 * @return array
	 */
	private static function examples( array $block_ids ): array {
		return (array) apply_filters( 'ayecode_sd_ai_examples', array(), $block_ids );
	}

	/**
	 * A hash that changes whenever any transformed schema changes.
	 *
	 * @return string
	 */
	public static function schema_hash(): string {
		$parts = array();

		foreach ( self::block_ids() as $base_id ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
				continue;
			}

			$result            = SchemaTransformer::transform( (array) $instance->get_arguments() );
			$parts[ $base_id ] = $result['attrs'];
		}

		ksort( $parts );

		return md5( (string) wp_json_encode( $parts ) );
	}
}
