<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<html>
<head>
	<title><?php echo fep_get_the_title(); ?></title>
</head>
<body>
	<div>
		<?php esc_html_e( 'Subject', 'front-end-pm');?>: <?php echo fep_get_the_title(); ?>
	</div>
	<div>
		<?php esc_html_e( 'Sender', 'front-end-pm');?>: <?php echo fep_user_name( fep_get_message_field( 'mgs_author') ); ?>
	</div>
	<div>
		<?php esc_html_e( 'Recipient', 'front-end-pm');?>: <?php fep_participants_view(); ?>
	</div>
	<hr />
	<div>
		<?php echo fep_get_the_content(); ?>
	</div>
</body>
</html>
