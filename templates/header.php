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
			<div>
				<?php echo get_avatar( $user_ID, 64, '', fep_user_name( $user_ID ) ); ?>
			</div>
			<div>
				<div>
					<strong><?php esc_html_e( 'Welcome', 'front-end-pm' );?>: <?php echo fep_user_name( $user_ID ); ?></strong>
				</div>
				<div><?php esc_html_e( 'You have', 'front-end-pm' ); ?>
					<span class="fep_unread_message_count_text"><?php printf( _n( '%s message', '%s messages', $unread_count, 'front-end-pm' ), number_format_i18n( $unread_count ) ); ?></span>
					<?php esc_html_e( 'and', 'front-end-pm' ); ?>
					<span class="fep_unread_announcement_count_text"><?php echo esc_html( sprintf( _n( '%s announcement', '%s announcements', $unread_ann_count, 'front-end-pm' ), number_format_i18n( $unread_ann_count ) ) ); ?></span>
					<?php esc_html_e( 'unread', 'front-end-pm' ); ?>
				</div>
				<div class="<?php echo $box_class; ?>"><?php 
					esc_html_e( 'Message box size:', 'front-end-pm' );
					echo strip_tags( sprintf( __( '%1$s of %2$s', 'front-end-pm' ), '<span class="fep_total_message_count">' . number_format_i18n( $total_count ) . '</span>', $max_text ), '<span>' ); ?>
				</div>
			</div>
			<?php do_action( 'fep_header_note', $user_ID ); ?>
		</div>
	</div>
