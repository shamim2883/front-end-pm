jQuery(document).ready(function(){
		jQuery('#submit').hide();
		
		jQuery( document ).on( "click", "#fep-update-button", function(e) {
			e.preventDefault();
			
			jQuery('#fep-update-button').prop('disabled', true);
			jQuery('#fep-ajax-response').html('Please wait, It may take some time.');
			
			jQuery('.fep-ajax-img').show();
			
		var data = jQuery('form').serialize().replace(/&action=[^&;]*/,'&action=fep_update_ajax');

		jQuery.post( ajaxurl, data, function(results) {
			jQuery('#fep-ajax-response').html(results['message']);
			
		}, 'json')
			.fail(function() {
					 jQuery('#fep-ajax-response').html('Refresh this page and try again.');
			})
			.complete(function() {
					 jQuery('.fep-ajax-img').hide();
			});;
      });
});

