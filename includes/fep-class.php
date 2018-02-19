<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Main CLASS
if (!class_exists("fep_main_class"))
{
  class fep_main_class
  {
    
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	


/******************************************MAIN DISPLAY BEGIN******************************************/

    //Display the proper contents
   function main_shortcode_output( $atts, $content = null )
    {
      global $user_ID;
      if ($user_ID)
      {
	  
	  if ( ! fep_current_user_can('access_message') ){
	  
	  	return apply_filters('fep_main_shortcode_output', '<div class="fep-error">'.__("You do not have permission to access message system", 'front-end-pm').'</div>' );
	  }
	  
	  $atts = shortcode_atts( array(
			'fepaction'		=> 'messagebox',
			'fep-filter'		=> 'show-all',
		), $atts, 'front-end-pm' );
		
		if( empty($_GET['fepaction'] ) )
		$_GET['fepaction'] = $atts['fepaction'];
		
		if( $_GET['fepaction'] == $atts['fepaction'] && empty($_GET['fep-filter'] ) )
			$_GET['fep-filter'] = $atts['fep-filter'];
	  
        //Add header
        $out = $this->Header();

        //Add Menu
        $out .= $this->Menu();
		$menu = Fep_Menu::init()->get_menu();
		
        //Start the guts of the display
		$switch = ( isset($_GET['fepaction'] ) && $_GET['fepaction'] ) ? $_GET['fepaction'] : 'messagebox';
		
        switch ($switch)
        {
		case has_action("fep_switch_{$switch}"):
			ob_start();
			do_action("fep_switch_{$switch}");
			$out .= ob_get_contents();
			ob_end_clean();
			break;
		case has_filter("fep_filter_switch_{$switch}"):
			$out .= apply_filters( "fep_filter_switch_{$switch}", '');
			break;
         case ( 'newmessage' == $switch && ! empty( $menu['newmessage'] ) ):
            $out .= $this->new_message();
            break;
          case 'viewmessage':
            $out .= $this->view_message();
            break;
			/*
			// See Fep_User_Settings Class
          case ( 'settings' == $switch && ! empty( $menu['settings'] ) ):
            $out .= $this->user_settings();
            break;
			*/
			/*
			// See Fep_Announcement Class
		case 'announcements':
            $out .= Fep_Announcement::init()->announcement_box();
            break;
		case 'view_announcement':
            $out .= Fep_Announcement::init()->view_announcement();
            break;
			*/
		//case 'directory': // See Fep_Directory Class
            //$out .= $this->directory();
           // break;
		case 'messagebox':
          default: //Message box is shown by Default
            $out .= $this->fep_message_box();
            break;
        }

        //Add footer
        $out .= $this->Footer();
      }
      else
      { 
        $out = "<div class='fep-error'>".sprintf(__("You must <a href='%s'>login</a> to view your message.", 'front-end-pm'), wp_login_url( get_permalink() ) )."</div>";
      }
      return apply_filters('fep_main_shortcode_output', $out);
    }
	
	function Posted()
	{
		_deprecated_function( __FUNCTION__, '4.9', 'fep_form_posted()' );
		
		$action = !empty($_POST['fep_action']) ? $_POST['fep_action'] : '';
		
		if( ! $action )
			return;
			
		switch( $action ) {
			case has_action("fep_posted_action_{$action}"):
				do_action("fep_posted_action_{$action}", $this );
			break;
			case 'newmessage' :
				if ( ! fep_current_user_can( 'send_new_message') )
					return;
				
				Fep_Form::init()->validate_form_field();
				if( count( fep_errors()->get_error_messages()) == 0 ){
					if( $message_id = fep_send_message() ) {
						$message = get_post( $message_id );
						
						if( 'publish' == $message->post_status ) {
							fep_success()->add( 'publish', __("Message successfully sent.", 'front-end-pm') );
						} else {
							fep_success()->add( 'pending', __("Message successfully sent and waiting for admin moderation.", 'front-end-pm') );
						}
					} else {
						fep_errors()->add( 'undefined', __("Something wrong. Please try again.", 'front-end-pm') );
					}
				}
				
			break;
			case 'reply' :
				
				if( isset( $_GET['fep_id'] ) ){
					$pID = absint( $_GET['fep_id'] );
				} else {
					$pID = !empty($_GET['id']) ? absint($_GET['id']) : 0;
				}
				$parent_id = fep_get_parent_id( $pID );
				
				if ( ! fep_current_user_can( 'send_reply', $parent_id ) )
					return;
					
				Fep_Form::init()->validate_form_field( 'reply' );
				if( count( fep_errors()->get_error_messages()) == 0 ){
					if( $message_id = fep_send_message() ) {
						$message = get_post( $message_id );
						
						if( 'publish' == $message->post_status ) {
							fep_success()->add( 'publish', __("Message successfully sent.", 'front-end-pm') );
						} else {
							fep_success()->add( 'pending', __("Message successfully sent and waiting for admin moderation.", 'front-end-pm') );
						}
					} else {
						fep_errors()->add( 'undefined', __("Something wrong. Please try again.", 'front-end-pm') );
					}
				}
				
			break;
			case 'bulk_action' :
				$posted_bulk_action = ! empty($_POST['fep-bulk-action']) ? $_POST['fep-bulk-action'] : '';
				if( ! $posted_bulk_action )
					return;
				
				$token = ! empty($_POST['token']) ? $_POST['token'] : '';
				
				if ( ! fep_verify_nonce( $token, 'bulk_action') ) {
					fep_errors()->add( 'token', __("Invalid Token. Please try again!", 'front-end-pm') );
					return;
				}
				
				if( $bulk_action_return = Fep_Message::init()->bulk_action( $posted_bulk_action ) ) {
					fep_success()->add( 'success', $bulk_action_return );
				}
			break;
			case 'announcement_bulk_action' :
				$posted_bulk_action = ! empty($_POST['fep-bulk-action']) ? $_POST['fep-bulk-action'] : '';
				if( ! $posted_bulk_action )
					return;
				
				$token = ! empty($_POST['token']) ? $_POST['token'] : '';
				
				if ( ! fep_verify_nonce( $token, 'announcement_bulk_action') ) {
					fep_errors()->add( 'token', __("Invalid Token. Please try again!", 'front-end-pm') );
					return;
				}
				
				if( $bulk_action_return = Fep_Announcement::init()->bulk_action( $posted_bulk_action ) ) {
					fep_success()->add( 'success', $bulk_action_return );
				}
			break;
			case 'settings' :
				
				add_action ('fep_action_form_validated', array($this, 'settings_save'), 10, 2);
				
				Fep_Form::init()->validate_form_field( 'settings' );
				
				if( count( fep_errors()->get_error_messages()) == 0 ){
					fep_success()->add( 'saved', __("Settings successfully saved.", 'front-end-pm') );
				}
				
			break;
			default:
				do_action("fep_posted_action", $this );
			break;
			
		}
	}
	
	function settings_save( $where, $fields )
	{
		_deprecated_function( __FUNCTION__, '4.9', 'fep_user_settings_save()' );
		
		if( 'settings' != $where )
			return;
		
		if( !$fields || ! is_array( $fields ) )
			return;
		
		$settings = array();
		
		foreach( $fields as $field ) {
			$settings[$field['name']] = $field['posted-value'];
		}
		$settings = apply_filters('fep_filter_user_settings_before_save', $settings );
		
		update_user_option( get_current_user_id(), 'FEP_user_options', $settings); 
	}
	
    function Header()
    {
      global $user_ID;

      $total_count = fep_get_user_message_count( 'total' );
	  $unread_count = fep_get_user_message_count( 'unread' );
	  $unread_ann_count = fep_get_user_announcement_count( 'unread' );
      $max_total = fep_get_current_user_max_message_number();
	  $max_text = $max_total ? number_format_i18n($max_total) : __('unlimited', 'front-end-pm' );
	  
	  $template = fep_locate_template( 'header.php');
	  
	  ob_start();
	  include( $template );
	  return ob_get_clean();
    }


    function Menu()
    {
		$template = fep_locate_template( 'menu.php');
			  
		ob_start();
		include( $template );
		return ob_get_clean();
    }

    function Footer()
    {
		
		$template = fep_locate_template( 'footer.php');
			  
		ob_start();
		include( $template );
		return ob_get_clean();
    }
	
	function fep_message_box($action = '', $total_message = false, $messages = false )
	{
	
		if ( !$action ){
	  		$action = ( ! empty( $_GET['fepaction']) ) ? $_GET['fepaction']: 'messagebox';
	  	}
		
	  	$g_filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
	  
	  	if( false === $total_message ) {
	  		//$total_message = fep_get_user_message_count('total');
	  	}
	  
	  	if( false === $messages ){
	  		$messages = Fep_Message::init()->user_messages( $action );
			$total_message = Fep_Message::init()->found_messages;
	  	}
	  
	  $template = fep_locate_template( 'messagebox.php');
	  
	  ob_start();
	  include( $template );
		
	  return apply_filters('fep_messagebox', ob_get_clean(), $action);
}
	
function user_settings()
    {
	  
	  $template = fep_locate_template( 'settings.php');
	  
	  ob_start();
	  include( $template );
	  return ob_get_clean();
	  
    }

function new_message(){

	$template = fep_locate_template( 'newmessage_form.php');
	  
	  ob_start();
	  include( $template );
	  return ob_get_clean();
}
	
function view_message()
    {
      global $wpdb, $user_ID, $post;

	  if( isset( $_GET['fep_id'] ) ){
	  	$id = absint( $_GET['fep_id'] );
	  } else {
	  	$id = !empty($_GET['id']) ? absint($_GET['id']) : 0;
	  }
	  
	  if ( ! $id || ! fep_current_user_can( 'view_message', $id ) ) {
	  	return "<div class='fep-error'>".__("You do not have permission to view this message!", 'front-end-pm')."</div>";
	  }
	  
	  	$parent_id = fep_get_parent_id( $id );
	
		$messages = fep_get_message_with_replies( $id );
	
		$template = fep_locate_template( 'viewmessage.php');
	  
	  ob_start();
	  include( $template );
	  return ob_get_clean();

    }

/******************************************MAIN DISPLAY END******************************************/

  } //END CLASS
} //ENDIF


