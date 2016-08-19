<?php
/*
Plugin Name: Front End PM
Plugin URI: https://www.shamimsplugins.com/wordpress/contact-us/
Description: Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system fromfront end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.
Version: 4.3
Author: Shamim
Author URI: https://www.shamimsplugins.com/wordpress/contact-us/
Text Domain: front-end-pm
License: GPLv2 or later
*/
//DEFINE

class Front_End_Pm {

	private static $instance;
	
	private function __construct() {

		$this->constants();
		$this->includes();
		$this->actions();
		//$this->filters();

	}
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	
	private function constants()
    	{
			define('FEP_PLUGIN_VERSION', '4.3' );
			define('FEP_PLUGIN_FILE',  __FILE__ );
			define('FEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			define('FEP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
			
			global $wpdb;
			
			if ( !defined ('FEP_MESSAGES_TABLE' ) )
			define('FEP_MESSAGES_TABLE',$wpdb->prefix.'fep_messages');
			
			if ( !defined ('FEP_META_TABLE' ) )
			define('FEP_META_TABLE',$wpdb->prefix.'fep_meta');
    	}
	
	private function includes()
    	{
			require_once( FEP_PLUGIN_DIR. 'functions.php');

			if( file_exists( FEP_PLUGIN_DIR. 'pro/pro-features.php' ) ) {
				require_once( FEP_PLUGIN_DIR. 'pro/pro-features.php');
			}
    	}
	
	private function actions()
    	{
			register_activation_hook(__FILE__ , array($this, 'fep_plugin_activate' ) );
			register_deactivation_hook(__FILE__ , array($this, 'fep_plugin_deactivate' ) );
    	}
	
	function fep_plugin_activate(){

		global $wpdb;
		
			$roles = array_keys( get_editable_roles() );
			$id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[front-end-pm]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
			
			$options = array();
			
			$options['userrole_access'] = $roles;
			$options['userrole_new_message'] = $roles;
			$options['userrole_reply'] = $roles;
			$options['plugin_version'] = FEP_PLUGIN_VERSION;
			$options['page_id'] = $id;
			
			update_option( 'FEP_admin_options', wp_parse_args( get_option('FEP_admin_options'), $options) );
			
			fep_add_caps_to_roles();
	
	}
	
} //END Class

Front_End_Pm::init();

	
