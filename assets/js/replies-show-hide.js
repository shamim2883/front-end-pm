jQuery(document).ready(function(){
		jQuery(".fep-hide-if-js").hide();
		jQuery(".fep-message-title").click(function () {
		//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
		jQuery(this).next('.fep-message-content').slideToggle(500);
		});
		
		jQuery("#selecctall").change(function(){
      	jQuery(".checkbox1").prop('checked', jQuery(this).prop("checked"));
      	});
});