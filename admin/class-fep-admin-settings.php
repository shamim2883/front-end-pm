<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Admin_Settings {
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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'settings_output' ) );
		add_action( 'admin_notices', array( $this, 'notice_review' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FEP_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		add_action( 'add_option_FEP_admin_options', array( $this, 'after_option_save' ), 99 );
		add_action( 'update_option_FEP_admin_options', array( $this, 'after_option_save' ), 99 );
		add_action( 'fep_action_after_admin_options_save', array( $this, 'recalculate_user_message_count' ), 10, 2 );
		add_action( 'publish_page', array( $this, 'set_page_id' ), 10, 2 );
	}

	function addAdminPage() {
		$admin_cap = apply_filters( 'fep_admin_cap', 'manage_options' );
		
		add_submenu_page( 'fep-all-messages', 'Front End PM - ' . __( 'Settings', 'front-end-pm' ), __( 'Settings', 'front-end-pm' ), $admin_cap, 'fep_settings', array( $this, 'settings_page' ) );
	}

	function admin_enqueue_scripts() {
		if ( isset( $_GET['tab'] ) && 'appearance' == $_GET['tab'] ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}
		wp_enqueue_script( 'fep-admin', FEP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
		wp_localize_script( 'fep-admin', 'fep_admin',
			array(
				'token'       => wp_create_nonce( 'fep-admin' ),
				'delete'      => __( 'Delete', 'front-end-pm' ),
				'del_confirm' => __( 'Are you sure you want to delete this?', 'front-end-pm' ),
			)
		);
	}

	function recalculate_user_message_count( $old_value, $tab ) {
		global $wpdb;
		if ( isset( $old_value['message_view'] ) && fep_get_message_view() != $old_value['message_view'] ) {
			if ( 'threaded' != fep_get_message_view() ) {
				delete_metadata( 'user', 0, '_fep_user_message_count', '', true );
			} else {
				update_option( '_fep_message_view_changed', 1 );
				//delete_metadata( 'post', 0, '_fep_last_reply_by', '', true );
				$wpdb->update( FEP_MESSAGE_TABLE, [ 'mgs_last_reply_by' => 0 ], [ 'mgs_type' => 'message', 'mgs_parent' => 0 ], ['%d'], [ '%s', '%d'] );
			}
		}
	}

	function after_option_save( $old_value ) {
		global $wp_settings_sections;
		if ( ! is_array( $old_value ) ) {
			$old_value = array();
		}
		$tab = 'general';
		$is_settings_page = false;
		if ( ! empty( $_POST['_wp_http_referer'] ) ) {
			wp_parse_str( parse_url( $_POST['_wp_http_referer'], PHP_URL_QUERY ), $referrer );
			$tab = ! empty( $referrer['tab'] ) ? $referrer['tab'] : 'general';
			if ( isset( $referrer['page'] ) && 'fep_settings' == $referrer['page'] && ! empty( $wp_settings_sections['fep_settings_' . $tab ] ) ) {
				$is_settings_page = true;
			}
		}
		do_action( 'fep_action_after_admin_options_save', $old_value, $tab, $is_settings_page );
	}

	public function form_fields( $section = 'general' ) {
		$user_role = array();
		foreach ( get_editable_roles() as $key => $role ) {
			$user_role[ $key ] = $role['name'];
		}
		$pages = array( '' => __( 'Select Page', 'front-end-pm' ) );
		foreach ( get_pages( array( 'hierarchical' => 0, 'number' => 100 ) ) as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}
		$page_id = fep_get_option( 'page_id', 0 );
		if ( $page_id && ! isset( $pages[ $page_id ] ) && get_post( $page_id ) ) {
			$pages[ $page_id ] = get_the_title( $page_id );
		}
		$fields = array(
			//General Settings
			'page_id' => array(
				'type'		 => 'select',
				'value'		 => $page_id,
				'priority'	 => 2,
				'label'		 => __( 'Front End PM Page', 'front-end-pm' ),
				'options'	 => $pages,
				'description'=> sprintf( __( 'The page must have %s shortcode in content.', 'front-end-pm' ), '<code>[front-end-pm]</code>' ),
			),
			'message_view' => array(
				'type'		 => 'select',
				'value'		 => fep_get_message_view(),
				'priority'	 => 3,
				//'section'	 => 'message',
				'label' =>	 __( 'Message view', 'front-end-pm' ),
				'description'=> ( 'threaded' == fep_get_message_view() ) ? '' : __( 'This setting change will redirect you to update page for database update.', 'front-end-pm' ),
				'options'	 => array(
					'threaded'	=> __( 'Threaded', 'front-end-pm' ),
					'individual'=> __( 'Individual', 'front-end-pm' ),
				),
			),
			'messages_page'	 => array(
				'type'		 => 'number',
				'value'		 => fep_get_option( 'messages_page', 15 ),
				'priority'	 => 4,
				'label'		 => __( 'Messages per page', 'front-end-pm' ),
				'description'=> __( 'Messages to show per page.', 'front-end-pm' ),
			),
			'announcements_page' => array(
				'type'		 => 'number',
				'value'		 => fep_get_option( 'announcements_page', 15 ),
				'priority'	 => 5,
				'label'		 => __( 'Announcements per page', 'front-end-pm' ),
				'description'=> __( 'Announcements to show per page.', 'front-end-pm' ),
			),
			'user_page'	=> array(
				'type'		 => 'number',
				'value'		 => fep_get_option( 'user_page', 50 ),
				'priority'	 => 6,
				'label'		 => __( 'Maximum users per page in directory', 'front-end-pm' ),
				'description'=> __( 'Maximum users per page in directory.', 'front-end-pm' ),
			),
			'time_delay' => array(
				'type'		 => 'number',
				'value'		 => fep_get_option( 'time_delay', 5 ),
				'priority'	 => 8,
				'label'		 => __( 'Time delay', 'front-end-pm' ),
				'description'=> __( 'Time delay between two sent messages by a user in minutes (0 = no delay required).', 'front-end-pm' ),
			),
			'editor_type' => array(
				'type'		=> 'select',
				'value'		=> fep_get_option( 'editor_type', 'wp_editor' ),
				'priority'	=> 12,
				'label'		=> __( 'Editor Type', 'front-end-pm' ),
				//'description'=> __( 'Admin alwayes have WP Editor.', 'front-end-pm' ),
				'options'	=> array(
					'wp_editor'	=> __( 'WP Editor', 'front-end-pm' ),
					'teeny'		=> __( 'WP Editor (Teeny)', 'front-end-pm' ),
					'textarea'	=> __( 'Textarea', 'front-end-pm' ),
				),
			),
			'parent_post_status' => array(
				'type'		 => 'select',
				'value'		 => fep_get_option( 'parent_post_status', 'publish' ),
				'priority'	 => 14,
				'label'		 => __( 'Parent Message Status', 'front-end-pm' ),
				'description'=> __( 'Parent Message status when sent from front end.', 'front-end-pm' ),
				'options'	 => fep_get_statuses( 'message' ),
			),
			'reply_post_status'	=> array(
				'type'		 => 'select',
				'value'		 => fep_get_option( 'reply_post_status', 'publish' ),
				'priority'	 => 16,
				'label'		 => __( 'Reply Message Status', 'front-end-pm' ),
				'description'=> __( 'Reply Message status when sent from front end.', 'front-end-pm' ),
				'options'	 => fep_get_statuses( 'message' ),
			),
			'att_status'	=> array(
				'type'		 => 'select',
				'value'		 => fep_get_option( 'att_status', 'publish' ),
				'priority'	 => 16,
				'label'		 => __( 'Attachment Default Status', 'front-end-pm' ),
				'description'=> __( 'Only publish attachments will show to users', 'front-end-pm' ),
				'options'	 => fep_get_statuses( 'attachment' ),
			),
			'allow_attachment' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'allow_attachment', 1 ),
				'priority'	=> 18,
				'class'		=> 'fep_toggle_next_tr fep_toggle_next_tr-2',
				'label'		=> __( 'Allow Attachment', 'front-end-pm' ),
				'cb_label'	=> __( 'Allow to attach attachment with message?', 'front-end-pm' ),
			),
			'attachment_size' => array(
				'type'		 => 'text',
				'value'		 => fep_get_option( 'attachment_size', '4MB' ),
				'priority'	 => 20,
				'label'		 => __( 'Maximum size of attachment', 'front-end-pm' ),
				'description'=> __( 'Use KB, MB or GB (eg. 4MB).', 'front-end-pm' ),
			),
			'attachment_no'	=> array(
				'type'		 => 'number',
				'value'		 => fep_get_option( 'attachment_no', '4' ),
				'priority'	 => 22,
				'label'		 => __( 'Maximum attachments', 'front-end-pm' ),
				'description'=> __( 'Maximum Number of attachments a user can add with message.', 'front-end-pm' ),
			),
			'show_directory' => array(
				'type'		 => 'checkbox',
				'value'		 => fep_get_option( 'show_directory', 1 ),
				'priority'	 => 24,
				'class'		 => '',
				'label'		 => __( 'Show Directory', 'front-end-pm' ),
				'cb_label'	 => __( 'Show Directory in front end?', 'front-end-pm' ),
				'description'=> __( 'Always shown to Admins.', 'front-end-pm' ),
			),
			'show_branding'	=> array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'show_branding', 1 ),
				'priority'	=> 30,
				'class'		=> '',
				'label'		=> __( 'Show Branding Footer', 'front-end-pm' ),
			),
			'delete_data_on_uninstall' => array(
				'type'		 => 'checkbox',
				'value'		 => fep_get_option( 'delete_data_on_uninstall', false ),
				'priority'	 => 35,
				'class'		 => '',
				'label'		 => __( 'Remove Data on Uninstall?', 'front-end-pm' ),
				'description'=> '<div style="color:red">' . sprintf( __( 'Check this box if you would like %s to completely remove all of its data when the plugin is deleted.', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ) . '</div>',
			),
			'load_css' => array(
				'type'		 => 'select',
				'section'	 => 'appearance',
				'value'		 => fep_get_option( 'load_css', 'only_in_message_page' ),
				'priority'	 => 2,
				'label'		 => __( 'Load CSS file', 'front-end-pm' ),
				'description'=> __( 'Select when you want to load CSS file of this plugin.', 'front-end-pm' ),
				'options'	=> array(
					'always'				=> __( 'Always', 'front-end-pm' ),
					'only_in_message_page'	=> __( 'Only in message page', 'front-end-pm' ),
					'never'					=> __( 'Never', 'front-end-pm' ),
				),
			),
			'custom_css' => array(
				'type'		 => 'textarea',
				'section'	=> 'appearance',
				'value'		 => fep_get_option( 'custom_css' ),
				'priority'	 => 3,
				'label'		 => __( 'Custom CSS', 'front-end-pm' ),
				'description'=> __( 'Add or override.', 'front-end-pm' ),
			),
			'bg_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'bg_color', '#ffffff' ),
				'default_value' => '#ffffff',
				'priority'		=> 5,
				'label'			=> __( 'Background Color', 'front-end-pm' ),
			),
			'text_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'text_color', '#000000' ),
				'default_value' => '#000000',
				'priority'		=> 10,
				'label'			=> __( 'Text Color', 'front-end-pm' ),
			),
			'link_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'link_color', '#000080' ),
				'default_value'	=> '#000080',
				'priority'		=> 20,
				'label'			=> __( 'Link Color', 'front-end-pm' ),
			),
			'btn_bg_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'btn_bg_color', '#F0FCFF' ),
				'default_value'	=> '#F0FCFF',
				'priority'		=> 25,
				'label'			=> __( 'Button Color', 'front-end-pm' ),
			),
			'btn_text_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'btn_text_color', '#000000' ),
				'default_value'	=> '#000000',
				'priority'		=> 30,
				'label'			=> __( 'Button Text Color', 'front-end-pm' ),
			),
			'active_btn_bg_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'active_btn_bg_color', '#D3EEF5' ),
				'default_value' => '#D3EEF5',
				'priority'		=> 35,
				'label'			=> __( 'Active Button Color', 'front-end-pm' ),
			),
			'active_btn_text_color' => array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'active_btn_text_color', '#000000' ),
				'default_value'	=> '#000000',
				'priority'		=> 40,
				'label'			=> __( 'Active Button Text Color', 'front-end-pm' ),
			),
			'odd_color'	=> array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'odd_color', '#F2F7FC' ),
				'default_value'	=> '#F2F7FC',
				'priority'		=> 45,
				'label'			=> __( 'Odd Messages Color', 'front-end-pm' ),
			),
			'even_color'	=> array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'even_color', '#FAFAFA' ),
				'default_value'	=> '#FAFAFA',
				'priority'		=> 50,
				'label'			=> __( 'Even Messages Color', 'front-end-pm' ),
			),
			'mgs_heading_color'	=> array(
				'type'			=> 'color_picker',
				//'class'		=> 'fep-color-picker',
				'section'		=> 'appearance',
				'value'			=> fep_get_option( 'mgs_heading_color', '#F2F7FC' ),
				'default_value'	=> '#F2F7FC',
				'priority'		=> 50,
				'label'			=> __( 'Messages Heading Color', 'front-end-pm' ),
			),
			//Recipient Settings
			'show_autosuggest' => array(
				'type'		 => 'checkbox',
				'value'		 => fep_get_option( 'show_autosuggest', 1 ),
				'priority'	 => 5,
				'section'	 => 'recipient',
				'class'		 => '',
				'label'		 => __( 'Show Autosuggestion', 'front-end-pm' ),
				'cb_label'	 => __( 'Show Autosuggestion when typing recipient name?', 'front-end-pm' ),
				'description'=> __( 'Always shown to Admins.', 'front-end-pm' ),
			),
			//Message
			//Announcement
			// Privacy
			'export_messages' => array(
				'type'		 => 'checkbox',
				'value'		 => fep_get_option( 'export_messages', 1 ),
				'priority'	 => 5,
				'section'	 => 'privacy_export',
				'class'		 => '',
				'label'		 => __( 'Export Messages', 'front-end-pm' ),
			),
			'export_announcements' => array(
				'type'		 => 'checkbox',
				'value'		 => fep_get_option( 'export_announcements', 1 ),
				'priority'	 => 5,
				'section'	 => 'privacy_export',
				'class'		 => '',
				'label'		 => __( 'Export Announcements', 'front-end-pm' ),
			),
			'erase_messages' => array(
				'type'		 => 'select',
				'value'		 => fep_get_option( 'erase_messages', 'anonymize' ),
				'priority'	 => 10,
				'section'	 => 'privacy_erase',
				'label'		 => __( 'Erase Messages', 'front-end-pm' ),
				'description' => __( 'If set Erase and message view as threaded, then all replies of that message will also be erased regardless of sender and erase replies setup.', 'front-end-pm' ),
				'options'	=> array(
					'none'      => __( 'No Action', 'front-end-pm' ),
					'erase'     => __( 'Erase', 'front-end-pm' ),
					'anonymize' => __( 'Anonymize', 'front-end-pm' ),
				),
			),
			'erase_replies' => array(
				'type'		 => 'select',
				'value'		 => fep_get_option( 'erase_replies', 'erase' ),
				'priority'	 => 15,
				'section'	 => 'privacy_erase',
				'label'		 => __( 'Erase Replies', 'front-end-pm' ),
				'options'	=> array(
					'none'      => __( 'No Action', 'front-end-pm' ),
					'erase'     => __( 'Erase', 'front-end-pm' ),
					'anonymize' => __( 'Anonymize', 'front-end-pm' ),
				),
			),
			'erase_announcements' => array(
				'type'		 => 'select',
				'value'		 => fep_get_option( 'erase_announcements', 'erase' ),
				'priority'	 => 20,
				'section'	 => 'privacy_erase',
				'label'		 => __( 'Erase Announcements', 'front-end-pm' ),
				'options'	=> array(
					'none'      => __( 'No Action', 'front-end-pm' ),
					'erase'     => __( 'Erase', 'front-end-pm' ),
					'anonymize' => __( 'Anonymize', 'front-end-pm' ),
				),
			),
			//Email Settings
			'email_content_type' => array(
				'type'		=> 'select',
				'value'		=> fep_get_option( 'email_content_type', 'plain_text' ),
				'priority'	=> 5,
				'section'	=> 'emails',
				'label'		=> __( 'Email Content Type', 'front-end-pm' ),
				'options'	=> array(
					'html'		=> __( 'HTML', 'front-end-pm' ),
					'plain_text'=> __( 'Plain Text', 'front-end-pm' ),
				),
			),
			'from_name' => array(
				'type'		 => 'text',
				'value'		 => fep_get_option( 'from_name', get_bloginfo( 'name' ) ),
				'priority'	 => 10,
				'section'	 => 'emails',
				'label'		 => __( 'From Name', 'front-end-pm' ),
				'description'=> __( 'All sent emails by Front End PM plugin will have this name as sender.', 'front-end-pm' ),
			),
			'from_email' => array(
				'type'		 => 'email',
				'value'		 => fep_get_option( 'from_email', get_bloginfo( 'admin_email' ) ),
				'priority'	 => 15,
				'section'	 => 'emails',
				'label'		 => __( 'From Email', 'front-end-pm' ),
				'description'=> __( 'All sent emails by Front End PM plugin will have this email address as sender.', 'front-end-pm' )
			),
			'notify_ann' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'notify_ann', '1' ),
				'priority'	=> 20,
				'class'		=> '',
				'section'	=> 'emails',
				'label'		=> __( 'Send email?', 'front-end-pm' ),
				'cb_label'	=> __( 'Send email to all users of selected role(s) when a new announcement is published?', 'front-end-pm' ),
			),
			'ann_to' => array(
				'type'		 => 'email',
				'value'		 => fep_get_option( 'ann_to', get_bloginfo( 'admin_email' ) ),
				'priority'	 => 25,
				'section'	 => 'emails',
				'label'		 => __( 'Valid email address for "to" field of announcement email', 'front-end-pm' ),
				'description'=> __( 'All users email will be in "Bcc" field.', 'front-end-pm' ),
			),
			//Security Settings
			'userrole_access' => array(
				'type'		 => 'checkbox',
				'value'		 => fep_get_option( 'userrole_access', array() ),
				'priority'	 => 5,
				'class'		 => '',
				'section'	 => 'security',
				'multiple'	 => true,
				'label'		 => __( 'Who can access message system?', 'front-end-pm' ),
				'options'	 => $user_role,
				'description'=> __( 'User must have access permission to send new message or reply.', 'front-end-pm' ),
			),
			'userrole_new_message' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'userrole_new_message', array() ),
				'priority'	=> 10,
				'class'		=> '',
				'section'	=> 'security',
				'multiple'	=> true,
				'label'		=> __( 'Who can send new messages?', 'front-end-pm' ),
				'options'	=> $user_role,
			),
			'userrole_reply' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'userrole_reply', array() ),
				'priority'	=> 15,
				'class'		=> '',
				'section'	=> 'security',
				'multiple'	=> true,
				'label'		=> __( 'Who can send replies?', 'front-end-pm' ),
				'options'	=> $user_role,
			),
			'whitelist_username' => array(
				'type'		 =>	'textarea',
				'value'		 => fep_get_option( 'whitelist_username' ),
				'section'	 => 'security',
				'priority'	 => 20,
				'label'		 => __( 'Whitelist Username', 'front-end-pm' ),
				'description'=> __( 'Separated by comma. These users have all permission if blocked by role also.', 'front-end-pm' ),
			),
			'have_permission' => array(
				'type'		 => 'textarea',
				'value'		 => fep_get_option( 'have_permission' ),
				'section'	 => 'security',
				'priority'	 => 25,
				'label'		 => __( 'Blacklist Username', 'front-end-pm' ),
				'description'=> __( 'Separated by comma. These users have NO permission if allowed by role also.', 'front-end-pm' ),
			),
			'block_other_users' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'block_other_users', 1 ),
				'priority'	=> 30,
				'section'	=> 'security',
				'class'		=> '',
				'label'		=> __( 'Block other users', 'front-end-pm' ),
				'cb_label'	=> __( 'Can user block other users?', 'front-end-pm' ),
			),
			'add_ann_frontend' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'add_ann_frontend', 1 ),
				'priority'	=> 35,
				'section'	=> 'security',
				'class'		=> '',
				'label'		=> __( 'Add Announcement', 'front-end-pm' ),
				'cb_label'	=> __( 'Can permitted users add Announcement from front end?', 'front-end-pm' ),
			),
			'reply_deleted_mgs' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'reply_deleted_mgs', '' ),
				'priority'	=> 40,
				'section'	=> 'security',
				'class'		=> '',
				'label'		=> __( 'Reply Deleted Message', 'front-end-pm' ),
				'cb_label'	=> __( 'Can user reply messages deleted by other user?', 'front-end-pm' ),
			),
			//Notification Settings
			'show_notification' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'show_notification', 1 ),
				'priority'	=> 5,
				'section'	=> 'notification',
				'class'		=> '',
				'label'		=> __( 'Show notification', 'front-end-pm' ),
				'cb_label'	=> __( 'Show site wide notification in header site?', 'front-end-pm' ),
			),
			'show_unread_count_in_title' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'show_unread_count_in_title', 1 ),
				'priority'	=> 10,
				'section'	=> 'notification',
				'class'		=> '',
				'label'		=> __( 'Show count', 'front-end-pm' ),
				'cb_label'	=> __( 'Show unread messages count in website title?', 'front-end-pm' ),
			),
			'show_unread_count_in_desktop' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'show_unread_count_in_desktop', 1 ),
				'priority'	=> 15,
				'section'	=> 'notification',
				'class'		=> '',
				'label'		=> __( 'Show desktop notification', 'front-end-pm' ),
				'cb_label'	=> __( 'Show desktop notification for new messages and announcements?', 'front-end-pm' ),
			),
			'play_sound' => array(
				'type'		=> 'checkbox',
				'value'		=> fep_get_option( 'play_sound', 1 ),
				'priority'	=> 20,
				'section'	=> 'notification',
				'class'		=> '',
				'label'		=> __( 'Play Sound', 'front-end-pm' ),
				'cb_label'	=> __( 'Play notification sound on message and announcement received?', 'front-end-pm' ),
			),
		);
		foreach ( $user_role as $key => $role ) {
			$fields["message_box_{$key}"] = array(
				'type'		 => 'number',
				'value'		 => fep_get_option( "message_box_{$key}", 50 ),
				'section'	 => 'message_box',
				'label'		 => $role,
				'description'=> sprintf( __( 'Max messages a %s can keep in box? (0 = unlimited)', 'front-end-pm' ), $role ),
			);
		}
		if ( ! fep_get_option( 'page_id', 0 ) ) {
			$fields['page_id']['description'] = $fields['page_id']['description'] . '<br /><a class="button-secondary" href="' . esc_url( add_query_arg( array( 'post_title' => 'Front End PM', 'content' => '[front-end-pm]' ), admin_url( 'post-new.php?post_type=page' ) ) ) . '">' . __( 'Create Page', 'front-end-pm' ) . '</a>';
		}
		$fields = apply_filters( 'fep_settings_fields', $fields );
		foreach ( $fields as $key => $field ) {
			if ( empty( $field['section'] ) ) {
				$field['section'] = 'general';
			}
			if ( $section != $field['section'] ) {
				unset( $fields[ $key ] );
				continue;
			}
			$this->priority++;
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';
			$defaults = array(
				'key'			=> $key,
				'type'			=> $type,
				'name'			=> $key,
				'class'			=> ( 'number' == $type ) ? 'small-text' : 'regular-text',
				'id'			=> $key,
				'label'			=> '',
				'cb_label'		=> '',
				'value'			=> '',
				'placeholder' 	=> '',
				'description'	=> '',
				'priority'		=> $this->priority,
			);
			$fields[ $key ] = wp_parse_args( $fields[ $key ], $defaults );
		}
		uasort( $fields, 'fep_sort_by_priority' );
		return $fields;
	}

	function settings_output() {	
		//register_setting( $option_group, $option_name, $sanitize_callback = '' );
		register_setting( 'fep_settings', 'FEP_admin_options', array( $this, 'options_sanitize' ) );
		foreach ( $this->tabs() as $slug => $tab ) {
			//add_settings_section( $id, $title, $callback, $page );
			add_settings_section( $tab['section_id'], $tab['section_title'], $tab['section_callback'], $tab['section_page'] );
			foreach ( $this->form_fields( str_replace( 'fep_settings_', '', $tab['section_id'] ) ) as $key => $field ) {
				if ( function_exists( 'fep_settings_field_output_callback_' . $field['type'] ) ) {
					$callback = 'fep_settings_field_output_callback_' . $field['type'];
				} else {
					$callback = array( $this, 'field_output' );
				}
				//add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() );
				add_settings_field( $field['id'], $field['label'], $callback, $tab['section_page'], $tab['section_id'], $field );
			}
		}
	}

	function field_output( $field ) {
		$attrib = ''; 
		if ( ! empty( $field['required'] ) ) {
			$attrib .= 'required = "required" ';
		}
		if ( ! empty( $field['readonly'] ) ) {
			$attrib .= 'readonly = "readonly" ';
		}
		if ( ! empty( $field['disabled'] ) ) {
			$attrib .= 'disabled = "disabled" ';
		}
		if ( ! empty( $field['minlength'] ) ) {
			$attrib .= 'minlength = "' . absint( $field['minlength'] ) . '" ';
		}
		if ( ! empty( $field['maxlength'] ) ) {
			$attrib .= 'maxlength = "' . absint( $field['maxlength'] ) . '" ';
		}
		if ( ! empty( $field['class'] ) ) {
			$field['class'] = fep_sanitize_html_class( $field['class'] );
		}
		switch ( $field['type'] ) {
			case has_action( 'fep_admin_settings_field_output_' . $field['type'] ):
				do_action( 'fep_admin_settings_field_output_' . $field['type'], $field );
				break;
			case 'text':
			case 'email':
			case 'url':
			case 'number':
				?><input id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( stripslashes( $field['value' ] ) ); ?>" <?php echo $attrib; ?> /><?php
				break;
			case 'color_picker':
				?><input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( stripslashes( $field['value' ] ) ); ?>" class="fep-color-picker" data-default-color="<?php echo esc_attr( $field['default_value'] ); ?>" ><?php
				break;
			case 'textarea':
				?><textarea id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" cols="50" name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" <?php echo $attrib; ?>><?php echo wp_kses_post( stripslashes( $field['value' ] ) ); ?></textarea><?php
				break;
			case 'wp_editor':
				wp_editor( wp_kses_post( stripslashes( $field['value' ] ) ), $field['id'], array( 'textarea_name' => $field['name'], 'editor_class' => $field['class'], 'media_buttons' => false) );
				break;
			case 'teeny':
				wp_editor( wp_kses_post( stripslashes( $field['value' ] ) ), $field['id'], array( 'textarea_name' => $field['name'], 'editor_class' => $field['class'], 'teeny' => true, 'media_buttons' => false) );
				break;
			case 'checkbox':
				if ( ! empty( $field['multiple' ] ) ) {
					foreach( $field['options' ] as $key => $name ) {
						?><label><input id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" type="checkbox" value="<?php echo esc_attr( $key ); ?>" <?php if ( in_array( $key, $field['value' ] ) ) { echo 'checked="checked"';} ?> /> <?php echo esc_attr( $name ); ?></label><br /><?php
					}
				} else {
					?><label><input id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>" type="checkbox" value="1" <?php checked( '1', $field['value' ] ); ?> /> <?php echo esc_attr( $field['cb_label'] ); ?></label><?php
				}
				break;
			case 'select':
				?><select id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>"><?php
				foreach( $field['options'] as $key => $name ) {
				?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $field['value' ], $key ); ?>><?php echo esc_attr( $name ); ?></option><?php }
				?></select><?php
				break;
			case 'radio':
				foreach( $field['options'] as $key => $name ) {
					?><label><input type="radio" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $field['posted-value' ], $key ); ?> /> <?php echo esc_attr( $name ); ?></label><br /><?php
				}
				break;
			case 'html':
				echo wp_kses_post( $field['value'] );
				break;
			default:
				printf( __( 'No Function or Hook defined for %s field type', 'front-end-pm' ), $field['type'] );
				break;
		}
		if ( ! empty( $field['description'] ) ) {
			?><p class="description"><?php echo wp_kses_post( $field['description'] ); ?></p><?php
		}
	}

	function posted_value_sanitize( $value, $field ) {
		$sanitized = $value;
			switch ( $field['type'] ) {
				case 'text':
				$sanitized = sanitize_text_field( trim( $value ) );
				break;
			case 'email': //sanitize_email()
				if ( ! is_email( $value ) ) {
					add_settings_error( 'fep-settings', $field['id'], sprintf( __( 'Provide valid email address for %s', 'front-end-pm' ), $field['label'] ) );
					$sanitized = $field['value'];
				}
				break;
			case 'color_picker':
				if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $value ) ) { // if user insert a HEX color with #
					add_settings_error( 'fep-settings', $field['id'], sprintf( __( 'Provide valid color for %s', 'front-end-pm' ), $field['label'] ) );
					$sanitized = $field['value'];
				}
				break;
			case 'url':
				$sanitized = esc_url( $value );
				break;
			case 'number':
				$sanitized = absint( $value );
				break;
			case 'textarea':
			case 'wp_editor':
			case 'teeny':
				$sanitized = wp_kses_post( $value );
				break;
			case 'checkbox':
				if ( ! empty( $field['multiple' ] ) ) {
					$sanitized = is_array( $value ) ? $value : array();
					foreach( $sanitized as $p_value ) {
						if ( ! array_key_exists( $p_value, $field['options'] ) ) {
							add_settings_error( 'fep-settings', $field['id'], sprintf( __( 'Invalid value for %s', 'front-end-pm' ), $field['label'] ) );
							$sanitized = $field['value'];
						}
					}
				} else {
					$sanitized = absint( $value );
				}
				break;
			case 'select':
				if ( ! array_key_exists( $value, $field['options'] ) ) {
					add_settings_error( 'fep-settings', $field['id'], sprintf( __( 'Invalid value for %s', 'front-end-pm' ), $field['label'] ) );
					$sanitized = $field['value'];
				}
				break;
			default:
				$sanitized = apply_filters( 'fep_settings_field_sanitize_filter_' . $field['type'], $value, $field );
				break;
		}
		return apply_filters( 'fep_settings_field_sanitize_filter', $sanitized, $field, $value );
	}

	function options_sanitize( $value ) {
		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $value;
		}
		global $wp_settings_sections;
		wp_parse_str( parse_url( $_POST['_wp_http_referer'], PHP_URL_QUERY ), $referrer );
		$tab = ! empty( $referrer['tab'] ) ? $referrer['tab'] : 'general';
		if ( empty( $referrer['page'] ) || 'fep_settings' != $referrer['page'] ) {
			return $value;
		}
		if ( empty( $wp_settings_sections['fep_settings_' . $tab] ) ) {
			//return /** $value */ get_option( 'FEP_admin_options' );
			return $value;
		}
		$posted_value = array();
		foreach ( (array) $wp_settings_sections['fep_settings_' . $tab] as $section ) {
			$section_tab = str_replace( 'fep_settings_', '', $section['id'] );
			$sanitized = apply_filters( "fep_settings_section_sanitize_filter_{$section_tab}", $this->sanitize( $section_tab ), $section );
			$sanitized = apply_filters( 'fep_settings_section_sanitize_filter', $sanitized, $section );
			$posted_value = wp_parse_args( $sanitized, $posted_value ); 
		}

		// Merge our new settings with the existing
		$settings = wp_parse_args( $posted_value, get_option( 'FEP_admin_options' ) );
		$settings = apply_filters( 'fep_filter_before_admin_options_save', $settings, $tab );

		//Do not use this action hook
		do_action( 'fep_action_before_admin_options_save', $settings, $tab );
		return $settings;
	}

	function sanitize( $section ) {
		$posted_value = array();
		foreach ( (array) $this->form_fields( $section ) as $key => $field ) {
			$posted_value[ $field['name'] ] = isset( $_POST[ $field['name'] ] ) ? $_POST[ $field['name'] ] : '';
			$posted_value[ $field['name'] ] = $this->posted_value_sanitize( $posted_value[ $field['name'] ], $field );
		}
		return $posted_value;
	}

	function tabs() {
		$tabs = array(
			'general' => array(
				'tab_title'	=> __( 'General', 'front-end-pm' ),
				'priority'	=> 5,
			),
			'appearance' => array(
				'tab_title'	=> __( 'Appearance', 'front-end-pm' ),
				'priority'	=> 6,
			),
			'recipient' => array(
				'tab_title'	=> __( 'Recipient', 'front-end-pm' ),
				'priority'	=> 7,
			),
			/*'message' => array(
				'tab_title'	=> __( 'Message', 'front-end-pm' ),
				'priority'	=> 10,
			),
			'announcement'	=> array(
				'tab_title'	=> __( 'Announcement', 'front-end-pm' ),
				'priority'	=> 15,
			),*/
			'privacy' => array(
				'tab_title'	=> __( 'Privacy', 'front-end-pm' ),
				'priority'	=> 16,
			),
			'emails' => array(
				'tab_title'	=> __( 'Emails', 'front-end-pm' ),
				'priority'	=> 20,
			),
			'security' => array(
				'tab_title'	=> __( 'Security', 'front-end-pm' ),
				'priority'	=> 25,
			),
			'misc' => array(
				'tab_title'	=> __( 'Misc', 'front-end-pm' ),
				'priority'	=> 27,
			),
			'privacy_export' => array(
				'section_title'	=> __( 'Export', 'front-end-pm' ),
				'section_page'	=> 'fep_settings_privacy',
				'priority'		=> 10,
				'tab_output'	=> false,
			),
			'privacy_erase' => array(
				'section_title'	=> __( 'Erase', 'front-end-pm' ),
				'section_page'	=> 'fep_settings_privacy',
				'priority'		=> 15,
				'tab_output'	=> false,
			),
			'notification' => array(
				'section_title'	=> __( 'Notification', 'front-end-pm' ),
				'section_page'	=> 'fep_settings_misc',
				'priority'		=> 10,
				'tab_output'	=> false,
			),
			'message_box' => array(
				'section_title'	=> __( 'Message Box', 'front-end-pm' ),
				'section_page'	=> 'fep_settings_misc',
				'priority'		=> 15,
				'tab_output'	=> false,
			),
		);
		$tabs = apply_filters( 'fep_admin_settings_tabs', $tabs );
		foreach ( $tabs as $key => $tab ) {
			$defaults = array(
				'section_id'		=> 'fep_settings_' . $key,
				'section_title'		=> '',
				'section_callback'	=> '',
				'section_page'		=> 'fep_settings_' . $key,
				'tab_output'		=> true,
				'tab_title'			=> '',
				'tab_slug'			=> $key,
				'priority'			=> 10,
			);
			$tabs[ $key ] = wp_parse_args( $tabs[ $key ], $defaults );
		}
		uasort( $tabs, 'fep_sort_by_priority' );
		return $tabs;
	}

	function settings_page() {
		$active_tab = ! empty( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		$args = array(
			'page'		=> 'fep_settings',
		);
		?>
		<div class="wrap">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<h2 class="nav-tab-wrapper">
							<?php
							foreach ( $this->tabs() as $key => $tab ) :
								if ( empty( $tab['tab_output'] ) ) {
									continue;
								}
								$args['tab'] = $tab['tab_slug'];
								?>
								<a href="<?php echo esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) ); ?>" class="nav-tab<?php if ( $active_tab == $tab['tab_slug'] ) echo ' nav-tab-active'; ?>"><?php echo $tab['tab_title']; ?></a>
							<?php endforeach; ?>
						</h2>
						<div id="tab_container">
							<?php settings_errors( /** 'fep-settings' */ ); ?>
							<form method="post" action="options.php">
								<?php 
								settings_fields( 'fep_settings' );
								do_settings_sections( "fep_settings_{$active_tab}" );
								submit_button();
								?>
							</form>
						</div><!-- #tab_container-->
					</div><!-- #post-body-content-->
					<div id="postbox-container-1" class="postbox-container">
						<?php echo $this->fep_admin_sidebar(); ?>
					</div><!-- #postbox-container-1 -->
				</div><!-- #post-body -->
				<br class="clear" />
			</div><!-- #poststuff -->
		</div><!-- .wrap -->
		<?php
	}

	function fep_admin_sidebar() {
		return '<div class="postbox">
		<h3 class="hndle" style="text-align:center;">
			<span>' . __( 'Plugin Author', 'front-end-pm' ) . '</span>
		</h3>
		<div class="inside">
			<div style="text-align:center;margin:auto">
				<strong>Shamim Hasan</strong><br />
				Know PHP, MySQL, CSS, JavaScript, HTML. Expert in WordPress.<br /><br />

				You can hire for plugin customization, build custom plugin or any kind of WordPress job via <br />
				<a href="https://www.shamimsplugins.com/contact-us/?utm_campaign=admin&utm_source=sidebar&utm_medium=author" target="_blank"><strong>Contact Form</strong></a>
			</div>
		</div><!-- .inside -->
		</div>
		<div class="postbox">
			<h3 class="hndle" style="text-align:center;">
				<span>' . __( 'Some Useful Links', 'front-end-pm' ) . '</span>
			</h3>
			<div class="inside">
				<div style="text-align:center;margin:auto">
					<p>Some useful links are bellow to work with this plugin.</p>
					<ul>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm/getting-started/basic-admin-settings/?utm_campaign=admin&utm_source=sidebar&utm_medium=useful_links" target="_blank">' . __( 'Basic Admin Settings', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm/getting-started/basic-front-end-walkthrough/?utm_campaign=admin&utm_source=sidebar&utm_medium=useful_links" target="_blank">' . __( 'Walkthrough', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm/customization/remove-minlength-message-title/?utm_campaign=admin&utm_source=sidebar&utm_medium=useful_links" target="_blank">' . __( 'Remove minlength', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm/customization/remove-settings-menu-button/?utm_campaign=admin&utm_source=sidebar&utm_medium=useful_links" target="_blank">' . __( 'Remove menu', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/category/front-end-pm/shortcode/?utm_campaign=admin&utm_source=sidebar&utm_medium=useful_links" target="_blank">' . __( 'Shortcodes', 'front-end-pm' ) . '</a></li>
					</ul>
				</div>
			</div><!-- .inside -->
		</div><!-- .postbox -->
		<div class="postbox">
			<h3 class="hndle" style="text-align: center;">
				<span>' . __( "Front End PM PRO", "front-end-pm" ) . '</span>
			</h3>
			<div class="inside">
				<div style="text-align:center;margin:auto">
					<p>Some useful links are bellow to work with this plugin.</p>
					<ul>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/email-piping/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Email Piping', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/multiple-recipients/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Multiple Recipient', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/only-admin/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Only Admin', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/group-messaging/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Group Messaging', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/email-beautify/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Email Beautify', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/read-receipt/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Read Receipt', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/docs/front-end-pm-pro/getting-started-2/role-to-role-block/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank">' . __( 'Role to Role Block', 'front-end-pm' ) . '</a></li>
						<li><a href="https://www.shamimsplugins.com/products/front-end-pm-pro/?utm_campaign=admin&utm_source=sidebar&utm_medium=pro" target="_blank"><strong>' . __( 'View More', 'front-end-pm' ) . '</strong></a></li>
					</ul>
				</div>
			</div><!-- .inside -->
		</div><!-- .postbox -->';
	}

	/**
	 * Admin notices for review
	 *
	 * @access	public
	 * @return	void
	 */
	public function notice_review() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( fep_is_pro() ) {
			return;
		}
		if ( fep_get_option( 'dismissed-review' ) ) {
			return;
		}
		$dismissed_time = get_user_meta( get_current_user_id(), 'fep_review_notice_dismiss', true );
		if( ! $dismissed_time || ! is_numeric( $dismissed_time ) ){
			update_user_meta( get_current_user_id(), 'fep_review_notice_dismiss', time() );
			return;
		}
		if ( $dismissed_time && time() < ( $dismissed_time + WEEK_IN_SECONDS ) ) {
			return;
		}
		?>
		<div class="notice notice-info inline fep-review-notice">
			<p><?php printf( __( 'Like %s plugin? Please consider review in WordPress.org and give 5&#9733; rating.', 'front-end-pm' ), 'Front End PM' ); ?></p>
			<p>
				<a href="https://wordpress.org/support/plugin/front-end-pm/reviews/?filter=5#new-post" class="button button-primary fep-review-notice-dismiss" data-fep_click="sure" target="_blank" rel="noopener"><?php _e( 'Sure, deserve it', 'front-end-pm' ); ?></a>
				<button class="button-secondary fep-review-notice-dismiss" data-fep_click="later"><?php _e( 'Maybe later', 'front-end-pm' ); ?></button>
				<button class="button-secondary fep-review-notice-dismiss" data-fep_click="did"><?php _e( 'Already did', 'front-end-pm' ); ?></button>
			</p>
		</div>
		<?php
	}

	function add_settings_link( $links ) {
		//add settings link in plugins page
		$settings_link = '<a href="' . admin_url( 'admin.php?page=fep_settings' ) . '">' . __( 'Settings', 'front-end-pm' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	function set_page_id( $id, $post ) {
		if ( ! fep_get_option( 'page_id' ) && false !== strpos( $post->post_content, '[front-end-pm' ) ) {
			fep_update_option( 'page_id', $id );
		}
	}
} //END CLASS
add_action( 'init', array(Fep_Admin_Settings::init(), 'actions_filters' ) );
