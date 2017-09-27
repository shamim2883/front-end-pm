jQuery( document ).ready( function() {
					
	function fep_update( custom_str, custom_int ){
		jQuery('#submit').hide();
		jQuery('.fep-ajax-img').show();
		var data = { 
			action: 'fep_update_ajax',
			custom_str : custom_str,
			custom_int : custom_int
			};
		jQuery.post( ajaxurl, data, function (response) {
			if( response['message'].length ) {
				jQuery('#fep-ajax-response').append(response['message'] + '<br />');
			}
			
			if( response['update'] == 'completed' ) {
				//jQuery('#fep-ajax-response').html('Update completed.');
				jQuery('.fep-ajax-img').hide();
				jQuery('#fep-update-warning').hide();
				jQuery('#fep-ajax-response').html(response['message']);
				//document.location.href = 'index.php'; // Redirect to the dashboard
			} else {
				fep_update( response['custom_str'], response['custom_int'] );
			}
		}, 'json')
		.fail(function() {
			jQuery('#fep-ajax-response').html('Refresh this page and try again.');
			jQuery('.fep-ajax-img').hide();
			jQuery('#fep-update-warning').hide();
		})
		.complete(function() {
			//jQuery('.fep-ajax-img').hide();
		});
	}
	// Trigger upgrades on page load
	fep_update( '', 0 );
});

