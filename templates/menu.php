<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<button id="fep-menu-toggle-button" class="fep-button"><?php esc_html_e( 'Message Menu', 'front-end-pm' ); ?></button>
<script type="text/javascript">
document.getElementById('fep-menu-toggle-button').onclick = function() {
	this.classList.toggle('fep-menu-toggle-expanded');
	document.getElementById("fep-menu").classList.toggle('fep-menu-toggle');
};
</script>

<div id="fep-menu" class="fep-menu-toggle">
	<?php do_action( 'fep_menu_button' ); ?>
</div><!--#fep-menu -->
<div id="fep-content">
<?php do_action( 'fep_display_before_content' ); ?>
