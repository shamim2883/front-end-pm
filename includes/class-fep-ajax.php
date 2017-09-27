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
			add_action('wp_ajax_fep_notification_ajax', array($this, 'fep_notification_ajax' ) );
			add_action('wp_ajax_nopriv_fep_notification_ajax', array($this, 'fep_notification_ajax' ) );
    	}

	function fep_autosuggestion_ajax() {
		global $user_ID;
		
		if(fep_get_option('hide_autosuggest') == '1' && !fep_is_user_admin() )
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
	
	function fep_notification_ajax() {

		if ( check_ajax_referer( 'fep-notification', 'token', false )) {
		
			$notification = fep_notification();
			if ( $notification )
				wp_die( $notification );
		}
		die();
	}
	
  } //END CLASS

add_action('init', array(Fep_Ajax::init(), 'actions_filters'));

