<?php
/**
 * This is a Hello World test plugin for WP Super Duper Class.
 *
 * @wordpress-plugin
 * Plugin Name: Super Duper - Hello World
 * Description: This is a Hello World test plugin for WP Super Duper Class.
 * Version: 0.0.1
 * Author: AyeCode
 * Author URI: https://ayecode.io
 * Text Domain: hello-world
 * Domain Path: /languages
 * Requires at least: 4.2
 * Tested up to: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if(!class_exists('WP_Super_Duper')) {
	// include the class if needed
	include_once( dirname( __FILE__ ) . "/wp-super-duper.php" );
}

class Hello_World extends WP_Super_Duper {


	public $arguments;
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'    => 'hello-world',
			'block-icon'    => 'admin-site',
			'block-category'=> 'common',
			'block-keywords'=> "['hello','world']",
			'block-output'  => array(
				'element::p'   => array(
					'title' => __('Placeholder','hello-world'),
					'class' => '[%className%]',
					'content' => 'Hello: [%after_text%]'
				)
			),
			'class_name'    => __CLASS__,
			'base_id'       => 'hello_world', // this is used as the widget id and the shortcode id.
			'name'          => __('Hello World','hello-world'), // the name of the widget.
			'widget_ops'    => array(
				'classname'   => 'hello-world-class', // widget class
				'description' => esc_html__('This is an example that will take a text parameter and output it after `Hello:`.','hello-world'), // widget description
			),
			'arguments'     => array(
				'after_text'  => array(
					'name' => __('after_text', 'hello-world'),
					'title' => __('Text after hello:', 'hello-world'),
					'desc' => __('This is the text that will appear after `Hello:`.', 'hello-world'),
					'type' => 'text',
					'placeholder' => 'World',
					'desc_tip' => true,
					'default'  => 'World',
					'advanced' => false
				),
			)
		);


		parent::__construct( $options );


	}


	public function output($args = array(), $widget_args = array(),$content = ''){
		
		/**
		 * @var string $after_text
		 */
		extract($args, EXTR_SKIP);
		
		return "Hello: ". $after_text;
		
	}

}

// register it.
add_action( 'widgets_init', function () {
	register_widget( 'Hello_World' );
} );