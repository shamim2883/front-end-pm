<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Main CLASS
if ( ! class_exists( 'fep_main_class' ) ) {
	class fep_main_class {
		private static $instance;

		public static function init() {
			if ( ! self::$instance instanceof self ) {
				self::$instance = new self;
			}
		return self::$instance;
		}

		/******************************************MAIN DISPLAY BEGIN******************************************/
		//Display the proper contents
		function main_shortcode_output( $atts, $content = null ) {
			global $user_ID;
			if ( is_user_logged_in() ) {
				if ( ! fep_current_user_can( 'access_message' ) ) {
					return apply_filters( 'fep_main_shortcode_output', '<div class="fep-error">' . __( 'You do not have permission to access message system', 'front-end-pm' ) . '</div>' );
				}
				$atts = shortcode_atts( array(
					'fepaction'		=> 'messagebox',
					'fep-filter'	=> 'show-all',
				), $atts, 'front-end-pm' );
				if ( empty( $_GET['fepaction'] ) ) {
					$_GET['fepaction'] = $atts['fepaction'];
				}
				if ( $_GET['fepaction'] == $atts['fepaction'] && empty( $_GET['fep-filter'] ) ) {
					$_GET['fep-filter'] = $atts['fep-filter'];
				}
				
				//Add header
				$out = $this->Header();

				//Add Menu
				$out .= $this->Menu();
				$menu = Fep_Menu::init()->get_menu();

				//Start the guts of the display
				$switch = ( isset( $_GET['fepaction'] ) && $_GET['fepaction'] ) ? $_GET['fepaction'] : 'messagebox';
				switch ( $switch ) {
					case has_action( "fep_switch_{$switch}" ):
						ob_start();
						do_action( "fep_switch_{$switch}" );
						$out .= ob_get_contents();
						ob_end_clean();
						break;
					case has_filter( "fep_filter_switch_{$switch}" ):
						$out .= apply_filters( "fep_filter_switch_{$switch}", '' );
						break;
					case ( 'newmessage' == $switch && ! empty( $menu['newmessage'] ) ):
						$out .= $this->new_message();
						break;
					case 'viewmessage':
						$out .= $this->view_message();
						break;
					case 'messagebox':
					default: //Message box is shown by Default
						$out .= $this->fep_message_box();
						break;
				}

				//Add footer
				$out .= $this->Footer();
			} else { 
				$out = '<div class="fep-error">' . sprintf( __( 'You must <a href="%s">login</a> to view your message.', 'front-end-pm' ), wp_login_url( get_permalink() ) ) . '</div>';
			}
			return apply_filters( 'fep_main_shortcode_output', $out);
		}

		function Header() {
			global $user_ID;
			$total_count = fep_get_user_message_count( 'total' );
			$unread_count = fep_get_user_message_count( 'unread' );
			$unread_ann_count = fep_get_user_announcement_count( 'unread' );
			$max_total = fep_get_current_user_max_message_number();
			$max_text = $max_total ? number_format_i18n( $max_total) : __( 'unlimited', 'front-end-pm' );
			$template = fep_locate_template( 'header.php' );
			ob_start();
			include( $template );
			return ob_get_clean();
		}

		function Menu() {
			$template = fep_locate_template( 'menu.php' );
			ob_start();
			include( $template );
			return ob_get_clean();
		}

		function Footer() {
			$template = fep_locate_template( 'footer.php' );
			ob_start();
			include( $template );
			return ob_get_clean();
		}

		function fep_message_box() {
			$box_content = Fep_Messages::init()->user_messages();
			$template = fep_locate_template( 'box-message.php' );
			ob_start();
			include( $template );
			return apply_filters( 'fep_messagebox', ob_get_clean() );
		}

		function new_message() {
			$template = fep_locate_template( 'form-message.php' );
			ob_start();
			include( $template );
			return ob_get_clean();
		}

		function view_message() {
			
			if ( isset( $_GET['fep_id'] ) ) {
				$id = absint( $_GET['fep_id'] );
			} else {
				$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			}
			if ( ! $id || ! is_numeric( $id )) {
				return '<div class="fep-error">' . __( 'You do not have permission to view this message!', 'front-end-pm' ) . '</div>';
			}
			$messages = Fep_Messages::init()->get_message_with_replies( $id );
			
			if ( ! fep_current_user_can( 'view_message', $id ) ) {
				return '<div class="fep-error">' . __( 'You do not have permission to view this message!', 'front-end-pm' ) . '</div>';
			}
			$parent_id = fep_get_parent_id( $id );
			$template = fep_locate_template( 'view-message.php' );

			ob_start();
			include( $template );
			$return = ob_get_clean();

			return apply_filters( 'fep_filter_viewmessage', $return, $id );
		}
		/******************************************MAIN DISPLAY END******************************************/
	} //END CLASS
} //ENDIF
