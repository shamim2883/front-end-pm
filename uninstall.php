<?php
/**
 * Uninstall Front End PM
 *
 * Deletes all the plugin data
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

if( ! function_exists( 'fep_get_option' ) ){
	include_once( 'functions.php' );
}

global $wpdb;

if( fep_get_option( 'delete_data_on_uninstall', false ) ) {

	/** Delete All the Custom Post Types of Front End PM */
	$post_types = array( 'fep_message', 'fep_announcement' );
	
	foreach ( $post_types as $post_type ) {

		$items = get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );

		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item, true);
			}
		}
	}

	/** Delete all the Plugin Options */
	delete_option( 'FEP_admin_options' );
	delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . 'FEP_user_options', '', true );
	delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . '_fep_user_message_count', '', true );
	delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . '_fep_user_announcement_count', '', true );
	delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . '_fep_notification_dismiss', '', true );
	
	$roles = array( 'administrator', 'editor' );
	$caps = fep_get_plugin_caps();
	
	foreach( $roles as $role ) {
		$role_obj = get_role( $role );
		if( !$role_obj )
			continue;
			
		foreach( $caps as $cap ) {
			$role_obj->remove_cap( $cap );
		}
	}

	// Remove all database tables of Front End PM (if any)
	$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "fep_messages" );
	$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "fep_meta" );

	// Remove any transients we've left behind
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_fep\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_fep\_%'" );
}
