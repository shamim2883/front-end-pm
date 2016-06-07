<?php

if (!class_exists('fep_admin_frontend_class'))
{
  class fep_admin_frontend_class
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
	if ( current_user_can('manage_options'))
		{
	add_action ('fep_menu_button', array(&$this, 'menu'));
	add_filter('fep_message_headline', array(&$this, "title"), 10, 2 );
	add_filter('fep_user_total_message_count_allmessages', array(&$this, "total"), 10, 2 );
	add_filter('fep_user_messages_allmessages', array(&$this, "messages"), 10, 2 );
	add_filter('fep_delete_message_url', array(&$this, "delete_url"), 10, 2 );
	add_filter('fep_filter_status_display', array(&$this, "status"), 10, 3 );
	add_action('fep_switch_deletemessageadmin', array(&$this, "delete"));
		}
    }
	
	function menu() {
	 $class = 'fep-button';
	 if (isset($_GET['fepaction']) && $_GET['fepaction'] == 'allmessages')
	 $class = 'fep-button-active';
	 if ( current_user_can('manage_options') )
	  echo "<a class='$class' href='".fep_action_url('allmessages')."'>".sprintf(__('All Messages%s', 'fep'), $this->total_unread() ).'</a>';
	  }
		
	function title( $title, $action ) {
	
		if ( $action == 'allmessages' && current_user_can('manage_options') ) {
		
		$title =  __("All Messages", 'fep' )  ;
		
		}
		return $title;
		}
		
	function total( $count, $action ) {
		global $wpdb;
		
		if ( current_user_can('manage_options') ) {
		$get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE parent_id = %d AND (status = %d OR status = %d)", 0, 0, 1 ));
	  $count = $wpdb->num_rows;
	  } else
	  $count = 1; //Not to show empty message error. 
		
		return $count;
		}
	
	function total_unread() {
	
		global $wpdb;
		
		if ( current_user_can('manage_options') ) {
		$get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE parent_id = %d AND status = %d", 0, 0 ));
	  $count = $wpdb->num_rows;
	  } else 
	  $count = 0;
	  
	   if ( $count )
	   $button = " (<font color='red'>$count</font>)";
	   else
	   $button = '';
		
		return $button;
		}
		
	function messages( $messages, $action ) {
		global $wpdb;
		
		$page = ( isset ($_GET['feppage']) && $_GET['feppage']) ? absint($_GET['feppage']) : 0;
		$start = $page * fep_get_option('messages_page', 50);
        $end = fep_get_option('messages_page', 50);
		
		if ( current_user_can('manage_options') ) {
		$messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE parent_id = %d AND (status = %d OR status = %d) ORDER BY last_date DESC LIMIT %d, %d", 0, 0, 1, $start, $end ));
	  } else
	  $messages = array();
		
		return $messages;
		}
		
	function delete_url( $del_url, $id ) {
	
		
		if ( current_user_can('manage_options') ) {
		$token = fep_create_nonce('delete_message_admin');
		$del_url = fep_action_url("deletemessageadmin&id=$id&token=$token");
		
		}
		return $del_url;
	}
		
	function status( $status, $msg, $action ) {
	
		if ( $action == 'allmessages' && current_user_can('manage_options') ) {
			
			if ( $msg->status == 0 )
            $status = "<font color='#FF0000'>".__("Unread", 'fep')."</font>";
          else
            $status = __("Read", 'fep');
		
		}
		return $status;
	}
		
		function delete()
    {
      global $wpdb;

      $delID = absint( $_GET['id'] );
	  
	  if (!fep_verify_nonce($_GET['token'], 'delete_message_admin')){
	  echo "<div id='fep-error'>".__("Invalid Token!", 'fep')."</div>";
	  return;}
	  
	  if ( 0 == $delID ){
	  echo "<div id='fep-error'>".__("Invalid message id!", 'fep')."</div>";
	  return;}
	  

      if ( current_user_can('manage_options') )
      {
		$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
	  $id = implode(',',$ids);
	  
	  do_action ('fep_message_before_delete', $delID, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
		  $wpdb->query("DELETE FROM ".FEP_META_TABLE." WHERE message_id IN ({$id})");
		  
		  } else {
	  echo "<div id='fep-error'>".__("No permission!", 'fep')."</div>";
	  return;
	  }
		
		echo "<div id='fep-success'>".__("Message was successfully deleted!", 'fep')."</div>";
		return;
    }
	
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_admin_frontend_class::init(), 'actions_filters'));
?>