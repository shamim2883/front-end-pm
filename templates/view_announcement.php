<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $more;
if ( $announcement->have_posts() ) {
	while ( $announcement->have_posts() ) {
		$announcement->the_post();
		$more = 1; // show all message content after <!--more--> tag also
		if ( fep_make_read() ) {
			delete_user_option( get_current_user_id(), '_fep_user_announcement_count' );
		}
		?>
		<div class="fep-per-message">
			<div class="fep-message-title"><?php the_title(); ?>
				<div class="date"><?php the_time(); ?></div>
			</div>
			<div class="fep-message-content">
				<?php the_content(); ?>
				<?php do_action ( 'fep_display_after_announcement' ); ?>
			</div>
		</div>
		<?php
	}
	wp_reset_postdata();
} else {
	echo '<div class="fep-error">' . __( 'You do not have permission to view this announcement!', 'front-end-pm' ) . '</div>';
}
