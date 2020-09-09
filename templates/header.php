<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$box_class = 'fep-box-size';
if ( $max_total && ( ( $max_total * 90 ) / 100 ) <= $total_count ) {
	$box_class .= ' fep-font-red';
}
?>
<div id="fep-wrapper">
	<div id="fep-header" class="fep-table">
		<div>
			<div class="fep-header-avatar">
				<?php echo get_avatar( $user_ID, 64, '', fep_user_name( $user_ID ) ); ?>
			</div>
			<div>
				<div class="fep-header-welcome">
					<strong><?php esc_html_e( 'Welcome', 'front-end-pm' );?>: <?php echo fep_user_name( $user_ID ); ?></strong>
				</div>
				<div class="fep-header-unread-text">
					<?php echo strip_tags( sprintf( __('You have %1$s and %2$s unread', 'front-end-pm'), '<span class="fep_unread_message_count_text">' . sprintf( _n( '%s message', '%s messages', $unread_count, 'front-end-pm' ), number_format_i18n( $unread_count ) ) . '</span>', '<span class="fep_unread_announcement_count_text">' . sprintf( _n( '%s announcement', '%s announcements', $unread_ann_count, 'front-end-pm' ), number_format_i18n( $unread_ann_count ) ) . '</span>' ), '<span>' ); ?> 
				</div>
				<div class="fep-header-box-size <?php echo $box_class; ?>">
					<?php echo strip_tags( sprintf( __( 'Message box size: %1$s of %2$s', 'front-end-pm' ), '<span class="fep_total_message_count">' . number_format_i18n( $total_count ) . '</span>', $max_text ), '<span>' ); ?>
				</div>
			</div>
			<?php do_action( 'fep_header_note', $user_ID ); ?>
		</div>
	</div>
