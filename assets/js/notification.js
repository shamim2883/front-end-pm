	var fep_notification_block_count = 0;
	function fep_notification_ajax_call(){
		
		if ( document.hidden ) {
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
			
			jQuery('.fep_new_message_count').html(response['message_unread_count_i18n']);
			if ( response['message_unread_count'] ){ 
				jQuery('.fep_new_message_count_hide_if_zero').show();
			} else {
				jQuery('.fep_new_message_count_hide_if_zero').hide();
			}
			
			jQuery('.fep_new_announcement_count').html(response['announcement_unread_count_i18n']);
			if ( response['announcement_unread_count'] ){ 
				jQuery('.fep_new_announcement_count_hide_if_zero').show();
			} else {
				jQuery('.fep_new_announcement_count_hide_if_zero').hide();
			}
			
			jQuery('.fep-notification').html(response['notification']);
			if ( response['notification'] ){ 
				jQuery('.fep-notification').show();
			} else {
				jQuery('.fep-notification').hide();
			}
			jQuery('.fep_new_message_count_text').html(response['message_unread_count_text']);
			jQuery('.fep_total_message_count').html(response['message_total_count_i18n']);
			jQuery('.fep_new_announcement_count_text').html(response['announcement_unread_count_text']);
			if( fep_notification_script.show_in_title == "1" ){
				fep_show_count_in_title( response['message_unread_count'] );
			}
			
		}, 'json');
	}
	
	function fep_show_count_in_title( count ){
		var title = document.title;
		// this regex will test if the document title already has a notification count in it, e.g. (1) My Document
		if (/\([\d]+\)/.test(title)) {
			// we will split the title after the first bracket
			title = title.split(') ');
			prev_count = title[0].substring(1);
			
			// only proceed when the notification count is difference to our ajax request
			if (prev_count != count ){
				if( count )
				document.title = '(' + count + ') ' + title[1];
				else
				document.title = title[1];
			}  
		} else {
			if( count )
			document.title = '(' + count + ') ' + title;
		}
	}
jQuery(document).ready(function(){
	setInterval(fep_notification_ajax_call, parseInt(fep_notification_script.interval, 10) );
});
