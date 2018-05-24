<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<h2><?php _e( 'Set your preferences below', 'front-end-pm' ); ?></h2>
<?php echo fep_info_output(); ?>
<?php echo Fep_Form::init()->form_field_output( 'settings' ); ?>
