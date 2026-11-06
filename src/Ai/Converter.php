<?php

namespace AyeCode\SuperDuper\Ai;

use AyeCode\SuperDuper\Registry;

// The PHP DOM API exposes camelCase properties (childNodes, ownerDocument,
// tagName, parentNode) that cannot be renamed; the snake_case rule does not
// apply to them.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * Converts a Bootstrap 5.3 HTML fragment into a native block tree.
 *
 * This is the server side of the HTML-first generation pipeline: a model designs
 * in the Bootstrap HTML it was trained on, and this class deterministically maps
 * that HTML onto the real Gutenberg blocks and native attributes the editor
 * inserts with createBlock(). Fidelity lives here, in testable code, instead of
 * in the model.
 *
 * Every element resolves in one of three ways, in order: a custom element (a
 * product block addressed by its `base_id` as a tag, e.g. `<gd-map>`), a claimed
 * native element (a block that declared the tag via `ai-html`, e.g. `<p>` →
 * bs_text), or an unmappable element (descended into if it has children, else
 * dropped). Classes are turned back into attributes through ReverseIndex; anything
 * left over lands in `css_class` so nothing styling is ever silently lost. Every
 * loss — dropped elements, spilled classes, inline styles, ignored children — is
 * recorded in the report the consumer surfaces to the user.
 *
 * See docs/dev/ai-api.md for the request/response contract.
 */
class Converter {

	const MAX_DEPTH = 12;
	const MAX_NODES = 500;

	/**
	 * Synthetic vocabulary elements that convert to a core WordPress block rather
	 * than a product block. The converter's one core-block allowlist: a
	 * <bs-post-content> tag is the only way a template can render the post body,
	 * which no product block provides.
	 *
	 * @var array<string, string> dash-tag => core block name.
	 */
	const CORE_ELEMENTS = array(
		'bs-post-content' => 'core/post-content',
	);

	/**
	 * Attributes whose element_require is an editor-form visibility rule only —
	 * build_aui_class renders them regardless of the condition. The border radius
	 * family is gated behind a visible border in the editor UI, but the radius
	 * class is emitted independent of border, so an unmet require must NOT spill it
	 * to css_class: keeping the attribute renders the same class.
	 *
	 * @var string[]
	 */
	const RENDER_INDEPENDENT_ATTRS = array(
		'rounded',
		'rounded_size',
		'rounded_size_md',
		'rounded_size_lg',
		// Align-self renders regardless of the editor's container=="col" gate.
		'flex_align_self',
		'flex_align_self_md',
		'flex_align_self_lg',
	);

	/** @var array The loss/coverage report accumulated during the walk. */
	private array $report;

	/** @var array<int, array{tag:string,class:?string,base_id:string,spec:int}> Claim table. */
	private array $claims = array();

	/** @var array<string, string> dash-tag => base_id for custom-element blocks. */
	private array $custom_map = array();

	/** @var string base_id of the block a plain text element falls back to. */
	private string $text_fallback = '';

	/** @var string base_id of the block a generic wrapper element falls back to. */
	private string $container_fallback = '';

	/** @var string base_id of the icon block (claims <i>), for composite items. */
	private string $icon_block = '';

	/** @var array<string, ReverseIndex> base_id => index (lazy). */
	private array $indexes = array();

	/** @var array<string, array> base_id => block options (lazy). */
	private array $options = array();

	/** @var array<string, string> base_id => full block name (lazy). */
	private array $names = array();

	/** @var int Elements processed, for the node ceiling. */
	private int $node_count = 0;

	/**
	 * Convert an HTML fragment to `{ blocks, report }`.
	 *
	 * @param string $html
	 * @return array{blocks: array, report: array}
	 */
	public static function convert( string $html ): array {
		return ( new self() )->run( $html );
	}

	/**
	 * @param string $html
	 * @return array
	 */
	private function run( string $html ): array {
		$this->report = self::empty_report();
		$this->build_maps();

		$clean = HtmlSanitizer::strip_dangerous_blocks( $html );
		$root  = $this->parse( $clean );

		$blocks = null === $root ? array() : $this->walk( $root->childNodes, 1, '' );
		$blocks = self::strip_internal( $blocks );

		$this->report['elements_converted'] = max(
			0,
			$this->report['elements_total'] - count( $this->report['dropped_elements'] )
		);

		return array(
			'blocks' => $blocks,
			'report' => $this->report,
		);
	}

	// -------------------------------------------------------------------------
	// Registry maps
	// -------------------------------------------------------------------------

	/**
	 * Build the claim table and custom-element map from the live registry. A
	 * block that declares `ai-html` claims those selectors; every other
	 * AI-eligible block is addressable as a custom element by its base_id.
	 */
	private function build_maps(): void {
		$html_filter = (array) apply_filters( 'ayecode_sd_ai_html', array() );

		foreach ( Registry::get_entries() as $base_id => $entry ) {
			$instance = Registry::get_instance_public( $base_id );
			if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
				continue;
			}

			$options                   = $instance->options ?? array();
			$this->options[ $base_id ] = $options;

			// Native claims mean the block represents that HTML element (a heading
			// is <h2>); they disqualify it as a custom element. Fallback claims are
			// safety nets (a hand-built <form> → bs_contact) that add a claim
			// without hiding the block's own custom element (<bs-contact>).
			$native   = $html_filter[ $base_id ] ?? ( $options['ai-html'] ?? array() );
			$native   = array_values( array_filter( array_map( 'strval', (array) $native ) ) );
			$fallback = array_values( array_filter( array_map( 'strval', (array) ( $options['ai-html-fallback'] ?? array() ) ) ) );

			if ( empty( $native ) ) {
				$this->custom_map[ str_replace( '_', '-', $base_id ) ] = $base_id;
			}

			foreach ( array_merge( $native, $fallback ) as $selector ) {
				$dot  = strpos( $selector, '.' );
				$ctag = false === $dot ? $selector : substr( $selector, 0, $dot );

				$this->claims[] = array(
					'tag'     => $ctag,
					'class'   => false === $dot ? null : substr( $selector, $dot + 1 ),
					'base_id' => $base_id,
					'spec'    => false === $dot ? 0 : 1,
				);

				// The block that claims <p> is where stray inline text lands.
				if ( 'p' === $ctag && '' === $this->text_fallback ) {
					$this->text_fallback = $base_id;
				}

				// The block that claims a bare <div> is the generic wrapper, used to
				// keep an unmapped wrapper's classes instead of dropping them.
				if ( 'div' === $ctag && false === $dot && '' === $this->container_fallback ) {
					$this->container_fallback = $base_id;
				}

				// The block that claims <i> is the icon block, used both for
				// standalone icons and for composite icon + text items.
				if ( 'i' === $ctag && '' === $this->icon_block ) {
					$this->icon_block = $base_id;
				}
			}
		}

		// Most specific (tag.class) first so it wins over a bare-tag claim.
		usort( $this->claims, static fn( $a, $b ) => $b['spec'] <=> $a['spec'] );
	}

	/**
	 * The ReverseIndex for a block, built once.
	 *
	 * @param string $base_id
	 * @return ReverseIndex
	 */
	private function index( string $base_id ): ReverseIndex {
		if ( ! isset( $this->indexes[ $base_id ] ) ) {
			$instance                  = Registry::get_instance_public( $base_id );
			$this->indexes[ $base_id ] = new ReverseIndex( (array) $instance->get_arguments() );
		}

		return $this->indexes[ $base_id ];
	}

	/**
	 * The full block name for a base_id.
	 *
	 * @param string $base_id
	 * @return string
	 */
	private function block_name( string $base_id ): string {
		if ( ! isset( $this->names[ $base_id ] ) ) {
			$this->names[ $base_id ] = Catalog::block_name( $this->options[ $base_id ] ?? array() );
		}

		return $this->names[ $base_id ];
	}

	/**
	 * Whether a block accepts inner blocks (a container) rather than being a leaf.
	 *
	 * @param string $base_id
	 * @return bool
	 */
	private function is_container( string $base_id ): bool {
		return ! empty( $this->options[ $base_id ]['nested-block'] );
	}

	// -------------------------------------------------------------------------
	// DOM parsing + walk
	// -------------------------------------------------------------------------

	/**
	 * Parse an HTML fragment and return the wrapper element whose children are
	 * the fragment's top-level nodes.
	 *
	 * @param string $html
	 * @return \DOMElement|null
	 */
	private function parse( string $html ): ?\DOMElement {
		$doc = new \DOMDocument();

		$previous = libxml_use_internal_errors( true );
		$doc->loadHTML(
			'<?xml encoding="utf-8"?><div data-sd-root="1">' . $html . '</div>',
			LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		foreach ( $doc->childNodes as $node ) {
			if ( $node instanceof \DOMElement && $node->hasAttribute( 'data-sd-root' ) ) {
				return $node;
			}
		}

		return null;
	}

	/**
	 * Walk a node list into a list of blocks, enforcing nesting at this level.
	 *
	 * @param \DOMNodeList $nodes
	 * @param int          $depth
	 * @param string       $parent_role Structural role of the enclosing container.
	 * @return array
	 */
	private function walk( \DOMNodeList $nodes, int $depth, string $parent_role ): array {
		$blocks = array();

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof \DOMElement ) {
				// A loose text node directly inside a container (mock-browser chrome
				// copy, stray labels) has no element home — wrap it in a text block
				// so its copy is not silently lost.
				if ( $node instanceof \DOMText && '' !== $this->text_fallback && '' !== trim( $node->textContent ) ) {
					$blocks[] = $this->build_text_node( trim( $node->textContent ) );
				}
				continue;
			}

			++$this->report['elements_total'];

			if ( $depth > self::MAX_DEPTH || $this->node_count >= self::MAX_NODES ) {
				$this->note( sprintf( 'Truncated at depth/node limit near <%s>.', $node->tagName ) );
				continue;
			}
			++$this->node_count;

			$tag = strtolower( $node->tagName );

			// A line break is content flow, not a droppable element. Inside a text
			// run it is absorbed by that run's fold/inner-HTML; a standalone break
			// between block-level runs has no cross-block home, so it is skipped
			// silently rather than reported as dropped.
			if ( 'br' === $tag ) {
				continue;
			}

			$resolution = $this->resolve( $node, $tag );

			// An icon chip — a coloured/rounded wrapper whose only content is an
			// icon — becomes a single icon-box with populated controls, whether the
			// wrapper is a claimed <div> or an unmapped inline element.
			if ( $this->is_icon_chip( $node, $tag ) ) {
				$blocks[] = $this->build_icon_chip( $node );
				continue;
			}

			// An icon beside inline-only text (a list item with a check icon and a
			// label, or a status pill whose icon is not the first child) decomposes
			// into one icon-box, its parts split across the icon/title attributes.
			// Restricted to would-be containers (or unmapped wrappers) so a
			// heading/button/link carrying an inline icon keeps its own block.
			$to_container = null === $resolution
				|| ( 'custom' !== $resolution['kind'] && $this->is_container( $resolution['base_id'] ) );
			if ( $to_container && $this->is_icon_item( $node ) ) {
				$blocks[] = $this->build_icon_item( $node );
				continue;
			}

			if ( null === $resolution ) {
				// A horizontal rule is a border-top divider: keep it as the generic
				// wrapper carrying its own classes plus border-top.
				if ( 'hr' === $tag && '' !== $this->text_fallback ) {
					$blocks[] = $this->build_divider( $node );
					continue;
				}

				// Unmappable. A wrapper with its own classes (a <ul class="…">)
				// becomes a generic container so those classes are not lost;
				// a classless wrapper is descended through transparently; a
				// text-bearing inline element (badge span, small print) becomes a
				// text block; an empty but styled element (a decorative dot) keeps
				// its classes on the generic container; only a bare empty leaf drops.
				$has_classes = array() !== self::classes_of( $node );
				if ( self::has_element_children( $node ) ) {
					if ( '' !== $this->container_fallback && $has_classes ) {
						$blocks[] = $this->build_container( $node, $this->container_fallback, $tag, $depth );
					} else {
						$blocks = array_merge( $blocks, $this->walk( $node->childNodes, $depth + 1, $parent_role ) );
					}
				} elseif ( '' !== $this->text_fallback && ( '' !== trim( $node->textContent ) || $has_classes ) ) {
					// Text-bearing or empty-but-styled (a decorative dot): the text
					// block renders its wrapper class even with empty content, which
					// the container block does not.
					$blocks[] = $this->build_leaf( $node, $this->text_fallback, 'p' );
				} else {
					$this->drop( $tag, 'no mapping', $node );
				}
				continue;
			}

			if ( 'core' === $resolution['kind'] ) {
				$blocks[] = $this->build_core( $node, $resolution['base_id'] );
			} elseif ( 'custom' === $resolution['kind'] ) {
				$blocks[] = $this->build_custom( $node, $resolution['base_id'], $depth );
			} elseif ( $this->is_container( $resolution['base_id'] ) ) {
				$blocks[] = $this->build_container( $node, $resolution['base_id'], $tag, $depth );
			} else {
				$blocks[] = $this->build_leaf( $node, $resolution['base_id'], $tag );
			}
		}

		return $this->enforce_nesting( $blocks, $parent_role );
	}

	/**
	 * Resolve an element to a block, or null when unmappable.
	 *
	 * @param \DOMElement $node
	 * @param string      $tag
	 * @return array{kind:string,base_id:string}|null
	 */
	private function resolve( \DOMElement $node, string $tag ): ?array {
		if ( isset( self::CORE_ELEMENTS[ $tag ] ) ) {
			return array(
				'kind'    => 'core',
				'base_id' => self::CORE_ELEMENTS[ $tag ],
			);
		}

		if ( false !== strpos( $tag, '-' ) && isset( $this->custom_map[ $tag ] ) ) {
			return array(
				'kind'    => 'custom',
				'base_id' => $this->custom_map[ $tag ],
			);
		}

		$classes = self::classes_of( $node );

		foreach ( $this->claims as $claim ) {
			if ( $claim['tag'] !== $tag ) {
				continue;
			}
			if ( null === $claim['class'] || in_array( $claim['class'], $classes, true ) ) {
				return array(
					'kind'    => 'claimed',
					'base_id' => $claim['base_id'],
				);
			}
		}

		return null;
	}

	// -------------------------------------------------------------------------
	// Block builders
	// -------------------------------------------------------------------------

	/**
	 * Build a leaf block (heading, text, button, image, icon) from an element.
	 *
	 * @param \DOMElement $node
	 * @param string      $base_id
	 * @param string      $tag
	 * @return array
	 */
	private function build_leaf( \DOMElement $node, string $base_id, string $tag ): array {
		$idx   = $this->index( $base_id );
		$attrs = array();

		// A standalone icon element: decompose its classes into the icon block's
		// dedicated attributes (identity vs colour vs size vs absorbed spacing)
		// rather than cramming the whole class string into one field.
		$icon_attr = $idx->icon_attr();
		if ( 'i' === $tag && null !== $icon_attr ) {
			$attrs          = $this->decompose_icon( self::classes_of( $node ), $idx, $icon_attr );
			$attrs['title'] = '';
			$this->set_no_link( $attrs, $idx );
			return $this->finish( $base_id, $attrs );
		}

		$sources = array();
		$classes = self::classes_of( $node );

		// A button decomposes its Bootstrap variant classes into the block's own
		// link fields so it does not double the `btn` class or miss its colour.
		if ( $idx->has_attribute( 'link_bg' ) ) {
			$classes = $this->decompose_button( $classes, $idx, $attrs );
		}

		// A badge maps its style classes to the badge block's own fields, dropping
		// the bare `badge` class the block emits itself.
		if ( $idx->has_attribute( 'badge_bg' ) ) {
			$classes = $this->decompose_badge( $classes, $idx, $attrs );
		}

		$attrs = array_merge( $attrs, $this->consume_class_list( $classes, $idx, $sources ) );
		$this->consume_element_attributes( $node, $idx, $tag, $attrs );

		// An inline icon child (e.g. inside a button) fills the icon attribute.
		if ( null !== $icon_attr && ! isset( $attrs[ $icon_attr ] ) ) {
			$icon = $this->pull_inline_icon( $node );
			if ( '' !== $icon ) {
				$attrs[ $icon_attr ] = $icon;
			}
		}

		$text = $idx->text_attr();
		if ( null !== $text && 'img' !== $tag ) {
			// Always pin the text attribute — an empty value included — so the
			// block does not fall back to its placeholder copy ("Add Your Text").
			$attrs[ $text['name'] ] = $text['html'] ? $this->inner_html( $node ) : trim( $node->textContent );
		}

		// A leaf that captures no children as content (a <form> mapped to the
		// contact block, which renders its own fields) drops them — reported, not
		// silent, so the loss is visible.
		if ( null === $text && null === $icon_attr && self::has_element_children( $node ) ) {
			$this->note( sprintf( 'Dropped the hand-built markup inside <%s>; the block renders its own interior.', $tag ) );
		}

		return $this->finish( $base_id, $attrs, $sources );
	}

	/**
	 * Split a button's Bootstrap variant classes into the block's link fields:
	 * `btn-{v}` / `btn-outline-{v}` → link_bg, `btn-lg`/`btn-sm` → link_size,
	 * `rounded-pill` → rounded_size. The bare `btn` is dropped — the block emits
	 * it from its link type. Unrecognized classes are returned for normal
	 * consumption.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $attrs Modified in place.
	 * @return string[] Remaining classes.
	 */
	private function decompose_button( array $classes, ReverseIndex $idx, array &$attrs ): array {
		$remaining = array();

		foreach ( $classes as $class ) {
			if ( 'btn' === $class ) {
				continue;
			}
			if ( preg_match( '/^btn-(outline-[a-z]+|primary|secondary|success|danger|warning|info|light|dark|black|white|transparent)$/', $class, $m )
				&& $idx->is_valid_value( 'link_bg', $m[1] ) ) {
				$attrs['link_bg'] = $m[1];
				continue;
			}
			if ( 'btn-lg' === $class ) {
				$this->maybe_set( $attrs, $idx, 'link_size', 'large' );
				continue;
			}
			if ( 'btn-sm' === $class ) {
				$this->maybe_set( $attrs, $idx, 'link_size', 'small' );
				continue;
			}

			// rounded-pill and everything else fall through to normal consumption,
			// which records the source class and can spill it to css_class if the
			// block gates the attribute behind an unmet condition.
			$remaining[] = $class;
		}

		return $remaining;
	}

	/**
	 * Split a badge's Bootstrap classes into the badge block's fields:
	 * `rounded-pill` → badge_style, the bare `small` → font_size fs-sm (the block
	 * has no font_case field, so text-uppercase spills). The bare `badge` is
	 * dropped — the block emits it. bg-* / text-* / fs-* fall through to normal
	 * consumption (badge_bg / text_color / font_size via the reverse index).
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $attrs Modified in place.
	 * @return string[] Remaining classes.
	 */
	private function decompose_badge( array $classes, ReverseIndex $idx, array &$attrs ): array {
		$remaining = array();

		foreach ( $classes as $class ) {
			if ( 'badge' === $class ) {
				continue;
			}
			if ( 'rounded-pill' === $class && $idx->is_valid_value( 'badge_style', 'pill' ) ) {
				$attrs['badge_style'] = 'pill';
				continue;
			}
			if ( 'small' === $class && $idx->is_valid_value( 'font_size', 'fs-sm' ) ) {
				$attrs['font_size'] = 'fs-sm';
				continue;
			}

			$remaining[] = $class;
		}

		return $remaining;
	}

	/**
	 * Build a container block, inferring its structural role and recursing into
	 * its children.
	 *
	 * @param \DOMElement $node
	 * @param string      $base_id
	 * @param string      $tag
	 * @param int         $depth
	 * @return array
	 */
	private function build_container( \DOMElement $node, string $base_id, string $tag, int $depth ): array {
		$idx     = $this->index( $base_id );
		$attrs   = array();
		$classes = self::classes_of( $node );

		// Repair invalid flex `gap-*` on a `.row` before role inference strips the
		// `row` marker — the rewrite depends on seeing it.
		$classes = $this->repair_row_gap( $classes );

		list( $role, $role_value, $used ) = $this->infer_role( $classes, $idx );
		if ( $idx->has_attribute( 'container' ) ) {
			// Pin the container role explicitly — an empty value for a plain
			// wrapper — so it never falls back to the "container" default, which
			// would inject a max-width the design never asked for.
			$attrs['container'] = $role_value ?? '';
		}
		$classes = array_values( array_diff( $classes, $used ) );

		if ( $idx->has_attribute( 'html_tag' ) && 'div' !== $tag
			&& in_array( $tag, $idx->enum_values( 'html_tag' ), true ) ) {
			$attrs['html_tag'] = $tag;
		}

		$sources = array();
		$attrs   = array_merge( $attrs, $this->consume_class_list( $classes, $idx, $sources ) );
		$this->consume_element_attributes( $node, $idx, $tag, $attrs );

		$block           = $this->finish( $base_id, $attrs, $sources );
		$block['__role'] = $role;

		// A media object — an icon chip beside a heading/paragraph/label caption —
		// folds into a single icon-box carrying the icon, title and description,
		// rather than leaving a chip block next to stranded copy. Any trailing
		// content (a feature list, a CTA) after the caption is preserved: the folded
		// nodes are removed and the remaining children convert as siblings.
		$media = $this->detect_media_object( $node );
		if ( null !== $media ) {
			$iconbox = $this->build_media_iconbox( $media );
			foreach ( $media['consumed'] as $folded ) {
				if ( null !== $folded->parentNode ) {
					$folded->parentNode->removeChild( $folded );
				}
			}
			$block['innerBlocks'] = array_merge(
				array( $iconbox ),
				$this->walk( $node->childNodes, $depth + 1, $role )
			);
		} else {
			$block['innerBlocks'] = $this->walk( $node->childNodes, $depth + 1, $role );
		}

		return $block;
	}

	/**
	 * Build a core WordPress block from a synthetic vocabulary element
	 * (<bs-post-content> → core/post-content). A dynamic block with no
	 * attributes to author and no interior — any children are ignored and noted.
	 *
	 * @param \DOMElement $node
	 * @param string      $block_name The core block name.
	 * @return array
	 */
	private function build_core( \DOMElement $node, string $block_name ): array {
		if ( self::has_element_children( $node ) || '' !== trim( $node->textContent ) ) {
			$this->note( sprintf( 'Ignored contents of <%s> — it renders the dynamic post body itself.', strtolower( $node->tagName ) ) );
		}

		return array(
			'name'        => $block_name,
			'attributes'  => array(),
			'innerBlocks' => array(),
		);
	}

	/**
	 * Build a custom-element block: HTML attributes map to same-named block
	 * attributes (validated), classes are consumed. A leaf widget ignores any
	 * children (it renders its own interior); a container widget (a block whose
	 * `nested-block` is set — navbar, nav, accordion, tabs, modal, offcanvas)
	 * recurses, converting its child custom/core elements into inner blocks.
	 *
	 * @param \DOMElement $node
	 * @param string      $base_id
	 * @param int         $depth
	 * @return array
	 */
	private function build_custom( \DOMElement $node, string $base_id, int $depth = 0 ): array {
		$idx     = $this->index( $base_id );
		$sources = array();
		$attrs   = $this->consume_classes( $node, $idx, $sources );

		foreach ( $node->attributes as $attribute ) {
			$name  = strtolower( $attribute->name );
			$value = $attribute->value;

			if ( 'class' === $name ) {
				continue;
			}
			if ( 'style' === $name ) {
				$this->report['inline_styles_dropped'][] = array(
					'tag'   => strtolower( $node->tagName ),
					'style' => $value,
				);
				continue;
			}
			if ( HtmlSanitizer::is_event_handler( $name ) ) {
				continue;
			}

			if ( $idx->has_attribute( $name ) && $idx->is_valid_value( $name, $value ) ) {
				$attrs[ $name ] = $value;
			} else {
				$this->note( sprintf( 'Dropped unknown or invalid attribute "%s" on <%s>.', $name, strtolower( $node->tagName ) ) );
			}
		}

		$block = $this->finish( $base_id, $attrs, $sources );

		if ( $this->is_container( $base_id ) ) {
			$block['innerBlocks'] = $this->convert_custom_children( $node, $base_id, $depth );
		} elseif ( self::has_element_children( $node ) ) {
			$this->note( sprintf( 'Ignored children of custom element <%s> (renders its own interior).', strtolower( $node->tagName ) ) );
		}

		return $block;
	}

	/**
	 * Convert the child elements of a container widget into inner blocks. Only
	 * custom-element and core-element children are valid widget interiors; when the
	 * block declares an allowlist, a child outside it is ignored and noted. Any
	 * non-widget child (raw HTML the block does not model) is ignored and noted.
	 *
	 * @param \DOMElement $node
	 * @param string      $base_id
	 * @param int         $depth
	 * @return array
	 */
	private function convert_custom_children( \DOMElement $node, string $base_id, int $depth ): array {
		$parent_tag = str_replace( '_', '-', $base_id );

		if ( $depth >= self::MAX_DEPTH ) {
			$this->note( sprintf( 'Truncated nested widget <%s> at the depth limit.', $parent_tag ) );
			return array();
		}

		$allowed = $this->allowed_child_ids( $base_id );
		$inner   = array();

		foreach ( self::element_children( $node ) as $child ) {
			if ( $this->node_count >= self::MAX_NODES ) {
				$this->note( 'Truncated nested widgets at the node limit.' );
				break;
			}
			++$this->node_count;

			$child_tag  = strtolower( $child->tagName );
			$resolution = $this->resolve( $child, $child_tag );

			if ( null === $resolution || ! in_array( $resolution['kind'], array( 'custom', 'core' ), true ) ) {
				$this->note( sprintf( 'Ignored non-widget child <%s> of <%s>.', $child_tag, $parent_tag ) );
				continue;
			}

			if ( null !== $allowed && ! in_array( $resolution['base_id'], $allowed, true ) ) {
				$this->note( sprintf( 'Ignored child <%s> — not allowed inside <%s>.', $child_tag, $parent_tag ) );
				continue;
			}

			if ( 'core' === $resolution['kind'] ) {
				$inner[] = $this->build_core( $child, $resolution['base_id'] );
			} else {
				$inner[] = $this->build_custom( $child, $resolution['base_id'], $depth + 1 );
			}
		}

		return $inner;
	}

	/**
	 * The base_ids a container widget allows as children, or null when it accepts
	 * any block (no allowlist declared).
	 *
	 * @param string $base_id
	 * @return string[]|null
	 */
	private function allowed_child_ids( string $base_id ): ?array {
		$allowed = $this->options[ $base_id ]['allowed-blocks'] ?? array();
		if ( empty( $allowed ) ) {
			return null;
		}

		return Catalog::block_names_to_ids_public( $allowed );
	}

	// -------------------------------------------------------------------------
	// Composite decomposition (icon boxes)
	// -------------------------------------------------------------------------

	/**
	 * Whether an element is an icon + text item — the icon-box idiom — rather
	 * than a real container: it holds a Font Awesome icon and visible text, and
	 * everything inside it is inline (no headings, images or nested blocks that
	 * would convert on their own).
	 *
	 * Interactive and claimed elements are hard fold boundaries (round 9): a
	 * <button>, image, form control or custom element inside means this is a
	 * container of separate blocks, not a single item. A lone <a> is allowed only
	 * when it wraps the whole item (a linked icon row → custom URL); two links, or
	 * a link that only covers part of the content, disqualify the fold so a CTA
	 * group (a flex wrapper of buttons) is never swallowed.
	 *
	 * @param \DOMElement $node
	 * @return bool
	 */
	private function is_icon_item( \DOMElement $node ): bool {
		if ( '' === $this->icon_block || null === $this->first_fa_icon( $node ) ) {
			return false;
		}
		if ( '' === trim( $node->textContent ) ) {
			return false;
		}

		$icon    = $this->first_fa_icon( $node );
		$inline  = array( 'i', 'span', 'small', 'strong', 'em', 'b', 'u', 'br' );
		$anchors = 0;
		foreach ( $node->getElementsByTagName( '*' ) as $descendant ) {
			$dtag = strtolower( $descendant->tagName );
			if ( 'a' === $dtag ) {
				++$anchors;
				// The single permitted link must be a plain (unclaimed) link wrapping
				// the icon — one linked item. A claimed link (a.btn → bs_button) is
				// its own block and outranks the composite, so it is never a wrapper.
				if ( $anchors > 1
					|| null !== $this->resolve( $descendant, 'a' )
					|| ! self::contains_node( $descendant, $icon ) ) {
					return false;
				}
				continue;
			}
			if ( ! in_array( $dtag, $inline, true ) ) {
				return false;
			}
		}

		// A single icon + label — not a multi-run inline row (a trust line with
		// bullet-separated spans, a justify-between assurance strip). More than one
		// text-bearing child element means several distinct label units, which belong
		// in their own blocks rather than fused into one title. Only the bare icon
		// element itself is skipped: a label that carries its own <i> still counts as
		// a text run, so a two-label row where one label holds an icon is not folded.
		$text_runs = 0;
		foreach ( self::element_children( $node ) as $child ) {
			if ( $child === $icon ) {
				continue;
			}
			if ( '' !== trim( $child->textContent ) ) {
				++$text_runs;
			}
		}
		if ( $text_runs > 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Build a divider from an <hr>: the generic text-fallback wrapper carrying the
	 * rule's own classes plus border-top (a Bootstrap hr is a top border).
	 *
	 * @param \DOMElement $node
	 * @return array
	 */
	private function build_divider( \DOMElement $node ): array {
		$idx     = $this->index( $this->text_fallback );
		$sources = array();
		$attrs   = $this->consume_class_list( self::classes_of( $node ), $idx, $sources );

		$attrs['css_class'] = trim( ( $attrs['css_class'] ?? '' ) . ' border-top' );

		$target = $idx->text_attr();
		if ( null !== $target ) {
			$attrs[ $target['name'] ] = '';
		}

		return $this->finish( $this->text_fallback, $attrs, $sources );
	}

	/**
	 * Wrap a loose text string in the text-fallback block.
	 *
	 * @param string $text
	 * @return array
	 */
	private function build_text_node( string $text ): array {
		$idx   = $this->index( $this->text_fallback );
		$attrs = array();

		$target = $idx->text_attr();
		if ( null !== $target ) {
			$attrs[ $target['name'] ] = $text;
		}

		return $this->finish( $this->text_fallback, $attrs );
	}

	/**
	 * Whether an element is an icon chip: a coloured or rounded wrapper whose only
	 * content is a Font Awesome icon, no text. The chip styling (subtle
	 * background, rounded corners, padding) belongs on the icon block, not a bare
	 * container wrapping a separate icon.
	 *
	 * @param \DOMElement $node
	 * @param string      $tag
	 * @return bool
	 */
	private function is_icon_chip( \DOMElement $node, string $tag ): bool {
		if ( '' === $this->icon_block || 'i' === $tag || null === $this->first_fa_icon( $node ) ) {
			return false;
		}
		// An interactive element is a button even when it holds only an icon: an
		// icon-only <a class="btn"> / <button> is a bs_button with icon_class, never
		// a chip. It outranks the composite (round 9's hard-boundary rule).
		if ( 'a' === $tag || 'button' === $tag ) {
			return false;
		}
		if ( '' !== trim( $node->textContent ) ) {
			return false;
		}

		// Only-inline content (the icon, maybe nested spans), and a chip marker.
		foreach ( $node->getElementsByTagName( '*' ) as $descendant ) {
			if ( ! in_array( strtolower( $descendant->tagName ), array( 'i', 'span', 'svg', 'path' ), true ) ) {
				return false;
			}
		}
		foreach ( self::classes_of( $node ) as $class ) {
			if ( preg_match( '/^(bg-|rounded)/', $class ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build an icon chip as a single icon-box: the wrapper's background, rounding
	 * and padding map to the icon block's fill fields (icon_bg, icon_rounded_size,
	 * iconbox_size), the icon's own classes decompose as usual, and the flex
	 * wrapper utilities spill to css_class so nothing is lost.
	 *
	 * @param \DOMElement $node
	 * @return array
	 */
	private function build_icon_chip( \DOMElement $node ): array {
		$base_id = $this->icon_block;
		$idx     = $this->index( $base_id );

		$sources              = array();
		$attrs                = $this->chip_attributes( $node, $idx, $sources );
		$attrs['title']       = '';
		$attrs['description'] = '';
		$this->set_no_link( $attrs, $idx );

		return $this->finish( $base_id, $attrs, $sources );
	}

	/**
	 * Extract the icon-box attributes for a chip: the icon's own classes plus the
	 * wrapper's fill styling (background, rounding). A bare `<i>` has no wrapper,
	 * so only the icon decomposes. Padding and flex utilities on the wrapper fall
	 * through to normal consumption so the chip's spacing survives.
	 *
	 * @param \DOMElement  $node
	 * @param ReverseIndex $idx
	 * @param array        $sources
	 * @return array
	 */
	private function chip_attributes( \DOMElement $node, ReverseIndex $idx, array &$sources ): array {
		$icon  = 'i' === strtolower( $node->tagName ) ? $node : $this->first_fa_icon( $node );
		$attrs = null !== $icon ? $this->decompose_icon( self::classes_of( $icon ), $idx, (string) $idx->icon_attr() ) : array();

		// A bare icon has no wrapper chip styling to fold in.
		if ( $node === $icon ) {
			return $attrs;
		}

		$remaining = array();
		$filled    = false;

		foreach ( self::classes_of( $node ) as $class ) {
			if ( preg_match( '/^bg-(.+)$/', $class, $m ) && $idx->is_valid_value( 'icon_bg', $m[1] ) ) {
				$attrs['icon_bg'] = $m[1];
				$filled           = true;
				continue;
			}
			if ( preg_match( '/^text-(.+)$/', $class, $m ) && $idx->is_valid_value( 'icon_color', $m[1] ) ) {
				$attrs['icon_color'] = $m[1];
				continue;
			}
			if ( in_array( $class, array( 'rounded-circle', 'rounded-pill' ), true ) ) {
				$attrs['icon_rounded_size'] = 'rounded-circle' === $class ? 'circle' : 'pill';
				$filled                     = true;
				continue;
			}
			if ( preg_match( '/^rounded-(\d)$/', $class, $m ) && $idx->is_valid_value( 'icon_rounded_size', $m[1] ) ) {
				$attrs['icon_rounded_size'] = $m[1];
				$filled                     = true;
				continue;
			}

			// Padding and flex-wrapper utilities fall through to normal consumption
			// (padding expands to per-side attributes, flex spills to css_class), so
			// the chip's spacing survives exactly as authored.
			$remaining[] = $class;
		}

		// The fill variant is what renders icon_bg + icon_rounded_size; without a
		// chip marker there is nothing to fill.
		if ( $filled ) {
			$this->maybe_set( $attrs, $idx, 'icon_type', 'iconbox-fill' );
		}

		return array_merge( $attrs, $this->consume_class_list( $remaining, $idx, $sources ) );
	}

	/**
	 * Detect the media-object pattern inside a container: an icon chip (or bare
	 * icon) as the first child, followed by a pure heading/paragraph text stack —
	 * either as direct siblings ([chip][h][p]) or wrapped in one element
	 * ([chip][div > h + p]). Returns the parts to fold, or null when the shape is
	 * anything else (the guardrail: a card with a button, link, image, or a
	 * second heading stays as separate blocks so nothing is swallowed).
	 *
	 * @param \DOMElement $node
	 * @return array{icon:\DOMElement,heading:?\DOMElement,paragraph:?\DOMElement,layout:string}|null
	 */
	private function detect_media_object( \DOMElement $node ): ?array {
		if ( '' === $this->icon_block ) {
			return null;
		}

		$children = self::element_children( $node );
		if ( count( $children ) < 2 ) {
			return null;
		}

		// The icon leads the row ([chip][copy]) — a chip wrapper or a bare Font
		// Awesome icon. A trailing icon is only recognized for the narrow
		// "name + verified badge" pattern: a single heading/label copy element
		// followed by a bare decorative <i>, so ordinary [copy][chip] cards are not
		// folded from the wrong end.
		$is_icon = function ( \DOMElement $el ): bool {
			$tag = strtolower( $el->tagName );
			return $this->is_icon_chip( $el, $tag )
				|| ( 'i' === $tag && null !== $this->first_fa_icon( $el ) );
		};

		$last = $children[ count( $children ) - 1 ];
		if ( $is_icon( $children[0] ) ) {
			$first = $children[0];
			$rest  = array_slice( $children, 1 );
		} elseif ( 2 === count( $children )
			&& 'i' === strtolower( $last->tagName )
			&& null !== $this->first_fa_icon( $last )
			&& ! $is_icon( $children[0] ) ) {
			$first = $last;
			$rest  = array_slice( $children, 0, -1 );
		} else {
			return null;
		}

		// The text stack is either the remaining siblings ([chip][h][p], or the
		// dominant [chip][span] label shape) or the children of a single wrapper
		// element ([chip][div > h + p]); the wrapper carries the stack's alignment
		// (a `text-start` block). A bare inline label (<span>/<small>/<strong> with
		// no element children of its own) is the stack itself, NOT a wrapper.
		$wrapper = null;
		if ( 1 === count( $rest )
			&& in_array( strtolower( $rest[0]->tagName ), array( 'div', 'span' ), true )
			&& self::has_block_children( $rest[0] ) ) {
			// A wrapper groups the copy — a div or span holding block children (a
			// heading/paragraph, or two <div> caption rows). Its whole content must
			// be foldable copy; a div/span carrying only inline content is itself a
			// caption line and is left in the stack.
			$wrapper    = $rest[0];
			$stack      = self::element_children( $wrapper );
			$allow_tail = false;
		} else {
			$stack      = $rest;
			$allow_tail = true;
		}

		if ( empty( $stack ) ) {
			return null;
		}

		// Slot fill: the caption run right after the icon reduces to a title line
		// and/or a description line. A heading fills the title, a paragraph the
		// description; an inline label (span/small/strong/b/em) or an inline-only
		// text line (a <div> caption row) fills the title first, then the
		// description. A line may carry inline markup (<br>, <strong>) but no
		// block-level children.
		//
		// In the sibling form, the run is contiguous from the icon: the first
		// non-caption element (a list, a CTA, an image, a second heading/paragraph)
		// ends the run and stays as a following sibling — the caption still folds so
		// nothing is stranded, and nothing beyond it is swallowed. In the wrapper
		// form the entire wrapper must be caption, or the fold is abandoned.
		$line_tags        = array( 'span', 'small', 'strong', 'b', 'em', 'div' );
		$title_el         = null;
		$title_is_heading = false;
		$desc_el          = null;
		$consumed_copy    = array();
		foreach ( $stack as $element ) {
			$stag = strtolower( $element->tagName );
			$done = false;

			if ( in_array( $stag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
				if ( null !== $title_el ) {
					$done = true;
				} else {
					$title_el         = $element;
					$title_is_heading = true;
				}
			} elseif ( 'p' === $stag ) {
				if ( null !== $desc_el ) {
					$done = true;
				} else {
					$desc_el = $element;
				}
			} elseif ( in_array( $stag, $line_tags, true ) && ! self::has_block_children( $element ) ) {
				if ( null === $title_el ) {
					$title_el = $element;
				} elseif ( null === $desc_el ) {
					$desc_el = $element;
				} else {
					$done = true;
				}
			} else {
				$done = true;
			}

			if ( $done ) {
				if ( $allow_tail ) {
					break;
				}
				return null;
			}

			$consumed_copy[] = $element;
		}

		if ( null === $title_el && null === $desc_el ) {
			return null;
		}

		// Layout: a flex row (not a column) puts the chip to the left of the copy.
		$container_classes = self::classes_of( $node );
		$layout            = ( in_array( 'd-flex', $container_classes, true ) || in_array( 'd-inline-flex', $container_classes, true ) )
			&& ! in_array( 'flex-column', $container_classes, true )
			? 'left'
			: 'top';

		// The nodes the fold absorbs: the icon plus either the grouping wrapper (which
		// holds the copy) or the contiguous caption siblings. build_container removes
		// these before walking so any following list/CTA still converts as a sibling.
		$consumed = null !== $wrapper
			? array( $first, $wrapper )
			: array_merge( array( $first ), $consumed_copy );

		return array(
			'icon'             => $first,
			'wrapper'          => $wrapper,
			'title_el'         => $title_el,
			'title_is_heading' => $title_is_heading,
			'desc_el'          => $desc_el,
			'layout'           => $layout,
			'consumed'         => $consumed,
		);
	}

	/**
	 * Fold a detected media object into one icon-box: the chip's icon and fill
	 * styling, the heading as the title (its tag and classes to the title family),
	 * and the paragraph as the description (its classes to the desc family).
	 *
	 * @param array $media
	 * @return array
	 */
	private function build_media_iconbox( array $media ): array {
		$base_id = $this->icon_block;
		$idx     = $this->index( $base_id );

		// Three source elements fold into one block whose per-side spacing and
		// css_class are single-valued, so their leftover utilities are collected
		// into one merged css_class (the scorer expands it either way) rather than
		// each consume overwriting the last.
		$spill = array();
		$attrs = $this->fold_chip( $media['icon'], $idx, $spill );

		// The title comes from the heading, an inline label, or the first text line
		// (a <div> caption row). A heading contributes its tag as title_tag; any
		// other line keeps the block's own title_tag default.
		$title_source = $media['title_el'];
		if ( null !== $title_source ) {
			$attrs['title'] = $this->flatten_text( $title_source );
			if ( ! empty( $media['title_is_heading'] ) ) {
				$this->maybe_set( $attrs, $idx, 'title_tag', strtolower( $title_source->tagName ) );
			}
			$this->fold_text_classes( self::classes_of( $title_source ), $idx, 'title', $attrs, $spill );

			// The title element is the outermost folded copy: its explicit bottom
			// margin (including mb-0) becomes the icon-box margin, so a blank does
			// not fall through to the block's default gap.
			foreach ( self::classes_of( $title_source ) as $class ) {
				if ( preg_match( '/^mb-(\d+)$/', $class, $mb ) && $idx->is_valid_value( 'mb', $mb[1] ) ) {
					$attrs['mb'] = $mb[1];
				}
			}
		} else {
			$attrs['title'] = '';
		}

		$desc_source = $media['desc_el'];
		if ( null !== $desc_source && $idx->has_attribute( 'description' ) ) {
			$attrs['description'] = $this->inner_html( $desc_source );
			$this->fold_text_classes( self::classes_of( $desc_source ), $idx, 'desc', $attrs, $spill );
		}

		// Alignment: the stack wrapper's (or a text element's) text-* alignment
		// applies to all three parts, so a left-aligned card stops rendering the
		// block's centred default on icon, title and description.
		$this->apply_stack_alignment( $media, $idx, $attrs, $spill );

		// Chip left of the copy is the flex-row layout; a vertical stack keeps the
		// block's default top layout.
		if ( 'left' === $media['layout'] ) {
			$this->maybe_set( $attrs, $idx, 'icon_position', 'left' );
		}

		if ( ! empty( $spill ) ) {
			$attrs['css_class'] = implode( ' ', array_values( array_unique( $spill ) ) );
		}
		$this->set_no_link( $attrs, $idx );

		return $this->finish( $base_id, $attrs );
	}

	/**
	 * Map the stack's text-* alignment (from the wrapper, else the heading) onto
	 * all three of the icon-box's alignment families — icon/title/desc, at each
	 * breakpoint — so the folded card keeps its authored alignment instead of the
	 * block's centred default.
	 *
	 * @param array        $media
	 * @param ReverseIndex $idx
	 * @param array        $attrs Modified in place.
	 * @param array        $spill Modified in place (consumed alignment removed).
	 */
	private function apply_stack_alignment( array $media, ReverseIndex $idx, array &$attrs, array &$spill ): void {
		$source = $media['wrapper'] ?? ( $media['title_el'] ?? null );
		if ( null === $source ) {
			return;
		}

		foreach ( self::classes_of( $source ) as $class ) {
			if ( ! preg_match( '/^text-((sm|md|lg|xl|xxl)-)?(start|end|center)$/', $class ) ) {
				continue;
			}
			// Bootstrap breakpoint infix maps to the attribute's _md/_lg suffix.
			$suffix = '';
			if ( preg_match( '/-(md|lg)-/', $class, $bp ) ) {
				$suffix = '_' . $bp[1];
			} elseif ( preg_match( '/-(sm|xl|xxl)-/', $class ) ) {
				$spill[] = $class; // No sm/xl/xxl attribute — keep verbatim.
				continue;
			}
			$applied = false;
			foreach ( array( 'icon_text_align', 'title_text_align', 'desc_text_align' ) as $family ) {
				$attr = $family . $suffix;
				if ( $idx->has_attribute( $attr ) && $idx->is_valid_value( $attr, $class ) ) {
					$attrs[ $attr ] = $class;
					$applied        = true;
				}
			}
			if ( ! $applied ) {
				$spill[] = $class;
			}
		}
	}

	/**
	 * Fold a chip into icon-box attributes for the media composite: the icon's own
	 * classes plus the wrapper's fill styling; every other wrapper class is added
	 * to the shared spill list.
	 *
	 * @param \DOMElement  $node
	 * @param ReverseIndex $idx
	 * @param array        $spill Accumulated leftover classes (by reference).
	 * @return array
	 */
	private function fold_chip( \DOMElement $node, ReverseIndex $idx, array &$spill ): array {
		$icon  = 'i' === strtolower( $node->tagName ) ? $node : $this->first_fa_icon( $node );
		$attrs = null !== $icon ? $this->decompose_icon( self::classes_of( $icon ), $idx, (string) $idx->icon_attr() ) : array();

		if ( $node === $icon ) {
			return $attrs;
		}

		$filled    = false;
		$remaining = array();
		foreach ( self::classes_of( $node ) as $class ) {
			if ( preg_match( '/^bg-(.+)$/', $class, $m ) && $idx->is_valid_value( 'icon_bg', $m[1] ) ) {
				$attrs['icon_bg'] = $m[1];
				$filled           = true;
			} elseif ( preg_match( '/^text-(.+)$/', $class, $m ) && $idx->is_valid_value( 'icon_color', $m[1] ) ) {
				$attrs['icon_color'] = $m[1];
			} elseif ( in_array( $class, array( 'rounded-circle', 'rounded-pill' ), true ) ) {
				$attrs['icon_rounded_size'] = 'rounded-circle' === $class ? 'circle' : 'pill';
				$filled                     = true;
			} elseif ( preg_match( '/^rounded-(\d)$/', $class, $m ) && $idx->is_valid_value( 'icon_rounded_size', $m[1] ) ) {
				$attrs['icon_rounded_size'] = $m[1];
				$filled                     = true;
			} elseif ( self::is_chip_layout_class( $class ) ) {
				// The chip's own flex/centering utilities lay out the icon inside
				// the coloured box, which the icon-box reproduces. Spilling them to
				// the icon-box wrapper would turn the whole card into a flex row.
				continue;
			} else {
				$remaining[] = $class;
			}
		}

		if ( $filled ) {
			$this->maybe_set( $attrs, $idx, 'icon_type', 'iconbox-fill' );
		}

		// The chip's padding and other spacing consume into per-side attributes
		// (p-3 → pt/pr/pb/pl) rather than riding css_class, so the folded icon-box
		// keeps an empty css_class while the spacing still renders.
		$chip_sources = array();
		$consumed     = $this->consume_class_list( $remaining, $idx, $chip_sources );
		if ( isset( $consumed['css_class'] ) ) {
			$spill = array_merge( $spill, preg_split( '/\s+/', $consumed['css_class'] ) );
			unset( $consumed['css_class'] );
		}

		return array_merge( $attrs, $consumed );
	}

	/**
	 * Route a folded text element's classes: colour and size to its attribute
	 * family (`title_*`/`desc_*`), everything else to the shared spill list.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param string       $prefix
	 * @param array        $attrs Modified in place.
	 * @param array        $spill Modified in place.
	 */
	private function fold_text_classes( array $classes, ReverseIndex $idx, string $prefix, array &$attrs, array &$spill ): void {
		foreach ( $classes as $class ) {
			if ( preg_match( '/^text-(.+)$/', $class, $m ) && $idx->is_valid_value( $prefix . '_color', $m[1] ) ) {
				$attrs[ $prefix . '_color' ] = $m[1];
			} elseif ( ( preg_match( '/^(fs-|h[1-6]$|display-)/', $class ) || 'small' === $class )
				&& $idx->is_valid_value( $prefix . '_size', 'small' === $class ? 'fs-sm' : $class ) ) {
				// The bare `small` is a font-size utility → fs-sm, never a spill.
				$attrs[ $prefix . '_size' ] = 'small' === $class ? 'fs-sm' : $class;
			} elseif ( preg_match( '/^(fw-|fst-)/', $class ) && $idx->is_valid_value( $prefix . '_font_weight', $class ) ) {
				$attrs[ $prefix . '_font_weight' ] = $class;
			} elseif ( in_array( $class, array( 'text-uppercase', 'text-lowercase', 'text-capitalize' ), true ) && $idx->is_valid_value( $prefix . '_font_case', $class ) ) {
				$attrs[ $prefix . '_font_case' ] = $class;
			} elseif ( preg_match( '/^mb-(\d+)$/', $class, $m ) && $idx->has_attribute( $prefix . '_pb' ) && $idx->is_valid_value( $prefix . '_pb', $m[1] ) ) {
				// An explicit bottom margin (including mb-0) maps to the part's
				// padding-bottom, so blank ≠ zero — the block's default gap is not
				// injected over an explicit zero.
				$attrs[ $prefix . '_pb' ] = $m[1];
			} else {
				$spill[] = $class;
			}
		}
	}

	/**
	 * Build an icon-box from an icon + text item, splitting the source classes
	 * across the block's dedicated attributes so its copy and colours survive
	 * where the block can manage them. Matches the shape of a hand-authored
	 * icon-box list item, including the list-scale defaults.
	 *
	 * @param \DOMElement $node
	 * @return array
	 */
	private function build_icon_item( \DOMElement $node ): array {
		$base_id = $this->icon_block;
		$idx     = $this->index( $base_id );

		$icon  = $this->first_fa_icon( $node );
		$attrs = $this->decompose_icon( self::classes_of( $icon ), $idx, (string) $idx->icon_attr() );
		if ( null !== $icon->parentNode ) {
			$icon->parentNode->removeChild( $icon );
		}

		// A wrapping link becomes a custom URL; otherwise the link type MUST be
		// set to "none" — the block default is "home", which would silently link
		// every converted item to the site homepage.
		$link = $node->getElementsByTagName( 'a' );
		$href = $link->length ? trim( $link->item( 0 )->getAttribute( 'href' ) ) : '';
		if ( '' !== $href && '#' !== $href && ! HtmlSanitizer::is_dangerous_url( $href )
			&& $idx->has_attribute( 'custom_url' ) ) {
			$attrs['custom_url'] = $href;
			$this->maybe_set( $attrs, $idx, 'type', 'custom' );
		} else {
			$this->maybe_set( $attrs, $idx, 'type', 'none' );
		}

		// The remaining text of the item is the title (always set — the block
		// default is placeholder copy). When the copy leads with a bold run
		// (<strong>Systemic Efficiency:</strong> …), split it: the bold lead
		// (trailing colon stripped) is the title, the remainder the description, so a
		// "label: sentence" caption no longer fuses into one over-long title. A <br>
		// in a plain label reads as a space.
		$split = $this->split_bold_lead( $node );
		if ( null !== $split && $idx->has_attribute( 'description' ) ) {
			$attrs['title']       = $split['title'];
			$attrs['description'] = $split['description'];
		} else {
			$attrs['title'] = $this->flatten_text( $node );
		}

		// The item's own classes plus the inner text element's classes
		// (a <span class="text-body small">) all feed the title: colour and size
		// map to the title attributes, everything else spills to css_class. The
		// wrapper's and chip's own flex/gap layout classes (d-flex, align-items-*,
		// gap-*) are reproduced by the icon-box template, so they are absorbed like
		// chip internals rather than spilled.
		$classes  = array();
		$absorbed = array();
		foreach ( self::descendant_classes( $node ) as $class ) {
			if ( self::is_chip_layout_class( $class ) ) {
				$absorbed[] = $class;
			} else {
				$classes[] = $class;
			}
		}
		if ( ! empty( $absorbed ) ) {
			$this->note( sprintf( 'Absorbed layout utilities "%s" — the icon-box lays out its own icon and copy.', implode( ' ', array_unique( $absorbed ) ) ) );
		}

		$sources = array();
		$attrs   = array_merge( $attrs, $this->consume_prefixed_classes( $classes, $idx, $sources, 'title' ) );

		// Structural list-item shape: a left icon beside a div-tag title. Size and
		// colour are left to neutralization (unset → neutral), so the item injects
		// no scale the source did not carry.
		$this->maybe_set( $attrs, $idx, 'title_tag', 'div' );
		$this->maybe_set( $attrs, $idx, 'icon_position', 'left' );

		return $this->finish( $base_id, $attrs, $sources );
	}

	/**
	 * Split an icon element's classes into the icon block's attributes: Font
	 * Awesome identity classes stay in the icon field; a text colour token
	 * becomes icon_color; an fs-* size becomes icon_size; spacing utilities the
	 * block renders itself (me-2) are absorbed and noted.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param string       $icon_attr The block's icon-class attribute name.
	 * @return array
	 */
	private function decompose_icon( array $classes, ReverseIndex $idx, string $icon_attr ): array {
		$identity = array();
		$attrs    = array();

		foreach ( $classes as $class ) {
			if ( preg_match( '/^fa[a-z]?(-|$)/', $class ) ) {
				$identity[] = $class;
				continue;
			}
			if ( preg_match( '/^text-(.+)$/', $class, $m ) && $idx->has_attribute( 'icon_color' )
				&& $idx->is_valid_value( 'icon_color', $m[1] ) ) {
				$attrs['icon_color'] = $m[1];
				continue;
			}
			if ( 0 === strpos( $class, 'fs-' ) && $idx->has_attribute( 'icon_size' )
				&& $idx->is_valid_value( 'icon_size', $class ) ) {
				$attrs['icon_size'] = $class;
				continue;
			}
			if ( preg_match( '/^[mp][tbeslrxy]?-/', $class ) ) {
				$this->note( sprintf( 'Absorbed icon spacing utility "%s" — the icon block renders its own gap.', $class ) );
				continue;
			}
			$identity[] = $class;
		}

		if ( '' !== $icon_attr && ! empty( $identity ) ) {
			$attrs[ $icon_attr ] = implode( ' ', $identity );
		}

		return $attrs;
	}

	/**
	 * Consume a text element's own classes, routing its colour and size to a
	 * prefixed attribute family (`title_*` or `desc_*`) and everything else
	 * through normal class consumption.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $sources
	 * @param string       $prefix Attribute prefix, e.g. 'title' or 'desc'.
	 * @return array
	 */
	private function consume_prefixed_classes( array $classes, ReverseIndex $idx, array &$sources, string $prefix ): array {
		$attrs     = array();
		$remaining = array();

		foreach ( $classes as $class ) {
			if ( preg_match( '/^text-(.+)$/', $class, $m ) && $idx->has_attribute( $prefix . '_color' )
				&& $idx->is_valid_value( $prefix . '_color', $m[1] ) ) {
				$attrs[ $prefix . '_color' ] = $m[1];
				continue;
			}
			// A font-size class — fs-*, h1-h6, display-*, or the bare `small`
			// (Bootstrap's fs-sm) — that the size enum accepts becomes the size
			// attribute (the tag drives the element; the class drives the scale).
			$size = 'small' === $class ? 'fs-sm' : $class;
			if ( ( preg_match( '/^(fs-|h[1-6]$|display-)/', $class ) || 'small' === $class )
				&& $idx->has_attribute( $prefix . '_size' ) && $idx->is_valid_value( $prefix . '_size', $size ) ) {
				$attrs[ $prefix . '_size' ] = $size;
				continue;
			}
			// A font-weight/style class (fw-*, fst-*) → the part's weight attribute.
			if ( preg_match( '/^(fw-|fst-)/', $class ) && $idx->has_attribute( $prefix . '_font_weight' )
				&& $idx->is_valid_value( $prefix . '_font_weight', $class ) ) {
				$attrs[ $prefix . '_font_weight' ] = $class;
				continue;
			}
			$remaining[] = $class;
		}

		return array_merge( $attrs, $this->consume_class_list( $remaining, $idx, $sources ) );
	}

	/**
	 * The first Font Awesome `<i>` descendant of an element, or null.
	 *
	 * @param \DOMElement $node
	 * @return \DOMElement|null
	 */
	private function first_fa_icon( \DOMElement $node ): ?\DOMElement {
		// The node itself may be the icon (a bare <i class="fa-…"> as a media
		// object's first child), not only a descendant.
		if ( 'i' === strtolower( $node->tagName ) && false !== strpos( $node->getAttribute( 'class' ), 'fa' ) ) {
			return $node;
		}

		foreach ( $node->getElementsByTagName( 'i' ) as $icon ) {
			if ( false !== strpos( $icon->getAttribute( 'class' ), 'fa' ) ) {
				return $icon;
			}
		}

		return null;
	}

	/**
	 * Set the icon block's link type to "none" so it does not default to the
	 * site homepage.
	 *
	 * @param array        $attrs
	 * @param ReverseIndex $idx
	 */
	private function set_no_link( array &$attrs, ReverseIndex $idx ): void {
		$this->maybe_set( $attrs, $idx, 'type', 'none' );
	}

	/**
	 * Set an attribute only when the block declares it and the value is valid.
	 *
	 * @param array        $attrs
	 * @param ReverseIndex $idx
	 * @param string       $name
	 * @param string       $value
	 */
	private function maybe_set( array &$attrs, ReverseIndex $idx, string $name, string $value ): void {
		if ( $idx->has_attribute( $name ) && $idx->is_valid_value( $name, $value ) ) {
			$attrs[ $name ] = $value;
		}
	}

	/**
	 * Assemble a finished block. An attribute whose element_require is unmet is
	 * first given a chance to be satisfied (its unambiguous equality
	 * prerequisites are set); if it still cannot be met, the class that produced
	 * it is spilled back into css_class so it still renders, rather than being
	 * dropped. Only attributes with no recoverable source class are dropped.
	 *
	 * @param string $base_id
	 * @param array  $attrs
	 * @param array  $sources attribute name => the class it came from.
	 * @return array
	 */
	private function finish( string $base_id, array $attrs, array $sources = array() ): array {
		$idx = $this->index( $base_id );

		foreach ( array_keys( $attrs ) as $name ) {
			$arg = $idx->argument( $name );
			if ( null === $arg || empty( $arg['element_require'] ) || ! is_string( $arg['element_require'] ) ) {
				continue;
			}

			if ( RequireExpression::evaluate( $arg['element_require'], $attrs ) ) {
				continue;
			}

			// The border-radius family is gated behind a visible border in the editor
			// form, but build_aui_class emits the radius class regardless — so keep the
			// attribute even when the border condition is unmet (a rounded-4 card with
			// border-0 still renders rounded-4), rather than spilling it to css_class.
			if ( in_array( $name, self::RENDER_INDEPENDENT_ATTRS, true ) ) {
				continue;
			}

			// A parse that skipped a term it could not read cannot be trusted to
			// justify dropping. Keep the attribute the source class explicitly asked for.
			$parsed = RequireExpression::parse( $arg['element_require'] );
			if ( null === $parsed || empty( $parsed['exact'] ) ) {
				continue;
			}

			// The class implies a prerequisite the block hides behind a condition;
			// set it where the condition is an unambiguous equality.
			$this->try_satisfy( $arg['element_require'], $attrs, $idx );
			if ( RequireExpression::evaluate( $arg['element_require'], $attrs ) ) {
				continue;
			}

			// Still unmet — keep the class verbatim in css_class if we can, so the
			// design survives even though the block cannot hold it as an attribute.
			$source = $sources[ $name ] ?? '';
			unset( $attrs[ $name ] );

			if ( '' !== $source ) {
				$attrs['css_class']                    = trim( ( $attrs['css_class'] ?? '' ) . ' ' . $source );
				$words                                 = count( explode( ' ', $source ) );
				$this->report['classes_as_attributes'] = max( 0, $this->report['classes_as_attributes'] - $words );
				$this->report['classes_as_css_class'] += $words;
			} else {
				$this->note( sprintf( 'Dropped attribute "%s" — its display condition could not be met.', $name ) );
			}
		}

		$this->neutralize_defaults( $attrs, $idx );

		// Icon identity classes belong in a block's icon field, never in css_class:
		// a fa-* token in css_class means a stray icon leaked from a composite. Strip
		// it here — the universal choke point — so no block ever renders one.
		if ( isset( $attrs['css_class'] ) ) {
			$kept    = array();
			$dropped = array();
			foreach ( preg_split( '/\s+/', trim( (string) $attrs['css_class'] ) ) as $class ) {
				if ( '' === $class ) {
					continue;
				}
				if ( preg_match( '/^fa[a-z]?(-|$)/', $class ) ) {
					$dropped[] = $class;
				} else {
					$kept[] = $class;
				}
			}
			if ( ! empty( $dropped ) ) {
				$this->note( sprintf( 'Dropped stray icon class(es) "%s" from css_class — icon identity belongs in an icon field.', implode( ' ', $dropped ) ) );
			}
			if ( empty( $kept ) ) {
				unset( $attrs['css_class'] );
			} else {
				$attrs['css_class'] = implode( ' ', $kept );
			}
		}

		return array(
			'name'        => $this->block_name( $base_id ),
			'attributes'  => $attrs,
			'innerBlocks' => array(),
		);
	}

	/**
	 * Pin every styling attribute the block would otherwise style from its own
	 * default. A block renders its schema default for any attribute the block
	 * markup leaves unset, injecting classes the design never asked for (a text
	 * block's mb-3, an icon-box's centered alignment and primary background). For
	 * each styling attribute the source did not set, this pins the neutral enum
	 * value so the block renders nothing for it. Functional attributes (the HTML
	 * tag, link type, image mode) are left alone — they carry no utility class.
	 *
	 * @param array        $attrs Modified in place.
	 * @param ReverseIndex $idx
	 */
	private function neutralize_defaults( array &$attrs, ReverseIndex $idx ): void {
		foreach ( $idx->argument_names() as $name ) {
			if ( isset( $attrs[ $name ] ) || ! self::is_styling_attr( $name ) ) {
				continue;
			}

			$arg = $idx->argument( $name );
			if ( null === $arg ) {
				continue;
			}

			$default = $arg['default'] ?? '';
			if ( '' === $default || null === $default ) {
				continue;
			}

			$type = $arg['type'] ?? '';
			if ( 'checkbox' === $type ) {
				$neutral = '';
			} elseif ( 'select' === $type ) {
				$neutral = $idx->neutral_value( $name );
			} else {
				continue;
			}

			if ( null === $neutral || (string) $default === $neutral ) {
				continue;
			}

			$attrs[ $name ] = $neutral;
		}
	}

	/**
	 * Whether an attribute renders a utility class (and so must be neutralized
	 * when unset), as opposed to a functional attribute like a tag name.
	 *
	 * @param string $name
	 * @return bool
	 */
	private static function is_styling_attr( string $name ): bool {
		if ( null !== ClassMap::prefix_for( $name ) || in_array( $name, ClassMap::literal_attrs(), true ) ) {
			return true;
		}

		// Product styling fields the shared field builders do not cover (icon-box
		// and button typography / colour / sizing).
		if ( preg_match( '/^(icon|title|desc)_(bg|size|color|text_align|font_weight|font_case)(_md|_lg)?$/', $name ) ) {
			return true;
		}

		return in_array( $name, array( 'link_bg', 'link_size', 'link_color', 'iconbox_size', 'icon_type', 'icon_rounded_size', 'badge_bg', 'badge_style' ), true );
	}

	/**
	 * Set the unambiguous prerequisites of an element_require so a class that
	 * implies them is not needlessly dropped. Only a plain AND of equality
	 * conditions is treated as unambiguous; anything with OR, existence or
	 * inequality is left alone (the caller then spills the class instead).
	 *
	 * @param string       $expression
	 * @param array        $attrs Modified in place.
	 * @param ReverseIndex $idx
	 */
	private function try_satisfy( string $expression, array &$attrs, ReverseIndex $idx ): void {
		$parsed = RequireExpression::parse( $expression );
		if ( null === $parsed || empty( $parsed['exact'] ) || 'AND' !== $parsed['operator'] || empty( $parsed['conditions'] ) ) {
			return;
		}

		foreach ( $parsed['conditions'] as $condition ) {
			if ( isset( $condition['operator'] ) || ! empty( $condition['negate'] ) || '==' !== ( $condition['compare'] ?? '' ) ) {
				return; // A nested group, negation, existence or inequality is not unambiguous.
			}
		}

		foreach ( $parsed['conditions'] as $condition ) {
			$name  = $condition['attribute'];
			$value = (string) $condition['value'];

			if ( isset( $attrs[ $name ] ) && '' !== $attrs[ $name ] ) {
				continue;
			}
			if ( $idx->has_attribute( $name ) && ( array() === $idx->enum_values( $name ) || $idx->is_valid_value( $name, $value ) ) ) {
				$attrs[ $name ] = $value;
			}
		}
	}

	// -------------------------------------------------------------------------
	// Class + attribute consumption
	// -------------------------------------------------------------------------

	/**
	 * Consume an element's `class` attribute into block attributes, spilling the
	 * remainder into `css_class`.
	 *
	 * @param \DOMElement  $node
	 * @param ReverseIndex $idx
	 * @param array        $sources Filled with attribute => source class.
	 * @return array
	 */
	private function consume_classes( \DOMElement $node, ReverseIndex $idx, array &$sources ): array {
		return $this->consume_class_list( self::classes_of( $node ), $idx, $sources );
	}

	/**
	 * Consume a list of classes into block attributes, recording which class each
	 * attribute came from so an attribute the block later rejects (unmet
	 * requires) can be spilled back to css_class verbatim.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $sources Filled with attribute => source class.
	 * @return array
	 */
	private function consume_class_list( array $classes, ReverseIndex $idx, array &$sources ): array {
		$attrs = array();
		$spill = array();

		$classes = $this->consume_absolute_position( $classes, $idx, $attrs, $sources );
		$classes = $this->consume_border( $classes, $idx, $attrs, $sources );
		$classes = $this->consume_ratio( $classes, $idx, $attrs, $sources );

		foreach ( $classes as $class ) {
			$this->dark_mode_lint( $class );

			// D1: a spacing shorthand maps to its per-side attributes so the
			// block's spacing controls reflect it, rendering the equivalent
			// longhand the scorer treats as identical.
			$expanded = $this->expand_spacing( $class, $idx );
			if ( null !== $expanded ) {
				foreach ( $expanded as $attr_name => $attr_value ) {
					$attrs[ $attr_name ]   = $attr_value;
					$sources[ $attr_name ] = $class;
				}
				++$this->report['classes_as_attributes'];
				continue;
			}

			// Bootstrap's bare `.small` is the fs-sm font-size utility; map it to the
			// block's font-size attribute wherever that attribute accepts fs-sm.
			$lookup = 'small' === $class ? 'fs-sm' : $class;
			$match  = $idx->match( $lookup );
			if ( null !== $match ) {
				$attrs = array_merge( $attrs, $match );
				foreach ( array_keys( $match ) as $attr_name ) {
					$sources[ $attr_name ] = $class;
				}
				++$this->report['classes_as_attributes'];
				continue;
			}

			$spill[] = $class;
		}

		if ( ! empty( $spill ) ) {
			$existing                              = isset( $attrs['css_class'] ) ? array( $attrs['css_class'] ) : array();
			$attrs['css_class']                    = implode( ' ', array_merge( $existing, $spill ) );
			$this->report['classes_as_css_class'] += count( $spill );
		}

		return $attrs;
	}

	/**
	 * Bootstrap validity repair: flex `gap-*` utilities on a `.row` fight the grid's
	 * padding-gutter system, pushing the columns past 100% so they wrap. On any
	 * element whose class list carries `row`, rewrite the flex-gap family to the
	 * matching gutter family (`gap`→`g`, `row-gap`→`gy`, `column-gap`→`gx`, keeping
	 * any breakpoint infix), dropping the source when the target gutter class is
	 * already present. Recorded under the report's `repairs` key, never as loss.
	 *
	 * @param string[] $classes
	 * @return string[] Rewritten classes.
	 */
	private function repair_row_gap( array $classes ): array {
		if ( ! in_array( 'row', $classes, true ) ) {
			return $classes;
		}

		// Mirror the wp-ai-labs client-side rewrite (normalize.js): Bootstrap's gap
		// scale is 0–5, so only those steps are the utilities that actually exist.
		$map = array(
			'/^gap(-(?:sm|md|lg|xl|xxl))?-([0-5])$/'        => 'g',
			'/^row-gap(-(?:sm|md|lg|xl|xxl))?-([0-5])$/'    => 'gy',
			'/^column-gap(-(?:sm|md|lg|xl|xxl))?-([0-5])$/' => 'gx',
		);

		$out = array();
		foreach ( $classes as $class ) {
			$rewritten = null;
			foreach ( $map as $pattern => $target ) {
				if ( preg_match( $pattern, $class, $m ) ) {
					$rewritten = $target . $m[1] . '-' . $m[2];
					break;
				}
			}

			if ( null === $rewritten ) {
				$out[] = $class;
				continue;
			}

			// Drop the source when the target gutter class is already present —
			// originally on the element or produced by an earlier rewrite this pass.
			$dropped = in_array( $rewritten, $classes, true ) || in_array( $rewritten, $out, true );

			$this->report['repairs'][] = array(
				'from'   => $class,
				'to'     => $dropped ? null : $rewritten,
				'reason' => 'gap-on-row',
			);

			if ( ! $dropped ) {
				$out[] = $rewritten;
			}
		}

		return $out;
	}

	/**
	 * Consume the multi-class combinations that map to `absolute_position`.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $attrs   Modified in place.
	 * @param array        $sources Modified in place.
	 * @return string[] Remaining classes.
	 */
	private function consume_absolute_position( array $classes, ReverseIndex $idx, array &$attrs, array &$sources ): array {
		if ( ! $idx->has_attribute( 'absolute_position' ) ) {
			return $classes;
		}

		$combos = ClassMap::absolute_positions();
		uksort( $combos, static fn( $a, $b ) => substr_count( $b, ' ' ) <=> substr_count( $a, ' ' ) );

		foreach ( $combos as $combo => $value ) {
			$words = explode( ' ', $combo );
			if ( array_diff( $words, $classes ) ) {
				continue;
			}

			$attrs['absolute_position']   = $value;
			$sources['absolute_position'] = $combo;
			++$this->report['classes_as_attributes'];

			return array_values( array_diff( $classes, $words ) );
		}

		return $classes;
	}

	/**
	 * Consume the coloured-border combo `border border-{token}` that build_aui_class
	 * emits from the `border` attribute. The colour class carries the value; the bare
	 * `border` marker beside it is absorbed with it so it does not spill. `border-0`
	 * (the None case) is handled by the exception table, and non-enum borders
	 * (`border-top` on a divider) are left to spill.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $attrs   Modified in place.
	 * @param array        $sources Modified in place.
	 * @return string[] Remaining classes.
	 */
	private function consume_border( array $classes, ReverseIndex $idx, array &$attrs, array &$sources ): array {
		if ( ! $idx->has_attribute( 'border' ) ) {
			return $classes;
		}

		foreach ( $classes as $class ) {
			if ( 'border-0' === $class || ! preg_match( '/^border-(.+)$/', $class, $m ) ) {
				continue;
			}
			if ( ! $idx->is_valid_value( 'border', $m[1] ) ) {
				continue;
			}

			$attrs['border']   = $m[1];
			$sources['border'] = $class;
			++$this->report['classes_as_attributes'];

			// Drop both the coloured class and the bare `border` on-marker.
			return array_values( array_filter( $classes, static fn( $c ) => $c !== $class && 'border' !== $c ) );
		}

		return $classes;
	}

	/**
	 * Consume the aspect-ratio combo `ratio ratio-{v}` that build_aui_class emits
	 * from the `ratio` attribute. The suffix class carries the value; the bare
	 * `ratio` marker beside it is absorbed with it.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @param array        $attrs   Modified in place.
	 * @param array        $sources Modified in place.
	 * @return string[] Remaining classes.
	 */
	private function consume_ratio( array $classes, ReverseIndex $idx, array &$attrs, array &$sources ): array {
		if ( ! $idx->has_attribute( 'ratio' ) ) {
			return $classes;
		}

		foreach ( $classes as $class ) {
			if ( ! preg_match( '/^ratio-(.+)$/', $class, $m ) || ! $idx->is_valid_value( 'ratio', $m[1] ) ) {
				continue;
			}

			$attrs['ratio']   = $m[1];
			$sources['ratio'] = $class;
			++$this->report['classes_as_attributes'];

			// Drop both the suffix class and the bare `ratio` marker.
			return array_values( array_filter( $classes, static fn( $c ) => $c !== $class && 'ratio' !== $c ) );
		}

		return $classes;
	}

	/**
	 * Handle non-class element attributes: inline styles (dropped + reported),
	 * event handlers (ignored), image src/alt, and custom link URLs.
	 *
	 * @param \DOMElement  $node
	 * @param ReverseIndex $idx
	 * @param string       $tag
	 * @param array        $attrs Modified in place.
	 */
	private function consume_element_attributes( \DOMElement $node, ReverseIndex $idx, string $tag, array &$attrs ): void {
		if ( $node->hasAttribute( 'style' ) ) {
			$this->consume_background_style( $node, $idx, $tag, $attrs );
		}

		$image = $idx->image_attrs();
		if ( null !== $image && 'img' === $tag ) {
			$src = trim( $node->getAttribute( 'src' ) );
			if ( '' !== $src && ! HtmlSanitizer::is_dangerous_url( $src ) ) {
				$attrs[ $image['src'] ] = $src;
				if ( null !== $image['mode'] && in_array( 'url', $idx->enum_values( $image['mode'] ), true ) ) {
					$attrs[ $image['mode'] ] = 'url';
				}
			}
			$alt = trim( $node->getAttribute( 'alt' ) );
			if ( '' !== $alt && null !== $image['alt'] ) {
				$attrs[ $image['alt'] ] = $alt;
			}
		}

		$link = $idx->link_attrs();
		if ( null !== $link && $node->hasAttribute( 'href' ) ) {
			$href = trim( $node->getAttribute( 'href' ) );
			// A placeholder "#" is kept as a custom link so the element renders as
			// the anchor the source wrote, rather than falling back to the block's
			// "home" default (which would link it to the site homepage).
			if ( '' !== $href && ! HtmlSanitizer::is_dangerous_url( $href ) ) {
				$attrs[ $link['url'] ] = $href;
				if ( null !== $link['type'] && in_array( 'custom', $idx->enum_values( $link['type'] ), true ) ) {
					$attrs[ $link['type'] ] = 'custom';
				}
			}
		}
	}

	/**
	 * Consume the background declarations from a `style=""` attribute into the
	 * block's bg attribute family, dropping + reporting only the residue. The
	 * blocks already render exactly these declarations (Utils::get_bg_styles), so
	 * this is the inbound half of that round-trip:
	 *   - `background-image: <gradient>` / `background: <gradient>` → bg custom-gradient + bg_gradient
	 *   - `background-color: <hex|rgb|hsl>` → bg custom-color + bg_color
	 *   - `background-image: url(…)` → bg_image (+ `background-attachment: fixed` → bg_image_fixed)
	 *   - `background-clip: text` / `-webkit-background-clip: text` → bg_on_text; its transparent
	 *     text-fill companions are consumed silently.
	 *   - `color: <hex|rgb|hsl>` (non-transparent) → text_color custom + text_color_custom.
	 * Each maps only when the block declares the target attribute; unsafe values
	 * (expression(), javascript:, braces) are rejected and left in the drop report.
	 *
	 * @param \DOMElement  $node
	 * @param ReverseIndex $idx
	 * @param string       $tag
	 * @param array        $attrs Modified in place.
	 */
	private function consume_background_style( \DOMElement $node, ReverseIndex $idx, string $tag, array &$attrs ): void {
		$declarations = array();
		foreach ( explode( ';', (string) $node->getAttribute( 'style' ) ) as $declaration ) {
			$declaration = trim( $declaration );
			if ( '' === $declaration || false === strpos( $declaration, ':' ) ) {
				continue;
			}
			list( $property, $value ) = explode( ':', $declaration, 2 );

			$declarations[] = array(
				'prop'  => strtolower( trim( $property ) ),
				'value' => trim( $value ),
				'raw'   => $declaration,
			);
		}

		// A background-clip:text present anywhere marks the gradient-text combo, so
		// its transparent text-fill companions read as combo pieces, not loss.
		$combo = false;
		foreach ( $declarations as $declaration ) {
			if ( in_array( $declaration['prop'], array( 'background-clip', '-webkit-background-clip' ), true )
				&& 'text' === $declaration['value'] ) {
				$combo = true;
			}
		}

		$residue = array();
		foreach ( $declarations as $declaration ) {
			if ( ! $this->consume_background_declaration( $declaration, $idx, $attrs, $combo ) ) {
				$residue[] = $declaration['raw'];
			}
		}

		if ( ! empty( $residue ) ) {
			$this->report['inline_styles_dropped'][] = array(
				'tag'   => $tag,
				'style' => implode( '; ', $residue ),
			);
		}
	}

	/**
	 * Consume one background declaration into $attrs. Returns true when the
	 * declaration was consumed (mapped or a silently-absorbed combo companion),
	 * false when it is residue to drop + report.
	 *
	 * @param array        $declaration { prop, value, raw }
	 * @param ReverseIndex $idx
	 * @param array        $attrs       Modified in place.
	 * @param bool         $combo       Whether the gradient-text combo is active.
	 * @return bool
	 */
	private function consume_background_declaration( array $declaration, ReverseIndex $idx, array &$attrs, bool $combo ): bool {
		$prop  = $declaration['prop'];
		$value = $declaration['value'];

		if ( 'background-image' === $prop || 'background' === $prop ) {
			if ( self::is_gradient_value( $value ) ) {
				if ( ! $idx->has_attribute( 'bg_gradient' ) || ! self::is_safe_style_value( $value ) ) {
					return false;
				}
				$attrs['bg']          = 'custom-gradient';
				$attrs['bg_gradient'] = $value;
				return true;
			}
			if ( preg_match( '#^url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)$#i', $value, $m ) ) {
				$url = trim( $m[1] );
				if ( ! $idx->has_attribute( 'bg_image' ) || '' === $url || HtmlSanitizer::is_dangerous_url( $url ) ) {
					return false;
				}
				$attrs['bg_image'] = $url;
				return true;
			}
			return false;
		}

		if ( 'background-color' === $prop ) {
			if ( ! $idx->has_attribute( 'bg_color' ) || ! self::is_color_value( $value ) || ! self::is_safe_style_value( $value ) ) {
				return false;
			}
			// A gradient set on the same element wins the bg marker; the colour still
			// records for the gradient/colour blend the renderer supports.
			if ( 'custom-gradient' !== ( $attrs['bg'] ?? '' ) ) {
				$attrs['bg'] = 'custom-color';
			}
			$attrs['bg_color'] = $value;
			return true;
		}

		if ( in_array( $prop, array( 'background-clip', '-webkit-background-clip' ), true ) && 'text' === $value ) {
			if ( ! $idx->has_attribute( 'bg_on_text' ) ) {
				return false;
			}
			$attrs['bg_on_text'] = '1';
			return true;
		}

		// Transparent text-fill companions of the gradient-text combo — absorbed
		// silently only when the combo is present, never reported as loss.
		if ( $combo && 'transparent' === $value
			&& in_array( $prop, array( '-webkit-text-fill-color', 'text-fill-color', 'color' ), true ) ) {
			return true;
		}

		// A non-transparent custom text colour maps to the text_color/text_color_custom
		// pair on blocks that carry it. Transparent `color:` values are absorbed above as
		// gradient-text combo companions, so only real colours reach here.
		if ( 'color' === $prop ) {
			if ( ! $idx->has_attribute( 'text_color_custom' ) || ! self::is_color_value( $value ) || ! self::is_safe_style_value( $value ) ) {
				return false;
			}
			$attrs['text_color']        = 'custom';
			$attrs['text_color_custom'] = $value;
			return true;
		}

		if ( 'background-attachment' === $prop && 'fixed' === $value ) {
			if ( ! $idx->has_attribute( 'bg_image_fixed' ) || empty( $attrs['bg_image'] ) ) {
				return false;
			}
			$attrs['bg_image_fixed'] = '1';
			return true;
		}

		return false;
	}

	/**
	 * Whether a CSS value is a single CSS gradient function.
	 *
	 * @param string $value
	 * @return bool
	 */
	private static function is_gradient_value( string $value ): bool {
		return (bool) preg_match( '/^(repeating-)?(linear|radial|conic)-gradient\s*\(.*\)$/is', trim( $value ) );
	}

	/**
	 * Whether a CSS value is a hex / rgb() / hsl() colour.
	 *
	 * @param string $value
	 * @return bool
	 */
	private static function is_color_value( string $value ): bool {
		return (bool) preg_match( '/^(#[0-9a-f]{3,8}|rgba?\([^)]*\)|hsla?\([^)]*\))$/i', trim( $value ) );
	}

	/**
	 * Whether a style value is safe to carry into an attribute: a single CSS value
	 * with no script-injection vectors or declaration/rule punctuation.
	 *
	 * @param string $value
	 * @return bool
	 */
	private static function is_safe_style_value( string $value ): bool {
		if ( '' === trim( $value ) ) {
			return false;
		}
		if ( preg_match( '/(expression\(|javascript:|[;{}<>])/i', $value ) ) {
			return false;
		}
		return true;
	}

	// -------------------------------------------------------------------------
	// Role + nesting
	// -------------------------------------------------------------------------

	/**
	 * Infer a container's structural role from its classes.
	 *
	 * @param string[]     $classes
	 * @param ReverseIndex $idx
	 * @return array{0:string,1:?string,2:array} [role token, container value, consumed classes]
	 */
	private function infer_role( array $classes, ReverseIndex $idx ): array {
		$enum = $idx->enum_values( 'container' );

		// Longest (most specific) enum value first, e.g. list-group variants.
		usort( $enum, static fn( $a, $b ) => substr_count( $b, ' ' ) <=> substr_count( $a, ' ' ) );

		foreach ( $enum as $value ) {
			if ( '' === $value ) {
				continue;
			}
			$words = explode( ' ', $value );
			if ( ! array_diff( $words, $classes ) ) {
				return array( $words[0], $value, $words );
			}
		}

		// Any column class — bare `col` (matched above) or a breakpoint-only
		// variant (col-md-6, col-sm-4) — is a column: pin the role so the block
		// knows it is one, even when the markup omits the bare class. The variant
		// classes still render their own widths (mapped or spilled).
		foreach ( $classes as $class ) {
			if ( 0 === strpos( $class, 'col-' ) ) {
				return array( 'col', 'col', array() );
			}
		}

		return array( '', null, array() );
	}

	/**
	 * Synthesize missing structural wrappers instead of dropping content: an
	 * orphan `col` gains a `row` parent, orphan `card-*` parts gain a `card`.
	 *
	 * @param array  $blocks
	 * @param string $parent_role
	 * @return array
	 */
	private function enforce_nesting( array $blocks, string $parent_role ): array {
		$card_parts = array( 'card-body', 'card-header', 'card-footer', 'card-img-top' );
		$result     = array();
		$count      = count( $blocks );
		$i          = 0;

		while ( $i < $count ) {
			$role = $blocks[ $i ]['__role'] ?? '';

			if ( 'col' === $role && 'row' !== $parent_role ) {
				$run = array();
				while ( $i < $count && 'col' === ( $blocks[ $i ]['__role'] ?? '' ) ) {
					$run[] = $blocks[ $i ];
					++$i;
				}
				$result[] = $this->synthesize_wrapper( $run, 'row' );
				$this->note( 'Wrapped orphan column(s) in a synthesized row.' );
				continue;
			}

			if ( in_array( $role, $card_parts, true ) && 'card' !== $parent_role ) {
				$run = array();
				while ( $i < $count && in_array( $blocks[ $i ]['__role'] ?? '', $card_parts, true ) ) {
					$run[] = $blocks[ $i ];
					++$i;
				}
				$result[] = $this->synthesize_wrapper( $run, 'card' );
				$this->note( 'Wrapped orphan card part(s) in a synthesized card.' );
				continue;
			}

			$result[] = $blocks[ $i ];
			++$i;
		}

		return $result;
	}

	/**
	 * Build a synthesized container wrapper of the given role around children,
	 * reusing the children's own block name (same product/block).
	 *
	 * @param array  $children
	 * @param string $role
	 * @return array
	 */
	private function synthesize_wrapper( array $children, string $role ): array {
		return array(
			'name'        => $children[0]['name'],
			'attributes'  => array( 'container' => $role ),
			'innerBlocks' => $children,
			'__role'      => $role,
		);
	}

	// -------------------------------------------------------------------------
	// Content helpers
	// -------------------------------------------------------------------------

	/**
	 * Serialize an element's children back to HTML (for HTML-capable text attrs).
	 *
	 * @param \DOMElement $node
	 * @return string
	 */
	private function inner_html( \DOMElement $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}

		return trim( $html );
	}

	/**
	 * Flatten an element to plain text for a plain-text attribute (a title): inline
	 * markup is stripped and a <br> becomes a space, so a two-line caption
	 * ("Average On-Site Time:<br>15 to 30 Minutes") reads as one clean string and
	 * the <br> is never left to drop.
	 *
	 * @param \DOMElement $node
	 * @return string
	 */
	private function flatten_text( \DOMElement $node ): string {
		$html = $this->inner_html( $node );
		$html = preg_replace( '/<br\s*\/?>/i', ' ', $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );

		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Split a caption that leads with a bold run into a title and description:
	 * "<strong>Systemic Efficiency:</strong> Streamline foundational…" →
	 * title "Systemic Efficiency", description "Streamline foundational…". The bold
	 * must LEAD the copy (a mid-text bold is emphasis, not a title), and there must
	 * be remaining copy after it. Returns null when the pattern does not apply.
	 *
	 * @param \DOMElement $node
	 * @return array{title:string,description:string}|null
	 */
	private function split_bold_lead( \DOMElement $node ): ?array {
		$bold = null;
		foreach ( $node->getElementsByTagName( '*' ) as $element ) {
			$etag = strtolower( $element->tagName );
			if ( 'strong' === $etag || 'b' === $etag ) {
				$bold = $element;
				break;
			}
		}
		if ( null === $bold ) {
			return null;
		}

		$lead = trim( preg_replace( '/\s+/', ' ', $bold->textContent ) );
		$full = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );
		if ( '' === $lead || 0 !== strpos( $full, $lead ) ) {
			return null;
		}

		$rest = trim( (string) substr( $full, strlen( $lead ) ) );
		if ( '' === $rest ) {
			return null;
		}

		$title = trim( rtrim( $lead, " \t:–—-" ) );
		if ( '' === $title ) {
			return null;
		}

		return array(
			'title'       => $title,
			'description' => $rest,
		);
	}

	/**
	 * Remove the first Font Awesome `<i>` descendant and return its class string,
	 * so a button/heading's inline icon becomes an attribute rather than markup.
	 *
	 * @param \DOMElement $node
	 * @return string
	 */
	private function pull_inline_icon( \DOMElement $node ): string {
		$icons = $node->getElementsByTagName( 'i' );
		if ( 0 === $icons->length ) {
			return '';
		}

		$icon  = $icons->item( 0 );
		$class = trim( $icon->getAttribute( 'class' ) );
		if ( '' === $class || false === strpos( $class, 'fa' ) ) {
			return '';
		}

		$icon->parentNode->removeChild( $icon );

		return $class;
	}

	// -------------------------------------------------------------------------
	// Report
	// -------------------------------------------------------------------------

	/**
	 * @return array
	 */
	private static function empty_report(): array {
		return array(
			'elements_total'        => 0,
			'elements_converted'    => 0,
			'dropped_elements'      => array(),
			'classes_as_attributes' => 0,
			'classes_as_css_class'  => 0,
			'classes_dropped'       => array(),
			'inline_styles_dropped' => array(),
			'repairs'               => array(),
			'notes'                 => array(),
		);
	}

	/**
	 * Record a dropped element.
	 *
	 * @param string      $tag
	 * @param string      $reason
	 * @param \DOMElement $node
	 */
	private function drop( string $tag, string $reason, \DOMElement $node ): void {
		$this->report['dropped_elements'][] = array(
			'tag'     => $tag,
			'reason'  => $reason,
			'excerpt' => self::excerpt( $node ),
		);
	}

	/**
	 * Append a free-text note.
	 *
	 * @param string $message
	 */
	private function note( string $message ): void {
		$this->report['notes'][] = $message;
	}

	/**
	 * Flag dark-mode-hostile classes (report only, never rewritten).
	 *
	 * @param string $class
	 */
	private function dark_mode_lint( string $class ): void {
		if ( in_array( $class, array( 'bg-white', 'text-dark', 'bg-light' ), true ) ) {
			$this->note( sprintf( 'Class "%s" does not adapt to dark mode; prefer a semantic token (bg-body, *-subtle, *-emphasis).', $class ) );
		}
	}

	// -------------------------------------------------------------------------
	// Static utilities
	// -------------------------------------------------------------------------

	/**
	 * Expand a Bootstrap margin/padding shorthand into its per-side attributes
	 * (`p-4` → pt/pr/pb/pl, `px-4` → pl/pr, `py-5` → pt/pb, plus the `-md-`
	 * breakpoint). Single-side classes (already longhand) and the `-lg-` tier
	 * (which build_aui_class collapses to the wrong breakpoint) are left for
	 * verbatim handling. Returns only the sides the block accepts, or null.
	 *
	 * @param string       $class
	 * @param ReverseIndex $idx
	 * @return array<string,string>|null
	 */
	private function expand_spacing( string $class, ReverseIndex $idx ): ?array {
		if ( ! preg_match( '/^([mp])([xy]?)(?:-(md|lg))?-(auto|n?\d+)$/', $class, $m ) ) {
			return null;
		}

		list( , $type, $axis, $breakpoint, $value ) = $m;

		$suffix = '' !== $breakpoint ? '_' . $breakpoint : '';
		$sides  = array(
			''  => array( 't', 'b', 'l', 'r' ),
			'x' => array( 'l', 'r' ),
			'y' => array( 't', 'b' ),
		)[ $axis ];

		$out = array();
		foreach ( $sides as $side ) {
			$name = $type . $side . $suffix;
			if ( $idx->has_attribute( $name ) && $idx->is_valid_value( $name, $value ) ) {
				$out[ $name ] = $value;
			}
		}

		return empty( $out ) ? null : $out;
	}

	/**
	 * The class tokens of an element.
	 *
	 * @param \DOMElement $node
	 * @return string[]
	 */
	private static function classes_of( \DOMElement $node ): array {
		$class = trim( $node->getAttribute( 'class' ) );
		if ( '' === $class ) {
			return array();
		}

		return array_values( array_unique( array_filter( preg_split( '/\s+/', $class ) ) ) );
	}

	/**
	 * An element's classes plus those of all its (remaining) element descendants,
	 * de-duplicated. Used to gather an icon-item's own and its inner text
	 * element's classes into one consumable list.
	 *
	 * @param \DOMElement $node
	 * @return string[]
	 */
	private static function descendant_classes( \DOMElement $node ): array {
		$classes = self::classes_of( $node );

		foreach ( $node->getElementsByTagName( '*' ) as $descendant ) {
			$classes = array_merge( $classes, self::classes_of( $descendant ) );
		}

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Whether an element has at least one element child.
	 *
	 * @param \DOMElement $node
	 * @return bool
	 */
	private static function has_element_children( \DOMElement $node ): bool {
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof \DOMElement ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a node contains any block-level element descendant (a heading,
	 * paragraph, div, list or sectioning element). Inline markup (<br>, <strong>,
	 * <span>, <a>) does not count — a caption carrying only inline content is a
	 * label, not a wrapper grouping separate copy blocks.
	 *
	 * @param \DOMElement $node
	 * @return bool
	 */
	private static function has_block_children( \DOMElement $node ): bool {
		// <a>/<button> are deliberately excluded: an interactive element inside a
		// caption line makes it a boundary, not foldable inline copy (round 9).
		$inline = array( 'i', 'span', 'small', 'strong', 'em', 'b', 'u', 'br', 'svg', 'path', 'mark', 'sub', 'sup' );
		foreach ( $node->getElementsByTagName( '*' ) as $descendant ) {
			if ( ! in_array( strtolower( $descendant->tagName ), $inline, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether $node is a descendant of $ancestor.
	 *
	 * @param \DOMNode      $ancestor
	 * @param \DOMNode|null $node
	 * @return bool
	 */
	private static function contains_node( \DOMNode $ancestor, ?\DOMNode $node ): bool {
		if ( null === $node ) {
			return false;
		}
		for ( $parent = $node->parentNode; null !== $parent; $parent = $parent->parentNode ) {
			if ( $parent === $ancestor ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a class is a chip's internal flex/centering utility — layout the
	 * icon-box reproduces itself, so it must not travel to the icon-box wrapper.
	 *
	 * @param string $class
	 * @return bool
	 */
	private static function is_chip_layout_class( string $class ): bool {
		// Display, alignment and direction reflow the icon-box if they reach its
		// wrapper. flex-shrink/flex-grow do not change the internal layout, so they
		// are left to spill and survive.
		return (bool) preg_match( '/^(d-(inline-)?flex|align-items-|align-self-|justify-content-|flex-(column|row|wrap)|gap-)/', $class );
	}

	/**
	 * The direct element children of a node (skipping text and comment nodes).
	 *
	 * @param \DOMElement $node
	 * @return \DOMElement[]
	 */
	private static function element_children( \DOMElement $node ): array {
		$elements = array();
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof \DOMElement ) {
				$elements[] = $child;
			}
		}

		return $elements;
	}

	/**
	 * A short excerpt of an element's opening markup for the report.
	 *
	 * @param \DOMElement $node
	 * @return string
	 */
	private static function excerpt( \DOMElement $node ): string {
		$html = (string) $node->ownerDocument->saveHTML( $node );
		$html = preg_replace( '/\s+/', ' ', $html );

		return mb_substr( trim( $html ), 0, 60 );
	}

	/**
	 * Recursively remove the internal `__role` key from a block tree.
	 *
	 * @param array $blocks
	 * @return array
	 */
	private static function strip_internal( array $blocks ): array {
		foreach ( $blocks as &$block ) {
			unset( $block['__role'] );
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::strip_internal( $block['innerBlocks'] );
			}
		}
		unset( $block );

		return $blocks;
	}
}
