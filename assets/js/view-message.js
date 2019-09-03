jQuery( document ).ready( function( $ ) {
	function fep_get_param( paramName, url ){
		if ( ! url ) {
			url = window.location.href;
		}
		if ( -1 !== url.indexOf( '?' ) ) {
			url = url.split( '?' );
			url = url[1];
		}
		var vars = url.split('&');
		for ( var i = 0; i < vars.length; i++ ) {
			var pair = vars[i].split("=");
			if( paramName === pair[0] ){
				return pair[1];
			}
		}
		return '';
	}
	
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
	function fep_load_paginated_content() {
		var fepaction = fep_get_param( 'fepaction' ),
		action_url    = fep_replace_param( fep_replace_param( window.location.href, 'feppage', fep_view_message.feppage ), 'fep-filter', $('.fep-filter.fep-ajax-load').val() ),
		loader_selector;
		
		if ( ! fepaction ) {
			fepaction = 'messagebox';
		}
		if ( 'viewmessage' == fepaction ) {
			loader_selector = '#fep-content-single-sidebar .fep-loader';
			
			$( loader_selector ).css({
				height: $('#fep-content-single-sidebar').height(), 
				width: $('#fep-content-single-sidebar').width()
			});
			$( '.fep-form-reply' ).attr('action', action_url );
		} else {
			loader_selector = '#fep-box-content-main .fep-loader';
			$( loader_selector ).css({
				height: $('#fep-box-content-main').height(), 
				width: $('#fep-box-content-main').width()
			});
		}
		
		$( loader_selector ).show();
		if ( history.replaceState ) {
			history.replaceState( '', '', action_url );
		}
		$.ajax({
			url: fep_view_message.root + '/pagination/' + fepaction + '/' + fep_view_message.feppage + '/' + $('.fep-filter.fep-ajax-load').val(),
			method: 'get',
			dataType: 'json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', fep_view_message.nonce );
			}
		}).done( function( response ) {
			if ( 'viewmessage' == fepaction ) {
				$( '#fep-content-single-heads' ).html( response.data_formated );
			} else {
				$( '#fep-box-content-content' ).html( response.data_formated );
			}
		}).fail( function() {
			$( '#fep-content-single-heads' ).html( 'Refresh this page and try again.' );
			$( '#fep-box-content-content' ).html( 'Refresh this page and try again.' );
		}).always( function() {
			$( loader_selector ).hide();
		});
	}
	$( '#fep-content' ).on( 'change', '.fep-cb-check-uncheck-all', function(e) {
		$( '.fep-cb' ).prop( 'checked', $( this ).prop( 'checked' ) );
	});
	$( '#fep-content' ).on( 'change', '.fep-cb', function(e) {
		if ( $( '.fep-cb:checked' ).length == $( '.fep-cb' ).length ) {
			$( '.fep-cb-check-uncheck-all' ).prop( 'checked', true );
		} else {
			$( '.fep-cb-check-uncheck-all' ).prop( 'checked', false );
		}
	});
	$( '.fep-hide-if-js' ).hide();
	$( '.fep-form-reply').attr('action', window.location.href );
	if ( fep_view_message.toggle ) {
		$( '#fep-content-single-content' ).on( 'click', '.fep-message-title', function () {
			//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
			$( this ).next( '.fep-message-content' ).slideToggle( 500 );
		});
		$( '#fep-content-single-content' ).on( 'click', '.fep-message-toggle-all', function () {
			//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
			$( '.fep-message-content' ).slideToggle( 500 );
		});
	}
	$( '#fep-content' ).on( 'change', '.fep-filter.fep-ajax-load', function(e) {
		fep_view_message.feppage = 1;
		fep_load_paginated_content();
	});
	$( '#fep-content' ).on( 'click', '.fep_pagination_prev_next a', function(e) {
		e.preventDefault();
		var fep_action = $(this).data('fep_action');
		if( 'prev' === fep_action && fep_view_message.feppage > 1 ){
			fep_view_message.feppage--;
		} else if ( 'next' === fep_action ) {
			fep_view_message.feppage++;
		} else {
			return false;
		}
		fep_load_paginated_content();
	});

	$( '#fep-content-single-sidebar' ).on( 'click', '.fep-message-head', function(e) {
		$('.fep-message-head').removeClass('fep-message-head-active');
		$(this).addClass('fep-message-head-active');
		
		$('#fep-content-single-main .fep-loader').css({
			height: $('#fep-content-single-main').height(), 
			width: $('#fep-content-single-main').width()
		});
		$('#fep-content-single-main .fep-loader').show();
		
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
			$( '.fep-ajax-response' ).empty();
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
			$( '#fep-content-single-content' ).html( 'Refresh this page and try again.' );
			$( '#fep-content-single-reply-form').hide();
		}).always( function(){
			$( '#fep-content-single-main .fep-loader').hide();
		});
	});
});
