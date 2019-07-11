<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Admin_Pages {
	private static $instance;
	private $priority = 0;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		add_action( 'admin_menu', array( $this, 'addAdminPage' ) );
		add_action( 'admin_init', array( $this, 'admin_actions' ) );
		add_action( 'admin_post_fep-edit', array( $this, 'edit_action' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_data_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_data_eraser' ) );
	}

	function addAdminPage() {
		$admin_cap = apply_filters( 'fep_admin_cap', 'manage_options' );
		$label = fep_is_pro() ? 'Front End PM PRO' : 'Front End PM';
		
		add_menu_page( $label, $label, $admin_cap, 'fep-all-messages', array( $this, 'all_messages' ), 'dashicons-email', 30 );
		
		add_submenu_page( 'fep-all-messages', "$label - " . __( 'All Messages', 'front-end-pm' ), __( 'All Messages', 'front-end-pm' ), $admin_cap, 'fep-all-messages', array( $this, 'all_messages' ) );
		
		add_submenu_page( 'fep-all-messages', "$label - " . __( 'All Announcements', 'front-end-pm' ), __( 'All Announcements', 'front-end-pm' ), $admin_cap, 'fep-all-announcements', array( $this, 'all_announcements' ) );
		
		add_submenu_page( 'fep-all-messages', "$label - " . __( 'All Attachments', 'front-end-pm' ), __( 'All Attachments', 'front-end-pm' ), $admin_cap, 'fep-all-attachments', array( $this, 'all_attachments' ) );
		
		add_submenu_page( 'fep-non-exist-menu', "$label - " . __( 'Edit', 'front-end-pm' ), __( 'Edit', 'front-end-pm' ), $admin_cap, 'fep-edit', array( $this, 'edit' ) );
		
	}
	
	function all_messages(){
		$table = new FEP_WP_List_Table( 'message' );
		$table->prepare_items(); ?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php
			esc_html_e( __( 'Messages', 'front-end-pm') );
			?></h1>
			<?php if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
				/* translators: %s: search keywords */
				printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $_REQUEST['s'] ) );
			} ?>
			<hr class="wp-header-end">
			<div class="fep-admin-messages-table">
				<?php $this->notifications( __('message', 'front-end-pm') ); ?>
				<?php $table->views(); ?>
				<form id="fep-admin-messages-table-form" method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
					<?php $table->search_box( __( 'Search', 'front-end-pm' ), 'fep-message' ); ?>
					<?php $table->display(); ?>
					<?php add_thickbox(); ?>
				</form>
			</div>
			<br class="clear" />
		</div>
		<?php 
	}
	
	function all_announcements(){
		$table = new FEP_WP_List_Table( 'announcement' );
		$table->prepare_items(); ?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php
			esc_html_e( __( 'Announcements', 'front-end-pm') );
			?></h1>
			<?php if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
				/* translators: %s: search keywords */
				printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $_REQUEST['s'] ) );
			} ?>
			<hr class="wp-header-end">
			<div class="fep-admin-announcements-table">
				<?php $this->notifications( __('announcement', 'front-end-pm') ); ?>
				<?php $table->views(); ?>
				<form id="fep-admin-announcements-table-form" method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
					<?php $table->search_box( __( 'Search', 'front-end-pm' ), 'fep-announcement' ); ?>
					<?php $table->display(); ?>
					<?php add_thickbox(); ?>
				</form>
			</div>
			<br class="clear" />
		</div>
		<?php 
	}
	
	function all_attachments(){
		$table = new FEP_Attachments_List_Table();
		$table->prepare_items(); ?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php
			esc_html_e( __( 'Attachments', 'front-end-pm') );
			?></h1>
			<?php if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
				/* translators: %s: search keywords */
				printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $_REQUEST['s'] ) );
			} ?>
			<hr class="wp-header-end">
			<div class="fep-admin-attachments-table">
				<?php $this->notifications( __('attachment', 'front-end-pm') ); ?>
				<?php $table->views(); ?>
				<form id="fep-admin-attachments-table-form" method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
					<?php $table->search_box( __( 'Search', 'front-end-pm' ), 'fep-attachments' ); ?>
					<?php $table->display(); ?>
					<?php add_thickbox(); ?>
				</form>
			</div>
			<br class="clear" />
		</div>
		<?php
	}
	
	function edit() {
		$fep_id = isset( $_REQUEST['fep_id'] ) ? (int) $_REQUEST['fep_id'] : 0;
		if( ! $fep_id || ! fep_is_user_admin() || ! ( $message = fep_get_message( $fep_id ) ) ) {
			wp_die( __( 'Invalid Request', 'front-end-pm' ) );
		}
		wp_enqueue_media();
		
		require fep_locate_template( 'admin-edit-message-announcement.php' );
	}
	
	function edit_action() {
		$fep_id = isset( $_POST['fep_id'] ) ? (int) $_POST['fep_id'] : 0;
		if ( ! $fep_id || ! fep_is_user_admin() || ! ( $message = fep_get_message( $fep_id ) ) ) {
			wp_die( __( 'Invalid Request', 'front-end-pm' ) );
		}
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], "fep-edit-{$fep_id}" ) ) {
			wp_die( __( 'Invalid nonce', 'front-end-pm' ) );
		}
		$args = [
			'mgs_title' => isset( $_POST['mgs_title'] ) ? $_POST['mgs_title'] : '',
			'mgs_content' => isset( $_POST['mgs_content'] ) ? $_POST['mgs_content'] : '',
			'mgs_status' => isset( $_POST['mgs_status'] ) ? $_POST['mgs_status'] : '',
		];
		$args = wp_unslash( $args );

		$att_ids = isset( $_POST['att_id'] ) ? $_POST['att_id'] : '';
		$attchments = [];
		
		if ( is_array( $att_ids ) ) {
			foreach ( $att_ids as $att_id ) {
				if ( 'attachment' === get_post_type( $att_id ) ) {
					$attchments[] = [
						'att_mime'   => get_post_mime_type( $att_id ),
						'att_file'   => get_attached_file( $att_id ),
						'att_status' => 'publish',
					];
				}
			}
		}
		$message->update( $args );
		$message->insert_attachments( $attchments );
		if ( 'announcement' === $message->mgs_type ) {
			$redirect_url = add_query_arg( [
				'page'    => 'fep-all-announcements',
				'updated' => 1,
			], admin_url( 'admin.php' ) );
		} else {
			$redirect_url = add_query_arg( [
				'page'    => 'fep-all-messages',
				'updated' => 1,
			], admin_url( 'admin.php' ) );
		}
		wp_safe_redirect( $redirect_url );
	}
	
	function admin_actions(){
		if( ! isset( $_REQUEST['page'] ) ){
			return false;
		}
		$type = '';
		if ( 'fep-all-messages' === $_REQUEST['page'] ) {
			$type = 'message';
		} elseif ( 'fep-all-announcements' === $_REQUEST['page'] ) {
			$type = 'announcement';
		} elseif ( 'fep-all-attachments' === $_REQUEST['page'] ) {
			$type = 'attachment';
		}
		if ( ! $type ) {
			return false;
		}
		
		if ( ! empty( $_REQUEST['filter_action'] ) ){
			return false;
		}
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ){
			$action = $_REQUEST['action'];
		} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ){
			$action = $_REQUEST['action2'];
		}
		if( empty( $action ) ){
			return false;
		}

		$sendback = add_query_arg( 'page', $_REQUEST['page'], admin_url( 'admin.php' ) );
		
		if( 'fep-all-attachments' == $_REQUEST['page'] ){
			switch ( $action ) {
				case 'delete':
					$id = isset( $_GET['fep_id'] ) ? absint( $_GET['fep_id'] ) : 0;
					$mgs_id = isset( $_GET['fep_parent_id'] ) ? absint( $_GET['fep_parent_id'] ) : 0;
					
					if( ! $id || ! $mgs_id || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete-fep-attachment-' . $id ) || ! fep_is_user_admin() ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					
					if( ! FEP_Attachments::init()->delete( $mgs_id, $id ) ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					$sendback = add_query_arg('deleted', 1, $sendback);
					break;
				case 'bulk_delete':
					$ids = isset( $_GET['fep_id'] ) ? $_GET['fep_id'] : [];
					if( ! $ids || ! is_array( $ids ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-fep-attachments' ) || ! fep_is_user_admin() ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					$deleted = 0;
					foreach( $ids as $mgs_id => $att_ids ){
						if( ! is_array( $att_ids ) ){
							continue;
						}
						foreach( $att_ids as $att_id ){
							if( ! $att_id || ! is_numeric( $att_id ) ){
								continue;
							}
							if( FEP_Attachments::init()->delete( $mgs_id, $att_id ) ){
								$deleted++;
							}
						}					
					}
					
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;
				case ( 0 === strpos( $action, 'bulk_status-change-to-' ) ):
					$status = str_replace( 'bulk_status-change-to-', '', $action );
					$ids = isset( $_GET['fep_id'] ) ? $_GET['fep_id'] : [];
					if( ! array_key_exists( $status, fep_get_statuses( $type ) ) || ! $ids || ! is_array( $ids ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-fep-attachments' ) || ! fep_is_user_admin() ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					$changed = 0;
					foreach( $ids as $mgs_id => $att_ids ){
						if( ! is_array( $att_ids ) ){
							continue;
						}
						foreach( $att_ids as $att_id ){
							if( ! $att_id || ! is_numeric( $att_id ) ){
								continue;
							}
							if( FEP_Attachments::init()->update( $mgs_id, [ 'att_status' => $status ], $att_id ) ){
								$changed++;
							}
						}					
					}
					
					$sendback = add_query_arg( 'changed', $changed, $sendback );
					break;
				default:
					// code...
					break;
			}
		} else {
			switch ( $action ) {
				case 'view':
					$this->view_message_announcement();
					break;
				case 'delete':
					$id = isset( $_GET['fep_id'] ) ? absint( $_GET['fep_id'] ) : 0;
					if( ! $id || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete-fep-message-' . $id ) || ! fep_is_user_admin() ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					$args = [
						'mgs_id' => $id,
						'per_page' => 0, //unlimited
						'mgs_status' => 'any',
						'mgs_type' => ( 'fep-all-announcements' == $_REQUEST['page'] ) ? 'announcement' : 'message',
					];
					if( 'threaded' == fep_get_message_view() && apply_filters( 'fep_erase_replies_if_threaded', true ) ){
						$args['include_child'] = true;
					}
					$messages = fep_get_messages( $args );
					$deleted = 0;
					foreach( $messages as $message ){
						if( $message->delete() ){
							$deleted++;
						}
					}
					$sendback = add_query_arg('deleted', $deleted, $sendback);
					break;
				case 'bulk_delete':
					$ids = isset( $_GET['fep_id'] ) ? $_GET['fep_id'] : [];
					if( ! $ids || ! is_array( $ids ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-fep-messages' ) || ! fep_is_user_admin() ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					$args = [
						'mgs_id_in' => $ids,
						'per_page' => 0, //unlimited
						'mgs_status' => 'any',
						'mgs_type' => ( 'fep-all-announcements' == $_REQUEST['page'] ) ? 'announcement' : 'message',
					];
					if( 'threaded' == fep_get_message_view() && apply_filters( 'fep_erase_replies_if_threaded', true ) ){
						$args['include_child'] = true;
					}
					$messages = fep_get_messages( $args );
					$deleted = 0;
					foreach( $messages as $message ){
						if( $message->delete() ){
							$deleted++;
						}
					}
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;
				case ( 0 === strpos( $action, 'bulk_status-change-to-' ) ):
					$status = str_replace( 'bulk_status-change-to-', '', $action );
					$ids = isset( $_GET['fep_id'] ) ? $_GET['fep_id'] : [];
					if( ! array_key_exists( $status, fep_get_statuses( $type ) ) || ! $ids || ! is_array( $ids ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-fep-messages' ) || ! fep_is_user_admin() ){
						wp_die( __( 'Invalid request!', 'front-end-pm' ) );
					}
					$changed = 0;
					foreach( $ids as $id ){
						$message = fep_get_message( $id );
						
						if( $message && $message->update( [ 'mgs_status' => $status ] ) ){
							$changed++;
						}
					}
					
					$sendback = add_query_arg( 'changed', $changed, $sendback );
					break;
				default:
					// code...
					break;
			}
		}
		
		wp_safe_redirect( $sendback );
		exit();
	}
	
	function notifications( $type ){
		$counts = array(
			'updated'   => isset( $_REQUEST['updated'] )   ? absint( $_REQUEST['updated'] )   : 0,
			'deleted'   => isset( $_REQUEST['deleted'] )   ? absint( $_REQUEST['deleted'] )   : 0,
			'changed'   => isset( $_REQUEST['changed'] )   ? absint( $_REQUEST['changed'] )   : 0,
		);
		
		$messages = array(
			'updated'   => _n( '%1$s %2$s updated.', '%1$s %2$ss updated.', $counts['updated'], 'front-end-pm' ),
			'deleted'   => _n( '%1$s %2$s permanently deleted.', '%1$s %2$ss permanently deleted.', $counts['deleted'], 'front-end-pm' ),
			'changed'   => _n( '%1$s %2$s status changed.', '%1$s %2$ss status changed.', $counts['changed'], 'front-end-pm' ),
		);
		
		$counts = array_filter( $counts );
		
		$mgs = array();
		foreach ( $counts as $message => $count ) {
			if ( isset( $messages[ $message ] ) ){
				$mgs[] = sprintf( $messages[ $message ], number_format_i18n( $count ), $type );
			}
		}

		if ( $mgs ){
			echo '<div id="message" class="updated notice is-dismissible"><p>' . join( ' ', $mgs ) . '</p></div>';
		}
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'deleted', 'changed' ), $_SERVER['REQUEST_URI'] );		
	}
	
	function view_message_announcement(){
		$id = isset( $_GET['fep_id'] ) ? absint( $_GET['fep_id'] ) : 0;
		$message = fep_get_message( $id );
		if( ! $message ){
			wp_die( __( 'You do not have permission to view this message!', 'front-end-pm' ) );
		}
		fep_set_current_message( $message );
		
		$type = fep_get_message_field( 'mgs_type' );
		if( 'message' == $type && ! fep_current_user_can( 'view_message', $id ) ){
			wp_die( __( 'You do not have permission to view this message!', 'front-end-pm' ) );
		} elseif( 'announcement' == $type && ! fep_current_user_can( 'view_announcement', $id ) ){
			wp_die( __( 'You do not have permission to view this announcement!', 'front-end-pm' ) );
		}

		require( fep_locate_template( 'admin-view-message-announcement.php' ) );
		exit;
	}
	
	function register_data_exporter( $exporters ){
		$exporters['front-end-pm-messages'] = array(
			'exporter_friendly_name' => sprintf( __( '%s Messages', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ),
			'callback'               => array( $this, 'data_exporter_messages' ),
		);
		$exporters['front-end-pm-announcements'] = array(
			'exporter_friendly_name' => sprintf( __( '%s Announcements', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ),
			'callback'               => array( $this, 'data_exporter_announcements' ),
		);
		return $exporters;
	}
	
	function data_exporter_messages( $email_address, $page = 1 ) {
		$user_id = (int) fep_get_userdata( $email_address, 'ID', 'email' );
		if ( ! $user_id || ! fep_get_option( 'export_messages', 1 ) ) {
			return array(
				'data' => [],
				'done' => true,
			);
		}
		$args = array(
			'mgs_type'   => 'message',
			'paged'      => (int) $page,
			'per_page'   => 100,
			'mgs_status' => 'any',
			'mgs_author' => $user_id,
		);
		$messages     = fep_get_messages( $args );
		$export_items = array();

		if ( $messages ) {
			foreach ( $messages as $message ) {
				$att_urls = [];
				if ( $attachments = $message->get_attachments( false, 'any' ) ) {
					foreach ( $attachments as $attachment ) {
						$att_urls[] = apply_filters( 'fep_filter_attachment_download_link', '<a href="' .
							fep_query_url( 'download', array(
								'fep_id'        => $attachment->att_id,
								'fep_parent_id' => $attachment->mgs_id,
							) )
						. '">' . esc_html( basename( $attachment->att_file ) ) . '</a>', $attachment->att_id );
					}
				}

				$data = array(
					array(
						'name'  => __( 'Date', 'front-end-pm' ),
						'value' => mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), get_date_from_gmt( $message->mgs_created ) ),
					),
					array(
						'name'  => __( 'Subject', 'front-end-pm' ),
						'value' => $message->mgs_title,
					),
					array(
						'name'  => __( 'Content', 'front-end-pm' ),
						'value' => $message->mgs_content,
					),
				);
				if ( $att_urls ) {
					$data[] = [
						'name'  => __( 'Attachments', 'front-end-pm' ),
						'value' => implode( '<br>', $att_urls ),
					];
				}

				$export_items[] = array(
					'group_id'    => 'fep_message',
					'group_label' => __( 'Messages', 'front-end-pm' ),
					'item_id'     => "fep_message-{$message->mgs_id}",
					'data'        => $data,
				);
			}
			$done = false;
		} else {
			$done = true;
		}
		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}
	
	function data_exporter_announcements( $email_address, $page = 1 ) {
		$user_id = (int) fep_get_userdata( $email_address, 'ID', 'email' );
		if ( ! $user_id || ! fep_get_option( 'export_announcements', 1 ) ) {
			return array(
				'data' => [],
				'done' => true,
			);
		}
		$args = array(
			'mgs_type'   => 'announcement',
			'paged'      => (int) $page,
			'per_page'   => 100,
			'mgs_status' => 'any',
			'mgs_author' => $user_id,
		);
		$announcements = fep_get_messages( $args );
		$export_items  = array();

		if ( $announcements ) {
			foreach ( $announcements as $announcement ) {
				$att_urls = [];
				if ( $attachments = $announcement->get_attachments( false, 'any' ) ) {
					foreach ( $attachments as $attachment ) {
						$att_urls[] = apply_filters( 'fep_filter_attachment_download_link', '<a href="' .
							fep_query_url( 'download', array(
								'fep_id'        => $attachment->att_id,
								'fep_parent_id' => $attachment->mgs_id,
							) )
						. '">' . esc_html( basename( $attachment->att_file ) ) . '</a>', $attachment->att_id );
					}
				}

				$data = array(
					array(
						'name'  => __( 'Date', 'front-end-pm' ),
						'value' => mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), get_date_from_gmt( $announcement->mgs_created ) ),
					),
					array(
						'name'  => __( 'Title', 'front-end-pm' ),
						'value' => $announcement->mgs_title,
					),
					array(
						'name'  => __( 'Content', 'front-end-pm' ),
						'value' => $announcement->mgs_content,
					),
				);
				if ( $att_urls ) {
					$data[] = [
						'name'  => __( 'Attachments', 'front-end-pm' ),
						'value' => implode( '<br>', $att_urls ),
					];
				}

				$export_items[] = array(
					'group_id'    => 'fep_announcement',
					'group_label' => __( 'Announcements', 'front-end-pm' ),
					'item_id'     => "fep_announcement-{$announcement->mgs_id}",
					'data'        => $data,
				);
			}
			$done = false;
		} else {
			$done = true;
		}
		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}
	
	function register_data_eraser( $erasers ) {
		$erasers['front-end-pm-messages'] = array(
			'eraser_friendly_name' => sprintf( __( '%s Messages', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ),
			'callback'             => array( $this, 'data_eraser_messages' ),
		);
		$erasers['front-end-pm-replies'] = array(
			'eraser_friendly_name' => sprintf( __( '%s Replies', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ),
			'callback'             => array( $this, 'data_eraser_replies' ),
		);
		$erasers['front-end-pm-announcements'] = array(
			'eraser_friendly_name' => sprintf( __( '%s Announcements', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ),
			'callback'             => array( $this, 'data_eraser_announcements' ),
		);
		return $erasers;
	}
	
	function data_eraser_messages( $email_address, $page = 1 ) {
		$user_id = (int) fep_get_userdata( $email_address, 'ID', 'email' );
		if ( ! $user_id || 'none' === fep_get_option( 'erase_messages', 'anonymize' ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$args = array(
			'mgs_type'   => 'message',
			'paged'      => (int) $page,
			'per_page'   => 25,
			'mgs_status' => 'any',
			'mgs_parent' => 0,
			'mgs_author' => $user_id,
		);
		$messages       = fep_get_messages( $args );
		$return_mgs     = array();
		$items_removed  = false;
		$items_retained = false;

		if ( $messages ) {
			foreach ( $messages as $message ) {
				if ( 'erase' === fep_get_option( 'erase_messages', 'anonymize' ) ) {
					if( 'threaded' == fep_get_message_view() && apply_filters( 'fep_erase_replies_if_threaded', true ) ){
						$args2 = [
							'mgs_id' => $message->mgs_id,
							'include_child' => true,
							'per_page' => 0, //unlimited
							'mgs_status' => 'any',
							'mgs_type' => 'message',
						];
						$messages2 = fep_get_messages( $args2 );
						foreach( $messages2 as $message2 ){
							$message2->delete();
						}
					} else {
						$message->delete();
					}
				} else {
					$update_args = array(
						'mgs_title'   => wp_privacy_anonymize_data( 'text', $message->mgs_title ),
						'mgs_content' => wp_privacy_anonymize_data( 'longtext', $message->mgs_content ),
					);
					$message->update( $update_args );
					FEP_Attachments::init()->delete( $message->mgs_id );
					fep_update_reply_info( $message->mgs_id );
				}
			}
			$items_removed = true;
			$done          = false;
		} else {
			$done = true;
		}
		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $return_mgs,
			'done'           => $done,
		);
	}
	
	function data_eraser_replies( $email_address, $page = 1 ) {
		$user_id = (int) fep_get_userdata( $email_address, 'ID', 'email' );
		if ( ! $user_id || 'none' === fep_get_option( 'erase_replies', 'erase' ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$args = array(
			'mgs_type'   => 'message',
			'paged'      => (int) $page,
			'per_page'   => 50,
			'mgs_status' => 'any',
			'mgs_parent_not_in' => array( 0 ),
			'mgs_author' => $user_id,
		);
		$messages       = fep_get_messages( $args );
		$return_mgs     = array();
		$items_removed  = false;
		$items_retained = false;

		if ( $messages ) {
			foreach ( $messages as $message ) {
				$parent = $message->mgs_parent;
				
				if ( 'erase' === fep_get_option( 'erase_replies', 'erase' ) ) {
					$message->delete();
				} else {
					$update_args = array(
						'mgs_title'   => wp_privacy_anonymize_data( 'text', $message->mgs_title ),
						'mgs_content' => wp_privacy_anonymize_data( 'longtext', $message->mgs_content ),
					);
					$message->update( $update_args );
					FEP_Attachments::init()->delete( $message->mgs_id );
				}
				fep_update_reply_info( $parent );
			}
			$items_removed = true;
			$done          = false;
		} else {
			$done = true;
		}
		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $return_mgs,
			'done'           => $done,
		);
	}
	
	function data_eraser_announcements( $email_address, $page = 1 ) {
		$user_id = (int) fep_get_userdata( $email_address, 'ID', 'email' );
		if ( ! $user_id || 'none' === fep_get_option( 'erase_announcements', 'erase' ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$args = array(
			'mgs_type'   => 'announcement',
			'paged'      => (int) $page,
			'per_page'   => 50,
			'mgs_status' => 'any',
			'mgs_author' => $user_id,
		);
		$announcements  = fep_get_messages( $args );
		$messages = array();
		$items_removed  = false;
		$items_retained = false;

		if ( $announcements ) {
			foreach ( $announcements as $announcement ) {
				if ( 'erase' === fep_get_option( 'erase_announcements', 'erase' ) ) {
					$announcement->delete();
				} else {
					$update_args = array(
						'mgs_title'   => wp_privacy_anonymize_data( 'text', $announcement->mgs_title ),
						'mgs_content' => wp_privacy_anonymize_data( 'longtext', $announcement->mgs_content ),
					);
					$announcement->update( $update_args );
					FEP_Attachments::init()->delete( $announcement->mgs_id );
				}
			}
			$items_removed = true;
			$done = false;
		} else {
			$done = true;
		}
		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}
} //END CLASS
add_action( 'init', array( Fep_Admin_Pages::init(), 'actions_filters' ) );
