jQuery( document ).ready( function( $ ) {
	var fep_delay = ( function() {
		var timer = 0;
		return function( callback, ms ) {
			clearTimeout ( timer );
			timer = setTimeout( callback, ms );
		};
	})();

	$( document ).on( 'keyup', '#fep-message-top', function() {
		fep_delay( function() {
			$( '#fep-result' ).hide();
			var display_name = $( '#fep-message-top' ).val();
			var data = {
				q: display_name
			};
			if ( ! display_name ) {
				return;
			}
			$( '#fep-message-top' ).addClass( 'fep-loading-gif' );
			$.ajax({
				url: fep_script.root +'/users/autosuggestion/',
				method: 'get',
				data: data,
				dataType: 'json',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', fep_script.nonce );
				}
			}).done( function( response ) {
				$( '#fep-result' ).html('<ul></ul>');
				if ( $.isEmptyObject( response ) ) {
					$( '#fep-result ul' ).append( $( '<li></li>' ).text( fep_script.no_match ) );
				} else {
					$.each( response.slice(0,5), function(i, v) {
						// Build nodes with .text()/.attr() so names are never parsed as HTML.
						var link = $( '<a href="#"></a>' )
							.attr( 'data-user_nicename', String( v.nicename ) )
							.attr( 'data-user_name', String( v.name ) )
							.text( String( v.name ) );
						$( '#fep-result ul' ).append( $( '<li></li>' ).append( link ) );
					});
				}
				$( '#fep-result' ).show();
			}).always( function() {
				$( '#fep-message-top' ).removeClass( 'fep-loading-gif' );
			});
		}, 300 );
	});
	
	$( document ).on( 'click', '#fep-result a', function(e) {
		e.preventDefault();
		$( '#fep-message-to' ).val( $(this).attr('data-user_nicename') );
		$( '#fep-message-top' ).val( $(this).attr('data-user_name') );
		$( '#fep-result' ).hide();
	});
});
