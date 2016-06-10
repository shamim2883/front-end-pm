<?php

class Fep_Emails
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
		//add_action ('publish_fep_message', array($this, 'publish_send_email'), 10, 2);
		add_action ('transition_post_status', array($this, 'publish_send_email'), 10, 3);
		add_action( 'fep_save_message', array($this, 'save_send_email'), 20, 2 ); //after '_participants' meta saved, if from Back End
		add_action( 'fep_action_message_after_send', array($this, 'save_send_email'), 20, 2 ); //Front End
		
		if ( '1' == fep_get_option('notify_ann') ){
			add_action ('transition_post_status', array($this, 'publish_notify_users'), 10, 3);
			add_action( 'fep_save_announcement', array($this, 'save_notify_users'), 20, 2 ); //after '_participant_roles' meta saved
		}
    }
	
	function publish_send_email( $new_status, $old_status, $post )
	{
		 if ( 'fep_message' != $post->post_type || $old_status == 'publish'  || $new_status != 'publish' ) {
		 	return;
		}
		if( get_post_meta( $post->ID, '_fep_email_sent', true ) )
			return;
		
		$this->send_email( $post->ID, $post );
	}
	
	function save_send_email( $postid, $post )
	{
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $postid );
		}
		if( 'publish' != $post->post_status )
			return;
		
		if( get_post_meta( $postid, '_fep_email_sent', true ) )
			return;
			
		$this->send_email( $postid, $post );
	}
	
	function send_email( $postid, $post ){
		
		$participants = get_post_meta( $postid, '_participants' );
		
		if( $participants && is_array( $participants ) )
		{
			fep_add_email_filters();
			
			$subject =  get_bloginfo("name").': '.__('New Message', 'front-end-pm');
			$message = __('You have received a new message in', 'front-end-pm'). "\r\n";
			$message .= get_bloginfo("name")."\r\n";
			$message .= sprintf(__("From: %s", 'front-end-pm'), fep_get_userdata( $post->post_author, 'display_name', 'id') ). "\r\n";
			$message .= sprintf(__("Subject: %s", 'front-end-pm'),  $post->post_title ). "\r\n";
			$message .= __('Please Click the following link to view full Message.', 'front-end-pm')."\r\n";
			$message .= fep_query_url('messagebox')."\r\n";
			
			if( 'html' == fep_get_option('email_content_type','html') ) {
				$message = nl2br( $message );
			}
			
			foreach( $participants as $participant ) 
			{
				if( $participant == $post->post_author )
					continue;
					
				if( ! fep_get_user_option( 'allow_emails', 1, $participant ) )
					continue;
					
				$to = fep_get_userdata( $participant, 'user_email', 'id');
				
				if( ! $to )
					continue;
						
				wp_mail( $to, $subject, $message );
			} //End foreach
			
			fep_remove_email_filters();
			
			update_post_meta( $post->ID, '_fep_email_sent', time() );
		}
	}
	
	function publish_notify_users( $new_status, $old_status, $post )
	{
		 if ( 'fep_announcement' != $post->post_type || $old_status == 'publish'  || $new_status != 'publish' ) {
		 	return;
		}
		if( get_post_meta( $post->ID, '_fep_email_sent', true ) )
			return;
		
		$this->notify_users( $post->ID, $post );
	}
	
	function save_notify_users( $postid, $post )
	{
		if( 'publish' != $post->post_status )
			return;
		
		if( get_post_meta( $postid, '_fep_email_sent', true ) )
			return;
			
		$this->notify_users( $postid, $post );
	}
	
	//Mass emails when announcement is created
	function notify_users( $postid, $post ) {
		
		$roles = get_post_meta( $postid, '_participant_roles' );
		
		if( !$roles || !is_array( $roles ) ) {
			return;
		} 
		$args = array( 
				'role__in' => $roles,
				'orderby' => 'ID' 
		);
		$usersarray = get_users( $args );
		$to = fep_get_option('ann_to', get_bloginfo('admin_email'));
		
		$bcc = array();
		foreach  ($usersarray as $user) {
			$notify = fep_get_user_option( 'allow_ann', 1, $user->ID);
			
			if ($notify == '1'){
				$bcc[] = $user->user_email;
			}
		}
		//var_dump($bcc);
		$chunked_bcc = array_chunk($bcc, 25);
		
		$subject =  get_bloginfo("name").': '.__('New Announcement', 'front-end-pm');
		$message = __('A new Announcement is Published in ', 'front-end-pm')."\r\n";
		$message .= get_bloginfo("name")."\r\n";
		$message .= sprintf(__("Title: %s", 'front-end-pm'), $post->post_title ). "\r\n";
		$message .= __('Please Click the following link to view full Announcement.', 'front-end-pm'). "\r\n";
		$message .= fep_query_url('announcements'). "\r\n";
		
		if( 'html' == fep_get_option('email_content_type','html') ) {
			$message = nl2br( $message );
		}
			
	fep_add_email_filters();
	
	foreach($chunked_bcc as $bcc_chunk){
			$headers = array();
			$headers['Bcc'] = 'Bcc: '.implode(',', $bcc_chunk);
			
			wp_mail($to , $subject, $message, $headers);
		}
		
	fep_remove_email_filters();
	
	update_post_meta( $post->ID, '_fep_email_sent', time() );
	
    }
	
	
	
  } //END CLASS

add_action('wp_loaded', array(Fep_Emails::init(), 'actions_filters'));

