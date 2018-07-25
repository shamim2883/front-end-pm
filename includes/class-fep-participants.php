<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_Participants {
	
	private static $instance;
	
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	private function __construct() {
	}
	
	public function get( $mgs_id, $participant_id = false, $exclude_deleted = false ){
		global $wpdb;
		$mgs_id = absint( $mgs_id );
		
		$all_participants = FEP_Cache::init()->update_cache( array( $mgs_id ), 'participants' );
		
		$participants = isset( $all_participants[ $mgs_id ] ) ? $all_participants[ $mgs_id ] : array();

		if( ! is_array( $participants ) ){
			$participants = array();
		}
		
		foreach ( $participants as $k => $participant ) {
			if( $exclude_deleted && ! empty( $participant->mgs_deleted ) ){
				unset( $participants[ $k ] );
				continue;
			}
			if( $participant_id && $participant->mgs_participant == $participant_id ){
				return $participant;
			}
		}
		if( $participant_id ){
			return array();
		}
		return $participants;
	}
	
	public function insert( $mgs_id, $participants = array() ){
		global $wpdb;
		
		if( ! $mgs_id || ! $participants || ! is_array( $participants ) ){
			return false;
		}
		$values = array();
		$place_holders = array();
		$is_flat_array = ! is_array( $participants[0] );
		
		if( $is_flat_array ){
			//eg $participants = array( 2, 4, 344 );
			$participants = array_unique( $participants );
			$query = 'INSERT INTO ' . FEP_PARTICIPANT_TABLE . ' (mgs_id, mgs_participant) VALUES ';
		} else {
			$query = 'INSERT INTO ' . FEP_PARTICIPANT_TABLE . ' (mgs_id, mgs_participant, mgs_read, mgs_parent_read, mgs_deleted, mgs_archived) VALUES ';
		}
		
		foreach( $participants as $participant ){
			if( $is_flat_array ){
				if( $this->get( $mgs_id, $participant ) ){
					continue;
				}
				$values[] = $mgs_id;
				$values[] = $participant;
				
				$place_holders[] = '(%d, %d)';
			} else {
				if( empty( $participant['mgs_participant'] ) || $this->get( $mgs_id, $participant['mgs_participant'] ) ){
					continue;
				}
				$values[] = $mgs_id;
				$values[] = empty( $participant['mgs_participant'] ) ? 0 : $participant['mgs_participant'];
				$values[] = empty( $participant['mgs_read'] ) ? 0 : $participant['mgs_read'];
				$values[] = empty( $participant['mgs_parent_read'] ) ? 0 : $participant['mgs_parent_read'];
				$values[] = empty( $participant['mgs_deleted'] ) ? 0 : $participant['mgs_deleted'];
				$values[] = empty( $participant['mgs_archived'] ) ? 0 : $participant['mgs_archived'];
				
				$place_holders[] = '(%d, %d, %d, %d, %d, %d)';
			}
		}
		if( ! $values ){
			return false;
		}
		$query .= implode( ', ', $place_holders );
		$return = $wpdb->query( $wpdb->prepare( $query, $values ) );
		if( $return ){
			wp_cache_delete( $mgs_id, 'fep_participants' );
		}
		return $return;
	}
	
	function mark( $mgs_id, $participant_id, $args = [] ){
		global $wpdb;

		if( ! $mgs_id || ! $participant_id ){
			return false;
		}
		$args = wp_parse_args( $args, [
			'read'        => false,
			'parent_read' => false,
			'delete'      => false,
			'archive'     => false,
		] );
		
		$data = array();
		$format = array();
		
		$participant = $this->get( $mgs_id, $participant_id );
		if( ! $participant ){
			return false;
		}
		
		if( $args['read'] && empty( $participant->mgs_read ) ){
			if( is_numeric( $args['read'] ) ){
				$data['mgs_read'] = $args['read'];
			} else {
				$data['mgs_read'] = current_time( 'timestamp', true );
			}
			$format[] = '%d';
		}
		if( $args['parent_read'] && empty( $participant->mgs_parent_read ) ){
			if( is_numeric( $args['parent_read'] ) ){
				$data['mgs_parent_read'] = $args['parent_read'];
			} else {
				$data['mgs_parent_read'] = current_time( 'timestamp', true );
			}
			$format[] = '%d';
		}
		if( $args['delete'] && empty( $participant->mgs_deleted ) ){
			if( is_numeric( $args['delete'] ) ){
				$data['mgs_deleted'] = $args['delete'];
			} else {
				$data['mgs_deleted'] = current_time( 'timestamp', true );
			}
			$format[] = '%d';
		}
		if( $args['archive'] && empty( $participant->mgs_archived ) ){
			if( is_numeric( $args['archive'] ) ){
				$data['mgs_archived'] = $args['archive'];
			} else {
				$data['mgs_archived'] = current_time( 'timestamp', true );
			}
			$format[] = '%d';
		}
		if( $data ){
			$where = array(
				'mgs_id'          => $mgs_id,
				'mgs_participant' => $participant_id,
			);
			$where_format = array( '%d', '%d' );
			if( $wpdb->update( FEP_PARTICIPANT_TABLE, $data, $where, $format, $where_format ) ){
				wp_cache_delete( $mgs_id, 'fep_participants' );
				return true;
			}
		}
		return false;
	}
	
	function unmark( $mgs_id, $participant_id = false, $args = [] ){
		global $wpdb;

		if( ! $mgs_id ){
			return false;
		}
		$args = wp_parse_args( $args, [
			'read'        => false,
			'parent_read' => false,
			'delete'      => false,
			'archive'     => false,
		] );
		
		$data = array();
		$format = array();

		if( $participant_id && is_numeric( $participant_id ) ){
			$participant = $this->get( $mgs_id, $participant_id );
			if( ! $participant ){
				return false;
			}
			
			if( $args['read'] && ! empty( $participant->mgs_read ) ){
				$data['mgs_read'] = 0;
				$format[] = '%d';
			}
			if( $args['parent_read'] && ! empty( $participant->mgs_parent_read ) ){
				$data['mgs_parent_read'] = 0;
				$format[] = '%d';
			}
			if( $args['delete'] && ! empty( $participant->mgs_deleted ) ){
				$data['mgs_deleted'] = 0;
				$format[] = '%d';
			}
			if( $args['archive'] && ! empty( $participant->mgs_archived ) ){
				$data['mgs_archived'] = 0;
				$format[] = '%d';
			}
		} else {
			if( $args['read'] ){
				$data['mgs_read'] = 0;
				$format[] = '%d';
			}
			if( $args['parent_read'] ){
				$data['mgs_parent_read'] = 0;
				$format[] = '%d';
			}
			if( $args['delete'] ){
				$data['mgs_deleted'] = 0;
				$format[] = '%d';
			}
			if( $args['archive'] ){
				$data['mgs_archived'] = 0;
				$format[] = '%d';
			}
		}
		
		if( $data ){
			$where = array(
				'mgs_id'          => $mgs_id,
			);
			$where_format = array( '%d' );
			
			if( $participant_id && is_numeric( $participant_id ) ){
				$where['mgs_participant'] = $participant_id;
				$where_format[] = '%d';
			}
			if( $wpdb->update( FEP_PARTICIPANT_TABLE, $data, $where, $format, $where_format ) ){
				wp_cache_delete( $mgs_id, 'fep_participants' );
				return true;
			}
		}
		return false;
	}
	function delete( $mgs_id, $participant_id = false ){
		global $wpdb;

		if( ! $mgs_id ){
			return false;
		}
		$participants = $this->get( $mgs_id, $participant_id );
		if( ! $participants ){
			return false;
		}
		$type = fep_get_message_field( 'mgs_type', $mgs_id );
		if( $participant_id ){
			if( $participants->mgs_participant ){
				if( 'message' == $type ){
					delete_user_meta( $participants->mgs_participant, '_fep_user_message_count' );
				} elseif( 'announcement' == $type ){
					delete_user_meta( $participants->mgs_participant, '_fep_user_announcement_count' );
				}
			}
		} else {
			foreach( $participants as $participant ){
				if( $participant->mgs_participant ){
					if( 'message' == $type ){
						delete_user_meta( $participant->mgs_participant, '_fep_user_message_count' );
					} elseif( 'announcement' == $type ){
						delete_user_meta( $participant->mgs_participant, '_fep_user_announcement_count' );
					}
				}
			}
		}
		$where = array(
			'mgs_id'          => $mgs_id,
		);
		$where_format = array( '%d' );
		
		if( $participant_id && is_numeric( $participant_id ) ){
			$where['mgs_participant'] = $participant_id;
			$where_format[] = '%d';
		}
		if( $wpdb->delete( FEP_PARTICIPANT_TABLE, $where, $where_format ) ){
			wp_cache_delete( $mgs_id, 'fep_participants' );
			return true;
		}
		return false;
	}
	
} //END Class

	
