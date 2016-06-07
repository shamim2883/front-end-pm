<?php

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
	add_action ('fep_display_after_parent_message', array($this, 'display_attachment'));
	add_action ('fep_display_after_reply_message', array($this, 'display_attachment'));
	add_action ('fep_display_after_announcement', array($this, 'display_attachment'));
	
	add_action ('before_delete_post', array($this, 'delete_attachment') );
	
	if ( '1' == fep_get_option('allow_attachment',0)) {
		add_action ('fep_action_message_after_send', array($this, 'upload_attachment'), 10, 2 );
		}
    }
	
	
function upload_attachment( $message_id, $message ) {
    if ( !isset( $_FILES['fep_upload'] ) ) {
        return false;
    }
	add_filter('upload_dir', array($this, 'upload_dir'));
	
    $fields = (int) fep_get_option('attachment_no', 4);

    for ($i = 0; $i < $fields; $i++) {
        $tmp_name = isset( $_FILES['fep_upload']['tmp_name'][$i] ) ? basename( $_FILES['fep_upload']['tmp_name'][$i] ) : '' ;

            if ( $tmp_name ) {
                $upload = array(
                    'name' => $_FILES['fep_upload']['name'][$i],
                    'type' => $_FILES['fep_upload']['type'][$i],
                    'tmp_name' => $_FILES['fep_upload']['tmp_name'][$i],
                    'error' => $_FILES['fep_upload']['error'][$i],
                    'size' => $_FILES['fep_upload']['size'][$i]
                );

                $this->upload_file( $upload, $message_id);
            }//file exists
        }// end for
		
	remove_filter('upload_dir', array($this, 'upload_dir'));
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
function upload_file( $upload_data, $message_id ) {

	if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
    $movefile = wp_handle_upload( $upload_data, array('test_form' => false) );

    if ($message_id && $movefile['type']&& $movefile['url'] && $movefile['file']) {
		
		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $movefile['url'], 
			'post_mime_type' => $movefile['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['url'] ) ),
			'post_content'   => '',
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
	
	$attachments = fep_get_attachments();
	$token = fep_create_nonce('download');
	
	if ($attachments) {
		  echo "<hr /><strong>" . __("Attachments", 'front-end-pm') . ":</strong><br />";
		  foreach ($attachments as $attachment){
		  
		$attachment_id = $attachment->ID;
		$name = esc_html( basename(wp_get_attachment_url( $attachment_id )) );
		
			echo "<a href='".fep_query_url('download', array( 'id' => $attachment_id, 'token' => $token ))."' title='Download {$name}'>{$name}</a><br />";
				} 
			}
		}
		
	
	function delete_attachment( $message_id ) {

		if( ! in_array( get_post_type( $message_id ), array( 'fep_message', 'fep_announcement' ) ) )
			return false;
		
		$attachments = fep_get_attachments( $message_id );
			
		if ($attachments) {
		  foreach ($attachments as $attachment){
			wp_delete_attachment($attachment->ID); 
		
			} 
		}
   }

	
	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Attachment::init(), 'actions_filters'));
?>