<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if( ! empty( $heading ) ) {
	?><h2><?php echo esc_html( $heading ); ?></h2><?php
}
if ( ! fep_current_user_can( 'send_new_message_to', $to_id ) ) {
	echo '<div class="fep-error">' . esc_html__( 'You do not have permission to send message to this receiver!', 'front-end-pm' ) . '</div>';
} elseif ( isset( $_POST['fep_action'] ) && 'shortcode-newmessage' == $_POST['fep_action'] ) {
	if ( fep_errors()->get_error_messages() ) {
		echo Fep_Form::init()->form_field_output( 'shortcode-newmessage', fep_errors(), array( 'message_to' => $to ) );
	} else {
		echo fep_info_output();
	}
} else {
	echo Fep_Form::init()->form_field_output( 'shortcode-newmessage', '', array( 'message_to' => $to, 'message_title' => $subject ) );
}
