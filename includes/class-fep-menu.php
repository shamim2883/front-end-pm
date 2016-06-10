<?php

class Fep_Menu
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
			add_action ('fep_menu_button', array($this, 'menu'));
    	}

	function menu(){
		$menu = '';
		
		  foreach( $this->get_menu() as $menu_array ) {
			$class = $menu_array['class'];
			 if ( isset($_GET['fepaction']) && $_GET['fepaction'] == $menu_array['action'])
			 $class = $menu_array['active-class'];
			 
			 $menu .= "<a class='$class' href='".fep_query_url( $menu_array['action'] )."'>".$menu_array['title'].'</a>';
		  }
		  echo $menu;
	 }
	
	private function get_menu()
	{
		$menu = array(
				'new_message'	=> array(
					'title'			=> __('New Message', 'front-end-pm'),
					'action'			=> 'newmessage',
					'priority'			=> 5
					),
				'message_box'	=> array(
					'title'			=> __('Message Box', 'front-end-pm'),
					'action'			=> 'messagebox',
					'priority'			=> 10
					),
				'settings'	=> array(
					'title'			=> __('Settings', 'front-end-pm'),
					'action'			=> 'settings',
					'priority'			=> 15
					),
				'announcements'	=> array(
					'title'			=> __('Announcement', 'front-end-pm'),
					'action'			=> 'announcements',
					'priority'			=> 20
					)
							
				);
		if( ! fep_current_user_can( 'send_new_message' ) ) {
			unset($menu['new_message']);
		}
							
		$menu = apply_filters('fep_menu_buttons', $menu);
						
				foreach ( $menu as $key => $tab )
					{
				
						$defaults = array(
								'title'			=> '',
								'action'		=> '',
								'class'			=> 'fep-button',
								'active-class'	=> 'fep-button-active',
								'priority'		=> 20
							);
					$menu[$key] = wp_parse_args( $menu[$key], $defaults);
			
				}
			uasort( $menu, 'fep_sort_by_priority' );
							
		return $menu;
	}
	
 } //END CLASS

add_action('wp_loaded', array(Fep_Menu::init(), 'actions_filters'));

