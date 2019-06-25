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
					$( '#fep-result ul' ).append('<li>' + fep_script.no_match + '</li>');
				} else {
					$.each( response.slice(0,5), function(i, v) {
						$( '#fep-result ul' ).append('<li><a href="#" data-user_nicename="' + v.nicename + '" data-user_name="' + v.name + '">' + v.name + '</a></li>');
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
		$( '#fep-message-to' ).val( $(this).data('user_nicename') );
		$( '#fep-message-top' ).val( $(this).data('user_name') );
		$( '#fep-result' ).hide();
	});
});
