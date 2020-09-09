<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Shortcodes {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		//ADD SHORTCODES
		add_shortcode( 'front-end-pm', array( fep_main_class::init(), 'main_shortcode_output' ) ); //for FRONT END PM
		add_shortcode( 'fep_shortcode_new_message_count', array( $this, 'new_message_count' ) );
		add_shortcode( 'fep_shortcode_new_announcement_count', array( $this, 'new_announcement_count' ) );
		add_shortcode( 'fep_shortcode_message_to', array( $this, 'message_to' ) );
		add_shortcode( 'fep_shortcode_new_message_form', array( $this, 'new_message_form' ) );
	}

	function new_message_count( $atts = array(), $content = null, $tag = '' ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		$atts = shortcode_atts( array(
			'show_bracket'	=> '1',
			'hide_if_zero'	=> '1',
			'ajax'			=> '1',
			'class'			=> 'fep-font-red',
		), $atts, $tag );
		return fep_get_new_message_button( $atts );
	}

	function new_announcement_count( $atts = array(), $content = null, $tag = '' ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		$atts = shortcode_atts( array(
			'show_bracket'	=> '1',
			'hide_if_zero'	=> '1',
			'ajax'			=> '1',
			'class'			=> 'fep-font-red',
		), $atts, $tag );
		return fep_get_new_announcement_button( $atts );
	}

	function message_to( $atts, $content = null, $tag = '' ) {
		$atts = shortcode_atts( array(
			'to'		=> '{current-post-author}',
			'subject'	=> '{current-post-title}',
			'text'		=> __( 'Contact','front-end-pm' ),
			'class'		=> 'fep-button',
			'fep_mr_to'	=> false, // Comma separated list of user ids (used in PRO version)
		), $atts, $tag );
		if ( '{current-post-author}' == $atts['to'] ) {
			$atts['to'] = get_the_author_meta( 'user_nicename' );
		} elseif ( '{current-author}' == $atts['to'] ) {
			if ( $nicename = fep_get_userdata( get_query_var( 'author_name' ), 'user_nicename' ) ) {
				$atts['to'] = $nicename;
			} elseif ( $nicename = fep_get_userdata( get_query_var( 'author' ), 'user_nicename', 'id' ) ) {
				$atts['to'] = $nicename;
			}
			unset( $nicename );
		} elseif ( '{um-current-author}' == $atts['to'] && function_exists( 'um_profile_id' ) ) {
			$atts['to'] = fep_get_userdata( um_profile_id(), 'user_nicename', 'id' );
		} else {
			$atts['to'] = esc_html( $atts['to'] );
		}
		if ( false !== strpos( $atts['subject'], '{current-post-title}' ) ) {
			$atts['subject'] = rawurlencode( str_replace( '{current-post-title}', get_the_title(), $atts['subject'] ) );
		} elseif ( ! empty( $atts['subject'] ) ) {
			$atts['subject'] = rawurlencode( $atts['subject'] );
		} else {
			$atts['subject'] = false;
		}
		if ( empty( $atts['to'] ) && empty( $atts['fep_mr_to'] ) ) {
			return '';
		}
		return '<a href="' . fep_query_url( 'newmessage', array( 'fep_to' => $atts['to'], 'message_title' => $atts['subject'], 'fep_mr_to' => $atts['fep_mr_to'] ) ) . '" class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $atts['text'] ) . '</a>';
	}

	function new_message_form( $atts, $content = null, $tag = '' ) {
		$atts = shortcode_atts( array(
			'to'		=> '{current-post-author}',
			'subject'	=> '',
			'heading'	=> __( 'Contact','front-end-pm' ),
		), $atts, $tag );
		if ( '{current-post-author}' == $atts['to'] ) {
			$atts['to'] = get_the_author_meta( 'user_nicename' );
		} elseif ( '{current-author}' == $atts['to'] ) {
			if ( $nicename = fep_get_userdata( get_query_var( 'author_name' ), 'user_nicename' ) ) {
				$atts['to'] = $nicename;
			} elseif ( $nicename = fep_get_userdata( get_query_var( 'author' ), 'user_nicename', 'id' ) ) {
				$atts['to'] = $nicename;
			}
			unset( $nicename );
		} elseif ( '{um-current-author}' == $atts['to'] && function_exists( 'um_profile_id' ) ) {
			$atts['to'] = fep_get_userdata( um_profile_id(), 'user_nicename', 'id' );
		} elseif ( '{fep_to}' == $atts['to'] ) {
			$atts['to'] = isset( $_REQUEST['fep_to'] ) ? $_REQUEST['fep_to'] : '';
		} else {
			$atts['to'] = esc_html( $atts['to'] );
		}
		if ( false !== strpos( $atts['subject'], '{current-post-title}' ) ) {
			$atts['subject'] = str_replace( '{current-post-title}', get_the_title(), $atts['subject'] );
		} elseif ( '{message_title}' == $atts['subject'] ) {
			$atts['subject'] = isset( $_REQUEST['message_title'] ) ? $_REQUEST['message_title'] : '';
		}
		extract( $atts );
		$to_id = fep_get_userdata( $to );
		if ( ! is_user_logged_in() ) {
			return apply_filters( 'fep_filter_shortcode_new_message_form', '<div class="fep-error">' . sprintf( __( 'You must <a href="%s">login</a> to contact', 'front-end-pm' ), wp_login_url( get_permalink() ) ) . '</div>', $atts );
		} elseif ( ! fep_current_user_can( 'send_new_message_to', $to_id ) ) {
			return apply_filters( 'fep_filter_shortcode_new_message_form', '<div class="fep-error">' . sprintf( __( 'You cannot send message to %s', 'front-end-pm' ), fep_user_name( $to_id ) ) . '</div>', $atts );
		}
		$template = fep_locate_template( 'form-shortcode-message.php' );
		ob_start();
		include( $template );
		return apply_filters( 'fep_filter_shortcode_new_message_form', ob_get_clean(), $atts );
	}
} //END CLASS
add_action( 'init', array( Fep_Shortcodes::init(), 'actions_filters' ) );
