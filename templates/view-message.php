<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
wp_enqueue_script( 'fep-view-message' );
if ( fep_get_option( 'block_other_users', 1 ) ) {
	wp_enqueue_script( 'fep-block-unblock-script' );
}
?>
<div id="fep-content-single">
	<div id="fep-content-single-sidebar">
		<div class="fep-loader"></div>
		<div class="fep-filter-heads-div">
			<select class="fep-filter fep-ajax-load">
				<?php foreach ( Fep_Messages::init()->get_table_filters() as $filter => $filter_display ) : ?>
					<option value="<?php echo esc_attr( $filter ); ?>"<?php selected( isset( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '', $filter ); ?>><?php echo esc_html( $filter_display ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div id="fep-content-single-heads" class="fep-odd-even">
			<?php require fep_locate_template( 'view-message-heads.php' ); ?>
		</div>
	</div>
	<div id="fep-content-single-main">
		<div class="fep-loader"></div>
		<div id="fep-content-single-content">
			<?php require fep_locate_template( 'view-message-content.php' ); ?>
		</div>
		<div id="fep-content-single-reply-form-error">
			<?php
			if ( ! fep_current_user_can( 'send_reply', $parent_id ) ) {
				echo '<div class="fep-error">' . esc_html__( 'You do not have permission to send reply to this message!', 'front-end-pm' ) . '</div>';
			} elseif ( fep_success()->get_error_messages() ) {
				echo fep_info_output();
			}
			?>
		</div>
		<div id="fep-content-single-reply-form"<?php if ( ! fep_current_user_can( 'send_reply', $parent_id ) ) echo ' style="display:none;"';?>>
			<?php
			if ( isset( $_POST['fep_action'] ) && 'reply' == $_POST['fep_action'] && ! fep_errors()->get_error_messages() ) {
				unset( $_REQUEST['message_content'] ); //hack to empty message content
			}
			echo Fep_Form::init()->form_field_output( 'reply', '', array( 'fep_parent_id' => $parent_id ) );
			?>
		</div>
	</div>
</div>

<?php
