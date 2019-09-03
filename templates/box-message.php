<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo fep_info_output();
if( ! fep_get_user_message_count( 'total' ) ) {
	echo '<div class="fep-error">' . apply_filters( 'fep_filter_messagebox_empty', esc_html__( 'No messages found.', 'front-end-pm' ) ) . '</div>';
	return;
}

wp_enqueue_script( 'fep-view-message' );

do_action( 'fep_display_before_messagebox' ); ?>
<div class="fep-messagebox-search-form-div">
	<form id="fep-messagebox-search-form" action="">
		<input type="hidden" name="fepaction" value="messagebox" />
		<input type="search" name="fep-search" class="fep-messagebox-search-form-field" value="<?php echo isset( $_GET['fep-search'] ) ? esc_attr( stripslashes( $_GET['fep-search'] ) ) : ''; ?>" placeholder="<?php _e( 'Search Messages', 'front-end-pm'); ?>" />
		<input type="hidden" name="feppage" value="1" />
	</form>
</div>
<form class="fep-message-table form" method="post" action="">
	<div class="fep-table fep-action-table">
		<div>
			<div class="fep-bulk-action">
				<select name="fep-bulk-action">
					<option value=""><?php esc_html_e( 'Bulk action', 'front-end-pm' ); ?></option>
					<?php foreach ( Fep_Messages::init()->get_table_bulk_actions() as $bulk_action => $bulk_action_display ) : ?>
						<option value="<?php echo esc_attr( $bulk_action ); ?>"><?php echo esc_html( $bulk_action_display ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<input type="hidden" name="token"  value="<?php echo fep_create_nonce( 'bulk_action' ); ?>"/>
				<button type="submit" class="fep-button" name="fep_action" value="bulk_action"><?php esc_html_e( 'Apply', 'front-end-pm' ); ?></button>
			</div>
			<div class="fep-loading-gif-div"></div>
			<div class="fep-filter">
				<select class="fep-filter fep-ajax-load">
					<?php foreach ( Fep_Messages::init()->get_table_filters() as $filter => $filter_display ) : ?>
						<option value="<?php echo esc_attr( $filter ); ?>" <?php selected( isset( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '', $filter );?>><?php echo esc_html( $filter_display ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<div id="fep-box-content-main">
		<div class="fep-loader"></div>
		<div id="fep-box-content-content">
			<?php require fep_locate_template( 'box-content.php' ); ?>
		</div>
	</div>
</form><?php

