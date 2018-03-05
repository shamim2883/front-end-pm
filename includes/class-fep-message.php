<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Message CLASS
class Fep_Message
  {
	private static $instance;
	
	public $found_messages = false;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	
	function actions_filters()
    	{
			add_action('fep_action_validate_form', array($this, "time_delay_check"), 10, 2);
			add_action('fep_action_validate_form', array($this, "box_full_check"), 10, 2);
			
			add_action( 'fep_save_message', array($this, 'recalculate_user_stats'), 20 ); //after '_fep_participants' meta saved
			add_action( 'before_delete_post', array($this, 'delete_replies') );
			add_action( 'before_delete_post', array($this, 'participants_save') );
			add_action( 'after_delete_post', array($this, 'recalculate_participants_stats') );
			add_action( 'fep_posted_bulk_bulk_action', array($this, 'bulk_action') );
    	}
	
	function time_delay_check( $where, $errors ) {
	
		if( 'newmessage' != $where )
			return;
			
		$delay = absint(fep_get_option('time_delay',5));
		
		if( fep_is_user_admin() || ! $delay )
			return;
			
		$args = array(
			'post_type' => 'fep_message',
			'post_status' => array( 'pending', 'publish' ),
			'posts_per_page' => 1,
			'author'	   => get_current_user_id(),
			'date_query' => array(
        		'after' => "-{$delay} minutes"
				) 
			);
		if( 'threaded' == fep_get_message_view() )
		 	$args['post_parent'] = 0;
			
		if( get_posts( $args ) ) {
			$errors->add('time_delay', sprintf(__( "Please wait at least %s between messages.", 'front-end-pm' ), sprintf(_n('%s minute', '%s minutes', $delay, 'front-end-pm'), number_format_i18n($delay) )));
		}
	}
	
	function box_full_check( $where, $errors ) {
	
		if( 'newmessage' != $where )
			return;
			
		if( ! $max = fep_get_current_user_max_message_number() )
			return;
			
		if( fep_get_user_message_count( 'total' ) >= $max ) {
			$errors->add('MgsBoxFull', __( "Your message box is full. Please delete some messages.", 'front-end-pm' ));
		}
	}

function recalculate_user_stats( $postid )
{
	
	$participants = fep_get_participants( $postid );
	
	if( $participants && is_array( $participants ) )
	{
		foreach( $participants as $participant ) 
		{
			delete_user_option( $participant, '_fep_user_message_count' );
		}
	}
}


function delete_replies( $message_id ) {

		if( get_post_type( $message_id ) != 'fep_message'  )
			return false;
		
		if( 'threaded' != fep_get_message_view() ){
			return false;
		}
		$args = array(
			'post_type' => 'fep_message',
			'post_status' => 'any',
			'post_parent' => $message_id,
			'posts_per_page' => -1,
			'fields' => 'ids'
		 );
		
		$replies = get_posts( $args );
			
		if ($replies) {
		  foreach ($replies as $reply){
			wp_delete_post( $reply ); 
		
			} 
		}
   }
  
 function participants_save( $message_id )
{
	if( get_post_type( $message_id ) != 'fep_message'  )
		return false;
			
	$participants = fep_get_participants( $message_id );
	
	if( $participants && is_array( $participants ) )
	{
		add_option( '_fep_before_delete_post', $participants );
	}
}

function recalculate_participants_stats()
{
	$participants = get_option( '_fep_before_delete_post' );
	
	if( false !== $participants )
	{
		delete_option( '_fep_before_delete_post' );
		
		if( is_array( $participants ) ) {
			foreach( $participants as $participant ) 
			{
				delete_user_option( $participant, '_fep_user_message_count' );
			}
		}
	}
}
	
function user_message_count( $value = 'all', $force = false, $user_id = false )
{
	if( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	
	if( 'show-all' == $value )
		$value = 'total';
	
	if( ! $user_id ) {
		if( 'all' == $value ) {
			return array();
		} else {
			return 0;
		}
	}
	
	$user_meta = get_user_option( '_fep_user_message_count', $user_id );
	
	if( false === $user_meta || $force || !isset( $user_meta['total'] ) || !isset( $user_meta['unread'] ) ) {
	
		$args = array(
			'post_type' => 'fep_message',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_fep_participants',
					'value' => $user_id,
					'compare' => '='
				),
				array(
					'key' => '_fep_delete_by_'. $user_id,
					//'value' => $id,
					'compare' => 'NOT EXISTS'
				)
			)
		 );
		 
		if( 'threaded' == fep_get_message_view() ){
			$args['post_parent'] = 0;
		}
		$args = apply_filters( 'fep_message_count_query_args', $args, $user_id );
		
		$messages = get_posts( $args );
		
		$total_count 	= count( $messages );
		
		$args['meta_query'][] = array(
			'key' => '_fep_parent_read_by_'. $user_id,
			'compare' => 'NOT EXISTS'
		);
		
		$messages = get_posts( $args );
		
		$unread_count 	= count( $messages );
		 
		 $user_meta = array(
			'total' => $total_count,
			'unread' => $unread_count,
		);
		update_user_option( $user_id, '_fep_user_message_count', $user_meta );
	}
	if( isset($user_meta[$value]) ) {
		return $user_meta[$value];
	}
	if( 'all' == $value ) {
		return $user_meta;
	} else {
		return 0;
	}

}

function user_messages( $action = 'messagebox', $user_id = false )
{
	if( ! $user_id ) {
		$user_id = get_current_user_id();
	}
		$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
	
		$args = array(
			'post_type' => 'fep_message',
			'post_status' => 'publish',
			'posts_per_page' => fep_get_option('messages_page',15),
			'paged'	=> !empty($_GET['feppage']) ? absint($_GET['feppage']): 1,
			//'orderby' => 'post_modified',
			'meta_query' => array(
				array(
					'key' => '_fep_participants',
					'value' => $user_id,
					'compare' => '='
				),
				array(
					'key' => '_fep_delete_by_'. $user_id,
					//'value' => $id,
					'compare' => 'NOT EXISTS'
				)
				
			)
		 );
		
		if( 'threaded' == fep_get_message_view() ){
			$args['post_parent'] = 0;
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = '_fep_last_reply_time';
		}
		if( !empty($_GET['fep-search']) ) {
			$args['s'] = $_GET['fep-search'];
		}
		 
		 switch( $filter ) {
		 	case 'inbox' :
				if( 'threaded' == fep_get_message_view() ){
					$args['meta_query'][] = array(
						'key' => '_fep_last_reply_by',
						'value' => $user_id,
						'compare' => '!='
					);
				} else {
					$args['author'] = -$user_id;
				}
			break;
			case 'sent' :
				if( 'threaded' == fep_get_message_view() ){
					$args['meta_query'][] = array(
						'key' => '_fep_last_reply_by',
						'value' => $user_id,
						'compare' => '='
					);
				} else {
					$args['author'] = $user_id;
				}
			break;
			case 'archive' :
				$args['meta_query'][] = array(
					'key' => '_fep_archived_by_'. $user_id,
					//'value' => $user_id,
					'compare' => 'EXISTS'
				);
			break;
			case 'read' :
				$args['meta_query'][] = array(
					'key' => '_fep_parent_read_by_'. $user_id,
					//'value' => $user_id,
					'compare' => 'EXISTS'
				);
			break;
			case 'unread' :
				$args['meta_query'][] = array(
					'key' => '_fep_parent_read_by_'. $user_id,
					//'value' => $user_id,
					'compare' => 'NOT EXISTS'
				);
			break;
			default:
				$args = apply_filters( 'fep_message_query_args_'. $filter, $args, $user_id );
			break;
		 }
		 
		 $args = apply_filters( 'fep_message_query_args', $args, $user_id );
		 
		if( 'threaded' == fep_get_message_view() && apply_filters( 'fep_thread_show_last_message', true ) ){
			
			$query = new WP_Query( wp_parse_args( array( 'fields' => 'ids' ), $args ) );
			//$ids = get_posts( wp_parse_args( array( 'fields' => 'ids' ), $args ) );
			$ids = $query->posts;
			$this->found_messages = $query->found_posts;
			
			if( $ids = array_filter( array_map( 'absint', $ids ) ) ){
				global $wpdb;
				$ids = implode( ',', $ids );
				$message_ids = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_fep_last_reply_id' AND post_id IN ($ids)" );
				
				if( $message_ids = array_filter( array_map( 'absint', $message_ids ) ) ){
					$args = array(
						'post_type' => 'fep_message',
						'post_status' => 'publish',
						'no_found_rows' => true,
						'posts_per_page' => fep_get_option( 'messages_page', 15 ),
						'post__in'		=> $message_ids,
						'order'		=> isset( $args['order'] ) ? $args['order'] : 'DESC',
						);
				} else {
					$args['post__in'] = array( 0 );
				}
			} else {
				$args['post__in'] = array( 0 );
			}
			
		} else {
			//return new WP_Query( $args );
		}
		
		$query = new WP_Query( $args );
		if( false === $this->found_messages ){
			$this->found_messages = $query->found_posts;
		}
		
	return $query;

}


function bulk_action( $action, $ids = null ) {

	if( null === $ids ) {
		$ids = !empty($_POST['fep-message-cb'])? $_POST['fep-message-cb'] : array();
	}
	if( !$action || !$ids || !is_array($ids) ) {
		return;
	}
	$count = 0;
	foreach( $ids as $id ) {
		if( $this->bulk_individual_action( $action, absint($id) ) ) {
			$count++;
		}
	}
	$message = '';
	
	if( $count ) {
		delete_user_option( get_current_user_id(), '_fep_user_message_count' );
		
			$message = sprintf(_n('%s message', '%s messages', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			
		if( 'delete' == $action ){
			$message .= __('successfully deleted.', 'front-end-pm');
		} elseif( 'mark-as-read' == $action ){
			$message .= __('successfully marked as read.', 'front-end-pm');
		} elseif( 'mark-as-unread' == $action ){
			$message .= __('successfully marked as unread.', 'front-end-pm');
		} elseif( 'archive' == $action ){
			$message .= __('successfully archived.', 'front-end-pm');
		} elseif( 'restore' == $action ){
			$message .= __('successfully restored.', 'front-end-pm');
		}
		//$message = '<div class="fep-success">'.$message.'</div>';
	}
	$message = apply_filters( 'fep_message_bulk_action_message', $message, $count);
	
	if( $message ){
		fep_success()->add( 'success', $message );
	}
}

function bulk_individual_action( $action, $passed_id ) {
	$return = false;
	
	if( 'threaded' == fep_get_message_view() ){
		$id = fep_get_parent_id( $passed_id );
	} else {
		$id = $passed_id;
	}
	
	switch( $action ) {
		case 'delete':
			$return = fep_delete_message( $id );
		break;
		case 'mark-as-read':
			if( fep_current_user_can( 'view_message', $id ) ) {
				$return = add_post_meta( $id, '_fep_parent_read_by_'. get_current_user_id(), time(), true );
			}
		break;
		case 'mark-as-unread':
			if( fep_current_user_can( 'view_message', $id ) ) {
				$return = delete_post_meta( $id, '_fep_parent_read_by_'. get_current_user_id() );
			}
		break;
		case 'archive':
			if( fep_current_user_can( 'view_message', $id ) ) {
				$return = add_post_meta( $id, '_fep_archived_by_'. get_current_user_id(), time(), true );
			}
		break;
		case 'restore':
			if( fep_current_user_can( 'view_message', $id ) ) {
				$return = delete_post_meta( $id, '_fep_archived_by_'. get_current_user_id() );
			}
		break;
		default:
			$return = apply_filters( 'fep_message_bulk_individual_action', false, $action, $id, $passed_id );
		break;
	}
	return $return;
}

function get_table_bulk_actions()
{
	$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
	
	$actions = array(
			'delete' => __('Delete', 'front-end-pm'),
			'mark-as-read' => __('Mark as read', 'front-end-pm'),
			'mark-as-unread' => __('Mark as unread', 'front-end-pm')
			);
			
	if( 'archive' == $filter ) {
		$actions['restore'] = __('Restore', 'front-end-pm');
	} else {
		$actions['archive'] = __('Archive', 'front-end-pm');
	}
	
	return apply_filters('fep_message_table_bulk_actions', $actions );
}

function get_table_filters()
{
	$filters = array(
			'show-all' => __('Show all', 'front-end-pm'),
			'inbox' => __('Inbox', 'front-end-pm'),
			'sent' => __('Sent', 'front-end-pm'),
			'read' => __('Read', 'front-end-pm'),
			'unread' => __('Unread', 'front-end-pm'),
			'archive' => __('Archive', 'front-end-pm')
			);
	return apply_filters('fep_message_table_filters', $filters );
}

function get_table_columns()
{
	$columns = array(
			'fep-cb' => __('Checkbox', 'front-end-pm'),
			'avatar' => __('Avatar', 'front-end-pm'),
			'author' => __('Author', 'front-end-pm'),
			'title' => __('Title', 'front-end-pm')
			);
	return apply_filters('fep_message_table_columns', $columns );
}

function get_column_content($column)
{
	switch( $column ) {
		
		case has_action("fep_message_table_column_content_{$column}"):

			do_action("fep_message_table_column_content_{$column}");

		break;
		case 'fep-cb' :
			?><input type="checkbox" name="fep-message-cb[]" value="<?php echo get_the_ID(); ?>" /><?php
		break;
		case 'avatar' :
			$participants = fep_get_participants( get_the_ID() );
			if ( apply_filters('fep_remove_own_avatar_from_messagebox', false )
			&& ($key = array_search(get_current_user_id(), $participants)) !== false) {
				unset($participants[$key]);
			}
			$count = 1;
		?>
		<div class="fep-avatar-p <?php echo ( count( $participants ) > 2 ) ? 'fep-avatar-p-120' : 'fep-avatar-p-90' ?>"><?php
			foreach( $participants as $p ){
				if( $count > 2 ){
					echo '<div class="fep-avatar-more-60" title="' . __('More users', 'front-end-pm') . '"></div>';
					break;
				} 
				?><div class="fep-avatar-<?php echo $count; ?>"><?php echo get_avatar( $p, 60, '', '', array( 'extra_attr'=> 'title="'. fep_user_name( $p ) . '"') ); ?></div><?php
				$count++;
			}
			if( ! $participants && $group = get_post_meta( get_the_ID(), '_fep_group', true ) ){
				echo '<div class="fep-avatar-group-60" title="' . __('Group', 'front-end-pm') . '"></div>';
			}
		echo '</div>';
		break;
		case 'author' :
			?><span class="fep-message-author"><?php echo fep_user_name( get_the_author_meta('ID') ); ?></span><span class="fep-message-date"><?php the_time(); ?></span><?php
		break;
		case 'title' :
			if( ! fep_is_read( true ) ) {
					$span = '<span class="fep-unread-classp"><span class="fep-unread-class">' .__("Unread", "front-end-pm"). '</span></span>';
					$class = ' fep-strong';
				} else {
					$span = '';
					$class = '';
				} 
			?><span class="<?php echo $class; ?>"><a href="<?php echo fep_query_url('viewmessage', array('fep_id'=> get_the_ID())); ?>"><?php the_title(); ?></a></span><?php echo $span; ?><div class="fep-message-excerpt"><?php echo fep_get_the_excerpt(100); ?></div><?php
		break;
		default:
			do_action( 'fep_message_table_column_content', $column );
		break;
	}
}

	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Message::init(), 'actions_filters'));

