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
		<?php printf(__( 'Subject: %s', 'front-end-pm'), fep_get_the_title() ); ?>
	</div>
	<div>
		<?php printf(__( 'Sender: %s', 'front-end-pm'), fep_user_name( fep_get_message_field( 'mgs_author') ) ); ?>
	</div>
	<div>
		<?php _e( 'Recipient', 'front-end-pm');?>: <?php fep_participants_view(); ?>
	</div>
	<hr />
	<div>
		<?php echo fep_get_the_content(); ?>
	</div>
</body>
</html>
