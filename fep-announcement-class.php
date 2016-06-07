<?php
//Announcement CLASS
if (!class_exists('fep_announcement_class'))
{
  class fep_announcement_class
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
			add_action('fep_header_note', 				array(&$this, 'header_note'), 15);
			add_action('fep_menu_button', 				array(&$this, 'menu'));
			add_action('fep_user_settings_form', 		array(&$this, 'user_settings'));
			add_filter('fep_filter_user_settings_before_save', array(&$this, 'user_settings_filter'));
			add_action('fep_switch_announcements', 		array(&$this, "announcements"));
			add_action('fep_switch_addannouncement', 	array(&$this, "add"));
			add_action('fep_switch_viewannouncement', 	array(&$this, "dispAnnouncement"));
			add_action('fep_switch_delannouncement', 	array(&$this, "delete"));
    	}
	
	function header_note() 
		{
			$numNew = $this->getAnnouncementsNum();
			$sa = ( $numNew != 1 ) ? __('new announcements', 'fep'): __('new announcement', 'fep');
	
			echo ' '. __('and', 'fep')." (<font color='red'>$numNew</font>) $sa"; 
		}
	
	function menu() 
		{
	 			$class = 'fep-button';
				
	 		if (isset($_GET['fepaction']) && $_GET['fepaction'] == 'announcements')
			 	$class = 'fep-button-active';
	 
	  	echo "<a class='$class' href='".fep_action_url('announcements')."'>".sprintf(__('Announcement%s', 'fep'), $this->getAnnouncementsNum_btn() ).'</a>';
	  	}
			
		function user_settings()
		{
		
			echo "<input type='checkbox' name='allow_ann' value='1' ".checked(fep_get_user_option('allow_ann', 1), '1', false)."/> <i>".__("Email me when New announcement is published?", 'fep')."</i><br/>";
			
		}
		
	function user_settings_filter( $options )
		{
		
			$options['allow_ann'] = ( isset( $_POST['allow_ann'] ) )? 1 : '';
			return $options;
		}
		
	    function announcements()
    	{
      global $wpdb, $user_ID;

      $announcements = $this->getAnnouncements();
      $num = sizeof($announcements);
	  $token = fep_create_nonce( 'announcement' );
	  
	  if (current_user_can('manage_options'))
	  $msgsOut = "<a class='fep-button' href='".fep_action_url('addannouncement')."'>".__('Add New', 'fep').'</a>';
	  else
	  $msgsOut = '';
	  
      if ($num)
      {
			$msgsOut .= "<p><strong>".__("Announcements", 'fep').": ($num)</strong></p>";
		
        $numPgs = $num / fep_get_option('messages_page', 50 );
        if ($numPgs > 1)
        {
          $msgsOut .= "<p><strong>".__("Page", 'fep').": </strong> ";
          for ($i = 0; $i < $numPgs; $i++)
            if ($_GET['feppage'] != $i){
              $msgsOut .= "<a href='".fep_action_url()."announcements&feppage=".$i."'>".($i+1)."</a> ";
            } else {
              $msgsOut .= "[<b>".($i+1)."</b>] ";}
          $msgsOut .= "</p>";
        }

        $msgsOut .= "<table><tr class='fep-head'>";
		if (current_user_can('manage_options'))
        $msgsOut .= "<th width='20%'>".__("Added By", 'fep')."</th>";
		$msgsOut .= "<th width='20%'>".__("Date", 'fep')."</th>
        <th width='30%'>".__("Subject", 'fep')."</th>
        <th width='10%'>".__("Action", 'fep')."</th></tr>";
        
		$a = 0;
		$page = (isset( $_GET['feppage']) && $_GET['feppage'] ) ? absint( $_GET['feppage'] ) : 0;
		$offset = $page * fep_get_option('messages_page', 50 );
		
	  $sliced_announcement = array_slice( $announcements, $offset, fep_get_option('user_page', 50 ), true );
	  //var_dump($sliced_announcement);
        foreach ($sliced_announcement as $msg)
        {
          if ( !$this->is_seen( $msg->id ) )
            $status = "<font color='#FF0000'>".__("Unread", 'fep')."</font>";
          else
            $status = __("Read", 'fep');
		  $msgsOut .= "<tr class='fep-trodd".$a."'>";
		  if (current_user_can('manage_options'))
		  $msgsOut .= "<td>" .fep_get_userdata( $msg->from_user, 'display_name', 'id' ). "</td>";
		  $msgsOut .= "<td>".fep_format_date($msg->send_date)."</td>";
		  $msgsOut .= "<td><a href='".fep_action_url()."viewannouncement&id=$msg->id'>".fep_output_filter($msg->message_title, true)."</a><br/><small>$status</small></td>";
		  if ( $this->is_seen($msg->id) )
		  $msgsOut .= "<td><a href='".fep_action_url()."delannouncement&id=$msg->id&token=$token' onclick='return confirm(\"".__('Are you sure?', 'fep')."\");'>".__("Delete", 'fep')."</a></td>";
		  else
		  $msgsOut .= "<td><a href='".fep_action_url()."viewannouncement&id=$msg->id'>".__("View", 'fep')."</a></td>";
			  
          $msgsOut .=  "</tr>";
		   //Alternate table colors
		  if ($a) $a = 0; else $a = 1;
        }
        $msgsOut .= "</table>";

      }
      else
      {
        $msgsOut .= "<div id='fep-error'>".__("Announcement is empty!", 'fep')."</div>";
     
      }
	  
	  echo $msgsOut;
		return;
    }
	
    function dispAnnouncement()
    {
      global $wpdb, $user_ID;
	  
	  $id = (isset( $_GET['id']) && $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
      $announcement = $this->getAnnouncements_by_id( $id );

      if ( $announcement ) //Just viewing announcements
      {
	  $this->make_seen( $id );
	  
        echo "<p><strong>".__("Announcement", 'fep').":</strong></p>";
        echo "<table>";
		
        echo "<tr class='fep-trodd1'><td class='fep-pmtext'><strong>".__("Subject", 'fep').":</strong> ".fep_output_filter($announcement->message_title, true).
          "<br/><strong>".__("Date", 'fep').":</strong> ".fep_format_date($announcement->send_date);
          if (current_user_can('manage_options')) {
		echo "<br/><strong>".__("Added by", 'fep').":</strong> ".fep_get_userdata( $announcement->from_user, 'display_name', 'id' );
		echo "<br/><strong>".__("Total Seen", 'fep').":</strong> ".$this->total_seen( $id ); }
		  
		  do_action ('fep_display_after_announcement_subject', $id );
		  
        echo "</td></tr>";
		  
        echo "<tr class='fep-trodd0'><td class='fep-pmtext'><strong>".__("Message", 'fep').":</strong><br/>".fep_output_filter($announcement->message_contents);
		  do_action ('fep_display_after_announcement_content', $id );
        
        echo "</td></tr></table>";
      } else {
	  	echo "<div id='fep-error'>".__("Announcement is empty!", 'fep')."</div>"; }

    }
	
	function add() {
		$html = '<h2>' . __('Add Announcement', 'fep') . '</h2>';
	if(isset($_POST['add-announcement'])){ 
		$errors = $this->check();
		if(count($errors->get_error_messages())>0){
			$html .= fep_error($errors);
			$html .= $this->form();
		}
		else{
			$html .= '<div id="fep-success">' .__("Announcement successfully added.", 'fep'). ' </div>';
		}
	}
	else{
		$html .= $this->form();
	}
	echo $html;
	return;
}

    function form()
    {
		global $user_ID;
		$token = fep_create_nonce('add_announcement');

	$message_title = ( isset( $_REQUEST['message_title'] ) ) ? esc_html($_REQUEST['message_title']): '';
	$message_content = ( isset( $_REQUEST['message_content'] ) ) ? esc_textarea($_REQUEST['message_content']): '';
	
      $form = "<form action='".fep_query_url('addannouncement')."' method='post' enctype='multipart/form-data'>
      ".__("Subject", 'fep').":<br/>
      <input type='text' name='message_title' value='$message_title' /><br/>";
	  ob_start();
		do_action('fep_announcement_form_before_content');
		echo __("Message", 'fep').":<br/>";
		if ('wp_editor' == fep_get_option('editor_type') || current_user_can ('manage_options')){
		wp_editor( $message_content, 'message_content', array('teeny' => false, 'media_buttons' => false, 'textarea_rows' => 8) );
		} elseif ('teeny' == fep_get_option('editor_type','teeny')){ 
		wp_editor( $message_content, 'message_content', array('teeny' => true, 'media_buttons' => false, 'textarea_rows' => 8) );
		} else {
        echo  "<textarea name='message_content' placeholder='Message Content'>$message_content</textarea>"; }
		
		do_action('fep_announcement_form_after_content');
		$form .= ob_get_contents();
		ob_end_clean();
      $form .= "<input type='hidden' name='message_from' value='$user_ID' />
	  <input type='hidden' name='token' value='$token' /><br/>
      <input type='submit' name='add-announcement' value='".__("Submit", 'fep')."' />
      </form>";

      return $form;
    }
	
	function check()
    {
      global $wpdb,$user_ID;
	  $errors = new WP_Error();
	  $message = $_POST;
      $message['send_date'] = current_time('mysql');
      $message['status'] = '2';
	  
	  if (!$message['message_title'])
		  $errors->add('invalidSub', __('You must enter subject.', 'fep'));
        if (!$message['message_content'])
		  $errors->add('invalidMgs', __('You must enter some announcement content!', 'fep'));
        if ($message['message_from'] != $user_ID)
          $errors->add('NoPermission', __("You do not have permission to add this announcement!", 'fep'));
		 if ( !current_user_can('manage_options') )
          $errors->add('NoPermission', __("You do not have permission to add this announcement!", 'fep'));
		if ( !fep_verify_nonce($message['token'], 'add_announcement') )
        $errors->add('InvalidToken', __("Invalid Token. Please try again!", 'fep'));
		
		// This action hook is DEPRECATED since version 3.4. Use following filter hook instead
		do_action('fep_action_announcement_before_add', $errors);
	  	$message = apply_filters('fep_filter_announcement_before_add', $message, $errors); //arg $errors added since version 3.4
	  
		if(count($errors->get_error_codes())==0){
		$wpdb->insert( FEP_MESSAGES_TABLE, array( 'from_user' => $message['message_from'], 'message_title' => $message['message_title'], 'message_contents' => $message['message_content'], 'send_date' => $message['send_date'], 'status' => $message['status'] ), array( '%d', '%s', '%s', '%s', '%d' )); 
		
		$message_id = $wpdb->insert_id;
		delete_transient("fep_announcements_with_seen");
		delete_transient("fep_announcements_with_deleted");
		
		do_action('fep_after_add_announcement', $message_id, $message);
		}
	  return $errors;
    }
	
	function getAnnouncements_with_deleted()
    {
      global $wpdb; 
		if (false === ($results = get_transient("fep_announcements_with_deleted"))){
	  $results = $wpdb->get_results($wpdb->prepare("SELECT t.*, m.field_value FROM ".FEP_MESSAGES_TABLE." AS t LEFT OUTER JOIN ".FEP_META_TABLE." AS m ON (t.id = m.message_id AND m.field_name = %s) WHERE t.status = %d GROUP BY t.id ORDER BY MAX(t.send_date) DESC",'announcement_deleted_user_id',2));
	  
	  set_transient("fep_announcements_with_deleted",$results,60*60*24);
	  }
	  
      return $results;
    }
	
	function getAnnouncements_with_seen()
    {
      global $wpdb;
	  
		if (false === ($results = get_transient("fep_announcements_with_seen"))){
	  $results = $wpdb->get_results($wpdb->prepare("SELECT t.*, m.field_value FROM ".FEP_MESSAGES_TABLE." AS t LEFT OUTER JOIN ".FEP_META_TABLE." AS m ON (t.id = m.message_id AND m.field_name = %s) WHERE t.status = %d GROUP BY t.id ORDER BY MAX(t.send_date) DESC",'announcement_seen_user_id',2));
	  
	  set_transient("fep_announcements_with_seen",$results,60*60*24);
	  }
	  
      return $results;
    }

    function getAnnouncements()
    {
      global $wpdb, $user_ID; 
	  $results = $this->getAnnouncements_with_deleted();
	  $user_registered = strtotime(get_userdata(get_current_user_id())->user_registered, current_time('timestamp'));
	  
	  //var_dump($results);
	foreach ($results as $index => $result)
	  {
	  if ( $user_registered > strtotime($result->send_date))
	  	{
			unset($results[$index]);
			continue;
		}
	  $arrayDelID = maybe_unserialize($result->field_value);
	  if (is_array ($arrayDelID))
	  	{
	  		if (in_array($user_ID,$arrayDelID))
			{
	  			unset($results[$index]);
	  		}
		}
	}
	  
      return $results;
    }
	
	function getAnnouncements_by_id( $id )
    {
      global $wpdb, $user_ID; 
	  $results = $this->getAnnouncements();
	  //var_dump($results);
	  $announcement = array();
	  foreach ($results as $result){
	  if ( $id == $result->id ) {
	   	$announcement = $result;
		break;
	  }}
	  
      return $announcement;
    }
	
	function is_seen( $id, $userid='' )
    {
      global $wpdb, $user_ID;
	  
	  if ( !$userid )
	  $userid = $user_ID;
	  
	  $results = $this->getAnnouncements_with_seen();
	  $seen = false;
	  
	  foreach ($results as $result){
	  if ( $result->id == $id ) {
	  $arrayseenID = maybe_unserialize($result->field_value);
	  if (is_array ($arrayseenID)){
	  if (in_array( $userid, $arrayseenID )){
	  $seen = true;
	  }}
	  break;}}
	  
      return $seen;
    }
	
	function make_seen( $id, $userid='' )
    {
      global $wpdb, $user_ID;
	  
	  if ( !$userid )
	  $userid = $user_ID;
	  
	  if ( $this->is_seen( $id, $userid ) )
	  return false;
	  
	  $userSeen = $wpdb->get_row($wpdb->prepare("SELECT meta_id, field_value FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s LIMIT 1", $id,'announcement_seen_user_id'));
	  
	  $userseenexists = ( $userSeen ) ? $userSeen->field_value : '';
	  
	  $user_array = maybe_unserialize($userseenexists);
	  if (is_array($user_array)){
	  $user_array[] = $userid;
	  } else {
	  $user_array = array( $userid ); }
	  sort($user_array);
	  $serialized_value = maybe_serialize( array_unique($user_array) );
	  if ($userSeen){
	  $result = $wpdb->update( FEP_META_TABLE, array( 'field_value' => $serialized_value ), array( 'meta_id' => $userSeen->meta_id ), array( '%s' ), array( '%d' ) );
	  } else{
	  $result = $wpdb->insert( FEP_META_TABLE, array( 'message_id' => $id, 'field_name' => 'announcement_seen_user_id','field_value' => $serialized_value ), array( '%d', '%s', '%s' ) );
	  }
	if (  $result )
	delete_transient('fep_announcements_with_seen');
	return true;
	  }
	
	function total_seen( $id )
    {
      global $wpdb;
	  
	  $results = $this->getAnnouncements_with_seen();
	  $count = 0;
	  
	  foreach ($results as $result){
	  if ( $result->id == $id ) {
	  $arrayseenID = maybe_unserialize($result->field_value);
	  if (is_array ($arrayseenID)){
	  $count = sizeof($arrayseenID);
	  }
	  break;}}
	  
      return $count;
    }

    function getAnnouncementsNum()
    {
      global $wpdb, $user_ID;
	  
	  $results = $this->getAnnouncements_with_seen();
	  $user_registered = strtotime(get_userdata(get_current_user_id())->user_registered, current_time('timestamp'));
	  
	  $count = 0;
	 foreach ($results as $result)
	  {
	  	if ( $user_registered > strtotime($result->send_date))
	  	{
			continue;
		}
	  $arrayDelID = maybe_unserialize($result->field_value);
	  if (is_array ($arrayDelID))
	  	{
	  		if (!in_array($user_ID,$arrayDelID))
				{
					$count += 1;
	  			}
	  	} else {
			$count += 1;
			}
	  }
	  
      return $count;
    }
	function getAnnouncementsNum_btn(){
	if ($this->getAnnouncementsNum()){
	  	$newmgs = " (<font color='red'>";
		$newmgs .= $this->getAnnouncementsNum();
		$newmgs .="</font>)";
		} else {
		$newmgs ="";}
		
		return $newmgs;
		}


    function delete()
    	{
      		global $wpdb, $user_ID;
	  
	  		$delID = ( isset( $_GET['id'] ) ) ? absint( $_GET['id'] ): 0;
	  
      		if ( current_user_can('manage_options') && fep_verify_nonce($_GET['token'], 'announcement') ) //Make sure only admins can delete announcements
      			{
	  				do_action ('fep_announcement_before_delete', $delID );
	  
        			$wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $delID));
					$wpdb->query($wpdb->prepare("DELETE FROM ".FEP_META_TABLE." WHERE message_id = %d", $delID));
					
					delete_transient("fep_announcements_with_seen");
					delete_transient("fep_announcements_with_deleted");
		
					echo '<div id="fep-success">' .__("Announcement successfully Deleted.", 'fep'). ' </div>';
        			return true;
      			} 
				
			elseif (!current_user_can('manage_options') && fep_verify_nonce($_GET['token'], 'announcement') )
			
	  			{
					if ( !$this->is_seen( $delID ) ){
	    			echo '<div id="fep-error">' .__("Something wrong. Please try again.", 'fep'). ' </div>';
      				return false;
						}
					
	  				$userDel = $wpdb->get_row($wpdb->prepare("SELECT meta_id, field_value FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s LIMIT 1", $delID,'announcement_deleted_user_id'));
					
	  			$user_array = maybe_unserialize($userDel->field_value);
					
	  			if (is_array($user_array)){
	  				$user_array[] = $user_ID;
	  			} else {
	  				$user_array = array($user_ID);
						}
	  				sort($user_array);
	  				$serialized_value = maybe_serialize( array_unique($user_array) );
	  			if ($userDel){
	  				$result = $wpdb->update( FEP_META_TABLE, array( 'field_value' => $serialized_value ), array( 'meta_id' => $userDel->meta_id ), array( '%s' ), array( '%d' ) );
	  						} 
				else{
	  				$result = $wpdb->insert( FEP_META_TABLE, array( 'message_id' => $delID, 'field_name' => 'announcement_deleted_user_id','field_value' => $serialized_value ), array( '%d', '%s', '%s' ) );
	  					}
						
				if (  $result ) {
					delete_transient("fep_announcements_with_deleted");
					echo '<div id="fep-success">' .__("Announcement successfully Deleted.", 'fep'). ' </div>';
					return true;
								}
	  		} else {
	    			echo '<div id="fep-error">' .__("Something wrong. Please try again.", 'fep'). ' </div>';
      				return false;
						}
    	}
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_announcement_class::init(), 'actions_filters'));

