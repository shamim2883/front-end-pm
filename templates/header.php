<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( $max_total && (( $max_total * 90 )/ 100 ) <= $total_count  ) {
	$class = " class='fep-font-red'";
} else {
	$class = "";
}

?>

<div id='fep-wrapper'>
	<div id='fep-header' class='fep-table'>
		<div>
			<div><?php echo get_avatar($user_ID, 64); ?></div>
			<div><strong><?php _e("Welcome", 'front-end-pm');?>: <?php echo fep_get_userdata( $user_ID, 'display_name', 'id' ); ?></strong>
	  			<div><?php _e('You have', 'front-end-pm');?> <?php printf(_n('%s unread message', '%s unread messages', $unread_count, 'front-end-pm'), number_format_i18n($unread_count) ). " " .__('and', 'front-end-pm');?> <?php printf(_n('%s unread announcement', '%s unread announcements', $unread_ann_count, 'front-end-pm'), number_format_i18n($unread_ann_count) ); ?>
				</div>
				<div<?php echo $class; ?>><?php _e("Message box size", 'front-end-pm');?>: <?php printf(__("%s of %s", 'front-end-pm'), number_format_i18n($total_count), $max_text ); ?>
				</div>
	  
	  			<?php do_action('fep_header_note', $user_ID); ?>
	  
			</div>
		</div>
	</div>

