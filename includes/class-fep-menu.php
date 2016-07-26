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
			$class = esc_attr( $menu_array['class'] );
			 if ( isset($_GET['fepaction']) && $_GET['fepaction'] == $menu_array['action'])
			 $class = esc_attr( $menu_array['active-class'] );
			 
			 $menu .= "<a class='$class' href='".fep_query_url( $menu_array['action'] )."'>".strip_tags( $menu_array['title'], '<span>' )."</a>";
		  }
		  echo $menu;
	 }
	
	private function get_menu()
	{
		$menu = array(
				'newmessage'	=> array(
					'title'			=> __('New Message', 'front-end-pm'),
					'action'			=> 'newmessage',
					'priority'			=> 5
					),
				'message_box'	=> array(
					'title'			=> sprintf(__('Message Box%s', 'front-end-pm'), fep_get_new_message_button() ),
					'action'			=> 'messagebox',
					'priority'			=> 10
					),
				'settings'	=> array(
					'title'			=> __('Settings', 'front-end-pm'),
					'action'			=> 'settings',
					'priority'			=> 15
					),
				'announcements'	=> array(
					'title'			=> sprintf(__('Announcement%s', 'front-end-pm'), fep_get_new_announcement_button() ),
					'action'			=> 'announcements',
					'priority'			=> 20
					)
							
				);
		if( ! fep_current_user_can( 'send_new_message' ) ) {
			unset($menu['newmessage']);
		}
							
		$menu = apply_filters('fep_menu_buttons', $menu );
						
				foreach ( $menu as $key => $tab )
					{
				
						$defaults = array(
								'title'			=> '',
								'action'		=> $key,
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

