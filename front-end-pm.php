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

define('FEP_PLUGIN_VERSION', '4.3' );
define('FEP_PLUGIN_DIR',plugin_dir_path( __FILE__ ) );
define('FEP_PLUGIN_URL',plugins_url( '/', __FILE__ ) );

global $wpdb;

if ( !defined ('FEP_MESSAGES_TABLE' ) )
define('FEP_MESSAGES_TABLE',$wpdb->prefix.'fep_messages');

if ( !defined ('FEP_META_TABLE' ) )
define('FEP_META_TABLE',$wpdb->prefix.'fep_meta');

require_once( FEP_PLUGIN_DIR. 'functions.php');

	

	
	

	//ACTIVATE PLUGIN
	register_activation_hook(__FILE__ , 'fep_plugin_activate');
	
	//ADD ACTIONS
	add_action('plugins_loaded', 'fep_translation');
	add_action('wp_enqueue_scripts', 'fep_enqueue_scripts');
	//add_action('admin_enqueue_scripts', 'fep_enqueue_scripts');
	
