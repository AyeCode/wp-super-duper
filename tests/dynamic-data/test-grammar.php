<?php
/**
 * Grammar, held to the fixture file the JS parser shares.
 */

use AyeCode\SuperDuper\DynamicData\Grammar;

$fixtures = json_decode( file_get_contents( dirname( __DIR__ ) . '/fixtures/dynamic-data-grammar.json' ), true );

SD_DD_Test::group( 'grammar/parse' );

foreach ( $fixtures['parse'] as $case ) {
	SD_DD_Test::same( $case['input'], $case['expect'], Grammar::parse( $case['input'] ) );
}

SD_DD_Test::group( 'grammar/build' );

foreach ( $fixtures['build'] as $case ) {
	$built = Grammar::build( $case['name'], $case['primary'], $case['args'] );
	SD_DD_Test::same( $case['expect'], $case['expect'], $built );

	// Whatever build writes must parse back to the same parts.
	$args = array_filter( $case['args'], static fn( $v ) => false !== $v );
	SD_DD_Test::same(
		$case['expect'] . ' round trip',
		array(
			'name'    => $case['name'],
			'primary' => $case['primary'],
			'args'    => $args,
		),
		Grammar::parse( $built )
	);
}

SD_DD_Test::group( 'grammar/find' );

foreach ( $fixtures['find'] as $case ) {
	SD_DD_Test::same( $case['input'], $case['expect'], array_column( Grammar::find( $case['input'] ), 'raw' ) );
}
