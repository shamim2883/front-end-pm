<?php
/*
Plugin Name: Front End PM
Plugin URI: https://shamimbiplob.wordpress.com/contact-us/
Description: Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system fromfront end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.
Version: 3.3.1
Author: Shamim
Author URI: https://shamimbiplob.wordpress.com/contact-us/
Text Domain: front-end-pm
License: GPLv2 or later
*/
//DEFINE
global $wpdb;
define('FEP_PLUGIN_DIR',plugin_dir_path( __FILE__ ));
define('FEP_PLUGIN_URL',plugins_url().'/front-end-pm/');

require_once( FEP_PLUGIN_DIR. 'functions.php');

	
function fep_include_require_files() 
	{

	$fep_files = array(
			'announcement' 	=> FEP_PLUGIN_DIR. 'class-fep-announcement.php',
			'attachment' 	=> FEP_PLUGIN_DIR. 'class-fep-attachment.php',
			'cpt' 			=> FEP_PLUGIN_DIR. 'class-fep-cpt.php',
			'directory' 	=> FEP_PLUGIN_DIR. 'class-fep-directory.php',
			'email' 		=> FEP_PLUGIN_DIR. 'class-fep-emails.php',
			'form' 			=> FEP_PLUGIN_DIR. 'class-fep-form.php',
			'menu' 			=> FEP_PLUGIN_DIR. 'class-fep-menu.php',
			'message' 		=> FEP_PLUGIN_DIR. 'class-fep-message.php',
			'main' 			=> FEP_PLUGIN_DIR. 'fep-class.php',
			'widgets' 		=> FEP_PLUGIN_DIR. 'fep-widgets.php'
			);
	
	if( is_admin() ) {
		$fep_files['settings'] 	= FEP_PLUGIN_DIR. 'admin/class-fep-admin-settings.php';
	}			
					
	$fep_files = apply_filters('fep_include_files', $fep_files );
	
	foreach ( $fep_files as $fep_file ) {
		require_once ( $fep_file );
		}
	}

	//ACTIVATE PLUGIN
	register_activation_hook(__FILE__ , 'fep_plugin_activate');
	
	//ADD ACTIONS
	add_action('after_setup_theme', 'fep_include_require_files');
	add_action('plugins_loaded', 'fep_translation');
	add_action('wp_enqueue_scripts', 'fep_enqueue_scripts');
	add_action('admin_enqueue_scripts', 'fep_enqueue_scripts');
	
