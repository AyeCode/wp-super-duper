<?php

namespace AyeCode\SuperDuper\DynamicData;

/**
 * Registry of dynamic data groups, tags and option sources.
 *
 * Addons register on the sd_dynamic_data_register action, which fires the first
 * time anything asks for a tag, so registration costs nothing on requests that
 * never render one:
 *
 *   add_action( 'sd_dynamic_data_register', function () {
 *       Registry::register_group( 'listing', __( 'Listing', 'ayecode-connect' ), 30 );
 *       Registry::register( 'listing_price', array(
 *           'label'    => __( 'Price', 'ayecode-connect' ),
 *           'group'    => 'listing',
 *           'callback' => fn( array $args, Context $context ) => ...,
 *       ) );
 *   } );
 *
 * A tag definition:
 *
 *   label       string    Shown in the picker.
 *   group       string    Group key.
 *   description string    Optional help text.
 *   returns     string    text | html | url | number. Decides how the value is escaped.
 *   args        array[]   Arguments, see normalize_arg().
 *   link        callable  Optional. fn( array $args, Context $context ): string URL. Enables @link and @new_tab.
 *   modifiers   string[]  Optional. Defaults to every modifier that suits the tag.
 *   callback    callable  fn( array $args, Context $context ): mixed. Null or '' means empty.
 *
 * Registering a name that already exists replaces it, so an addon can override a core tag.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {

	const RETURNS = array( 'text', 'html', 'url', 'number' );

	const ARG_TYPES = array( 'text', 'number', 'toggle', 'select', 'combobox', 'date_format' );

	/** @var array<string, array{label: string, priority: int}> */
	private static array $groups = array();

	/** @var array<string, array> */
	private static array $tags = array();

	/** @var array<string, array{callback: callable, capability: string}> */
	private static array $sources = array();

	private static bool $loaded = false;

	/**
	 * Register a group in the picker sidebar. Lower priority sorts first.
	 */
	public static function register_group( string $key, string $label, int $priority = 50 ): void {
		self::$groups[ sanitize_key( $key ) ] = array(
			'label'    => $label,
			'priority' => $priority,
		);
	}

	/**
	 * Register a tag. See the class docblock for the definition keys.
	 *
	 * @param string $name Tag name, lowercase [a-z0-9_].
	 * @param array  $def  Tag definition.
	 * @return bool False when the name or definition is invalid.
	 */
	public static function register( string $name, array $def ): bool {
		if ( ! preg_match( '/^[a-z0-9_]+$/', $name ) || empty( $def['callback'] ) || ! is_callable( $def['callback'] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Dynamic data tag "%s" needs a lowercase [a-z0-9_] name and a callable callback.', $name ) ), '3.0.10-beta' );
			return false;
		}

		self::$tags[ $name ] = self::normalize_tag( $name, $def );

		return true;
	}

	/**
	 * Remove a tag, for example to hide a core tag on a site.
	 */
	public static function unregister( string $name ): void {
		self::ensure_loaded();
		unset( self::$tags[ $name ] );
	}

	/**
	 * Register a source of options for combobox and select args, fetched by the
	 * editor over REST when the field is shown.
	 *
	 * @param string   $key        Source key referenced by an arg's "source".
	 * @param callable $callback   fn( array $params ): array of array{value: string, label: string}.
	 * @param string   $capability Capability needed to read it.
	 */
	public static function register_source( string $key, callable $callback, string $capability = 'edit_posts' ): void {
		self::$sources[ sanitize_key( $key ) ] = array(
			'callback'   => $callback,
			'capability' => $capability,
		);
	}

	/**
	 * @return array|null The normalized definition, or null when no such tag exists.
	 */
	public static function get( string $name ): ?array {
		self::ensure_loaded();

		return self::$tags[ $name ] ?? null;
	}

	/**
	 * @return array<string, array>
	 */
	public static function all(): array {
		self::ensure_loaded();

		return self::$tags;
	}

	/**
	 * @return array{callback: callable, capability: string}|null
	 */
	public static function get_source( string $key ): ?array {
		self::ensure_loaded();

		return self::$sources[ $key ] ?? null;
	}

	/**
	 * The shared modifiers, in the same shape as tag args so the picker renders
	 * both with one set of controls.
	 *
	 * @return array<string, array>
	 */
	public static function modifiers(): array {
		return array(
			'words'    => self::normalize_arg(
				array(
					'key'   => 'words',
					'label' => __( 'Limit words', 'ayecode-connect' ),
					'type'  => 'number',
				)
			),
			'link'     => self::normalize_arg(
				array(
					'key'   => 'link',
					'label' => __( 'Link it', 'ayecode-connect' ),
					'type'  => 'toggle',
				)
			),
			'new_tab'  => self::normalize_arg(
				array(
					'key'   => 'new_tab',
					'label' => __( 'Open link in a new tab', 'ayecode-connect' ),
					'type'  => 'toggle',
				)
			),
			'fallback' => self::normalize_arg(
				array(
					'key'   => 'fallback',
					'label' => __( 'Fallback text', 'ayecode-connect' ),
					'type'  => 'text',
					'help'  => __( 'Shown when the value is empty.', 'ayecode-connect' ),
				)
			),
		);
	}

	/**
	 * Turn a parsed tag into the typed values its callback receives.
	 *
	 * The primary value fills the arg marked primary. Unknown args are dropped so
	 * a tag written for a newer version still renders on an older one.
	 *
	 * @param array $def    Normalized tag definition.
	 * @param array $parsed Output of Grammar::parse().
	 * @return array{args: array<string, mixed>, modifiers: array<string, mixed>}|null
	 *         Null when the tag cannot take what it was given.
	 */
	public static function resolve_args( array $def, array $parsed ): ?array {
		$given = $parsed['args'];

		if ( null !== $parsed['primary'] ) {
			if ( null === $def['primary'] ) {
				return null;
			}

			// A named value wins over the shorthand when both are given.
			$given += array( $def['primary'] => $parsed['primary'] );
		}

		$args = array();
		foreach ( $def['args'] as $arg ) {
			$args[ $arg['key'] ] = self::cast( $arg, $given[ $arg['key'] ] ?? null );
		}

		$modifiers = array();
		$all       = self::modifiers();
		foreach ( $def['modifiers'] as $key ) {
			$modifiers[ $key ] = self::cast( $all[ $key ], $given[ $key ] ?? null );
		}

		return array(
			'args'      => $args,
			'modifiers' => $modifiers,
		);
	}

	/**
	 * Everything the picker needs, without callbacks.
	 *
	 * @return array{groups: array, tags: array, modifiers: array}
	 */
	public static function get_client_schema(): array {
		self::ensure_loaded();

		$groups = self::$groups;
		uasort(
			$groups,
			static fn( $a, $b ) => $a['priority'] <=> $b['priority']
		);

		$out_groups = array();
		foreach ( $groups as $key => $group ) {
			$out_groups[] = array(
				'key'   => $key,
				'label' => $group['label'],
			);
		}

		$out_tags = array();
		foreach ( self::$tags as $tag ) {
			$out_tags[] = array(
				'name'        => $tag['name'],
				'label'       => $tag['label'],
				'group'       => $tag['group'],
				'description' => $tag['description'],
				'returns'     => $tag['returns'],
				'primary'     => $tag['primary'],
				'args'        => array_map( array( static::class, 'resolve_arg' ), $tag['args'] ),
				'modifiers'   => $tag['modifiers'],
			);
		}

		return array(
			'groups'    => $out_groups,
			'tags'      => $out_tags,
			'modifiers' => self::modifiers(),
		);
	}

	/**
	 * Load the core tags and let addons register theirs, once per request.
	 */
	private static function ensure_loaded(): void {
		if ( self::$loaded ) {
			return;
		}

		self::$loaded = true;

		// Core goes first so anything registered afterwards can replace it.
		$early = array( self::$groups, self::$tags, self::$sources );
		CoreTags::register();
		self::$groups  = array_merge( self::$groups, $early[0] );
		self::$tags    = array_merge( self::$tags, $early[1] );
		self::$sources = array_merge( self::$sources, $early[2] );

		/**
		 * Register dynamic data groups, tags and option sources.
		 */
		do_action( 'sd_dynamic_data_register' );

		/**
		 * Filter the registered tags after every addon has registered.
		 *
		 * @param array<string, array> $tags Normalized definitions keyed by name.
		 */
		self::$tags = (array) apply_filters( 'sd_dynamic_data_tags', self::$tags );
	}

	private static function normalize_tag( string $name, array $def ): array {
		$returns = in_array( $def['returns'] ?? '', self::RETURNS, true ) ? $def['returns'] : 'text';
		$link    = isset( $def['link'] ) && is_callable( $def['link'] ) ? $def['link'] : null;

		$args    = array();
		$primary = null;
		foreach ( (array) ( $def['args'] ?? array() ) as $arg ) {
			if ( empty( $arg['key'] ) ) {
				continue;
			}
			$arg                 = self::normalize_arg( $arg );
			$args[ $arg['key'] ] = $arg;
			if ( null === $primary && $arg['primary'] ) {
				$primary = $arg['key'];
			}
		}

		// In the order the picker shows them, fallback last.
		$suitable = array();
		if ( in_array( $returns, array( 'text', 'html' ), true ) ) {
			$suitable[] = 'words';
		}
		if ( $link && 'url' !== $returns ) {
			$suitable[] = 'link';
			$suitable[] = 'new_tab';
		}
		$suitable[] = 'fallback';

		// Explicit modifiers can narrow the suitable set but never widen it, since
		// @link without a link callback would have nothing to link to.
		$modifiers = isset( $def['modifiers'] ) ? array_values( array_intersect( $suitable, (array) $def['modifiers'] ) ) : $suitable;

		// Args shadow modifiers of the same name.
		$modifiers = array_values( array_diff( $modifiers, array_keys( $args ) ) );

		return array(
			'name'        => $name,
			'label'       => (string) ( $def['label'] ?? $name ),
			'group'       => sanitize_key( $def['group'] ?? 'other' ),
			'description' => (string) ( $def['description'] ?? '' ),
			'returns'     => $returns,
			'primary'     => $primary,
			'args'        => array_values( $args ),
			'modifiers'   => $modifiers,
			'link'        => $link,
			'callback'    => $def['callback'],
		);
	}

	/**
	 * Arg definition keys:
	 *
	 *   key         string  Name used in @key:value.
	 *   label       string  Field label.
	 *   type        string  text | number | toggle | select | combobox | date_format.
	 *   default     mixed   Used when the arg is not given.
	 *   primary     bool    Filled by the {name:value} shorthand. One per tag.
	 *   required    bool    The tag renders empty (or its fallback) without it.
	 *   options     array|callable  value => label, for select, combobox and date_format presets.
	 *                       A callable returning that array is only called when the editor
	 *                       asks for the schema, for labels that are costly to build.
	 *   source      string  Option source key, fetched over REST, for select and combobox.
	 *   placeholder string
	 *   help        string
	 */
	private static function normalize_arg( array $arg ): array {
		$type = in_array( $arg['type'] ?? '', self::ARG_TYPES, true ) ? $arg['type'] : 'text';

		$options = $arg['options'] ?? array();
		if ( ! is_callable( $options ) ) {
			$options = self::option_list( (array) $options );
		}

		return array(
			'key'         => sanitize_key( $arg['key'] ),
			'label'       => (string) ( $arg['label'] ?? $arg['key'] ),
			'type'        => $type,
			'default'     => $arg['default'] ?? ( 'toggle' === $type ? false : '' ),
			'primary'     => ! empty( $arg['primary'] ),
			'required'    => ! empty( $arg['required'] ),
			'options'     => $options,
			'source'      => isset( $arg['source'] ) ? sanitize_key( $arg['source'] ) : '',
			'placeholder' => (string) ( $arg['placeholder'] ?? '' ),
			'help'        => (string) ( $arg['help'] ?? '' ),
		);
	}

	/**
	 * @param array $options value => label.
	 * @return array<int, array{value: string, label: string}>
	 */
	private static function option_list( array $options ): array {
		$out = array();
		foreach ( $options as $value => $label ) {
			$out[] = array(
				'value' => (string) $value,
				'label' => (string) $label,
			);
		}

		return $out;
	}

	/**
	 * An arg with its options resolved, if they were given as a callable.
	 */
	private static function resolve_arg( array $arg ): array {
		$arg['options'] = self::resolve_options( $arg );

		return $arg;
	}

	private static function resolve_options( array $arg ): array {
		return is_callable( $arg['options'] ) ? self::option_list( (array) call_user_func( $arg['options'] ) ) : $arg['options'];
	}

	/**
	 * Cast a raw value from the tag string to the arg's type.
	 *
	 * @param array            $arg   Normalized arg.
	 * @param string|true|null $value Parsed value, true for a bare flag, null when absent.
	 * @return mixed
	 */
	private static function cast( array $arg, $value ) {
		if ( null === $value ) {
			return $arg['default'];
		}

		switch ( $arg['type'] ) {
			case 'toggle':
				return true === $value || ! in_array( strtolower( (string) $value ), array( '0', 'false', 'no', 'off', '' ), true );

			case 'number':
				return is_numeric( $value ) ? $value + 0 : $arg['default'];

			case 'select':
				// A select with a static list only accepts a value from it.
				if ( true === $value ) {
					return $arg['default'];
				}
				$options = self::resolve_options( $arg );
				if ( $options && ! in_array( $value, array_column( $options, 'value' ), true ) ) {
					return $arg['default'];
				}
				return $value;

			default:
				return true === $value ? $arg['default'] : $value;
		}
	}
}
