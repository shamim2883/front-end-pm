<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?><h2><?php esc_html_e( 'Send Message', 'front-end-pm' ); ?></h2>
<?php
if ( ! fep_current_user_can( 'send_new_message' ) ) {
	echo '<div class="fep-error">' . esc_html__( 'You do not have permission to send new message!', 'front-end-pm' ) . '</div>';
} elseif ( ! empty( $_POST['fep_action'] ) && 'newmessage' == $_POST['fep_action'] ) {
	if ( fep_errors()->get_error_messages() ) {
		echo Fep_Form::init()->form_field_output( 'newmessage', fep_errors() );
	} else {
		echo fep_info_output();
	}
} else {
	echo Fep_Form::init()->form_field_output( 'newmessage' );
}
