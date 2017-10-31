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
			
			if( fep_notification_script.show_in_title == "1" ){
				fep_show_count_in_title( response['message_unread_count'], response['message_unread_count_i18n'] );
			}
			
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
jQuery(document).ready(function(){
	if( fep_notification_script.call_on_ready == "1" ){
		fep_notification_ajax_call();
	}
	setInterval(fep_notification_ajax_call, parseInt(fep_notification_script.interval, 10) );
});
