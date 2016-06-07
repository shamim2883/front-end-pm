<?php

    function fep_plugin_activate()
    {
      global $wpdb;

      $charset_collate = '';
      if( $wpdb->has_cap('collation'))
      {
        if(!empty($wpdb->charset))
          $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if(!empty($wpdb->collate))
          $charset_collate .= " COLLATE $wpdb->collate";
      }
	  $installed_ver = get_option( "fep_db_version" );
	  $installed_meta_ver = get_option( "fep_meta_db_version" );

	if( $installed_ver != FEP_DB_VERSION || $wpdb->get_var("SHOW TABLES LIKE '".FEP_MESSAGES_TABLE."'") != FEP_MESSAGES_TABLE) {

      $sqlMsgs = 	"CREATE TABLE ".FEP_MESSAGES_TABLE." (
            id int(11) NOT NULL auto_increment,
            parent_id int(11) NOT NULL default '0',
            from_user int(11) NOT NULL default '0',
            to_user int(11) NOT NULL default '0',
            last_sender int(11) NOT NULL default '0',
            send_date datetime NOT NULL default '0000-00-00 00:00:00',
            last_date datetime NOT NULL default '0000-00-00 00:00:00',
            message_title varchar(256) NOT NULL,
            message_contents longtext NOT NULL,
            status int(11) NOT NULL default '0',
            to_del int(11) NOT NULL default '0',
            from_del int(11) NOT NULL default '0',
            PRIMARY KEY (id))
            {$charset_collate};";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      dbDelta($sqlMsgs);
	  update_option( "fep_db_version", FEP_DB_VERSION );
	  //var_dump('1');
	  }
	  
	  	if( $installed_meta_ver != FEP_META_VERSION || $wpdb->get_var("SHOW TABLES LIKE '".FEP_META_TABLE."'") != FEP_META_TABLE) {

      $sql_meta = 	"CREATE TABLE ".FEP_META_TABLE." (
            meta_id int(11) NOT NULL auto_increment,
            message_id int(11) NOT NULL default '0',
            field_name varchar(128) NOT NULL,
            field_value longtext NOT NULL,
            PRIMARY KEY (meta_id),
			KEY (field_name))
            {$charset_collate};";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      dbDelta($sql_meta);
	  update_option( "fep_meta_db_version", FEP_META_VERSION );
	  //var_dump('2');
	  }
	  //var_dump('3');
    }
	
function fep_translation()
	{
	//SETUP TEXT DOMAIN FOR TRANSLATIONS
	load_plugin_textdomain('fep', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
function fep_enqueue_scripts()
    {
	if ( !wp_style_is ( 'fep-style' ) )
	wp_enqueue_style( 'fep-style', FEP_PLUGIN_URL . 'style/style.css' );
	$custom_css = trim(fep_get_option('custom_css'));
	wp_add_inline_style( 'fep-style', $custom_css );
	
	wp_register_script( 'fep-script', FEP_PLUGIN_URL . 'js/script.js', array( 'jquery' ), '3.1', true );
	wp_localize_script( 'fep-script', 'fep_script', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce('fep-autosuggestion')
			) 
		);
		
	wp_register_script( 'fep-notification-script', FEP_PLUGIN_URL . 'js/notification.js', array( 'jquery' ), '3.1', true );
	wp_localize_script( 'fep-notification-script', 'fep_notification_script', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce('fep-notification')
			) 
		);
	
	
	wp_register_script( 'fep-attachment-script', FEP_PLUGIN_URL . 'js/attachment.js', array( 'jquery' ), '3.1', true );
	wp_localize_script( 'fep-attachment-script', 'fep_attachment_script', 
			array( 
				'remove' => esc_js(__('Remove', 'fep')),
				'maximum' => esc_js( fep_get_option('attachment_no', 4) ),
				'max_text' => esc_js(__('Maximum file allowed', 'fep'))
				
			) 
		);
    }
 
 
function fep_get_option( $option, $default = '', $section = 'FEP_admin_options' ) {
	
    $options = get_option( $section );

    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}

function fep_get_user_option( $option, $default = '', $userid = '', $section = 'FEP_user_options' ) {
			
    $options = get_user_option( $section, $userid ); //if $userid = '' current user option will be return

    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}

function fep_page_id() {

	global $wpdb;
	
	if ( false === ($id = get_transient('fep_page_id'))){
	
		$id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[front-end-pm]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
		
		if ($id)
		set_transient('fep_page_id', $id, 60*60*24);
		}
		
     return apply_filters( 'fep_page_id_filter', $id);
}

function fep_action_url( $action = '' ) {
      global $wp_rewrite;
      if($wp_rewrite->using_permalinks())
        $delim = '?';
      else
        $delim = '&';
	  
	  return get_permalink(fep_page_id()).$delim."fepaction=$action";
}

function fep_query_url( $action, $arg = array() ) {
      
	  $args = array( 'fepaction' => $action );
	  $args = array_merge( $args, $arg );
	  
	  $permalink = apply_filters( 'fep_page_permalink_filter', get_permalink( fep_page_id() ), fep_page_id(), $args );
	  
	  if ( $permalink )
	  return esc_url( add_query_arg( $args, $permalink ) );
	  else
	  return esc_url( add_query_arg( $args ) );
}

if ( !function_exists('fep_create_nonce') ) :
 /**
 * Creates a token usable in a form
 * return nonce with time
 * @return string
 */
	function fep_create_nonce($action = -1) {
   	 $time = time();
    	$nonce = wp_create_nonce($time.$action);
    return $nonce . '-' . $time;
	}	

endif;

if ( !function_exists('fep_verify_nonce') ) :
 /**
 * Check if a token is valid. Mark it as used
 * @param string $_nonce The token
 * @return bool
 */
	function fep_verify_nonce( $_nonce, $action = -1) {

    //Extract timestamp and nonce part of $_nonce
    $parts = explode( '-', $_nonce );
    $nonce = $parts[0]; // Original nonce generated by WordPress.
    $generated = $parts[1]; //Time when generated

    $nonce_life = 60*60; //We want these nonces to have a short lifespan
    $expire = (int) $generated + $nonce_life;
    $time = time(); //Current time
		// bad formatted onetime-nonce
	if ( empty( $nonce ) || empty( $generated ) )
		return false;

    //Verify the nonce part and check that it has not expired
    if( ! wp_verify_nonce( $nonce, $generated.$action ) || $time > $expire )
        return false;

    //Get used nonces
    $used_nonces = get_option('_fep_used_nonces');

    //Nonce already used.
    if( isset( $used_nonces[$nonce] ) )
        return false;

    foreach ($used_nonces as $nonces => $timestamp){
        if( $timestamp < $time ){
        //This nonce has expired, so we don't need to keep it any longer
        unset( $used_nonces[$nonces] );
		}
    }

    //Add nonce to used nonces and sort
    $used_nonces[$nonce] = $expire;
    asort( $used_nonces );
    update_option( '_fep_used_nonces',$used_nonces );
	return true;
}
endif;

function fep_error($wp_error){
	if(!is_wp_error($wp_error)){
		return '';
	}
	if(count($wp_error->get_error_messages())==0){
		return '';
	}
	$errors = $wp_error->get_error_messages();
	if (is_admin())
	$html = '<div id="message" class="error">';
	else
	$html = '<div id="fep-wp-error">';
	foreach($errors as $error){
		$html .= '<strong>' . __('Error', 'fep') . ': </strong>'.esc_html($error).'<br />';
	}
	$html .= '</div>';
	return $html;
}

function fep_get_new_message_number()
    {
      global $wpdb, $user_ID;

      $get_pms = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0 AND status = 0 AND last_sender <> %d) OR (from_user = %d AND parent_id = 0 AND from_del = 0 AND status = 0 AND last_sender <> %d)", $user_ID, $user_ID, $user_ID, $user_ID));
      return $wpdb->num_rows;
    }
	
function fep_get_new_message_button(){
	if (fep_get_new_message_number()){
	  	$newmgs = " (<font color='red'>";
		$newmgs .= fep_get_new_message_number();
		$newmgs .='</font>)';
		} else {
		$newmgs = '';
	}
		
	return $newmgs;
}

    function fep_get_version()
    {
      $plugin_data = implode('', file(FEP_PLUGIN_DIR."front-end-pm.php"));
      if (preg_match("|Version:(.*)|i", $plugin_data, $version))
        $version = trim($version[1]);
		if (preg_match("|dbVersion:(.*)|i", $plugin_data, $dbversion))
        $dbversion = trim($dbversion[1]);
		if (preg_match("|metaVersion:(.*)|i", $plugin_data, $metaversion))
        $metaversion = trim($metaversion[1]);
      return array('version' => $version, 'dbversion' => $dbversion, 'metaversion' => $metaversion);
    }
	
function fep_is_message_box_full($to, $boxSize, $parentID)
    {
      global $wpdb;

      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)", $to, $to));
      $num = $wpdb->num_rows;

      if ($boxSize == 0 || $num < $boxSize || $parentID != 0 || current_user_can('manage_options') || user_can( $to, 'manage_options' ))
        return false;
      else
        return true;
}

function fep_is_user_blocked( $login = '' ){
	global $user_login;
	if ( !$login && $user_login )
	$login = $user_login;
	
	if ($login){
	$wpusers = explode(',', str_replace(' ', '', fep_get_option('have_permission')));
	//var_dump($wpusers);
		if(in_array( $login, $wpusers))
		return true;
		} //User not logged in
	return false;
}

function fep_get_userdata($data, $need = 'ID', $type = 'login' )
		{
		if (!$data)
		return '';
		
		$type = strtolower($type);
		if ( !in_array($type, array ('id', 'slug', 'email', 'login' )))
		return '';
		
		$user = get_user_by( $type , $data);
		if ( $user && in_array($need, array('ID', 'user_login', 'display_name', 'user_email')))
		return $user->$need;
		else
		return '';
		}

function fep_message_box($action = '', $title = '', $total_message = false, $messages = false )
{
	global $user_ID;
	  
	  
	  if ( !$action )
	  $action = ( isset( $_GET['fepaction']) && $_GET['fepaction'] )? $_GET['fepaction']: 'messagebox';
	  
	  if ( !$title )
	  $title = __('Your Messages', 'fep');
	  $title = apply_filters('fep_message_headline', $title, $action );
	  
	  if( false === $total_message )
	  $total_message = fep_get_user_total_message( $action );
	  
	  if( false === $messages )
	  $messages = fep_get_user_messages( $action );
	  
	  $msgsOut = '';
      if ($total_message)
      {
			  	ob_start();
	  			do_action('fep_display_before_messagebox', $action);
	  			$msgsOut .= ob_get_clean();
				
			$msgsOut .= "<p><strong>$title: ($total_message)</strong></p>";
		
        $numPgs = $total_message / fep_get_option('messages_page',50);
		$page = ( isset ($_GET['feppage']) && $_GET['feppage']) ? absint($_GET['feppage']) : 0;
		
        if ($numPgs > 1)
        {
          $msgsOut .= "<p><strong>".__("Page", 'fep').": </strong> ";
          for ($i = 0; $i < $numPgs; $i++)
            if ( $page != $i){
			  $msgsOut .= "<a href='".esc_url( fep_action_url($action) )."&feppage=".$i."'>".($i+1)."</a> ";
            } else {
              $msgsOut .= "[<b>".($i+1)."</b>] ";}
          $msgsOut .= "</p>";
        }

        $msgsOut .= "<table><tr class='fep-head'>
        <th width='20%'>".__("Started By", 'fep')."</th>
		<th width='20%'>".__("To", 'fep')."</th>
        <th width='40%'>".__("Subject", 'fep')."</th>
        <th width='20%'>".__("Last Reply By", 'fep')."</th></tr>";
        
		$a = 0;
        foreach ($messages as $msg)
        {
          if ($msg->status == 0 && $msg->last_sender != $user_ID)
            $status = "<font color='#FF0000'>".__("Unread", 'fep')."</font>";
          else
            $status = __("Read", 'fep');
			
			$status = apply_filters ('fep_filter_status_display', $status, $msg, $action );
			
		  $msgsOut .= "<tr class='fep-trodd".$a."'>";
		  if ($msg->from_user != $user_ID){
          $msgsOut .= "<td><a href='".fep_action_url()."between&with=".fep_get_userdata( $msg->from_user, 'user_login', 'id' )."'>" .fep_get_userdata( $msg->from_user, 'display_name', 'id' ). "</a><br/><small>".fep_format_date($msg->send_date)."</small></td>"; }
		  else {
		  $msgsOut .= "<td>" .fep_get_userdata( $msg->from_user, 'display_name', 'id' ). "<br/><small>".fep_format_date($msg->send_date)."</small></td>"; }
		  if ( $msg->to_user != $user_ID ){
          $msgsOut .= "<td><a href='".fep_action_url()."between&with=".fep_get_userdata( $msg->to_user, 'user_login', 'id' )."'>" .fep_get_userdata( $msg->to_user, 'display_name', 'id' ). "</a></td>";}
		  else {
		  $msgsOut .= "<td>" .fep_get_userdata( $msg->to_user, 'display_name', 'id' ). "</td>";}
		  $msgsOut .= "<td><a href='".fep_action_url()."viewmessage&id=".$msg->id."'>".fep_output_filter($msg->message_title,true)."</a><br/><small>".$status."</small></td>";
		  $msgsOut .= "<td>" .fep_get_userdata( $msg->last_sender, 'display_name', 'id' ). "<br/><small>".fep_format_date($msg->last_date)."</small></td>";
          $msgsOut .=  "</tr>";
		   //Alternate table colors
		  if ($a) $a = 0; else $a = 1;
        }
        $msgsOut .= "</table>";
		

        return apply_filters('fep_messagebox', $msgsOut, $action);
      }
      else
      {
        return "<div id='fep-error'>".apply_filters('fep_filter_messagebox_empty', sprintf(__("%s empty", 'fep'), $title ), $action)."</div>";
      }
	
}

function fep_get_user_total_message( $action = 'messagebox', $userID = 0 )
    {
      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;
	  
	  if ( has_filter("fep_user_total_message_count_{$action}") ){
	  
	  $count = apply_filters( "fep_user_total_message_count_{$action}" , 0, $action );
	  
	  } elseif ( 'inbox' == $action ){
	  
      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0) AND (status = 0 OR status = 1)", $userID));
	  $count = $wpdb->num_rows;
	  
	  } elseif ( 'outbox' == $action ){
	  
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE (from_user = %d AND parent_id = 0 AND from_del = 0) AND (status = 0 OR status = 1)", $userID));
	  $count = $wpdb->num_rows;
	  
	  } else {
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT id FROM ".FEP_MESSAGES_TABLE." WHERE ((to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1)", $userID, $userID));
	  $count = $wpdb->num_rows;
	  
	  }
	  
      return $count;
    }
	
function fep_get_user_messages( $action = 'messagebox', $userID = 0 )
    {
      global $wpdb, $user_ID;
	  
	  if ( !$userID )
	  $userID = $user_ID;
	  
	  $page = ( isset ($_GET['feppage']) && $_GET['feppage']) ? absint($_GET['feppage']) : 0;
	  
      $start = $page * fep_get_option('messages_page', 50);
      $end = fep_get_option('messages_page', 50);
	  
	  if ( has_filter("fep_user_messages_{$action}") ){
	  
	  $get_messages = apply_filters( "fep_user_messages_{$action}" , array(), $action );
	  
	  } elseif ( 'inbox' == $action ){
	  
      $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE (to_user = %d AND parent_id = 0 AND to_del = 0) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $userID, $start, $end));
	  
	  } elseif ( 'outbox' == $action ){
	  
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE (from_user = %d AND parent_id = 0 AND from_del = 0) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $userID, $start, $end));
	  
	  } else {
	  $get_messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_MESSAGES_TABLE." WHERE ((to_user = %d AND parent_id = 0 AND to_del = 0) OR (from_user = %d AND parent_id = 0 AND from_del = 0)) AND (status = 0 OR status = 1) ORDER BY last_date DESC LIMIT %d, %d", $userID, $userID, $start, $end));
	  
	  }
	  
      return $get_messages;
    }
	
function fep_get_message_meta($message_id, $name = ''){
	global $wpdb;
		if (is_array($name)){
			$string_name = implode (',',$name);
					$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name IN (%s)",								 						$message_id, $string_name));
						} elseif ($name) {
							$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE message_id = %d AND field_name = %s",	 							$message_id, $name));
							} else {
						$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE message_id = %d",$message_id));
					}
			
				if ($results){
			return $results;
		} else {
	return array();}
}

function fep_format_date($date)
    {
		$now = current_time('mysql');
      //return date('M d, h:i a', strtotime($date));
	  $formate = human_time_diff(strtotime($date),strtotime($now)).' '.__('ago', 'fep');
	  
	  return apply_filters( 'fep_formate_date', $formate, $date );
    }
	
	function fep_output_filter($string, $title = false)
    {
		$string = stripslashes($string);
		
	  if ($title) {
	  $html = apply_filters('fep_filter_display_title', $string);
	  } else {
	  $html = apply_filters('fep_filter_display_message', $string);
	  }
	  
      return $html;
    }

	
	function fep_reply_form($args = '') {
	global $user_ID;
	$defaults = array (
						'message_from' => 	$user_ID,
						'message_to' =>		'',
						'message_top' =>	'',
						'message_title' =>	'',
						'parent_id' => 		0,
						'token' => 			fep_create_nonce('new_message')
						);
						
	$args = wp_parse_args($args, $defaults);
	
	$reply_form = "
      <p><strong>".__("Add Reply", 'fep').":</strong></p>
      <form action='".fep_query_url('checkmessage')."' method='post' enctype='multipart/form-data'><br/>";
	  
      ob_start();
		do_action('fep_reply_form_before_content');
		
		if ('wp_editor' == fep_get_option('editor_type') || current_user_can ('manage_options')){
		wp_editor( '', 'message_content', array('teeny' => false, 'media_buttons' => false) );
		} elseif ('teeny' == fep_get_option('editor_type','teeny')){ 
		wp_editor( '', 'message_content', array('teeny' => true, 'media_buttons' => false) );
		} else {
        echo  "<textarea name='message_content' placeholder='".__('Message Content', 'fep')."'></textarea>"; }
		
		do_action('fep_reply_form_after_content');
		
		$reply_form .= ob_get_contents();
		ob_end_clean();
		
        $reply_form .="
      <input type='hidden' name='message_to' value='".$args['message_to']."' />
	  <input type='hidden' name='message_top' value='".$args['message_top']."' />
      <input type='hidden' name='message_title' value='".$args['message_title']."' />
      <input type='hidden' name='message_from' value='".$args['message_from']."' />
      <input type='hidden' name='parent_id' value='".$args['parent_id']."' />
	  <input type='hidden' name='token' value='".$args['token']."' /><br/>
      <input type='submit' name='new_message' value='".__("Send Message", 'fep')."' />
      </form>";
	
	return apply_filters('fep_reply_form', $reply_form );
	}
	
function fep_include_require_files() 
	{
	if ( is_admin() ) 
		{
			$fep_files = array(
							'admin' => 'admin/fep-admin-class.php'
							);
										
		} else {
			$fep_files = array(
							'main' => 'fep-class.php',
							'menu' => 'fep-menu-class.php',
							'between' => 'fep-between-class.php',
							'directory' => 'fep-directory-class.php',
							'frontend-admin' => 'admin/fep-admin-frontend-class.php',
							'email' => 'fep-email-class.php'
							);
				}
	$fep_files['announcement'] = 'fep-announcement-class.php';
	$fep_files['widgets'] = 'fep-widgets.php';
	$fep_files['functions'] = 'functions.php';
	$fep_files['attachment'] = 'fep-attachment-class.php';
					
	$fep_files = apply_filters('fep_include_files', $fep_files );
	
	foreach ( $fep_files as $fep_file ) {
	require_once ( $fep_file );
		}
	}

add_action('template_redirect', 'fep_download_file');

function fep_download_file()
		{
		if ( !isset($_GET['fepaction']) || $_GET['fepaction'] != 'download')
		return;
		
			global $wpdb, $user_ID;
	$id = absint($_GET['id']);

	if ( !fep_verify_nonce($_GET['token'], 'download') )
	wp_die(__('Invalid token', 'fep'));

	$msgsMeta = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".FEP_META_TABLE." WHERE meta_id = %d", $id));
	if (!$msgsMeta)
	wp_die(__('No attachment found', 'fep'));

	$message_id = $msgsMeta->message_id;

	$unserialized_file = maybe_unserialize( $msgsMeta->field_value );
		  
	if ( $msgsMeta->field_name != 'attachment' || !$unserialized_file['type'] || !$unserialized_file['url'] || !$unserialized_file['file'] )
	wp_die(__('Invalid Attachment', 'fep'));

		$attachment_type = $unserialized_file['type'];
		$attachment_url = $unserialized_file['url'];
		$attachment_path = $unserialized_file['file'];
		$attachment_name = basename($attachment_url);

	$msgsInfo = $wpdb->get_row($wpdb->prepare("SELECT from_user, to_user, status FROM ".FEP_MESSAGES_TABLE." WHERE id = %d", $message_id));

	if (!$msgsInfo)
	wp_die(__('Message already deleted', 'fep'));

	if ( $msgsInfo->from_user != $user_ID && $msgsInfo->to_user != $user_ID && $msgsInfo->status != 2 && !current_user_can('manage_options') )
	wp_die(__('No permission', 'fep'));

	if(!file_exists($attachment_path)){
	$wpdb->query($wpdb->prepare("DELETE FROM ".FEP_META_TABLE." WHERE meta_id = %d", $id));
	wp_die(__('Attachment already deleted', 'fep'));
	}
	
	
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header("Content-Type: $attachment_type", true, 200);
		header("Content-Disposition: attachment; filename=\"$attachment_name\"");
		header("Content-Length: " . filesize($attachment_path));
		nocache_headers();
		
		//clean all levels of output buffering
		while (ob_get_level()) {
    		ob_end_clean();
		}
		
		readfile($attachment_path);
		
			exit;
		}