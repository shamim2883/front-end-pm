<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Announcement CLASS
class Fep_User_Settings
  {
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	
    function actions_filters(){
		
		add_filter('fep_menu_buttons', array($this, 'menu'));
		
		$menu = Fep_Menu::init()->get_menu();
		if( ! empty( $menu['settings'] ) ){
			add_filter('fep_filter_switch_settings', array($this, 'settings_form'));
			add_action( 'fep_posted_action_settings', array($this, 'posted_settings') );
			add_action( 'fep_after_form_fields', array($this, 'fep_after_form_fields'), 10, 3 );
		}		
	}
	
	function menu( $menu ){
	 
	 	$menu['settings']	= array(
			'title'			=> __('Settings', 'front-end-pm'),
			'action'		=> 'settings',
			'priority'		=> 15
			);
		
		return $menu;
	  }
		 
	  function settings_form()
		  {
			
			$template = fep_locate_template( 'settings.php');
			
			ob_start();
			include( $template );
			return ob_get_clean();
			
		  }
		
		function posted_settings(){
			add_action ('fep_action_form_validated', array( $this, 'settings_save' ), 10, 2);
			
			Fep_Form::init()->validate_form_field( 'settings' );
			
			if( count( fep_errors()->get_error_messages()) == 0 ){
				fep_success()->add( 'saved', __("Settings successfully saved.", 'front-end-pm') );
			}
		}
		
		function settings_save( $where, $fields )
		{
			if( 'settings' != $where )
				return;
			
			if( !$fields || ! is_array( $fields ) )
				return;
			
			$settings = array();
			
			foreach( $fields as $field ) {
				$settings[$field['name']] = $field['posted-value'];
			}
			$settings = apply_filters('fep_filter_user_settings_before_save', $settings );
			
			fep_update_user_option( $settings ); 
		}
		
		function fep_after_form_fields( $where, $errors, $fields ){
			if( 'settings' != $where )
			return;
			
			if( empty( $fields['blocked_users'] ) )
			return;
			
			wp_enqueue_script( 'fep-tokeninput-script');
			wp_enqueue_style( 'fep-tokeninput-style');
			
			$users = array();
			foreach( fep_get_blocked_users_for_user() as $id ) {
				if( $name = fep_user_name( $id ) ) {
					$users[] = array(
						'id' => $id,
						'name' => $name
						);
				}
			}
			
			?><script type="text/javascript">
				jQuery(document).ready(function(){
				//comment previous line and uncomment next line if you have any issue with multiple receipant field ( eg. for CloudFlare rocketscript tech )
				//jQuery(window).load(function(){	
				jQuery("#blocked_users").tokenInput( "<?php echo admin_url( 'admin-ajax.php' ); ?>?action=fep_users_ajax&token=<?php echo wp_create_nonce('fep_users_ajax'); ?>", {
					method: "POST",
					theme: "facebook",
					excludeCurrent: true,
					hintText: "<?php _e("Type user name", 'front-end-pm'); ?>",
					noResultsText: "<?php _e("No matches found", 'front-end-pm'); ?>",
					searchingText: "<?php _e("Searching...", 'front-end-pm'); ?>",
					prePopulate: <?php echo wp_json_encode( $users ); ?>,
					width: '250px',
					preventDuplicates: true,
					zindex: 99999,
					resultsLimit: 5
				});

				});
				</script><?php
			
		}
			
  } //END CLASS

add_action('init', array(Fep_User_Settings::init(), 'actions_filters'));

