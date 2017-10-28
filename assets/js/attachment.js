    var fep_Index = 1;
    function fep_get_by_id(id) { return document.getElementById(id); }
    function fep_create_element(name) { return document.createElement(name); }
    function fep_remove_element(id) {
        var e = fep_get_by_id(id);
        e.parentNode.removeChild(e);
    }
    function fep_add_new_file_field() {
        var maximum = fep_attachment_script.maximum;
        var num_img = jQuery('input[name="fep_upload[]"]').size();
        if((maximum!=0 && num_img<maximum) || maximum==0) {
            var id = 'p-' + fep_Index++;

            var i = fep_create_element('input');
            i.setAttribute('type', 'file');
            i.setAttribute('name', 'fep_upload[]');

            var a = fep_create_element('a');
			a.setAttribute('class', 'fep-attachment-field');
            a.setAttribute('href', '#');
            a.setAttribute('divid', id);
            a.onclick = function() { fep_remove_element(this.getAttribute('divid')); return false; }
            a.appendChild(document.createTextNode(fep_attachment_script.remove));

            var d = fep_create_element('div');
            d.setAttribute('id', id);
            d.setAttribute('style','padding: 4px 0;')

            d.appendChild(i);
            d.appendChild(a);

            fep_get_by_id('fep_upload').appendChild(d);

        } else {
            alert( fep_attachment_script.max_text );
        }
    }
    // Listener: automatically add new file field when the visible ones are full.
	// Listener: automatically hide file field when maximum field reached.
	function fep_listener() {
		if ( jQuery('#fep_upload').length ) {
			fep_add_file_field();
			fep_hide_file_field();
		}
	}
		
    setInterval( fep_listener, 1000);
    /**
     * Timed: if there are no empty file fields, add new file field.
     */
    function fep_add_file_field() {
        var count = 0;
        jQuery('input[name="fep_upload[]"]').each(function(index) {
            if ( jQuery(this).val() == '' ) {
                count++;
            }
        });
        var maximum = fep_attachment_script.maximum;
        var num_img = jQuery('input[name="fep_upload[]"]').size();
        if (count == 0 && (maximum==0 || (maximum!=0 && num_img<maximum))) {
            fep_add_new_file_field();
        }
    }
	function fep_hide_file_field() {
        var maximum = fep_attachment_script.maximum;
        var num_img = jQuery('input[name="fep_upload[]"]').size();
        if (maximum!=0 && num_img>maximum-1) {
			//alert('maximum');
            jQuery('#fep-attachment-field-add').hide();
			jQuery('#fep-attachment-note').html( fep_attachment_script.max_text );
        } else {
			jQuery('#fep-attachment-field-add').show();
			jQuery('#fep-attachment-note').html('');
		}
    }