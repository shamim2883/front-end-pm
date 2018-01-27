jQuery(document).ready(function($) {
    // Add Color Picker to all inputs that have 'fep-color-picker' class
    $( '.fep-color-picker' ).wpColorPicker();
    
    $(document).on('click', '.fep-review-notice .fep-review-notice-dismiss', function(e){
        var fep_click = $( this ).data( 'fep_click' );
        $(this).parent().parent().remove();
        var data = {
			action: 'fep_review_notice_dismiss',
			fep_click: fep_click
		};
		$.post(ajaxurl, data );
    });
});