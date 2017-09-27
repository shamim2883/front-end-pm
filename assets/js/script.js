var fep_delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();
jQuery( document ).on( "keyup", "#fep-message-top", function() {
	fep_delay(function(){
			jQuery('#fep-result').hide();
			jQuery('#fep-message-top').addClass('fep-loading-gif');
				var display_name=jQuery('#fep-message-top').val();
				var data = {
						action: 'fep_autosuggestion_ajax',
						searchBy: display_name,
						token: fep_script.nonce
						};
								
	jQuery.post(fep_script.ajaxurl, data, function(results) {
		jQuery('#fep-message-top').removeClass('fep-loading-gif');
		jQuery('#fep-result').html(results);
		if ( results ){
			jQuery('#fep-result').show();
		}
		
		});
	}, 1000 );
});

function fep_fill_autosuggestion(login, display) {
	
	jQuery('#fep-message-to').val( login );
	jQuery('#fep-message-top').val( display );
	jQuery('#fep-result').hide();
}
