<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


class Fep_Ajax
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
			add_action('wp_ajax_fep_autosuggestion_ajax', array($this, 'fep_autosuggestion_ajax' ) );
			add_action('wp_ajax_fep_users_ajax', array($this, 'fep_users_ajax' ) );
			add_action('wp_ajax_fep_notification_ajax', array($this, 'fep_notification_ajax' ) );
			add_action('wp_ajax_nopriv_fep_notification_ajax', array($this, 'fep_notification_ajax' ) );
			
			if ( fep_get_option( 'block_other_users', 1 ) ) {
				add_action('wp_ajax_fep_block_unblock_users_ajax', array($this, 'fep_block_unblock_users_ajax' ) );
			}
    	}

	function fep_autosuggestion_ajax() {
		global $user_ID;
		
		if( !fep_get_option('show_autosuggest', 1) && !fep_is_user_admin() )
			die();
		
		if ( check_ajax_referer( 'fep-autosuggestion', 'token', false )) {
		
			$searchq = $_POST['searchBy'];
			
			$args = array(
					'search' => "*{$searchq}*",
					'search_columns' => array( 'display_name' ),
					'exclude' => array( $user_ID ),
					'number' => 5,
					'orderby' => 'display_name',
					'order' => 'ASC',
					'fields' => array( 'ID', 'display_name', 'user_nicename' )
			);
			
			if( strlen($searchq) > 0 )
			{
				$args = apply_filters ('fep_autosuggestion_arguments', $args );
	
				// The Query
				$users = get_users( $args );
			
				echo "<ul>";
				if ( ! empty( $users ) )
				{
					foreach( $users as $user)
					{	
						$display = apply_filters( 'fep_autosuggestion_user_name', $user->display_name, $user->ID );
						
						?><li><a href="#" onClick="fep_fill_autosuggestion('<?php echo $user->user_nicename; ?>','<?php echo $display; ?>');return false;"><?php echo $display; ?></a></li><?php
					}
				} else {
					echo "<li>".__("No matches found", 'front-end-pm')."</li>";
				}
				echo "</ul>";
			}
		}
		die();
	}
	
	function fep_users_ajax() {
		global $user_ID;
		
		if ( check_ajax_referer( 'fep_users_ajax', 'token', false )) {
		
		$searchq = $_POST['q'];
		$exclude = empty( $_POST['x'] ) ? array() : explode( ',', $_POST['x']);
		$exclude[] = $user_ID;
		
		
		$args = array(
			'search' => "*{$searchq}*",
			'search_columns' => array( 'display_name' ),
			'exclude' => $exclude,
			'number' => 10,
			'orderby' => 'display_name',
			'order' => 'ASC',
			'fields' => array( 'ID', 'display_name' )
		);
		
		$ret = array();
			
		if( strlen($searchq) > 0 )
		{
			$args = apply_filters ('fep_users_ajax_arguments', $args );
		
			// The Query
			$users = get_users( $args );
		
			foreach( $users as $user)
			{
				$ret[] = array(
						'id'	=> $user->ID,
						'name'	=>  $user->display_name
					);
			}
		}
		
		wp_send_json( $ret );
		}
		die;
	}
	
	function fep_block_unblock_users_ajax(){
		if ( check_ajax_referer( 'fep-block-unblock-script', 'token', false ) && ! empty( $_POST['user_id'] ) ) {
			$user_id =  absint( $_POST['user_id'] );
			if( fep_is_user_blocked_for_user( get_current_user_id(), $user_id ) ){
				fep_unblock_users_for_user( $user_id );
				$return = __("Block", "front-end-pm");
			} else {
				fep_block_users_for_user( $user_id );
				$return = __("Unblock", "front-end-pm");
			}

			wp_die( $return );
		}
		$return = __("Failed", "front-end-pm");
		wp_die( $return );
	}
	
	function fep_notification_ajax() {

		if ( check_ajax_referer( 'fep-notification', 'token', false )) {
			$mgs_unread_count 		= fep_get_new_message_number();
			$mgs_total_count 		= fep_get_user_message_count( 'total' );
			$ann_unread_count 		= fep_get_new_announcement_number();
			
			$ret = array(
				'message_unread_count'				=> $mgs_unread_count,
				'message_unread_count_i18n'			=> number_format_i18n( $mgs_unread_count ),
				'message_unread_count_text'			=> sprintf(_n('%s message', '%s messages', $mgs_unread_count, 'front-end-pm'), number_format_i18n($mgs_unread_count) ),
				//'message_total_count'				=> $mgs_total_count,
				'message_total_count_i18n'			=> number_format_i18n( $mgs_total_count ),
				'announcement_unread_count'			=> $ann_unread_count,
				'announcement_unread_count_i18n'	=> number_format_i18n( $ann_unread_count ),
				'announcement_unread_count_text'	=> sprintf(_n('%s announcement', '%s announcements', $ann_unread_count, 'front-end-pm'), number_format_i18n($ann_unread_count) ),
			);
			wp_send_json( $ret );
		}
		die;
	}
	
  } //END CLASS

add_action('init', array(Fep_Ajax::init(), 'actions_filters'));

