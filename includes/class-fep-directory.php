<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Directory
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
			if( fep_current_user_can( 'access_directory') ){
				add_filter('fep_menu_buttons', array($this, 'menu'));
				add_action('fep_switch_directory', array($this, "directory"));
				}
    	}
	
	function menu( $menu ) {
	 
	 	$menu['directory']	= array(
					'title'			=> __('Directory', 'front-end-pm'),
					'action'			=> 'directory',
					'priority'			=> 25
					);
					
		return $menu;
	  }

	function directory()
    {
		if ( ! fep_current_user_can( 'access_directory') ) {
	  		echo "<div class='fep-error'>".__("You do not have permission to access directory!", 'front-end-pm')."</div>";
			return;
	  	}
	  
	  $args = array(
			'number' => fep_get_option('user_page', 50 ),
			'paged'	=> !empty($_GET['feppage']) ? absint($_GET['feppage']): 1,
			'orderby' => 'display_name',
			'order' => 'ASC',
			'fields' => array( 'ID', 'display_name', 'user_nicename' )
		);
		if( !empty($_GET['fep-search']) ) {
			$args['search'] = '*'. $_GET['fep-search'] . '*';
		}
	
		$args = apply_filters ('fep_directory_arguments', $args );
	
		// The Query
		$user_query = new WP_User_Query( $args );
	  	$total = $user_query->get_total();
      
	  	$template = fep_locate_template( 'directory.php');
	  
	  	ob_start();
	  	include( $template );
		echo apply_filters( 'fep_directory_output', ob_get_clean() );
	  
    }
	
	
	
 } //END CLASS

add_action('wp_loaded', array(Fep_Directory::init(), 'actions_filters'));
?>