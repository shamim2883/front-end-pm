<?php

if (!class_exists('fep_between_class'))
{
  class fep_between_class
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
	add_filter('fep_message_headline', array(&$this, "title"), 10, 2);
	add_filter('fep_messagebox', array(&$this, "username_check"), 10, 2);
	add_filter('fep_user_total_message_count_between', array(&$this, "total"), 10, 2);
	add_filter('fep_user_messages_between', array(&$this, "messages"), 10, 2);
    }
	
	function username_check( $title, $action ) {
	
		if ( $action == 'between' ) {
		
		if ( !isset($_GET['with']) || !username_exists( $_GET['with'] ))
		$title =  "<div id='fep-error'>".__("No Message found", 'fep')."</div>";
		}
		return $title;
		}
		
	function title( $title, $action ) {
	
		if ( $action == 'between' ) {
		
		$with = fep_get_userdata( $_GET['with'], 'display_name' );
		$title =  sprintf(__("Messages between you and %s", 'fep'), $with ) ;
		
		$another = fep_get_userdata( isset($_GET['another']) ? $_GET['another'] : '', 'display_name' );
		if ( $another && current_user_can('manage_options')) 
		$title =  sprintf(__("Messages between %s and %s", 'fep'), $another, $with );
		}
		return $title;
		}
		
	function total( $count, $action ) {
		global $wpdb, $user_ID;
		
		$with = fep_get_userdata( $_GET['with'] );
		
		$another = fep_get_userdata( isset($_GET['another']) ? $_GET['another'] : '' );
		
		if ( $another && current_user_can('manage_options'))
		$user = $another;
		else 
		$user = $user_ID;
		
		if ( $with ) {
		$get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE ((to_user = %d AND from_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND to_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1)", $user, $with, $user, $with));
	  $count = $wpdb->num_rows;
	  } else
	  $count = 1; //Not to show empty message error. 
		
		return $count;
		}
		
	function messages( $messages, $action ) {
		global $wpdb, $user_ID;
		
		$page = ( isset ($_GET['feppage']) && $_GET['feppage']) ? absint($_GET['feppage']) : 0;
		$start = $page * fep_get_option('messages_page', 50);
        $end = fep_get_option('messages_page', 50);
	  
		$with = fep_get_userdata( $_GET['with'] );
		$another = fep_get_userdata( isset($_GET['another']) ? $_GET['another'] : '' );
		
		if ( $another && current_user_can('manage_options'))
		$user = $another;
		else 
		$user = $user_ID;
		
		if ( $with ) {
		$messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE ((to_user = %d AND from_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND to_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $user, $with, $user, $with, $start, $end ));
	  } else
	  $messages = array();
		
		return $messages;
		}
	
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_between_class::init(), 'actions_filters'));
?>