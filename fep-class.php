<?php
//Main CLASS
if (!class_exists("fep_main_class"))
{
  class fep_main_class
  {
    
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	


/******************************************MAIN DISPLAY BEGIN******************************************/

    //Display the proper contents
   function main_shortcode_output()
    {
      global $user_ID;
      if ($user_ID)
      {
	  
	  if (fep_get_option('min_cap','read') != ''){ 
	  //Required capability
	  $cap = trim(fep_get_option('min_cap','read'));
	  if (!current_user_can($cap)){
	  
	  return "<div id='fep-error'>".sprintf(__("Messaging is only allowed for users at least %s capability!", 'fep'), $cap)."</div>";}}

        //Add header
        $out = $this->Header();

        //Add Menu
        $out .= $this->Menu();
		
        //Start the guts of the display
		$switch = ( isset($_GET['fepaction'] ) && $_GET['fepaction'] ) ? $_GET['fepaction'] : 'messagebox';
		
        switch ($switch)
        {
		case has_action("fep_switch_{$switch}"):
			ob_start();
			do_action("fep_switch_{$switch}");
			$out .= ob_get_contents();
			ob_end_clean();
			break;
         case 'newmessage':
            $out .= $this->new_message();
            break;
          case 'checkmessage':
            $out .= $this->new_message_action();
            break;
          case 'viewmessage':
            $out .= $this->view_message();
            break;
		  case 'between':
            $out .= fep_message_box();
            break;
          case 'deletemessage':
            $out .= $this->delete();
            break;
          case 'settings':
            $out .= $this->user_settings();
            break;
          default: //Message box is shown by Default
            $out .= fep_message_box();
            break;
        }

        //Add footer
        $out .= $this->Footer();
      }
      else
      {
        $out = "<div id='fep-error'>".__("You must be logged-in to view your message.", 'fep')."</div>";
      }
      return apply_filters('fep_main_shortcode_output', $out);
    }
	
    function Header()
    {
      global $user_ID;

      $msgBoxSize = $this->getUserNumMsgs();
      if (fep_get_option('num_messages') == 0 || current_user_can('manage_options'))
        $msgBoxTotal = __("Unlimited", 'fep');
      else
        $msgBoxTotal = fep_get_option('num_messages');

      $header = "<div id='fep-wrapper'>";
      $header .= "<div id='fep-header'>";
      $header .= get_avatar($user_ID, 55)."<p><strong>".__("Welcome", 'fep').": ". fep_get_userdata( $user_ID, 'display_name', 'id' ) ."</strong><br/>";
	  
	  ob_start();
	  do_action('fep_header_note', $user_ID);
	  $header .= ob_get_contents();
	  ob_end_clean();
	  
	  $header .= '<br />';
      if ($msgBoxTotal == __("Unlimited", 'fep') || $msgBoxSize < $msgBoxTotal)
        $header .= __("Message box size", 'fep').": ".$msgBoxSize." ".__("of", 'fep')." ".$msgBoxTotal."</p>";
      else
        $header .= "<font color='red'>".__("Your Message Box Is Full! Please delete some messages.", 'fep')."</font></p>";
      $header .= "</div>";
      return $header;
    }


    function Menu()
    {
      $menu = "<div id='fep-menu'>";
	  
	  ob_start();
	  do_action('fep_menu_button');
	  $menu .= ob_get_clean();
	  
	  $menu .="</div>";
      $menu .= "<div id='fep-content'>";
	  ob_start();
	  do_action('fep_display_before_content');
	  $menu .= ob_get_clean();
	  
      return $menu;
    }
	

    function Footer()
    {
      $footer = '</div>'; //End content
	  
	  if(has_action('fep_footer_note')) {
      $footer .= "<div id='fep-footer'>";
	  ob_start();
	  do_action('fep_footer_note');
	  $footer .= ob_get_clean();
	  
      $footer .= '</div>'; }//End Footer
	  $footer .= '</div>'; //End main wrapper
      
      return $footer;
    }
	
	function getUserNumMsgs()
    {
      global $wpdb, $user_ID;
      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE ((to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1)", $user_ID, $user_ID));
      return $wpdb->num_rows;
    }
	
	    function user_settings()
    {
	  $prefs = "<p><strong>".__("Set your preferences below", 'fep').":</strong></p>";
	  if(isset($_POST['fep-user-form_submit'])){ 
		$errors = $this->user_settings_action();
		if(count($errors->get_error_messages())>0){
			$prefs .= fep_error($errors);
		}
		else{
			$prefs .= '<div id="fep-success">' .__("Your settings have been saved!", 'fep'). ' </div>';
		}
	}
	  $token = fep_create_nonce('user_settings');
      $prefs .= "<form method='post' action='".fep_query_url('settings')."'>
	  
      <input type='checkbox' name='allow_messages' value='1' ".checked(fep_get_user_option( 'allow_messages', 1), '1', false)."/> <i>".__("Allow others to send me messages?", 'fep')."</i><br/>
	  
	  <input type='checkbox' name='allow_emails' value='1' ".checked(fep_get_user_option( 'allow_emails', 1), '1', false)."/> <i>".__("Email me when I get new messages?", 'fep')."</i><br/>";
	  
	  ob_start();
	  do_action('fep_user_settings_form');
	  $prefs .= ob_get_contents();
	  ob_end_clean();

	  $prefs .="<input type='hidden' name='token' value='$token' /><br/>
      <input class='button' type='submit' name='fep-user-form_submit' value='".__("Save Options", 'fep')."' />
      </form>";
      return $prefs;
    }

    function user_settings_action()
    {
      global $user_ID;
      if (isset($_POST['fep-user-form_submit']))
      {
	  $errors = new WP_Error();
	  
      $options = array(	'allow_emails' 	=> ( isset( $_POST['allow_emails'] ) )? 1 : '',
                    'allow_messages' => ( isset( $_POST['allow_messages'] ) )? 1 : ''
        );
		
	  if (!fep_verify_nonce($_POST['token'],'user_settings'))
		  $errors->add('invalidToken', __('Your Token did not verify, Please try again!', 'fep'));
		  
		// This action hook is DEPRECATED since version 3.4. Use following filter hook instead
	  do_action('fep_action_user_settings_before_save', $errors);
	  
	  $options = apply_filters('fep_filter_user_settings_before_save', $options, $errors); //arg $errors added since version 3.4
	  //var_dump($options);
	  
		if(count($errors->get_error_codes())==0){
        update_user_option($user_ID, 'FEP_user_options', $options);
		do_action('fep_user_settings_after_save', $user_ID);
		}
		return $errors;
      }
      return false;
    }
	
	    function new_message()
    {
      global $user_ID;
	  $token = fep_create_nonce('new_message');
	  
      $to = (isset($_GET['to']))? $_GET['to']:'';
	  
	$message_to = ( isset( $_POST['message_to'] ) ) ? esc_html( $_POST['message_to'] ): fep_get_userdata( $to, 'user_login' );
	$message_top = ( isset( $_POST['message_top'] ) ) ? esc_html( $_POST['message_top'] ): fep_get_userdata($to, 'display_name');
	$message_title = ( isset( $_REQUEST['message_title'] ) ) ? esc_html( $_REQUEST['message_title'] ): '';
	$message_content = ( isset( $_REQUEST['message_content'] ) ) ? esc_textarea( $_REQUEST['message_content'] ): '';
	$parent_id = ( isset( $_POST['parent_id'] ) ) ? absint( $_POST['parent_id'] ): 0;
	
        $newMsg = "<p><strong>".__("Create New Message", 'fep').":</strong></p>";
        $newMsg .= "<form action='".fep_query_url('checkmessage')."' method='post' enctype='multipart/form-data'>";
        $MgsTo = __("To", 'fep').": ";
		if(fep_get_option('hide_autosuggest') != '1' || current_user_can('manage_options')) { 
			wp_enqueue_script( 'fep-script' );
			
		$MgsTo .="<noscript>".__('Username of recipient', 'fep')."</noscript><br/>";
        $MgsTo .="<input type='hidden' id='fep-message-to' name='message_to' autocomplete='off' value='$message_to' />
		<input type='text' id='fep-message-top' name='message_top' placeholder='".__('Name of recipient', 'fep')."' autocomplete='off' value='$message_top' /><img src='".FEP_PLUGIN_URL."images/loading.gif' class='fep-ajax-img' style='display:none;'/><br/>
        <div id='fep-result'></div>";
		} else {
		$MgsTo .="<br/><input type='text' name='message_to' placeholder='".__('Username of recipient', 'fep')."' autocomplete='off' value='$message_to' /><br/>";}
		
		$newMsg .= apply_filters( 'fep_message_form_to_filter', $MgsTo, $message_to); //arg $message_to added in 3.4
		
        $newMsg .= __("Subject", 'fep').":<br/>
        <input type='text' name='message_title' placeholder='".__('Subject', 'fep')."' maxlength='65' value='$message_title' /><br/>";
		ob_start();
		do_action('fep_message_form_before_content');
		echo __("Message", 'fep').":<br/>";
		if ('wp_editor' == fep_get_option('editor_type') || current_user_can ('manage_options')){
		wp_editor( $message_content, 'message_content', array('teeny' => false, 'media_buttons' => false) );
		} elseif ('teeny' == fep_get_option('editor_type','teeny')){ 
		wp_editor( $message_content, 'message_content', array('teeny' => true, 'media_buttons' => false) );
		} else {
        echo  "<textarea name='message_content' placeholder='".__('Message Content', 'fep')."'>$message_content</textarea>"; }
		
		do_action('fep_message_form_after_content');
		$newMsg .= ob_get_contents();
		ob_end_clean();
		
        $newMsg .="<input type='hidden' name='message_from' value='$user_ID' />
        <input type='hidden' name='parent_id' value='$parent_id' />
		<input type='hidden' name='token' value='$token' /><br/>
        <input type='submit' name='new_message' value='".__("Send Message", 'fep')."' />
        </form>";
        
        return apply_filters('fep_filter_new_message_form', $newMsg);
    }
	
function new_message_action(){
	$html = '<h2>' . __('Send Message', 'fep') . '</h2>';
	if(isset($_POST['new_message'])){ 
		$errors = $this->check_message();
		if(count($errors->get_error_messages())>0){
			$html .= fep_error($errors);
			$html .= $this->new_message();
		}
		else{
			$html .= '<div id="fep-success">' .__("Message successfully sent.", 'fep'). ' </div>';
		}
	}
	else{
		$html .= $this->new_message();
	}
	return $html;
}

    function check_message()
    {
      global $wpdb, $user_ID;
	  $errors = new WP_Error();
	  $message = $_POST;
	  // print var_dump($_POST);
      if (!empty($message['message_to'])) {
	  $preTo = $message['message_to'];
	  } else {
	  $preTo = ( isset( $message['message_top'] ) ) ? esc_html( $message['message_top'] ): ''; }
	  
	  $preTo = apply_filters( 'fep_preto_filter', $preTo );
	  
      $message['to'] = fep_get_userdata( $preTo );
      $message['send_date'] = current_time('mysql');
      

      //Check for errors first
	  	if (!$message['to'])
		  	$errors->add('invalidTo', __('You must enter a valid recipient!', 'fep'));
        if (!$message['message_title'])
		  $errors->add('invalidSub', __('You must enter subject.', 'fep'));
        if (!$message['message_content'])
		  $errors->add('invalidMgs', __('You must enter some message content!', 'fep'));
        if ($message['message_from'] != $user_ID || $message['to'] == $user_ID )
          $errors->add('NoPermission', __("You do not have permission to send this message!", 'fep'));
		
      if (fep_get_user_option( 'allow_messages', 1, $message['to'] ) != '1')
        $errors->add('ToDisallow', __("This user does not want to receive messages!", 'fep'));
      if (fep_is_message_box_full($message['to'], fep_get_option('num_messages',50), $message['parent_id']))
        $errors->add('MgsBoxFull', __("Your or Recipients Message Box Is Full!", 'fep'));
	  if (fep_is_user_blocked())
        $errors->add('Blocked', __("You cannot send messages because you are blocked by administrator!", 'fep'));
	  $timeDelay = $this->TimeDelay(fep_get_option('time_delay', 0));
	  if ($timeDelay['diffr'] < fep_get_option('time_delay') && !current_user_can('manage_options'))
        $errors->add('TimeDiff', sprintf(__("Please wait at least more %s to send another message!", 'fep'),$timeDelay['time']));
	  if (!fep_verify_nonce($message['token'], 'new_message'))
        $errors->add('InvalidToken', __("Invalid Token. Please try again!", 'fep'));
		
	  if ($message['parent_id'] != 0) {
	  $mgsInfo = $wpdb->get_row($wpdb->prepare("SELECT to_user, from_user FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $message['parent_id']));
	  if ($mgsInfo->to_user != $user_ID && $mgsInfo->from_user != $user_ID && !current_user_can( 'manage_options' ))
          $errors->add('OthersMgs', __("You do not have permission to send this message!", 'fep'));
		  
		  do_action('fep_before_send_new_reply', $errors);
		} else {
		do_action('fep_before_send_new_message', $errors);
		}
	
		// This action hook is DEPRECATED since version 3.4. Use following filter hook instead
	  do_action('fep_action_message_before_send', $errors);
	  
	  $message = apply_filters('fep_filter_message_before_send', $message, $errors); //arg $errors added since version 3.4

      //If no errors then continue on
	  if(count($errors->get_error_codes())==0){
      if ($message['parent_id'] == 0){
	  
	  	$wpdb->insert( FEP_MESSAGES_TABLE, array( 'from_user' => $message['message_from'], 'to_user' => $message['to'], 'message_title' => $message['message_title'], 'message_contents' => $message['message_content'], 'parent_id' => $message['parent_id'], 'last_sender' => $message['message_from'], 'send_date' => $message['send_date'], 'last_date' => $message['send_date'] ), array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )); 
		
		$message_id = $wpdb->insert_id;
		do_action('fep_after_send_new_message', $message_id);
      } else {
	  
	  	$wpdb->insert( FEP_MESSAGES_TABLE, array( 'from_user' => $message['message_from'], 'to_user' => $message['to'], 'message_title' => $message['message_title'], 'message_contents' => $message['message_content'], 'parent_id' => $message['parent_id'], 'send_date' => $message['send_date'] ), array( '%d', '%d', '%s', '%s', '%d', '%s' ));
		
		
		$message_id = $wpdb->insert_id; 
		
		$wpdb->update( FEP_MESSAGES_TABLE, array( 'status' => 0, 'last_sender' => $message['message_from'], 'last_date' => $message['send_date'], 'to_del' => 0, 'from_del' => 0 ), array( 'id' => $message['parent_id'] ), array( '%d', '%d', '%s', '%d', '%d' ), array ( '%d' ));
		
		do_action('fep_after_send_new_reply', $message_id);
      }
	  do_action('fep_action_message_after_send', $message_id, $message);
	  }

      return $errors;
    }
	
function autoembed($string)
    {
      global $wp_embed;
      if (is_object($wp_embed))
        return $wp_embed->autoembed($string);
      else
        return $string;
    }
	
function getWholeThread( $id, $order = 'ASC' )
    {
      global $wpdb;
	  
      $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d ORDER BY send_date $order", $id, $id));
      return $results;
    }
	
function view_message()
    {
      global $wpdb, $user_ID;

      $pID = absint( $_GET['id']);
	  $order = (isset ( $_GET['order'] ) && strtoupper($_GET['order']) == 'DESC' ) ? 'DESC' : 'ASC';
	  if ( 'ASC' == $order ) $anti_order = 'DESC'; else $anti_order = 'ASC';
	  
	  if ( !$pID )
	  return "<div id='fep-error'>".__("You do not have permission to view this message!", 'fep')."</div>";
	  
      $wholeThread = $this->getWholeThread( $pID, $order );

      $threadOut = "<p><strong>".__("Message Thread", 'fep').":</strong></p>";
	  
	  	ob_start();
		  
		  do_action ('fep_display_in_message_header', $pID, $wholeThread );
		  $threadOut .= ob_get_contents();
		  
		  ob_end_clean();
		  
      $threadOut .= "
      <table><tr><th width='15%'>".__("Sender", 'fep')."</th><th width='85%'>".__("Message", 'fep')."</th></tr>";
	  

      foreach ($wholeThread as $post)
      {
        //Check for privacy errors first
        if ($post->to_user != $user_ID && $post->from_user != $user_ID && !current_user_can( 'manage_options' ))
        {
          return "<div id='fep-error'>".__("You do not have permission to view this message!", 'fep')."</div>";
        }

        //setup info for the reply form
        if ($post->parent_id == 0) //If it is the parent message
        {
          $to = $post->from_user;
          if ($to == $user_ID) //Make sure user doesn't send a message to himself
            $to = $post->to_user;
          $message_title = $post->message_title;
          if (substr_count($message_title, __("Re:", 'fep')) < 1) //Prevent all the Re:'s from happening
            $re = __("Re:", 'fep');
          else
            $re = "";
        }

        $threadOut .= "<tr><td><a href='".fep_action_url()."between&with=".fep_get_userdata( $post->from_user, 'user_login', 'id' )."'>" .fep_get_userdata( $post->from_user, 'display_name', 'id' ). "</a><br/><small><a href='".fep_action_url()."viewmessage&id=$pID&order=$anti_order'>" .fep_format_date($post->send_date). "</a></small><br/>".get_avatar($post->from_user, 60)."</td>";

        if ($post->parent_id == 0) //If it is the parent message
        {
          $threadOut .= "<td class='fep-pmtext'><strong>".__("Subject", 'fep').": </strong>".fep_output_filter($post->message_title, true)."<hr/>".fep_output_filter($post->message_contents)."";
		  ob_start();
		  
		  do_action ('fep_display_after_parent_message', $post->id );
		  $threadOut .= ob_get_contents();
		  
		  ob_end_clean();
		  
		$threadOut .="</td></tr>";
		
      if ($post->status == 0 && $user_ID != $post->last_sender && ( $user_ID == $post->from_user || $user_ID == $post->to_user )) //Update only if the reader is not last sender
	  	$wpdb->update( FEP_MESSAGES_TABLE, array( 'status' => 1 ), array( 'id' => $post->id ), array( '%d' ), array( '%d' ));
        }
        else
        {
          $threadOut .= "<td class='fep-pmtext'>".fep_output_filter($post->message_contents)."";
		  ob_start();
		  
		  do_action ('fep_display_after_reply_message', $post->id );
		  $threadOut .= ob_get_contents();
		  
		  ob_end_clean();
		  
		$threadOut .="</td></tr>";
        }
      }

      $threadOut .= "</table>";

      //SHOW THE REPLY FORM
	  if ( fep_is_user_blocked() ){
	  $threadOut .= "<div id='fep-error'>".__("You cannot send messages because you are blocked by administrator!", 'fep')."</div>";
	  
	  } else {
	  
	  $reply_args = array (
	  						'message_to' => fep_get_userdata( $to, 'user_login', 'id' ),
							'message_top' => fep_get_userdata( $to, 'display_name', 'id' ),
							'message_title' => $re.$message_title,
							'message_from' => $user_ID,
							'parent_id' => $pID
							);
	  
	  $threadOut .= fep_reply_form( $reply_args );
	  }

      return $threadOut;
    }
	
	function delete()
    {
      global $wpdb, $user_ID;

      $delID = absint( $_GET['id'] );
	  
	  if (!fep_verify_nonce($_GET['token'], 'delete_message')){
	  return "<div id='fep-error'>".__("Invalid Token!", 'fep')."</div>";}
	  
	  $info = $wpdb->get_row($wpdb->prepare("SELECT from_user, to_user, to_del, from_del FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $delID));

      if ($info->to_user == $user_ID)
      {
        if ($info->from_del == 0){
			$wpdb->update( FEP_MESSAGES_TABLE, array( 'to_del' => 1 ), array( 'id' => $delID ), array( '%d' ), array( '%d' ));
        } else {
		$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
	  $id = implode(',',$ids);
	  
	  do_action ('fep_message_before_delete', $delID, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
		  $wpdb->query("DELETE FROM ".FEP_META_TABLE." WHERE message_id IN ({$id})");
		  }
      }
      elseif ($info->from_user == $user_ID)
      {
        if ($info->to_del == 0){
			$wpdb->update( FEP_MESSAGES_TABLE, array( 'from_del' => 1 ), array( 'id' => $delID ), array( '%d' ), array( '%d' ));
        } else {
		$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
	  $id = implode(',',$ids);
	  
	  do_action ('fep_message_before_delete', $delID, $ids);
	  
          $wpdb->query($wpdb->prepare("DELETE FROM ".FEP_MESSAGES_TABLE." WHERE id = %d OR parent_id = %d", $delID, $delID));
		  $wpdb->query("DELETE FROM ".FEP_META_TABLE." WHERE message_id IN ({$id})");
		  }
      } else {
	  return "<div id='fep-error'>".__("No permission!", 'fep')."</div>";
	  }
		
		return "<div id='fep-success'>".__("Your message was successfully deleted!", 'fep')."</div>";
    }
	

function TimeDelay($DeTime)
    {
		global $wpdb, $user_ID;
		$now = current_time('mysql');
		$Dtime = $DeTime * 60;
		$Prev = $wpdb->get_var($wpdb->prepare("SELECT last_date FROM ".FEP_MESSAGES_TABLE." WHERE parent_id = 0 AND last_sender = %d ORDER BY last_date DESC LIMIT 1", $user_ID));
	  $diff = strtotime($now) - strtotime($Prev);
	  $diffr = $diff/60;
	  $next = strtotime($Prev) + $Dtime;
	  $Ntime = human_time_diff(strtotime($now),$next);
	   return array('diffr' => $diffr, 'time' => $Ntime);
    }

/******************************************MAIN DISPLAY END******************************************/

  } //END CLASS
} //ENDIF

//ADD SHORTCODES
add_shortcode('front-end-pm', array(fep_main_class::init(), 'main_shortcode_output' )); //for FRONT END PM
?>