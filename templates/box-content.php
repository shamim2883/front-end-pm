<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( $box_content->found_messages ) {
?>
	<div class="fep-cb-check-uncheck-all-div">
		<label>
			<input type="checkbox" class="fep-cb-check-uncheck-all" />
			<?php esc_html_e( 'Check/Uncheck all', 'front-end-pm' ); ?>
		</label>
	</div>
	<div id="fep-table" class="fep-table fep-odd-even">
		<?php
		while ( $box_content->have_messages() ) {
			$box_content->the_message();
			if ( 'announcement' === fep_get_message_field( 'mgs_type' ) ) :
				?>
				<div id="fep-announcement-<?php echo fep_get_the_id(); ?>" class="fep-table-row">
				<?php foreach ( FEP_Announcements::init()->get_table_columns() as $column => $display ) : ?>
				<div class="fep-column fep-column-<?php echo esc_attr( $column ); ?>"><?php FEP_Announcements::init()->get_column_content( $column ); ?></div>
			<?php endforeach; ?>
			</div>
		<?php elseif ( 'message' === fep_get_message_field( 'mgs_type' ) ) : ?>
			<div id="fep-message-<?php echo fep_get_the_id(); ?>" class="fep-table-row">
				<?php foreach ( Fep_Messages::init()->get_table_columns() as $column => $display ) : ?>
					<div class="fep-column fep-column-<?php echo esc_attr( $column ); ?>"><?php Fep_Messages::init()->get_column_content( $column ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
			<?php
		} //endwhile
		?>
	</div>
	<?php
	echo fep_pagination_prev_next( $box_content->has_more_row );
} else {
	if ( empty( $_GET['fep-filter'] ) || 'show-all' == $_GET['fep-filter'] ) {
		?>
		<div class="fep-error"><?php echo sprintf( __( 'No %s found.', 'front-end-pm' ), ( isset( $_GET['fepaction'] ) && 'announcements' == $_GET['fepaction'] ) ? __('announcements', 'front-end-pm') : __('messages', 'front-end-pm') ); ?></div>
		<?php
	} else {
		?>
		<div class="fep-error"><?php echo sprintf( __( 'No %s found. Try different filter.', 'front-end-pm' ), ( isset( $_GET['fepaction'] ) && 'announcements' == $_GET['fepaction'] ) ? __('announcements', 'front-end-pm') : __('messages', 'front-end-pm') ); ?></div>
		<?php
	}
}
