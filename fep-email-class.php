<?php

if (!class_exists('fep_email_class'))
{
  class fep_email_class
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
	add_action ('fep_action_message_after_send', array(&$this, 'send_email'), 10, 2);
	
	if ( '1' == fep_get_option('notify_ann') )
	add_action ('fep_after_add_announcement', array(&$this, 'notify_users'), 10, 2);
    }
	
	function send_email( $message_id, $mgs )
    {
      $notify = fep_get_user_option( 'allow_emails', 1, $mgs['to'] );
      if ($notify == '1')
      {
        $sendername = get_bloginfo("name");
        $sendermail = get_bloginfo("admin_email");
        $headers = "MIME-Version: 1.0\r\n" .
          "From: ".$sendername." "."<".$sendermail.">\r\n" . 
          "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\r\n";
		$subject =  get_bloginfo("name").': '.__('New Message', 'fep');
		$message = __('You have received a new message in', 'fep'). "\r\n";
		$message .= get_bloginfo("name")."\r\n";
		$message .= sprintf(__("From: %s", 'fep'), fep_get_userdata($mgs['message_from'], 'display_name', 'id') ). "\r\n";
		$message .= sprintf(__("Subject: %s", 'fep'), $mgs['message_title'] ). "\r\n";
		$message .= __('Please Click the following link to view full Message.', 'fep')."\r\n";
		$message .= fep_action_url('messagebox')."\r\n";
        $mailTo = fep_get_userdata( $mgs['to'], 'user_email', 'id');
		
		//wp_mail($mailTo, $subject, $message, $headers); // uncomment this line if you want blog name in message from, comment following line
        wp_mail($mailTo, $subject, $message);
      }
    }
	
	//Mass emails when announcement is created
		function notify_users( $message_id, $mgs) {
		
		$domain_name =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
		$usersarray = get_users("orderby=ID");
		$to = fep_get_option('ann_to', get_bloginfo('admin_email'));
		$from = 'noreply@'.$domain_name;
		
		$bcc = array();
		foreach  ($usersarray as $user) {
		$notify = fep_get_user_option( 'allow_ann', 1, $user->ID);
		if ($notify == '1'){
		$bcc[] = $user->user_email;
			}
		}
		//var_dump($bcc);
	$chunked_bcc = array_chunk($bcc, 25);
	
	$subject =  get_bloginfo("name").': '.__('New Announcement', 'fep');
	$message = __('A new Announcement is Published in ', 'fep')."\r\n";
	$message .= get_bloginfo("name")."\r\n";
	$message .= sprintf(__("Title: %s", 'fep'), $mgs['message_title'] ). "\r\n";
	$message .= __('Please Click the following link to view full Announcement.', 'fep'). "\r\n";
	$message .= fep_action_url('announcements'). "\r\n";
	foreach($chunked_bcc as $bcc_chunk){
        $headers = array();
		$headers['From'] = 'From: '.get_bloginfo("name").'<'.$from.'>';
        $headers['Bcc'] = 'Bcc: '.implode(', ', $bcc_chunk);
		
        wp_mail($to , $subject, $message, $headers);
		}
		return;
    }
	
	
	
  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_email_class::init(), 'actions_filters'));
?>