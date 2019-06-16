<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_REST_API {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		$namespace = 'front-end-pm/v1';
		register_rest_route(
			$namespace, '/view-message/(?P<fep_id>\d+)', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'message_content' ),
				'permission_callback' => function ( $request ) {
					return fep_current_user_can( 'access_message' );
				},
				'args'                => array(
					'fep_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
		// fep-filter not working with hyphen so use fep_filter (without hyphen).
		register_rest_route(
			$namespace, '/message-heads/(?P<feppage>\d+)/(?P<fep_filter>[a-zA-Z0-9_-]+)', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'message_heads' ),
				'permission_callback' => function ( $request ) {
					return fep_current_user_can( 'access_message' );
				},
				'args'                => array(
					'feppage'    => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'fep_filter' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		register_rest_route(
			$namespace, '/users/(?P<for>[a-zA-Z0-9_-]+)/', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'users' ),
				'permission_callback' => function ( $request ) {
					return fep_current_user_can( 'access_message' );
				},
				'args'                => array(
					'for' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'q'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'x'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	function message_content( $request ) {
		$response = [];
		$mgs_id   = $request->get_param( 'fep_id' );
		if ( ! $mgs_id ) {
			return new WP_Error( 'no_id', esc_html__( 'You cannot view this message.', 'front-end-pm' ), array( 'status' => 404 ) );
		}
		$messages = Fep_Messages::init()->get_message_with_replies( $mgs_id );

		if ( ! fep_current_user_can( 'view_message', $mgs_id ) ) {
			return new WP_Error( 'no_permission', esc_html__( 'You do not have permission to view this message!', 'front-end-pm' ), array( 'status' => 404 ) );
		}

		if ( ! $messages->have_messages() ) {
			$response['show_reply_form'] = false;
		} elseif ( ! fep_current_user_can( 'send_reply', $mgs_id ) ) {
			$response['show_reply_form']       = false;
			$response['show_reply_form_error'] = '<div class="fep-error">' . esc_html__( 'You do not have permission to send reply to this message!', 'front-end-pm' ) . '</div>';
		} else {
			$response['show_reply_form'] = true;
		}
		ob_start();
		require fep_locate_template( 'view-message-content.php' );
		$response['data_formated'] = ob_get_clean();

		return rest_ensure_response( $response );
	}

	function message_heads( $request ) {
		$response           = [];
		$_GET['feppage']    = $request->get_param( 'feppage' );
		$_GET['fep-filter'] = $request->get_param( 'fep_filter' ); // fep-filter not working with hyphen so use fep_filter (without hyphen).

		ob_start();
		require fep_locate_template( 'view-message-heads.php' );
		$response['data_formated'] = ob_get_clean();

		return rest_ensure_response( $response );
	}

	function users( $request ) {
		$response = [];
		$for      = $request->get_param( 'for' );
		$q        = $request->get_param( 'q' );
		$x        = $request->get_param( 'x' );
		$exclude  = ! $x ? array() : explode( ',', $x );
		// $exclude[] = get_current_user_id();
		$args = array(
			'search'         => "*{$q}*",
			'search_columns' => array( 'user_login', 'display_name' ),
			'exclude'        => $exclude,
			'number'         => 10,
			'orderby'        => 'display_name',
			'order'          => 'ASC',
			'role__in'       => fep_get_option( 'userrole_access', array() ),
		);

		if ( strlen( $q ) > 0 ) {
			if ( 'autosuggestion' === $for ) {
				if ( ! fep_get_option( 'show_autosuggest', 1 ) && ! fep_is_user_admin() ) {
					return rest_ensure_response( $response );
				}
				$args['exclude'][] = get_current_user_id();
				$args = apply_filters( 'fep_autosuggestion_arguments', $args );
			} elseif ( 'multiple_recipients' === $for ) {
				$args['exclude'][] = get_current_user_id();
				$args = apply_filters( 'fep_users_ajax_arguments', $args );
			} elseif ( 'blocked' === $for ) {
				$args['exclude'][] = get_current_user_id();
				$args = apply_filters( 'fep_users_ajax_arguments', $args );
			}
			
			$args = apply_filters( 'fep_filter_rest_users_args', $args, $for, $q, $x );
			
			if ( has_filter( "fep_filter_rest_users_response_{$for}" ) ) {
				$response = apply_filters( "fep_filter_rest_users_response_{$for}", $response, $args, $q, $x );
			} elseif ( has_filter( 'fep_filter_rest_users_response' ) ) {
				$response = apply_filters( 'fep_filter_rest_users_response', $response, $args, $for, $q, $x );
			} else {
				// The Query
				$users = get_users( $args );
				foreach ( $users as $user ) {
					$response[] = array(
						'id'       => $user->ID,
						'nicename' => $user->user_nicename,
						'name'     => fep_user_name( $user->ID ),
					);
				}
			}
		}

		return rest_ensure_response( $response );
	}
} //END CLASS

add_action( 'init', array( FEP_REST_API::init(), 'actions_filters' ) );
