<?php
//Message CLASS
class Fep_Message
  {
	private static $instance;
	
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
			
			add_action( 'publish_fep_message', array($this, 'recalculate_user_stats') );
			add_action( 'save_post_fep_message', array($this, 'recalculate_user_stats'), 20 ); //after '_participants' meta saved
			add_action( 'trashed_post', array($this, 'recalculate_user_stats') );
			add_action( 'before_delete_post', array($this, 'delete_replies') );
			add_action( 'before_delete_post', array($this, 'participants_save') );
			add_action( 'after_delete_post', array($this, 'recalculate_participants_stats') );
    	}
	
	function time_delay_check( $where, $errors ) {
	
		if( 'new_message' != $where )
			return;
			
		if( current_user_can('manage_options') || ! $delay = absint(fep_get_option('time_delay',5)) )
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
		if( 'threaded' == fep_get_option('message_view','threaded') )
		 	$args['post_parent'] = 0;
			
		if( get_posts( $args ) ) {
			$errors->add('time_delay', sprintf(__( "Please wait at least %s between messages.", 'front-end-pm' ), sprintf(_n('%s minute', '%s minutes', $delay, 'front-end-pm'), number_format_i18n($delay) )));
		}
	}
	
	function box_full_check( $where, $errors ) {
	
		if( 'new_message' != $where )
			return;
			
		if( ! $max = fep_get_current_user_max_message_number() )
			return;
			
		if( fep_get_user_message_count( 'total' ) >= $max ) {
			$errors->add('MgsBoxFull', __( "Your message box is full. Please delete some messages.", 'front-end-pm' ));
		}
	}

function recalculate_user_stats( $postid )
{
	$participants = get_post_meta( $postid, '_participants' );
	
	if( $participants && is_array( $participants ) )
	{
		foreach( $participants as $participant ) 
		{
			delete_user_meta( $participant, '_fep_user_message_count' );
		}
	}
}


function delete_replies( $message_id ) {

		if( get_post_type( $message_id ) != 'fep_message'  )
			return false;
			
			$args = array(
			'post_type' => 'fep_message',
			'post_status' => 'any',
			'post_parent' => $message_id,
			'posts_per_page' => -1,
			'fields' => 'ids'
		 );
		
		$replies = get_posts( $message_id );
			
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
			
	$participants = get_post_meta( $message_id, '_participants' );
	
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
				delete_user_meta( $participant, '_fep_user_message_count' );
			}
		}
	}
}
	
function user_message_count( $value = 'all', $force = false, $user_id = false )
{
	if( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$user_meta = get_user_meta( $user_id, '_fep_user_message_count', true );
	
	if( false === $user_meta || $force || !isset( $user_meta['total'] ) || !isset( $user_meta['read'] )|| !isset( $user_meta['unread'] ) || !isset( $user_meta['archive'] ) || !isset( $user_meta['inbox'] ) || !isset( $user_meta['sent'] ) ) {
	
		$args = array(
			'post_type' => 'fep_message',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_participants',
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
		 
		 if( 'threaded' == fep_get_option('message_view','threaded') )
		 	$args['post_parent'] = 0;
			
		 $messages = get_posts( $args );
		 
		 $total_count 		= 0;
		 $read_count 		= 0;
		 $unread_count 		= 0;
		 $archive_count 	= 0;
		 $inbox_count 		= 0;
		 $sent_count 	= 0;
		 
		 if( $messages && !is_wp_error($messages) ) {
			 foreach( $messages as $message ) {
			 	$total_count++;
			 
			 	$from_user 		= $message->post_author;
				$to_user_meta 	= get_post_meta( $message->ID, '_participants' );
				
			 	$read_meta 	= get_post_meta( $message->ID, '_fep_parent_read_by_'. $user_id, true );
				$archive_meta 	= get_post_meta( $message->ID, '_fep_archived_by_'. $user_id, true );
				
			 	if( $from_user == $user_id )
				{
					$inbox_count++;
					
				} elseif( is_array( $to_user_meta ) && in_array($user_id, $to_user_meta ) ) {
				
					$sent_count++;
				}
				if( $archive_meta ) {
				
					$archive_count++;
				}
				if( $read_meta ) {
						$read_count++;
					} else {
						$unread_count++;
					}
				}
			 }

		 
		 $user_meta = array(
			'total' => $total_count,
			'read' => $read_count,
			'unread' => $unread_count,
			'archive' => $archive_count,
			'inbox' => $inbox_count,
			'sent' => $sent_count
		);
		update_user_meta( $user_id, '_fep_user_message_count', $user_meta );
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
			'meta_query' => array(
				array(
					'key' => '_participants',
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
		 
		 if( 'threaded' == fep_get_option('message_view','threaded') )
		 	$args['post_parent'] = 0;
		 
		 switch( $filter ) {
		 	case 'inbox' :
				$args['author'] = $user_id;
			break;
			case 'sent' :
				$args['author'] = -$user_id;
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
				$args = apply_filters( 'fep_message_query_args_'. $filter, $args);
			break;
		 }
		 $args = apply_filters( 'fep_message_query_args', $args);
		 
	return new WP_Query( $args );

}


function bulk_action( $action, $ids = null ) {

	if( null === $ids ) {
		$ids = !empty($_POST['fep-message-cb'])? $_POST['fep-message-cb'] : array();
	}
	if( !$action || !$ids || !is_array($ids) ) {
		return '';
	}
	$count = 0;
	foreach( $ids as $id ) {
		if( $this->bulk_individual_action( $action, absint($id) ) ) {
			$count++;
		}
	}
	$message = '';
	
	if( $count ) {
		delete_user_meta( get_current_user_id(), '_fep_user_message_count' );
		
		if( 'delete' == $action ){
			$message = sprintf(_n('%s message', '%s messages', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			$message .= __('successfully deleted.', 'front-end-pm');
		} elseif( 'mark-as-read' == $action ){
			$message = sprintf(_n('%s message', '%s messages', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			$message .= __('successfully marked as read.', 'front-end-pm');
		} elseif( 'mark-as-unread' == $action ){
			$message = sprintf(_n('%s message', '%s messages', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			$message .= __('successfully marked as unread.', 'front-end-pm');
		} elseif( 'archive' == $action ){
			$message = sprintf(_n('%s message', '%s messages', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			$message .= __('successfully archived.', 'front-end-pm');
		} elseif( 'restore' == $action ){
			$message = sprintf(_n('%s message', '%s messages', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			$message .= __('successfully restored.', 'front-end-pm');
		}
		$message = '<div id="fep-success">'.$message.'</div>';
	}
	return apply_filters( 'fep_message_bulk_action_message', $message, $count);
}

function bulk_individual_action( $action, $id ) {
	$return = false;
	
	switch( $action ) {
		case 'delete':
			if( fep_current_user_can( 'delete_message', $id ) ) {
				$return = add_post_meta( $id, '_fep_delete_by_'. get_current_user_id(), time(), true );
			}
			$should_delete_from_db = true;
			foreach( get_post_meta( $id, '_participants' ) as $participant ) {
				if( false === get_post_meta( $id, '_fep_delete_by_'. $participant, true ) ) {
					$should_delete_from_db = false;
					break;
				}
				
			}
			if( $should_delete_from_db ) {
				$return = wp_trash_post( $id  );
			}
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
			$return = apply_filters( 'fep_message_bulk_individual_action', false, $action, $id );
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
		case 'fep-cb' :
			?><input type="checkbox" name="fep-message-cb[]" value="<?php echo get_the_ID(); ?>" /><?php
		break;
		case 'avatar' :
			?><span class="fep-message-avatar"><?php echo get_avatar( get_the_author_meta('ID'), 55 ); ?></span><?php
		break;
		case 'author' :
			?><span class="fep-message-author"><?php the_author_meta('display_name'); ?></span><span class="fep-message-date"><?php the_time(); ?></span><?php
		break;
		case 'title' :
			if( ! fep_is_read( true ) ) {
					$span = '<span class="fep-unread-classp"><span class="fep-unread-class">' .__("Unread", "front-end-pm"). '</span></span>';
					$class = ' fep-strong';
				} else {
					$span = '';
					$class = '';
				} 
			?><span class="fep-message-titleq<?php echo $class; ?>"><a href="<?php echo fep_query_url('viewmessage', array('id'=> get_the_ID())); ?>"><?php the_title(); ?></a></span><?php echo $span; ?><span class="fep-message-excerpt"><?php echo fep_get_the_excerpt(100); ?></span><?php
		break;
		default:
			do_action( 'fep_message_table_column_content', $column );
		break;
	}
}

	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Message::init(), 'actions_filters'));

