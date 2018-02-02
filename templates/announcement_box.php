<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo fep_info_output();
/*
if( ! $total_announcements ) {
	echo "<div class='fep-error'>".apply_filters('fep_filter_announcement_empty', __("No announcements found.", 'front-end-pm') )."</div>";
	return;
}
*/
do_action('fep_display_before_announcementbox');
	  
	  	?><form class="fep-message-table form" method="post" action="">
		<div class="fep-table fep-action-table">
			<div>
				<div class="fep-bulk-action">
					<select name="fep-bulk-action">
						<option value=""><?php _e('Bulk action', 'front-end-pm'); ?></option>
						<?php foreach( Fep_Announcement::init()->get_table_bulk_actions() as $bulk_action => $bulk_action_display ) { ?>
						<option value="<?php echo $bulk_action; ?>"><?php echo $bulk_action_display; ?></option>
						<?php } ?>
					</select>
				</div>
				<div>
					<input type="hidden" name="token"  value="<?php echo fep_create_nonce('announcement_bulk_action'); ?>"/>
					<button type="submit" class="fep-button" name="fep_action" value="announcement_bulk_action"><?php _e('Apply', 'front-end-pm'); ?></button>
				</div>
				<div class="fep-loading-gif-div">
				</div>
				<div class="fep-filter">
					<select onchange="if (this.value) window.location.href=this.value">
						<?php foreach( Fep_Announcement::init()->get_table_filters() as $filter => $filter_display ) { ?>
						<option value="<?php echo esc_url( add_query_arg( array('fep-filter' => $filter, 'feppage' => false ) ) ); ?>" <?php selected($g_filter, $filter);?>><?php echo $filter_display; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>
		<?php if( $announcements->have_posts() ) { ?>
		<div id="fep-table" class="fep-table fep-odd-even"><?php
			while ( $announcements->have_posts() ) { 
				$announcements->the_post(); ?>
					<div id="fep-message-<?php echo get_the_ID(); ?>" class="fep-table-row"><?php
						foreach ( Fep_Announcement::init()->get_table_columns() as $column => $display ) { ?>
							<div class="fep-column fep-column-<?php echo $column; ?>"><?php Fep_Announcement::init()->get_column_content($column); ?></div>
						<?php } ?>
					</div>
				<?php
			} //endwhile
			?></div><?php
			echo fep_pagination( $total_announcements, fep_get_option('announcements_page', 15) );
		} else {
			if( !$g_filter || 'show-all' == $g_filter ){
				?><div class="fep-error"><?php _e('No announcements found.', 'front-end-pm'); ?></div><?php	
			} else {
				?><div class="fep-error"><?php _e('No announcements found. Try different filter.', 'front-end-pm'); ?></div><?php
			}
		}
		?></form><?php 
		wp_reset_postdata();