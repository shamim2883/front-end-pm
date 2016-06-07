<?php
//Main CLASS
if (!class_exists('fep_directory_class'))
{
  class fep_directory_class
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
			if( fep_get_option('hide_directory', 0 ) != '1' || current_user_can('manage_options')){
				add_action ('fep_menu_button', array(&$this, 'menu'));
				add_action('fep_switch_directory', array(&$this, "directory"));
				}
    	}
	
	function menu() {
	 $class = 'fep-button';
	 if (isset($_GET['fepaction']) && $_GET['fepaction'] == 'directory')
	 $class = 'fep-button-active';
	 
	  echo "<a class='$class' href='".fep_action_url('directory')."'>".__('Directory', 'fep').'</a>';
	  }

	function directory()
    {
	if( fep_get_option('hide_directory', 0 ) == '1' && !current_user_can('manage_options')){
	  echo fep_message_box() ;
	  return;
	  }
	  
	  $page = (isset( $_GET['feppage']) && $_GET['feppage'] ) ? absint( $_GET['feppage'] ) : 0;
	  
      $offset = $page * fep_get_option('user_page', 50 );
	  
	  $args = array(
					'number' => fep_get_option('user_page', 50 ),
					'offset' => $offset,
					'orderby' => 'display_name',
					'order' => 'ASC'
		);
	
	$args = apply_filters ('fep_directory_arguments', $args );
	
	// The Query
	$user_query = new WP_User_Query( $args );
	  $total = $user_query->get_total();
      if (! empty( $user_query->results))
      {
        $directory = "<p><strong>".__("Total Users", 'fep').": (".$total.")</strong></p>";
        $numPgs = $total / fep_get_option('user_page', 50 );
        if ($numPgs > 1)
        {
          $directory .= "<p><strong>".__("Page", 'fep').": </strong> ";
          for ($i = 0; $i < $numPgs; $i++)
            if ($_GET['feppage'] != $i)
              $directory .= "<a href='".fep_action_url()."directory&feppage=".$i."'>".($i+1)."</a> ";
            else
              $directory .= "[<b>".($i+1)."</b>] ";
          $directory .= "</p>";
        }
		$directory .= "<table><tr class='fep-head'>
        <th width='40%'>".__("User", 'fep')."</th>
        <th width='30%'>".__("View Messages between", 'fep')."</th>
		<th width='30%'>".__("Send Message", 'fep')."</th></tr>";
		$a=0;

      foreach( $user_query->results as $u )
      {
	  $directory .= "<tr class='fep-trodd".$a."'><td>".$u->display_name."</td>";
	  $directory .= "<td><a href='".fep_action_url()."between&with=$u->user_login'>".__("View Messages between", 'fep')."</a></td>";
      $directory .= "<td><a href='".fep_action_url()."newmessage&to=$u->user_login'>".__("Send Message", 'fep')."</a></td></tr>";
		  if ($a) $a = 0; else $a = 1;
      }
	  $directory .= "</table>";

      }
      else
      {
        $directory = "<div id='fep-error'>".__("No users found.", 'fep')."</div>";;
      }
	  echo apply_filters( 'fep_directory_output', $directory );
    }
	
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_directory_class::init(), 'actions_filters'));
?>