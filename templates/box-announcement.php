<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_script( 'fep-view-message' );

echo fep_info_output();
/*
if( ! $total_announcements ) {
	echo '<div class="fep-error">' . apply_filters( 'fep_filter_announcement_empty', __( 'No announcements found.', 'front-end-pm' ) ) . '</div>';
	return;
}
*/
do_action( 'fep_display_before_announcementbox' );
?>
<div class="fep-announcementbox-search-form-div">
	<form id="fep-announcementbox-search-form" action="">
		<input type="hidden" name="fepaction" value="announcements" />
		<input type="search" name="fep-search" class="fep-announcementbox-search-form-field" value="<?php echo isset( $_GET['fep-search'] ) ? esc_attr( stripslashes( $_GET['fep-search'] ) ) : ''; ?>" placeholder="<?php esc_html_e( 'Search Announcements', 'front-end-pm'); ?>" />
		<input type="hidden" name="feppage" value="1" />
	</form>
</div>
<form class="fep-announcement-table form" method="post" action="">
	<div class="fep-table fep-action-table">
		<div>
			<div class="fep-bulk-action">
				<select name="fep-bulk-action">
					<option value=""><?php _e('Bulk action', 'front-end-pm'); ?></option>
					<?php foreach( FEP_Announcements::init()->get_table_bulk_actions() as $bulk_action => $bulk_action_display ) : ?>
						<option value="<?php echo esc_attr( $bulk_action ); ?>"><?php echo esc_html( $bulk_action_display ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<input type="hidden" name="token" value="<?php echo fep_create_nonce('announcement_bulk_action'); ?>"/>
				<button type="submit" class="fep-button" name="fep_action" value="announcement_bulk_action"><?php esc_html_e( 'Apply', 'front-end-pm' ); ?></button>
			</div>
			<div class="fep-loading-gif-div"></div>
			<div class="fep-filter">
				<select class="fep-filter fep-ajax-load">
				<?php foreach( FEP_Announcements::init()->get_table_filters() as $filter => $filter_display ) : ?>
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
</form>
<?php 

