jQuery( document ).ready( function( $ ) {
	function fep_replace_param(url, paramName, paramValue){
		if (paramValue == null) {
			paramValue = '';
		}
		var pattern = new RegExp('\\b('+paramName+'=).*?(&|#|$)');
		if (url.search(pattern)>=0) {
			return url.replace(pattern,'$1' + paramValue + '$2');
		}
		url = url.replace(/[?#]$/,'');
		return url + (url.indexOf('?')>0 ? '&' : '?') + paramName + '=' + paramValue;
	}
	function fep_load_heads() {
		$('.fep-content-single-sidebar-loader').css({
			height: $('#fep-content-single-sidebar').height(), 
			width: $('#fep-content-single-sidebar').width()
		});
		$('.fep-content-single-sidebar-loader').show();
		var action_url = fep_replace_param( fep_replace_param( $( '.fep-form-reply').attr('action' ), 'feppage', fep_view_message.feppage ), 'fep-filter', $('.fep-filter-heads').val() );
		$( '.fep-form-reply').attr('action', action_url );
		if ( history.replaceState ) {
			history.replaceState( '', '', action_url );
		}
		$.ajax({
			url: fep_view_message.root + '/message-heads/' + fep_view_message.feppage + '/' + $('.fep-filter-heads').val(),
			method: 'get',
			dataType: 'json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', fep_view_message.nonce );
			}
		}).done( function( response ) {
			$('.fep-content-single-sidebar-loader').hide();
			$( '#fep-content-single-heads' ).html( response.data_formated );
		}).fail( function() {
			$('.fep-content-single-sidebar-loader').hide();
			$( '#fep-content-single-heads' ).html( 'Refresh this page and try again.' );
		});
	}
	$( '.fep-hide-if-js' ).hide();
	$( '.fep-form-reply').attr('action', window.location.href );
	$( '#fep-content-single-content' ).on( 'click', '.fep-message-title', function () {
		//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
		$( this ).next( '.fep-message-content' ).slideToggle( 500 );
	});
	$( '#fep-content-single-content' ).on( 'click', '.fep-message-toggle-all', function () {
		//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
		$( '.fep-message-content' ).slideToggle( 500 );
	});
	$( '#fep-content-single-sidebar' ).on( 'change', '.fep-filter-heads', function(e) {
		fep_view_message.feppage = 1;
		fep_load_heads();
	});
	$( '#fep-content-single-sidebar' ).on( 'click', '.fep-heads-pagination', function(e) {
		e.preventDefault();
		var fep_action = $(this).data('fep_action');
		if( 'prev' === fep_action && fep_view_message.feppage > 1 ){
			fep_view_message.feppage--;
		} else if ( 'next' === fep_action ) {
			fep_view_message.feppage++;
		} else {
			return false;
		}
		fep_load_heads();
	});
	// Trigger upgrades
	$( '#fep-content-single-sidebar' ).on( 'click', '.fep-message-head', function(e) {
		$('.fep-message-head').removeClass('fep-message-head-active');
		$(this).addClass('fep-message-head-active');
		
		$('.fep-content-single-main-loader').css({
			height: $('#fep-content-single-main').height(), 
			width: $('#fep-content-single-main').width()
		});
		$('.fep-content-single-main-loader').show();
		
		var fep_id = $(this).data('fep_id');
		var action_url = fep_replace_param( $( '.fep-form-reply').attr('action' ), 'fep_id', fep_id );
		$('#fep_parent_id').val( fep_id );
		$( '.fep-form-reply').attr('action', action_url );
		if ( history.replaceState ) {
			history.replaceState( '', '', action_url );
		}

		$.ajax({
			url: fep_view_message.root + '/view-message/' + fep_id,
			method: 'get',
			dataType: 'json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', fep_view_message.nonce );
			}
		}).done( function( response ) {
			$('.fep-content-single-main-loader').hide();
			$( '#fep-content-single-content' ).html( response.data_formated );
			$( '.fep-hide-if-js' ).hide();
			if( response.show_reply_form ){
				$('#fep-content-single-reply-form').show();
				$('#fep-content-single-reply-form-error').empty();
			} else if( response.show_reply_form_error ) {
				$('#fep-content-single-reply-form').hide();
				$('#fep-content-single-reply-form-error').html( response.show_reply_form_error );
			} else {
				$('#fep-content-single-reply-form').hide();
			}
		}).fail( function() {
			$( '.fep-content-single-main-loader').hide();
			$( '#fep-content-single-content' ).html( 'Refresh this page and try again.' );
			$( '#fep-content-single-reply-form').hide();
		});
	});
});
