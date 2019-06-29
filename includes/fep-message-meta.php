<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function fep_get_meta( $message_id, $meta_key = '', $single = false ) {
	FEP_Cache::init()->update_cache( array( $message_id ), 'meta' );
	
	return get_metadata( 'fep_message', $message_id, $meta_key, $single );
}

function fep_add_meta( $message_id, $meta_key = '', $meta_value = '', $unique = false ) {
	return add_metadata( 'fep_message', $message_id, $meta_key, $meta_value, $unique );
}

function fep_update_meta( $message_id, $meta_key = '', $meta_value = '', $prev_value = '' ) {
	return update_metadata( 'fep_message', $message_id, $meta_key, $meta_value, $prev_value );
}

function fep_delete_meta( $message_id, $meta_key = '', $meta_value = '', $delete_all = false ) {
	global $wpdb;
	
	if ( ! $message_id ) {
		return false;
	}
	if ( $meta_key ) {
		return delete_metadata( 'fep_message', $message_id, $meta_key, $meta_value, $delete_all );
	} elseif ( $delete_all ) {
		if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->fep_messagemeta WHERE fep_message_id = %d", $message_id ) ) ) {
			wp_cache_delete( $message_id, 'fep_message_meta' );
			return true;
		} else {
			return false;
		}
	}
}
