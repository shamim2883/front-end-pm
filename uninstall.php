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

$fep_options = get_option( 'FEP_admin_options' );

global $wpdb;
if ( is_array( $fep_options ) && ! empty( $fep_options['delete_data_on_uninstall'] ) ) {

	/** Delete all the Plugin Options */
	delete_option( 'FEP_admin_options' );
	delete_option( 'fep_updated_versions' );
	delete_site_option( 'fep_db_version' );
	
	delete_metadata( 'user', 0, 'FEP_user_options', '', true );
	delete_metadata( 'user', 0, '_fep_user_message_count', '', true );
	delete_metadata( 'user', 0, '_fep_user_announcement_count', '', true );
	delete_metadata( 'user', 0, '_fep_notification_dismiss', '', true );

	// Remove all database tables of Front End PM (if any).
	$fep_tables = [
		'message'      => defined( 'FEP_MESSAGE_TABLE' ) ? FEP_MESSAGE_TABLE : $wpdb->base_prefix . 'fep_messages',
		'meta'         => defined( 'FEP_META_TABLE' ) ? FEP_META_TABLE : $wpdb->base_prefix . 'fep_messagemeta',
		'participants' => defined( 'FEP_PARTICIPANT_TABLE' ) ? FEP_PARTICIPANT_TABLE : $wpdb->base_prefix . 'fep_participants',
		'attachments'  => defined( 'FEP_ATTACHMENT_TABLE' ) ? FEP_ATTACHMENT_TABLE : $wpdb->base_prefix . 'fep_attachments',
	];
	foreach ( $fep_tables as $fep_table ) {
		$wpdb->query( "DROP TABLE IF EXISTS $fep_table" );
	}

	function fep_recursive_remove_directory( $directory ) {
		foreach ( glob( "{$directory}/*" ) as $file ) {
			if ( is_dir( $file ) ) {
				fep_recursive_remove_directory( $file );
			} else {
				unlink( $file );
			}
		}
		rmdir( $directory );
	}
	// Need to improve delete attachments files for multisite.
	if( ! is_multisite() ){
		$wp_upload_dir = wp_upload_dir();
		if ( $wp_upload_dir && false === $wp_upload_dir['error'] ) {
			fep_recursive_remove_directory( $wp_upload_dir['basedir'] . '/front-end-pm' );
		}
	}

	// Remove any transients we've left behind
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_fep\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_fep\_%'" );
}
