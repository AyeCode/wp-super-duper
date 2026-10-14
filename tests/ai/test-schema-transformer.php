<?php
/**
 * SchemaTransformer + Catalog — native contract.
 *
 * Native means attribute keys and values are exactly what createBlock() accepts:
 * no responsive collapse, no enum normalization. Size is controlled by tiering.
 */

use AyeCode\SuperDuper\Ai\SchemaTransformer;
use AyeCode\SuperDuper\Ai\Catalog;
use AyeCode\SuperDuper\Registry;

// Editor plumbing that must never survive into a transformed attribute.
const SD_AI_FORBIDDEN_KEYS = array(
	'device_type', 'row', 'block_component', 'group', 'element_require',
	'desc_tip', 'icon', 'clears_on_change', 'advanced', 'options', 'name',
	'value', 'placeholder', 'custom_attributes', 'button_title', 'is_slug',
);

SD_Ai_Test::group( 'transform/shape' );

$container = Registry::get_instance_public( 'bs_container' );
$result    = SchemaTransformer::transform( (array) $container->get_arguments() );
$attrs     = $result['attrs'];

SD_Ai_Test::ok( 'container produces attributes', ! empty( $attrs ), count( $attrs ) . ' attrs' );

foreach ( array( 'visibility_conditions', 'anchor', 'metadata_name', 'bg_image', 'bg_image_id', 'hover_animations', 'sd_shortcode', 'styleid' ) as $dropped ) {
	SD_Ai_Test::ok( "dropped: {$dropped}", ! isset( $attrs[ $dropped ] ) );
}

// Every surviving attribute carries json_type and tier, and nothing else leaks.
$leaks = array();
foreach ( $attrs as $name => $schema ) {
	if ( empty( $schema['json_type'] ) ) {
		$leaks[] = "{$name}: no json_type";
	}
	if ( empty( $schema['tier'] ) ) {
		$leaks[] = "{$name}: no tier";
	}
	foreach ( SD_AI_FORBIDDEN_KEYS as $forbidden ) {
		if ( array_key_exists( $forbidden, $schema ) ) {
			$leaks[] = "{$name}.{$forbidden}";
		}
	}
}
SD_Ai_Test::ok( 'every attr has json_type + tier, no leaks', empty( $leaks ), implode( ', ', array_slice( $leaks, 0, 8 ) ) );

// container: content tier, native enum + labels + default.
SD_Ai_Test::same( 'container json_type string', 'string', $attrs['container']['json_type'] ?? null );
SD_Ai_Test::same( 'container tier content', 'content', $attrs['container']['tier'] ?? null );
SD_Ai_Test::same( 'container default kept', 'container', $attrs['container']['default'] ?? null );
SD_Ai_Test::ok( 'container enum has native row', in_array( 'row', $attrs['container']['enum'], true ) );
SD_Ai_Test::ok( 'container enum_labels present', ! empty( $attrs['container']['enum_labels'] ) );

// css_class: kept, style tier, escape-hatch note.
SD_Ai_Test::same( 'css_class tier style', 'style', $attrs['css_class']['tier'] ?? null );
SD_Ai_Test::ok( 'css_class note', ! empty( $attrs['css_class']['note'] ) );

// bg grouped options flattened, native keys, dark-mode note.
SD_Ai_Test::ok( 'bg enum flattened native', in_array( 'primary-subtle', $attrs['bg']['enum'] ?? array(), true ) );
SD_Ai_Test::ok( 'bg dark-mode note', ! empty( $attrs['bg']['note'] ) );
SD_Ai_Test::same( 'bg_color format hex', 'hex', $attrs['bg_color']['format'] ?? null );
SD_Ai_Test::same( 'bg_gradient format css-gradient', 'css-gradient', $attrs['bg_gradient']['format'] ?? null );

SD_Ai_Test::group( 'transform/native-values' );

// text_align keeps NATIVE values (text-start), and is NOT collapsed/responsive.
SD_Ai_Test::ok( 'text_align enum native (text-start)', in_array( 'text-start', $attrs['text_align']['enum'] ?? array(), true ) );
SD_Ai_Test::ok( 'no responsive flag on text_align', ! isset( $attrs['text_align']['responsive'] ) );
SD_Ai_Test::ok( 'text_align_md survives as its own attr', isset( $attrs['text_align_md'] ) );
SD_Ai_Test::ok( 'text_align_md keeps native value', in_array( 'text-md-start', $attrs['text_align_md']['enum'] ?? array(), true ) );

// Tiering: typography and spacing are style; a breakpoint variant is never
// promoted above its base.
SD_Ai_Test::same( 'text_align (typography) is style', 'style', $attrs['text_align']['tier'] ?? null );
SD_Ai_Test::same( 'text_align_md is style', 'style', $attrs['text_align_md']['tier'] ?? null );
SD_Ai_Test::same( 'mt is style tier', 'style', $attrs['mt']['tier'] ?? null );
// A layout choice (container group) IS content.
SD_Ai_Test::same( 'container is content tier', 'content', $attrs['container']['tier'] ?? null );
SD_Ai_Test::same( 'html_tag forced to content', 'content', $attrs['html_tag']['tier'] ?? null );

SD_Ai_Test::group( 'transform/checkbox+requires' );

// h100 is a checkbox → string enum, never boolean.
SD_Ai_Test::same( 'h100 json_type string', 'string', $attrs['h100']['json_type'] ?? null );
SD_Ai_Test::ok( 'h100 enum is string pair', ! in_array( true, $attrs['h100']['enum'] ?? array(), true ) );
SD_Ai_Test::ok( 'h100 has enum_labels', ! empty( $attrs['h100']['enum_labels'] ) );

// requires derived where the expression is clean.
SD_Ai_Test::same( 'col requires container=col', array( 'container' => 'col' ), $attrs['col']['requires'] ?? null );
SD_Ai_Test::same( 'bg_color requires bg=custom-color', array( 'bg' => 'custom-color' ), $attrs['bg_color']['requires'] ?? null );

// Raw requires kept server-side for the lossy flex fields.
SD_Ai_Test::ok( 'lossy flex expression kept raw', ! empty( $result['requires']['flex_align_items'] ) );
SD_Ai_Test::ok( 'lossy flex not described to model', ! isset( $attrs['flex_align_items']['requires'] ) && ! isset( $attrs['flex_align_items']['requires_any'] ) );

SD_Ai_Test::group( 'transform/corpus' );

$leaks     = array();
$max_bytes = 0;
$worst     = '';

foreach ( Catalog::block_ids() as $base_id ) {
	$instance = Registry::get_instance_public( $base_id );
	if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
		continue;
	}

	$t = SchemaTransformer::transform( (array) $instance->get_arguments() );

	foreach ( $t['attrs'] as $name => $schema ) {
		if ( empty( $schema['json_type'] ) || empty( $schema['tier'] ) ) {
			$leaks[] = "{$base_id}.{$name}: missing json_type/tier";
		}
		foreach ( SD_AI_FORBIDDEN_KEYS as $forbidden ) {
			if ( array_key_exists( $forbidden, $schema ) ) {
				$leaks[] = "{$base_id}.{$name}.{$forbidden}";
			}
		}
	}

	$bytes = strlen( (string) wp_json_encode( $t['attrs'] ) );
	if ( $bytes > $max_bytes ) {
		$max_bytes = $bytes;
		$worst     = $base_id;
	}
}

SD_Ai_Test::ok( 'no leaks across all blocks; every attr tiered + typed', empty( $leaks ), implode( ', ', array_slice( $leaks, 0, 8 ) ) );
SD_Ai_Test::note( sprintf( 'largest full block schema: %s at %d bytes', $worst, $max_bytes ) );

SD_Ai_Test::group( 'catalog' );

$catalog = Catalog::catalog();
$bytes   = strlen( (string) wp_json_encode( $catalog ) );
SD_Ai_Test::ok( 'catalog lists blocks', ! empty( $catalog['blocks'] ) );
SD_Ai_Test::ok( 'catalog under 200KB ceiling', $bytes < 200000, "{$bytes} bytes" );
SD_Ai_Test::note( sprintf( 'catalog is %d bytes for %d blocks', $bytes, count( $catalog['blocks'] ) ) );

$by_id = array();
foreach ( $catalog['blocks'] as $entry ) {
	$by_id[ $entry['id'] ] = $entry;
}

$c = $by_id['bs_container'];
SD_Ai_Test::same( 'catalog block_name native', 'blockstrap/blockstrap-widget-container', $c['block_name'] ?? null );
SD_Ai_Test::same( 'catalog title stripped', 'Container', $c['title'] ?? null );
SD_Ai_Test::same( 'catalog inner_blocks any', 'any', $c['inner_blocks'] ?? null );
SD_Ai_Test::ok( 'catalog carries content-tier attr (container)', isset( $c['attributes']['container'] ) );
SD_Ai_Test::ok( 'catalog omits style-tier attr (mt)', ! isset( $c['attributes']['mt'] ) );
SD_Ai_Test::ok( 'catalog has description', ! empty( $c['description'] ) );

// A non-nesting block reports inner_blocks none.
SD_Ai_Test::same( 'heading inner_blocks none', 'none', ( $by_id['bs_heading']['inner_blocks'] ?? null ) );

SD_Ai_Test::group( 'schemas' );

$schemas = Catalog::schemas( array( 'bs_container', 'bs_image' ) );
$sc      = $schemas['blocks']['bs_container'];
SD_Ai_Test::same( 'schemas block_name native', 'blockstrap/blockstrap-widget-container', $sc['block_name'] ?? null );
SD_Ai_Test::ok( 'schemas carries content attr (container)', isset( $sc['attributes']['container'] ) );
SD_Ai_Test::ok( 'schemas carries style attr (mt)', isset( $sc['attributes']['mt'] ) );
SD_Ai_Test::ok( 'schemas has nesting', ! empty( $sc['nesting'] ) );

// The image URL path the AI uses in v1.
$img = $schemas['blocks']['bs_image']['attributes'];
SD_Ai_Test::ok( 'img_url present', isset( $img['img_url'] ) );
SD_Ai_Test::same( 'img_url gated on img_src=url', array( 'img_src' => 'url' ), $img['img_url']['requires'] ?? null );

// /schemas carries the raw field group, disambiguating repeated labels; /catalog
// does not.
SD_Ai_Test::same( 'schemas attr carries group (mt)', 'wrapper-styles', $sc['attributes']['mt']['group'] ?? null );

$iconbox = Catalog::schemas( array( 'bs_iconbox' ) )['blocks']['bs_iconbox']['attributes'];
SD_Ai_Test::same( 'icon size group', 'icon-style', $iconbox['icon_size']['group'] ?? null );
SD_Ai_Test::same( 'title size group', 'title-style', $iconbox['title_size']['group'] ?? null );
SD_Ai_Test::ok(
	'same label disambiguated by group',
	( $iconbox['icon_size']['label'] ?? '' ) === ( $iconbox['title_size']['label'] ?? '_' )
		&& ( $iconbox['icon_size']['group'] ?? '' ) !== ( $iconbox['title_size']['group'] ?? '' )
);

$catalog_container = null;
foreach ( Catalog::catalog()['blocks'] as $entry ) {
	if ( 'bs_container' === $entry['id'] ) {
		$catalog_container = $entry;
	}
}
$catalog_has_group = false;
foreach ( (array) ( $catalog_container['attributes'] ?? array() ) as $schema ) {
	if ( isset( $schema['group'] ) ) {
		$catalog_has_group = true;
	}
}
SD_Ai_Test::ok( 'catalog attrs omit group', ! $catalog_has_group );
