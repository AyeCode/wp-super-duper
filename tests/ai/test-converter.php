<?php
/**
 * Converter — HTML fragment → native block tree.
 *
 * Runs against the live BlockStrap blocks (their ai-html claims must be present)
 * so it exercises the real claim table, reverse index and nesting rules.
 */

use AyeCode\SuperDuper\Ai\Converter;

/** Find the first block of a given block_name anywhere in a tree. */
function sd_ai_find_block( array $blocks, string $name ): ?array {
	foreach ( $blocks as $block ) {
		if ( ( $block['name'] ?? '' ) === $name ) {
			return $block;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = sd_ai_find_block( $block['innerBlocks'], $name );
			if ( null !== $found ) {
				return $found;
			}
		}
	}

	return null;
}

SD_Ai_Test::group( 'converter/reference-fixture' );

$html   = '<section class="py-5 bg-body-tertiary"><div class="container"><div class="row"><div class="col-md-6"><h2 class="fw-bold">Hi</h2><p class="text-body-secondary">Copy.</p><a class="btn btn-primary" href="https://example.com">Go</a></div></div></div></section>';
$result = Converter::convert( $html );
$blocks = $result['blocks'];
$report = $result['report'];

SD_Ai_Test::same( 'nothing dropped', array(), $report['dropped_elements'] );

$section = $blocks[0] ?? array();
SD_Ai_Test::same( 'section is a container block', 'blockstrap/blockstrap-widget-container', $section['name'] ?? null );
// Spacing shorthands expand to the block's per-side attributes (D1), rendering
// the longhand the scorer treats as equivalent.
SD_Ai_Test::same( 'py-5 expands to pt', '5', $section['attributes']['pt'] ?? null );
SD_Ai_Test::same( 'py-5 expands to pb', '5', $section['attributes']['pb'] ?? null );
SD_Ai_Test::same( 'bg token mapped', 'body-tertiary', $section['attributes']['bg'] ?? null );
SD_Ai_Test::same( 'html_tag preserved', 'section', $section['attributes']['html_tag'] ?? null );

$heading = sd_ai_find_block( $blocks, 'blockstrap/blockstrap-widget-heading' );
SD_Ai_Test::same( 'heading text', 'Hi', $heading['attributes']['text'] ?? null );
SD_Ai_Test::same( 'heading fw-bold', 'fw-bold', $heading['attributes']['font_weight'] ?? null );

$text = sd_ai_find_block( $blocks, 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::same( 'text color', 'body-secondary', $text['attributes']['text_color'] ?? null );

$button = sd_ai_find_block( $blocks, 'blockstrap/blockstrap-widget-button' );
SD_Ai_Test::same( 'button text', 'Go', $button['attributes']['text'] ?? null );
SD_Ai_Test::same( 'button href → custom_url', 'https://example.com', $button['attributes']['custom_url'] ?? null );

$col = sd_ai_find_block( $blocks, 'blockstrap/blockstrap-widget-container' );
// The row/col chain resolves container roles.
$row = null;
$walk = function ( $bs ) use ( &$walk, &$row ) {
	foreach ( $bs as $b ) {
		if ( ( $b['attributes']['container'] ?? '' ) === 'row' ) {
			$row = $b;
		}
		if ( ! empty( $b['innerBlocks'] ) ) {
			$walk( $b['innerBlocks'] );
		}
	}
};
$walk( $blocks );
SD_Ai_Test::ok( 'a row container exists', null !== $row );
SD_Ai_Test::same( 'col role + col_md sizing', 'col', $row['innerBlocks'][0]['attributes']['container'] ?? null );
SD_Ai_Test::same( 'col_md from col-md-6', '6', $row['innerBlocks'][0]['attributes']['col_md'] ?? null );

SD_Ai_Test::group( 'converter/inline-markup' );

$r = Converter::convert( '<p>Some <strong>bold</strong> text</p>' );
$p = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::ok( 'inline HTML preserved in text attr', false !== strpos( (string) ( $p['attributes']['text'] ?? '' ), '<strong>bold</strong>' ) );

SD_Ai_Test::group( 'converter/spacing-shorthand' );

// Spacing shorthands expand to per-side attributes (D1): px-4 → pl/pr,
// my-3 → mt/mb, and single-side pt-5 maps directly.
$r = Converter::convert( '<div class="px-4 my-3 pt-5">x</div>' );
$d = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'px-4 → pl', '4', $d['pl'] ?? null );
SD_Ai_Test::same( 'px-4 → pr', '4', $d['pr'] ?? null );
SD_Ai_Test::same( 'my-3 → mt', '3', $d['mt'] ?? null );
SD_Ai_Test::same( 'my-3 → mb', '3', $d['mb'] ?? null );
SD_Ai_Test::same( 'pt-5 maps to attribute', '5', $d['pt'] ?? null );

SD_Ai_Test::group( 'converter/requires-spill' );

// rounded-4 renders regardless of border (build_aui_class emits the radius class
// outside the border block); the border condition is only an editor-form
// visibility rule. On a border-0 element the radius stays a real attribute — not
// spilled to css_class — because keeping it renders the same class (round 10).
$r   = Converter::convert( '<div class="card border-0 rounded-4">x</div>' );
$box = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-container' )['attributes'] ?? array();
SD_Ai_Test::same( 'rounded-4 → rounded_size attribute', '4', $box['rounded_size'] ?? null );
SD_Ai_Test::ok( 'rounded-4 not left in css_class', false === strpos( (string) ( $box['css_class'] ?? '' ), 'rounded-4' ) );
SD_Ai_Test::ok( 'requires-spill drops nothing', empty( $r['report']['dropped_elements'] ) );

SD_Ai_Test::group( 'converter/inline-text-fallback' );

// A plain inline element (no badge/known claim) with text falls back to a text block.
$r = Converter::convert( '<small class="fw-light">Fine print</small>' );
$t = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::ok( 'inline element became a text block', null !== $t );
SD_Ai_Test::ok( 'inline text preserved', false !== strpos( (string) ( $t['attributes']['text'] ?? '' ), 'Fine print' ) );

SD_Ai_Test::group( 'converter/badge' );

// span.badge decomposes into bs_badge (round 5), not a text block.
$r = Converter::convert( '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill small">New</span>' );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-badge' );
SD_Ai_Test::ok( 'span.badge became a bs_badge', null !== $b );
$ba = $b['attributes'] ?? array();
SD_Ai_Test::same( 'badge text', 'New', $ba['text'] ?? null );
SD_Ai_Test::same( 'bg-*-subtle → badge_bg', 'primary-subtle', $ba['badge_bg'] ?? null );
SD_Ai_Test::same( 'text-*-emphasis → text_color', 'primary-emphasis', $ba['text_color'] ?? null );
SD_Ai_Test::same( 'rounded-pill → badge_style', 'pill', $ba['badge_style'] ?? null );
SD_Ai_Test::same( 'small → font_size fs-sm', 'fs-sm', $ba['font_size'] ?? null );
SD_Ai_Test::ok( 'bare badge class dropped', false === strpos( (string) ( $ba['css_class'] ?? '' ), 'badge' ) );

SD_Ai_Test::group( 'converter/icon-chip' );

// An icon-only coloured/rounded chip becomes a single icon-box with fill fields.
$r = Converter::convert( '<div class="d-inline-flex p-3 bg-primary-subtle text-primary-emphasis rounded-3"><i class="fa-solid fa-star fa-lg"></i></div>' );
$c = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::ok( 'icon chip became an icon-box', null !== $c );
$ca = $c['attributes'] ?? array();
SD_Ai_Test::same( 'chip bg → icon_bg', 'primary-subtle', $ca['icon_bg'] ?? null );
SD_Ai_Test::same( 'chip rounded-3 → icon_rounded_size', '3', $ca['icon_rounded_size'] ?? null );
SD_Ai_Test::same( 'chip fill type', 'iconbox-fill', $ca['icon_type'] ?? null );
SD_Ai_Test::same( 'icon identity kept', 'fa-solid fa-star fa-lg', $ca['icon_class'] ?? null );

SD_Ai_Test::group( 'converter/media-object' );

/** Count icon-box / heading / text descendants of a block tree. */
function sd_ai_count_blocks( array $blocks, string $name ): int {
	$n = 0;
	foreach ( $blocks as $b ) {
		if ( ( $b['name'] ?? '' ) === $name ) {
			++$n;
		}
		if ( ! empty( $b['innerBlocks'] ) ) {
			$n += sd_ai_count_blocks( $b['innerBlocks'], $name );
		}
	}
	return $n;
}

// Siblings shape (03): [chip][h3][p] fold into ONE icon-box; no stray heading/text.
$r = Converter::convert( '<div class="card-body d-flex flex-column"><div class="d-inline-flex bg-primary-subtle text-primary-emphasis rounded-3 p-3"><i class="fa-solid fa-shield-virus fa-xl"></i></div><h3 class="h5 fw-bold">Malware Scanning</h3><p class="text-body-secondary">Continuous analysis of threats.</p></div>' );
$c = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::ok( 'media object folds to one icon-box', null !== $c );
SD_Ai_Test::same( 'only one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
SD_Ai_Test::same( 'no stray heading block', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-heading' ) );
SD_Ai_Test::same( 'no stray text block', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-text' ) );
$ma = $c['attributes'] ?? array();
SD_Ai_Test::same( 'heading → title', 'Malware Scanning', $ma['title'] ?? null );
SD_Ai_Test::same( 'heading tag → title_tag', 'h3', $ma['title_tag'] ?? null );
SD_Ai_Test::same( 'paragraph → description', 'Continuous analysis of threats.', $ma['description'] ?? null );
SD_Ai_Test::same( 'column stack → top layout (icon_position unset)', null, $ma['icon_position'] ?? null );

// Wrapper shape (06) in a flex row: [chip][div > h3 + p] → left layout.
$r = Converter::convert( '<div class="card-body d-flex align-items-center"><div class="d-inline-flex bg-primary-subtle rounded-3 p-3"><i class="fa-solid fa-certificate fa-lg"></i></div><div><h3 class="h6">Licensed</h3><p class="small">Certified pros.</p></div></div>' );
$c = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::same( 'wrapper media object → one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
SD_Ai_Test::same( 'wrapper collapses (no stray container heading)', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-heading' ) );
SD_Ai_Test::same( 'flex row → icon_position left', 'left', $c['attributes']['icon_position'] ?? null );
SD_Ai_Test::same( 'wrapper heading → title', 'Licensed', $c['attributes']['title'] ?? null );
SD_Ai_Test::same( 'wrapper paragraph → description', 'Certified pros.', $c['attributes']['description'] ?? null );

// A feature card with trailing content (round 8): the chip + heading + paragraph
// caption folds into ONE titled icon-box, and the trailing CTA is preserved as a
// sibling — nothing is stranded, nothing is swallowed.
$r = Converter::convert( '<div class="card-body d-flex flex-column"><div class="bg-primary-subtle rounded-3 p-3"><i class="fa-solid fa-star"></i></div><h3>Feature</h3><p>Copy.</p><a class="btn btn-primary" href="#">Learn more</a></div>' );
$c = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::same( 'caption folds to one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
SD_Ai_Test::same( 'folded icon-box carries the title', 'Feature', $c['attributes']['title'] ?? null );
SD_Ai_Test::same( 'folded icon-box carries the description', 'Copy.', $c['attributes']['description'] ?? null );
SD_Ai_Test::ok( 'trailing CTA preserved as a sibling', null !== sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-button' ) );
SD_Ai_Test::same( 'no stray heading beside the box', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-heading' ) );

SD_Ai_Test::group( 'converter/media-object-inline-label' );

// Round 8: the dominant soak-failure shape — a chip beside an inline <span>/<small>
// label. The label is the title; its classes route through the title_* family.
$r = Converter::convert( '<div class="d-flex align-items-center gap-2"><div class="d-inline-flex bg-primary-subtle text-primary-emphasis rounded-circle p-2"><i class="fa-solid fa-shield-halved"></i></div><span class="small fw-semibold text-body-emphasis">Licensed &amp; Insured</span></div>' );
$ib = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
$a  = $ib['attributes'] ?? array();
SD_Ai_Test::same( 'chip + inline label folds to one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
SD_Ai_Test::same( 'label → title (entity decoded)', 'Licensed & Insured', $a['title'] ?? null );
SD_Ai_Test::same( 'label small → title_size fs-sm', 'fs-sm', $a['title_size'] ?? null );
SD_Ai_Test::same( 'label fw-semibold → title_font_weight', 'fw-semibold', $a['title_font_weight'] ?? null );
SD_Ai_Test::same( 'label text-body-emphasis → title_color', 'body-emphasis', $a['title_color'] ?? null );
SD_Ai_Test::same( 'no stray text block for the label', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-text' ) );

// A bare Font Awesome icon (no chip wrapper) beside an inline label also folds.
$r = Converter::convert( '<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-truck-fast text-primary fs-5"></i><span class="small">Rapid Dispatch</span></div>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'bare icon + label → icon_class', 'fa-solid fa-truck-fast', $a['icon_class'] ?? null );
SD_Ai_Test::same( 'bare icon fs-5 → icon_size', 'fs-5', $a['icon_size'] ?? null );
SD_Ai_Test::same( 'bare icon + label → title', 'Rapid Dispatch', $a['title'] ?? null );

// A label carrying inline markup and a <br> folds into a single clean title, and
// the <br> is never dropped (round 8, item 2).
$r = Converter::convert( '<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-clock text-primary"></i><span>Average On-Site Time:<br><strong>15 to 30 Minutes</strong></span></div>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'label with <br> → one flattened title', 'Average On-Site Time: 15 to 30 Minutes', $a['title'] ?? null );
SD_Ai_Test::same( '<br> not dropped', array(), $r['report']['dropped_elements'] );

// Caption rows built from inline-only <div>s fold as title + description.
$r = Converter::convert( '<div class="d-inline-flex align-items-center gap-2"><div class="bg-primary-subtle rounded-3 p-3"><i class="fa-solid fa-stopwatch"></i></div><div><div class="fw-bold">30-45 Min</div><div class="small text-body-secondary">Avg Response</div></div></div>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'div caption line → title', '30-45 Min', $a['title'] ?? null );
SD_Ai_Test::same( 'second div caption line → description', 'Avg Response', $a['description'] ?? null );

// A feature card with a trailing feature list: the caption folds, the list stays.
$r = Converter::convert( '<div class="card-body d-flex flex-column"><div class="bg-primary-subtle rounded-3 p-3"><i class="fa-solid fa-fire"></i></div><h3 class="h4">Group Classes</h3><p class="text-body-secondary">HIIT and cycling.</p><ul class="list-unstyled"><li>Heart rate tracking</li></ul></div>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'card caption → one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
SD_Ai_Test::same( 'card caption folds heading → title', 'Group Classes', $a['title'] ?? null );
SD_Ai_Test::ok( 'trailing list survives in the render', false !== strpos( \AyeCode\SuperDuper\Ai\Renderer::render( $r['blocks'] ), 'Heart rate tracking' ) );

SD_Ai_Test::group( 'converter/status-pill' );

// A status pill whose icon is not the first child (a spinner precedes it) is an
// inline icon+text item → one icon-box, no stranded text sibling.
$r = Converter::convert( '<div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-danger-subtle text-danger-emphasis small fw-bold"><span class="spinner-grow spinner-grow-sm"></span><i class="fa-solid fa-bolt"></i> 24/7 Emergency Dispatch Active</div>' );
SD_Ai_Test::same( 'status pill → one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'pill icon identity', 'fa-solid fa-bolt', $a['icon_class'] ?? null );
SD_Ai_Test::ok( 'pill text becomes the title', false !== strpos( (string) ( $a['title'] ?? '' ), '24/7 Emergency Dispatch Active' ) );
SD_Ai_Test::same( 'no stray text block beside the pill box', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-text' ) );

SD_Ai_Test::group( 'converter/border-combo' );

// Round 8: `border border-{token}` maps to the container's border attribute; the
// bare `border` marker is absorbed, not spilled.
$r = Converter::convert( '<div class="p-3 border border-danger-subtle">x</div>' );
$c = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'border border-danger-subtle → border attr', 'danger-subtle', $c['border'] ?? null );
SD_Ai_Test::ok( 'bare border marker not spilled', false === strpos( (string) ( $c['css_class'] ?? '' ), 'border' ) );

SD_Ai_Test::group( 'converter/nested-custom-elements' );

// R11: a container widget (a custom element whose block declares inner_blocks !==
// "none") recurses — its listed child custom elements become inner blocks.
$r = Converter::convert( '<bs-navbar class="navbar-dark"><bs-navbar-brand text="Acme"></bs-navbar-brand><bs-nav><bs-nav-item text="Services" url="#services"></bs-nav-item><bs-nav-item text="About" url="#about"></bs-nav-item></bs-nav></bs-navbar>' );
$nav = $r['blocks'][0] ?? array();
SD_Ai_Test::same( 'top block is the navbar', 'blockstrap/blockstrap-widget-navbar', $nav['name'] ?? null );
SD_Ai_Test::same( 'navbar has brand + nav children', 2, count( $nav['innerBlocks'] ?? array() ) );
SD_Ai_Test::same( 'first child is the brand', 'blockstrap/blockstrap-widget-navbar-brand', $nav['innerBlocks'][0]['name'] ?? null );
$navmenu = $nav['innerBlocks'][1] ?? array();
SD_Ai_Test::same( 'second child is the nav', 'blockstrap/blockstrap-widget-nav', $navmenu['name'] ?? null );
SD_Ai_Test::same( 'nav holds its two items', 2, sd_ai_count_blocks( array( $navmenu ), 'blockstrap/blockstrap-widget-nav-item' ) );
SD_Ai_Test::same( 'nav-item attributes map (text)', 'Services', $navmenu['innerBlocks'][0]['attributes']['text'] ?? null );
SD_Ai_Test::same( 'nothing dropped', array(), $r['report']['dropped_elements'] );

// An accordion recurses into its accordion-item children.
$r = Converter::convert( '<bs-accordion><bs-accordion-item></bs-accordion-item><bs-accordion-item></bs-accordion-item><bs-accordion-item></bs-accordion-item></bs-accordion>' );
$acc = $r['blocks'][0] ?? array();
SD_Ai_Test::same( 'accordion has three items', 3, count( $acc['innerBlocks'] ?? array() ) );

// A child outside the block's allowlist is ignored and noted, not converted.
$r = Converter::convert( '<bs-accordion><bs-nav-item text="stray"></bs-nav-item></bs-accordion>' );
$acc = $r['blocks'][0] ?? array();
SD_Ai_Test::same( 'non-listed child not converted', 0, count( $acc['innerBlocks'] ?? array() ) );
SD_Ai_Test::ok( 'non-listed child noted', false !== strpos( implode( ' ', $r['report']['notes'] ), 'not allowed inside' ) );

// A leaf widget still ignores any children (unchanged behaviour).
$r = Converter::convert( '<bs-gallery><div>x</div></bs-gallery>' );
SD_Ai_Test::same( 'leaf widget stays a leaf', array(), $r['blocks'][0]['innerBlocks'] ?? '__unset__' );

SD_Ai_Test::group( 'converter/vocabulary-nesting' );

// R11: the vocabulary reflects nesting reality — a container block is leaf:false
// with a derived children list; a leaf widget stays leaf:true.
$vocab = \AyeCode\SuperDuper\Ai\Vocabulary::vocabulary( '' );
$by_tag = array();
foreach ( $vocab['elements'] as $el ) {
	$by_tag[ $el['tag'] ] = $el;
}
SD_Ai_Test::same( 'bs-navbar is not a leaf', false, $by_tag['bs-navbar']['leaf'] ?? '__missing__' );
SD_Ai_Test::ok( 'bs-navbar lists brand + nav children', in_array( 'bs-navbar-brand', $by_tag['bs-navbar']['children'] ?? array(), true ) && in_array( 'bs-nav', $by_tag['bs-navbar']['children'] ?? array(), true ) );
SD_Ai_Test::same( 'bs-accordion children', array( 'bs-accordion-item' ), $by_tag['bs-accordion']['children'] ?? null );
SD_Ai_Test::same( 'bs-nav-item stays a leaf', true, $by_tag['bs-nav-item']['leaf'] ?? '__missing__' );
SD_Ai_Test::ok( 'a container with no allowlist has no children key', ! isset( $by_tag['bs-nav']['children'] ) && false === ( $by_tag['bs-nav']['leaf'] ?? true ) );

SD_Ai_Test::group( 'converter/hr-divider' );

$r = Converter::convert( '<hr class="my-4">' );
$hr = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::ok( 'hr carries border-top', false !== strpos( (string) ( $hr['css_class'] ?? '' ), 'border-top' ) );
SD_Ai_Test::ok( 'hr keeps its own spacing', ( $hr['mt'] ?? '' ) === '4' && ( $hr['mb'] ?? '' ) === '4' );

SD_Ai_Test::group( 'converter/form-contact' );

$r = Converter::convert( '<form class="p-4"><input type="text"><button>Send</button></form>' );
$f = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-contact' );
SD_Ai_Test::ok( 'form became bs_contact', null !== $f );
SD_Ai_Test::ok( 'dropped form fields noted', false !== strpos( implode( ' ', $r['report']['notes'] ), 'hand-built' ) );

SD_Ai_Test::group( 'converter/loose-text' );

// A bare text node inside a container is wrapped, not lost.
$r = Converter::convert( '<div class="card-body">loopreport.com<span class="badge">v2</span></div>' );
$rendered = \AyeCode\SuperDuper\Ai\Renderer::render( $r['blocks'] );
SD_Ai_Test::ok( 'loose text node survives', false !== strpos( $rendered, 'loopreport.com' ) );

SD_Ai_Test::group( 'converter/image-and-icon' );

$r   = Converter::convert( '<img src="https://picsum.photos/seed/x/600/400" alt="A photo">' );
$img = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-image' );
SD_Ai_Test::same( 'img src → img_url', 'https://picsum.photos/seed/x/600/400', $img['attributes']['img_url'] ?? null );
SD_Ai_Test::same( 'img mode url', 'url', $img['attributes']['img_src'] ?? null );
SD_Ai_Test::same( 'img alt', 'A photo', $img['attributes']['img_alt'] ?? null );

$r    = Converter::convert( '<i class="fas fa-star"></i>' );
$ib   = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::same( 'standalone icon → icon_class', 'fas fa-star', $ib['attributes']['icon_class'] ?? null );

SD_Ai_Test::group( 'converter/nesting-synthesis' );

$r     = Converter::convert( '<div class="col-md-4">A</div><div class="col-md-4">B</div>' );
$top   = $r['blocks'][0] ?? array();
SD_Ai_Test::same( 'orphan cols wrapped in a row', 'row', $top['attributes']['container'] ?? null );
SD_Ai_Test::same( 'row has both cols', 2, count( $top['innerBlocks'] ?? array() ) );
SD_Ai_Test::ok( 'wrap noted in report', in_array( 'Wrapped orphan column(s) in a synthesized row.', $r['report']['notes'], true ) );

SD_Ai_Test::group( 'converter/custom-element' );

$r  = Converter::convert( '<bs-gallery></bs-gallery>' );
$el = $r['blocks'][0] ?? array();
SD_Ai_Test::same( 'bs-gallery → gallery block', 'blockstrap/blockstrap-widget-gallery', $el['name'] ?? null );

$r = Converter::convert( '<bs-gallery><div>ignored</div></bs-gallery>' );
SD_Ai_Test::ok( 'custom element children ignored + noted', ! empty( $r['report']['notes'] ) );

SD_Ai_Test::group( 'converter/sanitization' );

$r = Converter::convert( '<p onclick="x()" style="color:red">Hi<script>alert(1)</script></p>' );
$p = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::ok( 'script stripped from text', false === strpos( (string) ( $p['attributes']['text'] ?? '' ), 'alert' ) );
SD_Ai_Test::ok( 'onclick not emitted', ! isset( $p['attributes']['onclick'] ) );
SD_Ai_Test::same( 'inline style reported', 1, count( $r['report']['inline_styles_dropped'] ) );

SD_Ai_Test::group( 'converter/unmappable' );

$r = Converter::convert( '<svg><path d="M0 0"/></svg>' );
SD_Ai_Test::ok( 'unmappable leaf dropped + reported', ! empty( $r['report']['dropped_elements'] ) );
SD_Ai_Test::same( 'no phantom blocks from svg', 0, count( $r['blocks'] ) );

SD_Ai_Test::group( 'converter/dark-mode-lint' );

$r = Converter::convert( '<div class="bg-white">x</div>' );
SD_Ai_Test::ok( 'bg-white flagged in notes', ! empty( $r['report']['notes'] ) );

SD_Ai_Test::group( 'converter/icon-box-composite' );

// An icon + text list item decomposes into an icon-box: identity in icon_class,
// colour in icon_color, copy in title, spacing absorbed, and type "none" so it
// does not silently link to the homepage.
$r  = Converter::convert( '<li class="mb-3"><i class="fa-solid fa-check me-2 text-success-emphasis"></i> Unlimited tasks</li>' );
$ib = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::ok( 'list item became an icon-box', null !== $ib );
$a = $ib['attributes'] ?? array();
SD_Ai_Test::same( 'icon_class is identity only', 'fa-solid fa-check', $a['icon_class'] ?? null );
SD_Ai_Test::same( 'icon colour token extracted', 'success-emphasis', $a['icon_color'] ?? null );
SD_Ai_Test::same( 'item copy becomes title', 'Unlimited tasks', $a['title'] ?? null );
SD_Ai_Test::same( 'link type forced to none', 'none', $a['type'] ?? null );
SD_Ai_Test::same( 'list-scale title_tag default', 'div', $a['title_tag'] ?? null );
SD_Ai_Test::same( 'icon_position left', 'left', $a['icon_position'] ?? null );
// Size and colour are neutralized (unset → neutral) so the item injects no
// scale the source did not carry.
SD_Ai_Test::same( 'icon_size neutralized', '0', $a['icon_size'] ?? null );
SD_Ai_Test::same( 'title_size neutralized', '0', $a['title_size'] ?? null );
SD_Ai_Test::ok( 'never emits derived content attribute', ! isset( $a['content'] ) && ! isset( $a['sd_shortcode'] ) && ! isset( $a['styleid'] ) );
SD_Ai_Test::ok( 'me-2 absorbed, not dropped', false !== strpos( implode( ' ', $r['report']['notes'] ), 'me-2' ) );

// A wrapping link becomes a custom URL instead of type none.
$r2 = Converter::convert( '<li><a href="https://x.test"><i class="fa fa-star"></i> Linked</a></li>' );
$a2 = sd_ai_find_block( $r2['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'wrapping link → custom type', 'custom', $a2['type'] ?? null );
SD_Ai_Test::same( 'wrapping link → custom_url', 'https://x.test', $a2['custom_url'] ?? null );

SD_Ai_Test::group( 'converter/defaults-neutralization' );

// A plain paragraph must not inherit the text block's mb_lg default (mb-3), and
// a plain wrapper div must pin container to empty, never the "container" default.
$r = Converter::convert( '<p>Hello</p>' );
$t = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::same( 'text mb_lg neutralized to empty', '', $t['attributes']['mb_lg'] ?? '__unset__' );

$r = Converter::convert( '<div class="p-4">x</div>' );
$c = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'plain wrapper pins container empty', '', $c['container'] ?? '__unset__' );

SD_Ai_Test::group( 'converter/button-variants' );

$r = Converter::convert( '<a href="#" class="btn btn-outline-primary btn-lg">Get Started</a>' );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-button' )['attributes'] ?? array();
SD_Ai_Test::same( 'btn-outline-primary → link_bg', 'outline-primary', $b['link_bg'] ?? null );
SD_Ai_Test::same( 'btn-lg → link_size large', 'large', $b['link_size'] ?? null );
SD_Ai_Test::same( 'button text', 'Get Started', $b['text'] ?? null );
SD_Ai_Test::same( '# link kept as custom url', '#', $b['custom_url'] ?? null );
SD_Ai_Test::same( '# link → type custom', 'custom', $b['type'] ?? null );
SD_Ai_Test::ok( 'btn not doubled in css_class', false === strpos( (string) ( $b['css_class'] ?? '' ), 'btn' ) );

SD_Ai_Test::group( 'converter/fold-boundaries' );

// Round 9: interactive/claimed elements are hard fold boundaries on every
// composite path. A flex wrapper of two buttons is NOT an icon item — it converts
// as a plain container with two bs_button children, never a fused icon-box.
$r = Converter::convert( '<div class="d-flex flex-wrap gap-3 align-items-center"><a href="tel:123" class="btn btn-primary btn-lg"><i class="fa-solid fa-phone"></i> Call Now</a><a href="#chat" class="btn btn-outline-secondary btn-lg"><i class="fa-brands fa-whatsapp"></i> Live Chat</a></div>' );
SD_Ai_Test::same( 'button group makes no icon-box', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
SD_Ai_Test::same( 'button group → two bs_button', 2, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-button' ) );
$btns = array();
$collect = function ( $bs ) use ( &$collect, &$btns ) {
	foreach ( $bs as $b ) {
		if ( ( $b['name'] ?? '' ) === 'blockstrap/blockstrap-widget-button' ) {
			$btns[] = $b['attributes'] ?? array();
		}
		if ( ! empty( $b['innerBlocks'] ) ) {
			$collect( $b['innerBlocks'] );
		}
	}
};
$collect( $r['blocks'] );
SD_Ai_Test::same( 'first button label', 'Call Now', $btns[0]['text'] ?? null );
SD_Ai_Test::same( 'first button variant', 'primary', $btns[0]['link_bg'] ?? null );
SD_Ai_Test::same( 'first button leading icon → icon_class', 'fa-solid fa-phone', $btns[0]['icon_class'] ?? null );
SD_Ai_Test::same( 'second button label', 'Live Chat', $btns[1]['text'] ?? null );
SD_Ai_Test::same( 'second button variant', 'outline-secondary', $btns[1]['link_bg'] ?? null );

// A single claimed button wrapped in a layout div is a button, not an icon item.
$r = Converter::convert( '<div class="col-12 col-md-auto text-center"><a href="tel:123" class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-phone me-2"></i>Call Now</a></div>' );
SD_Ai_Test::same( 'wrapped single button makes no icon-box', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-button' )['attributes'] ?? array();
SD_Ai_Test::same( 'wrapped button label', 'Call Now', $b['text'] ?? null );
SD_Ai_Test::same( 'wrapped button variant', 'primary', $b['link_bg'] ?? null );

// A plain (unclaimed) link wrapping an icon + label still folds into an icon item.
$r = Converter::convert( '<li><a href="https://x.test"><i class="fa fa-star"></i> Linked</a></li>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'plain wrapping link still folds', 'Linked', $a['title'] ?? null );
SD_Ai_Test::same( 'plain wrapping link → custom_url', 'https://x.test', $a['custom_url'] ?? null );

// A multi-run inline row (a trust line with bullet-separated spans) is not folded
// into one fused-title icon-box — the runs stay as their own blocks.
$r        = Converter::convert( '<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-circle-check text-success"></i><span>14-day trial</span><span class="opacity-50">&bull;</span><span>No card required</span></div>' );
$rendered = \AyeCode\SuperDuper\Ai\Renderer::render( $r['blocks'] );
SD_Ai_Test::ok( 'trust line keeps opacity-50 separator in render', false !== strpos( $rendered, 'opacity-50' ) );
SD_Ai_Test::ok( 'trust line keeps both text runs distinct', false !== strpos( $rendered, '14-day trial' ) && false !== strpos( $rendered, 'No card required' ) );
$fused = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::ok( 'trust line not fused into one icon-box title', null === $fused || false === strpos( (string) ( $fused['attributes']['title'] ?? '' ), 'No card required' ) );

// Round 9.1: a two-label justify row where ONE label carries its own icon must not
// fold — two label units means separate blocks, not one fused icon-box.
$r = Converter::convert( '<div class="d-flex align-items-center justify-content-between small"><span><i class="fa-solid fa-lock me-1"></i> Your data is secure</span><span><i class="fa-solid fa-bolt text-primary me-1"></i> Immediate dispatch</span></div>' );
$box = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
SD_Ai_Test::ok( 'two-label row not fused into one title', null === $box || false === strpos( (string) ( $box['attributes']['title'] ?? '' ), 'Immediate dispatch' ) || false === strpos( (string) ( $box['attributes']['title'] ?? '' ), 'Your data is secure' ) );
$both = \AyeCode\SuperDuper\Ai\Renderer::render( $r['blocks'] );
SD_Ai_Test::ok( 'two-label row keeps both labels', false !== strpos( $both, 'Your data is secure' ) && false !== strpos( $both, 'Immediate dispatch' ) );

// Round 9.1: a fa-* identity class must never appear in any css_class — a stray
// icon that cannot be an icon_class is stripped, not spilled.
$fa_leak = function ( array $blocks ) use ( &$fa_leak ) {
	foreach ( $blocks as $b ) {
		if ( preg_match( '/(^|\s)fa[a-z]?-/', (string) ( $b['attributes']['css_class'] ?? '' ) ) ) {
			return true;
		}
		if ( ! empty( $b['innerBlocks'] ) && $fa_leak( $b['innerBlocks'] ) ) {
			return true;
		}
	}
	return false;
};
SD_Ai_Test::ok( 'no fa- identity class in any css_class (two-label row)', ! $fa_leak( $r['blocks'] ) );
$r = Converter::convert( '<div class="d-flex align-items-center gap-2"><span><i class="fa-solid fa-a"></i> One</span><span><i class="fa-solid fa-b"></i> Two</span><span><i class="fa-solid fa-c"></i> Three</span></div>' );
SD_Ai_Test::ok( 'no fa- identity class in any css_class (three-label row)', ! $fa_leak( $r['blocks'] ) );

// A feature card caption folds, but a trailing CTA button is preserved (round 8
// behaviour, now guaranteed not to swallow the button on the generalized path).
$r = Converter::convert( '<div class="card-body d-flex flex-column"><div class="bg-primary-subtle rounded-3 p-3"><i class="fa-solid fa-star"></i></div><h3>Feature</h3><p>Copy.</p><a class="btn btn-primary" href="#">Learn more</a></div>' );
SD_Ai_Test::same( 'card caption still folds to one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-button' )['attributes'] ?? array();
SD_Ai_Test::same( 'trailing CTA kept as a real button with its label', 'Learn more', $b['text'] ?? null );
SD_Ai_Test::ok( 'folded box css_class carries no btn token', false === strpos( (string) ( sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes']['css_class'] ?? '' ), 'btn' ) );

SD_Ai_Test::group( 'converter/container-div-default' );

// D2: a plain wrapper renders as div, not section.
SD_Ai_Test::ok(
	false !== strpos( \AyeCode\SuperDuper\Ai\Renderer::render( Converter::convert( '<div class="p-4">x</div>' )['blocks'] ), '<div' ),
	'plain container renders as div'
);

SD_Ai_Test::group( 'converter/enum-safety' );

// The converter must never set an attribute to a value outside its enum. Build a
// reverse index per block and check every attribute value it emits.
$offenders = array();
$check = function ( array $blocks ) use ( &$check, &$offenders ) {
	foreach ( $blocks as $block ) {
		$base_id = null;
		foreach ( \AyeCode\SuperDuper\Ai\Catalog::block_ids() as $id ) {
			$inst = \AyeCode\SuperDuper\Registry::get_instance_public( $id );
			if ( $inst && \AyeCode\SuperDuper\Ai\Catalog::block_name( $inst->options ?? array() ) === ( $block['name'] ?? '' ) ) {
				$base_id = $id;
				break;
			}
		}
		if ( null !== $base_id ) {
			$idx = new \AyeCode\SuperDuper\Ai\ReverseIndex( (array) \AyeCode\SuperDuper\Registry::get_instance_public( $base_id )->get_arguments() );
			foreach ( (array) ( $block['attributes'] ?? array() ) as $name => $value ) {
				if ( 'select' === ( $idx->argument( $name )['type'] ?? '' ) && ! $idx->is_valid_value( $name, (string) $value ) ) {
					$offenders[] = "{$block['name']}.{$name}={$value}";
				}
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$check( $block['innerBlocks'] );
		}
	}
};
$r = Converter::convert( '<section class="py-5"><h2 class="display-6 fw-bold">A</h2><h3 class="h4">B</h3><div class="col-lg-6 d-inline-flex align-items-center rounded-4 border-0"><p class="text-body-secondary fs-5">c</p></div></section>' );
$check( $r['blocks'] );
SD_Ai_Test::ok( 'no attribute value outside its enum', empty( $offenders ), implode( ', ', $offenders ) );

SD_Ai_Test::group( 'converter/responsive-mapping' );

// R7.4: responsive variants map to their _md/_lg attributes (no longer verbatim).
$r = Converter::convert( '<div class="mt-lg-3 pt-md-4 text-lg-start d-lg-flex">x</div>' );
$d = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'mt-lg-3 → mt_lg', '3', $d['mt_lg'] ?? null );
SD_Ai_Test::same( 'pt-md-4 → pt_md', '4', $d['pt_md'] ?? null );
SD_Ai_Test::same( 'text-lg-start → text_align_lg', 'text-lg-start', $d['text_align_lg'] ?? null );
SD_Ai_Test::same( 'd-lg-flex → display_lg', 'd-lg-flex', $d['display_lg'] ?? null );
SD_Ai_Test::ok( 'responsive classes not left in css_class', '' === trim( (string) ( $d['css_class'] ?? '' ) ) );

SD_Ai_Test::group( 'converter/flex-when-display-set' );

// R7.3: flex utilities map to flex_* when d-flex is present on the same element,
// instead of spilling to css_class with the flex_* attributes left empty.
$r = Converter::convert( '<div class="d-flex align-items-center justify-content-center">x</div>' );
$d = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'd-flex → display', 'd-flex', $d['display'] ?? null );
SD_Ai_Test::same( 'align-items-center → flex_align_items', 'align-items-center', $d['flex_align_items'] ?? null );
SD_Ai_Test::same( 'justify-content-center → flex_justify_content', 'justify-content-center', $d['flex_justify_content'] ?? null );
SD_Ai_Test::ok( 'flex utilities not spilled to css_class', false === strpos( (string) ( $d['css_class'] ?? '' ), 'align-items' ) );

SD_Ai_Test::group( 'converter/media-object-alignment' );

// R7.1/R7.2/R7.5: the 07-contact-hero media object. A left-aligned chip + text
// stack folds to one icon-box carrying the alignment, chip size and spill hygiene.
$html = '<div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3"><div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary-emphasis rounded-3 p-3 flex-shrink-0"><i class="fa-solid fa-shield-halved fs-5"></i></div><div class="text-start"><h3 class="h6 mb-0 text-body-emphasis fw-bold">Licensed &amp; Insured</h3><p class="small text-body-secondary mb-0">Full coverage protection</p></div></div>';
$r    = Converter::convert( $html );
$ib   = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' );
$a    = $ib['attributes'] ?? array();

// R7.1 — the text stack's text-start survives as alignment attributes.
SD_Ai_Test::same( 'icon_text_align from stack', 'text-start', $a['icon_text_align'] ?? null );
SD_Ai_Test::same( 'title_text_align from stack', 'text-start', $a['title_text_align'] ?? null );
SD_Ai_Test::same( 'desc_text_align from stack', 'text-start', $a['desc_text_align'] ?? null );
// R7.2 — the chip's fs-5 becomes icon_size (was previously lost).
SD_Ai_Test::same( 'chip fs-5 → icon_size', 'fs-5', $a['icon_size'] ?? null );
// R7.5 — spill hygiene.
SD_Ai_Test::same( 'chip p-3 absorbed to pt', '3', $a['pt'] ?? null );
SD_Ai_Test::same( 'chip p-3 absorbed to pl', '3', $a['pl'] ?? null );
SD_Ai_Test::same( 'outer mb-0 → mb 0', '0', $a['mb'] ?? null );
SD_Ai_Test::same( 'heading mb-0 → title_pb 0', '0', $a['title_pb'] ?? null );
SD_Ai_Test::same( 'paragraph mb-0 → desc_pb 0', '0', $a['desc_pb'] ?? null );
SD_Ai_Test::same( 'small → desc_size fs-sm', 'fs-sm', $a['desc_size'] ?? null );
SD_Ai_Test::same( 'text-body-emphasis → title_color', 'body-emphasis', $a['title_color'] ?? null );
// Only genuine junk with no attribute equivalent remains in css_class.
SD_Ai_Test::ok( 'css_class holds only flex-shrink-0', 'flex-shrink-0' === trim( (string) ( $a['css_class'] ?? '' ) ) );

// The flex wrapper's utilities are attributes, incl. the responsive lg variant.
$w = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'wrapper flex_align_items', 'align-items-center', $w['flex_align_items'] ?? null );
SD_Ai_Test::same( 'wrapper flex_justify_content', 'justify-content-center', $w['flex_justify_content'] ?? null );
SD_Ai_Test::same( 'wrapper flex_justify_content_lg', 'justify-content-lg-start', $w['flex_justify_content_lg'] ?? null );

SD_Ai_Test::group( 'converter/body-emphasis-enum' );

// R7.5: body-emphasis is a valid shared colour token, mapped not spilled.
$r = Converter::convert( '<p class="text-body-emphasis">x</p>' );
$t = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::same( 'text-body-emphasis → text_color', 'body-emphasis', $t['attributes']['text_color'] ?? null );

SD_Ai_Test::group( 'converter/core-post-content' );

// R10.0: <bs-post-content> is the one core-block exception, emitting
// core/post-content; its interior is ignored (it renders the dynamic body).
$r  = Converter::convert( '<bs-post-content>literal copy that must be ignored</bs-post-content>' );
$pc = $r['blocks'][0] ?? array();
SD_Ai_Test::same( 'bs-post-content → core/post-content', 'core/post-content', $pc['name'] ?? null );
SD_Ai_Test::same( 'core block carries no attributes', array(), $pc['attributes'] ?? null );
SD_Ai_Test::same( 'core block is a leaf', array(), $pc['innerBlocks'] ?? null );
SD_Ai_Test::ok( 'ignored interior is noted', false !== strpos( implode( ' ', $r['report']['notes'] ), 'post body' ) );
// The vocabulary advertises it in single context with the reserved description.
$vocab = \AyeCode\SuperDuper\Ai\Vocabulary::vocabulary( 'single' );
$tags  = array_column( $vocab['elements'], 'tag' );
SD_Ai_Test::ok( 'vocabulary lists bs-post-content (single)', in_array( 'bs-post-content', $tags, true ) );
SD_Ai_Test::ok( 'vocabulary omits bs-post-content (page)', ! in_array( 'bs-post-content', array_column( \AyeCode\SuperDuper\Ai\Vocabulary::vocabulary( 'page' )['elements'], 'tag' ), true ) );

SD_Ai_Test::group( 'converter/icon-only-button' );

// R10.1: an icon-only a.btn/button is a bs_button with icon_class, not an icon-box
// — even when it carries a chip-like rounded-circle marker.
$r = Converter::convert( '<a class="btn btn-outline-secondary rounded-circle p-2" href="#"><i class="fa-brands fa-x-twitter"></i></a>' );
SD_Ai_Test::same( 'icon-only a.btn makes no icon-box', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-button' )['attributes'] ?? array();
SD_Ai_Test::same( 'icon-only a.btn → bs_button icon_class', 'fa-brands fa-x-twitter', $b['icon_class'] ?? null );
SD_Ai_Test::same( 'icon-only a.btn variant', 'outline-secondary', $b['link_bg'] ?? null );
SD_Ai_Test::same( 'icon-only a.btn has no text', '', $b['text'] ?? '__unset__' );

SD_Ai_Test::group( 'converter/strong-prefix-split' );

// R10.2: a caption that leads with a bold run splits — bold lead (colon stripped)
// → title, remainder → description. A mid-text bold stays emphasis, not a title.
$r = Converter::convert( '<li class="d-flex align-items-start gap-3"><span class="bg-primary-subtle rounded-circle p-2 flex-shrink-0"><i class="fa-solid fa-check"></i></span><span><strong>Systemic Efficiency:</strong> Streamline foundational processes to reduce load.</span></li>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'bold lead → title (colon stripped)', 'Systemic Efficiency', $a['title'] ?? null );
SD_Ai_Test::same( 'remainder → description', 'Streamline foundational processes to reduce load.', $a['description'] ?? null );
// R10.3: the wrapper's/chip's flex + gap layout classes are absorbed, not spilled.
SD_Ai_Test::ok( 'outer flex/gap absorbed from css_class', false === strpos( (string) ( $a['css_class'] ?? '' ), 'gap-3' ) && false === strpos( (string) ( $a['css_class'] ?? '' ), 'align-items-start' ) );

// A mid-text bold is emphasis, not a title split.
$r = Converter::convert( '<li><i class="fa-solid fa-check"></i><span>Streamline <strong>everything</strong> now.</span></li>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'mid-text bold not split (no description)', null, $a['description'] ?? null );
SD_Ai_Test::same( 'mid-text bold whole run is title', 'Streamline everything now.', $a['title'] ?? null );

SD_Ai_Test::group( 'converter/name-badge' );

// R10: a heading followed by a trailing decorative icon (a verified badge) folds
// into one titled icon-box, not a stranded icon-box beside the heading.
$r = Converter::convert( '<div class="d-flex align-items-center gap-2"><h3 class="h4">Elena Vance</h3><i class="fa-solid fa-circle-check text-primary"></i></div>' );
SD_Ai_Test::same( 'name + trailing badge → one icon-box', 1, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' ) );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'name → title', 'Elena Vance', $a['title'] ?? null );
SD_Ai_Test::same( 'badge → icon_class', 'fa-solid fa-circle-check', $a['icon_class'] ?? null );
SD_Ai_Test::same( 'no stray heading beside the badge box', 0, sd_ai_count_blocks( $r['blocks'], 'blockstrap/blockstrap-widget-heading' ) );

SD_Ai_Test::group( 'converter/rounded-independent' );

// R10.4: a border-radius renders independent of border (build_aui_class emits it
// outside the border block), so rounded-4 on a borderless element stays a real
// rounded_size attribute rather than spilling to css_class.
$r   = Converter::convert( '<div class="ratio ratio-16x9 rounded-4 overflow-hidden">x</div>' );
$box = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'rounded-4 (no border) → rounded_size', '4', $box['rounded_size'] ?? null );
SD_Ai_Test::ok( 'rounded-4 not spilled to css_class', false === strpos( (string) ( $box['css_class'] ?? '' ), 'rounded-4' ) );

SD_Ai_Test::group( 'converter/census-mappings' );

// R12 bucket A: Bootstrap's bare `.small` is the fs-sm font-size utility — mapped
// universally on any block whose font-size attribute accepts fs-sm.
$r = Converter::convert( '<p class="small">Fine print</p>' );
$t = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' );
SD_Ai_Test::same( 'small → font_size fs-sm (text)', 'fs-sm', $t['attributes']['font_size'] ?? null );
SD_Ai_Test::ok( 'small not spilled', false === strpos( (string) ( $t['attributes']['css_class'] ?? '' ), 'small' ) );

// small on a container (font_size enabled on the container in R12).
$r = Converter::convert( '<div class="small">x</div>' );
SD_Ai_Test::same( 'small → font_size on container', 'fs-sm', $r['blocks'][0]['attributes']['font_size'] ?? null );

// small + fw-semibold on an icon-item label route to the title family, not css_class.
$r = Converter::convert( '<li><i class="fa-solid fa-check"></i><span class="small fw-semibold">Label</span></li>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-icon-box' )['attributes'] ?? array();
SD_Ai_Test::same( 'icon-item small → title_size', 'fs-sm', $a['title_size'] ?? null );
SD_Ai_Test::same( 'icon-item fw-semibold → title_font_weight', 'fw-semibold', $a['title_font_weight'] ?? null );

// R12 bucket B: container gap / flex_direction / flex_wrap families.
$r = Converter::convert( '<div class="d-flex flex-column flex-md-row flex-wrap gap-2 gap-md-3">x</div>' );
$c = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'flex-column → flex_direction', 'flex-column', $c['flex_direction'] ?? null );
SD_Ai_Test::same( 'flex-md-row → flex_direction_md', 'flex-md-row', $c['flex_direction_md'] ?? null );
SD_Ai_Test::same( 'flex-wrap → flex_wrap', 'flex-wrap', $c['flex_wrap'] ?? null );
SD_Ai_Test::same( 'gap-2 → gap', 'gap-2', $c['gap'] ?? null );
SD_Ai_Test::same( 'gap-md-3 → gap_md', 'gap-md-3', $c['gap_md'] ?? null );
SD_Ai_Test::ok( 'flex/gap not spilled', '' === trim( (string) ( $c['css_class'] ?? '' ) ) );

// R12 bucket B: ratio combo (ratio ratio-16x9) → ratio attribute, bare marker absorbed.
$r = Converter::convert( '<div class="ratio ratio-16x9">x</div>' );
$c = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'ratio-16x9 → ratio', '16x9', $c['ratio'] ?? null );
SD_Ai_Test::ok( 'ratio marker not spilled', false === strpos( (string) ( $c['css_class'] ?? '' ), 'ratio' ) );

// R12 bucket B: fw-medium is now a valid font_weight token.
$r = Converter::convert( '<p class="fw-medium">x</p>' );
SD_Ai_Test::same( 'fw-medium → font_weight', 'fw-medium', sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-text' )['attributes']['font_weight'] ?? null );

// R12 bucket B: the flex_align_self enum bug is fixed — align-self-* maps (and
// renders regardless of the editor's container=="col" gate).
$r = Converter::convert( '<div class="align-self-start">x</div>' );
$c = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'align-self-start → flex_align_self', 'align-self-start', $c['flex_align_self'] ?? null );
SD_Ai_Test::ok( 'align-self not spilled', false === strpos( (string) ( $c['css_class'] ?? '' ), 'align-self' ) );

SD_Ai_Test::group( 'converter/background-style' );

// R13: background declarations in style="" are consumed into the bg family;
// only the residue is dropped + reported.

// 1. Gradient via background-image → custom-gradient + bg_gradient.
$r = Converter::convert( '<section style="background-image: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)">x</section>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'gradient → bg custom-gradient', 'custom-gradient', $a['bg'] ?? null );
SD_Ai_Test::same( 'gradient → bg_gradient value', 'linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)', $a['bg_gradient'] ?? null );
SD_Ai_Test::same( 'no background left in inline_styles_dropped', array(), $r['report']['inline_styles_dropped'] );

// 1b. Gradient via the `background:` shorthand (bare gradient value).
$r = Converter::convert( '<div style="background: radial-gradient(circle, #fff, #000)">x</div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'shorthand gradient → bg_gradient', 'radial-gradient(circle, #fff, #000)', $a['bg_gradient'] ?? null );
SD_Ai_Test::same( 'shorthand gradient → bg custom-gradient', 'custom-gradient', $a['bg'] ?? null );

// 2. Gradient-text combo → bg_on_text, transparent companions consumed silently.
$r = Converter::convert( '<h1 style="background-image: linear-gradient(90deg, #ff512f, #dd2476); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">Hi</h1>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-heading' )['attributes'] ?? array();
SD_Ai_Test::same( 'combo → bg_on_text', '1', $a['bg_on_text'] ?? null );
SD_Ai_Test::same( 'combo → bg custom-gradient', 'custom-gradient', $a['bg'] ?? null );
SD_Ai_Test::same( 'combo companions not reported as loss', array(), $r['report']['inline_styles_dropped'] );

// 3. Custom colour → custom-color + bg_color (hex, rgb, hsl forms).
foreach ( array( '#1a2b3c', 'rgb(10, 20, 30)', 'hsl(210, 50%, 20%)' ) as $colour ) {
	$r = Converter::convert( '<div style="background-color: ' . $colour . '">x</div>' );
	$a = $r['blocks'][0]['attributes'] ?? array();
	SD_Ai_Test::same( "background-color {$colour} → bg custom-color", 'custom-color', $a['bg'] ?? null );
	SD_Ai_Test::same( "background-color {$colour} → bg_color", $colour, $a['bg_color'] ?? null );
}

// 4. Image background → bg_image; background-attachment: fixed → bg_image_fixed.
$r = Converter::convert( '<div style="background-image: url(https://picsum.photos/seed/x/1200/600); background-attachment: fixed">x</div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'url background → bg_image', 'https://picsum.photos/seed/x/1200/600', $a['bg_image'] ?? null );
SD_Ai_Test::same( 'background-attachment fixed → bg_image_fixed', '1', $a['bg_image_fixed'] ?? null );

// 5. Prefix generality: a block WITHOUT the field leaves the declaration in the drop
// report (bs_button has no bg_gradient).
$r = Converter::convert( '<a class="btn btn-primary" href="#" style="background-image: linear-gradient(90deg, #fff, #000)">Go</a>' );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-button' )['attributes'] ?? array();
SD_Ai_Test::ok( 'button gets no bg_gradient', ! isset( $b['bg_gradient'] ) );
SD_Ai_Test::ok( 'unsupported background reported dropped', ! empty( $r['report']['inline_styles_dropped'] ) );

// 6. Rejection guard: an unsafe value is not consumed — dropped + reported.
$r = Converter::convert( '<div style="background-image: linear-gradient(90deg, #fff); background: expression(alert(1))">x</div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::ok( 'safe gradient still consumed', ( $a['bg'] ?? '' ) === 'custom-gradient' );
SD_Ai_Test::ok( 'unsafe expression() reported dropped', false !== strpos( wp_json_encode( $r['report']['inline_styles_dropped'] ), 'expression' ) );

// 7. Non-background declarations keep today's behaviour: dropped + reported.
$r = Converter::convert( '<div style="max-width: 500px; background-color: #123456">x</div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'background-color consumed', '#123456', $a['bg_color'] ?? null );
SD_Ai_Test::ok( 'non-background residue dropped', false !== strpos( wp_json_encode( $r['report']['inline_styles_dropped'] ), 'max-width' ) );

// 8. Round-trip stability: convert → render → re-convert yields identical bg attrs.
$html = '<section style="background-image: linear-gradient(135deg, #6a11cb, #2575fc)"><h1 style="background-image: linear-gradient(90deg, #ff512f, #dd2476); background-clip: text; -webkit-text-fill-color: transparent;">Hi</h1></section>';
$one  = Converter::convert( $html );
$two  = Converter::convert( \AyeCode\SuperDuper\Ai\Renderer::render( $one['blocks'] ) );
$s1   = $one['blocks'][0]['attributes'] ?? array();
$s2   = $two['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'round-trip stable: section bg', $s1['bg'] ?? null, $s2['bg'] ?? null );
SD_Ai_Test::same( 'round-trip stable: section bg_gradient', $s1['bg_gradient'] ?? null, $s2['bg_gradient'] ?? null );
$h1a = sd_ai_find_block( $one['blocks'], 'blockstrap/blockstrap-widget-heading' )['attributes'] ?? array();
$h2a = sd_ai_find_block( $two['blocks'], 'blockstrap/blockstrap-widget-heading' )['attributes'] ?? array();
SD_Ai_Test::same( 'round-trip stable: heading bg_on_text', $h1a['bg_on_text'] ?? null, $h2a['bg_on_text'] ?? null );

SD_Ai_Test::group( 'converter/custom-text-color' );

// R13 addendum (2026-08-07): color: <hex|rgb|hsl> → text_color custom + text_color_custom.
foreach ( array( '#ffd166', 'rgb(255, 209, 102)', 'hsl(45, 100%, 70%)' ) as $colour ) {
	$r = Converter::convert( '<div style="color: ' . $colour . '">x</div>' );
	$a = $r['blocks'][0]['attributes'] ?? array();
	SD_Ai_Test::same( "color {$colour} → text_color custom", 'custom', $a['text_color'] ?? null );
	SD_Ai_Test::same( "color {$colour} → text_color_custom", $colour, $a['text_color_custom'] ?? null );
	SD_Ai_Test::same( "color {$colour} → nothing dropped", array(), $r['report']['inline_styles_dropped'] );
}

// Ordering: a transparent color: inside the gradient-text combo is a companion (absorbed
// silently), never a custom text colour.
$r = Converter::convert( '<h1 style="background-image: linear-gradient(90deg, #ff512f, #dd2476); background-clip: text; color: transparent;">Hi</h1>' );
$a = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-heading' )['attributes'] ?? array();
SD_Ai_Test::ok( 'combo transparent color not mapped to text_color_custom', ! isset( $a['text_color_custom'] ) );
SD_Ai_Test::same( 'combo transparent color not reported as loss', array(), $r['report']['inline_styles_dropped'] );

// background-color and color together map both families on the same element.
$r = Converter::convert( '<div style="background-color: #1a2b3c; color: #ffd166">x</div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'combined bg_color', '#1a2b3c', $a['bg_color'] ?? null );
SD_Ai_Test::same( 'combined text_color_custom', '#ffd166', $a['text_color_custom'] ?? null );

// A block WITHOUT text_color_custom (bs_image) leaves color: in the drop report.
$r = Converter::convert( '<img src="https://picsum.photos/seed/x/400" style="color: #ffd166">' );
$b = sd_ai_find_block( $r['blocks'], 'blockstrap/blockstrap-widget-image' )['attributes'] ?? array();
SD_Ai_Test::ok( 'image gets no text_color_custom', ! isset( $b['text_color_custom'] ) );
SD_Ai_Test::ok( 'unsupported color reported dropped', false !== strpos( wp_json_encode( $r['report']['inline_styles_dropped'] ), 'color' ) );

SD_Ai_Test::group( 'converter/gap-on-row-repair' );

// R13 addendum (2026-08-07): flex gap-* on a .row breaks the grid; rewrite to the
// gutter family, reported under `repairs` (never as loss).
$r = Converter::convert( '<div class="row gap-3"><div class="col">x</div></div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
$c = ' ' . ( $a['css_class'] ?? '' ) . ' ';
SD_Ai_Test::ok( 'gap-3 → g-3 present', false !== strpos( $c, ' g-3 ' ) );
SD_Ai_Test::ok( 'gap-3 gone', false === strpos( $c, ' gap-3 ' ) );
SD_Ai_Test::ok( 'gap-on-row repair recorded', false !== strpos( wp_json_encode( $r['report']['repairs'] ), 'gap-on-row' ) );

// row-gap → gy, column-gap → gx.
$r = Converter::convert( '<div class="row row-gap-2 column-gap-4"><div class="col">x</div></div>' );
$c = ' ' . ( $r['blocks'][0]['attributes']['css_class'] ?? '' ) . ' ';
SD_Ai_Test::ok( 'row-gap-2 → gy-2', false !== strpos( $c, ' gy-2 ' ) );
SD_Ai_Test::ok( 'column-gap-4 → gx-4', false !== strpos( $c, ' gx-4 ' ) );

// Breakpoint infix is preserved.
$r = Converter::convert( '<div class="row gap-md-3"><div class="col">x</div></div>' );
$c = ' ' . ( $r['blocks'][0]['attributes']['css_class'] ?? '' ) . ' ';
SD_Ai_Test::ok( 'gap-md-3 → g-md-3', false !== strpos( $c, ' g-md-3 ' ) );

// Dedupe: when the target gutter class is already present, the source is dropped.
$r = Converter::convert( '<div class="row g-3 gap-3"><div class="col">x</div></div>' );
$c = ' ' . ( $r['blocks'][0]['attributes']['css_class'] ?? '' ) . ' ';
SD_Ai_Test::same( 'dedupe: single g-3, no doubling', 1, substr_count( $c, ' g-3 ' ) );
$repairs = $r['report']['repairs'];
SD_Ai_Test::ok( 'dedupe repair records a drop (to null)', ! empty( array_filter( $repairs, static fn( $x ) => null === $x['to'] ) ) );

// Guard: gap-* on a non-row element is left untouched (no repair) — it stays a valid
// gap utility, consumed into the block's `gap` attribute.
$r = Converter::convert( '<div class="d-flex gap-3">x</div>' );
$a = $r['blocks'][0]['attributes'] ?? array();
SD_Ai_Test::same( 'gap-3 kept on non-row flex', 'gap-3', $a['gap'] ?? null );
SD_Ai_Test::same( 'no repair on non-row', array(), $r['report']['repairs'] );
