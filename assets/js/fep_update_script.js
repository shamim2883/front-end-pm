jQuery( document ).ready( function( $ ) {
	function fep_update( custom_str, custom_int ) {
		$( '.fep-ajax-img' ).show();
		var data = {
			action: 'fep_update_ajax',
			custom_str : custom_str,
			custom_int : custom_int
		};
		$.post( ajaxurl, data, function ( response ) {
			if ( response['message'].length ) {
				$( '#fep-ajax-response' ).append( response['message'] + '<br />' );
			}
			if ( 'completed' == response['update'] ) {
				//jQuery( '#fep-ajax-response' ).html( 'Update completed.' );
				$( '.fep-ajax-img' ).hide();
				$( '#fep-update-warning' ).hide();
				$( '#fep-ajax-response' ).html( response['message'] );
				//document.location.href = 'index.php'; // Redirect to the dashboard
			} else {
				fep_update( response['custom_str'], response['custom_int'] );
			}
		}, 'json')
		.fail( function() {
			$( '#fep-ajax-response' ).html( 'Refresh this page and try again.' );
			$( '.fep-ajax-img' ).hide();
			$( '#fep-update-warning' ).hide();
		})
		.complete(function() {
			//jQuery( '.fep-ajax-img' ).hide();
		});
	}
	// Trigger upgrades
	$( '.fep-start-update' ).click( function(e) {
		e.preventDefault();
		$(this).hide();
		fep_update( '', 0 );
	});
});
