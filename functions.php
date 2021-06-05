<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function fep_register_metadata_table(){
	global $wpdb;
	$wpdb->fep_messagemeta = FEP_META_TABLE;
	$wpdb->fep_messages    = FEP_MESSAGE_TABLE;
}

function fep_create_database(){
	global $wpdb;
	if ( is_multisite() ) {
		$installed_ver = get_site_option( 'fep_db_version' );
	} else {
		$installed_ver = get_option( 'fep_db_version' );
	}
	
	if ( version_compare( $installed_ver, '1011', '<' ) && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', FEP_MESSAGE_TABLE ) ) && $wpdb->get_var( $wpdb->prepare( 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s', DB_NAME, FEP_MESSAGE_TABLE, 'id' ) ) ) {
		if ( ! $wpdb->get_var( 'SELECT COUNT(*) FROM ' . FEP_MESSAGE_TABLE . ' WHERE id IS NOT NULL LIMIT 1' ) ) {
			$wpdb->query( 'DROP TABLE IF EXISTS ' . FEP_MESSAGE_TABLE );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'fep_meta' );
		} else {
			$wpdb->query( sprintf( 'ALTER TABLE %1$s RENAME %1$s_old', FEP_MESSAGE_TABLE ) );
		}
	}
	if ( version_compare( $installed_ver, FEP_DB_VERSION, '!=' ) ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql_message = "CREATE TABLE $wpdb->fep_messages (
			mgs_id bigint(20) unsigned NOT NULL auto_increment,
			mgs_parent bigint(20) unsigned NOT NULL default '0',
			mgs_author bigint(20) unsigned NOT NULL default '0',
			mgs_created datetime NOT NULL default '0000-00-00 00:00:00',
			mgs_title text NOT NULL,
			mgs_content mediumtext NOT NULL,
			mgs_type varchar(20) NOT NULL DEFAULT 'message',
			mgs_status varchar(20) NOT NULL DEFAULT 'pending',
			mgs_last_reply_by bigint(20) unsigned NOT NULL default '0',
			mgs_last_reply_time datetime NOT NULL default '0000-00-00 00:00:00',
			mgs_last_reply_excerpt varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (mgs_id),
			KEY mgs_parent_last_time (mgs_parent,mgs_last_reply_time),
			KEY mgs_type_created (mgs_type,mgs_created)
		) $charset_collate;";
		
		$sql_perticipiants = "CREATE TABLE " . FEP_PARTICIPANT_TABLE . " (
			per_id bigint(20) unsigned NOT NULL auto_increment,
			mgs_id bigint(20) unsigned NOT NULL default '0',
			mgs_participant bigint(20) unsigned NOT NULL default '0',
			mgs_read bigint(20) unsigned NOT NULL default '0',
			mgs_parent_read bigint(20) unsigned NOT NULL default '0',
			mgs_deleted bigint(20) unsigned NOT NULL default '0',
			mgs_archived bigint(20) unsigned NOT NULL default '0',
			PRIMARY KEY  (per_id),
			UNIQUE KEY mgs_id_participant (mgs_id,mgs_participant)
		) $charset_collate;";
		
		$sql_meta = "CREATE TABLE $wpdb->fep_messagemeta (
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			fep_message_id bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY fep_message_id (fep_message_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";
		
		$sql_attachments = "CREATE TABLE " . FEP_ATTACHMENT_TABLE . " (
			att_id bigint(20) unsigned NOT NULL auto_increment,
			mgs_id bigint(20) unsigned NOT NULL default '0',
			att_mime varchar(100) NOT NULL default '',
			att_file varchar(255) NOT NULL default '',
			att_status varchar(20) NOT NULL default '',
			PRIMARY KEY  (att_id),
			KEY mgs_id (mgs_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_message );
		dbDelta( $sql_perticipiants );
		dbDelta( $sql_meta );
		dbDelta( $sql_attachments );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', FEP_MESSAGE_TABLE ) ) ){
			if ( is_multisite() ) {
				update_site_option( 'fep_db_version', FEP_DB_VERSION );
			} else {
				update_option( 'fep_db_version', FEP_DB_VERSION, true );
			}
		}
	}
}

function fep_include_require_files() {
	require_once( FEP_PLUGIN_DIR . 'includes/class-fep-cache.php' );
	require_once( FEP_PLUGIN_DIR . 'includes/class-fep-message.php' );
	require_once( FEP_PLUGIN_DIR . 'includes/fep-message-meta.php' );
	require_once( FEP_PLUGIN_DIR . 'includes/class-fep-participants.php' );
	require_once( FEP_PLUGIN_DIR . 'includes/class-fep-attachments.php' );
	require_once( FEP_PLUGIN_DIR . 'includes/class-fep-message-query.php' );
	
	$fep_files = array(
		'announcement' 	=> FEP_PLUGIN_DIR . 'includes/class-fep-announcements.php',
		'attachment' 	=> FEP_PLUGIN_DIR . 'includes/class-fep-attachment.php',
		'directory' 	=> FEP_PLUGIN_DIR . 'includes/class-fep-directory.php',
		'email' 		=> FEP_PLUGIN_DIR . 'includes/class-fep-emails.php',
		'form' 			=> FEP_PLUGIN_DIR . 'includes/class-fep-form.php',
		'menu' 			=> FEP_PLUGIN_DIR . 'includes/class-fep-menu.php',
		'messages' 		=> FEP_PLUGIN_DIR . 'includes/class-fep-messages.php',
		'shortcodes' 	=> FEP_PLUGIN_DIR . 'includes/class-fep-shortcodes.php',
		'user-settings' => FEP_PLUGIN_DIR . 'includes/class-fep-user-settings.php',
		'main' 			=> FEP_PLUGIN_DIR . 'includes/fep-class.php',
		'widgets' 		=> FEP_PLUGIN_DIR . 'includes/fep-widgets.php',
		'rest' 		=> FEP_PLUGIN_DIR . 'includes/class-fep-rest-api.php',
	);
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$fep_files['ajax'] 	= FEP_PLUGIN_DIR . 'includes/class-fep-ajax.php';
	}
	if ( is_admin() ) {
		$fep_files['table'] 			= FEP_PLUGIN_DIR . 'admin/class-fep-wp-list-table.php';
		$fep_files['attachment-table'] 	= FEP_PLUGIN_DIR . 'admin/class-fep-attachments-list-table.php';
		$fep_files['admin-pages'] 		= FEP_PLUGIN_DIR . 'admin/class-fep-admin-pages.php';
		$fep_files['settings'] 			= FEP_PLUGIN_DIR . 'admin/class-fep-admin-settings.php';
		$fep_files['update'] 			= FEP_PLUGIN_DIR . 'admin/class-fep-update.php';
		$fep_files['pro-info'] 			= FEP_PLUGIN_DIR . 'admin/class-fep-pro-info.php';
	}
	$fep_files = apply_filters( 'fep_include_files', $fep_files );
	foreach ( $fep_files as $fep_file ) {
		require_once( $fep_file );
	}
}

function fep_get_option( $option, $default = '', $section = 'FEP_admin_options' ) {
	$options = get_option( $section );
	if ( ! is_array( $options ) ) {
		$options = array();
	}
	
	$is_default = false;
	if ( isset( $options[ $option ] ) ) {
		$value = $options[ $option ];
	} else {
		$value = $default;
		$is_default = true;
	}
	return apply_filters( 'fep_get_option', $value, $option, $default, $is_default );
}

function fep_update_option( $option, $value = '', $section = 'FEP_admin_options' ) {
	if ( empty( $option ) ) {
		return false;
	}
	if ( ! is_array( $option ) ) {
		$option = array( $option => $value );
	}

	$options = get_option( $section );
	if ( ! is_array( $options ) ) {
		$options = array();
	}
	return update_option( $section, wp_parse_args( $option, $options ) );
}

function fep_get_user_option( $option, $default = '', $userid = '', $section = 'FEP_user_options' ) {
	if( ! $userid ){
		$userid = get_current_user_id();
	}
	$options = get_user_meta( $userid, $section, true );
	$is_default = false;
	if ( isset( $options[ $option ] ) ) {
		$value = $options[ $option ];
	} else {
		$value = $default;
		$is_default = true;
	}
	return apply_filters( 'fep_get_user_option', $value, $option, $default, $userid, $is_default );
}

function fep_update_user_option( $option, $value = '', $userid = '', $section = 'FEP_user_options' ) {
	if ( empty( $option ) ) {
		return false;
	}
	if ( ! is_array( $option ) ) {
		$option = array( $option => $value );
	}
	if ( ! $userid ) {
		$userid = get_current_user_id();
	}
	
	$options = get_user_meta( $userid, $section, true );
	if ( ! is_array( $options ) ) {
		$options = array();
	}
	return update_user_meta( $userid, $section, wp_parse_args( $option, $options ) );
}

function fep_translation() {
	// SETUP TEXT DOMAIN FOR TRANSLATIONS
	load_plugin_textdomain( 'front-end-pm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function fep_enqueue_scripts() {
	wp_register_style( 'fep-common-style', FEP_PLUGIN_URL . 'assets/css/common-style.css', array(), FEP_PLUGIN_VERSION );
	wp_register_style( 'fep-style', FEP_PLUGIN_URL . 'assets/css/style.css', array(), FEP_PLUGIN_VERSION );
	if ( 'always' == fep_get_option( 'load_css','only_in_message_page' ) ) {
		wp_enqueue_style( 'fep-style' );
	} elseif ( 'only_in_message_page' == fep_get_option( 'load_css','only_in_message_page' ) &&
				fep_page_id() && ( is_page( fep_page_id() ) || is_single( fep_page_id() ) ) ) {
		wp_enqueue_style( 'fep-style' );
	}
	wp_enqueue_style( 'fep-common-style' );
	
	$important = '';
	
	if ( apply_filters( 'fep_add_important_to_inline_css', false ) ) {
		$important = ' !important';
	}

	$custom_css = '#fep-wrapper{';
	$custom_css .= 'background-color:' . fep_get_option( 'bg_color' ) . $important . ';';
	$custom_css .= 'color:' . fep_get_option( 'text_color', '#000000' ) . $important . ';';
	$custom_css .= '}';
	$custom_css .= ' #fep-wrapper a:not(.fep-button,.fep-button-active) {color:' . fep_get_option( 'link_color', '#000080' ) . $important . ';}';
	$custom_css .= ' .fep-button{';
	$custom_css .= 'background-color:' . fep_get_option( 'btn_bg_color', '#F0FCFF' ) . $important . ';';
	$custom_css .= 'color:' . fep_get_option( 'btn_text_color', '#000000' ) . $important . ';';
	$custom_css .= '}';
	$custom_css .= ' .fep-button:hover,.fep-button-active{';
	$custom_css .= 'background-color:' . fep_get_option( 'active_btn_bg_color', '#D3EEF5' ) . $important . ';';
	$custom_css .= 'color:' . fep_get_option( 'active_btn_text_color', '#000000' ) . $important . ';';
	$custom_css .= '}';
	$custom_css .= ' .fep-odd-even > div:nth-child(odd) {background-color:' . fep_get_option( 'odd_color', '#F2F7FC' ) . $important . ';}';
	$custom_css .= ' .fep-odd-even > div:nth-child(even) {background-color:' . fep_get_option( 'even_color', '#FAFAFA' ) . $important . ';}';
	$custom_css .= ' .fep-message .fep-message-title-heading, .fep-per-message .fep-message-title{background-color:' . fep_get_option( 'mgs_heading_color', '#F2F7FC' ) . $important . ';}';
	$custom_css .= ' #fep-content-single-heads .fep-message-head:hover,#fep-content-single-heads .fep-message-head-active{';
	$custom_css .= 'background-color:' . fep_get_option( 'active_btn_bg_color', '#D3EEF5' ) . $important . ';';
	$custom_css .= 'color:' . fep_get_option( 'active_btn_text_color', '#000000' ) . $important . ';';
	$custom_css .= '}';
	$custom_css .= trim( stripslashes( fep_get_option( 'custom_css' ) ) );
	if ( $custom_css ) {
		wp_add_inline_style( 'fep-common-style', $custom_css );
	}
	wp_register_script( 'fep-script', FEP_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	wp_localize_script( 'fep-script', 'fep_script',
		array(
			'root'    => esc_url_raw( rest_url( 'front-end-pm/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'no_match'   => __( 'No matches found', 'front-end-pm' ),
		)
	);
	wp_register_script( 'fep-notification-script', FEP_PLUGIN_URL . 'assets/js/notification.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	$call_on_ready = ( isset( $_GET['fepaction'] ) &&
		( ( 'viewmessage' == $_GET['fepaction'] && fep_get_new_message_number() ) || ( 'view_announcement' == $_GET['fepaction'] && fep_get_new_announcement_number() ) )
	) ? true : false;
	wp_localize_script( 'fep-notification-script', 'fep_notification_script',
		apply_filters( 'fep_filter_notification_script_localize', array(
				'root'    => esc_url_raw( rest_url( 'front-end-pm/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'interval'	=> apply_filters( 'fep_filter_ajax_notification_interval', 2 * MINUTE_IN_SECONDS * 1000 ),
				'skip'		=> apply_filters( 'fep_filter_skip_notification_call', 2 ), // How many times notification ajax call will be skipped if browser tab not opened
				'show_in_title'		=> fep_get_option( 'show_unread_count_in_title', '1' ),
				'show_in_desktop'	=> fep_get_option( 'show_unread_count_in_desktop', '1' ),
				'call_on_ready'		=> apply_filters( 'fep_filter_notification_call_on_ready', $call_on_ready ),
				'play_sound'		=> fep_get_option( 'play_sound', '1' ),
				'sound_url'			=> FEP_PLUGIN_URL . 'assets/audio/plucky.mp3',
				'icon_url'			=> FEP_PLUGIN_URL . 'assets/images/desktop-notification-32.png',
				'mgs_notification_title'=> __( 'New Message. ', 'front-end-pm' ),
				'mgs_notification_body'	=> __( 'You have received a new message. ', 'front-end-pm' ),
				'mgs_notification_url'	=> fep_query_url( 'messagebox' ),
				'ann_notification_title'=> __( 'New Announcement. ', 'front-end-pm' ),
				'ann_notification_body'	=> __( 'You have received a new announcement. ', 'front-end-pm' ),
				'ann_notification_url'	=> fep_query_url( 'announcements' ),
			)
		)
	);
	wp_register_script( 'fep-replies-show-hide', FEP_PLUGIN_URL . 'assets/js/replies-show-hide.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	wp_register_script( 'fep-attachment-script', FEP_PLUGIN_URL . 'assets/js/attachment.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	wp_localize_script( 'fep-attachment-script', 'fep_attachment_script', array(
			'remove'	=> esc_js( __( 'Remove', 'front-end-pm' ) ),
			'maximum'	=> esc_js( fep_get_option( 'attachment_no', 4 ) ),
			'max_text'	=> esc_js( sprintf( __( 'Maximum %s allowed', 'front-end-pm' ), sprintf( _n( '%s file', '%s files', fep_get_option( 'attachment_no', 4 ), 'front-end-pm' ), number_format_i18n( fep_get_option( 'attachment_no', 4 ) ) ) ) )
		)
	);
	wp_register_script( 'fep-form-submit', FEP_PLUGIN_URL . 'assets/js/form-submit.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	wp_localize_script( 'fep-form-submit', 'fep_form_submit',
		array(
			'ajaxurl'      => admin_url( 'admin-ajax.php' ),
			'token'        => wp_create_nonce( 'fep-form' ),
			'refresh_text' => __( 'Refresh this page and try again. ', 'front-end-pm' ),
			'processing_text' => __( 'Processing... ', 'front-end-pm' ),
		)
	);
	wp_register_script( 'fep-block-unblock-script', FEP_PLUGIN_URL . 'assets/js/block-unblock.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	wp_localize_script( 'fep-block-unblock-script', 'fep_block_unblock_script', array(
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'token'		=> wp_create_nonce( 'fep-block-unblock-script' ),
			'confirm'   => __( 'Do you really want to block %s? If you click "OK" then this user will not be able to send you any more messages.' ),
		)
	);
	wp_register_script( 'fep-view-message', FEP_PLUGIN_URL . 'assets/js/view-message.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	wp_localize_script( 'fep-view-message', 'fep_view_message', array(
			'root'    => esc_url_raw( rest_url( 'front-end-pm/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'feppage' => ! empty( $_GET['feppage'] ) ? absint( $_GET['feppage'] ) : 1,
			'toggle'  => apply_filters( 'fep_filter_message_toggle_feature', true ),
		)
	);
	wp_register_script( 'fep-cb-check-uncheck-all', FEP_PLUGIN_URL . 'assets/js/check-uncheck-all.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
}

function fep_common_scripts() {
	wp_register_style( 'fep-tokeninput-style', FEP_PLUGIN_URL . 'assets/css/token-input-facebook.css' );
	wp_register_script( 'fep-tokeninput-script', FEP_PLUGIN_URL . 'assets/js/jquery.tokeninput.js', array( 'jquery' ), '6.1', true );
	wp_register_script( 'fep-tokeninput', FEP_PLUGIN_URL . 'assets/js/fep-tokeninput.js', array( 'fep-tokeninput-script' ), FEP_PLUGIN_VERSION, true );
}

function fep_tokeninput_localize( $args ) {
	static $count = 1;
	
	$args = wp_parse_args( $args,
		array(
			'ajaxurl'       => esc_url_raw( rest_url( 'front-end-pm/v1/users/' . $args['for'] . '/' ) ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'method'        => 'GET',
			'theme'         => 'facebook',
			'hintText'      => __( 'Type user name', 'front-end-pm'),
			'noResultsText' => __( 'No matches found', 'front-end-pm' ),
			'searchingText' => __( 'Searching...', 'front-end-pm' ),
			'width'         => '250px',
			'tokenLimit'    => null,
			// Following have to be overwritten.
			'selector'      => '', // Field id
			'for'           => '',
			'prePopulate'   => [],
		)
	);
	$args = apply_filters( 'fep_filter_tokeninput_localize', $args );
	wp_localize_script( 'fep-tokeninput', "fep_tokeninput_{$count}", $args );
	$count++;
}

function fep_page_id() {
	return (int) apply_filters( 'fep_page_id_filter', fep_get_option( 'page_id', 0 ) );
}

function fep_action_url( $action = '', $arg = array() ) {
	return fep_query_url( $action, $arg );
}

function fep_query_url( $action, $arg = array() ) {
	$args = array( 'fepaction' => $action );
	$args = array_merge( $args, $arg );
	$url = esc_url( fep_query_url_without_esc( $action, $arg ) );
	return apply_filters( 'fep_query_url_filter', $url, $args );
}

function fep_query_url_raw( $action, $arg = array() ) {
	return esc_url_raw( fep_query_url_without_esc( $action, $arg ) );
}

function fep_query_url_without_esc( $action, $arg = array() ) {
	$args = array( 'fepaction' => $action );
	$args = array_merge( $args, $arg );
	if ( fep_page_id() ) {
		$url = add_query_arg( $args, get_permalink( fep_page_id() ) );
	} else {
		$url = add_query_arg( $args );
	}
	return apply_filters( 'fep_query_url_without_esc_filter', $url, $args );
}

if ( ! function_exists( 'fep_create_nonce' ) ) :
	/**
	 * Creates a token usable in a form
	 * return nonce with time
	 * @return string
	 */
	function fep_create_nonce( $action = -1 ) {
		$time = time();
		$nonce = wp_create_nonce( $time . $action );
		return $nonce . '-' . $time;
	}
endif;

if ( ! function_exists( 'fep_verify_nonce' ) ) :
	/**
	 * Check if a token is valid. Mark it as used
	 * @param string $_nonce The token
	 * @return bool
	 */
	function fep_verify_nonce( $_nonce, $action = -1) {
		$parts = explode( '-', $_nonce ); // Extract timestamp and nonce part of $_nonce

		// bad formatted onetime-nonce
		if ( empty( $parts[0] ) || empty( $parts[1] ) ) {
			return false;
		}
		$nonce = $parts[0]; // Original nonce generated by WordPress.
		$generated = $parts[1]; // Time when generated
		$expire = (int) $generated + HOUR_IN_SECONDS; //We want these nonces to have a short lifespan
		$time = time();

		//Verify the nonce part and check that it has not expired
		if ( ! wp_verify_nonce( $nonce, $generated.$action ) || $time > $expire ) {
			return false;
		}
		
		//Get used nonces
		$used_nonces = get_option( '_fep_used_nonces' );
		if ( ! is_array( $used_nonces ) ) {
			$used_nonces = array();
		}
		
		//Nonce already used.
		if ( isset( $used_nonces[ $nonce] ) ) {
			return false;
		}
		foreach ( $used_nonces as $nonces => $timestamp ) {
			if ( $timestamp < $time ) {
				//This nonce has expired, so we don't need to keep it any longer
				unset( $used_nonces[ $nonces ] );
			}
		}
		$used_nonces[ $nonce ] = $expire; // Add nonce to used nonces
		update_option( '_fep_used_nonces', $used_nonces, 'no' );
		return true;
	}
endif;

function fep_error( $wp_error) {
	if ( ! is_wp_error( $wp_error) ) {
		return '';
	}
	if ( 0 == count( $wp_error->get_error_messages() ) ) {
		return '';
	}
	$errors = $wp_error->get_error_messages();
	if ( is_admin() ) {
		$html = '<div id="message" class="error">';
	} else {
		$html = '<div class="fep-wp-error">';
	}
	foreach ( $errors as $error) {
		$html .= '<strong>' . __( 'Error', 'front-end-pm' ) . ': </strong>' . esc_html( $error ) . '<br />';
	}
	$html .= '</div>';
	return $html;
}

function fep_get_new_message_number() {
	return fep_get_user_message_count( 'unread' );
}
	
function fep_get_new_message_button( $args = array() ) {
	if ( ! fep_current_user_can( 'access_message' ) ) {
		return '';
	}
	$args = wp_parse_args( $args, array(
		'show_bracket'	=> '1',
		'hide_if_zero'	=> '1',
		'ajax'			=> '1',
		'class'			=> 'fep-font-red',
	) );
	$args['class'] = fep_sanitize_html_class( $args['class'] );
	$new           = number_format_i18n( fep_get_new_message_number() );
	if ( empty( $args['ajax'] ) ) {
		if ( ! $new && $args['hide_if_zero'] ) {
			return '';
		}
		$ret = '';
		if ( $args['show_bracket'] ) {
			$ret .= '( ';
		}
		$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
		if ( $args['show_bracket'] ) {
			$ret .= ' )';
		}
		return $ret;
	}
	wp_enqueue_script( 'fep-notification-script' );
	$args['class'] = $args['class'] . ' fep_unread_message_count';
	if ( $args['hide_if_zero'] ) {
		$args['class'] = $args['class'] . ' fep_unread_message_count_hide_if_zero';
	}
	$ret = '';
	if ( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ) {
		$ret .= '<span class="fep_unread_message_count_hide_if_zero fep-hide">(</span>';
	} elseif ( $args['show_bracket'] && $args['hide_if_zero'] ) {
		$ret .= '<span class="fep_unread_message_count_hide_if_zero">(</span>';
	} elseif ( $args['show_bracket'] ) {
		$ret .= '( ';
	}
	if ( ! $new && $args['hide_if_zero'] ) {
		$args['class'] = $args['class'] . ' fep-hide';
	}
	$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
	if ( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ) {
		$ret .= '<span class="fep_unread_message_count_hide_if_zero fep-hide">)</span>';
	} elseif ( $args['show_bracket'] && $args['hide_if_zero'] ) {
		$ret .= '<span class="fep_unread_message_count_hide_if_zero">)</span>';
	} elseif ( $args['show_bracket'] ) {
		$ret .= ' )';
	}
	return $ret;
}

function fep_get_new_announcement_number() {
	return fep_get_user_announcement_count( 'unread' );
}

function fep_get_new_announcement_button( $args = array() ) {
	if ( ! fep_current_user_can( 'access_message' ) ) {
		return '';
	}
	$args = wp_parse_args( $args, array(
		'show_bracket'	=> '1',
		'hide_if_zero'	=> '1',
		'ajax'			=> '1',
		'class'			=> 'fep-font-red',
	) );
	$args['class'] = fep_sanitize_html_class( $args['class'] );
	$new           = number_format_i18n( fep_get_new_announcement_number() );
	if ( empty( $args['ajax'] ) ) {
		if ( ! $new && $args['hide_if_zero'] ) {
			return '';
		}
		$ret = '';
		if ( $args['show_bracket'] ) {
			$ret .= '( ';
		}
		$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
		if ( $args['show_bracket'] ) {
			$ret .= ' )';
		}
		return $ret;
	}
	wp_enqueue_script( 'fep-notification-script' );
	$args['class'] = $args['class'] . ' fep_unread_announcement_count';
	if ( $args['hide_if_zero'] ) {
		$args['class'] = $args['class'] . ' fep_unread_announcement_count_hide_if_zero';
	}
	$ret = '';
	if ( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ) {
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero fep-hide">(</span>';
	} elseif ( $args['show_bracket'] && $args['hide_if_zero'] ) {
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero">(</span>';
	} elseif ( $args['show_bracket'] ) {
		$ret .= '( ';
	}
	if ( ! $new && $args['hide_if_zero'] ) {
		$args['class'] = $args['class'] . ' fep-hide';
	}
	$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';

	if ( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ) {
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero fep-hide">)</span>';
	} elseif ( $args['show_bracket'] && $args['hide_if_zero'] ) {
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero">)</span>';
	} elseif ( $args['show_bracket'] ) {
		$ret .= ' )';
	}
	return $ret;
}

function fep_is_user_blocked( $login = '' ) {
	global $user_login;
	if ( ! $login && $user_login ) {
		$login = $user_login;
	}
	if ( $login ) {
		$wpusers = explode( ',', fep_get_option( 'have_permission' ) );
		$wpusers = array_map( 'trim', $wpusers );
		if ( in_array( $login, $wpusers) ) {
			return true;
		}
	} //User not logged in
	return false;
}

function fep_is_user_whitelisted( $login = '' ) {
	global $user_login;
	if ( ! $login && $user_login ) {
		$login = $user_login;
	}
	if ( $login ) {
		$wpusers = explode( ',', fep_get_option( 'whitelist_username' ) );
		$wpusers = array_map( 'trim', $wpusers );
		if ( in_array( $login, $wpusers) ) {
			return true;
		}
	} //User not logged in
	return false;
}

function fep_get_userdata( $data, $need = 'ID', $type = 'slug' ) {
	if ( ! $data ) {
		return '';
	}
	$type = strtolower( $type );
	if ( 'user_nicename' == $type ) {
		$type = 'slug';
	}
	if ( ! in_array( $type, array ( 'id', 'slug', 'email', 'login' ) ) ) {
		return '';
	}
	$user = get_user_by( $type , $data );
	if ( $user ) {
		return $user->$need;
	} else {
		return '';
	}
}
	
function fep_user_name( $id ) {
	$which = apply_filters( 'fep_filter_show_which_name', 'display_name' );
	switch ( $which ) {
		case 'first_last_name':
			$name = fep_get_userdata( $id, 'first_name', 'id' ) . ' ' . fep_get_userdata( $id, 'last_name', 'id' );
			break;
		case 'last_first_name':
			$name = fep_get_userdata( $id, 'last_name', 'id' ) . ' ' . fep_get_userdata( $id, 'first_name', 'id' );
			break;
		case 'first_name':
		case 'last_name':
		case 'user_login':
		case 'user_nicename':
			$name = fep_get_userdata( $id, $which, 'id' );
			break;
		case 'display_name':
			default:
			$name = fep_get_userdata( $id, 'display_name', 'id' );
			break;
	}
	return apply_filters( 'fep_filter_user_name', trim( $name ), $id );
}

function fep_get_user_message_count( $value = 'all', $force = false, $user_id = false ) {
	return Fep_Messages::init()->user_message_count( $value, $force, $user_id );
}

function fep_get_user_announcement_count( $value = 'all', $force = false, $user_id = false ) {
	return FEP_Announcements::init()->get_user_announcement_count( $value, $force, $user_id );
}

function fep_get_message( $id ) {
	return FEP_Message::get_instance( $id );
}

function fep_get_replies( $id ) {
	$args = array(
		'mgs_type'		=> 'message',
		'mgs_status'	=> 'publish',
		'mgs_parent'	=> $id,
		'per_page'		=> 0,
		'order'			=> 'ASC',
	);
	$args = apply_filters( 'fep_filter_get_replies', $args );
	return new FEP_Message_Query( $args );
}

function fep_get_attachments( $mgs_id = 0, $fields = '' ) {
	if ( '' !== $fields ) {
		_deprecated_argument( __FUNCTION__, '10.1.1' );
	}
	if ( ! $mgs_id ) {
		$mgs_id = fep_get_the_id();
	}
	if ( ! $mgs_id ) {
		return array();
	}
	return FEP_Attachments::init()->get( $mgs_id );
}
function fep_get_messages( $args = [] ){
	$args = wp_parse_args( $args, array(
		'count_total' => false,
	));
	$query =  new FEP_Message_Query( $args );
	return $query->get_results();
}

function fep_get_parent_id( $id ) {
	if ( ! $id ) {
		return 0;
	}
	$parent = $id;
	do {
		$message = FEP_Message::get_instance( $parent );
		if( $message ){
			$parent = $message->mgs_parent;
			$id = $message->mgs_id;
		} else {
			$parent = 0;
		}
	} while( $parent  );
	// climb up the hierarchy until we reach parent = 0
	return $id;
}

function fep_update_reply_info( $mgs_id ) {
	$updated = false;
	
	if ( ! $mgs_id || ! is_numeric( $mgs_id ) ) {
		return $updated;
	}
	$args = array(
		'mgs_type'      => 'message',
		'mgs_status'    => 'publish',
		'per_page'      => 1,
		'mgs_id'        => $mgs_id,
		'include_child' => true,
		'orderby'       => 'mgs_created',
		'order'         => 'DESC',
	);
	$messages = fep_get_messages( $args );
	if ( $messages && ! empty( $messages[0] ) && $message = FEP_Message::get_instance( $mgs_id ) ) {
		$updated = $message->update(
			array(
				'mgs_last_reply_by'      => $messages[0]->mgs_author,
				'mgs_last_reply_excerpt' => fep_get_the_excerpt_from_content( 100, $messages[0]->mgs_content ),
				'mgs_last_reply_time'    => $messages[0]->mgs_created,
			)
		);
	}
	return $updated;
}

function fep_format_date( $date ) {

	if ( '0000-00-00 00:00:00' === $date ) {
		$h_time = __( 'Unpublished', 'front-end-pm' );
	} else {
		$time = strtotime( $date );
		//$time = get_post_time( 'G', true, $post, false );
		if ( ( abs( $t_diff = time() - $time ) ) < DAY_IN_SECONDS ) {
			if ( $t_diff < 0 ) {
				$h_time = sprintf( __( '%s from now', 'front-end-pm' ), human_time_diff( $time ) );
			} else {
				$h_time = sprintf( __( '%s ago', 'front-end-pm' ), human_time_diff( $time ) );
			}
		} else {
			$h_time = mysql2date( get_option( 'date_format' ). ' ' .get_option( 'time_format' ), get_date_from_gmt( $date ) );
		}
	}
	return apply_filters( 'fep_formate_date', $h_time, $date );
}

function fep_sort_by_priority( $a, $b ) {
	if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
		return 0;
	}
	return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
}

function fep_pagination( $total = null, $per_page = null, $list_class = 'fep-pagination' ) {
	$total = apply_filters( 'fep_pagination_total', $total);
	if ( null === $per_page ) {
		$per_page = fep_get_option( 'messages_page', 15 );
	}
	$per_page = apply_filters( 'fep_pagination_per_page', $per_page );
	$last = ceil( absint( $total) / absint( $per_page ) );
	if ( $last <= 1 ) {
		return '';
	}
	// $numPgs = $total_message / fep_get_option( 'messages_page', 50 );
	$page = ( ! empty( $_GET['feppage'] ) ) ? absint( $_GET['feppage'] ) : 1;
	$links = ( isset( $_GET['links'] ) ) ? absint( $_GET['links'] ) : 2;
	$start = ( ( $page - $links ) > 0 ) ? $page - $links : 1;
	$end = ( ( $page + $links ) < $last ) ? $page + $links : $last;
	$html = '<div class="fep-align-centre"><ul class="' . $list_class . '">';
	$class = ( $page == 1 ) ? "disabled" : '';
	$html .= '<li class="' . $class . '"><a href="' . esc_url( add_query_arg( 'feppage', ( $page - 1 ) ) ) . '">&laquo;</a></li>';
	if ( $start > 1 ) {
		$html .= '<li><a href="' . esc_url( add_query_arg( 'feppage', 1 ) ) . '">' . number_format_i18n( 1 ) . '</a></li>';
		$html .= '<li class="disabled"><span>...</span></li>';
	}
	for ( $i = $start ; $i <= $end; $i++ ) {
		$class = ( $page == $i ) ? "active" : '';
		$html .= '<li class="' . $class . '"><a href="' . esc_url( add_query_arg( 'feppage', $i ) ) . '">' . number_format_i18n( $i ) . '</a></li>';
	}
	if ( $end < $last ) {
		$html .= '<li class="disabled"><span>...</span></li>';
		$html .= '<li><a href="' . esc_url( add_query_arg( 'feppage', $last ) ) . '">' . number_format_i18n( $last ) . '</a></li>';
	}
	$class = ( $page == $last ) ? "disabled" : '';
	$html .= '<li class="' . $class . '"><a href="' . esc_url( add_query_arg( 'feppage', ( $page + 1 ) ) ) . '">&raquo;</a></li>';
	$html .= '</ul></div>';
	return $html;
}

function fep_pagination_prev_next( $has_more_row ) {
	$feppage = ! empty( $_GET['feppage'] ) ? absint( $_GET['feppage'] ) : 1;
	
	if ( $feppage > 1 || $has_more_row ) :
	?>
	<div class="fep_pagination_prev_next fep-align-centre">
		<ul class="fep-pagination fep-pagination-ul">
			<li class="fep-pagination-li<?php echo ( 1 === $feppage ) ? ' disabled' : ''; ?>">
				<a class="fep-pagination-a" data-fep_action="<?php echo ( 1 === $feppage ) ? '' : 'prev'; ?>" href="<?php echo esc_url( add_query_arg( 'feppage', $feppage - 1 ) ); ?>" title="<?php esc_attr_e( 'Previous', 'front-end-pm' ); ?>">&laquo;</a>
			</li>
			<li class="fep-pagination-li active"><span class="fep-pagination-span"><?php echo number_format_i18n( $feppage ); ?></span>
			</li>
			<li class="fep-pagination-li<?php echo ( ! $has_more_row ) ? ' disabled' : ''; ?>">
				<a class="fep-pagination-a" data-fep_action="<?php echo ( ! $has_more_row ) ? '' : 'next'; ?>" href="<?php echo esc_url( add_query_arg( 'feppage', $feppage + 1 ) ); ?>" title="<?php esc_attr_e( 'Next', 'front-end-pm' ); ?>">&raquo;</a>
			</li>
		</ul>
	</div>
	<?php
	endif;
}

function fep_is_user_admin( $user_id = 0 ) {
	$admin_cap = apply_filters( 'fep_admin_cap', 'manage_options' );
	if ( $user_id ) {
		return user_can( $user_id, $admin_cap );
	}
	return current_user_can( $admin_cap );
}

function fep_current_user_can( $cap, $id = false ) {
	$can = false;
	if ( ! is_user_logged_in() || fep_is_user_blocked() ) {
		return apply_filters( 'fep_current_user_can', $can, $cap, $id );
	}
	$no_role_access = apply_filters( 'fep_no_role_access', false, $cap, $id );
	$roles = wp_get_current_user()->roles;
	switch ( $cap ) {
		case has_filter( "fep_current_user_can_{$cap}" ):
			$can = apply_filters( "fep_current_user_can_{$cap}", $can, $cap, $id );
			break;
		case 'access_message':
			if ( fep_is_user_whitelisted() || array_intersect( fep_get_option( 'userrole_access', array() ), $roles ) || ( ! $roles && $no_role_access ) ) {
				$can = true;
			}
			break;
		case 'send_new_message':
			if ( fep_is_user_whitelisted() || array_intersect( fep_get_option( 'userrole_new_message', array() ), $roles ) || ( ! $roles && $no_role_access ) ) {
				$can = true;
			}
			break;
		case 'send_new_message_to':
			if ( is_numeric( $id ) ) {
				// $id == user ID
				if ( $id && $id != get_current_user_id() && fep_current_user_can( 'access_message' ) && fep_current_user_can( 'send_new_message' ) && ( fep_is_user_whitelisted() || ( fep_get_user_option( 'allow_messages', 1, $id ) && ! fep_is_user_blocked_for_user( $id ) ) ) ) {
					$can = true;
				}
				// $id == user_nicename
				// Backward compability ( do not use )
			} elseif ( $id && $id != fep_get_userdata( get_current_user_id(), 'user_nicename', 'id' ) && fep_current_user_can( 'access_message' ) && fep_current_user_can( 'send_new_message' ) && ( fep_is_user_whitelisted() || ( fep_get_user_option( 'allow_messages', 1, fep_get_userdata( $id ) ) && ! fep_is_user_blocked_for_user( fep_get_userdata( $id ) ) ) ) ) {
				$can = true;
			}
			break;
		case 'send_reply':
			if ( ! $id || fep_get_message_status( $id ) !== 'publish' ) {
			} elseif ( fep_is_user_whitelisted() || fep_is_user_admin() ) {
				$can = true;
			} elseif ( in_array( get_current_user_id(), fep_get_participants( $id, true ) ) && ( array_intersect( fep_get_option( 'userrole_reply', array() ), $roles ) || ( ! $roles && $no_role_access ) ) ) {
				$can = true;
			}
			if ( $can ) {
				$participants = FEP_Participants::init()->get( $id );
				foreach ( $participants as $participant ) {
					if ( $participant->mgs_deleted && ! fep_get_option( 'reply_deleted_mgs' ) ) {
						$can = false;
						break;
					}
					if ( ! fep_is_user_whitelisted() && fep_is_user_blocked_for_user( $participant->mgs_participant ) ) {
						$can = false;
						break;
					}
				}
			}
			break;
		case 'view_message':
		case 'view_announcement':
			if ( $id && ( ( in_array( get_current_user_id(), fep_get_participants( $id, true ) ) && fep_get_message_status( $id ) == 'publish' ) || fep_is_user_admin() || fep_is_user_whitelisted() ) ) {
				$can = true;
			}
			break;
		case 'delete_message': // only for himself
		case 'delete_announcement': // only for himself
			if ( $id && in_array( get_current_user_id(), fep_get_participants( $id, true ) ) && fep_get_message_status( $id ) == 'publish' ) {
				$can = true;
			}
			break;
		case 'access_directory':
			if ( fep_is_user_admin() || fep_is_user_whitelisted() || fep_get_option( 'show_directory', 1 ) ) {
				$can = true;
			}
			break;
		case 'add_announcement':
			if ( fep_is_user_admin() ) {
				$can = true;
			}
			break;
		default :
			break;
	}
	return apply_filters( 'fep_current_user_can', $can, $cap, $id );
}

function fep_is_read( $parent = false, $mgs_id = false, $user_id = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $mgs_id ) {
		$mgs_id = fep_get_the_id();
	}
	if ( ! $mgs_id || ! $user_id ) {
		return false;
	}
	$participant = FEP_Participants::init()->get( $mgs_id, $user_id );
	$return = 0;
	if( $participant ){
		if( $parent ){
			$return = $participant->mgs_parent_read;
		} else {
			$return = $participant->mgs_read;
		}
	}
	return (int) $return;
}

function fep_make_read( $parent = false, $mgs_id = false, $user_id = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $mgs_id ) {
		$mgs_id = fep_get_the_id();
	}
	if ( ! $mgs_id || ! $user_id ) {
		return false;
	}
	$return = false;
	
	if( $parent ){
		$return = FEP_Participants::init()->mark( $mgs_id, $user_id, ['parent_read' => true ] );
	} else {
		$return = FEP_Participants::init()->mark( $mgs_id, $user_id, ['read' => true ] );
	}
	return $return;
}

function fep_get_the_excerpt_from_content( $count = 100, $excerpt = '' ) {

	$excerpt = strip_shortcodes( $excerpt );
	$excerpt = wp_strip_all_tags( $excerpt );
	$excerpt = substr( $excerpt, 0, $count );
	$excerpt = substr( $excerpt, 0, strripos( $excerpt, ' ' ) );
	$excerpt = $excerpt. ' ... ';
	return apply_filters( 'fep_get_the_excerpt_from_content', $excerpt, $count );
}

function fep_get_current_user_max_message_number() {
	$roles = wp_get_current_user()->roles;
	$count_array = array();
	if ( $roles && is_array( $roles ) ) {
		foreach ( $roles as $role ) {
			$count = fep_get_option( "message_box_{$role}", 50 );
			if ( ! $count ) {
				return 0;
			}
			$count_array[] = $count;
		}
	}
	if ( $count_array ) {
		return max( $count_array);
	} else {
		return 0; // FIX ME. 0 = unlimited !!!!
	}
}

function fep_add_email_filters( $for = 'message' ) {
	do_action( 'fep_action_after_add_email_filters', $for );
}

function fep_remove_email_filters( $for = 'message' ) {
	do_action( 'fep_action_after_remove_email_filters', $for );
}

function fep_delete_message( $mgs_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $mgs_id || ! $user_id ) {
		return false;
	}

	$return = FEP_Participants::init()->mark( $mgs_id, $user_id, ['delete' => true ] );
	
	$should_delete_from_db = true;
	foreach ( FEP_Participants::init()->get( $mgs_id ) as $participant ) {
		if ( ! $participant->mgs_deleted ) {
			$should_delete_from_db = false;
			break;
		}
	}
	if ( apply_filters( 'fep_filter_delete_from_db', $should_delete_from_db, $mgs_id ) ) {
		$args = [
			'mgs_id' => $mgs_id,
			'per_page' => 0, //unlimited
			'mgs_status' => 'any',
		];
		if( 'threaded' == fep_get_message_view() && apply_filters( 'fep_erase_replies_if_threaded', true ) ){
			$args['include_child'] = true;
		}
		$messages = fep_get_messages( $args );
		foreach( $messages as $message ){
			$message->delete();
		}
	}
	return $return;
}

function fep_send_message( $message = null, $override = array() ) {
	if ( null === $message ) {
		$message = $_POST;
	}
	if ( ! empty( $message['fep_parent_id'] ) ) {
		$message['mgs_parent'] = absint( $message['fep_parent_id'] );
		$message['mgs_status'] = fep_get_option( 'reply_post_status', 'publish' );
		$message['message_title'] = __( 'RE:', 'front-end-pm' ). ' ' . wp_slash( fep_get_message_field( 'mgs_title', $message['mgs_parent'] ) );
		$message['message_to_id'] = fep_get_participants( $message['mgs_parent'], ! fep_get_option( 'reply_deleted_mgs' ) );
	} else {
		$message['mgs_status'] = fep_get_option( 'parent_post_status','publish' );
		$message['mgs_parent'] = 0;
	}
	$message = apply_filters( 'fep_filter_message_before_send', $message );
	if ( empty( $message['message_title'] ) || empty( $message['message_content'] ) ) {
		return false;
	}
	// Create post array
	$post = array(
		'mgs_title'	=> $message['message_title'],
		'mgs_content'	=> $message['message_content'],
		'mgs_status'	=> $message['mgs_status'],
		'mgs_parent'	=> $message['mgs_parent'],
		'mgs_type'		=> 'message',
		'mgs_author'	=> get_current_user_id(),
		'mgs_created'	=> current_time( 'mysql', true ),
	);
	
	if ( $override && is_array( $override ) ) {
		$post = wp_parse_args( $override, $post );
	}
	if( ! $post['mgs_parent'] && 'threaded' === fep_get_message_view() ){
		$post['mgs_last_reply_by'] = $post['mgs_author'];
		$post['mgs_last_reply_excerpt'] = fep_get_the_excerpt_from_content( 100, $post['mgs_content'] );
		$post['mgs_last_reply_time'] = $post['mgs_created'];
	}
	
	$post = apply_filters( 'fep_filter_message_after_override', $post, $message );

	$post = wp_unslash( $post );
	
	$new_message = new FEP_Message;
	$message_id = $new_message->insert( $post );
	// Insert the message into the database
	if ( ! $message_id  ) {
		return false;
	}
	/*
	$inserted_message = FEP_Message::get_instance( $message_id );
	if( ! $inserted_message ){
		return false;
	}
	*/
	if( ! empty( $message['message_to_id'] ) ){
		$message['message_to_id'] = (array) $message['message_to_id'];
		$message['message_to_id'][] = $new_message->mgs_author;
		$new_message->insert_participants( $message['message_to_id'] );
	}
	if ( is_multisite() ) {
		fep_add_meta( $message_id, '_fep_blog_id', get_current_blog_id() );
	}
	do_action( 'fep_action_message_after_send', $message_id, $message, $new_message );
	do_action( 'fep_action_message_after_sent', $message_id, $message, $new_message );
	
	fep_status_change( 'new', $new_message );
	
	return $message_id;
}

function fep_status_change( $old_status, $message ){
	if( ! $old_status || ! is_object( $message ) ){
		return false;
	}
	$new_status = $message->mgs_status;
	
	if( $old_status == $new_status ){
		return false;
	}
	do_action( "fep_transition_post_status", $new_status, $old_status, $message );
	
	do_action( "fep_status_{$old_status}_to_{$new_status}", $message );
	
	do_action( "fep_status_to_{$new_status}", $message, $old_status );
	
	do_action( "fep_transition_{$message->mgs_type}_status", $new_status, $old_status, $message );
	
	do_action( "fep_{$message->mgs_type}_status_{$old_status}_to_{$new_status}", $message );
	
	do_action( "fep_{$message->mgs_type}_status_to_{$new_status}", $message, $old_status );
}

function fep_delete_counts_cache( $new_status, $old_status, $message ) {
	wp_cache_delete( $message->mgs_type, 'fep_counts' );
}

function fep_send_message_transition_post_status( $new_status, $old_status, $message ) {
	if ( 'message' != $message->mgs_type ) {
		return;
	}

	if ( 'new' === $old_status ) {
		if ( 'threaded' === fep_get_message_view() && $message->mgs_parent ) {
			$unmark = [
				'parent_read' => true,
			];
			if ( fep_get_option( 'reply_deleted_mgs' ) ) {
				$unmark['delete'] = true;
			}
			FEP_Participants::init()->unmark( $message->mgs_parent, false, $unmark );
			FEP_Participants::init()->mark( $message->mgs_parent, $message->mgs_author, [ 'parent_read' => true ] );
		}
		FEP_Participants::init()->mark( $message->mgs_id, $message->mgs_author, [ 'parent_read' => true ] );
	}
	if ( 'publish' == $new_status || 'publish' == $old_status ) {
		if( 'threaded' === fep_get_message_view() && $message->mgs_parent ) {
			fep_update_reply_info( $message->mgs_parent );
		}
		$participants = fep_get_participants( $message->mgs_id, true );
		foreach ( $participants as $participant ) {
			delete_user_meta( $participant, '_fep_user_message_count' );
			if ( $participant != $message->mgs_author && 'publish' == $new_status ) {
				delete_user_meta( $participant, '_fep_notification_dismiss' );
			}
		}
	}
}

function fep_add_announcement( $announcement = null, $override = array() ) {
	if ( null === $announcement ) {
		$announcement = $_POST;
	}
	$announcement = apply_filters( 'fep_filter_announcement_before_added', $announcement );
	if ( empty( $announcement['message_title'] ) || empty( $announcement['message_content'] ) ) {
		return false;
	}
	// Create post array
	$post = array(
		'mgs_title'	=> $announcement['message_title'],
		'mgs_content'	=> $announcement['message_content'],
		'mgs_status'	=> 'publish',
		'mgs_type'		=> 'announcement',
		'mgs_author'	=> get_current_user_id(),
		'mgs_created'	=> current_time( 'mysql', true ),
	);

	if ( $override && is_array( $override ) ) {
		$post = wp_parse_args( $override, $post );
	}
	$post = apply_filters( 'fep_filter_announcement_after_override', $post, $announcement );

	$post = wp_unslash( $post );
	
	$new_message = new FEP_Message;
	$announcement_id = $new_message->insert( $post );

	// Insert the message into the database
	if ( ! $announcement_id ) {
		return false;
	}
	/*
	$inserted_announcement = FEP_Message::get_instance( $announcement_id );
	if( ! $inserted_announcement ){
		return false;
	}
	*/
	if ( ! empty( $announcement['announcement_roles'] ) && is_array( $announcement['announcement_roles'] ) ) {
		$user_ids = get_users( [ 'fields' => 'ids', 'role__in' => $announcement['announcement_roles'] ] );
		$user_ids[] = $new_message->mgs_author;
		
		$user_ids = apply_filters( 'fep_filter_announcement_participant_ids', $user_ids, $announcement_id, $announcement, $new_message );
		
		$new_message->insert_participants( $user_ids );
		
		foreach( $announcement['announcement_roles'] as $role ){
			fep_add_meta( $announcement_id, '_fep_participant_roles', $role );
		}
	}
	if ( is_multisite() ) {
		fep_add_meta( $announcement_id, '_fep_blog_id', get_current_blog_id() );
	}
	
	FEP_Participants::init()->mark( $new_message->mgs_id, $new_message->mgs_author, ['read' => true, 'parent_read' => true ] );

	do_action( 'fep_action_announcement_after_added', $announcement_id, $announcement, $new_message );
	
	fep_status_change( 'new', $new_message );
	
	return $announcement_id;
}

function fep_backticker_encode( $text ) {
	$text = $text[1];
	$text = str_replace( '&amp;lt;', '&lt;', $text );
	$text = str_replace( '&amp;gt;', '&gt;', $text );
	$text = htmlspecialchars( $text, ENT_QUOTES );
	$text = preg_replace( '|\n+|', '\n', $text );
	$text = nl2br( $text );
	$text = str_replace( '\t', '&nbsp;&nbsp;&nbsp;&nbsp;', $text );
	$text = preg_replace( '/^ /', '&nbsp;', $text );
	$text = preg_replace( '/(?<=&nbsp;| |\n) /', '&nbsp;', $text );
	return "<code>$text</code>";
}

function fep_backticker_display_code( $text ) {
	//$text = preg_replace_callback( "|`(.*?)`|", "fep_backticker_encode", $text );
	$text = preg_replace_callback( '!`(?:\r\n|\n|\r|)(.*?)(?:\r\n|\n|\r|)`!ims', 'fep_backticker_encode', $text );
	$text = str_replace( '<code></code>', '`', $text );
	return $text;
}

function fep_backticker_code_input_filter( $message ) {
	$message['message_content'] = fep_backticker_display_code( $message['message_content'] );
	return $message;
}

function fep_footer_credit() {
	$style = '';
	if ( ! fep_get_option( 'show_branding', 1 ) ) {
		$style = ' style="display:none;"';
	}
	echo '<div' . $style . '><a href="https://www.shamimsplugins.com/products/front-end-pm-pro/" target="_blank">Front End PM</a></div>';
}

function fep_notification_div() {
	if ( ! fep_current_user_can( 'access_message' ) ) {
		return;
	}
	if ( ! fep_get_option( 'show_notification', 1 ) ) {
		return;
	}
	wp_enqueue_script( 'fep-notification-script' );
	$unread_count = fep_get_new_message_number();
	$sm = sprintf( _n( '%s message', '%s messages', $unread_count, 'front-end-pm' ), number_format_i18n( $unread_count ) );
	$unread_ann_count = fep_get_new_announcement_number();
	$sa = sprintf( _n( '%s announcement', '%s announcements', $unread_ann_count, 'front-end-pm' ), number_format_i18n( $unread_ann_count ) );
	$class = 'fep-notification-bar';
	if ( ! $unread_count && ! $unread_ann_count ) {
		$class .= ' fep-hide';
	} elseif ( get_user_meta( get_current_user_id(), '_fep_notification_dismiss', true ) ) {
		$class .= ' fep-hide';
	}
	$show = '<div id="fep-notification-bar" class="' . $class . '"><p>';
	$show .= __( 'You have', 'front-end-pm' );
	$class = 'fep_unread_message_count_hide_if_zero';
	if ( ! $unread_count ) {
		$class .= ' fep-hide';
	}
	$show .= '<span class="' . $class . '"> <a href="' . fep_query_url( 'messagebox' ) . '"><span class="fep_unread_message_count_text">' . $sm . '</span></a></span>';
	$class = 'fep_hide_if_anyone_zero';
	if ( ! $unread_count || ! $unread_ann_count ) {
		$class .= ' fep-hide';
	}
	$show .= '<span class="' . $class . '"> ' . __( 'and', 'front-end-pm' ) . '</span>';
	$class = 'fep_unread_announcement_count_hide_if_zero';
	if ( ! $unread_ann_count ) {
		$class .= ' fep-hide';
	}
	$show .= '<span class="' . $class . '"> <a href="' . fep_query_url( 'announcements' ) . '"><span class="fep_unread_announcement_count_text">' . $sa . '</span></a></span>';
	$show .= ' ';
	$show .= __( 'unread', 'front-end-pm' );
	$show .= '</p>';
	$show .= '<button aria-label="' . esc_attr( 'Dismiss notice', 'front-end-pm' ) . '" class="fep-notice-dismiss">×</button>';
	$show .= '</div>';
	echo apply_filters( 'fep_header_notification', $show );
}

function fep_auth_redirect() {
	if ( ! fep_page_id() || ( ! is_page( fep_page_id() ) && ! is_single( fep_page_id() ) ) ) {
		return;
	}
	do_action( 'fep_template_redirect' );
	if ( apply_filters( 'fep_using_auth_redirect', false ) ) {
		auth_redirect();
	}
}

function fep_auth_redirect_scheme( $scheme ) {
	if ( ! apply_filters( 'fep_using_auth_redirect', false ) ) {
		return $scheme;
	}
	if ( is_admin() || ! fep_page_id() || ( ! is_page( fep_page_id() ) && ! is_single( fep_page_id() ) ) ) {
		return $scheme;
	}
	return 'logged_in';
}

function fep_array_trim( $array ) {
	if ( ! is_array( $array ) ) {
		return trim( $array );
	}
	return array_map( 'fep_array_trim', $array );
}

function fep_is_pro() {
	return file_exists( FEP_PLUGIN_DIR . 'pro/pro-features.php' );
}

function fep_errors() {
	static $errors; // Will hold global variable safely
	return isset( $errors ) ? $errors : ( $errors = new WP_Error() );
}

function fep_success() {
	static $success; // Will hold global variable safely
	return isset( $success ) ? $success : ( $success = new WP_Error() );
}

function fep_info_output() {
	do_action( 'fep_action_info_output' );
	
	$html = '';
	if ( fep_success()->get_error_messages() ) {
		$html .= '<div class="fep-success">';
		foreach ( fep_success()->get_error_messages() as $s ) {
			$html .= esc_html( $s ). '<br />';
		}
		$html .= '</div>';
	}
	if ( fep_errors()->get_error_messages() ) {
		$html .= '<div class="fep-wp-error">';
		foreach ( fep_errors()->get_error_messages() as $e ) {
			$html .= '<strong>' . __( 'Error', 'front-end-pm' ) . ': </strong>' . esc_html( $e ) . '<br />';
		}
		$html .= '</div>';
	}
	return $html;
}

function fep_locate_template( $template_names, $load = false, $require_once = true ) {
	$locations = array();
	$locations[10] = trailingslashit( STYLESHEETPATH ) . 'front-end-pm/';
	$locations[20] = trailingslashit( TEMPLATEPATH ) . 'front-end-pm/';
	$locations[30] = FEP_PLUGIN_DIR . 'pro/templates/';
	$locations[40] = FEP_PLUGIN_DIR . 'templates/';
	$locations = apply_filters( 'fep_template_locations', $locations );

	// sort the $locations based on priority
	ksort( $locations, SORT_NUMERIC );
	$template = '';
	if ( ! is_array( $template_names ) ) {
		$template_names = explode( ',', $template_names );
	}
	foreach ( $template_names as $template_name ) {
		$template_name = trim( $template_name );
		if ( empty( $template_name ) ) {
			continue;
		}
		if ( strpos( $template_name, '../' ) !== false || strpos( $template_name, '..\\' ) !== false ) {
			continue;
		}
		foreach ( $locations as $location ) {
			if ( file_exists( $location . $template_name ) ) {
				$template = $location . $template_name;
				break 2;
			}
		}
	}
	if ( ( true == $load ) && ! empty( $template ) ) {
		load_template( $template, $require_once );
	}
	return apply_filters( 'fep_locate_template', $template, $template_names, $load, $require_once );
}

function fep_form_posted() {
	$action = ! empty( $_POST['fep_action'] ) ? $_POST['fep_action'] : '';
	if ( ! $action ) {
		return;
	}
	if ( ! fep_current_user_can( 'access_message' ) ) {
		return;
	}
	$menu = Fep_Menu::init()->get_menu();
	switch ( $action ) {
		case has_action( "fep_posted_action_{$action}" ):
			do_action( "fep_posted_action_{$action}" );
			break;
		case ( 'newmessage' == $action && ! empty( $menu['newmessage'] ) ):
		case 'shortcode-newmessage':
			if ( ! fep_current_user_can( 'send_new_message' ) ) {
				fep_errors()->add( 'permission', __( 'You do not have permission to send new message!', 'front-end-pm' ) );
				break;
			}
			Fep_Form::init()->validate_form_field( $action );
			if ( count( fep_errors()->get_error_messages() ) == 0 ) {
				if ( $message_id = fep_send_message() ) {
					if ( 'publish' == fep_get_message_status( $message_id ) ) {
						fep_success()->add( 'publish', __( 'Message successfully sent.', 'front-end-pm' ) );
					} else {
						fep_success()->add( 'pending', __( 'Message successfully sent and waiting for admin moderation.', 'front-end-pm' ) );
					}
				} else {
					fep_errors()->add( 'undefined', __( 'Something wrong. Please try again.', 'front-end-pm' ) );
				}
			}
			break;
		case 'reply':
			$parent_id = isset( $_POST['fep_parent_id'] ) ? absint( $_POST['fep_parent_id'] ) : 0;
			if ( ! fep_current_user_can( 'send_reply', $parent_id ) ) {
				fep_errors()->add( 'permission', __( 'You do not have permission to send reply to this message!', 'front-end-pm' ) );
				break;
			}
			Fep_Form::init()->validate_form_field( 'reply' );
			if ( 0 == count( fep_errors()->get_error_messages() ) ) {
				if ( $message_id = fep_send_message() ) {
					if ( 'publish' == fep_get_message_status( $message_id ) ) {
						fep_success()->add( 'publish', __( 'Message successfully sent.', 'front-end-pm' ) );
					} else {
						fep_success()->add( 'pending', __( 'Message successfully sent and waiting for admin moderation.', 'front-end-pm' ) );
					}
				} else {
					fep_errors()->add( 'undefined', __( 'Something wrong. Please try again.', 'front-end-pm' ) );
				}
			}
			break;
		case 'bulk_action':
		case 'announcement_bulk_action':
		case 'directory_bulk_action':
			$posted_bulk_action = ! empty( $_POST['fep-bulk-action'] ) ? $_POST['fep-bulk-action'] : '';
			if ( ! $posted_bulk_action ) {
				break;
			}
			$token = ! empty( $_POST['token'] ) ? $_POST['token'] : '';
			if ( ! fep_verify_nonce( $token, $action ) ) {
				fep_errors()->add( 'token', __( 'Invalid Token. Please try again!', 'front-end-pm' ) );
				break;
			}
			do_action( "fep_posted_bulk_{$action}", $posted_bulk_action );
			break;
		default:
			do_action( 'fep_posted_action' );
			break;
	}
	do_action( 'fep_posted_action_after', $action );
	
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$response = array();
		if ( count( fep_errors()->get_error_messages() ) > 0 ) {
			$response['fep_return'] = 'error';
		} elseif ( count( fep_success()->get_error_messages() ) > 0 ) {
			$response['fep_return'] = 'success';
		} else {
			$response['fep_return'] = '';
		}
		$response['info'] = fep_info_output();
		if( ! empty( $_POST['fep_redirect'] ) ) {
			$response['fep_redirect'] = wp_validate_redirect( wp_sanitize_redirect( $_POST['fep_redirect'] ) );
		}
		wp_send_json( $response );
	} elseif ( ! empty( $_POST['fep_redirect'] ) ) {
		wp_safe_redirect( $_POST['fep_redirect'] );
		exit;
	}
}

function fep_get_participants( $message_id, $exclude_deleted = false ) {
	if ( empty( $message_id ) || ! is_numeric( $message_id ) ) {
		return array();
	}

	$participants = FEP_Participants::init()->get( $message_id, false, $exclude_deleted );
	$return = [];
	
	foreach( $participants as $participant ){
		$return[] = $participant->mgs_participant;
	}
	return $return;
}

function fep_get_participant_roles( $announcement_id ) {

	$roles = fep_get_meta( $announcement_id, '_fep_participant_roles' );
	if( ! is_array( $roles ) ){
		$roles = [];
	}
	return $roles;
}

function fep_get_message_view() {
	$message_view = fep_get_option( 'message_view', 'threaded' );
	$message_view = apply_filters( 'fep_get_message_view', $message_view );
	if ( ! $message_view || ! in_array( $message_view, array( 'threaded', 'individual' ) ) ) {
		$message_view = 'threaded';
	}
	return $message_view;
}

function fep_get_blocked_users_for_user( $userid = '' ) {
	$return = array();
	if ( $blocked_users = fep_get_user_option( 'blocked_users', '', $userid ) ) {
		$blocked_users = explode( ',', $blocked_users );
		$return = array_filter( array_map( 'absint', $blocked_users ) );
	}
	return apply_filters( 'fep_get_blocked_users_for_user', $return, $userid );
}

function fep_is_user_blocked_for_user( $userid, $check_id = '' ) {
	$blocked_users = fep_get_blocked_users_for_user( $userid );
	if ( ! $check_id ) {
		$check_id = get_current_user_id();
	}
	if ( in_array( $check_id, $blocked_users ) ) {
		return true;
	}
	return false;
}

function fep_block_users_for_user( $user_ids, $userid = '' ) {
	if ( is_numeric( $user_ids ) ) {
		$user_ids = array( $user_ids );
	}
	if ( ! $user_ids || ! is_array( $user_ids ) ) {
		return 0;
	}
	$blocked_users = fep_get_blocked_users_for_user( $userid );
	$need_block = array_diff( $user_ids, $blocked_users );
	if ( $need_block ) {
		$blocked_users = array_unique( array_merge( $blocked_users, $need_block ) );
		fep_update_user_option( 'blocked_users', implode( ',', $blocked_users ) );
	}
	return count( $need_block );
}

function fep_unblock_users_for_user( $user_ids, $userid = '' ) {
	if ( is_numeric( $user_ids ) ) {
		$user_ids = array( $user_ids );
	}
	if ( ! $user_ids || ! is_array( $user_ids ) ) {
		return 0;
	}
	$blocked_users = fep_get_blocked_users_for_user( $userid = '' );
	$need_unblock = array_intersect( $blocked_users, $user_ids );
	if ( $need_unblock ) {
		$blocked_users = array_unique( array_diff( $blocked_users, $need_unblock ) );
		fep_update_user_option( 'blocked_users', implode( ',', $blocked_users ) );
	}
	return count( $need_unblock );
}

function fep_sanitize_html_class( $class ) {
	if ( $class ) {
		if ( ! is_array( $class ) ) {
			$class = explode( ' ', $class );
		}
		$class = array_map( 'sanitize_html_class', $class );
		$class = implode( ' ', array_unique( array_filter( $class ) ) );
	}
	if ( ! is_string( $class ) ) {
		$class = '';
	}
	return apply_filters( 'fep_sanitize_html_class', $class );
}

function fep_show_unread_count_in_title( $title ) {
	if ( fep_get_option( 'show_unread_count_in_title', 1 ) && fep_current_user_can( 'access_message' ) ) {
		wp_enqueue_script( 'fep-notification-script' );
		if ( $count = fep_get_new_message_number() ) {
			$count = number_format_i18n( $count );
			$title['title'] = "($count) " . $title['title'];
		}
	}
	return $title;
}

function fep_pre_get_document_title( $title ) {
	if ( ! empty( $title ) && fep_get_option( 'show_unread_count_in_title', 1 ) && fep_current_user_can( 'access_message' ) ) {
		wp_enqueue_script( 'fep-notification-script' );
		if ( $count = fep_get_new_message_number() ) {
			$count = number_format_i18n( $count );
			$title = "($count) " . $title;
		}
	}
	return $title;
}

function fep_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );
	return in_array( $function, $disabled );
}

//new

function fep_set_current_message( $message ){
	global $fep_message;
	if( $message instanceof FEP_Message ){
		$fep_message = $message;
	} else {
		$fep_message = null;
	}
	return $fep_message;
}

function fep_get_current_message(){
	global $fep_message;
	if( isset( $fep_message ) && ( $fep_message instanceof FEP_Message ) ){
		return $fep_message;
	} else {
		return null;
	}
}

function fep_get_message_field( $field, $mgs_id = 0 ){
	if( $mgs_id && is_numeric( $mgs_id ) ){
		$message = fep_get_message( $mgs_id );
	} else {
		$message = fep_get_current_message();
	}
	$value = false;
	if( $message && isset( $message->$field ) ){
		$value = $message->$field;
	}
	return apply_filters( 'fep_get_message_field', $value, $field, $mgs_id, $message );
}

function fep_get_the_id( $mgs_id = 0 ){
	$id = fep_get_message_field( 'mgs_id', $mgs_id );
	
	return apply_filters( 'fep_get_the_id', (int) $id, $mgs_id );
}

function fep_get_the_title( $mgs_id = 0 ){
	$title = fep_get_message_field( 'mgs_title', $mgs_id );
	
	return apply_filters( 'fep_get_the_title', $title, $mgs_id );
}

function fep_get_the_content( $mgs_id = 0 ){
	$content = fep_get_message_field( 'mgs_content', $mgs_id );
	
	return apply_filters( 'fep_get_the_content', $content, $mgs_id );
}

function fep_get_the_excerpt( $mgs_id = 0 ){
	if( 'threaded' === fep_get_message_view() && 'message' === fep_get_message_field( 'mgs_type', $mgs_id ) ){
		$excerpt = fep_get_message_field( 'mgs_last_reply_excerpt', $mgs_id );
	} else {
		$excerpt = fep_get_the_excerpt_from_content( 100, fep_get_message_field( 'mgs_content', $mgs_id ) );
	}
	
	return apply_filters( 'fep_get_the_excerpt', $excerpt, $mgs_id );
}

function fep_get_message_status( $mgs_id = 0 ){
	$status = fep_get_message_field( 'mgs_status', $mgs_id );
	
	return apply_filters( 'fep_get_message_status', $status, $mgs_id );
}

function fep_get_the_date( $which = 'created', $mgs_id = 0 ){
	if( 'created' == $which ){
		$field = 'mgs_created';
	} else {
		$field = 'mgs_last_reply_time';
	}
	$date = fep_get_message_field( $field, $mgs_id );
	if( ! $date ){
		$date = '0000-00-00 00:00:00';
	}

	return apply_filters( 'fep_get_the_date', $date, $which, $mgs_id );
}

function fep_participants_view( $mgs_id = 0 ) {
	$wp_roles = wp_roles();
	
	if( 'announcement' == fep_get_message_field( 'mgs_type', $mgs_id ) ){
		$roles = fep_get_participant_roles( fep_get_the_id( $mgs_id ) );
		foreach( $roles as $role ){
			if( $wp_roles->is_role( $role ) )
			 echo translate_user_role( $wp_roles->roles[ $role ]['name'] ) .'<br />';
		}
	} elseif( 'message' == fep_get_message_field( 'mgs_type', $mgs_id ) ){
		if( $group = apply_filters( 'fep_is_group_message', false, $mgs_id ) ){
			echo esc_html( $group );
		} elseif( $recipients = fep_get_participants( fep_get_the_id( $mgs_id ) ) ){
			foreach ( $recipients as $recipient ) {
				if( fep_get_message_field( 'mgs_author', $mgs_id ) != $recipient )
				echo fep_user_name( $recipient ) . '<br />';
			}
		}
	}
}

function fep_get_statuses( $for = 'message' ){
	$statuses = [
		'pending'  => __('Pending', 'front-end-pm' ),
		'publish'  => __('Publish', 'front-end-pm' ),
	];
	return apply_filters( 'fep_get_statuses', $statuses, $for );
}

function fep_recursive_remove_directory( $directory ) {
	foreach ( glob( "{$directory}/*" ) as $file ) {
		if ( is_dir( $file ) ) {
			fep_recursive_remove_directory( $file );
		} else {
			unlink( $file );
		}
	}
	rmdir( $directory );
}

function fep_fs_uninstall_cleanup() {
	global $wpdb;

	$fep_options = get_option( 'FEP_admin_options' );

	if ( is_array( $fep_options ) && ! empty( $fep_options['delete_data_on_uninstall'] ) ) {

		/** Delete all the Plugin Options */
		delete_option( 'FEP_admin_options' );
		delete_option( 'fep_updated_versions' );
		delete_site_option( 'fep_db_version' );
		
		delete_metadata( 'user', 0, 'FEP_user_options', '', true );
		delete_metadata( 'user', 0, '_fep_user_message_count', '', true );
		delete_metadata( 'user', 0, '_fep_user_announcement_count', '', true );
		delete_metadata( 'user', 0, '_fep_notification_dismiss', '', true );

		// Remove all database tables of Front End PM (if any).
		$fep_tables = [
			'message'      => defined( 'FEP_MESSAGE_TABLE' ) ? FEP_MESSAGE_TABLE : $wpdb->base_prefix . 'fep_messages',
			'meta'         => defined( 'FEP_META_TABLE' ) ? FEP_META_TABLE : $wpdb->base_prefix . 'fep_messagemeta',
			'participants' => defined( 'FEP_PARTICIPANT_TABLE' ) ? FEP_PARTICIPANT_TABLE : $wpdb->base_prefix . 'fep_participants',
			'attachments'  => defined( 'FEP_ATTACHMENT_TABLE' ) ? FEP_ATTACHMENT_TABLE : $wpdb->base_prefix . 'fep_attachments',
		];
		foreach ( $fep_tables as $fep_table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $fep_table" );
		}

		// Need to improve delete attachments files for multisite.
		if( ! is_multisite() ){
			$wp_upload_dir = wp_upload_dir();
			if ( $wp_upload_dir && false === $wp_upload_dir['error'] ) {
				fep_recursive_remove_directory( $wp_upload_dir['basedir'] . '/front-end-pm' );
			}
		}

		// Remove any transients we've left behind
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_fep\_%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_fep\_%'" );
	}
}

function fep_fs_support_forum_url( $wp_org_support_forum_url ) {
	return 'https://www.shamimsplugins.com/support/forum/front-end-pm-pro/';
}

