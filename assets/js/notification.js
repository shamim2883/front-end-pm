jQuery(document).ready(function(){
var data = {
			action: 'fep_notification_ajax',
			token: fep_notification_script.nonce
			};
var fep_ajax_call = function(){
	jQuery.post(fep_notification_script.ajaxurl, data, function(response) {
		
		jQuery('.fep_new_message_count').html(response['message_count_i18n']);
		if ( response['message_count'] ){ 
			jQuery('.fep_new_message_count_hide_if_zero').show();
		} else {
			jQuery('.fep_new_message_count_hide_if_zero').hide();
		}
		
		jQuery('.fep_new_announcement_count').html(response['announcement_count_i18n']);
		if ( response['announcement_count'] ){ 
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
		jQuery('.fep_new_message_count_text').html(response['message_count_text']);
		jQuery('.fep_new_announcement_count_text').html(response['announcement_count_text']);
		
	}, 'json');
}
setInterval(fep_ajax_call, parseInt(fep_notification_script.interval, 10) );
});
