<?php
/**
 * ClassMap + SchemaTransformer class_prefix.
 *
 * ClassMap mirrors Utils::build_aui_class in reverse. These tests pin the
 * non-obvious pieces of that mirror (the logical me-/ms-/pe-/ps- prefixes,
 * responsive infixes) and confirm the class_prefix is stamped onto token
 * attributes in the transformed schema — plus a drift check that a value really
 * does round-trip to the class build_aui_class emits.
 */

use AyeCode\SuperDuper\Ai\ClassMap;
use AyeCode\SuperDuper\Ai\SchemaTransformer;
use AyeCode\SuperDuper\Registry;
use AyeCode\SuperDuper\Utils;

SD_Ai_Test::group( 'class-map/prefixes' );

$expected = array(
	'mt'     => 'mt-',
	'mr'     => 'me-',
	'mb'     => 'mb-',
	'ml'     => 'ms-',
	'pr'     => 'pe-',
	'pl'     => 'ps-',
	'mt_md'  => 'mt-md-',
	'mr_lg'  => 'me-lg-',
	'col'    => 'col-',
	'col_md' => 'col-md-',
	'row_cols' => 'row-cols-',
	'bg'     => 'bg-',
	'text_color' => 'text-',
);

foreach ( $expected as $attr => $prefix ) {
	SD_Ai_Test::same( "prefix {$attr}", $prefix, ClassMap::prefix_for( $attr ) );
}

SD_Ai_Test::same( 'unknown attr has no prefix', null, ClassMap::prefix_for( 'text' ) );

SD_Ai_Test::group( 'class-map/literal-attrs' );

$literal = ClassMap::literal_attrs();
foreach ( array( 'shadow', 'font_weight', 'text_align', 'display', 'text_align_lg' ) as $name ) {
	SD_Ai_Test::ok( "literal includes {$name}", in_array( $name, $literal, true ) );
}
foreach ( array( 'container', 'css_class', 'styleid' ) as $name ) {
	SD_Ai_Test::ok( "literal excludes {$name}", ! in_array( $name, $literal, true ) );
}

SD_Ai_Test::group( 'class-map/exceptions' );

$exceptions = ClassMap::exceptions();
SD_Ai_Test::same( 'border-0 exception', array( 'border' => '0' ), $exceptions['border-0'] ?? null );
SD_Ai_Test::same( 'text-justify exception', array( 'text_justify' => '1' ), $exceptions['text-justify'] ?? null );
SD_Ai_Test::same( 'absolute center', 'center', ClassMap::absolute_positions()['start-50 top-50 translate-middle'] ?? null );

SD_Ai_Test::group( 'class-map/schema-stamp' );

$container = Registry::get_instance_public( 'bs_container' );
$attrs     = SchemaTransformer::transform( (array) $container->get_arguments() )['attrs'];

SD_Ai_Test::same( 'mt class_prefix stamped', 'mt-', $attrs['mt']['class_prefix'] ?? null );
SD_Ai_Test::same( 'mr class_prefix stamped', 'me-', $attrs['mr']['class_prefix'] ?? null );
SD_Ai_Test::same( 'bg class_prefix stamped', 'bg-', $attrs['bg']['class_prefix'] ?? null );
SD_Ai_Test::ok( 'literal attr has no class_prefix', ! isset( $attrs['shadow']['class_prefix'] ) );
SD_Ai_Test::ok( 'text attr has no class_prefix', ! isset( $attrs['html_tag']['class_prefix'] ) );

// Drift check: a value fed through build_aui_class must produce exactly the
// class that prefix + value predicts, so the reverse mapping stays honest.
SD_Ai_Test::group( 'class-map/drift' );

$cases = array(
	array( 'mt', '3', 'mt-3' ),
	array( 'mr', '2', 'me-2' ),
	array( 'ml', '4', 'ms-4' ),
	array( 'col_md', '6', 'col-md-6' ),
	array( 'bg', 'primary', 'bg-primary' ),
	array( 'text_color', 'body-secondary', 'text-body-secondary' ),
);

foreach ( $cases as $case ) {
	list( $attr, $value, $class ) = $case;
	$built = Utils::build_aui_class( array( $attr => $value ) );
	SD_Ai_Test::ok(
		"build_aui_class {$attr}={$value} => {$class}",
		in_array( $class, explode( ' ', $built ), true ),
		"got: {$built}"
	);
}

// An lg-only responsive column must keep its breakpoint: col_lg "6" with no base
// col renders col-lg-6, not col-6 (which would widen it to every breakpoint).
$col_lg_only = explode( ' ', Utils::build_aui_class( array( 'container' => 'col', 'col_lg' => '6' ) ) );
SD_Ai_Test::ok( 'col_lg-only keeps -lg- breakpoint', in_array( 'col-lg-6', $col_lg_only, true ), implode( ' ', $col_lg_only ) );
SD_Ai_Test::ok( 'col_lg-only does not collapse to col-6', ! in_array( 'col-6', $col_lg_only, true ) );

$col_base_lg = explode( ' ', Utils::build_aui_class( array( 'col' => '4', 'col_lg' => '6' ) ) );
SD_Ai_Test::ok( 'col + col_lg both present', in_array( 'col-4', $col_base_lg, true ) && in_array( 'col-lg-6', $col_base_lg, true ), implode( ' ', $col_base_lg ) );

$rc_lg = explode( ' ', Utils::build_aui_class( array( 'row_cols_lg' => '3' ) ) );
SD_Ai_Test::ok( 'row_cols_lg-only keeps -lg- breakpoint', in_array( 'row-cols-lg-3', $rc_lg, true ), implode( ' ', $rc_lg ) );
