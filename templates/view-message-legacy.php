<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$i = 0;
if ( $messages->have_messages() ) {
	wp_enqueue_script( 'fep-replies-show-hide' );
	if ( fep_get_option( 'block_other_users', 1 ) ) {
		wp_enqueue_script( 'fep-block-unblock-script' );
	}
	$hide_read = apply_filters( 'fep_filter_hide_message_initially_if_read', true );
	?>
	<div class="fep-message"><?php
		while( $messages->have_messages() ) {
			$messages->the_message();
			$i++;
			$read_class = ( $hide_read && fep_is_read() ) ? ' fep-hide-if-js' : '';
			$content_class = array();
			$content_class[] = 'fep-message-content';
			$content_class[] = 'fep-message-content-' . fep_get_the_id();
			//$content_class[] = 'fep-message-content-author-' . get_the_author_meta('ID' );
			$per_mgs_class = array();
			$per_mgs_class[] = 'fep-per-message';
			$per_mgs_class[] = 'fep-per-message-' . fep_get_the_id();
			//$per_mgs_class[] = 'fep-per-message-' . get_the_author_meta('ID' );
			if ( get_current_user_id() == fep_get_message_field( 'mgs_author' ) ) {
				$content_class[] = 'fep-message-content-own';
				$per_mgs_class[] = 'fep-per-message-own';
			}
			if ( fep_is_user_admin( fep_get_message_field( 'mgs_author' ) ) ) {
				$content_class[] = 'fep-message-content-admin';
				$per_mgs_class[] = 'fep-per-message-admin';
			}
			if ( $hide_read && fep_is_read() ) {
				$content_class[] = 'fep-hide-if-js';
				//$per_mgs_class[] = 'fep-hide-if-js';
			}
			
			if( FEP_Participants::init()->mark( fep_get_the_id(), get_current_user_id(), ['read' => true, 'parent_read' => true ] ) ){
				delete_user_meta( get_current_user_id(), '_fep_user_message_count' );
			}
			if ( $i === 1 ) {
				?>
				<div class="fep-per-message fep-per-message-top fep-per-message-<?php echo fep_get_the_id(); ?>">
					<div class="fep-message-title-heading"><?php echo fep_get_the_title(); ?></div>
					<div class="fep-message-title-heading participants"><?php esc_html_e( 'Participants', 'front-end-pm' ); ?>: <?php
					if( $group = apply_filters( 'fep_is_group_message', false, fep_get_the_id() ) ){
						echo esc_html( $group );
					} else {
						$participants = fep_get_participants( fep_get_the_id() );
						$par = array();
						foreach ( $participants as $participant ) {
							if ( get_current_user_id() != $participant && fep_get_option( 'block_other_users', 1 ) ) {
								if ( fep_is_user_blocked_for_user( get_current_user_id(), $participant ) ) {
									$par[] = fep_user_name( $participant ) . '(<a href="#" class="fep_block_unblock_user fep_user_blocked" data-user_id="' . $participant . '" data-user_name="' . esc_attr( fep_user_name( $participant ) ) . '">' . esc_html__( 'Unblock', 'front-end-pm' ) . '</a>)';
								} else {
									$par[] = fep_user_name( $participant ) . '(<a href="#" class="fep_block_unblock_user" data-user_id="' . $participant . '" data-user_name="' . esc_attr( fep_user_name( $participant ) ) . '">' . esc_html__( 'Block', 'front-end-pm' ) . '</a>)';
								}
							} else {
								$par[] = fep_user_name( $participant );
							}
						}
						echo apply_filters( 'fep_filter_display_participants', implode( ', ', $par ), $par, $participants );
					}
					?></div>
					<div class="fep-message-toggle-all fep-align-right"><?php esc_html_e( 'Toggle Messages', 'front-end-pm' ); ?></div>
				</div>
				<?php
			} ?>
			<div id="fep-message-<?php echo fep_get_the_id(); ?>" class="<?php echo fep_sanitize_html_class( $per_mgs_class ); ?>">
				<div class="fep-message-title fep-message-title-<?php echo fep_get_the_id(); ?>">
					<div class="author"><?php echo fep_user_name( fep_get_message_field( 'mgs_author' ) ); ?></div>
					<div class="date"><?php echo fep_get_the_date(); ?></div>
				</div>
				<div class="<?php echo fep_sanitize_html_class( $content_class ); ?>">
					<?php echo fep_get_the_content();
					if (  1 === $i ) {
						do_action( 'fep_display_after_parent_message' );
					} else {
						do_action( 'fep_display_after_reply_message' );
					}
					do_action( 'fep_display_after_message', $i ); ?>
				</div>
			</div>
			<?php
		} ?>
	</div><?php
	include( fep_locate_template( 'form-reply.php' ) );
} else {
	echo '<div class="fep-error">' . esc_html__( 'You do not have permission to view this message!', 'front-end-pm' ) . '</div>';
}
