<?php

class Fep_Cpt {

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
		add_action ('init', array($this, 'create_cpt') );
		add_action ('contextual_help', array($this, 'contextual_help'), 10, 3 );
		add_action ('save_post_fep_message', array($this, 'save_message'), 10, 3 );
		add_action ('save_post_fep_announcement', array($this, 'save_announcement'), 10, 3 );
		
		add_action ('fep_save_message', array($this, 'fep_save_message_to'), 10, 3 );
		add_action ('fep_save_message', array($this, 'fep_save_message'), 10, 3 );
		
		add_action ('fep_save_announcement', array($this, 'save_announcement_to'), 10, 3 );
		
		add_action ('edit_form_after_title', array($this, 'edit_form_after_title') );
		add_action ('add_meta_boxes', array($this, 'add_meta_boxes') );
		add_filter( 'redirect_post_location', array($this, 'redirect_post_location'), 10, 2 );
		add_filter('manage_fep_message_posts_columns', array($this, 'columns_head'));
		add_filter('post_row_actions', array($this, 'view_link'), 10, 2 );
		add_action('manage_fep_message_posts_custom_column', array($this, 'columns_content'), 10, 2);
		add_filter( 'manage_fep_message_sortable_columns', array($this, 'sortable_column' ));
		
		add_filter('manage_fep_announcement_posts_columns', array($this, 'announcement_columns_head'));
		add_action('manage_fep_announcement_posts_custom_column', array($this, 'announcement_columns_content'), 10, 2);
		
		add_action ('post_submitbox_start', array($this, 'post_submitbox_start_info') );
		//add_action( 'pre_get_posts', array($this, 'sortable_orderby' ));
		//add_filter('user_has_cap', array($this, 'restrict_editing'), 10, 3 );
    }

	function create_cpt()
	{
		/** fep_message Post Type */
		$labels = array(
			'name' 				=> _x('Messages', 'post type general name', 'edd' ),
			'singular_name' 	=> _x('Message', 'post type singular name', 'edd' ),
			'add_new' 			=> __( 'New Message', 'edd' ),
			'add_new_item' 		=> __( 'New Message', 'edd' ),
			'edit_item' 		=> __( 'Edit Message', 'edd' ),
			'new_item' 			=> __( 'New Message', 'edd' ),
			'all_items' 		=> __( 'All Messages', 'edd' ),
			'view_item' 		=> __( 'View Message', 'edd' ),
			'search_items' 		=> __( 'Search Message', 'edd' ),
			'not_found' 		=>  __( 'No Messages found', 'edd' ),
			'not_found_in_trash'=> __( 'No Messages found in Trash', 'edd' ),
			'parent_item_colon' => '',
			'menu_name' 		=> __( 'Front End PM', 'edd' )
		);
	
		$args = array(
			'labels' 			=> apply_filters( 'fep_message_cpt_labels', $labels ),
			'query_var' 		=> false,
			'rewrite' 			=> false,
			'show_ui' 			=> true,
			//'show_in_menu' 		=> true,
			'capability_type' 	=> 'post',
			'capabilities' => array(
				'create_posts' => 'do_not_allow', //will be changed in next version to send message from BACK END
				'edit_published_posts' => 'fep_cap_edit_published_posts' ), //Should not give permission to edit Sent Message/ Published announcements
			'map_meta_cap'      => true,
			'menu_icon'   		=> 'dashicons-email-alt',
			'supports' 			=> apply_filters( 'fep_message_cpt_supports', array( 'title', 'editor' ) ),
			'can_export'		=> true
		);
		register_post_type( 'fep_message', apply_filters( 'fep_message_cpt_args', $args )  );
		
		
		/** fep_announcement Post Type */
		$announcement_labels = array(
			'name' 				=> _x('Announcements', 'post type general name', 'edd' ),
			'singular_name' 	=> _x('Announcement', 'post type singular name', 'edd' ),
			'add_new' 			=> __( 'New Announcement', 'edd' ),
			'add_new_item' 		=> __( 'New Announcement', 'edd' ),
			'edit_item' 		=> __( 'Edit Announcement', 'edd' ),
			'new_item' 			=> __( 'New Announcement', 'edd' ),
			'all_items' 		=> __( 'All Announcements', 'edd' ),
			'view_item' 		=> __( 'View Announcement', 'edd' ),
			'search_items' 		=> __( 'Search Announcement', 'edd' ),
			'not_found' 		=>  __( 'No Announcements found', 'edd' ),
			'not_found_in_trash'=> __( 'No Announcements found in Trash', 'edd' ),
			'parent_item_colon' => '',
			'menu_name' 		=> __( 'Announcement', 'edd' )
		);
		
		$announcement_args = array(
			'labels' 			=> apply_filters( 'fep_announcement_cpt_labels', $announcement_labels ),
			'query_var' 		=> false,
			'rewrite' 			=> false,
			'show_ui' 			=> true,
			'show_in_menu' 		=> 'edit.php?post_type=fep_message',
			'capability_type' 	=> 'page',
			'capabilities' => array(
				'edit_published_posts' => 'fep_cap_edit_published_posts' 
				), //Should not give permission to edit Sent Message/ Published announcements
			'map_meta_cap'      => true,
			'supports' 			=> apply_filters( 'fep_announcement_cpt_supports', array( 'title', 'editor' ) ),
			'can_export'		=> true
		);
		register_post_type( 'fep_announcement', apply_filters( 'fep_announcement_cpt_args', $announcement_args )  );
	
	}


	function contextual_help( $contextual_help, $screen_id, $screen ) { 
	  if ( 'fep_message' == $screen->id ) {
	
		$contextual_help = '<h2>Message</h2>
		<p>Test help.</p> 
		<p>Test help.</p>';
	
	  } elseif ( 'edit-fep_message' == $screen->id ) {
	
		$contextual_help = '<h2>Editing Message</h2>
		<p>Test help.</p> 
		<p>Test help.</p>';
	
	  }
	  return $contextual_help;
	}

function edit_form_after_title( $post ) {
    if( ! in_array( $post->post_type, array( 'fep_message', 'fep_announcement' ) ) ) {
        return;
    }

    wp_nonce_field( 'fep_nonce', 'fep_nonce' );
}

function add_meta_boxes() {
    add_meta_box( 
        'fep_message_to_box',
        __( 'Message To', 'front-end-pm' ),
        array($this, 'fep_message_to_box_content'),
        'fep_message',
        'side',
        'high'
    );
	remove_meta_box( 'slugdiv', 'fep_message', 'normal' );
	 //remove_meta_box( 'submitdiv', 'fep_message', 'core' );
	 add_meta_box( 'fep_announcement_to', __( 'Announcement to roles' ), array($this, 'announcement_to'), 'fep_announcement', 'side', 'core' );
}

function announcement_to( $post ) {
 
		$participants = get_post_meta( $post->ID, '_participant_roles' );

		
			foreach( get_editable_roles() as $key => $role ) {
			
				?><label><input id="" class="" name="participant_roles[]" type="checkbox" value="<?php echo $key; ?>" <?php if( in_array( $key, $participants ) ) echo'checked="checked"'; ?> /> <?php esc_attr_e( $role['name'] ); ?></label><br /><?php
			}

	}

	
function fep_message_to_box_content( $post ) {
 
	if ( isset($_GET['action'])  && $_GET['action'] == 'edit' ) {
		$participants = get_post_meta( $post->ID, '_participants' );
		
		if( $participants ) {
			foreach( $participants as $participant ) {
			
				if( $participant != $post->post_author )
				echo '<a href="'. get_edit_user_link( $participant ) .'" target="_blank">'. esc_attr( fep_get_userdata( $participant, 'display_name', 'ID' ) ) .'</a>';
			}
		}
		echo '<h2>'. __('Sender', 'front-end-pm') . '</h2>';
		echo '<a href="'. get_edit_user_link( $post->post_author ) .'" target="_blank">'. esc_attr( fep_get_userdata( $post->post_author, 'display_name', 'ID' ) ) .'</a>';

	} else {

		$parent = ( !empty( $_REQUEST['fep_parent_id'] ) ) ? absint( $_REQUEST['fep_parent_id'] ) : '';
		$to 	= ( !empty( $_REQUEST['fep_to'] ) ) ? $_REQUEST['fep_to'] : '';
		
		if( $parent ) {
			echo 'You are replying to '. $parent;
			echo '<input type="hidden" name="fep_parent_id" value="' . $parent . '" />';
		} else {
			wp_enqueue_script( 'fep-script' ); ?>
							
			<input type="hidden" name="message_to" id="fep-message-to" autocomplete="off" value="<?php echo fep_get_userdata( $to, 'user_login' ); ?>" />		
			<input type="text" name="message_top" id="fep-message-top" autocomplete="off" value="<?php echo fep_get_userdata($to, 'display_name'); ?>" />
			<img src="<?php echo FEP_PLUGIN_URL; ?>images/loading.gif" class="fep-ajax-img" style="display:none;"/>
			<div id="fep-result"></div><?php
		} 
	}
}

function fep_save_message_to( $message_id, $message, $update ){
	if( ! empty($_REQUEST['message_to'] ) ) { //BACK END message_to return login of participants
		if( is_array( $_REQUEST['message_to'] ) ) {
			foreach( $_REQUEST['message_to'] as $participant ) {
				add_post_meta( $message_id, '_participants', fep_get_userdata( $participant, 'ID', 'login' ) );
			}
		} else {
			add_post_meta( $message_id, '_participants', fep_get_userdata( $_REQUEST['message_to'], 'ID', 'login' ) );
		}
		add_post_meta( $message_id, '_participants', $message->post_author );
		
		unset( $_REQUEST['message_to'] );
	}
}

function save_announcement_to( $announcement_id, $announcement, $update ){
	if( ! empty($_POST['participant_roles'] ) && is_array( $_POST['participant_roles'] ) ) {
		delete_post_meta( $announcement_id, '_participant_roles' );
		
			foreach($_POST['participant_roles'] as $role ) {
				add_post_meta( $announcement_id, '_participant_roles', $role );
			}
	
	}
}

function fep_save_message( $message_id, $message, $update ){
	if( ! empty($_REQUEST['fep_parent_id'] ) ) {
	remove_action ('fep_save_message', array($this, 'fep_save_message'), 10, 3 );
			wp_update_post(
						array(
							'ID' => $message_id, 
							'post_parent' => absint($_REQUEST['fep_parent_id'])
						)
					);
				unset( $_REQUEST['fep_parent_id'] );
	add_action ('fep_save_message', array($this, 'fep_save_message'), 10, 3 );
	}
}
function post_submitbox_start_info()
{
	global $post;
	
	if( ! in_array( $post->post_type, array( 'fep_message', 'fep_announcement' ) ) ) {
        return;
    }
	
	_e('Can NOT be edited once published', 'front-end-pm');
}

	function save_message( $message_id, $message, $update ) {
			if ( ! is_admin() ) return; //only for BACK END . for FRONT END use 'fep_action_message_after_send' action hook
			if ( empty( $message_id ) || empty( $message ) || empty( $_POST ) ) return;
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
			if ( is_admin() && ( empty($_POST['fep_nonce']) || ! wp_verify_nonce( $_POST['fep_nonce'], 'fep_nonce' ) ) ) return;
			if ( wp_is_post_revision( $message ) ) return;
			if ( wp_is_post_autosave( $message ) ) return;
			//if ( ! current_user_can( 'edit_posts' ) ) return;
			if ( ! current_user_can( 'edit_post', $message_id ) && ! current_user_can( 'delete_post', $message_id ) ) return;
			//if ( $message->post_type != 'fep_message' ) return;
			
			do_action( 'fep_save_message', $message_id, $message, $update );
		}
		
	function save_announcement( $announcement_id, $announcement, $update ) {
			if ( empty( $announcement_id ) || empty( $announcement ) || empty( $_POST ) ) return;
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
			if ( empty($_POST['fep_nonce']) || ! wp_verify_nonce( $_POST['fep_nonce'], 'fep_nonce' ) ) return;
			if ( wp_is_post_revision( $announcement ) ) return;
			if ( wp_is_post_autosave( $announcement ) ) return;
			//if ( ! current_user_can( 'edit_pages' ) ) return;
			if ( ! current_user_can( 'edit_page', $announcement_id ) && ! current_user_can( 'delete_page', $announcement_id ) ) return;
			//if ( $announcement->post_type != 'fep_announcement' ) return;
	
			do_action( 'fep_save_announcement', $announcement_id, $announcement, $update );
		}

	function restrict_editing( $allcaps, $cap, $args ) {

    // Bail out if we're not asking to edit a post ...
    if( 'edit_post' != $args[0] 
      // ... or user already cannot edit the post
      || empty( $allcaps['edit_posts'] ) )
        return $allcaps;

    // Load the post data:
    $post = get_post( $args[2] );

    // Bail out if the post isn't published or not message
    if( 'publish' != $post->post_status || 'fep_message' != $post->post_type )
        return $allcaps;

        //Then disallow editing.
        $allcaps[$cap[0]] = false;
		
    return $allcaps;
}

function view_link($actions, $post)
{
    if ($post->post_type=='fep_message')
    {
        $actions['fep_view'] = '<a href="'.fep_query_url('viewmessage', array( 'id' => $post->ID ) ).'" title="" target="_blank">View</a>';
    }
    return $actions;
}

function columns_head($defaults) {
	$defaults['author'] = __('From', 'front-end-pm');
	$defaults['participants'] = __('To', 'front-end-pm');
    $defaults['parent'] = __('Parent', 'front-end-pm');
    return $defaults;
}
function columns_content($column_name, $post_ID) {
	global $post;
	
    if ($column_name == 'parent') {
        $parent = $post->post_parent;
		
		if( $parent ) {
			echo '<a href="'.fep_query_url('viewmessage', array( 'id' => $parent ) ).'" title="" target="_blank">View</a>';
		} else {
			_e('No Parent', 'front-end-pm');
		}
    }
	if ($column_name == 'participants') {
        $participants = get_post_meta($post_ID, '_participants' );
		
		if( $participants ) {
			foreach( $participants as $participant ) {
			
				if( $participant != $post->post_author )
				echo '<a href="'. get_edit_user_link( $participant ) .'" target="_blank">'. esc_attr( fep_get_userdata( $participant, 'display_name', 'ID' ) ) .'</a><br />';
			}
		} else {
		_e('No Participants', 'front-end-pm');
		}
    }
}
function sortable_column( $columns ) {
    $columns['parent'] = 'parent';
 
    return $columns;
}
function sortable_orderby( $query ) {
    if( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type') != 'fep_message' )
        return;
 
    $orderby = $query->get( 'orderby');
 
    if( 'parent' == $orderby ) {
        //$query->set('meta_key','_fep_parent_id');
        //$query->set('orderby','meta_value_num');
		//$query->set('orderby','parent');
    }
}

function announcement_columns_head($defaults) {
	$defaults['to'] = __('To', 'front-end-pm');
	$defaults['read_count'] = __('Read Count', 'front-end-pm');
	$defaults['deleted_count'] = __('Deleted Count', 'front-end-pm');
    return $defaults;
}

function announcement_columns_content($column_name, $post_ID) {
	
	if ($column_name == 'to') {
		global $wp_roles;
		
       $roles = get_post_meta( $post_ID, '_participant_roles' );
	
		if( $roles && is_array( $roles ) ) {
			foreach( $roles as $role ) {
				 echo translate_user_role( $wp_roles->roles[ $role ]['name'] ) .'<br />';
			}
		}
    }
    if ($column_name == 'read_count') {
       $read_by = get_post_meta( $post_ID, '_fep_read_by', true );
	
		if( ! is_array( $read_by ) ) {
			$read_by = array();
		}
		echo count( $read_by );
    }
	if ($column_name == 'deleted_count') {
       $deleted_by = get_post_meta( $post_ID, '_fep_deleted_by', true );
	
		if( ! is_array( $deleted_by ) ) {
			$deleted_by = array();
		}
		echo count( $deleted_by );
    }
	
}

/**
 * Redirect to the edit.php on post save or publish.
 */
function redirect_post_location( $location, $post_id ) {

    if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
		$post_type = get_post_type( $post_id );
		
		if ( 'fep_message' == $post_type )
        return admin_url( "edit.php?post_type=fep_message" );
		
		if ( 'fep_announcement' == $post_type )
        return admin_url( "edit.php?post_type=fep_announcement" );
    }

    return $location;
}
}

add_action('init', array( Fep_Cpt::init(), 'actions_filters'), 5);
