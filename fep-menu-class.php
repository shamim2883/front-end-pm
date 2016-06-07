<?php

if (!class_exists('fep_menu_class'))
{
  class fep_menu_class
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
		if ( '1' != fep_get_option('disable_new') || current_user_can('manage_options') )
			add_action ('fep_menu_button', array(&$this, 'newmessage'));
			add_action ('fep_menu_button', array(&$this, 'messagebox'));
			add_action ('fep_menu_button', array(&$this, 'settings'));
    	}

		function settings() {
	 $class = 'fep-button';
	 if ( is_page( fep_page_id() ) && isset($_GET['fepaction']) && $_GET['fepaction'] == 'settings')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('settings')."'>".__('Settings', 'fep').'</a>';
	  }

	function newmessage() {
	 $class = 'fep-button';
	 if ( is_page( fep_page_id() ) && isset($_GET['fepaction']) && $_GET['fepaction'] == 'newmessage')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('newmessage')."'>".__('New Message', 'fep').'</a>';
	  }
	  
	  function messagebox() {
		$numNew = fep_get_new_message_button();
		$class = 'fep-button';
	 if ( is_page( fep_page_id() ) && ( !isset($_GET['fepaction']) || $_GET['fepaction'] == 'messagebox') )
	 $class = 'fep-button-active';
     echo "<a class='$class' href='".get_permalink(fep_page_id())."'>".sprintf(__("Message Box%s", 'fep'),$numNew)."</a>";
	 
	  }
	
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_menu_class::init(), 'actions_filters'));
?>