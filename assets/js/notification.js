	var fep_notification_block_count = 0;
	var fep_sound = new Audio( fep_notification_script.sound_url );
	function fep_notification_ajax_call(){
		
		if ( document.hidden || document.msHidden || document.mozHidden || document.webkitHidden ) {
			if( fep_notification_block_count < parseInt(fep_notification_script.skip, 10) ){
				fep_notification_block_count++;
				return;				
			}
		}
		fep_notification_block_count = 0;
		
		var data = {
			action: 'fep_notification_ajax',
			token: fep_notification_script.nonce
		};
					
		jQuery.post(fep_notification_script.ajaxurl, data, function(response) {
			
			jQuery('.fep_unread_message_count').html(response['message_unread_count_i18n']);
			jQuery('.fep_unread_announcement_count').html(response['announcement_unread_count_i18n']);
			jQuery('.fep_total_message_count').html(response['message_total_count_i18n']);
			jQuery('.fep_unread_message_count_text').html(response['message_unread_count_text']);
			jQuery('.fep_unread_announcement_count_text').html(response['announcement_unread_count_text']);
			
			if ( response['message_unread_count'] ){ 
				jQuery('.fep_unread_message_count_hide_if_zero').show();
			} else {
				jQuery('.fep_unread_message_count_hide_if_zero').hide();
			}
			
			if ( response['announcement_unread_count'] ){ 
				jQuery('.fep_unread_announcement_count_hide_if_zero').show();
			} else {
				jQuery('.fep_unread_announcement_count_hide_if_zero').hide();
			}
			
			if ( response['announcement_unread_count'] && response['message_unread_count'] ){ 
				jQuery('.fep_hide_if_anyone_zero').show();
			} else {
				jQuery('.fep_hide_if_anyone_zero').hide();
			}
			
			if ( response['announcement_unread_count'] || response['message_unread_count'] ){ 
				jQuery('.fep_hide_if_both_zero').show();
			} else {
				jQuery('.fep_hide_if_both_zero').hide();
			}
			if ( response['notification_bar'] ){
				jQuery('.fep-notification-bar').show();
			} else{
				jQuery('.fep-notification-bar').hide();
			}
			
			if( fep_notification_script.show_in_title == "1" ){
				fep_show_count_in_title( response['message_unread_count'], response['message_unread_count_i18n'] );
			}
			if( fep_notification_script.show_in_desktop == "1" ){
				fep_desktop_notification( response );
			}
			if( fep_notification_script.play_sound == "1"
			&& ( response['message_unread_count'] || response['announcement_unread_count'] )
		  	&& ( response['message_unread_count'] > response['message_unread_count_prev'] || response['announcement_unread_count'] > response['announcement_unread_count_prev'] ) ){
				fep_sound.play();
			}
			jQuery(document).trigger( 'fep_notification', response );
			
		}, 'json');
	}
	
	var fep_prev_count = -1;
	function fep_show_count_in_title( count, count_i18n ){
		if( fep_prev_count === count )
		return;
		fep_prev_count = count;
		
		var title = document.title;
		// this will test if the document title already has a notification count in it, e.g. (1) website title
		if ( title.charAt(0) === '(' && title.indexOf(') ') !== -1 ) {
			// we will split the title after the first bracket
			title = title.split(') ');
			
			if( count )
			document.title = '(' + count_i18n + ') ' + title[1];
			else
			document.title = title[1];
		} else {
			if( count )
			document.title = '(' + count_i18n + ') ' + title;
		}
	}
	function fep_desktop_notification( response ){
		//console.log( response );
		// Let's check if the browser supports notifications
		if( "Notification" in window ){
			if( Notification.permission === 'denied' ){
				//denied, so nothing to do
			} else if( Notification.permission === 'default' ) {
				Notification.requestPermission();
				if( Notification.permission === 'granted' ) {
					fep_desktop_notification_show( response );
				}
			} else {
				fep_desktop_notification_show( response );
			}
		} else if( "webkitNotifications" in window ) {
            fep_desktop_notification_show( response );
        } else if( "mozNotification" in navigator ){
			fep_desktop_notification_show( response );
		}
	}
	function fep_desktop_notification_show( response ){
		
		var title, body, link, notification;
		
		//Multiple notification in same time create issue in Firefox. So we will show only message OR announcement notification
		if( response['message_unread_count']
		&& response['message_unread_count'] > response['message_unread_count_prev'] ){
			title = fep_notification_script.mgs_notification_title;
			body = fep_notification_script.mgs_notification_body;
			link = fep_notification_script.mgs_notification_url;
		}
		else if( response['announcement_unread_count']
		&& response['announcement_unread_count'] > response['announcement_unread_count_prev'] ){
			title = fep_notification_script.ann_notification_title;
			body = fep_notification_script.ann_notification_body;
			link = fep_notification_script.ann_notification_url;
		} else {
			return false;
		}
		if( "Notification" in window ){
			notification = new Notification(
				title, {
					body: body,
					icon: fep_notification_script.icon_url
				}
			);
			notification.onclick = function ( event ) {
				location.href = link;
			};
			notification.onerror = function ( event ) {
				
			};
		} else if( "webkitNotifications" in window ) {
            notification = window.webkitNotifications.createNotification(fep_notification_script.icon_url, title, body);
            notification.show();
        } else if( "mozNotification" in navigator ){
			notification = navigator.mozNotification.createNotification(title, body, fep_notification_script.icon_url);
			notification.show();
		}
	}

jQuery(document).ready(function(){
	if( fep_notification_script.call_on_ready == "1" ){
		fep_notification_ajax_call();
	}
	if( fep_notification_script.show_in_desktop == "1" && ("Notification" in window) && Notification.permission === 'default' ){
		Notification.requestPermission();
	}
	setInterval(fep_notification_ajax_call, parseInt(fep_notification_script.interval, 10) );
	
	jQuery('.fep-notification-bar .fep-notice-dismiss').on('click', function(){
		jQuery(this).parent().hide('slow');
		var data = {
			action: 'fep_notification_dismiss',
			token: fep_notification_script.nonce
		};
					
		jQuery.get(fep_notification_script.ajaxurl, data );
	});
});
