<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Announcement CLASS
class FEP_Announcements {
	private static $instance;
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'fep_transition_post_status', array( $this, 'recalculate_user_stats' ), 10, 3 );
		add_filter( 'fep_menu_buttons', array( $this, 'menu' ) );
		if ( fep_current_user_can( 'add_announcement' ) && fep_get_option( 'add_ann_frontend', 1 ) ) {
			add_filter( 'fep_menu_buttons', array( $this, 'menu_new_announcement' ) );
			add_filter( 'fep_filter_switch_new_announcement', array( $this, 'new_announcement' ) );
			add_action( 'fep_posted_action_new_announcement', array( $this, 'fep_posted_action_new_announcement' ) );
		}
		$menu = Fep_Menu::init()->get_menu();
		if ( ! empty( $menu['announcements'] ) ) {
			add_filter( 'fep_filter_switch_announcements', array( $this, 'announcement_box' ) );
			add_filter( 'fep_filter_switch_view_announcement', array( $this, 'view_announcement' ) );
			add_action( 'fep_posted_bulk_announcement_bulk_action', array( $this, 'bulk_action' ) );
		}
	}

	function menu( $menu ) {
		$menu['announcements'] = array(
			'title'		=> sprintf( __( 'Announcement%s', 'front-end-pm' ), fep_get_new_announcement_button() ),
			'action'	=> 'announcements',
			'priority'	=> 20,
		);
		return $menu;
	}

	function menu_new_announcement( $menu ) {
		$menu['new_announcement'] = array(
			'title'		=> __( 'New Announcement', 'front-end-pm' ),
			'action'	=> 'new_announcement',
			'priority'	=> 22,
		);
		return $menu;
	}

	function new_announcement() {
		$template = fep_locate_template( 'form-announcement.php' );
		ob_start();
		include( $template );
		return ob_get_clean();
	}

	function fep_posted_action_new_announcement() {
		if ( ! fep_current_user_can( 'add_announcement' ) ) {
			fep_errors()->add( 'permission', __( 'You do not have permission to create announcement!', 'front-end-pm' ) );
			return;
		}

		Fep_Form::init()->validate_form_field( 'new_announcement' );
		if ( count( fep_errors()->get_error_messages() ) == 0 ) {
			if ( fep_add_announcement() ) {
				fep_success()->add( 'publish', __( 'Announcement successfully added.', 'front-end-pm' ) );
			} else {
				fep_errors()->add( 'undefined', __( 'Something wrong. Please try again.', 'front-end-pm' ) );
			}
		}
	}

	function recalculate_user_stats( $new_status, $old_status, $post ) {
		global $wpdb;
		if ( 'announcement' != $post->mgs_type ) {
			return;
		}
		if ( 'publish' == $new_status || 'publish' == $old_status ) {
			delete_metadata( 'user', 0, '_fep_user_announcement_count', '', true );
		}
		if ( 'publish' == $new_status ) {
			delete_metadata( 'user', 0, '_fep_notification_dismiss', '', true );
		}
	}
	
	function get_announcement_with_replies( $id ) {
		$args = array(
			'mgs_type'		=> 'announcement',
			'mgs_status'	=> 'publish',
			'mgs_id'		=> $id,
			'include_child' => true,
			'per_page'		=> 0,
			'orderby'		=>'mgs_created',
			'order'			=> 'ASC',
			'count_total'	=> false,
		);
		$args = apply_filters( 'fep_filter_get_announcement_with_replies', $args );
		return new FEP_Message_Query( $args );
	}

	function get_user_announcements() {
		$user_id = get_current_user_id();
		$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
		$args = array(
			'mgs_type'		=> 'announcement',
			'mgs_status'	=> 'publish',
			'mgs_parent'	=> 0,
			'per_page'=> fep_get_option( 'announcements_page', 15 ),
			'paged'			=> ! empty( $_GET['feppage'] ) ? absint( $_GET['feppage'] ): 1,
			'check_more_row' => true,
			'count_total'    => false,
		);

		if ( ! empty( $_GET['fep-search'] ) ) {
			$args['s'] = $_GET['fep-search'];
		}
		switch ( $filter ) {
			case 'read':
				$args['participant_query'][] = array(
					'mgs_participant' => $user_id,
					'mgs_deleted' => false,
					'mgs_parent_read' => true,
				);
				break;
			case 'unread':
				$args['participant_query'][] = array(
					'mgs_participant' => $user_id,
					'mgs_deleted' => false,
					'mgs_parent_read' => false,
				);
				break;
			default:
				$args['participant_query'][] = array(
					'mgs_participant' => $user_id,
					'mgs_deleted' => false,
				);
				$args = apply_filters( 'fep_announcement_query_args_' . $filter, $args );
				break;
		}
		$args = apply_filters( 'fep_announcement_query_args', $args );
		return new FEP_Message_Query( $args );;
	}

	function get_user_announcement_count( $value = 'all', $force = false, $user_id = false ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( 'show-all' == $value ) {
			$value = 'total';
		}
		if ( ! $user_id ) {
			if ( 'all' == $value ) {
				return array();
			} else {
				return 0;
			}
		}
		$user_meta = get_user_meta( $user_id, '_fep_user_announcement_count', true );
		if ( false === $user_meta || $force || ! isset( $user_meta['unread'] ) ) {
			$args = array(
				'mgs_type'		=> 'announcement',
				'mgs_status'	=> 'publish',
				'per_page'		=> 0,
				'fields'		=> 'COUNT(*)',
				'mgs_parent' 	=> 0,
				'orderby'       => false,
				'participant_query' => array(
					array(
						'mgs_participant' => $user_id,
						'mgs_parent_read' => false,
						'mgs_deleted' => false,
					)
				),
			);
			$args = apply_filters( 'fep_announcement_count_query_args', $args );

			$user_meta = array(
				'unread' => fep_get_messages( $args ),
			);
			update_user_meta( $user_id, '_fep_user_announcement_count', $user_meta );
		}
		if ( isset( $user_meta[ $value ] ) ) {
			return $user_meta[ $value ];
		}
		if ( 'all' == $value ) {
				return $user_meta;
			} else {
				return 0;
			}
	}

	function bulk_action( $action, $ids = null ) {
		if ( null === $ids ) {
			$ids = ! empty( $_POST['fep-message-cb'] ) ? $_POST['fep-message-cb'] : array();
		}
		if ( ! $action || ! $ids || ! is_array( $ids ) ) {
			return '';
		}
		$count = 0;
		foreach ( $ids as $id ) {
			if ( $this->bulk_individual_action( $action, absint( $id ) ) ) {
				$count++;
			}
		}
		$message = '';
		if ( $count ) {
			delete_user_meta( get_current_user_id(), '_fep_user_announcement_count' );
			if ( 'delete' == $action ) {
				$message = sprintf( _n( '%s announcement successfully deleted.', '%s announcements successfully deleted.', $count, 'front-end-pm' ), number_format_i18n( $count) );
			} 
			//$message = '<div class="fep-success">' .$message.'</div>';
		}
		$message = apply_filters( 'fep_bulk_action_message', $message, $count );
		if ( $message ) {
			fep_success()->add( 'success', $message );
		}
	}

	function bulk_individual_action( $action, $id ) {
		$return = false;
		switch ( $action ) {
			case 'delete':
				if ( fep_current_user_can( 'view_announcement', $id ) ) {
					$return = FEP_Participants::init()->mark( $id, get_current_user_id(), ['delete' => true ] );
				}
				break;
			default:
				$return = apply_filters( 'fep_announcement_bulk_individual_action', false, $action, $id );
				break;
		}
		return $return;
	}

	function get_table_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'front-end-pm' )
		);
		return apply_filters( 'fep_announcement_table_bulk_actions', $actions );
	}

	function get_table_filters() {
		$filters = array(
			'show-all'			=> __( 'Show all', 'front-end-pm' ),
			'read'				=> __( 'Read', 'front-end-pm' ),
			'unread'			=> __( 'Unread', 'front-end-pm' ),
			//'after-i-registered'=> __( 'After I registered', 'front-end-pm' ),
		);
		return apply_filters( 'fep_announcementbox_table_filters', $filters );
	}

	function get_table_columns() {
		$columns = array(
			'fep-cb'=> __( 'Checkbox', 'front-end-pm' ),
			'date'	=> __( 'Date', 'front-end-pm' ),
			'title'	=> __( 'Title', 'front-end-pm' ),
		);
		return apply_filters( 'fep_announcement_table_columns', $columns );
	}

	function get_column_content( $column ) {
		switch ( $column ) {
			case has_action( "fep_get_announcement_column_content_{$column}" ):
				do_action( "fep_get_announcement_column_content_{$column}" );
				break;
			case 'fep-cb':
				?><input type="checkbox" class="fep-cb" name="fep-message-cb[]" value="<?php echo fep_get_the_id(); ?>" /><?php
				break;
			case 'date':
				?><span class="fep-message-date"><?php echo fep_get_the_date(); ?></span><?php
				break;
			case 'title':
				if ( ! fep_is_read( true ) ) {
					$span = '<span class="fep-unread-classp"><span class="fep-unread-class">' .__( 'Unread', 'front-end-pm' ). '</span></span>';
					$class = ' fep-strong';
				} else {
					$span = '';
					$class = '';
				} 
				?><span class="<?php echo $class; ?>"><a href="<?php echo fep_query_url( 'view_announcement', array( 'fep_id'=> fep_get_the_id() ) ); ?>"><?php echo fep_get_the_title(); ?></a></span><?php echo $span; ?>
				<div class="fep-message-excerpt">
					<?php echo fep_get_the_excerpt(); ?>
				</div><?php
				break;
			default:
				do_action( 'fep_get_announcement_column_content', $column );
				break;
		}
	}

	function announcement_box() {
		$box_content = $this->get_user_announcements();
		$template = fep_locate_template( 'box-announcement.php' );
		ob_start();
		include( $template );
		return apply_filters( 'fep_announcementbox', ob_get_clean() );
	}

	function view_announcement() {
		
		if ( isset( $_GET['fep_id'] ) ) {
			$id = absint( $_GET['fep_id'] );
		} else {
			$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		}
		if ( ! $id || ! is_numeric( $id ) ) {
			return '<div class="fep-error">' . __( 'You do not have permission to view this announcement!', 'front-end-pm' ) . '</div>';
		}
		$announcements = $this->get_announcement_with_replies( $id );
		$template = fep_locate_template( 'view-announcement.php' );
		
		if ( ! fep_current_user_can( 'view_announcement', $id ) ) {
			return '<div class="fep-error">' . __( 'You do not have permission to view this announcement!', 'front-end-pm' ) . '</div>';
		}

		ob_start();
		include( $template );
		$return = ob_get_clean();

		return apply_filters( 'fep_filter_view_announcement', $return, $id );
	}
} //END CLASS
add_action( 'wp_loaded', array( FEP_Announcements::init(), 'actions_filters' ) );
