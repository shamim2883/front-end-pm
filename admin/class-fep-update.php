<?php

class Fep_Update
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
		add_filter('fep_admin_settings_tabs', array($this, 'sections'));
		add_filter('fep_settings_fields', array($this, 'fields'));
		add_action('fep_admin_settings_field_output_update', array($this, 'field_output'));
		add_action('wp_ajax_fep_update_ajax', array($this, 'ajax'));
    }
	
	function sections( $tabs)
	{
		$tabs['update'] =  array(
									'priority'			=> 35,
									'tab_title'			=> __('Update', 'front-end-pm'),
									);
		return $tabs;
		
	}
	
	function fields( $fields)
	{
		$fields['update'] =   array(
									'type'	=>	'update',
									'section'	=> 'update',
									'label' => __( 'Update', 'front-end-pm' )
									);
		return $fields;
		
	}
	
	function field_output( $field ){
		
		global $wpdb;

		if( $wpdb->get_var("SHOW TABLES LIKE '". FEP_MESSAGES_TABLE . "'") != FEP_MESSAGES_TABLE ) {
			_e('You are up to date, No need to update.', 'front-end-pm' );
			return;
		}
		if( ! $wpdb->get_var("SELECT COUNT(*) FROM " . FEP_MESSAGES_TABLE . " WHERE id IS NOT NULL LIMIT 1") ) {
			$wpdb->query( "DROP TABLE IF EXISTS ".FEP_MESSAGES_TABLE );
			$wpdb->query( "DROP TABLE IF EXISTS ".FEP_META_TABLE );
			_e('You are up to date, No need to update.', 'front-end-pm' );
			return;
		}
		wp_enqueue_script( 'fep_update_script'); ?>
		
		<form id="fep_update_form" action="" method="post">
		<div class="form">
			<div id="fep-prev-version-div">
			<label for="fep-prev-version"><?php _e('Previous version', 'front-end-pm' ); ?></label>
			<select name="fep-prev-version" id="fep-prev-version">
				<option value="31"><?php _e('3.1', 'front-end-pm' ); ?></option>
				<option value="32"><?php _e('3.2', 'front-end-pm' ); ?></option>
				<option value="33"><?php _e('3.3', 'front-end-pm' ); ?></option>
			</select>
			</div>
			<div id="fep-submit_button">
			 <p class="submit"><button id="fep-update-button" class="button button-secondary"><?php _e('Update', 'front-end-pm' ); ?></button>
			 <img src="<?php echo FEP_PLUGIN_URL; ?>assets/images/loading.gif" class="fep-ajax-img" style="display:none;"/></p>
			</div>
			<div id="fep-ajax-response"></div>
			<div>
			</div>
		</div>
		</form><?php 
		
	}
	
	function ajax(){
		global $wpdb;
		
		check_ajax_referer( 'fep_settings-options' );
		
		$prev_version = ! empty( $_POST['fep-prev-version'] ) ? absint($_POST['fep-prev-version']) : '';
		
		$messages = $this->get_messages();
		$total = count($messages);
		
		$announcements = $this->get_announcements();
		
		$output['success'] = 0;
		
		if( ! $total && ! $announcements ) {
		
		//Delete Table
		$wpdb->query( "DROP TABLE IF EXISTS ".FEP_MESSAGES_TABLE );
		$wpdb->query( "DROP TABLE IF EXISTS ".FEP_META_TABLE );
			
		$output['message'] = __('You are up to date, No need to update.', 'front-end-pm' );

		echo wp_json_encode( $output );
		wp_die();
		}
		
		$count = 0;
		if( $messages ) {
		foreach ( $messages as $message ) {
			$arr = array(
					'message_title'	=> $message->message_title,
					'message_content'	=> $message->message_contents,
					'fep_parent_id'	=> 0,
					'message_to_id'	=> $message->to_user
				);
			$override = array(
					'post_author'	=> $message->from_user,
					'post_date'	=> $message->send_date
				);
			
			if( $message_id = fep_send_message( $arr, $override ) ) {
				if( 1 == $message->status ) {
					fep_make_read( true, $message_id, $message->to_user );
				}
				if( 1 == $message->from_del ) {
					add_post_meta( $message_id, '_fep_delete_by_'. $message->from_user, time(), true ); //No time from previous version
				} elseif( 1 == $message->to_del ) {
					add_post_meta( $message_id, '_fep_delete_by_'. $message->to_user, time(), true );
				}
				
				$this->insert_attachment( $message_id, $message->id );
				$this->insert_replies( $message_id, $message->id );
				
				$this->delete_message( $message->id );
				
				 ++$count;
			}
		}
		}
		if( $announcements ) {
		
		foreach( $announcements as $announcement ) {
		
			$arr = array(
					'post_title'	=> $announcement->message_title,
					'post_content'	=> $announcement->message_contents,
					'post_author'	=> $announcement->from_user,
					'post_date'	=> $announcement->send_date,
					'post_type'	=> 'fep_announcement',
					'post_status'    => 'publish'
				);
			if( ! $ann_id = wp_insert_post($arr) )
				continue;
			
			$this->insert_attachment( $ann_id, $announcement->id );

			foreach( array_keys( get_editable_roles() ) as $role ) {
				add_post_meta( $ann_id, '_participant_roles', $role );
			}
			
			$seen = $this->get_announcement_meta( $announcement->id );
			$seen = maybe_unserialize( $seen );
			
			if( $seen && is_array($seen) ) {
				add_post_meta( $ann_id, '_fep_read_by', $seen, true );
			}
			
			$deleted = $this->get_announcement_meta( $announcement->id, 'announcement_deleted_user_id' );
			$deleted = maybe_unserialize( $deleted );
			
			if( $deleted && is_array($deleted) ) {
				foreach( $deleted as $del ) {
					add_post_meta( $ann_id, '_fep_delete_by_'. $del, time(), true );
				}
			}
			
			$this->delete_message( $announcement->id );
			
		}
		}
		delete_metadata( 'user', 0, '_fep_user_announcement_count', '', true );
		
		if( $count == $total ) {
			//Delete Table
			$wpdb->query( "DROP TABLE IF EXISTS ".FEP_MESSAGES_TABLE );
			$wpdb->query( "DROP TABLE IF EXISTS ".FEP_META_TABLE );
			
			$output['success'] = 1;
			$output['message'] = sprintf(__('Successfully updated.', 'front-end-pm' ), $count, $total);
		
		} else {
			$output['message'] = __('Please refresh this page and update again.', 'front-end-pm' );
		}
		
		echo wp_json_encode( $output );
		wp_die();
	}
	
	function insert_attachment( $message_id, $message_prev_id )
	{
		if( ! $attachments = $this->get_attachments( $message_prev_id ) )
		return;
		
		foreach( $attachments as $attachment ) {
			$unserialized_file = maybe_unserialize( $attachment->field_value );
			if ( $unserialized_file['type'] && $unserialized_file['url'] && $unserialized_file['file'] ) {
				// Prepare an array of post data for the attachment.
				$att = array(
					'guid'           => $unserialized_file['url'], 
					'post_mime_type' => $unserialized_file['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $unserialized_file['url'] ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				
				// Insert the attachment.
				wp_insert_attachment( $att, $unserialized_file['file'], $message_id );
			}
		}
		
	}
	function insert_replies( $message_id, $message_prev_id )
	{
		if( ! $replies = $this->get_replies( $message_prev_id ) )
		return;
		
		foreach( $replies as $reply ) {
			$arr = array(
					'message_title'	=> $reply->message_title,
					'message_content'	=> $reply->message_contents,
					'fep_parent_id'	=> $message_id
				);
			$override = array(
					'post_author'	=> $reply->from_user,
					'post_date'	=> $reply->send_date
				);
			
			if( $reply_id = fep_send_message( $arr, $override ) ) {
				$this->insert_attachment( $reply_id, $reply->id );
				
				$this->delete_message( $reply->id );
			}
			
		}
		
	}
	
	function delete_message( $message_id )
    {	global $wpdb;
 
 		$wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $message_id ));
		$wpdb->query($wpdb->prepare("DELETE FROM ".FEP_META_TABLE." WHERE message_id = %d", $message_id ));

    }
	
	function get_messages()
    {	global $wpdb;
 
	 return $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE parent_id = %d AND (status = 0 OR status = 1) ORDER BY last_date DESC", 0 ));

    }
	function get_replies( $parent )
    {
 		global $wpdb;
	 return $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE parent_id = %d AND (status = 0 OR status = 1) ORDER BY last_date DESC", $parent ));

    }
	function get_attachments( $message_id )
    {
 		global $wpdb;
	 return $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s", $message_id, 'attachment' ));

    }
	function get_announcements()
    {	global $wpdb;
 
	 return $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE status = %d", 2 ));

    }
	function get_announcement_meta( $id, $meta = 'announcement_seen_user_id' )
    {	global $wpdb;
 
	 return $wpdb->get_var($wpdb->prepare("SELECT field_value FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s LIMIT 1", $id, $meta ));

    }
	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Update::init(), 'actions_filters'));

function fep_update_script() {

	wp_register_script( 'fep_update_script', FEP_PLUGIN_URL . 'assets/js/fep_update_script.js', array( 'jquery' ), '3.1', true );
}
add_action( 'admin_enqueue_scripts', 'fep_update_script' );


