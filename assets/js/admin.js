jQuery( document ).ready( function( $ ) {
	// Add Color Picker to all inputs that have 'fep-color-picker' class
	if( !!$.prototype.wpColorPicker ) {
		$( '.fep-color-picker' ).wpColorPicker();
	}
	$("input.fep_toggle_next_tr").each(function(){
		if( ! $(this).prop("checked")) {
			var trs = 1;
			if( $(this).hasClass('fep_toggle_next_tr-2') ){
				trs = 2;
			} else if( $(this).hasClass('fep_toggle_next_tr-3') ){
				trs = 3;
			}
			$(this).closest('tr').nextAll(':lt(' + trs + ')').hide('slow');
		}
	});
	$("input.fep_toggle_next_tr").change(function(){
		var trs = 1;
		if( $(this).hasClass('fep_toggle_next_tr-2') ){
			trs = 2;
		} else if( $(this).hasClass('fep_toggle_next_tr-3') ){
			trs = 3;
		}
		if($(this).prop("checked")) {
			$(this).closest('tr').nextAll(':lt(' + trs + ')').show('slow');
		} else {
			$(this).closest('tr').nextAll(':lt(' + trs + ')').hide('slow');
		}
	});

	$( document ).on( 'click', '.fep-review-notice .fep-review-notice-dismiss', function( e ) {
		var fep_click = $( this ).data( 'fep_click' );
		$( this ).parent().parent().remove();
		var data = {
			action: 'fep_review_notice_dismiss',
			fep_click: fep_click
		};
		$.post( ajaxurl, data );
	});

	// Uploading files
	var file_frame;

	$('.fep-att-upload').on('click', function( e ){
		e.preventDefault();

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
			file_frame.open();
			return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			title: jQuery( this ).data( 'uploader_title' ),
			button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			},
			multiple: true  // Set to true to allow multiple files to be selected
		});

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
			var selection = file_frame.state().get('selection');
			var src;

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();
				if ( 'image' === attachment.type ) {
					src = attachment.url;
				} else {
					src = attachment.icon;
				}
				//console.log(attachment);
				
				$('.fep_edit_attachments_table > tbody:last-child').append('<tr><td><img src="' + src + '" width="200px" height="150px" /></td><td>' + attachment.filename + ' <input name="att_id[]" type="hidden" value="' + attachment.id + '" /></td><td><a href="#" class="fep_att_delete">' + fep_admin.delete + '</a></td></tr>');
			});
		});

		// Finally, open the modal
		file_frame.open();
	});
	$( '.fep_edit_attachments_table' ).on('click', '.fep_att_delete', function( e ){
		if ( ! confirm( fep_admin.del_confirm ) ) {
			return false;
		}
		jQuery(this).parent().parent().remove();
		return false;
	});
	$('.fep_delete_a').on('click', function( e ){
		if ( ! confirm( fep_admin.del_confirm ) ) {
			return false;
		}
	});
	$('.fep_att_delete_ajax').on('click', function( e ){
		e.preventDefault();
		if ( ! confirm( fep_admin.del_confirm ) ) {
			return false;
		}
		var element = $( this );
		element.next('.spinner').addClass( 'is-active' );
		var data = {
			action: 'fep_ajax_att_delete',
			fep_id: element.data( 'fep_id' ),
			fep_parent_id: element.data( 'fep_parent_id' ),
			nonce: element.data( 'nonce' )
		};
		$.get( ajaxurl, data, function( response ) {
			if( response.success ) {
				element.parent().parent().remove();
			} else {
				element.next('.spinner').removeClass( 'is-active' );
			}
		});
	});
	$('#fep-save').on('click', function( e ){
		$('#publishing-action .spinner').addClass( 'is-active' );
	});
});
