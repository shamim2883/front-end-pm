<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_Attachments {
	
	private static $instance;
	
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	private function __construct() {
	}
	
	public function get( $mgs_id, $att_id = false, $att_status = 'publish' ){
		global $wpdb;
		$mgs_id = absint( $mgs_id );
		if( ! $mgs_id ){
			return [];
		}
		
		$all_attachments = FEP_Cache::init()->update_cache( array( $mgs_id ), 'attachments' );
		
		$attachments = isset( $all_attachments[ $mgs_id ] ) ? $all_attachments[ $mgs_id ] : array();

		if( ! is_array( $attachments ) ){
			$attachments = array();
		}
		
		foreach ( $attachments as $k => $attachment ) {
			if( $att_status && 'any' != $att_status && $att_status != $attachment->att_status ){
				unset( $attachments[ $k ] );
				continue;
			}
			if( $att_id && $attachment->att_id == $att_id ){
				return $attachment;
			}
		}
		if( $att_id ){
			return array();
		}
		return $attachments;
	}
	
	public function insert( $mgs_id, $attachments = array() ){
		global $wpdb;
		
		if( ! $mgs_id || ! $attachments || ! is_array( $attachments ) ){
			return false;
		}
		$values = array();
		$place_holders = array();
		
		$query = 'INSERT INTO ' . FEP_ATTACHMENT_TABLE . ' (mgs_id, att_mime, att_file, att_status) VALUES ';
		
		foreach( $attachments as $attachment ){
			if( empty( $attachment['att_mime'] ) || empty( $attachment['att_file'] ) ){
				continue;
			}
			$values[] = $mgs_id;
			$values[] = $attachment['att_mime'];
			$values[] = $attachment['att_file'];
			$values[] = empty( $attachment['att_status'] ) ? fep_get_option( 'att_status', 'publish' ) : $attachment['att_status'];
			
			$place_holders[] = '(%d, %s, %s, %s)';
		}
		if( ! $values ){
			return false;
		}
		$query .= implode( ', ', $place_holders );
		$return = $wpdb->query( $wpdb->prepare( $query, $values ) );
		if( $return ){
			wp_cache_delete( $mgs_id, 'fep_attachments' );
		}
		return $return;
	}
	public static function get_columns() {
		return array(			
			'att_id'      => '%d',
			'mgs_id'      => '%d',
			'att_mime'    => '%s',
			'att_file'    => '%s',
			'att_status'  => '%s',	
		);
	}
	
	function update( $mgs_id, $data, $attachment_id = false ){
		global $wpdb;

		if( ! $mgs_id || ! is_array( $data ) || ! $data ){
			return false;
		}
		// Initialise column format array
		$column_formats = $this->get_columns();
		
		// White list columns
		$data = array_intersect_key( $data, $column_formats );
		
		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );
		
		$where = array(
			'mgs_id'          => $mgs_id,
		);
		$where_format = array( '%d' );
		
		if( $attachment_id && is_numeric( $attachment_id ) ){
			$where['att_id'] = $attachment_id;
			$where_format[] = '%d';
		}
		
		if( $wpdb->update( FEP_ATTACHMENT_TABLE, $data, $where, $column_formats, $where_format ) ){
			wp_cache_delete( $mgs_id, 'fep_attachments' );
			return true;
		}
		return false;
	}
	
	function delete( $mgs_id, $attachment_id = false ){
		global $wpdb;

		$attachments = $this->get( $mgs_id, $attachment_id, false );
		if( ! $attachments ){
			return false;
		}
		if( $attachment_id ){
			if( $file = $attachments->att_file ){
				$this->delete_file( $file );
			}
		} else {
			foreach( $attachments as $attachment ){
				if( $file = $attachment->att_file ){
					$this->delete_file( $file );
				}
			}
		}
		$where = array(
			'mgs_id'          => $mgs_id,
		);
		$where_format = array( '%d' );
		
		if( $attachment_id && is_numeric( $attachment_id ) ){
			$where['att_id'] = $attachment_id;
			$where_format[] = '%d';
		}
		
		if( $wpdb->delete( FEP_ATTACHMENT_TABLE, $where, $where_format ) ){
			wp_cache_delete( $mgs_id, 'fep_attachments' );
			return true;
		}
		return false;
	}
	
	function delete_file( $file ){
		// If the file is relative, prepend upload dir.
		$file = Fep_Attachment::init()->absulate_path( $file );
		
		if ( 0 === validate_file( $file ) ) {
			wp_delete_file( $file );
		}
	}
	
} //END Class

	
