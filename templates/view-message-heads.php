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
		if ( apply_filters( 'fep_is_group_message', false, fep_get_the_id() ) ) {
			?>
			<div class="fep-avatar-p fep-avatar-p-90">
				<div class="fep-avatar-group-60" title="<?php esc_attr_e( 'Group', 'front-end-pm' ); ?>"></div>
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
				<div class="fep-avatar-<?php echo $count; ?>"><?php echo get_avatar( $p, 60, '', '', array( 'extra_attr' => 'title="' . fep_user_name( $p ) . '"' ) ); ?></div>
				<?php
				$count++;
			}
			echo '</div>';
		}
		?></div><?php
	}
	$feppage = ! empty( $_GET['feppage'] ) ? absint( $_GET['feppage'] ) : 1;
	$heads_last_page = ceil( $messages_heads->total_messages / fep_get_option( 'messages_page', 15 ) );
	if ( $heads_last_page > 1 ) :
	?>
	<div class="fep-align-centre">
		<ul class="fep-pagination fep-pagination-heads">
			<li class="<?php echo ( 1 === $feppage ) ? 'disabled' : ''; ?>">
				<a class="fep-heads-pagination" data-fep_action="<?php echo ( 1 === $feppage ) ? '' : 'prev'; ?>" href="#" title="<?php esc_attr_e( 'Previous', 'front-end-pm' ); ?>">&laquo;</a>
			</li>
			<li>
				<li class="active"><a class="fep-heads-pagination" data-fep_action="current" href="#"><?php echo number_format_i18n( $feppage ); ?></a></li>
			</li>
			<li class="<?php echo ( $heads_last_page <= $feppage ) ? 'disabled' : ''; ?>">
				<a class="fep-heads-pagination" data-fep_action="<?php echo ( $heads_last_page <= $feppage ) ? '' : 'next'; ?>" href="#" title="<?php esc_attr_e( 'Next', 'front-end-pm' ); ?>">&raquo;</a>
			</li>
		</ul>
	</div>
	<?php
	endif;
} else {
	echo '<div class="fep-error">' . esc_html__( 'No messages found. Try different filter.', 'front-end-pm' ) . '</div>';
}
