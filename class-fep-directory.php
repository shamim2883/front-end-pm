<?php

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
	  	echo "<div id='fep-error'>".__("You do not have permission to access directory!", 'front-end-pm')."</div>";
		return;
	  }
	  
	  $page = !empty( $_GET['feppage']) ? absint( $_GET['feppage'] ) - 1: 0;
	  
      $offset = $page * fep_get_option('user_page', 50 );
	  
	  $args = array(
					'number' => fep_get_option('user_page', 50 ),
					'offset' => $offset, //paged support since wordpress version 4.4, so not using for backword compatibility
					'orderby' => 'display_name',
					'order' => 'ASC',
					'fields' => array( 'ID', 'display_name', 'user_nicename' )
		);
		if( !empty($_GET['search']) ) {
			$args['search'] = '*'. $_GET['search'] . '*';
		}
	
	$args = apply_filters ('fep_directory_arguments', $args );
	
	// The Query
	$user_query = new WP_User_Query( $args );
	  $total = $user_query->get_total();
      if (! empty( $user_query->results))
      {
  
		$directory = '<div class="fep-table fep-odd-even">';
		
        $directory .= '<span class="fep-table-caption">'.__("Total Users", "front-end-pm").': ('.number_format_i18n($total).')</span>';
		
      foreach( $user_query->results as $u )
      {
		  $directory .= '<div class="fep-table-row">';
		  $directory .= '<div class="fep-column">' .get_avatar($u->ID, 64).'</div>';
		  $directory .= '<div class="fep-column">' . esc_html( $u->display_name ).'</div>';
		  $directory .= '<div class="fep-column"><a href="' .fep_query_url( "newmessage", array( "to" => $u->user_nicename)).'">'.__("Send Message", "front-end-pm").'</a></div>';
			$directory .= '</div>';
      }
	  $directory .= "</div>";
	  $directory .= fep_pagination( $total, fep_get_option('user_page', 50 ) );

      }
      else
      {
        $directory = "<div id='fep-error'>".__("No users found.", 'front-end-pm')."</div>";;
      }
	  echo apply_filters( 'fep_directory_output', $directory );
    }
	
	
	
 } //END CLASS

add_action('wp_loaded', array(Fep_Directory::init(), 'actions_filters'));
?>