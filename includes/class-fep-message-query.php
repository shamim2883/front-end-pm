<?php
/**
 * Messages and Announcements Query
 *
 * @package Front End PM
 * @since 10.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_Message_Query {
	
	public $message_table = FEP_MESSAGE_TABLE;
	public $participant_table = FEP_PARTICIPANT_TABLE;
	
	public $query = '';
	public $args;
	public $query_fields = '';
	public $join = '';
	public $query_where = '';
	public $order = '';
	public $groupby = '';
	public $limit = '';
	
	public $meta_query = false;
	
	public $messages;
	public $found_messages = 0;
	public $total_messages = 0;
	public $current_message = -1;
	public $in_the_loop = false;
	
	private $has_id_column = false;
	public  $has_more_row  = false;
	
	public function __construct( $args = array() ) {
		$this->args = wp_parse_args( $args, array(
			'mgs_id'	=> 0,
			'mgs_id_in' => array(),
			'mgs_id_not_in' => array(),
			'mgs_parent' => false,
			'mgs_parent_in' => array(),
			'mgs_parent_not_in' => array(),
			'include_child' => false,
			//'full_thread' => true,
			'mgs_author' => false,
			'mgs_author_in' => array(),
			'mgs_author_not_in' => array(),
			'mgs_last_reply_by' => false,
			'mgs_last_reply_by_in' => array(),
			'mgs_last_reply_by_not_in' => array(),
			'mgs_type' => 'message',
			'mgs_type_in' => array(),
			'mgs_type_not_in' => array(),
			'mgs_status' => 'publish',
			'mgs_status_in' => array(),
			'mgs_status_not_in' => array(),
			'created_before' => '',
			'created_after' => '',
			'created_between' => array(),
			'last_reply_time_before' => '',
			'last_reply_time_after' => '',
			'last_reply_time_between' => array(),
			'fields' => 'all', //all, ids, array of field(s)
			'count_total' => true,
			'check_more_row' => false,
			'queue_participants_cache' => true,
			'queue_attachments_cache' => true,
			'queue_meta_cache' => true,
			'participant_query' => array(),
			'orderby' => 'mgs_created',
			'order' => 'DESC',
			'per_page' => 15,
			'paged' => 1,
			
		) );
		
		$this->parse_limit();
		$this->parse_fields();
		$this->parse_where();
		$this->parse_order();
		
		$this->query();
	}
	
	public function parse_fields(){
		if ( 'COUNT(*)' === $this->args['fields'] ) {
			$this->query_fields .= 'COUNT(*)';
			return;
		}
		
		if( ! empty( $this->args['count_total'] ) && $this->limit ){
			$this->query_fields .= 'SQL_CALC_FOUND_ROWS ';
		}
		if( 'ids' === $this->args['fields'] ){
			$this->args['fields'] = array( 'mgs_id' );
		}
		
		if( is_array( $this->args['fields'] ) ){
			$this->args['fields'] = array_intersect( $this->args['fields'], array_keys( FEP_Message::get_columns() ) );

			if( in_array( 'mgs_id', $this->args['fields'] ) ){
				$this->has_id_column = true;
			}
			if( ! $this->args['fields'] ){
				$this->query_fields .= "{$this->message_table}.*";
			} else {
				$this->query_fields .= $this->message_table . '.' . implode( ", {$this->message_table}.", $this->args['fields'] );
			}
		} else {
			$this->query_fields .= "{$this->message_table}.*";
		}
	}
	public function parse_where(){
		
		$this->query_where = '1=1';
		
		foreach( $this->args as $key => $value ){
			switch ( $key ) {
				case 'mgs_id':
					if( $value && is_numeric( $value ) ){
						if( ! empty( $this->args['include_child'] ) ){
							$this->query_where .= sprintf(" AND ( {$this->message_table}.{$key} = %d OR {$this->message_table}.mgs_parent = %d )", $value, $value );
						} else {
							$this->query_where .= sprintf(" AND {$this->message_table}.{$key} = %d", $value );
						}
					}
					break;
				case 'mgs_parent':
				case 'mgs_author':
				case 'mgs_last_reply_by':
					if( is_numeric( $value ) ){
						$this->query_where .= " AND {$this->message_table}.{$key} = " . absint( $value );
					}
					break;
				case 'mgs_type':
				case 'mgs_status':
					if( $value && 'any' != $value ){
						$this->query_where .= " AND {$this->message_table}.{$key} = '" . sanitize_key( $value ) . "'";
					}
					break;
				case 'mgs_id_in':
					if( $value ){
						$key = substr( $key, 0, -3 );
						$value = implode( ', ', array_map( 'absint', $value ) );
						
						if( ! empty( $this->args['include_child'] ) ){
							$this->query_where .= " AND ( {$this->message_table}.{$key} IN ( {$value} ) OR {$this->message_table}.mgs_parent IN ( {$value} ) )";
						} else {
							$this->query_where .= " AND {$this->message_table}.{$key} IN ( {$value} )";
						}
					}
					break;
				case 'mgs_parent_in':
				case 'mgs_author_in':
				case 'mgs_last_reply_by_in':
					if( $value ){
						$key = substr( $key, 0, -3 );
						$this->query_where .= " AND {$this->message_table}.{$key} IN (" . implode( ',', array_map( 'absint', $value ) ) . ")";
					}
					break;
				case 'mgs_type_in':
				case 'mgs_status_in':
					if( $value ){
						$key = substr( $key, 0, -3 );
						$this->query_where .= " AND {$this->message_table}.{$key} IN ('" . implode( "', '", array_map( 'sanitize_key', $value ) ) . "')";
					}
					break;
				case 'mgs_id_not_in':
				case 'mgs_parent_not_in':
				case 'mgs_author_not_in':
				case 'mgs_last_reply_by_not_in':
					if( $value ){
						$key = substr( $key, 0, -7 );
						$this->query_where .= " AND {$this->message_table}.{$key} NOT IN (" . implode( ',', array_map( 'absint', $value ) ) . ")";
					}
					break;
				case 'mgs_type_not_in':
				case 'mgs_status_not_in':
					if( $value ){
						$key = substr( $key, 0, -7 );
						$this->query_where .= " AND {$this->message_table}.{$key} NOT IN ('" . implode( "', '", array_map( 'sanitize_key', $value ) ) . "')";
					}
					break;
				case 'created_before':
				case 'last_reply_time_before':
					if( $value && $this->verifyDate( $value ) ){
						$key = substr( $key, 0, -7 );
						$this->query_where .= " AND {$this->message_table}.mgs_{$key} < '$value'";
					}
					break;
				case 'created_after':
				case 'last_reply_time_after':
					if( $value && $this->verifyDate( $value ) ){
						$key = substr( $key, 0, -6 );
						$this->query_where .= " AND {$this->message_table}.mgs_{$key} > '$value'";
					}
					break;
				case 'created_between':
				case 'last_reply_time_between':
					if( $value && is_array( $value ) && $this->verifyDate( $value[0] ) && $this->verifyDate( $value[1] ) ){
						$key = substr( $key, 0, -8 );
						$this->query_where .= sprintf(" AND ({$this->message_table}.mgs_{$key} BETWEEN '%s' AND '%s')", $value[0], $value[1] );
					}
					break;
				case 'search':
				case 's':
					if( $value ){
						$this->search_query( $value );
					}
					break;
				
				case 'participant_query':
					if( $value && is_array( $value ) ){
						$this->participant_query( $value );
					}
					break;
				case 'meta_query':
					if( $value && is_array( $value ) ){
						$this->meta_query();
					}
					break;
				
				default:
					break;
			}
		}
	}
	
	function participant_query( $value ){
		if( ! $value || ! is_array( $value ) ){
			return;
		}
			
		$relation = 'AND';
		$add_relation = false;
		$where = '';
		$i = 0;
		
		foreach ( $value as $k => $v ) {
			if( 'relation' === $k ){
				if( in_array( $v, array( 'AND', 'OR' ) ) ){
					$relation = $v;
				}
				continue;
			}
			$and = '';
			$where2 = '';
			$alias = $i ? "pt{$i}" : $this->participant_table;
			
			if( is_array( $v ) ){
				foreach( $v as $x => $y ){
					switch ( $x ) {
						case 'mgs_participant':
							$where2 .= sprintf("{$and}{$alias}.{$x} = %d", absint( $y ) );
							$and = ' AND ';
							break;
						case 'mgs_read':
						case 'mgs_parent_read':
						case 'mgs_deleted':
						case 'mgs_archived':
							if( $y ){
								$where2 .= "{$and}{$alias}.{$x} != 0";
							} else {
								$where2 .= "{$and}{$alias}.{$x} = 0";
							}
							$and = ' AND ';
							break;
						default:
							break;
					}
				}
			}
			if( $where2 ){
				$this->join .= " INNER JOIN $this->participant_table";
				$this->join .= $i ? " AS {$alias}" : '';
				$this->join .= " ON ( {$this->message_table}.mgs_id = {$alias}.mgs_id )";
				
				$where .= sprintf("%s({$where2})", $add_relation ? " {$relation} " : '' );
				$where2 = '';
				
				$add_relation = true;
			}
			$i++;
		}
		if( $where ){

			if ( $i > 1 ) {
				$this->groupby = " GROUP BY {$this->message_table}.mgs_id";
			}
			$this->query_where .= " AND ({$where})";
		}
	}
	
	public function meta_query(){
		// Parse meta query
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->args );
		
		if ( ! empty( $this->meta_query->queries ) ) {
			$this->groupby = " GROUP BY {$this->message_table}.mgs_id";
		}
		
		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( 'fep_message', $this->message_table, 'mgs_id', $this );
			$this->join   .= $clauses['join'];
			$this->query_where  .= $clauses['where'];
		}
	}
	
	public function search_query( $value ){
		global $wpdb;
		
		if ( empty( $value ) || ! is_scalar( $value ) || strlen( $value ) > 1600 ) {
			return false;
		}
		$value = stripslashes( $value );
		$value = urldecode( $value );
		$value = str_replace( array( "\r", "\n" ), '', $value );
		
		$terms_count = 1;
		$search_terms = [];
		
		if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $value, $matches ) ) {
			$terms_count = count( $matches[0] );
			$search_terms = $this->parse_search_terms( $matches[0] );
			// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
			if ( empty( $search_terms ) || count( $search_terms) > 9 )
			$search_terms = array( $value );
		} else {
			$search_terms = array( $value );
		}
		$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' );
		$searchand = '';
		$search = '';
		
		foreach ( $search_terms as $term ) {
			$exclude = $exclusion_prefix && ( $exclusion_prefix === substr( $term, 0, 1 ) );
			if ( $exclude ) {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			} else {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			}
			
			$like = '%' . $wpdb->esc_like( $term ) . '%';
			$search .= $wpdb->prepare( "{$searchand}(({$this->message_table}.mgs_title $like_op %s) $andor_op ({$this->message_table}.mgs_last_reply_excerpt $like_op %s) $andor_op ({$this->message_table}.mgs_content $like_op %s))", $like, $like, $like );
			$searchand = ' AND ';
		}
		if ( ! empty( $search ) ) {
			$this->query_where .= " AND ({$search})";
		}
	}
	
	protected function parse_search_terms( $terms ) {
		$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
		$checked = array();
	 
		$stopwords = $this->get_search_stopwords();
	 
		foreach ( $terms as $term ) {
			// keep before/after spaces when term is for exact match
			if ( preg_match( '/^".+"$/', $term ) )
				$term = trim( $term, "\"'" );
			else
				$term = trim( $term, "\"' " );
	 
			// Avoid single A-Z and single dashes.
			if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) )
				continue;
	 
			if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) )
				continue;
	 
			$checked[] = $term;
		}
	 
		return $checked;
	}
	
	protected function get_search_stopwords() {
		if ( isset( $this->stopwords ) )
			return $this->stopwords;
	 
		/* translators: This is a comma-separated list of very common words that should be excluded from a search,
		 * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
		 * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		 */
		$words = explode( ',', _x( 'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
			'Comma-separated list of search stopwords in your language' ) );
	 
		$stopwords = array();
		foreach ( $words as $word ) {
			$word = trim( $word, "\r\n\t " );
			if ( $word )
				$stopwords[] = $word;
		}
	 
		$this->stopwords = apply_filters( 'wp_search_stopwords', $stopwords );
		return $this->stopwords;
	}
	
	public function parse_order(){
		if ( empty( $this->args['orderby'] ) ) {
			return;
		}
		if( in_array( $this->args['orderby'], array_keys( FEP_Message::get_columns() ) ) ){
			$orderby = $this->message_table .'.'. $this->args['orderby'];
		} else {
			$orderby = "{$this->message_table}.mgs_created";
		}
		if( isset( $this->args['order'] ) && 'ASC' == strtoupper( $this->args['order'] ) ){
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}
		$this->order = " ORDER BY $orderby $order";
	}
	
	public function parse_limit(){
		if( ! empty( $this->args['per_page'] ) && is_numeric( $this->args['per_page'] ) && -1 !== $this->args['per_page'] ){
			$per_page = absint( $this->args['per_page'] );
			$paged = empty( $this->args['paged'] ) ? 1 : absint( $this->args['paged'] );
			$limit = $per_page;
			if ( ! empty( $this->args['check_more_row'] ) ) {
				$limit += 1;
			}
			
			$this->limit = sprintf(' LIMIT %d, %d', (($paged - 1) * $per_page), $limit );
		}
	}
	
	public function query(){
		global $wpdb;
		
		$query = "SELECT $this->query_fields FROM {$this->message_table}{$this->join} WHERE {$this->query_where}{$this->groupby}{$this->order}{$this->limit}";
		$query = apply_filters( 'fep_filter_message_query_sql', $query, $this );
		
		if ( 'COUNT(*)' === $this->args['fields'] ) {
			$this->messages       = (int) $wpdb->get_var( $query );
			$this->found_messages = $this->messages;
			$this->total_messages = $this->messages;
			return $this->messages;
		}
		
		if( is_array( $this->args['fields'] ) && $this->args['fields'] ){
			if( count( $this->args['fields'] ) === 1 ){
				$this->messages = $wpdb->get_col( $query );
				if( $this->has_id_column && ! empty( $this->args['queue_participants_cache'] ) ){
					FEP_Cache::init()->add_to_queue( $this->messages, 'participants' );
				}
				if( $this->has_id_column && ! empty( $this->args['queue_attachments_cache'] ) ){
					FEP_Cache::init()->add_to_queue( $this->messages, 'attachments' );
				}
				if( $this->has_id_column && ! empty( $this->args['queue_meta_cache'] ) ){
					FEP_Cache::init()->add_to_queue( $this->messages, 'meta' );
				}
			} else {
				$this->messages = $wpdb->get_results( $query );
				foreach ( $this->messages as $key => &$message) {
					$message = new FEP_Message( $message );
					if( $this->has_id_column && ! empty( $this->args['queue_participants_cache'] ) ){
						FEP_Cache::init()->add_to_queue( $message->mgs_id, 'participants' );
					}
					if( $this->has_id_column && ! empty( $this->args['queue_attachments_cache'] ) ){
						FEP_Cache::init()->add_to_queue( $message->mgs_id, 'attachments' );
					}
					if( $this->has_id_column && ! empty( $this->args['queue_meta_cache'] ) ){
						FEP_Cache::init()->add_to_queue( $message->mgs_id, 'meta' );
					}
				}
				unset( $message );
			}
		} else {
			$this->messages = $wpdb->get_results( $query );
			//$this->messages = array_map( '', $this->messages);
			foreach ( $this->messages as $key => &$message) {
				$message_raw = $message;
				$message = new FEP_Message( $message );
				wp_cache_add( $message->mgs_id, $message_raw, 'fep-message' );
				if( ! empty( $this->args['queue_participants_cache'] ) ){
					FEP_Cache::init()->add_to_queue( $message->mgs_id, 'participants' );
				}
				if( ! empty( $this->args['queue_attachments_cache'] ) ){
					FEP_Cache::init()->add_to_queue( $message->mgs_id, 'attachments' );
				}
				if( ! empty( $this->args['queue_meta_cache'] ) ){
					FEP_Cache::init()->add_to_queue( $message->mgs_id, 'meta' );
				}
			}
			unset( $message );
		}
		if ( ! empty( $this->args['check_more_row'] ) ) {
			if ( ! empty( $this->args['per_page'] ) && is_numeric( $this->args['per_page'] ) && -1 !== $this->args['per_page'] && count( $this->messages ) > absint( $this->args['per_page'] ) ) {
				array_pop( $this->messages );
				$this->has_more_row = true;
			}
		}
		$this->found_messages = count( $this->messages );
		if( ! empty( $this->args['count_total'] ) ){
			if( $this->limit ){
				$this->total_messages = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
			} else {
				$this->total_messages = $this->found_messages;
			}
		}
		return $this->messages;
	}
	
	public function get_results(){
		return $this->messages;
	}
	
	public function have_messages(){
		if ( $this->current_message + 1 < $this->found_messages ) {
			return true;
		} elseif( $this->current_message + 1 == $this->found_messages && $this->found_messages > 0 ){
			do_action('fep_loop_end', $this );
			$this->rewind_messages();
		} elseif( 0 === $this->found_messages ) {
			do_action( 'fep_loop_no_results', $this );
		}
		$this->in_the_loop = false;
		return false;
	}
	
	public function the_message(){
		global $fep_message;
		$this->in_the_loop = true;
		if ( $this->current_message == -1 ){
			do_action( 'fep_loop_start', $this );
		}
		$fep_message = $this->next_message();
	}
	
	public function next_message(){
		$this->current_message++;
		return $this->messages[ $this->current_message ];
	}
	
	public function rewind_messages(){
		//global $fep_message;
		//$fep_message = null;
		
		$this->current_message = -1;
	}
	
	public function verifyDate( $date, $strict = true ){
		if( ! $date ){
			return false;
		}
		$dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
		if ($strict) {
			$errors = DateTime::getLastErrors();
			if (!empty($errors['warning_count'])) {
				return false;
			}
		}
		return $dateTime !== false;
	}
	
} //END Class
	
