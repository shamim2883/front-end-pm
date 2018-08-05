<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><h2><?php _e( 'New Announcement', 'front-end-pm' ); ?></h2>
<?php
if ( ! fep_current_user_can( 'add_announcement') ) {
	echo '<div class="fep-error">' . __( 'You do not have permission to create announcement!', 'front-end-pm' ) . '</div>';
} elseif ( ! empty( $_POST['fep_action'] ) && 'new_announcement' == $_POST['fep_action'] ) {
	if ( fep_errors()->get_error_messages() ) {
		echo Fep_Form::init()->form_field_output( 'new_announcement', fep_errors() );
	} else {
		echo fep_info_output();
	}
} else {
	echo Fep_Form::init()->form_field_output( 'new_announcement' );
}
