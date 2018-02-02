<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Attachment
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
	//add_action ('fep_display_after_parent_message', array($this, 'display_attachment'));
	add_action ('fep_display_after_message', array($this, 'display_attachment'));
	add_action ('fep_display_after_announcement', array($this, 'display_attachment'));
	add_action('template_redirect', array($this, 'download_file' ) );
	
	add_action ('before_delete_post', array($this, 'delete_attachments') );
	
	if ( '1' == fep_get_option('allow_attachment', 1)) {
		add_action ('fep_action_message_after_send', array($this, 'upload_attachment'), 10, 3 );
		add_action ('fep_action_announcement_after_added', array($this, 'upload_attachment'), 10, 3 );
		}
    }
	
	
	function upload_attachment( $message_id, $message, $inserted_message ) {
		$field = 'fep_upload';
		
	    if ( !isset( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) {
	        return false;
	    }
		if ( empty( $_FILES[ $field ]['tmp_name'] ) || ! is_array( $_FILES[ $field ]['tmp_name'] ) ) {
	        return false;
	    }
		add_filter('upload_dir', array($this, 'upload_dir'), 99 );
		
	    $fields = (int) fep_get_option('attachment_no', 4);
		
		$i = 0;
	    foreach( $_FILES[ $field ]['tmp_name'] as $key => $tmp_name ) {

	        if ( $tmp_name ) {
	            $upload = array(
	                'name' 		=> $_FILES[ $field ]['name'][ $key ],
	                'type' 		=> $_FILES[ $field ]['type'][ $key ],
	                'tmp_name' 	=> $_FILES[ $field ]['tmp_name'][ $key ],
	                'error' 	=> $_FILES[ $field ]['error'][ $key ],
	                'size' 		=> $_FILES[ $field ]['size'][ $key ]
	            );

	            $this->upload_file( $upload, $message_id, $inserted_message );
				
				if( ++$i >= $fields )
					break;
					
	        }//file exists
	    }// end foreach
			
		remove_filter('upload_dir', array($this, 'upload_dir'), 99 );
	}

	function upload_dir($upload) {
	/* Append year/month folders if that option is set */
		$subdir = '';
        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
                $time = current_time( 'mysql' );

            $y = substr( $time, 0, 4 );
            $m = substr( $time, 5, 2 );

            $subdir = "/$y/$m";    
        }
	$upload['subdir']	= '/front-end-pm' . $subdir;
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
	}

/**
 * Generic function to upload a file
 *
 * @since 3.3
 * @param array $upload_data
 * @param int $message_id
 * @return bool
 */
function upload_file( $upload_data, $message_id, $inserted_message ) {

	if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
    $movefile = wp_handle_upload( $upload_data, array('test_form' => false) );

    if ($message_id && !empty($movefile['type']) && $movefile['url'] && $movefile['file']) {
		
		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $movefile['url'], 
			'post_mime_type' => $movefile['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['url'] ) ),
			'post_content'   => '',
			'post_author'	=> $inserted_message->post_author,
			'post_status'    => 'inherit'
		);
		
		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $movefile['file'], $message_id );
		
		if ( $attach_id )
        return true;
    }

    return false;
}

	function display_attachment() {
	
	$attachment_ids = fep_get_attachments( get_the_ID(), 'ids');
	
	if ( $attachment_ids ) {
		  echo "<hr /><strong>" . __("Attachments", 'front-end-pm') . ":</strong><br />";
		  foreach ( $attachment_ids as $attachment_id ){
		  
			$name = esc_html( basename(wp_get_attachment_url( $attachment_id )) );
		
			echo "<a href='".fep_query_url('download', array( 'fep_id' => $attachment_id, 'token' => wp_create_nonce('download_' . $attachment_id ) ))."' title='". sprintf(__( 'Download %s', 'front-end-pm'), $name ) . "'>{$name}</a><br />";
				} 
			}
		}

	function download_file(){
	
		if ( empty($_GET['fepaction']) || $_GET['fepaction'] != 'download' )
			return;
		
		if( isset( $_GET['fep_id'] ) ){
			$id = absint( $_GET['fep_id'] );
		} else {
			$id = !empty($_GET['id']) ? absint($_GET['id']) : 0;
		}

		$token = ! empty( $_GET['token'] ) ? $_GET['token'] : '';
	
		if ( ! $id || ! wp_verify_nonce( $token, 'download_' . $id ) )
		wp_die(__('Invalid token', 'front-end-pm'));
		
		if ( !fep_current_user_can( 'access_message' ) )
		wp_die(__('No attachments found', 'front-end-pm'));
	
		if ( 'attachment' != get_post_type( $id ) || 'publish' != get_post_status ( $id ) )
		wp_die(__('No attachments found', 'front-end-pm'));
	
		if( 'threaded' == fep_get_message_view() ) {
			$message_id = fep_get_parent_id($id);
		} else {
			$message_id = wp_get_post_parent_id($id);
		}
		$post_type = get_post_type($message_id);
		
		if( ! in_array( $post_type, array( 'fep_message', 'fep_announcement' ) ) ) {
			wp_die(__('You have no permission to download this attachment.', 'front-end-pm'));
		} elseif( 'fep_message' == $post_type && ! fep_current_user_can('view_message', $message_id ) ) {
			wp_die(__('You have no permission to download this attachment.', 'front-end-pm'));
		} elseif( 'fep_announcement' == $post_type && ! fep_current_user_can('view_announcement', $message_id ) ) {
			wp_die(__('You have no permission to download this attachment.', 'front-end-pm'));
		}
			  
	
			$attachment_type = get_post_mime_type( $id );
			$attachment_url = wp_get_attachment_url( $id );
			$attachment_path = get_attached_file( $id );
			$attachment_name = basename($attachment_url);
	
		if( !file_exists($attachment_path) ){
			wp_delete_attachment( $id );
			wp_die(__('Attachment already deleted', 'front-end-pm'));
		}
		
		
			header("Content-Description: File Transfer");
			header("Content-Transfer-Encoding: binary");
			header("Content-Type: $attachment_type", true, 200);
			header("Content-Disposition: attachment; filename=\"$attachment_name\"");
			header("Content-Length: " . filesize($attachment_path));
			nocache_headers();
			
			//clean all levels of output buffering
			while (ob_get_level()) {
				ob_end_clean();
			}
			
			readfile($attachment_path);
			
				exit;
		}
		
	
	function delete_attachments( $message_id ) {

		if( ! in_array( get_post_type( $message_id ), array( 'fep_message', 'fep_announcement' ) ) )
			return false;
		
		$attachment_ids = fep_get_attachments( $message_id, 'ids' );
			
		if ( $attachment_ids ) {
		  foreach ( $attachment_ids as $attachment_id ){
			wp_delete_attachment( $attachment_id ); 
		
			} 
		}
   }

	
	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Attachment::init(), 'actions_filters'));
