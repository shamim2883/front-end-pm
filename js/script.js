jQuery( document ).on( "keyup", "#fep-message-top", function() {	
				document.getElementById('fep-result').style.display="none";
				jQuery('.fep-ajax-img').show();
					var display_name=jQuery('#fep-message-top').val();
					var data = {
									action: 'fep_autosuggestion_ajax',
									searchBy: display_name,
									token: fep_script.nonce
									};
									
		jQuery.post(fep_script.ajaxurl, data, function(results) {
			jQuery('.fep-ajax-img').hide();
			jQuery('#fep-result').html(results);
			document.getElementById('fep-result').style.display="block";
			if (results=='')
			{document.getElementById('fep-result').style.display="none";}
			
			});
				});

function fep_fill_autosuggestion(login, display) {
	
	document.getElementById('fep-message-to').value=login;
	document.getElementById('fep-message-top').value=display;
	document.getElementById('fep-result').style.display="none";
}