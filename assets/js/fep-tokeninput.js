jQuery(document).ready(function( $ ){
	var fep_tokeninput,
		count = 1;
	
	while ( window.hasOwnProperty( 'fep_tokeninput_' + count ) ) {
		fep_tokeninput = window['fep_tokeninput_' + count];
		count++;
		
		$( fep_tokeninput.selector ).tokenInput( fep_tokeninput.ajaxurl, {
			method: fep_tokeninput.method,
			theme: fep_tokeninput.theme,
			excludeCurrent: true,
			tokenLimit: fep_tokeninput.tokenLimit,
			hintText: fep_tokeninput.hintText,
			noResultsText: fep_tokeninput.noResultsText,
			searchingText: fep_tokeninput.searchingText,
			prePopulate: fep_tokeninput.prePopulate,
			width: fep_tokeninput.width,
			preventDuplicates: true,
			zindex: 99999,
			resultsLimit: 5,
			onSend: function ( ajax_params ) {
				ajax_params.beforeSend = function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', fep_tokeninput.nonce );
				}
			}
		});
	}
});
