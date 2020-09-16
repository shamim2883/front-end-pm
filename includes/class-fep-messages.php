<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//Message CLASS
class Fep_Messages {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'fep_action_validate_form', array( $this, 'time_delay_check' ), 10, 2 );
		add_action( 'fep_action_validate_form', array( $this, 'box_full_check' ), 10, 2 );
		add_action( 'fep_posted_bulk_bulk_action', array( $this, 'bulk_action' ) );
	}

	function time_delay_check( $where, $errors ) {
		if ( ! in_array( $where, [ 'newmessage', 'shortcode-newmessage' ] ) ) {
			return;
		}
		$delay = absint( fep_get_option( 'time_delay', 5 ) );
		if ( fep_is_user_admin() || ! $delay ) {
			return;
		}
		$args = array(
			'mgs_type'		=> 'message',
			'mgs_author'	=> get_current_user_id(),
			'created_after'	=> date( 'Y-m-d H:i:s', strtotime( "-{$delay} minutes" ) ),
			'fields'		=> array( 'mgs_id' ),
			'per_page'		=> 1,
		);
		if ( 'threaded' == fep_get_message_view() ) {
			$args['mgs_parent'] = 0;
		}
		if ( fep_get_messages( $args ) ) {
			$errors->add( 'time_delay', sprintf( __( 'Please wait at least %s between messages.', 'front-end-pm' ), sprintf( _n( '%s minute', '%s minutes', $delay, 'front-end-pm' ), number_format_i18n( $delay ) ) ) );
		}
	}

	function box_full_check( $where, $errors ) {
		if ( ! in_array( $where, [ 'newmessage', 'shortcode-newmessage' ] ) ) {
			return;
		}
		if ( ! $max = fep_get_current_user_max_message_number() ) {
			return;
		}
		if ( fep_get_user_message_count( 'total' ) >= $max ) {
			$errors->add( 'MgsBoxFull', __( 'Your message box is full. Please delete some messages.', 'front-end-pm' ) );
		}
	}

	function user_message_count( $value = 'all', $force = false, $user_id = false ) {
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
		$user_meta = get_user_meta( $user_id, '_fep_user_message_count', true );
		if ( false === $user_meta || $force || ! isset( $user_meta['total'] ) || ! isset( $user_meta['unread'] ) ) {
			$args = array(
				'mgs_type'		=> 'message',
				'mgs_status'	=> 'publish',
				'per_page'		=> 0,
				'fields'		=> 'COUNT(*)',
				'orderby'       => false,
			);
			if ( 'threaded' == fep_get_message_view() ) {
				$args['mgs_parent'] = 0;
			}
			$args = apply_filters( 'fep_message_count_query_args', $args, $user_id );
			
			$total_args = $args;
			$total_args['participant_query'][] = array(
				'mgs_participant' => $user_id,
				'mgs_deleted' => false,
			);
			
			$unread_args = $args;
			$unread_args['participant_query'][] = array(
				'mgs_participant' => $user_id,
				'mgs_parent_read' => false,
				'mgs_deleted' => false,
			);
			
			$user_meta = array(
				'total'		=> fep_get_messages( $total_args ),
				'unread'	=> fep_get_messages( $unread_args ),
			);
			update_user_meta( $user_id, '_fep_user_message_count', $user_meta );
		}
		if ( isset( $user_meta[$value] ) ) {
			return $user_meta[$value];
		}
		if ( 'all' == $value ) {
			return $user_meta;
		} else {
			return 0;
		}
	}

	function user_messages( $action = 'messagebox', $user_id = false ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
		$args = array(
			'mgs_type'		=> 'message',
			'mgs_status'	=> 'publish',
			'per_page'		=> fep_get_option( 'messages_page', 15 ),
			'paged'			=> ! empty( $_GET['feppage'] ) ? absint( $_GET['feppage'] ): 1,
			'check_more_row' => true,
			'count_total'    => false,
		);
		if ( 'threaded' == fep_get_message_view() ) {
			$args['mgs_parent'] = 0;
			$args['orderby'] = 'mgs_last_reply_time';
		} else {
			$args['orderby'] = 'mgs_created';
		}
		if ( ! empty( $_GET['fep-search'] ) ) {
			$args['s'] = $_GET['fep-search'];
		}
		switch ( $filter ) {
			case 'inbox':
				if ( 'threaded' == fep_get_message_view() ) {
					$args['mgs_last_reply_by_not_in'] = [ $user_id ];
				} else {
					$args['mgs_author_not_in'] = [ $user_id ];
				}
				$args['participant_query'][] = array(
					'mgs_participant' => $user_id,
					'mgs_deleted' => false,
				);
				break;
			case 'sent':
				if ( 'threaded' == fep_get_message_view() ) {
					$args['mgs_last_reply_by'] = $user_id;
				} else {
					$args['mgs_author'] = $user_id;
				}
				$args['participant_query'][] = array(
					'mgs_participant' => $user_id,
					'mgs_deleted' => false,
				);
				break;
			case 'archive':
				$args['participant_query'][] = array(
					'mgs_participant' => $user_id,
					'mgs_deleted' => false,
					'mgs_archived' => true,
				);
				break;
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
					'mgs_archived' => false,
				);
				$args = apply_filters( 'fep_message_query_args_' . $filter, $args, $user_id );
				break;
		}
		$args = apply_filters( 'fep_message_query_args', $args, $user_id );
		
		$query = new FEP_Message_Query( $args );
		
		return $query;
	}
	
	function get_message_with_replies( $id ) {
		$args = array(
			'mgs_type'		=> 'message',
			'mgs_status'	=> 'publish',
			'mgs_id'		=> $id,
			'per_page'		=> 0,
			'orderby'		=>'mgs_created',
			'order'			=> 'ASC',
			'count_total'	=> false,
		);
		if ( 'threaded' == fep_get_message_view() ) {
			$args['include_child'] = true;
		}
		$args = apply_filters( 'fep_filter_get_message_with_replies', $args );
		return new FEP_Message_Query( $args );
	}

	function bulk_action( $action, $ids = null ) {
		if ( null === $ids ) {
			$ids = ! empty( $_POST['fep-message-cb'] ) ? $_POST['fep-message-cb'] : array();
		}
		if ( ! $action || ! $ids || ! is_array( $ids ) ) {
			return;
		}
		$count = 0;
		foreach( $ids as $id ) {
			if ( $this->bulk_individual_action( $action, absint( $id ) ) ) {
				$count++;
			}
		}
		$message = '';
		if ( $count ) {
			delete_user_meta( get_current_user_id(), '_fep_user_message_count' );
			switch ( $action ) {
				case 'delete':
					$message = sprintf( _n( '%s message successfully deleted.', '%s messages successfully deleted.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
					break;
				case 'mark-as-read':
					$message = sprintf( _n( '%s message successfully marked as read.', '%s messages successfully marked as read.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
					break;
				case 'mark-as-unread':
					$message = sprintf( _n( '%s message successfully marked as unread.', '%s messages successfully marked as unread.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
					break;
				case 'archive':
					$message = sprintf( _n( '%s message successfully archived.', '%s messages successfully archived.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
					break;
				case 'restore':
					$message = sprintf( _n( '%s message successfully restored.', '%s messages successfully restored.', $count, 'front-end-pm' ), number_format_i18n( $count ) );
					break;
			}
			//$message = '<div class="fep-success">' . $message . '</div>';
		}
		$message = apply_filters( 'fep_message_bulk_action_message', $message, $count );
		if ( $message ) {
			fep_success()->add( 'success', $message );
		}
	}

	function bulk_individual_action( $action, $id ) {
		$return = false;
		
		switch ( $action ) {
			case 'delete':
				$return = fep_delete_message( $id );
				break;
			case 'mark-as-read':
				if ( fep_current_user_can( 'view_message', $id ) ) {
					$return = fep_make_read( true, $id);
				}
				break;
			case 'mark-as-unread':
				if ( fep_current_user_can( 'view_message', $id ) ) {
					$return = FEP_Participants::init()->unmark( $id, get_current_user_id(), [ 'parent_read' => true ] );
				}
				break;
			case 'archive':
				if ( fep_current_user_can( 'view_message', $id ) ) {
					$return = FEP_Participants::init()->mark( $id, get_current_user_id(), ['archive' => true ] );
				}
				break;
			case 'restore':
				if ( fep_current_user_can( 'view_message', $id ) ) {
					$return = FEP_Participants::init()->unmark( $id, get_current_user_id(), [ 'archive' => true ] );
				}
				break;
			default:
				$return = apply_filters( 'fep_message_bulk_individual_action', false, $action, $id, $id ); //second $id for back-word compatability
				break;
		}
		return $return;
	}

	function get_table_bulk_actions() {
		$filter = ! empty( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '';
		$actions = array(
			'delete'		=> __( 'Delete', 'front-end-pm' ),
			'mark-as-read'	=> __( 'Mark as read', 'front-end-pm' ),
			'mark-as-unread'=> __( 'Mark as unread', 'front-end-pm' ),
		);
		if ( 'archive' == $filter ) {
			$actions['restore'] = __( 'Restore', 'front-end-pm' );
		} else {
			$actions['archive'] = __( 'Archive', 'front-end-pm' );
		}
		return apply_filters( 'fep_message_table_bulk_actions', $actions );
	}

	function get_table_filters() {
		$filters = array(
			'show-all'	=> __( 'Show all', 'front-end-pm' ),
			'inbox'		=> __( 'Inbox', 'front-end-pm ' ),
			'sent'		=> __( 'Sent', 'front-end-pm' ),
			'read'		=> __( 'Read', 'front-end-pm' ),
			'unread'	=> __( 'Unread', 'front-end-pm' ),
			'archive'	=> __( 'Archive', 'front-end-pm' ),
		);
		return apply_filters( 'fep_message_table_filters', $filters );
	}

	function get_table_columns() {
		$columns = array(
			'fep-cb'	=> __( 'Checkbox', 'front-end-pm' ),
			'avatar'	=> __( 'Avatar', 'front-end-pm' ),
			'author'	=> __( 'Author', 'front-end-pm' ),
			'title'		=> __( 'Title', 'front-end-pm' ),
		);
		return apply_filters( 'fep_message_table_columns', $columns );
	}

	function get_column_content( $column ) {
		switch ( $column ) {
			case has_action( "fep_message_table_column_content_{$column}" ):
				do_action( "fep_message_table_column_content_{$column}" );
				break;
			case 'fep-cb':
				?><input type="checkbox" class="fep-cb" name="fep-message-cb[]" value="<?php echo fep_get_the_id(); ?>" /><?php
				break;
			case 'avatar':
				if( $group = apply_filters( 'fep_is_group_message', false, fep_get_the_id() ) ){
					?><div class="fep-avatar-p fep-avatar-p-90"><?php
					echo '<div class="fep-avatar-group-60" title="' . esc_attr( $group ) . '"></div>';
					echo '</div>';
				} else {
					$participants = fep_get_participants( fep_get_the_id() );
					if ( apply_filters( 'fep_remove_own_avatar_from_messagebox', false )
						 && ( $key = array_search( get_current_user_id(), $participants ) ) !== false ) {
						unset( $participants[$key] );
					}
					$count = 1;
					?>
					<div class="fep-avatar-p <?php echo ( count( $participants ) > 2 ) ? 'fep-avatar-p-120': 'fep-avatar-p-90' ?>"><?php
					foreach( $participants as $p ) {
						if ( $count > 2 ) {
							echo '<div class="fep-avatar-more-60" title="' . __( 'More users', 'front-end-pm' ) . '"></div>';
							break;
						} 
						?><div class="fep-avatar-<?php echo $count; ?>"><?php echo get_avatar( $p, 60, '', strip_tags( fep_user_name( $p ) ), array( 'extra_attr'=> 'title="' . esc_attr( strip_tags( fep_user_name( $p ) ) ) . '"' ) ); ?></div><?php
						$count++;
					}
					echo '</div>';
				}
				break;
			case 'author':
				if( 'threaded' === fep_get_message_view() ){
					?><span class="fep-message-author"><?php echo fep_user_name( fep_get_message_field( 'mgs_last_reply_by' ) ); ?></span><span class="fep-message-date"><?php echo fep_get_the_date( 'mgs_last_reply_time' ); ?></span><?php
				} else {
					?><span class="fep-message-author"><?php echo fep_user_name( fep_get_message_field( 'mgs_author' ) ); ?></span><span class="fep-message-date"><?php echo fep_get_the_date( 'created' ); ?></span><?php
				}
				break;
			case 'title':
				if ( ! fep_is_read( true ) ) {
					$span = '<span class="fep-unread-classp"><span class="fep-unread-class">' . __( 'Unread', 'front-end-pm' ) . '</span></span>';
					$class = ' fep-strong';
				} else {
					$span = '';
					$class = '';
				} 
				?><span class="<?php echo $class; ?>"><a href="<?php echo fep_query_url( 'viewmessage', [
					'fep_id' => fep_get_the_id(),
					'feppage' => isset( $_GET['feppage'] ) ? $_GET['feppage'] : 1,
					'fep-filter' => isset( $_GET['fep-filter'] ) ? $_GET['fep-filter'] : '',
					] ); ?>"><?php echo fep_get_the_title(); ?></a></span><?php echo $span; ?>
				<div class="fep-message-excerpt">
					<?php echo fep_get_the_excerpt(); ?>
				</div><?php
				break;
			default:
				do_action( 'fep_message_table_column_content', $column );
				break;
		}
	}
} //END CLASS

add_action( 'wp_loaded', array( Fep_Messages::init(), 'actions_filters' ) );
