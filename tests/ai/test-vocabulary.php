<?php
/**
 * Vocabulary + Renderer.
 *
 * Vocabulary lists custom elements (blocks with no ai-html claim) plus the fixed
 * authoring rules; Renderer serializes a native tree to block markup.
 */

use AyeCode\SuperDuper\Ai\Vocabulary;
use AyeCode\SuperDuper\Ai\Renderer;
use AyeCode\SuperDuper\Ai\Converter;

SD_Ai_Test::group( 'vocabulary/shape' );

$vocab = Vocabulary::vocabulary();

SD_Ai_Test::ok( 'has schema_hash', ! empty( $vocab['schema_hash'] ) );
SD_Ai_Test::ok( 'rules is non-empty list', ! empty( $vocab['rules'] ) && is_array( $vocab['rules'] ) );
SD_Ai_Test::ok( 'elements is a list', is_array( $vocab['elements'] ) );

$tags = array_column( $vocab['elements'], 'tag' );

// Claimed native blocks must NOT appear as custom elements.
foreach ( array( 'bs-heading', 'bs-text', 'bs-button', 'bs-image', 'bs-container', 'bs-iconbox' ) as $claimed ) {
	SD_Ai_Test::ok( "claimed {$claimed} excluded from vocabulary", ! in_array( $claimed, $tags, true ) );
}

// An unclaimed block appears, as a leaf, with a dashed tag.
SD_Ai_Test::ok( 'unclaimed bs-gallery is a custom element', in_array( 'bs-gallery', $tags, true ) );

$gallery = null;
foreach ( $vocab['elements'] as $element ) {
	if ( 'bs-gallery' === $element['tag'] ) {
		$gallery = $element;
	}
}
SD_Ai_Test::ok( 'custom element is a leaf', ! empty( $gallery['leaf'] ) );
SD_Ai_Test::ok( 'custom element lists attributes', isset( $gallery['attributes'] ) && is_array( $gallery['attributes'] ) );

SD_Ai_Test::group( 'renderer/serialize' );

$blocks = Converter::convert( '<section class="py-5"><h2 class="fw-bold">Hi</h2></section>' )['blocks'];
$markup = Renderer::serialize( $blocks );

SD_Ai_Test::ok( 'serializes container open tag', false !== strpos( $markup, '<!-- wp:blockstrap/blockstrap-widget-container' ) );
SD_Ai_Test::ok( 'serializes heading self-closing', false !== strpos( $markup, '<!-- wp:blockstrap/blockstrap-widget-heading' ) );
SD_Ai_Test::ok( 'container has a closing tag', false !== strpos( $markup, '<!-- /wp:blockstrap/blockstrap-widget-container -->' ) );
SD_Ai_Test::same( 'render disabled by default', false, Renderer::is_enabled() );
