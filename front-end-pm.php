<?php
/*
Plugin Name:	Front End PM
Plugin URI:		https://www.shamimsplugins.com/contact-us/
Description:	Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system fromfront end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.
Version:		11.3.3
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
		$this->hooks();
	}

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function constants() {
		global $wpdb;
		define( 'FEP_PLUGIN_VERSION', '11.3.3' );
		define( 'FEP_DB_VERSION', '1121' );
		define( 'FEP_PLUGIN_FILE', __FILE__ );
		define( 'FEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'FEP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

		if ( ! defined ('FEP_MESSAGE_TABLE' ) ) {
			define( 'FEP_MESSAGE_TABLE', $wpdb->base_prefix . 'fep_messages' );
		}
		if ( ! defined ('FEP_META_TABLE' ) ) {
			define( 'FEP_META_TABLE', $wpdb->base_prefix . 'fep_messagemeta' );
		}
		if ( ! defined ('FEP_PARTICIPANT_TABLE' ) ) {
			define( 'FEP_PARTICIPANT_TABLE', $wpdb->base_prefix . 'fep_participants' );
		}
		if ( ! defined ('FEP_ATTACHMENT_TABLE' ) ) {
			define( 'FEP_ATTACHMENT_TABLE', $wpdb->base_prefix . 'fep_attachments' );
		}
	}

	function includes() {
		require_once( FEP_PLUGIN_DIR . 'functions.php' );
		require_once( FEP_PLUGIN_DIR . 'default-hooks.php' );
	}

	function hooks() {
		//cleanup after uninstall
		fep_fs()->add_action('after_uninstall', 'fep_fs_uninstall_cleanup');
		//Support fourm link in admin dashboard sidebar
		fep_fs()->add_filter( 'support_forum_url', 'fep_fs_support_forum_url' );
	}

} //END Class
//Front_End_Pm::init();

if ( function_exists( 'fep_fs' ) ) {
	fep_fs()->set_basename( false, __FILE__ );
} else {
	// DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
	if ( ! function_exists( 'fep_fs' ) ) {
		// Create a helper function for easy SDK access.
		function fep_fs() {
			global $fep_fs;
	
			if ( ! isset( $fep_fs ) ) {
				// Include Freemius SDK.
				require_once dirname(__FILE__) . '/freemius/start.php';
	
				$fep_fs = fs_dynamic_init( array(
					'id'                  => '5809',
					'slug'                => 'front-end-pm',
					'premium_slug'        => 'front-end-pm-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_c7329ca7019f17b830c22b8f3a729',
					'is_premium'          => false,
					'premium_suffix'      => 'PRO',
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'anonymous_mode'      => true,
					'is_live'             => true,
					'menu'                => array(
						'slug'           => 'fep-all-messages',
						'contact'        => false,
					),
				) );
			}
	
			return $fep_fs;
		}
	
		// Init Freemius.
		fep_fs();
		// Signal that SDK was initiated.
		do_action( 'fep_fs_loaded' );
	}

	// ... Your plugin's main file logic ...
	Front_End_Pm::init();
}
