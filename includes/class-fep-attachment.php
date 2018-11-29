<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Attachment {
	private static $instance;
	public $icons;
	
	private function __construct(){
		$this->icons = apply_filters( 'fep_filter_attachment_icons',
			array(
				'code' => 'c|cc|h|js|class', 
				'xml' => 'xml', 
				'excel' => 'xla|xls|xlsx|xlt|xlw|xlam|xlsb|xlsm|xltm', 
				'word' => 'docx|dotx|docm|dotm', 
				'image' => 'png|gif|jpg|jpeg|jpe|jp|bmp|tif|tiff', 
				'psd' => 'psd', 
				'ai' => 'ai', 
				'archive' => 'zip|rar|gz|gzip|tar|7z',
				'text' => 'txt|asc|nfo', 
				'powerpoint' => 'pot|pps|ppt|pptx|ppam|pptm|sldm|ppsm|potm', 
				'pdf' => 'pdf', 
				'html' => 'htm|html|css', 
				'video' => 'avi|asf|asx|wax|wmv|wmx|divx|flv|mov|qt|mpeg|mpg|mpe|mp4|m4v|ogv|mkv', 
				'documents' => 'odt|odp|ods|odg|odc|odb|odf|wp|wpd|rtf',
				'audio' => 'mp3|m4a|m4b|wav|ra|ram|ogg|oga|mid|midi|wma|mka',
				'icon' => 'ico',
			)
		);
	}
	
	public static function init() {
		if( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	function actions_filters() {
		add_action( 'fep_display_after_message', array( $this, 'display_attachment' ), 99 );
		add_action( 'fep_display_after_announcement', array( $this, 'display_attachment' ), 99 );
		add_action( 'template_redirect', array( $this, 'download_file' ) );
		
		if ( fep_get_option( 'allow_attachment', 1 ) ) {
			add_action( 'fep_action_message_after_send', array( $this, 'upload_attachment' ), 10, 3 );
			add_action( 'fep_action_announcement_after_added', array( $this, 'upload_attachment' ), 10, 3 );
		}
	}
	
	
	function upload_attachment( $message_id, $message, $inserted_message ) {

		$field = 'fep_upload';
		
		if ( ! $message_id || ! isset( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) {
			return false;
		}
		if ( empty( $_FILES[ $field ]['tmp_name'] ) || ! is_array( $_FILES[ $field ]['tmp_name'] ) ) {
			return false;
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
		
		add_filter( 'upload_dir', array( $this, 'upload_dir' ), 99 );
		
		$fields = (int) fep_get_option( 'attachment_no', 4 );
		$i = 0;
		$attachments = array();
		
		foreach( $_FILES[ $field ]['tmp_name'] as $key => $tmp_name ) {
			if ( $tmp_name ) {
				$upload = array(
					'name' 		=> $_FILES[ $field ]['name'][ $key ],
					'type' 		=> $_FILES[ $field ]['type'][ $key ],
					'tmp_name' 	=> $_FILES[ $field ]['tmp_name'][ $key ],
					'error' 	=> $_FILES[ $field ]['error'][ $key ],
					'size' 		=> $_FILES[ $field ]['size'][ $key ],
				);
				if( ( $movefile = wp_handle_upload( $upload, array( 'test_form' => false ) ) ) && ! empty( $movefile['file'] ) ){
					$attachments[] = array(
						'att_mime' => $movefile['type'],
						'att_file' => _wp_relative_upload_path( $movefile['file'] ),
					);
				}
				if( ++$i >= $fields )
					break;
			}
		}
		if( $attachments ){
			$inserted_message->insert_attachments( $attachments );
		}
			
		remove_filter( 'upload_dir', array( $this, 'upload_dir' ), 99 );
	}

	function upload_dir( $upload ) {
		if( is_multisite() ){
			$siteurl = get_option( 'siteurl' );
			$upload_path = trim( get_option( 'upload_path' ) );
		 
			if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
				$dir = WP_CONTENT_DIR . '/uploads';
			} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
				// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
				$dir = path_join( ABSPATH, $upload_path );
			} else {
				$dir = $upload_path;
			}
		 
			if ( !$url = get_option( 'upload_url_path' ) ) {
				if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
					$url = WP_CONTENT_URL . '/uploads';
				else
					$url = trailingslashit( $siteurl ) . $upload_path;
			}
			$upload['basedir'] = $dir;
			$upload['baseurl'] = $url;
		}
		
		$upload['subdir']	= '/front-end-pm' . $upload['subdir'];
		$upload['path']		= $upload['basedir'] . $upload['subdir'];
		$upload['url']		= $upload['baseurl'] . $upload['subdir'];
		
		return $upload;
	}

	function display_attachment( $i ) {
		$message = fep_get_current_message();
		if( ! $message ){
			return '';
		}
	
		if ( $attachments = $message->get_attachments() ) {
			echo '<div class="fep-attachments">';
			echo '<div class="fep-attachments-heading">' . _n( 'Attachment', 'Attachments', count( $attachments ), 'front-end-pm' ) . '</div>';
			
			foreach ( $attachments as $attachment ){
				if( 'publish' != $attachment->att_status ){
					continue;
				}
				echo '<div class="fep-attachment fep-attachment-' . $attachment->att_id . '">';
				$name = basename( $attachment->att_file );
				
				echo apply_filters( 'fep_filter_attachment_icon', '<span class="fep-attachment-icon fep-attachment-icon-' . $this->icon( $attachment->att_file ) . '"></span>', $attachment->att_id, $attachment );
				
				echo apply_filters( 'fep_filter_attachment_download_link', '<a href="' . fep_query_url( 'download', array( 'fep_id' => $attachment->att_id, 'fep_parent_id' => $attachment->mgs_id ) ) . '" title="' . sprintf( __( 'Download %s', 'front-end-pm' ), esc_attr( $name ) ) . '">' . esc_html( $name ) . '</a>', $attachment->att_id, $attachment );
				
				echo '</div>';
			}
			echo '</div>';
		}
	}
	
	function icon( $file ){
		$ext = pathinfo( $file, PATHINFO_EXTENSION );
		
		foreach ( $this->icons as $icon => $extensions ) {
			$extensions = explode( '|', $extensions );
			
			if( in_array( $ext, $extensions ) ){
				return $icon;
			}
		}
		return 'default';
	}

	function download_file(){
	
		if ( ! isset( $_GET['fepaction'] ) || ! in_array( $_GET['fepaction'], [ 'download', 'view-download' ] ) ){
			return false;
		}
		$id = isset( $_GET['fep_id'] ) ? absint( $_GET['fep_id'] ) : 0;
		$mgs_id = isset( $_GET['fep_parent_id'] ) ? absint( $_GET['fep_parent_id'] ) : 0;

		$token = ! empty( $_GET['token'] ) ? $_GET['token'] : '';
	
		if ( ! $id || ! $mgs_id ){
			wp_die(__( 'No attachments found', 'front-end-pm' ) );
		}
		
		if ( ! fep_current_user_can( 'access_message' ) ){
			wp_die(__( 'No attachments found', 'front-end-pm' ) );
		}
	
		if ( 'publish' != fep_get_message_status( $mgs_id ) && ! fep_is_user_admin() ){
			wp_die(__( 'No attachments found', 'front-end-pm' ) );
		}
		$attachment = FEP_Attachments::init()->get( $mgs_id, $id, 'any' );
		
		if( ! $attachment || ( 'publish' != $attachment->att_status && ! fep_is_user_admin() ) ){
			wp_die(__( 'No attachments found', 'front-end-pm' ) );
		}

		$message_type = fep_get_message_field( 'mgs_type', $mgs_id );
		
		if( 'message' == $message_type && ! fep_current_user_can( 'view_message', $mgs_id ) ) {
			wp_die(__( 'You have no permission to download this attachment.', 'front-end-pm' ) );
		} elseif( 'announcement' == $message_type && ! fep_current_user_can( 'view_announcement', $mgs_id ) ) {
			wp_die(__( 'You have no permission to download this attachment.', 'front-end-pm' ) );
		}
		
		$attachment_path = $this->absulate_path( $attachment->att_file );
		$attachment_name = basename( $attachment_path );
	
		if( ! file_exists( $attachment_path ) ){
			//wp_delete_attachment( $id );
			wp_die( __( 'Attachment already deleted', 'front-end-pm' ) );
		}
		
		if( 'download' == $_GET['fepaction'] ){
			header( "Content-Description: File Transfer" );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Disposition: attachment; filename=\"$attachment_name\"" );
			nocache_headers();
		}
		
		header( "Content-Type: {$attachment->att_mime}", true, 200 );
		header( "Content-Length: " . filesize( $attachment_path ) );
		
		//clean all levels of output buffering
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		readfile( $attachment_path );
		exit;
	}
	
	function absulate_path( $file ){
		// If the file is relative, prepend upload dir.
		add_filter( 'upload_dir', array( $this, 'upload_dir' ), 99 );
		
		if ( $file && 0 !== strpos( $file, '/' ) && ! preg_match( '|^.:\\\|', $file ) && ( ( $uploads = wp_get_upload_dir() ) && false === $uploads['error'] ) ) {
			$file = $uploads['basedir'] . "/$file";
		}
		remove_filter( 'upload_dir', array( $this, 'upload_dir' ), 99 );
		
		return $file;
	}
} //END CLASS

add_action( 'wp_loaded', array( Fep_Attachment::init(), 'actions_filters' ) );
