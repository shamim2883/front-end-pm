jQuery( document ).ready( function( $ ) {
	function fep_update( custom_str, custom_int ) {
		$.ajax({
				url: ajaxurl,
				method: 'post',
				dataType: 'json',
				data: {
					action: 'fep_update_ajax',
					custom_str : custom_str,
					custom_int : custom_int
				}
		}).done( function( response ) {
			if ( 'completed' == response.update ) {
				$( '.fep-ajax-img' ).hide();
				$( '#fep-update-warning' ).hide();
				$( '#fep-ajax-response' ).html( response.message );
			} else {
				if ( response.message.length ) {
					$( '#fep-ajax-response' ).append( response.message + '<br />' );
				}
				setTimeout( function(){ fep_update( response.custom_str, response.custom_int ); } );
			}
		}).fail( function() {
			$( '.fep-ajax-img' ).hide();
			$( '#fep-update-warning' ).hide();
			$( '#fep-ajax-response' ).html( 'Refresh this page and try again.' );
		});
	}
	// Trigger upgrades
	$( '.fep-start-update' ).click( function(e) {
		e.preventDefault();
		e.stopPropagation();
		$( '.fep-ajax-img' ).show();
		$(this).hide();
		fep_update( '', 0 );
	});
});
