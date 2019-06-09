<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_Cache {
	
	private static $instance;
	
	private $queue = array();
	
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	private function __construct() {
	}
	
	public function add_to_queue( $ids, $type ){
		$ids = (array) $ids;
		$type = (string) $type;
		
		if( ! $ids || ! is_array( $ids ) || ! $type ){
			return false;
		}
		if( ! isset( $this->queue[ $type ] ) || ! is_array( $this->queue[ $type ] ) ){
			$this->queue[ $type ] = array();
		}
		$this->queue[ $type ] = array_merge( $this->queue[ $type ], $ids );
	}
	
	public function update_cache( $provided_ids, $type ){
		$type = (string) $type;
		
		if( ! is_array( $provided_ids ) || ! $type ){
			return array();
		}
		if( ! isset( $this->queue[ $type ] ) || ! is_array( $this->queue[ $type ] ) ){
			$this->queue[ $type ] = array();
		}
		$ids = array_merge( $this->queue[ $type ], $provided_ids );
		$this->queue[ $type ] = array();
		
		$ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );
		if( ! $ids ){
			return array();
		}
		
		$callback = apply_filters( 'fep_update_cache_callback', array( $this, "update_cache_{$type}" ), $type );
		
		return $callback( $ids, $provided_ids );
	}
	
	function update_cache_messages( $message_ids, $provided_ids ){
		global $wpdb;
		
		$need_query = array();
		$return = array();
		
		foreach( $message_ids as $message_id ){
			$cached = wp_cache_get( $message_id, 'fep-message' );
			if( false === $cached ){
				$need_query[] = $message_id;
			} elseif( in_array( $message_id, $provided_ids ) ) {
				$return[ $message_id ] = new FEP_Message( $cached );
			}
		}
		
		if( $need_query ){
			$messages = $wpdb->get_results( 'SELECT * FROM ' . FEP_MESSAGE_TABLE . ' WHERE mgs_id IN (' . implode( ', ', $need_query ) . ')' );
			
			foreach ( $messages as $key => $message ) {
				$message_id = (int) $message->mgs_id;
				wp_cache_add( $message_id, $message, 'fep-message' );
				if( in_array( $message_id, $provided_ids ) ) {
					$return[ $message_id ] = new FEP_Message( $message );
				}
			}
		}
		return $return;
	}
	
	function update_cache_meta( $message_ids, $provided_ids ){
		return update_meta_cache( 'fep_message', $message_ids );
	}
	
	function update_cache_user( $user_ids, $provided_ids ){
		cache_users( $user_ids );
	}
	
	function update_cache_participants( $message_ids, $provided_ids ){
		global $wpdb;
		
		$need_query = array();
		$return = array();
		
		foreach( $message_ids as $message_id ){
			$cached = wp_cache_get( $message_id, 'fep_participants' );
			if( false === $cached ){
				$need_query[] = $message_id;
			} elseif( in_array( $message_id, $provided_ids ) ) {
				$return[ $message_id ] = $cached;
			}
		}
		
		if( $need_query ){
			$participants = array();
			
			$results = $wpdb->get_results( 'SELECT * FROM ' . FEP_PARTICIPANT_TABLE . ' WHERE mgs_id IN (' . implode( ', ', $need_query ) . ')' );
			
			foreach ( $results as $key => $result ) {
				$participants[ (int) $result->mgs_id ][] = $result;
			}
			foreach( $need_query as $id ){
				if( ! isset( $participants[ $id ] ) ){
					$participants[ $id ] = array();
				}
			}
			
			foreach( $participants as $mgs_id => $participant ){
				wp_cache_add( $mgs_id, $participant, 'fep_participants' );
				
				if( in_array( $mgs_id, $provided_ids ) ){
					$return[ $mgs_id ] = $participant;
				}
			}
		}
		return $return;
	}
	
	function update_cache_attachments( $message_ids, $provided_ids ){
		global $wpdb;
		
		$need_query = array();
		$return = array();
		
		foreach( $message_ids as $message_id ){
			$cached = wp_cache_get( $message_id, 'fep_attachments' );
			if( false === $cached ){
				$need_query[] = $message_id;
			} elseif( in_array( $message_id, $provided_ids ) ) {
				$return[ $message_id ] = $cached;
			}
		}
		
		if( $need_query ){
			$attachments = array();
			
			$results = $wpdb->get_results( 'SELECT * FROM ' . FEP_ATTACHMENT_TABLE . ' WHERE mgs_id IN (' . implode( ', ', $need_query ) . ')' );
			
			foreach ( $results as $key => $result ) {
				$attachments[ (int) $result->mgs_id ][] = $result;
			}
			foreach( $need_query as $id ){
				if( ! isset( $attachments[ $id ] ) ){
					$attachments[ $id ] = array();
				}
			}
			
			foreach( $attachments as $mgs_id => $attachment ){
				wp_cache_add( $mgs_id, $attachment, 'fep_attachments' );
				
				if( in_array( $mgs_id, $provided_ids ) ){
					$return[ $mgs_id ] = $attachment;
				}
			}
		}
		return $return;
	}
	
} //END Class

	
