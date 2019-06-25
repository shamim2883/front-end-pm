<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Ajax {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'wp_ajax_fep_review_notice_dismiss', array( $this, 'fep_review_notice_dismiss' ) );
		add_action( 'wp_ajax_fep_ajax_att_delete', array( $this, 'att_delete' ) );
		if ( fep_get_option( 'block_other_users', 1 ) ) {
			add_action( 'wp_ajax_fep_block_unblock_users_ajax', array( $this, 'fep_block_unblock_users_ajax' ) );
		}
	}

	function fep_block_unblock_users_ajax() {
		if ( check_ajax_referer( 'fep-block-unblock-script', 'token', false ) && ! empty( $_POST['user_id'] ) ) {
			$user_id = absint( $_POST['user_id'] );
			if ( fep_is_user_blocked_for_user( get_current_user_id(), $user_id ) ) {

				fep_unblock_users_for_user( $user_id );
				$return = __( 'Block', 'front-end-pm' );
			} else {
				fep_block_users_for_user( $user_id );
				$return = __( 'Unblock', 'front-end-pm' );
			}
			wp_die( $return );
		}
		$return = __( 'Failed', 'front-end-pm' );
		wp_die( $return );
	}

	function fep_review_notice_dismiss() {
		if ( ! empty( $_POST['fep_click'] ) && current_user_can( 'manage_options' ) ) {
			if ( 'later' == $_POST['fep_click'] ) {
				update_user_meta( get_current_user_id(), 'fep_review_notice_dismiss', time() );
			} elseif ( in_array( $_POST['fep_click'], array( 'sure', 'did' ) ) ) {

				fep_update_option( 'dismissed-review', time() );
			}
		}
		die;
	}
	
	function att_delete() {
		$id     = isset( $_GET['fep_id'] ) ? absint( $_GET['fep_id'] ) : 0;
		$mgs_id = isset( $_GET['fep_parent_id'] ) ? absint( $_GET['fep_parent_id'] ) : 0;

		check_ajax_referer( "delete-att-{$id}", 'nonce' );

		if ( ! $id || ! $mgs_id || ! fep_is_user_admin() ) {
			wp_send_json_error();
		}
		if ( FEP_Attachments::init()->delete( $mgs_id, $id ) ) {
			wp_send_json_success();
		}
		wp_send_json_error();
	}
} //END CLASS

add_action( 'init', array( Fep_Ajax::init(), 'actions_filters' ) );
