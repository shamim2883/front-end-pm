jQuery( document ).ready( function( $ ) {
	$( document ).on( 'click', '#fep-attachment-field-add', function( e ) {
		e.preventDefault();
			
		if ( ! fep_attachment_script.maximum || $( 'input[name="fep_upload[]"]' ).length < fep_attachment_script.maximum ) {
			$( '#fep_upload' ).append('<div class="fep-attachment-field-div"><input class="fep-attachment-field-input" type="file" name="fep_upload[]" /><a href="#" class="fep-attachment-field-a">' + fep_attachment_script.remove + '</a></div>');
		} else {
			$( '#fep-attachment-field-add' ).hide();
			$( '#fep-attachment-note' ).html( fep_attachment_script.max_text );
		}
	});
	$( document ).on( 'click', '.fep-attachment-field-a', function( e ) {
		e.preventDefault();
		$(this).closest('.fep-attachment-field-div').remove();
			
		if ( ! fep_attachment_script.maximum || $( 'input[name="fep_upload[]"]' ).length < fep_attachment_script.maximum ) {
			$( '#fep-attachment-field-add' ).show();
			$( '#fep-attachment-note' ).empty();
		} else {
			$( '#fep-attachment-field-add' ).hide();
			$( '#fep-attachment-note' ).html( fep_attachment_script.max_text );
		}
	});
});
