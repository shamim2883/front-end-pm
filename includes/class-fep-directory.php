<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Directory {
	private static $instance;
	
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		if ( fep_current_user_can( 'access_directory' ) ) {
			add_filter( 'fep_menu_buttons', array( $this, 'menu' ) );
			add_action( 'fep_switch_directory', array( $this, 'directory' ) );
			add_action( 'fep_posted_bulk_directory_bulk_action', array( $this, 'bulk_action' ) );
		}
	}

	function menu( $menu ) {
		$menu['directory']	= array(
			'title'		=> __( 'Directory', 'front-end-pm' ),
			'action'	=> 'directory',
			'priority'	=> 25,
		);
		return $menu;
	}

	function directory() {
		if ( ! fep_current_user_can( 'access_directory' ) ) {
			echo apply_filters( 'fep_directory_output', '<div class="fep-error">' . __( 'You do not have permission to access directory!', 'front-end-pm' ) . '</div>' );
			return;
		}
		$g_filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';

		// The Query
		$user_query = $this->user_query();
		$total = $user_query->get_total();

		$template = fep_locate_template( 'directory.php' );

		ob_start();
		include( $template );
		echo apply_filters( 'fep_directory_output', ob_get_clean() );
	}
	
	function user_query() {
		$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';

		$args = array(
			'number'	=> fep_get_option( 'user_page', 50 ),
			'paged'		=> ! empty( $_GET['feppage']) ? absint( $_GET['feppage'] ): 1,
			'orderby'	=> 'display_name',
			'order'		=> 'ASC',
			'fields'	=> 'all_with_meta',
			'role__in' => fep_get_option( 'userrole_access', array() ),
		);
		if ( ! empty( $_GET['fep-search'] ) ) {
			$args['search'] = '*' . $_GET['fep-search'] . '*';
		}
		switch ( $filter ) {
			case 'blocked':
				if ( fep_get_blocked_users_for_user() ) {
					$args['include'] = fep_get_blocked_users_for_user();
				} else {
					$args['include'] = array(0);
				}
				break;
			case 'unblocked':
				$args['exclude'] = fep_get_blocked_users_for_user();
				break;
			default:
				$args = apply_filters( 'fep_directory_query_args_' . $filter, $args );
				break;
		}

		$args = apply_filters( 'fep_directory_arguments', $args );

		// The Query
		return new WP_User_Query( $args );
	}
	
	function get_table_bulk_actions() {
		$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';

		$actions = array(
			'block'		=> __( 'Block', 'front-end-pm' ),
			'unblock'	=> __( 'Unblock', 'front-end-pm' ),
		);

		if ( 'blocked' == $filter ) {
			unset( $actions['block'] );
		}

		return apply_filters( 'fep_directory_table_bulk_actions', $actions );
	}

	function get_table_filters() {
		$filters = array(
			'show-all'	=> __( 'Show all', 'front-end-pm' ),
			'blocked'	=> __( 'Blocked', 'front-end-pm' ),
			'unblocked'	=> __( 'Unblocked', 'front-end-pm' ),
		);
		return apply_filters('fep_directory_table_filters', $filters );
	}

	function get_table_columns() {
		$columns = array(
			'fep-cb' => __( 'Checkbox', 'front-end-pm' ),
			'avatar' => __( 'Avatar', 'front-end-pm' ),
			'name'	 => __( 'Name', 'front-end-pm' ),
		);
		if ( fep_get_option( 'block_other_users', 1 ) ) {
			$columns['block_unblock'] = __( 'Block', 'front-end-pm' ) . '/' . __( 'Unblock', 'front-end-pm' );
		}
		$columns['send_message'] = __( 'Send Message', 'front-end-pm' );

		return apply_filters('fep_directory_table_columns', $columns );
	}

	function get_column_content( $column, $user ) {
		switch ( $column ) {
			case has_action( "fep_directory_table_column_content_{$column}" ):
				do_action( "fep_directory_table_column_content_{$column}", $user );
				break;
			case 'fep-cb':
				?><input type="checkbox" class="fep-cb" name="fep-directory-cb[]" value="<?php echo $user->ID; ?>" /><?php
				break;
			case 'avatar':
				echo get_avatar( $user->ID, 55, '', fep_user_name( $user->ID ) );
				break;
			case 'name':
				echo fep_user_name( $user->ID );
				break;
			case 'block_unblock':
				wp_enqueue_script( 'fep-block-unblock-script' );
				if ( get_current_user_id() != $user->ID ) {
					if ( fep_is_user_blocked_for_user( get_current_user_id(), $user->ID ) ) {
						echo '<a href="#" class="fep_block_unblock_user fep_user_blocked" data-user_id="' . $user->ID . '" data-user_name="' . esc_attr( fep_user_name( $user->ID ) ) . '">' . esc_html__( 'Unblock', 'front-end-pm' ) . '</a>';
					} else {
						echo '<a href="#" class="fep_block_unblock_user" data-user_id="' . $user->ID . '" data-user_name="' . esc_attr( fep_user_name( $user->ID ) ) . '">' . esc_html__( 'Block', 'front-end-pm' ) . '</a>';
					}
				}
				break;
			case 'send_message' :
				if ( get_current_user_id() != $user->ID ) {
					?><a href="<?php echo fep_query_url( 'newmessage', array( 'fep_to' => $user->user_nicename ) ); ?>"><?php _e( 'Send Message', 'front-end-pm' ); ?></a><?php
				}
				break;
			default:
				do_action( 'fep_directory_table_column_content', $column, $user );
				break;
		}
	}

	function bulk_action( $action, $ids = null ) {
		if ( null === $ids ) {
			$ids = ! empty( $_POST['fep-directory-cb'] ) ? $_POST['fep-directory-cb'] : array();
		}
		if ( ! $action || ! $ids || ! is_array( $ids ) ) {
			fep_errors()->add( 'empty', __( 'Please select users.', 'front-end-pm' ) );
			return;
		}
		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( ! $ids ) {
			return;
		}
		switch ( $action ) {
			case 'block':
				if ( ! fep_get_option( 'block_other_users', 1 ) ) {
					fep_errors()->add( 'no-permission', __( 'You cannot block other users.', 'front-end-pm' ) );
					break;
				}
				$count = fep_block_users_for_user( $ids );
				$message = sprintf( _n( '%s user successfully blocked.', '%s users successfully blocked.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
				fep_success()->add( 'success', $message );
				break;
			case 'unblock':
				$count = fep_unblock_users_for_user( $ids );
				$message = sprintf( _n( '%s user successfully unblocked.', '%s users successfully unblocked.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
				fep_success()->add( 'success', $message );
				break;
			default:
				do_action( "fep_directory_posted_bulk_action_{$action}", $ids );
				break;
		}
	}
} //END CLASS
add_action( 'wp_loaded', array( Fep_Directory::init(), 'actions_filters' ) );
