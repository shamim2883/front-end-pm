<?php
/**
 *	Uninstall Front End PM
 *
 *	Deletes all the plugin data
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! function_exists( 'fep_get_option' ) ) {
	include_once( 'functions.php' );
}

global $wpdb;
if ( fep_get_option( 'delete_data_on_uninstall', false ) ) {

	/** Delete all the Plugin Options */
	delete_option( 'FEP_admin_options' );
	delete_option( 'fep_updated_versions' );
	
	delete_metadata( 'user', 0, 'FEP_user_options', '', true );
	delete_metadata( 'user', 0, '_fep_user_message_count', '', true );
	delete_metadata( 'user', 0, '_fep_user_announcement_count', '', true );
	delete_metadata( 'user', 0, '_fep_notification_dismiss', '', true );

	// Remove all database tables of Front End PM (if any)
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'fep_messages' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'fep_messagemeta' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'fep_participants' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . 'fep_attachments' );

	// Remove any transients we've left behind
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_fep\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_fep\_%'" );
}
