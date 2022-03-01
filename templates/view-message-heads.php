<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$messages_heads = Fep_Messages::init()->user_messages();

if ( $messages_heads->have_messages() ) {
	while ( $messages_heads->have_messages() ) {
		$messages_heads->the_message();
		?>
		<div id="fep-message-head-<?php echo fep_get_the_id(); ?>" class="fep-message-head<?php echo ( isset( $_GET['fep_id'] ) && absint( $_GET['fep_id'] ) === fep_get_the_id() ) ? ' fep-message-head-active' : ''; ?>" data-fep_id="<?php echo fep_get_the_id(); ?>">
		<?php
		if ( $group = apply_filters( 'fep_is_group_message', false, fep_get_the_id() ) ) {
			?>
			<div class="fep-avatar-p fep-avatar-p-90">
				<div class="fep-avatar-group-60" title="<?php echo esc_attr( $group ); ?>"></div>
			</div>
			<?php
		} else {
			$participants = fep_get_participants( fep_get_the_id() );
			if ( apply_filters( 'fep_remove_own_avatar_from_messagebox', false )
				 && ( $key = array_search( get_current_user_id(), $participants ) ) !== false ) {
				unset( $participants[ $key ] );
			}
			$count = 1;
			?>
			<div class="fep-avatar-p <?php echo ( count( $participants ) > 2 ) ? 'fep-avatar-p-120' : 'fep-avatar-p-90' ?>">
			<?php
			foreach ( $participants as $p ) {
				if ( $count > 2 ) {
					echo '<div class="fep-avatar-more-60" title="' . __( 'More users', 'front-end-pm' ) . '"></div>';
					break;
				}
				?>
				<div class="fep-avatar-<?php echo $count; ?>"><?php echo get_avatar( $p, 60, '', strip_tags( fep_user_name( $p ) ), array( 'extra_attr' => 'title="' . esc_attr( strip_tags( fep_user_name( $p ) ) ) . '"' ) ); ?></div>
				<?php
				$count++;
			}
			echo '</div>';
		}
		?></div><?php
	}
	echo fep_pagination_prev_next( $messages_heads->has_more_row );
} else {
	echo '<div class="fep-error">' . esc_html__( 'No messages found. Try different filter.', 'front-end-pm' ) . '</div>';
}
