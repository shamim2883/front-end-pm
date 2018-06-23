<?php
/*
Plugin Name:	Front End PM
Plugin URI:		https://www.shamimsplugins.com/contact-us/
Description:	Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system fromfront end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.
Version:		8.4.1
Author:			Shamim Hasan
Author URI:		https://www.shamimsplugins.com/contact-us/
License:		GPLv2 or later
License URI:	https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:	front-end-pm
Domain Path:	/languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Front_End_Pm {
	private static $instance;

	private function __construct() {
		$this->constants();
		$this->includes();
		$this->actions();
		//$this->filters();
	}

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function constants() {
		global $wpdb;
		define( 'FEP_PLUGIN_VERSION', '8.4.1' );
		define( 'FEP_PLUGIN_FILE',  __FILE__ );
		define( 'FEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'FEP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		if ( ! defined ('FEP_MESSAGES_TABLE' ) ) {
			define( 'FEP_MESSAGES_TABLE', $wpdb->prefix . 'fep_messages' );
		}
		if ( ! defined ('FEP_META_TABLE' ) ) {
			define( 'FEP_META_TABLE', $wpdb->prefix . 'fep_meta' );
		}
	}

	function includes() {
		require_once( FEP_PLUGIN_DIR . 'functions.php' );
		if ( file_exists( FEP_PLUGIN_DIR . 'pro/pro-features.php' ) ) {
			require_once( FEP_PLUGIN_DIR . 'pro/pro-features.php' );
		}
	}

	function actions() {
		register_activation_hook( __FILE__ , array( $this, 'fep_plugin_activate' ) );
		register_deactivation_hook( __FILE__ , array( $this, 'fep_plugin_deactivate' ) );
	}

	function fep_plugin_activate() {
	}

	function fep_plugin_deactivate() {
	}
} //END Class
Front_End_Pm::init();
