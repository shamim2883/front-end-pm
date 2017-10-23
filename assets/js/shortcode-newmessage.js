jQuery(document).ready(function(){
		
		jQuery( '.fep-form' ).on( "click", ".fep-button", function(e) {
			e.preventDefault();
			var element = this;
			jQuery(element).prop('disabled', true);
			jQuery('.fep-ajax-response').html('');
			
			jQuery('.fep-ajax-img').show();
			
		var data = jQuery(this.form).serialize().replace(/&token=[^&;]*/,'&token=' + fep_shortcode_newmessage.token) + '&fep_action=shortcode-newmessage';

		jQuery.post( fep_shortcode_newmessage.ajaxurl, data, function(response) {
			jQuery('.fep-ajax-response').html(response['info']);
			if( response['fep_return'] == 'success' ){
				jQuery(element.form).hide();
			}
			
		}, 'json')
			.fail(function() {
					 jQuery('.fep-ajax-response').html(fep_shortcode_newmessage.refresh_text);
			})
			.complete(function() {
					 jQuery('.fep-ajax-img').hide();
					 jQuery(element).prop('disabled', false);
			});;
      });
});

