<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( $max_total && (( $max_total * 90 )/ 100 ) <= $total_count  ) {
	$class = ' class="fep-font-red"';
} else {
	$class = '';
}

?>

<div id="fep-wrapper">
	<div id="fep-header" class="fep-table">
		<div>
			<div>
				<?php echo get_avatar($user_ID, 64); ?>
			</div>
			<div>
				<div>
					<strong><?php _e('Welcome', 'front-end-pm');?>: <?php echo fep_user_name( $user_ID ); ?></strong>
				</div>
	  			<div><?php _e('You have', 'front-end-pm');
					?> <span class="fep_unread_message_count_text"><?php printf(_n('%s message', '%s messages', $unread_count, 'front-end-pm'), number_format_i18n($unread_count) ); 
					?></span> <?php 
					_e('and', 'front-end-pm');
					?> <span class="fep_unread_announcement_count_text"><?php
					printf(_n('%s announcement', '%s announcements', $unread_ann_count, 'front-end-pm'), number_format_i18n($unread_ann_count) ); 
					?></span> <?php _e('unread', 'front-end-pm'); ?>
				</div>
				<div<?php echo $class; ?>><?php 
					_e('Message box size', 'front-end-pm');?>: <?php 
					printf(__('%1$s of %2$s', 'front-end-pm'),
					'<span class="fep_total_message_count">' . number_format_i18n($total_count) . '</span>',
					$max_text ); ?>
				</div>
			</div>
	  
	  			<?php do_action('fep_header_note', $user_ID); ?>
	  
		</div>
	</div>

