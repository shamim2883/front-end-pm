<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$i = 0;
if ( $announcements->have_messages() ) { ?>
	<div class="fep-announcement"><?php
		while( $announcements->have_messages() ) {
			$announcements->the_message();
			$i++;
			
			if( FEP_Participants::init()->mark( fep_get_the_id(), get_current_user_id(), ['read' => true, 'parent_read' => true ] ) ){
				delete_user_meta( get_current_user_id(), '_fep_user_announcement_count' );
			} ?>
			<div id="fep-announcement-<?php echo fep_get_the_id(); ?>" class="fep-per-message">
				<div class="fep-message-title fep-announcement-title fep-announcement-title-<?php echo fep_get_the_id(); ?>">
					<?php echo fep_get_the_title(); ?>
					<div class="date"><?php echo fep_get_the_date(); ?></div>
				</div>
				<div class="fep-announcement-content">
					<?php echo fep_get_the_content();
					if (  1 === $i ) {
						do_action( 'fep_display_after_parent_announcement' );
					} else {
						do_action( 'fep_display_after_reply_announcement' );
					}
					do_action( 'fep_display_after_announcement', $i ); ?>
				</div>
			</div>
			<?php
		} ?>
	</div><?php
	//include( fep_locate_template( 'form-reply.php' ) );
} else {
	echo '<div class="fep-error">' . esc_html__( 'You do not have permission to view this announcement!', 'front-end-pm' ) . '</div>';
}
