<?php
/**
 * ReverseIndex — class → attribute resolution per block.
 *
 * Built from a block's own arguments, so these run against the real bs_container
 * / bs_text / bs_button / bs_image / bs_iconbox schemas registered on this
 * install.
 */

use AyeCode\SuperDuper\Ai\ReverseIndex;
use AyeCode\SuperDuper\Registry;

$container = new ReverseIndex( (array) Registry::get_instance_public( 'bs_container' )->get_arguments() );

SD_Ai_Test::group( 'reverse-index/prefix' );

$cases = array(
	'mt-3'              => array( 'mt' => '3' ),
	'me-2'             => array( 'mr' => '2' ),
	'ms-4'             => array( 'ml' => '4' ),
	'pt-5'             => array( 'pt' => '5' ),
	'col-md-6'         => array( 'col_md' => '6' ),
	'bg-body-tertiary' => array( 'bg' => 'body-tertiary' ),
	'bg-primary'       => array( 'bg' => 'primary' ),
	'text-body-secondary' => array( 'text_color' => 'body-secondary' ),
	'row-cols-2'       => array( 'row_cols' => '2' ),
	'mt-n1'            => array( 'mt' => 'n1' ),
);

foreach ( $cases as $class => $expected ) {
	SD_Ai_Test::same( "match {$class}", $expected, $container->match( $class ) );
}

SD_Ai_Test::group( 'reverse-index/literal' );

SD_Ai_Test::same( 'text-center is literal text_align', array( 'text_align' => 'text-center' ), $container->match( 'text-center' ) );
SD_Ai_Test::same( 'shadow-lg is literal shadow', array( 'shadow' => 'shadow-lg' ), $container->match( 'shadow-lg' ) );
SD_Ai_Test::same( 'fw-bold is literal font_weight', array( 'font_weight' => 'fw-bold' ), $container->match( 'fw-bold' ) );
SD_Ai_Test::same( 'd-none is literal display', array( 'display' => 'd-none' ), $container->match( 'd-none' ) );

SD_Ai_Test::group( 'reverse-index/exceptions' );

SD_Ai_Test::same( 'border-0 exception', array( 'border' => '0' ), $container->match( 'border-0' ) );

SD_Ai_Test::group( 'reverse-index/miss' );

SD_Ai_Test::same( 'unknown class does not match', null, $container->match( 'not-a-real-class' ) );
SD_Ai_Test::same( 'button-only class does not match container', null, $container->match( 'btn-primary' ) );

SD_Ai_Test::group( 'reverse-index/content-attrs' );

$text   = new ReverseIndex( (array) Registry::get_instance_public( 'bs_text' )->get_arguments() );
$button = new ReverseIndex( (array) Registry::get_instance_public( 'bs_button' )->get_arguments() );
$image  = new ReverseIndex( (array) Registry::get_instance_public( 'bs_image' )->get_arguments() );
$icon   = new ReverseIndex( (array) Registry::get_instance_public( 'bs_iconbox' )->get_arguments() );

SD_Ai_Test::same( 'bs_text text attr is html-capable', array( 'name' => 'text', 'html' => true ), $text->text_attr() );
SD_Ai_Test::same( 'bs_button text attr is plain', array( 'name' => 'text', 'html' => false ), $button->text_attr() );
SD_Ai_Test::same( 'container has no text attr', null, $container->text_attr() );

SD_Ai_Test::same( 'bs_button icon attr', 'icon_class', $button->icon_attr() );
SD_Ai_Test::same( 'bs_iconbox icon attr', 'icon_class', $icon->icon_attr() );

SD_Ai_Test::same( 'bs_image image attrs', array( 'src' => 'img_url', 'mode' => 'img_src', 'alt' => 'img_alt' ), $image->image_attrs() );
SD_Ai_Test::same( 'bs_button link attrs', array( 'url' => 'custom_url', 'type' => 'type' ), $button->link_attrs() );
SD_Ai_Test::same( 'container has no image attrs', null, $container->image_attrs() );
