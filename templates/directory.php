<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo fep_info_output();
/*
if ( ! $total ) {
	echo '<div class="fep-error">' . apply_filters( 'fep_filter_directory_empty', __( 'No users found.', 'front-end-pm' ) ) . '</div>';
	return;
}
*/
do_action( 'fep_display_before_directory' );
?>
<div class="fep-directory-search-form-div">
	<form id="fep-directory-search-form" action="">
		<input type="hidden" name="fepaction" value="directory" />
		<input type="search" name="fep-search" class="fep-directory-search-form-field" value="<?php echo isset( $_GET['fep-search'] ) ? esc_attr( stripslashes( $_GET['fep-search'] ) ) : ''; ?>" placeholder="<?php _e( 'Search Users', 'front-end-pm' ); ?>" />
		<input type="hidden" name="feppage" value="1" />
	</form>
</div>
<form class="fep-directory-table form" method="post" action="">
	<div class="fep-table fep-action-table">
		<div>
			<div class="fep-bulk-action">
				<select name="fep-bulk-action">
					<option value=""><?php esc_html_e( 'Bulk action', 'front-end-pm' ); ?></option>
					<?php foreach( Fep_Directory::init()->get_table_bulk_actions() as $bulk_action => $bulk_action_display ) : ?>
						<option value="<?php echo esc_attr( $bulk_action ); ?>"><?php echo esc_html( $bulk_action_display ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<input type="hidden" name="token"  value="<?php echo fep_create_nonce( 'directory_bulk_action' ); ?>"/>
				<button type="submit" class="fep-button" name="fep_action" value="directory_bulk_action"><?php esc_html_e( 'Apply', 'front-end-pm' ); ?></button>
			</div>
			<div class="fep-loading-gif-div"></div>
			<div class="fep-filter">
				<select onchange="if ( this.value ) window.location.href=this.value">
				<?php foreach( Fep_Directory::init()->get_table_filters() as $filter => $filter_display ) : ?>
					<option value="<?php echo esc_url( add_query_arg( array( 'fep-filter' => $filter, 'feppage' => false ) ) ); ?>" <?php selected( $g_filter, $filter );?>><?php echo esc_html( $filter_display ); ?></option>
				<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<?php
	if ( $user_query->get_results() ) {
		wp_enqueue_script( 'fep-cb-check-uncheck-all' );
		?>
		<div class="fep-cb-check-uncheck-all-div">
			<label>
				<input type="checkbox" class="fep-cb-check-uncheck-all" />
				<?php esc_html_e( 'Check/Uncheck all', 'front-end-pm' ); ?>
			</label>
		</div>
		<div id="fep-table" class="fep-table fep-odd-even">
			<?php foreach( $user_query->get_results() as $user ) : ?>
				<div id="fep-directory-<?php echo $user->ID; ?>" class="fep-table-row"><?php
					foreach ( Fep_Directory::init()->get_table_columns() as $column => $display ) : ?>
						<div class="fep-column fep-column-<?php echo esc_attr( $column ); ?>"><?php Fep_Directory::init()->get_column_content( $column, $user ); ?></div>
					<?php endforeach ?>
				</div>
				<?php
			endforeach; ?>
		</div>
		<?php
		echo fep_pagination( $total, fep_get_option( 'user_page', 50 ) );
	} else {
		?><div class="fep-error"><?php esc_html_e( 'No users found. Try different filter.', 'front-end-pm' ); ?></div><?php 
	}
	?>
</form>
<?php
