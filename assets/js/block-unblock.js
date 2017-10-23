jQuery(document).ready(function(){
		
		jQuery( document ).on( "click", ".fep_block_unblock_user", function(e) {
			e.preventDefault();
			var element = this;
			jQuery(element).addClass('fep-loading-gif');
			var data = {
				action: 'fep_block_unblock_users_ajax',
				user_id: jQuery(element).data('user_id'),
				token: fep_block_unblock_script.token
				};

		jQuery.post( fep_block_unblock_script.ajaxurl, data, function(response) {
			jQuery(element).html( response );
			jQuery(element).removeClass('fep-loading-gif');
      });
  });
});

