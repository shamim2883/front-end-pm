jQuery( document ).ready( function( $ ) {
	jQuery( document ).on( 'click', '.fep_block_unblock_user', function( e ) {
		e.preventDefault();
		var element = this;
		if ( ! $( element ).hasClass( 'fep_user_blocked' ) && ! confirm( fep_block_unblock_script.confirm.replace( '%s', $( element ).data( 'user_name' ) ) ) ) {
			return false;
		}
		$( element ).addClass( 'fep-loading-gif' );
		var data = {
			action: 'fep_block_unblock_users_ajax',
			user_id: jQuery( element ).data( 'user_id' ),
			token: fep_block_unblock_script.token
		};
		$.post( fep_block_unblock_script.ajaxurl, data, function( response ) {
			$( element ).html( response );
			$( element ).removeClass( 'fep-loading-gif' );
			$( element ).toggleClass( 'fep_user_blocked' );
		});
	});
});
