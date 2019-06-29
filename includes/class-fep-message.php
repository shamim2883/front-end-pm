<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_Message {

	public $mgs_id = 0;
	public $mgs_parent = 0;
	public $mgs_author = 0;
	public $mgs_created = '0000-00-00 00:00:00';
	public $mgs_title = '';
	public $mgs_content = '';
	public $mgs_type = 'message';
	public $mgs_status = 'publish';
	public $mgs_last_reply_by = 0;
	public $mgs_last_reply_time = '0000-00-00 00:00:00';
	public $mgs_last_reply_excerpt = '';
	
	public function __construct( $obj = '' ) {
		if( is_object( $obj ) ){
			foreach ( get_object_vars( $obj ) as $key => $value ) {
				$this->set( $key, $value );
			}
		}
	}
	
	public function set( $key, $value ){
		if( property_exists( $this, $key ) ){
			$column_formats = $this->get_columns();
			switch( $column_formats[ $key ] ){
				case '%d':
					$value = (int) $value;
					break;
				case '%f':
					$value = (float) $value;
					break;
				case '%s':
				default:
					$value = (string) $value;
					break;
			}
			$this->$key = $value;
		}
	}
	public static function get_instance( $id ) {
		global $wpdb;
 
 		if( ! is_numeric( $id ) ){
			return false;
		}
		
		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}
		$messages = FEP_Cache::init()->update_cache( array( $id ), 'messages' );
 
		$message = isset( $messages[ $id ] ) ? $messages[ $id ] : false;

		return $message;
	}
	
	public static function get_columns() {
		return array(			
			'mgs_id'                => '%d',
			'mgs_parent'            => '%d',
			'mgs_author'            => '%d',
			'mgs_created'           => '%s',
			'mgs_last_reply_time'   => '%s',
			'mgs_title'             => '%s',
			'mgs_content'           => '%s',
			'mgs_type'              => '%s',
			'mgs_status'            => '%s',
			'mgs_last_reply_by'     => '%d',
			'mgs_last_reply_time'   => '%s',
			'mgs_last_reply_excerpt'=> '%s',
		);
	}
	
	public function insert( $data_array = array() ){
		global $wpdb;
		
		if( ! empty( $this->mgs_id ) ){
			return false;
		}
		if( is_array( $data_array ) && $data_array ){
			foreach( $data_array as $k => $v ){
				$this->set( $k, $v );
			}
		}
		$data = get_object_vars( $this );
		$data = apply_filters( 'fep_filter_message_before_insert', $data );
		
		if ( empty( $data['mgs_id'] ) ) {
			unset( $data['mgs_id'] );
		} elseif ( fep_get_message( $data['mgs_id'] ) ) {
			unset( $data['mgs_id'] );
		}
		
		// Initialise column format array
		$column_formats = $this->get_columns();
		
		// White list columns
		$data = array_intersect_key( $data, $column_formats );
		
		if( ! $data ){
			return false;
		}
		
		foreach( $data as $key => &$value ){
			$value = apply_filters( "fep_pre_save_{$key}", $value );
		}
		unset( $value );
		
		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );
				
		$wpdb->insert( FEP_MESSAGE_TABLE, $data, $column_formats );
		$this->mgs_id = $wpdb->insert_id;
		
		if( $this->mgs_id && isset( $data_array['participants'] ) ){
			$this->insert_participants( $data_array['participants'] );
		}
		if( $this->mgs_id && isset( $data_array['attachment'] ) ){
			$this->insert_attachments( $data_array['attachment'] );
		}
		
		return $this->mgs_id;
	}
		
	public function update( $data_array = array() ){
		global $wpdb;
		
		if( is_array( $data_array ) && $data_array ){
			foreach( $data_array as $k => $v ){
				$this->set( $k, $v );
			}
		}
		if( empty( $this->mgs_id ) ){
			return $this->insert( $data_array );
		}
		$original_message = FEP_Message::get_instance( $this->mgs_id );
		$data = array();
		foreach( get_object_vars( $this ) as $k => $v ){
			if( $original_message->$k !== $v ){
				$data[ $k ] = $v;
			}
		}
		$data = apply_filters( 'fep_filter_message_before_update', $data, $this->mgs_id );
		unset( $data['mgs_id'] );
		
		// Initialise column format array
		$column_formats = $this->get_columns();
		
		// White list columns
		$data = array_intersect_key( $data, $column_formats );
		
		if( ! $data ){
			return false;
		}
		
		foreach( $data as $key => &$value ){
			$value = apply_filters( "fep_pre_save_{$key}", $value );
		}
		unset( $value );
		
		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );
				
		if( $wpdb->update( FEP_MESSAGE_TABLE, $data, array( 'mgs_id' => $this->mgs_id ), $column_formats, array( '%d' ) ) ){
			wp_cache_delete( $this->mgs_id, 'fep-message' );
			if( isset( $data['mgs_status'] ) ){
				fep_status_change( $original_message->mgs_status, $this );
			}
			return true;
		}
		return false;
	}
	public function delete(){
		global $wpdb;
		
		if( empty( $this->mgs_id ) ){
			return false;
		}
		FEP_Attachments::init()->delete( $this->mgs_id );
		FEP_Participants::init()->delete( $this->mgs_id );
		fep_delete_meta( $this->mgs_id, '', '', true );
		
		if( $wpdb->delete( FEP_MESSAGE_TABLE, array( 'mgs_id' => $this->mgs_id ), array( '%d' ) ) ){
			wp_cache_delete( $this->mgs_id, 'fep-message' );
			return true;
		}
		return false;
	}
	
	public function get_participants( $participant_id = false, $exclude_deleted = false ){
		return FEP_Participants::init()->get( $this->mgs_id, $participant_id, $exclude_deleted );
	}
	
	public function insert_participants( $participants = array() ){
		return FEP_Participants::init()->insert( $this->mgs_id, $participants );
	}
	
	public function get_attachments( $att_id = false, $att_status = 'publish' ){		
		return FEP_Attachments::init()->get( $this->mgs_id, $att_id, $att_status );
	}
	
	public function insert_attachments( $attachments = array() ){
		return FEP_Attachments::init()->insert( $this->mgs_id, $attachments );
	}
} //END Class

	
