<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Menu {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function actions_filters() {
			add_action( 'fep_menu_button', array( $this, 'menu' ) );
	}

	function menu() {
		$menu = '';

		foreach ( $this->get_menu() as $menu_array ) {
			$class = $menu_array['class'];
			if ( isset( $_GET['fepaction'] ) && $_GET['fepaction'] === $menu_array['action'] ) {
				$class = $menu_array['active-class'];
			}

			$menu .= sprintf(
				'<a id="%1$s" class="%2$s" href="%3$s">%4$s</a>',
				$menu_array['id'],
				fep_sanitize_html_class( $class ),
				$menu_array['url'] ? esc_url( $menu_array['url'] ) : fep_query_url( $menu_array['action'] ),
				strip_tags( $menu_array['title'], '<span>' )
			);
		}
		echo $menu;
	}

	public function get_menu() {
		$menu = array(
			'message_box' => array(
				'title'    => sprintf( __( 'Message Box%s', 'front-end-pm' ), fep_get_new_message_button() ),
				'action'   => 'messagebox',
				'priority' => 10,
			),
		);
		if ( fep_current_user_can( 'send_new_message' ) ) {
			$menu['newmessage'] = array(
				'title'    => __( 'New Message', 'front-end-pm' ),
				'action'   => 'newmessage',
				'priority' => 5,
			);
		}

		$menu = apply_filters( 'fep_menu_buttons', $menu );

		foreach ( $menu as $key => $tab ) {
			$defaults     = array(
				'title'        => '',
				'action'       => $key,
				'url'          => '',
				'id'           => 'fep-menu-' . $key,
				'class'        => 'fep-button',
				'active-class' => 'fep-button-active',
				'priority'     => 20,
			);
			$menu[ $key ] = wp_parse_args( $tab, $defaults );
		}
		uasort( $menu, 'fep_sort_by_priority' );
		return $menu;
	}

} //END CLASS

add_action( 'init', array( Fep_Menu::init(), 'actions_filters' ) );

