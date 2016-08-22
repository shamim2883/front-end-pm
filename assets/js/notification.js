jQuery(document).ready(function(){
		var data = {
					action: 'fep_notification_ajax',
					token: fep_notification_script.nonce
					};
        var fep_ajax_call = function(){
		jQuery.post(fep_notification_script.ajaxurl, data, function(results) {
			jQuery('#fep-notification-bar').html(results);
			if (results=='')
			{ jQuery('#fep-notification-bar').hide(); }
			else 
			{ jQuery('#fep-notification-bar').show(); }
		});
        }
        setInterval(fep_ajax_call, parseInt(fep_notification_script.interval, 10) );
      });