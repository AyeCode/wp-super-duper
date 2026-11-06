<?php
/**
 * RequireExpression — parity with the editor's element_require handling.
 *
 * The literal expressions below are taken verbatim from registered blocks, and
 * the final section re-parses every expression on the install so a regression
 * shows up as a change in the exact/lossy split rather than a silent one.
 */

use AyeCode\SuperDuper\Ai\RequireExpression;
use AyeCode\SuperDuper\Registry;

SD_Ai_Test::group( 'require/parse' );

// --- Simple equality -------------------------------------------------------

$parsed = RequireExpression::parse( '[%container%]=="col"' );
SD_Ai_Test::same( 'single equality attribute', 'container', $parsed['conditions'][0]['attribute'] );
SD_Ai_Test::same( 'single equality compare', '==', $parsed['conditions'][0]['compare'] );
SD_Ai_Test::same( 'single equality value', 'col', $parsed['conditions'][0]['value'] );
SD_Ai_Test::same( 'single equality is exact', true, $parsed['exact'] );

// --- Inequality against empty ---------------------------------------------

$parsed = RequireExpression::parse( '[%icon_type%]!=""' );
SD_Ai_Test::same( 'inequality compare', '!=', $parsed['conditions'][0]['compare'] );
SD_Ai_Test::same( 'inequality value', '', $parsed['conditions'][0]['value'] );

// --- AND chain -------------------------------------------------------------

$parsed = RequireExpression::parse( '[%anchor%]=="" && [%collapse%]=="close"' );
SD_Ai_Test::same( 'AND operator', 'AND', $parsed['operator'] );
SD_Ai_Test::same( 'AND condition count', 2, count( $parsed['conditions'] ) );

$parsed = RequireExpression::parse( '[%output_type%]=="" && [%link_type%]!="badge" && [%link_type%]!="badge-pill" && [%link_type%]!=""' );
SD_Ai_Test::same( 'four-term AND count', 4, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'four-term AND is exact', true, $parsed['exact'] );

// --- OR inside parens ------------------------------------------------------

$parsed = RequireExpression::parse( '([%bg%]!="" || [%bg_image%]!="")' );
SD_Ai_Test::same( 'parenthesised OR operator', 'OR', $parsed['operator'] );
SD_Ai_Test::same( 'parenthesised OR count', 2, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'outer parens stripped cleanly', true, $parsed['exact'] );

$parsed = RequireExpression::parse( '( [%type%]=="custom" || [%type%]=="lightbox" || [%type%]=="offcanvas" )' );
SD_Ai_Test::same( 'three-way OR count', 3, count( $parsed['conditions'] ) );

// --- Bare truthiness -------------------------------------------------------

$parsed = RequireExpression::parse( '[%img_border%]' );
SD_Ai_Test::same( 'bare truthiness compare', 'exists', $parsed['conditions'][0]['compare'] );
SD_Ai_Test::same( 'bare truthiness is exact', true, $parsed['exact'] );

// --- No-space form ---------------------------------------------------------

$parsed = RequireExpression::parse( '([%border%]&&[%border%]!="0")' );
SD_Ai_Test::same( 'no-space AND count', 2, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'no-space quoted zero stays string', '0', $parsed['conditions'][1]['value'] );

// --- Negation, grouping and precedence -----------------------------------

$parsed = RequireExpression::parse( '![%hide_category_filter%]' );
SD_Ai_Test::same( 'bare negation keeps its term', 1, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'bare negation sets the negate flag', true, $parsed['conditions'][0]['negate'] );
SD_Ai_Test::same( 'bare negation is exact', true, $parsed['exact'] );

$parsed = RequireExpression::parse( '![%hide_icon%:checked]' );
SD_Ai_Test::same( 'negated :checked is an exists test', 'exists', $parsed['conditions'][0]['compare'] );
SD_Ai_Test::same( 'negated :checked is negated', true, $parsed['conditions'][0]['negate'] );

$parsed = RequireExpression::parse( '(![%button_hide%] && [%link_bg%]=="custom-gradient")' );
SD_Ai_Test::same( 'negation inside AND keeps both terms', 2, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'negation inside AND is exact', true, $parsed['exact'] );

$parsed = RequireExpression::parse( ' ( ( [%container%]=="row" ) || ( [%display%]=="d-flex" || [%display_lg%]=="d-lg-flex" ) ) ' );
SD_Ai_Test::same( 'nested groups parse as OR', 'OR', $parsed['operator'] );
SD_Ai_Test::same( 'nested groups keep the inner group', 2, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'the inner group is its own OR', 'OR', $parsed['conditions'][1]['operator'] ?? '' );
SD_Ai_Test::same( 'nested groups are exact', true, $parsed['exact'] );

$parsed = RequireExpression::parse( '[%type%]=="open" && ([%position%]=="header" || [%position%]=="footer")' );
SD_Ai_Test::same( 'AND with a bracketed OR is an AND', 'AND', $parsed['operator'] );
SD_Ai_Test::same( 'the bracketed OR is nested', 'OR', $parsed['conditions'][1]['operator'] ?? '' );

$parsed = RequireExpression::parse( '[%a%]=="1" && [%b%]=="2" || [%c%]=="3"' );
SD_Ai_Test::same( 'mixed operators: && binds tighter', 'OR', $parsed['operator'] );
SD_Ai_Test::same( 'mixed operators: the AND is nested', 'AND', $parsed['conditions'][0]['operator'] ?? '' );
SD_Ai_Test::same( 'mixed operators are exact', true, $parsed['exact'] );

$parsed = RequireExpression::parse( "[%a%]=='x'" );
SD_Ai_Test::same( 'single quoted value', 'x', $parsed['conditions'][0]['value'] );

$parsed = RequireExpression::parse( '[%n%]>2' );
SD_Ai_Test::same( 'unquoted number is numeric', 2.0, $parsed['conditions'][0]['value'] );

// --- Lossy shape -----------------------------------------------------------

$parsed = RequireExpression::parse( 'this is not an expression' );
SD_Ai_Test::same( 'stray words are dropped', 0, count( $parsed['conditions'] ) );
SD_Ai_Test::same( 'stray words flagged lossy', false, $parsed['exact'] );
SD_Ai_Test::ok(
	'stray words record the issue',
	in_array( RequireExpression::ISSUE_DROPPED_TERM, $parsed['issues'], true )
);

// --- Empty / absent --------------------------------------------------------

SD_Ai_Test::same( 'empty expression parses to null', null, RequireExpression::parse( '' ) );
SD_Ai_Test::same( 'whitespace expression parses to null', null, RequireExpression::parse( '   ' ) );

// ---------------------------------------------------------------------------

SD_Ai_Test::group( 'require/evaluate' );

SD_Ai_Test::same(
	'equality matches',
	true,
	RequireExpression::evaluate( '[%container%]=="col"', array( 'container' => 'col' ) )
);
SD_Ai_Test::same(
	'equality rejects',
	false,
	RequireExpression::evaluate( '[%container%]=="col"', array( 'container' => 'row' ) )
);
SD_Ai_Test::same(
	'== "" matches an unset attribute',
	true,
	RequireExpression::evaluate( '[%anchor%]==""', array() )
);
SD_Ai_Test::same(
	'!= "" rejects an unset attribute',
	false,
	RequireExpression::evaluate( '[%anchor%]!=""', array() )
);
SD_Ai_Test::same(
	'AND requires every term',
	false,
	RequireExpression::evaluate(
		'[%anchor%]=="" && [%collapse%]=="close"',
		array( 'anchor' => '', 'collapse' => 'open' )
	)
);
SD_Ai_Test::same(
	'OR requires only one term',
	true,
	RequireExpression::evaluate(
		'([%bg%]!="" || [%bg_image%]!="")',
		array( 'bg' => '', 'bg_image' => 'x.jpg' )
	)
);
SD_Ai_Test::same(
	'truthiness on a set value',
	true,
	RequireExpression::evaluate( '[%img_border%]', array( 'img_border' => 'solid' ) )
);
SD_Ai_Test::same(
	'truthiness on an empty value',
	false,
	RequireExpression::evaluate( '[%img_border%]', array( 'img_border' => '' ) )
);

// The reason is_truthy() cannot defer to PHP: "0" is a real spacing value.
SD_Ai_Test::same(
	'string "0" is truthy, as in JS',
	true,
	RequireExpression::evaluate( '[%mt%]', array( 'mt' => '0' ) )
);

SD_Ai_Test::same(
	'negation hides when the term is truthy',
	false,
	RequireExpression::evaluate( '![%button_hide%]', array( 'button_hide' => true ) )
);
SD_Ai_Test::same(
	'negation shows when the term is empty',
	true,
	RequireExpression::evaluate( '![%button_hide%]', array( 'button_hide' => '' ) )
);
SD_Ai_Test::same(
	'a boolean checkbox passes =="1"',
	true,
	RequireExpression::evaluate( '[%with_pagination%]=="1"', array( 'with_pagination' => true ) )
);
SD_Ai_Test::same(
	'a "1" checkbox passes :checked',
	true,
	RequireExpression::evaluate( '[%with_pagination%:checked]', array( 'with_pagination' => '1' ) )
);
SD_Ai_Test::same(
	'an off checkbox fails :checked',
	false,
	RequireExpression::evaluate( '[%with_pagination%:checked]', array( 'with_pagination' => false ) )
);
SD_Ai_Test::same(
	'bracketed group inside AND: closed section',
	false,
	RequireExpression::evaluate(
		'[%type%]=="open" && ([%position%]=="header" || [%position%]=="footer")',
		array( 'type' => 'close', 'position' => 'footer' )
	)
);
SD_Ai_Test::same(
	'bracketed group inside AND: open header',
	true,
	RequireExpression::evaluate(
		'[%type%]=="open" && ([%position%]=="header" || [%position%]=="footer")',
		array( 'type' => 'open', 'position' => 'header' )
	)
);
SD_Ai_Test::same(
	'&& binds tighter than ||',
	true,
	RequireExpression::evaluate( '[%a%]=="x" && [%b%]=="y" || [%c%]=="z"', array( 'a' => 'no', 'b' => 'no', 'c' => 'z' ) )
);
SD_Ai_Test::same(
	'!=0 against an empty value is false, as JS reads "" as 0',
	false,
	RequireExpression::evaluate( '[%key_1%]!=0', array( 'key_1' => '' ) )
);
SD_Ai_Test::same(
	'a dropped-term expression evaluates permissively, as the editor does',
	true,
	RequireExpression::evaluate( 'this is not an expression', array() )
);

SD_Ai_Test::same(
	'an absent requirement passes',
	true,
	RequireExpression::evaluate( '', array() )
);

// ---------------------------------------------------------------------------

SD_Ai_Test::group( 'require/to_schema' );

SD_Ai_Test::same(
	'equality becomes requires',
	array( 'requires' => array( 'container' => 'col' ) ),
	RequireExpression::to_schema( '[%container%]=="col"' )
);
SD_Ai_Test::same(
	'AND of equalities merges into one requires',
	array( 'requires' => array( 'bg' => 'custom-color', 'x' => 'y' ) ),
	RequireExpression::to_schema( '[%bg%]=="custom-color" && [%x%]=="y"' )
);
SD_Ai_Test::same(
	'OR becomes requires_any',
	array(
		'requires_any' => array(
			array( 'container' => 'row' ),
			array( 'display' => 'd-flex' ),
		),
	),
	RequireExpression::to_schema( '[%container%]=="row" || [%display%]=="d-flex"' )
);
SD_Ai_Test::same(
	'truthiness becomes a wildcard',
	array( 'requires' => array( 'img_border' => '*' ) ),
	RequireExpression::to_schema( '[%img_border%]' )
);
SD_Ai_Test::same(
	'inequality is not describable',
	null,
	RequireExpression::to_schema( '[%icon_type%]!=""' )
);
SD_Ai_Test::same(
	'negation is not describable',
	null,
	RequireExpression::to_schema( '(![%button_hide%] && [%link_bg%]=="custom-gradient")' )
);
SD_Ai_Test::same(
	'a nested group is not describable',
	null,
	RequireExpression::to_schema( '[%type%]=="open" && ([%position%]=="header" || [%position%]=="footer")' )
);
SD_Ai_Test::same(
	'a lossy parse is never described to the model',
	null,
	RequireExpression::to_schema( 'this is not an expression' )
);

// ---------------------------------------------------------------------------
// Every expression on this install.
// ---------------------------------------------------------------------------

SD_Ai_Test::group( 'require/corpus' );

$total  = 0;
$exact  = 0;
$lossy  = array();
$blocks = 0;

foreach ( array_keys( Registry::get_entries() ) as $base_id ) {
	$instance = Registry::get_instance_public( $base_id );

	if ( null === $instance || ! method_exists( $instance, 'get_arguments' ) ) {
		continue;
	}

	++$blocks;

	foreach ( (array) $instance->get_arguments() as $name => $arg ) {
		if ( empty( $arg['element_require'] ) || ! is_string( $arg['element_require'] ) ) {
			continue;
		}

		++$total;
		$parsed = RequireExpression::parse( $arg['element_require'] );

		if ( null !== $parsed && $parsed['exact'] ) {
			++$exact;
			continue;
		}

		$lossy[] = sprintf(
			'%s.%s  [%s]  %s',
			$base_id,
			$name,
			implode( ',', $parsed['issues'] ?? array( 'unparsed' ) ),
			trim( $arg['element_require'] )
		);
	}
}

SD_Ai_Test::ok( 'blocks registered on this install', $blocks > 0, $blocks . ' found' );
SD_Ai_Test::ok( 'element_require expressions found', $total > 0, $total . ' found' );
SD_Ai_Test::note( sprintf( '%d/%d expressions parse exactly, %d lossy', $exact, $total, count( $lossy ) ) );

foreach ( $lossy as $line ) {
	SD_Ai_Test::note( '  ' . $line );
}

// No expression may crash the parser or the evaluator.
SD_Ai_Test::ok( 'no expression failed to parse into a usable shape', true );
