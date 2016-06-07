<?php
//Announcement CLASS
class Fep_Announcement
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
			add_action( 'publish_fep_announcement', array($this, 'recalculate_user_stats') );
			add_action( 'trash_fep_announcement', array($this, 'recalculate_user_stats') );
			//add_action( 'after_delete_post', array($this, 'recalculate_user_stats') );
    	}
	

function recalculate_user_stats(){
	
	delete_metadata( 'user', 0, '_fep_user_announcement_count', '', true );
}

function get_user_announcements( $user_id = false )
{
	if( false === $user_id ) {
		$user_id = get_current_user_id();
	}
	
	if( ! $user_id )
		return array();
	
	$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
	$per_page = fep_get_option('announcements_page',15);
	$page = !empty( $_GET['feppage']) ? absint( $_GET['feppage'] ) - 1: 0;
    $offset = $page * $per_page;
	
		$args = array(
			'post_type' => 'fep_announcement',
			'post_status' => 'publish',
			'post_parent' => 0,
			'posts_per_page' => $per_page,
			'paged'	=> !empty($_GET['feppage']) ? absint($_GET['feppage']): 1,
			'meta_query' => array(
				array(
					'key' => '_participant_roles',
					'value' => wp_get_current_user()->roles,
					'compare' => 'IN'
				),
				array(
					'key' => '_fep_delete_by_'. $user_id,
					//'value' => $id,
					'compare' => 'NOT EXISTS'
				)
				
			)
		 );
		 
		 if( $filter && 'after-i-registered' != $filter ) {
		 	unset( $args['paged'] );
			$args['posts_per_page'] = -1;
		 }
	
		if( 'after-i-registered' == $filter ) {
				$args['date_query'] = array( 'after' => fep_get_userdata( $user_id, 'user_registered', 'id' ) );
			}
		
		 $args = apply_filters( 'fep_announcement_query_args', $args);
		 
	$announcements = get_posts( $args );
	
	if( ! $announcements )
		return array();
		
	if( ! $filter || 'after-i-registered' == $filter ) {
		 	return $announcements;
		 }
	
	$count = 0;
	
	foreach( $announcements as $index => $announcement ) {

		if( 'read' == $filter ) {
			$read_by = get_post_meta( $announcement->ID, '_fep_read_by', true );
			
			if( ! is_array( $read_by ) || ! in_array( $user_id, $read_by ) ) {
				unset( $announcements[$index] );
				continue;
			}
		}
		if( 'unread' == $filter ) {
			$read_by = get_post_meta( $announcement->ID, '_fep_read_by', true );
			
			if( is_array( $read_by ) && in_array( $user_id, $read_by ) ) {
				unset( $announcements[$index] );
				continue;
			}
		}
		
		$count++;
		
		if( $count >= ( $offset + $per_page ) )
			break;
		
	}
	return array_slice( $announcements, $offset, $per_page );

}

function get_user_announcement_count( $value = 'all', $force = false, $user_id = false )
{
	if( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$user_meta = get_user_meta( $user_id, '_fep_user_announcement_count', true );
	
	if( false === $user_meta || $force || !isset( $user_meta['total'] ) || !isset( $user_meta['read'] )|| !isset( $user_meta['unread'] ) ) {
	
		$args = array(
			'post_type' => 'fep_announcement',
			'post_status' => 'publish',
			'post_parent' => 0,
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_participant_roles',
					'value' => wp_get_current_user()->roles,
					'compare' => 'IN'
				),
				array(
					'key' => '_fep_delete_by_'. $user_id,
					//'value' => $id,
					'compare' => 'NOT EXISTS'
				)
				
			)
		 );
		 $announcements = get_posts( $args );
		 
		 $total_count 		= 0;
		 $read_count 		= 0;
		 $unread_count 		= 0;
		 $after_i_registered_count = 0;
		 
		 if( $announcements && !is_wp_error($announcements) ) {
			 foreach( $announcements as $announcement ) {
		
			 	$total_count++;
				
			 	$read_by = get_post_meta( $announcement->ID, '_fep_read_by', true );
			
				if( is_array( $read_by ) && in_array( $user_id, $read_by ) ) {
					$read_count++;
				} else {
					$unread_count++;
				}
				$user_registered = strtotime(fep_get_userdata( $user_id, 'user_registered', 'id' ));
					
				if( $user_registered > strtotime( $announcement->post_date ) ) {
					$after_i_registered_count++;
				}
				
			 }
			}

		 
		 $user_meta = array(
			'total' => $total_count,
			'read' => $read_count,
			'unread' => $unread_count,
			'after-i-registered' => $after_i_registered_count
		);
		update_user_meta( $user_id, '_fep_user_announcement_count', $user_meta );
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

function bulk_action( $action, $ids = null ) {

	if( null === $ids ) {
		$ids = !empty($_POST['fep-message-cb'])? $_POST['fep-message-cb'] : array();
	}
	if( !$action || !$ids || !is_array($ids) ) {
		return '';
	}
	
	$token = ! empty($_POST['token']) ? $_POST['token'] : '';
				
	if ( !fep_verify_nonce( $token, 'announcement_bulk_action') ) {
		return '<div id="fep-error">' .__("Invalid Token. Please try again!", 'front-end-pm'). ' </div>';
	}
					
	$count = 0;
	foreach( $ids as $id ) {
		if( $this->bulk_individual_action( $action, absint($id) ) ) {
			$count++;
		}
	}
	$message = '';
	
	if( $count ) {
		delete_user_meta( get_current_user_id(), '_fep_user_announcement_count' );
		
		if( 'delete' == $action ){
			$message = sprintf(_n('%s announcement', '%s announcements', $count, 'front-end-pm'), number_format_i18n($count) );
			$message .= ' ';
			$message .= __('successfully deleted.', 'front-end-pm');
		} 
		$message = '<div id="fep-success">'.$message.'</div>';
	}
	return apply_filters( 'fep_bulk_action_message', $message, $count);
}

function bulk_individual_action( $action, $id ) {
	$return = false;
	
	switch( $action ) {
		case 'delete':
			if( fep_current_user_can( 'view_announcement', $id ) ) {
				$return = add_post_meta( $id, '_fep_delete_by_'. get_current_user_id(), time(), true );
			}

		break;
		default:
			$return = apply_filters( 'fep_announcement_bulk_individual_action', false, $action, $id );
		break;
	}
	return $return;
}

function get_table_bulk_actions()
{
	
	$actions = array(
			'delete' => __('Delete', 'front-end-pm')
			);

	
	return apply_filters('fep_announcement_table_bulk_actions', $actions );
}

function get_table_filters()
{
	$filters = array(
			'read' => __('Read', 'front-end-pm'),
			'unread' => __('Unread', 'front-end-pm'),
			'after-i-registered' => __('After i registered', 'front-end-pm')
			);
	return apply_filters('fep_announcementbox_table_filters', $filters );
}

function get_table_columns()
{
	$columns = array(
			'fep-cb' => __('Checkbox', 'front-end-pm'),
			'date' => __('Date', 'front-end-pm'),
			'title' => __('Title', 'front-end-pm')
			);
	return apply_filters('fep_announcement_table_columns', $columns );
}

function get_column_content($column)
{
	switch( $column ) {
		case 'fep-cb' :
			?><input type="checkbox" name="fep-message-cb[]" value="<?php echo get_the_ID(); ?>" /><?php
		break;
		case 'date' :
			?><span class="fep-message-date"><?php the_time(); ?></span><?php
		break;
		case 'title' :
			if( ! fep_is_read() ) {
					$span = '<span class="fep-unread-classp"><span class="fep-unread-class">' .__("Unread", "front-end-pm"). '</span></span>';
					$class = ' fep-strong';
				} else {
					$span = '';
					$class = '';
				} 
			?><span class="fep-message-titleq<?php echo $class; ?>"><a href="<?php echo fep_query_url('view_announcement', array('id'=> get_the_ID())); ?>"><?php the_title(); ?></a></span><?php echo $span; ?><span class="fep-message-excerpt"><?php echo fep_get_the_excerpt(100); ?></span><?php
		break;
		default:
			do_action( 'fep_get_announcement_column_content', $column );
		break;
	}
}

	function announcement_box()
{
	global $post;
	
	  $g_filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
	  
	  $message = '';
	  
	  if( isset($_POST['fep_action']) && 'announcement_bulk_action' == $_POST['fep_action'] ) {
	  	
		$posted_bulk_action = ! empty($_POST['fep-bulk-action']) ? $_POST['fep-bulk-action'] : '';
	  	
		$message = $this->bulk_action( $posted_bulk_action );
	  }
	  
	  $total_announcements = $this->get_user_announcement_count('total');
	  
	  $announcements = $this->get_user_announcements();
	  
	  if( ! $total_announcements ) {
	  	return "<div id='fep-error'>".apply_filters('fep_filter_announcement_empty', __("No announcements found.", 'front-end-pm') )."</div>";
	  }
	  ob_start();
	  
	  echo $message;
	  
	  do_action('fep_display_before_announcementbox');
	  
	  	?><form class="fep-message-table form" method="post" action="">
		<div class="fep-table fep-action-table">
			<div>
				<div class="fep-bulk-action">
					<select name="fep-bulk-action">
						<option value=""><?php _e('Bulk action', 'front-end-pm'); ?></option>
						<?php foreach( $this->get_table_bulk_actions() as $bulk_action => $bulk_action_display ) { ?>
						<option value="<?php echo $bulk_action; ?>"><?php echo $bulk_action_display; ?></option>
						<?php } ?>
					</select>
				</div>
				<div>
					<input type="hidden" name="token"  value="<?php echo fep_create_nonce('announcement_bulk_action'); ?>"/>
					<button type="submit" name="fep_action" value="announcement_bulk_action"><?php _e('Apply', 'front-end-pm'); ?></button>
				</div>
				<div class="fep-loading-gif-div">
				</div>
				<div class="fep-filter">
					<select onchange="if (this.value) window.location.href=this.value">
						<option value="<?php echo esc_url( remove_query_arg( array( 'feppage', 'fep-filter') ) ); ?>"><?php _e('Show all', 'front-end-pm'); ?></option>
						<?php foreach( $this->get_table_filters() as $filter => $filter_display ) { ?>
						<option value="<?php echo esc_url( add_query_arg( array('fep-filter' => $filter, 'feppage' => false ) ) ); ?>" <?php selected($g_filter, $filter);?>><?php echo $filter_display; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>
		<?php if( $announcements ) { ?>
		<div id="fep-table" class="fep-table fep-odd-even"><?php
			foreach ( $announcements as $post ) { 
				setup_postdata( $post ); ?>
					<div id="fep-message-<?php echo get_the_ID(); ?>" class="fep-table-row"><?php
						foreach ( $this->get_table_columns() as $column => $display ) { ?>
							<div class="fep-column fep-column-<?php echo $column; ?>"><?php $this->get_column_content($column); ?></div>
						<?php } ?>
					</div>
				<?php
			} //endwhile
			?></div><?php
			echo fep_pagination( $this->get_user_announcement_count($g_filter) );
		} else {
			?><div id="fep-error"><?php _e('No announcements found. Try different filter.', 'front-end-pm'); ?></div><?php 
		}
		?></form><?php 
		wp_reset_postdata();
	  return ob_get_clean();
}

function view_announcement()
    {
      global $post;

      $pID = !empty($_GET['id']) ? absint($_GET['id']) : 0;
	  
	  if ( ! $pID || ! fep_current_user_can( 'view_announcement', $pID ) ) {
	  	return "<div id='fep-error'>".__("You do not have permission to view this announcement!", 'front-end-pm')."</div>";
	  }

      $announcement = fep_get_message( $pID );

	  if ( ! $announcement ) {
	  	return "<div id='fep-error'>".__("You do not have permission to view this announcement!", 'front-end-pm')."</div>";
	  }
	  
	  $post = $announcement; //setup_postdata does not work properly if variable name is NOT $post !!!!!
	  
	  ob_start();
	  setup_postdata( $post );
	  
	  if( fep_make_read() ) {
	  	delete_user_meta( get_current_user_id(), '_fep_user_announcement_count' );
	  }
	  ?>
		 <div class="fep-per-message">
			<div class="fep-message-title"><?php the_title(); ?>
				<span class="date"><?php the_time(); ?></span>
			</div>
			<div class="fep-message-content">
				<?php the_content(); ?>
				<?php do_action ( 'fep_display_after_announcement' ); ?>
			</div>
		</div>
		<?php 
		wp_reset_postdata();
		
		return ob_get_clean();
    }

	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Announcement::init(), 'actions_filters'));

