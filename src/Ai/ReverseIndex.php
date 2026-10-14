<?php

namespace AyeCode\SuperDuper\Ai;

/**
 * The class-consumption index for a single block.
 *
 * Given a block's raw `get_arguments()`, this builds the reverse of
 * build_aui_class: a `class string → [attribute => value]` map, so the converter
 * can turn a Bootstrap class on an HTML element back into the native attribute
 * that produces it. The map is derived entirely from the block's own schema —
 * enum option keys combined with ClassMap's prefix table and exception list — so
 * a block that registers a new styling field is covered with no change here.
 *
 * It also resolves, by field presence rather than by hardcoded block id, the
 * handful of content-bearing attributes the converter needs to fill from an
 * element (its text, icon, image and link fields), keeping the converter
 * product-agnostic.
 */
class ReverseIndex {

	/** @var array<string, array<string, string>> class => [attr => value] */
	private array $index = array();

	/** @var array<string, array> The block's raw arguments. */
	private array $arguments;

	/**
	 * @param array $arguments Result of $instance->get_arguments().
	 */
	public function __construct( array $arguments ) {
		$this->arguments = $arguments;
		$this->build();
	}

	/**
	 * Build the class → attribute index from the block's arguments.
	 */
	private function build(): void {
		$literal = ClassMap::literal_attrs();

		foreach ( $this->arguments as $name => $arg ) {
			if ( ! is_array( $arg ) || 'select' !== ( $arg['type'] ?? '' ) ) {
				continue;
			}

			$prefix     = ClassMap::prefix_for( $name );
			$is_literal = in_array( $name, $literal, true );

			if ( null === $prefix && ! $is_literal ) {
				continue;
			}

			foreach ( self::option_keys( $arg['options'] ?? array() ) as $value ) {
				if ( '' === $value ) {
					continue;
				}

				$class = null !== $prefix ? $prefix . $value : $value;

				// First declaration wins; enums across attributes are disjoint in
				// practice, so this only guards the rare literal/prefix overlap.
				if ( ! isset( $this->index[ $class ] ) ) {
					$this->index[ $class ] = array( $name => $value );
				}
			}
		}

		// Fixed classes from checkboxes / special-cased attributes, added only
		// when the block actually declares the target attribute.
		foreach ( ClassMap::exceptions() as $class => $pair ) {
			$attr = (string) array_key_first( $pair );
			if ( isset( $this->arguments[ $attr ] ) && ! isset( $this->index[ $class ] ) ) {
				$this->index[ $class ] = $pair;
			}
		}
	}

	/**
	 * Resolve a single class to its attribute/value pair, or null if the block
	 * has no attribute that produces it.
	 *
	 * @param string $class
	 * @return array<string, string>|null
	 */
	public function match( string $class ): ?array {
		return $this->index[ $class ] ?? null;
	}

	/**
	 * The full class → attribute map, for tests and debugging.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function all(): array {
		return $this->index;
	}

	/**
	 * Whether the block declares an attribute.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function has_attribute( string $name ): bool {
		return isset( $this->arguments[ $name ] );
	}

	/**
	 * A single raw argument definition, or null.
	 *
	 * @param string $name
	 * @return array|null
	 */
	public function argument( string $name ): ?array {
		$arg = $this->arguments[ $name ] ?? null;

		return is_array( $arg ) ? $arg : null;
	}

	/**
	 * All argument names the block declares.
	 *
	 * @return string[]
	 */
	public function argument_names(): array {
		return array_keys( $this->arguments );
	}

	/**
	 * The neutral value for a select attribute — the option that renders no
	 * class — preferring an empty string, then "0". Null when neither exists.
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function neutral_value( string $name ): ?string {
		$enum = $this->enum_values( $name );

		if ( in_array( '', $enum, true ) ) {
			return '';
		}
		if ( in_array( '0', $enum, true ) ) {
			return '0';
		}

		return null;
	}

	/**
	 * The flattened option keys of a select attribute (empty for non-selects).
	 *
	 * @param string $name
	 * @return string[]
	 */
	public function enum_values( string $name ): array {
		$arg = $this->arguments[ $name ] ?? null;
		if ( null === $arg || 'select' !== ( $arg['type'] ?? '' ) ) {
			return array();
		}

		return self::option_keys( $arg['options'] ?? array() );
	}

	/**
	 * Whether a value is a valid option for a select attribute. Non-select
	 * attributes accept any value (text, url, number).
	 *
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	public function is_valid_value( string $name, string $value ): bool {
		$arg = $this->arguments[ $name ] ?? null;
		if ( null === $arg || 'select' !== ( $arg['type'] ?? '' ) ) {
			return isset( $this->arguments[ $name ] );
		}

		return in_array( $value, self::option_keys( $arg['options'] ?? array() ), true );
	}

	/**
	 * The block's primary text attribute and whether it accepts HTML.
	 *
	 * A textarea stores post-safe HTML (bs_text/bs_heading render it through
	 * wp_kses_post), so inline markup can be preserved; a plain text field
	 * (bs_button) stores a flat string. Resolved by the conventional field names
	 * in priority order.
	 *
	 * @return array{name: string, html: bool}|null
	 */
	public function text_attr(): ?array {
		foreach ( array( 'text', 'title', 'content' ) as $name ) {
			$arg = $this->arguments[ $name ] ?? null;
			if ( null === $arg ) {
				continue;
			}

			$type = $arg['type'] ?? 'text';
			if ( in_array( $type, array( 'text', 'textarea' ), true ) ) {
				return array(
					'name' => $name,
					'html' => 'textarea' === $type,
				);
			}
		}

		return null;
	}

	/**
	 * The block's icon attribute — the first field flagged as an icon picker —
	 * or null if it has none.
	 *
	 * @return string|null
	 */
	public function icon_attr(): ?string {
		foreach ( $this->arguments as $name => $arg ) {
			if ( ! is_array( $arg ) ) {
				continue;
			}

			if ( ! empty( $arg['icon_picker'] ) || 'iconpicker' === ( $arg['type'] ?? '' ) ) {
				return (string) $name;
			}
		}

		return null;
	}

	/**
	 * The block's image attributes, when it declares the url-mode image family.
	 *
	 * @return array{src: string, mode: ?string, alt: ?string}|null
	 */
	public function image_attrs(): ?array {
		if ( ! isset( $this->arguments['img_url'] ) ) {
			return null;
		}

		return array(
			'src'  => 'img_url',
			'mode' => isset( $this->arguments['img_src'] ) ? 'img_src' : null,
			'alt'  => isset( $this->arguments['img_alt'] ) ? 'img_alt' : null,
		);
	}

	/**
	 * The block's custom-link attributes, when it declares a custom_url field
	 * gated on a link-type selector.
	 *
	 * @return array{url: string, type: ?string}|null
	 */
	public function link_attrs(): ?array {
		if ( ! isset( $this->arguments['custom_url'] ) ) {
			return null;
		}

		return array(
			'url'  => 'custom_url',
			'type' => isset( $this->arguments['type'] ) ? 'type' : null,
		);
	}

	/**
	 * Recursively collect the option keys of a (possibly grouped) select's
	 * options array, mirroring SchemaTransformer::flatten_options for keys only.
	 *
	 * @param array $options
	 * @return string[]
	 */
	private static function option_keys( array $options ): array {
		$keys = array();

		foreach ( $options as $key => $label ) {
			if ( is_array( $label ) ) {
				$keys = array_merge( $keys, self::option_keys( $label ) );
				continue;
			}

			$keys[] = (string) $key;
		}

		return $keys;
	}
}
