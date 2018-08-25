<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap">
	<h2><?php _e( 'Front End PM - Extensions', 'front-end-pm' ); ?></h2>
	<?php
	$extensions = get_transient( 'fep_extensions' );
	if ( false === $extensions ) {
		$response = wp_remote_get( 'https://www.shamimsplugins.com/wp-json/api/v1/extensions/front-end-pm', array( 'timeout' => 15, 'sslverify' => false, 'decompress' => false ) );
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
			echo '<div class="error"><p>' . __( 'Error loading extensions. Please reload the page again!', 'front-end-pm' ) . '</p></div>';
			return false;
		}
		$extensions = json_decode( wp_remote_retrieve_body( $response ) );
		set_transient( 'fep_extensions', $extensions, 12 * HOUR_IN_SECONDS );
	}
	if ( count( $extensions ) ) {
		foreach ( $extensions as $slug => $extension ) : ?>
			<div class="fep-extensions">
				<div class="thumbnail">
					<a href="<?php echo add_query_arg( array( 'utm_campaign' => 'admin', 'utm_source' => 'extensions', 'utm_medium' => 'thumbnail' ), $extension->url ); ?>" target="_blank">
						<img src="<?php echo $extension->thumbnail_url ? $extension->thumbnail_url : FEP_PLUGIN_URL . 'assets/images/default-extensions-img.jpg'; ?>" alt="<?php echo esc_attr( $extension->title ); ?>" />
					</a>
				</div>
				<div class="details">
					<h3 class="title">
						<a href="<?php echo add_query_arg( array( 'utm_campaign' => 'admin', 'utm_source' => 'extensions', 'utm_medium' => 'title' ), $extension->url ); ?>" target="_blank"><?php echo $extension->title; ?></a>
					</h3>
					<div class="text"><?php echo $extension->excerpt ? fep_get_the_excerpt( 50, $extension->excerpt ) : ''; ?></div>
				</div>
				<div class="links">
					<?php if ( function_exists( str_replace( '-', '_', $slug ) . '_activate' ) ) : ?>
						<a class="button button-disabled" href="<?php echo add_query_arg( array( 'utm_campaign' => 'admin', 'utm_source' => 'extensions', 'utm_medium' => 'installed' ), $extension->url ); ?>" target="_blank"><?php _e( 'Installed', 'front-end-pm' ); ?></a>
					<?php else : ?>
						<a class="button" href="<?php echo add_query_arg( array( 'utm_campaign' => 'admin', 'utm_source' => 'extensions', 'utm_medium' => 'view_details' ), $extension->url ); ?>" target="_blank"><?php _e( 'View Details', 'front-end-pm' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php
		endforeach;
	} else {
		echo '<div class="error"><p>' . __( 'Error loading extensions. Please reload the page again!', 'front-end-pm' ) . '</p></div>';
	}
	?>
	<style type="text/css">
	.fep-extensions {
		border: 1px solid #E6E6E6;
		float: left;
		margin: 10px;
		width: 220px;
	}
	.fep-extensions .thumbnail img {
		max-height: 140px;
		max-width: 220px;
	}
	.fep-extensions .details {
		background: #fff;
		min-height: 10px;
		padding: 6px 10px 10px;
	}
	.fep-extensions .details h3.title {
		margin: 5px 0 10px;
		padding: 0;
	}
	.fep-extensions .details h3.title a {
		color: #111;
		text-decoration: none;
	}
	.fep-extensions .links {
		background: #F5F5F5;
		border-top: 1px solid #E6E6E6;
		padding: 10px;
	}
	.fep-extensions .links a.button.disabled {
		background: #eee;
	}
	</style>
</div>
