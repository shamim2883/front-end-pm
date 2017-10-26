jQuery(document).ready(function(){
		
		jQuery( '.shortcode-newmessage-ajax' ).on( "click", ".fep-button", function(e) {
			e.preventDefault();
			var element = this;
			jQuery(element).prop('disabled', true);
			jQuery(element).parent().parent().next('.fep-ajax-response').html('');
			jQuery(element).next('.fep-ajax-img').show();
			
		var data = jQuery(element.form).serialize().replace(/&token=[^&;]*/,'&token=' + fep_shortcode_newmessage.token) + '&fep_action=shortcode-newmessage';

		jQuery.post( fep_shortcode_newmessage.ajaxurl, data, function(response) {
			jQuery(element).parent().parent().next('.fep-ajax-response').html(response['info']);
			if( response['fep_return'] == 'success' ){
				jQuery(element.form).hide();
			}
			
		}, 'json')
			.fail(function() {
					 jQuery(element).parent().parent().next('.fep-ajax-response').html(fep_shortcode_newmessage.refresh_text);
			})
			.complete(function() {
					 jQuery(element).next('.fep-ajax-img').hide();
					 jQuery(element).prop('disabled', false);
			});
      });
});

