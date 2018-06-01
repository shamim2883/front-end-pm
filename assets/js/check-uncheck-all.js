( function( $ ) {
	$( '.fep-cb-check-uncheck-all' ).change( function() {
		$( '.fep-cb' ).prop( 'checked', $( this ).prop( 'checked' ) );
	});
	$( '.fep-cb' ).change( function() {
		if ( $( '.fep-cb:checked' ).length == $( '.fep-cb' ).length ) {
			$( '.fep-cb-check-uncheck-all' ).prop( 'checked', true );
		} else {
			$( '.fep-cb-check-uncheck-all' ).prop( 'checked', false );
		}
	});
})( jQuery );
