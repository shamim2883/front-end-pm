<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function fep_plugin_activate(){
	
	_deprecated_function( __FUNCTION__, '4.4' );
	//Deprecated in 4.4
	//Move inside Front_End_Pm class
	
	}

function fep_get_option( $option, $default = '', $section = 'FEP_admin_options' ) {
	
    $options = get_option( $section );
	
	if( ! is_array( $options ) )
		$options = array();
	
	$is_default = false;

    if ( isset( $options[$option] ) ) {
        $value = $options[$option];
    } else {
		$value = $default;
		$is_default = true;
	}

    return apply_filters('fep_get_option', $value, $option, $default, $is_default );
}

function fep_update_option( $option, $value = '', $section = 'FEP_admin_options' ) {
	
	if( empty( $option ) )
		return false;
	if( ! is_array( $option ) )
		$option = array( $option => $value );
	
    $options = get_option( $section );
	
	if( ! is_array( $options ) )
		$options = array();

    return update_option( $section, wp_parse_args( $option, $options ) );
}

function fep_get_user_option( $option, $default = '', $userid = '', $section = 'FEP_user_options' ) {
			
    $options = get_user_option( $section, $userid ); //if $userid = '' current user option will be return

    $is_default = false;

    if ( isset( $options[$option] ) ) {
        $value = $options[$option];
    } else {
		$value = $default;
		$is_default = true;
	}

    return apply_filters('fep_get_user_option', $value, $option, $default, $userid, $is_default );
}

function fep_update_user_option( $option, $value = '', $userid = '', $section = 'FEP_user_options' ) {
	
	if( empty( $option ) )
		return false;
		
	if( ! is_array( $option ) )
		$option = array( $option => $value );
	
	if( ! $userid )
	$userid = get_current_user_id();
	
    $options = get_user_option( $section, $userid );
	
	if( ! is_array( $options ) )
		$options = array();

    return update_user_option( $userid, $section, wp_parse_args( $option, $options ) );
}

if ( !function_exists('fep_get_plugin_caps') ) :

function fep_get_plugin_caps( $edit_published = false, $for = 'both' ){
	$message_caps = array(
		'delete_published_fep_messages' => 1,
		'delete_private_fep_messages' => 1,
		'delete_others_fep_messages' => 1,
		'delete_fep_messages' => 1,
		'publish_fep_messages' => 1,
		'read_private_fep_messages' => 1,
		'edit_private_fep_messages' => 1,
		'edit_others_fep_messages' => 1,
		'edit_fep_messages' => 1,
		'create_fep_messages' => 1,
		);
	
	$announcement_caps = array(
		'delete_published_fep_announcements' => 1,
		'delete_private_fep_announcements' => 1,
		'delete_others_fep_announcements' => 1,
		'delete_fep_announcements' => 1,
		'publish_fep_announcements' => 1,
		'read_private_fep_announcements' => 1,
		'edit_private_fep_announcements' => 1,
		'edit_others_fep_announcements' => 1,
		'edit_fep_announcements' => 1,
		'create_fep_announcements' => 1,
		);
	
	if( 'fep_message' == $for ) {
		$caps = $message_caps;
		if( $edit_published ) {
			$caps['edit_published_fep_messages'] = 1;
		}
	} elseif( 'fep_announcement' == $for ){
		$caps = $announcement_caps;
		if( $edit_published ) {
			$caps['edit_published_fep_announcements'] = 1;
		}
	} else {
		$caps = array_merge( $message_caps, $announcement_caps );
		if( $edit_published ) {
			$caps['edit_published_fep_messages'] = 1;
			$caps['edit_published_fep_announcements'] = 1;
		}
	}
	return $caps;
}

endif;

if ( !function_exists('fep_add_caps_to_roles') ) :

function fep_add_caps_to_roles( $roles = array( 'administrator', 'editor' ), $edit_published = true ) {

	if( ! is_array( $roles ) )
		$roles = array();
	
	$caps = fep_get_plugin_caps( $edit_published );
	
	foreach( $roles as $role ) {
		$role_obj = get_role( $role );
		if( !$role_obj )
			continue;
			
		foreach( $caps as $cap => $val ) {
			if( $val )
				$role_obj->add_cap( $cap );
		}
	}
}

endif;

if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) return;

add_action('after_setup_theme', 'fep_include_require_files');

function fep_include_require_files() {

	$fep_files = array(
			'announcement' 	=> FEP_PLUGIN_DIR. 'includes/class-fep-announcement.php',
			'attachment' 	=> FEP_PLUGIN_DIR. 'includes/class-fep-attachment.php',
			'cpt' 			=> FEP_PLUGIN_DIR. 'includes/class-fep-cpt.php',
			'directory' 	=> FEP_PLUGIN_DIR. 'includes/class-fep-directory.php',
			'email' 		=> FEP_PLUGIN_DIR. 'includes/class-fep-emails.php',
			'form' 			=> FEP_PLUGIN_DIR. 'includes/class-fep-form.php',
			'menu' 			=> FEP_PLUGIN_DIR. 'includes/class-fep-menu.php',
			'message' 		=> FEP_PLUGIN_DIR. 'includes/class-fep-message.php',
			'shortcodes' 	=> FEP_PLUGIN_DIR. 'includes/class-fep-shortcodes.php',
			'user-settings' => FEP_PLUGIN_DIR. 'includes/class-fep-user-settings.php',
			'main' 			=> FEP_PLUGIN_DIR. 'includes/fep-class.php',
			'widgets' 		=> FEP_PLUGIN_DIR. 'includes/fep-widgets.php'
			);
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$fep_files['ajax'] 	= FEP_PLUGIN_DIR. 'includes/class-fep-ajax.php';
	}
			
	if( is_admin() ) {
		$fep_files['settings'] 	= FEP_PLUGIN_DIR. 'admin/class-fep-admin-settings.php';
		$fep_files['update'] 	= FEP_PLUGIN_DIR. 'admin/class-fep-update.php';
		$fep_files['pro-info'] 	= FEP_PLUGIN_DIR. 'admin/class-fep-pro-info.php';
	}			
					
	$fep_files = apply_filters('fep_include_files', $fep_files );
	
	foreach ( $fep_files as $fep_file ) {
			require_once( $fep_file );
		}
}

function fep_plugin_update(){
	
	_deprecated_function( __FUNCTION__, '4.9', 'Fep_Update class' );
	
	$prev_ver = fep_get_option( 'plugin_version', '4.1' );
	
	if( version_compare( $prev_ver, FEP_PLUGIN_VERSION, '!=' ) ) {
		
		do_action( 'fep_plugin_update', $prev_ver );
		
		fep_update_option( 'plugin_version', FEP_PLUGIN_VERSION );
	}

}

function fep_plugin_update_from_first( $prev_ver ){
	
	if( is_admin() && '4.1' == $prev_ver ) { //any previous version of 4.1 also return 4.1
		fep_plugin_activate();
	}

}
add_action( 'fep_plugin_update', 'fep_plugin_update_from_first' );

add_action('after_setup_theme', 'fep_translation');

function fep_translation()
	{
	//SETUP TEXT DOMAIN FOR TRANSLATIONS
	load_plugin_textdomain('front-end-pm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

add_action('wp_enqueue_scripts', 'fep_enqueue_scripts');
	
function fep_enqueue_scripts()
    {
	
	wp_register_style( 'fep-common-style', FEP_PLUGIN_URL . 'assets/css/common-style.css', array(), '7.1' );
	wp_register_style( 'fep-style', FEP_PLUGIN_URL . 'assets/css/style.css', array(), '7.1' );
	wp_register_style( 'fep-tokeninput-style', FEP_PLUGIN_URL . 'assets/css/token-input-facebook.css' );
	
	if( 'always' == fep_get_option('load_css','only_in_message_page') ){
		wp_enqueue_style( 'fep-style' );
	} elseif( 'only_in_message_page' == fep_get_option('load_css','only_in_message_page') && fep_page_id() && ( is_page( fep_page_id() ) || is_single( fep_page_id() ) ) ){
		wp_enqueue_style( 'fep-style' );
	}
	wp_enqueue_style( 'fep-common-style' );
	
	$custom_css = '#fep-wrapper{';
	$custom_css .= 'background-color:' . fep_get_option('bg_color') . ';';
	$custom_css .= 'color:' . fep_get_option('text_color','#000000') . ';';
	$custom_css .= '}';
	$custom_css .= '#fep-wrapper a:not(.fep-button,.fep-button-active){color:' . fep_get_option('link_color','#000080') . ';}';
	$custom_css .= '.fep-button{';
	$custom_css .= 'background-color:' . fep_get_option('btn_bg_color', '#F0FCFF') . ';';
	$custom_css .= 'color:' . fep_get_option('btn_text_color','#000000') . ';';
	$custom_css .= '}';
	$custom_css .= '.fep-button:hover,.fep-button-active{';
	$custom_css .= 'background-color:' . fep_get_option('active_btn_bg_color','#D3EEF5') . ';';
	$custom_css .= 'color:' . fep_get_option('active_btn_text_color','#000000') . ';';
	$custom_css .= '}';
	$custom_css .= '.fep-odd-even > div:nth-child(odd){background-color:' . fep_get_option('odd_color','#F2F7FC') . ';}';
	$custom_css .= '.fep-odd-even > div:nth-child(even){background-color:' . fep_get_option('even_color','#FAFAFA') . ';}';
	$custom_css .= '.fep-message .fep-message-title-heading, .fep-per-message .fep-message-title{background-color:' . fep_get_option('mgs_heading_color','#F2F7FC') . ';}';
	$custom_css .= trim( stripslashes(fep_get_option('custom_css') ) );
	if( $custom_css ) {
		wp_add_inline_style( 'fep-common-style', $custom_css );
	}
	
	wp_register_script( 'fep-script', FEP_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '3.1', true );
	wp_localize_script( 'fep-script', 'fep_script', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce('fep-autosuggestion')
			) 
		);
		
	wp_register_script( 'fep-notification-script', FEP_PLUGIN_URL . 'assets/js/notification.js', array( 'jquery' ), '7.2', true );
	$call_on_ready = ( isset($_GET['fepaction']) &&
		( ( $_GET['fepaction'] == 'viewmessage' && fep_get_new_message_number() ) || ( $_GET['fepaction'] == 'view_announcement' && fep_get_new_announcement_number() ) ) 
		) ? '1' : '0';
	wp_localize_script( 'fep-notification-script', 'fep_notification_script', 
			apply_filters( 'fep_filter_notification_script_localize', array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce('fep-notification'),
				'interval' => apply_filters( 'fep_filter_ajax_notification_interval', MINUTE_IN_SECONDS * 1000 ),
				'skip' => apply_filters( 'fep_filter_skip_notification_call', 2 ), //How many times notification ajax call will be skipped if browser tab not opened
				'show_in_title'	=> fep_get_option( 'show_unread_count_in_title', '1' ),
				'show_in_desktop'	=> fep_get_option( 'show_unread_count_in_desktop', '1' ),
				'call_on_ready'	=> apply_filters( 'fep_filter_notification_call_on_ready', $call_on_ready ),
				'play_sound'	=> fep_get_option( 'play_sound', '1' ),
				'sound_url'	=> FEP_PLUGIN_URL . 'assets/audio/plucky.mp3',
				'icon_url'	=> FEP_PLUGIN_URL . 'assets/images/desktop-notification-32.png',
				'mgs_notification_title'=> __('New Message.', 'front-end-pm'),
				'mgs_notification_body'	=> __('You have received a new message.', 'front-end-pm'),
				'mgs_notification_url'	=> fep_query_url( 'messagebox' ),
				'ann_notification_title'=> __('New Announcement.', 'front-end-pm'),
				'ann_notification_body'	=> __('You have received a new announcement.', 'front-end-pm'),
				'ann_notification_url'	=> fep_query_url( 'announcements' ),
			))
		);
	
	
	wp_register_script( 'fep-replies-show-hide', FEP_PLUGIN_URL . 'assets/js/replies-show-hide.js', array( 'jquery' ), '3.1', true );
	
	wp_register_script( 'fep-attachment-script', FEP_PLUGIN_URL . 'assets/js/attachment.js', array( 'jquery' ), '6.1', true );
	wp_localize_script( 'fep-attachment-script', 'fep_attachment_script', 
			array( 
				'remove' => esc_js(__('Remove', 'front-end-pm')),
				'maximum' => esc_js( fep_get_option('attachment_no', 4 ) ),
				'max_text' => esc_js( sprintf( __( 'Maximum %s allowed', 'front-end-pm' ), sprintf(_n('%s file', '%s files', fep_get_option('attachment_no', 4 ), 'front-end-pm'), number_format_i18n(fep_get_option('attachment_no', 4 )) ) ) )
				
			) 
		);
	wp_register_script( 'fep-shortcode-newmessage', FEP_PLUGIN_URL . 'assets/js/shortcode-newmessage.js', array( 'jquery' ), '6.1', true );
	wp_localize_script( 'fep-shortcode-newmessage', 'fep_shortcode_newmessage', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'token' => wp_create_nonce('fep_message'),
				'refresh_text' => __('Refresh this page and try again.', 'front-end-pm'),
			) 
		);
	wp_register_script( 'fep-tokeninput-script', FEP_PLUGIN_URL . 'assets/js/jquery.tokeninput.js', array( 'jquery' ), '6.1', true );
	wp_register_script( 'fep-block-unblock-script', FEP_PLUGIN_URL . 'assets/js/block-unblock.js', array( 'jquery' ), '6.1', true );
	wp_localize_script( 'fep-block-unblock-script', 'fep_block_unblock_script', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'token' => wp_create_nonce('fep-block-unblock-script')
			) 
		);
    }

function fep_page_id() {
		
     return (int) apply_filters( 'fep_page_id_filter', fep_get_option('page_id', 0 ) );
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

if ( !function_exists('fep_create_nonce') ) :
 /**
 * Creates a token usable in a form
 * return nonce with time
 * @return string
 */
	function fep_create_nonce($action = -1) {
   	 $time = time();
    	$nonce = wp_create_nonce($time.$action);
    return $nonce . '-' . $time;
	}	

endif;

if ( !function_exists('fep_verify_nonce') ) :
 /**
 * Check if a token is valid. Mark it as used
 * @param string $_nonce The token
 * @return bool
 */
	function fep_verify_nonce( $_nonce, $action = -1) {

    //Extract timestamp and nonce part of $_nonce
    $parts = explode( '-', $_nonce );
	
	// bad formatted onetime-nonce
	if ( empty( $parts[0] ) || empty( $parts[1] ) )
		return false;
		
    $nonce = $parts[0]; // Original nonce generated by WordPress.
    $generated = $parts[1]; //Time when generated

    $expire = (int) $generated + HOUR_IN_SECONDS; //We want these nonces to have a short lifespan
    $time = time();

    //Verify the nonce part and check that it has not expired
    if( ! wp_verify_nonce( $nonce, $generated.$action ) || $time > $expire )
        return false;

    //Get used nonces
    $used_nonces = get_option('_fep_used_nonces');
	
	if(! is_array( $used_nonces ) )
		$used_nonces = array();

    //Nonce already used.
    if( isset( $used_nonces[$nonce] ) )
        return false;

    foreach ($used_nonces as $nonces => $timestamp){
        if( $timestamp < $time ){
        //This nonce has expired, so we don't need to keep it any longer
        unset( $used_nonces[$nonces] );
		}
    }

    //Add nonce to used nonces
    $used_nonces[$nonce] = $expire;
    update_option( '_fep_used_nonces', $used_nonces, 'no' );
	return true;
}
endif;

function fep_error($wp_error){
	if(!is_wp_error($wp_error)){
		return '';
	}
	if(count($wp_error->get_error_messages())==0){
		return '';
	}
	$errors = $wp_error->get_error_messages();
	if (is_admin())
	$html = '<div id="message" class="error">';
	else
	$html = '<div class="fep-wp-error">';
	foreach($errors as $error){
		$html .= '<strong>' . __('Error', 'front-end-pm') . ': </strong>'.esc_html($error).'<br />';
	}
	$html .= '</div>';
	return $html;
}

function fep_get_new_message_number()
    {

      return fep_get_user_message_count( 'unread' );
    }
	
function fep_get_new_message_button( $args = array() ){
	if ( ! fep_current_user_can( 'access_message' ) )
	return '';
	
	$args = wp_parse_args( $args, array(
			'show_bracket'		=> '1',
			'hide_if_zero'		=> '1',
			'ajax'				=> '1',
			'class'				=> 'fep-font-red',
		) );
	
	$new = fep_get_new_message_number();
	
	if( empty( $args['ajax'] ) ){
		if( ! $new && $args['hide_if_zero'] ){
			return '';
		}
		$ret = '';
		
		if( $args['show_bracket'] ){
			$ret .= '(';
		}
		$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
		if( $args['show_bracket'] ){
			$ret .= ')';
		}
			
		return $ret;
	}
	
	wp_enqueue_script( 'fep-notification-script' );

	$args['class'] =  $args['class'] . ' fep_unread_message_count';
	
	if( $args['hide_if_zero'] ){
		$args['class'] =  $args['class'] . ' fep_unread_message_count_hide_if_zero';
	}

	$ret = '';
	
	if( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ){
		$ret .= '<span class="fep_unread_message_count_hide_if_zero fep-hide">(</span>';
	} elseif( $args['show_bracket'] && $args['hide_if_zero'] ){
		$ret .= '<span class="fep_unread_message_count_hide_if_zero">(</span>';
	} elseif( $args['show_bracket'] ){
		$ret .= '(';
	}
	if( ! $new && $args['hide_if_zero'] ){
		$args['class'] =  $args['class'] . ' fep-hide';
	}
	$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
	
	if( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ){
		$ret .= '<span class="fep_unread_message_count_hide_if_zero fep-hide">)</span>';
	} elseif( $args['show_bracket'] && $args['hide_if_zero'] ){
		$ret .= '<span class="fep_unread_message_count_hide_if_zero">)</span>';
	} elseif( $args['show_bracket'] ){
		$ret .= ')';
	}
		
	return $ret;
}

function fep_get_new_announcement_number()
    {

      return fep_get_user_announcement_count( 'unread' );
    }

function fep_get_new_announcement_button( $args = array() ){
	if ( ! fep_current_user_can( 'access_message' ) )
	return '';
	
	$args = wp_parse_args( $args, array(
			'show_bracket'		=> '1',
			'hide_if_zero'		=> '1',
			'ajax'				=> '1',
			'class'				=> 'fep-font-red',
		) );
	
	$new = fep_get_new_announcement_number();
	
	if( empty( $args['ajax'] ) ){
		if( ! $new && $args['hide_if_zero'] ){
			return '';
		}
		$ret = '';
		
		if( $args['show_bracket'] ){
			$ret .= '(';
		}
		$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
		if( $args['show_bracket'] ){
			$ret .= ')';
		}
			
		return $ret;
	}
	
	wp_enqueue_script( 'fep-notification-script' );

	$args['class'] =  $args['class'] . ' fep_unread_announcement_count';
	
	if( $args['hide_if_zero'] ){
		$args['class'] =  $args['class'] . ' fep_unread_announcement_count_hide_if_zero';
	}

	$ret = '';
	
	if( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ){
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero fep-hide">(</span>';
	} elseif( $args['show_bracket'] && $args['hide_if_zero'] ){
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero">(</span>';
	} elseif( $args['show_bracket'] ){
		$ret .= '(';
	}
	if( ! $new && $args['hide_if_zero'] ){
		$args['class'] =  $args['class'] . ' fep-hide';
	}
	$ret .= '<span class="' . $args['class'] . '">' . $new . '</span>';
	
	if( $args['show_bracket'] && $args['hide_if_zero'] && ! $new ){
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero fep-hide">)</span>';
	} elseif( $args['show_bracket'] && $args['hide_if_zero'] ){
		$ret .= '<span class="fep_unread_announcement_count_hide_if_zero">)</span>';
	} elseif( $args['show_bracket'] ){
		$ret .= ')';
	}
		
	return $ret;
}

function fep_is_user_blocked( $login = '' ){
	global $user_login;
	if ( !$login && $user_login )
	$login = $user_login;
	
	if ($login){
		$wpusers = explode(',', fep_get_option('have_permission') );
		
		$wpusers = array_map( 'trim', $wpusers );

		if( in_array( $login, $wpusers) )
		return true;
	} //User not logged in
	return false;
}

function fep_is_user_whitelisted( $login = '' ){
	global $user_login;
	if ( !$login && $user_login )
	$login = $user_login;
	
	if ($login){
	$wpusers = explode(',', fep_get_option('whitelist_username') );
	
	$wpusers = array_map( 'trim', $wpusers );

		if(in_array( $login, $wpusers))
		return true;
	} //User not logged in
	return false;
}

function fep_get_userdata($data, $need = 'ID', $type = 'slug' )
		{
		if (!$data)
			return '';
		
		$type = strtolower($type);
		
		if( 'user_nicename' == $type )
			$type = 'slug';
			
		if ( !in_array($type, array ('id', 'slug', 'email', 'login' )))
			return '';
		
		$user = get_user_by( $type , $data);
		
		if ( $user )
			return $user->$need;
		else
			return '';
	}
	
function fep_user_name( $id ){
	$which = apply_filters( 'fep_filter_show_which_name', 'display_name' );
	
	switch( $which ){
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
	return apply_filters( 'fep_filter_user_name', $name, $id );
}

function fep_get_user_message_count( $value = 'all', $force = false, $user_id = false )
{
	return Fep_Message::init()->user_message_count( $value, $force, $user_id );
}

function fep_get_user_announcement_count( $value = 'all', $force = false, $user_id = false )
{
	return Fep_Announcement::init()->get_user_announcement_count( $value, $force, $user_id );
}

function fep_get_message( $id )
{
	$post = get_post( $id );
	
	if( $post && in_array( get_post_type( $post ), array( 'fep_message', 'fep_announcement') ) ){
		return $post;
	} else {
		return null;
	}
	
}

function fep_get_replies( $id )
{
	$args = array(
		'post_type' => 'fep_message',
		'post_status' => 'publish',
		'post_parent' => $id,
		'posts_per_page' => -1,
		'order'=> 'ASC'
	 );
	 
	 $args = apply_filters( 'fep_filter_get_replies', $args );
	 
	return new WP_Query( $args );
}

function fep_get_attachments( $post_id = 0, $fields = '' ) {

	if( ! $post_id ) {
		$post_id = get_the_ID();
	}
	
	if( ! $post_id ) {
		return array();
	}
    $args =  array(
        'post_type'      => 'attachment',
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'inherit' ),
        'post_parent'    => $post_id,
		'fields'		=> $fields,
    );
	
	$args = apply_filters( 'fep_filter_get_attachments', $args );
	
	return get_posts( $args );
}

function fep_get_message_with_replies( $id )
{
	
	$args = array(
		'post_type' => 'fep_message',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'order'=> 'ASC'
	 );
	 
	 if( 'threaded' == fep_get_message_view() ) {
		$args['post_parent'] = fep_get_parent_id( $id );
		$args['fep_include_parent'] = true;
	} else {
		$args['post__in'] = array( $id );
	}
	
	$args = apply_filters( 'fep_filter_get_message_with_replies', $args );
	 
	return new WP_Query( $args );
}

add_filter( 'posts_where' , 'fep_posts_where', 10, 2 );

function fep_posts_where( $where, $q ) {

	global $wpdb;
	
	if ( true === $q->get( 'fep_include_parent' ) && $q->get( 'post_parent' ) ){
        $where .= $wpdb->prepare( " OR ( $wpdb->posts.ID = %d AND $wpdb->posts.post_status = %s )", $q->get( 'post_parent' ), $q->get( 'post_status' ) );
	}
	
	return $where;
}

function fep_get_parent_id( $id ) {

	if( ! $id )
		return 0;
	
	do {
		$parent = $id;
	} while( $id = wp_get_post_parent_id( $id ) );
	// climb up the hierarchy until we reach parent = 0
	
	return $parent;

}

add_filter( 'the_time', 'fep_format_date', 10, 2  ) ;

function fep_format_date( $date, $d = '' )
    {
		global $post;
		
		if( is_admin() || ! in_array( get_post_type(), apply_filters( 'fep_post_types_for_time', array( 'fep_message', 'fep_announcement' ) ) ) )
			return $date;
			
		
		if ( '0000-00-00 00:00:00' === $post->post_date ) {
			$h_time = __( 'Unpublished', 'front-end-pm' );
		} else {
			$m_time = $post->post_date;
			//$time = strtotime( $post->post_date_gmt );
			$time = get_post_time( 'G', true, $post, false );
			
			if ( ( abs( $t_diff = time() - $time ) ) < DAY_IN_SECONDS ) {
				if ( $t_diff < 0 ) {
					$h_time = sprintf( __( '%s from now', 'front-end-pm' ), human_time_diff( $time ) );
				} else {
					$h_time = sprintf( __( '%s ago', 'front-end-pm' ), human_time_diff( $time ) );
				}
			} else {
				$h_time = mysql2date( get_option( 'date_format' ). ' '.get_option( 'time_format' ), $m_time );
			}
		}

	  
	  return apply_filters( 'fep_formate_date', $h_time, $date, $d );
    }
	
	function fep_output_filter($string, $title = false)
    {
		$string = stripslashes($string);
		
	  if ($title) {
	  $html = apply_filters('fep_filter_display_title', $string);
	  } else {
	  $html = apply_filters('fep_filter_display_message', $string);
	  }
	  
      return $html;
    }

function fep_sort_by_priority( $a, $b ) {
	    if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
	        return 0;
	    }
	    return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	
function fep_pagination( $total = null, $per_page = null, $list_class = 'fep-pagination' ) {
	
	$total = apply_filters( 'fep_pagination_total', $total);
	
	if( null === $per_page ) {
		$per_page = fep_get_option('messages_page',15);
	}
	$per_page = apply_filters( 'fep_pagination_per_page', $per_page);
		
    $last       = ceil( absint($total) / absint($per_page) );
	
	if( $last <= 1 )
		return '';
		
	//$numPgs = $total_message / fep_get_option('messages_page',50);
	$page 		=  ( ! empty( $_GET['feppage'] )) ? absint($_GET['feppage']) : 1;
	$links      = ( isset( $_GET['links'] ) ) ? absint($_GET['links']) : 2;
 
    $start      = ( ( $page - $links ) > 0 ) ? $page - $links : 1;
    $end        = ( ( $page + $links ) < $last ) ? $page + $links : $last;
 
    $html       = '<div class="fep-align-centre"><ul class="' . $list_class . '">';
 
    $class      = ( $page == 1 ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="' . esc_url( add_query_arg( 'feppage', ( $page - 1 ) ) ) . '">&laquo;</a></li>';
 
    if ( $start > 1 ) {
        $html   .= '<li><a href="' . esc_url( add_query_arg( 'feppage',  1 ) ) . '">' . number_format_i18n( 1 ) . '</a></li>';
        $html   .= '<li class="disabled"><span>...</span></li>';
    }
 
    for ( $i = $start ; $i <= $end; $i++ ) {
        $class  = ( $page == $i ) ? "active" : "";
        $html   .= '<li class="' . $class . '"><a href="' . esc_url( add_query_arg( 'feppage', $i ) ) . '">' . number_format_i18n( $i ) . '</a></li>';
    }
 
    if ( $end < $last ) {
        $html   .= '<li class="disabled"><span>...</span></li>';
        $html   .= '<li><a href="' . esc_url( add_query_arg( 'feppage', $last ) ) . '">' . number_format_i18n( $last ) . '</a></li>';
    }
 
    $class      = ( $page == $last ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="' . esc_url( add_query_arg( 'feppage', ( $page + 1 ) ) ) . '">&raquo;</a></li>';
 
    $html       .= '</ul></div>';
 
    return $html;
}

function fep_is_user_admin( $user_id = 0 ){
	
	$admin_cap = apply_filters( 'fep_admin_cap', 'manage_options' );
	if( $user_id ){
		return user_can( $user_id, $admin_cap );
	}
	return current_user_can( $admin_cap );
}

function fep_current_user_can( $cap, $id = false ) {
	$can = false;
	
	if( ! is_user_logged_in() || fep_is_user_blocked() ) {
		return apply_filters( 'fep_current_user_can', $can, $cap, $id );
	}
	$no_role_access = apply_filters( 'fep_no_role_access', false, $cap, $id );
	$roles = wp_get_current_user()->roles;
	
	switch( $cap ) {
		case 'access_message':
			if( fep_is_user_whitelisted() || array_intersect( fep_get_option('userrole_access', array() ), $roles ) || ( ! $roles && $no_role_access ) ){
				$can = true;
			}
		break;
		case 'send_new_message' :
			if( fep_is_user_whitelisted() || array_intersect( fep_get_option('userrole_new_message', array() ), $roles ) || ( ! $roles && $no_role_access ) ){
				$can = true;
			}
		break;
		case 'send_new_message_to' :
			if( is_numeric( $id ) ){
				// $id == user ID
				if ( $id && $id != get_current_user_id() && fep_current_user_can('access_message') && fep_current_user_can('send_new_message') && fep_get_user_option( 'allow_messages', 1,  $id ) && ! fep_is_user_blocked_for_user( $id ) ){
					$can = true;
				}
			// $id == user_nicename
			// Backward compability ( do not use )
		} elseif ( $id && $id != fep_get_userdata( get_current_user_id(), 'user_nicename', 'id' ) && fep_current_user_can('access_message') && fep_current_user_can('send_new_message') && fep_get_user_option( 'allow_messages', 1, fep_get_userdata( $id ) ) && ! fep_is_user_blocked_for_user( fep_get_userdata( $id ) ) ){
				$can = true;
			}
		break;
		case 'send_reply' :
			if( ! $id ||  ( ! in_array( get_current_user_id(), fep_get_participants( $id ) ) && ! fep_is_user_admin() ) || get_post_status ( $id ) != 'publish' ) {
			
			} elseif( fep_is_user_whitelisted() || fep_is_user_admin() || array_intersect( fep_get_option('userrole_reply', array() ), $roles ) || ( ! $roles && $no_role_access ) ){
				$can = true;
				$participants = fep_get_participants( $id );
				foreach( $participants as $participant ){
					if( fep_is_user_blocked_for_user( $participant ) ){
						$can = false;
						break;
					}
				}
			}
		break;
		case 'view_message' :
			if( $id && ( ( in_array( get_current_user_id(), fep_get_participants( $id ) ) && get_post_status ( $id ) == 'publish' ) || fep_is_user_admin() )) {
				$can = true;
			}
		break;
		case 'delete_message' : //only for himself
			if( $id && in_array( get_current_user_id(), fep_get_participants( $id ) ) && get_post_status ( $id ) == 'publish' ) {
				$can = true;
			}
		break;
		case 'access_directory' :
			if( fep_is_user_admin() || fep_get_option('show_directory', 1 ) ) {
				$can = true;
			}
		break;
		case 'add_announcement' :
			if( fep_is_user_admin() || current_user_can('create_fep_announcements') ) {
				$can = true;
			}
		break;
		case 'view_announcement' :
			if( $id && ( ( ( array_intersect( fep_get_participant_roles( $id ), $roles ) || ( ! $roles && $no_role_access ) ) && get_post_status ( $id ) == 'publish') || fep_is_user_admin() || get_post_field( 'post_author', $id ) == get_current_user_id() ) ) {
				$can = true;
			}
		break;
		default :	
			$can = apply_filters( 'fep_current_user_can_' . $cap, $can, $cap, $id );
		break;
	}
	return apply_filters( 'fep_current_user_can', $can, $cap, $id );
}

function fep_is_read( $parent = false, $post_id = false, $user_id = false )
{
	if( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if( ! $post_id ) {
		$post_id = get_the_ID();
	}
	if( !$post_id || !$user_id ) {
		return false;
	}
	if( $parent ) {
		if( 'threaded' == fep_get_message_view() ){
			return get_post_meta( fep_get_parent_id( $post_id ), '_fep_parent_read_by_'. $user_id, true );
		} else {
			return get_post_meta( $post_id, '_fep_parent_read_by_'. $user_id, true );
		}
	}
	$read_by = get_post_meta( $post_id, '_fep_read_by', true );

	
	if( is_array( $read_by ) && in_array( $user_id, $read_by ) ) {
		return true;
	}

	return false;
}

function fep_make_read( $parent = false, $post_id = false, $user_id = false )
{
	if( ! $post_id ) {
		$post_id = get_the_ID();
	}
	if( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if( !$post_id || !$user_id ) {
		return false;
	}
	if( $parent ) {
		if( 'threaded' == fep_get_message_view() ){
			$return = add_post_meta( fep_get_parent_id( $post_id ), '_fep_parent_read_by_'. $user_id, time(), true );
		} else {
			$return = add_post_meta( $post_id, '_fep_parent_read_by_'. $user_id, time(), true );
		}
		if( $return ) {
		 delete_user_option( $user_id, '_fep_user_message_count' );
		 	return true;
		} else {
			return false;
		}
	} 
	$read_by = get_post_meta( $post_id, '_fep_read_by', true );
	
	if( ! is_array( $read_by ) ) {
		$read_by = array();
	}
	if( in_array( $user_id, $read_by ) ) {
		return false;
	}
	$read_by[time()] = $user_id;
	
	return update_post_meta( $post_id, '_fep_read_by', $read_by );
	
}

function fep_get_the_excerpt($count = 100, $excerpt = false ){
  if( false === $excerpt )
  $excerpt = get_the_excerpt();
  $excerpt = strip_shortcodes($excerpt);
  $excerpt = wp_strip_all_tags($excerpt);
  $excerpt = substr($excerpt, 0, $count);
  $excerpt = substr($excerpt, 0, strripos($excerpt, " "));
  $excerpt = $excerpt.' ...';
  
  return apply_filters( 'fep_get_the_excerpt', $excerpt, $count);
}

function fep_get_current_user_max_message_number()
{
	$roles = wp_get_current_user()->roles;
	
	$count_array = array();
	
	if( $roles && is_array($roles) ) {
		foreach( $roles as $role ) {
			$count = fep_get_option("message_box_{$role}", 50);
			if( ! $count ) {
				return 0;
			}
			$count_array[] = $count;
		}
	}
	if( $count_array ) {
		return max($count_array);
	} else {
		return 0; //FIX ME. 0 = unlimited !!!!
	}
}

function fep_wp_mail_from( $from_email ) {
	
	$email = fep_get_option('from_email', get_bloginfo('admin_email'));
	
	if( is_email( $email ) ) {
		return $email;
	}
	return $from_email;	
	
}

function fep_wp_mail_from_name( $from_name ) {
	
	$name = stripslashes( fep_get_option('from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) );
	
	if( $name ) {
		return $name;
	}
	return $from_name;	
	
}

function fep_wp_mail_content_type( $content_type ) {
	
	$type = fep_get_option( 'email_content_type', 'plain_text' );
	
	if( 'html' == $type ) {
		return 'text/html';
	} elseif( 'plain_text' == $type ) {
		return 'text/plain';
	}
	return $content_type;	
	
}

function fep_add_email_filters( $for = 'message' ){
	
	//add_filter( 'wp_mail_from', 'fep_wp_mail_from', 10 );
	//add_filter( 'wp_mail_from_name', 'fep_wp_mail_from_name', 10 );
	//add_filter( 'wp_mail_content_type', 'fep_wp_mail_content_type', 10 );
	
	do_action( 'fep_action_after_add_email_filters', $for );
}

function fep_remove_email_filters( $for = 'message' ){
	
	//remove_filter( 'wp_mail_from', 'fep_wp_mail_from', 10 );
	//remove_filter( 'wp_mail_from_name', 'fep_wp_mail_from_name', 10 );
	//remove_filter( 'wp_mail_content_type', 'fep_wp_mail_content_type', 10 );
	
	do_action( 'fep_action_after_remove_email_filters', $for );
}

function fep_delete_message( $message_id, $user_id = 0 ){
	if( 'threaded' == fep_get_message_view() ){
		$id = fep_get_parent_id( $message_id );
	} else {
		$id = $message_id;
	}
	$return = false;
	
	if( $user_id ) {
		$return = add_post_meta( $id, '_fep_delete_by_'. $user_id, time(), true );
	} elseif( fep_current_user_can( 'delete_message', $id ) ){
		$return = add_post_meta( $id, '_fep_delete_by_'. get_current_user_id(), time(), true );
	}
	$should_delete_from_db = true;
	foreach( fep_get_participants( $id ) as $participant ) {
		if( ! get_post_meta( $id, '_fep_delete_by_'. $participant, true ) ) {
			$should_delete_from_db = false;
			break;
		}
		
	}
	if( $should_delete_from_db && ! get_post_meta( $id, '_fep_group', true ) ) {
		$return = wp_trash_post( $id  );
	}
	return $return;
}

function fep_undelete_message( $message_id, $user_id = 0 ){
	if( 'threaded' == fep_get_message_view() ){
		$id = fep_get_parent_id( $message_id );
	} else {
		$id = $message_id;
	}
	$return = false;
	
	if( $user_id ) {
		$return = delete_post_meta( $id, '_fep_delete_by_'. $user_id );
	} elseif( fep_current_user_can( 'delete_message', $id ) ){
		$return = delete_post_meta( $id, '_fep_delete_by_'. get_current_user_id() );
	}

	return $return;
}

function fep_send_message( $message = null, $override = array() )
{
	if( null === $message ) {
		$message = $_POST;
	}
	
	if( ! empty($message['fep_parent_id'] ) ) {
		$message['post_parent'] = absint( $message['fep_parent_id'] );
		$message['post_status'] = fep_get_option('reply_post_status','publish');
		$message['message_title'] = __('RE:', 'front-end-pm'). ' ' . wp_slash( get_post( $message['post_parent'] )->post_title );
		if( 'threaded' != fep_get_message_view() )
			$message['message_to_id'] = fep_get_participants( $message['post_parent'] );
	} else {
		$message['post_status'] = fep_get_option('parent_post_status','publish');
		$message['post_parent'] = 0;
	}
	
	$message = apply_filters('fep_filter_message_before_send', $message );
	
	if( empty($message['message_title']) || empty($message['message_content']) ) {
		return false;
	}
	// Create post array
	$post = array(
	  	'post_title'    => $message['message_title'],
	  	'post_content'  => $message['message_content'],
	  	'post_status'   => $message['post_status'],
	  	'post_parent'   => $message['post_parent'],
	  	'post_type'   	=> 'fep_message'
	);
	
	if( $override && is_array( $override ) ) {
		$post = wp_parse_args( $override, $post );
	}
	 
	$post = apply_filters('fep_filter_message_after_override', $post, $message );
	
	// Insert the message into the database
	$message_id = wp_insert_post( $post );
	
	if( ! $message_id || is_wp_error( $message_id ) ) {
		return false;
	}
	$inserted_message = get_post( $message_id );
	
	 do_action('fep_action_message_after_send', $message_id, $message, $inserted_message );
	
	return $message_id;
}

add_action( 'fep_action_message_after_send', 'fep_add_message_participants', 5, 3 );

function fep_add_message_participants( $message_id, $message, $inserted_message ){
	
	if( $inserted_message->post_parent ) {
		if( 'threaded' == fep_get_message_view() ){
			if( ! in_array( $inserted_message->post_author, fep_get_participants( $inserted_message->post_parent ) )){
				add_post_meta( $inserted_message->post_parent, '_fep_participants', $inserted_message->post_author );
				fep_make_read( true, $message_id, $inserted_message->post_author );
			}
		
			$participants = fep_get_participants( $inserted_message->post_parent );
	
			foreach( $participants as $participant ) 
			{
				if( $participant != $inserted_message->post_author ){
					fep_undelete_message( $inserted_message->post_parent, $participant);
					delete_post_meta( $inserted_message->post_parent, '_fep_parent_read_by_'. $participant );
				}
			}
		}		
	}
	if( ! $inserted_message->post_parent || ( $inserted_message->post_parent && 'threaded' != fep_get_message_view() ) ) {
		if( ! empty($message['message_to_id'] ) ) { //FRONT END message_to return id of participants
			if( is_array( $message['message_to_id'] ) ) {
				foreach( $message['message_to_id'] as $participant ) {
					
					if( ! in_array( $participant, fep_get_participants( $message_id ) )){
						add_post_meta( $message_id, '_fep_participants', $participant );
						if( 'publish' == $inserted_message->post_status ){
							delete_user_option( $participant, '_fep_user_message_count' );
							delete_user_option( $participant, '_fep_notification_dismiss' );
						}
					}
				}
			} else {
				if( ! in_array( $message['message_to_id'], fep_get_participants( $message_id ) )){
					add_post_meta( $message_id, '_fep_participants', $message['message_to_id'] );
					if( 'publish' == $inserted_message->post_status ){
						delete_user_option( $message['message_to_id'], '_fep_user_message_count' );
						delete_user_option( $message['message_to_id'], '_fep_notification_dismiss' );
					}
				}
			}
		}		
		if( ! in_array( $inserted_message->post_author, fep_get_participants( $message_id ) )){
			add_post_meta( $message_id, '_fep_participants', $inserted_message->post_author );
		}
			
		fep_make_read( true, $message_id, $inserted_message->post_author );
	}
}

add_action ('transition_post_status', 'fep_send_message_transition_post_status', 10, 3);

function fep_send_message_transition_post_status( $new_status, $old_status, $post ){
	if ( 'fep_message' != $post->post_type || $old_status === $new_status ) {
		 return;
	}
	
	if( 'publish' == $new_status && 'threaded' == fep_get_message_view() ){
		if( $post->post_parent ) {
			update_post_meta( $post->post_parent, '_fep_last_reply_by', $post->post_author );
			update_post_meta( $post->post_parent, '_fep_last_reply_id', $post->ID );
			update_post_meta( $post->post_parent, '_fep_last_reply_time', strtotime( $post->post_date_gmt ) );
		} else {
			add_post_meta( $post->ID, '_fep_last_reply_by', $post->post_author, true );
			add_post_meta( $post->ID, '_fep_last_reply_id', $post->ID, true );
			add_post_meta( $post->ID, '_fep_last_reply_time', strtotime( $post->post_date_gmt ), true );
		}
		
	} elseif( 'publish' == $old_status && 'threaded' == fep_get_message_view() ){
		if( $post->post_parent ) {
			$child_args = array(
				'post_type' => 'fep_message',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'post_parent' => $post->post_parent
			 );
			 $child = get_posts( $child_args );
			 
			 if( $child && ! empty( $child[0] ) ){
				update_post_meta( $post->post_parent, '_fep_last_reply_by', $child[0]->post_author );
				update_post_meta( $post->post_parent, '_fep_last_reply_id', $child[0]->ID );
				update_post_meta( $post->post_parent, '_fep_last_reply_time', strtotime( $child[0]->post_date_gmt ) );
			} else {
				$parent_post = get_post( $post->post_parent );
				
				update_post_meta( $parent_post->ID, '_fep_last_reply_by', $parent_post->post_author );
				update_post_meta( $parent_post->ID, '_fep_last_reply_id', $parent_post->ID );
				update_post_meta( $parent_post->ID, '_fep_last_reply_time', strtotime( $parent_post->post_date_gmt ) );
			}
		}
	}
	if( 'publish' == $new_status || 'publish' == $old_status ){

		$participants = fep_get_participants( $post->ID );

		foreach( $participants as $participant ) 
		{
			delete_user_option( $participant, '_fep_user_message_count' );
			if( $participant != $post->post_author && 'publish' == $new_status ){
				delete_user_option( $participant, '_fep_notification_dismiss' );
			}
		}
	}
}

function fep_add_announcement( $announcement = null, $override = array() )
{
	if( null === $announcement ) {
		$announcement = $_POST;
	}
	
	$announcement = apply_filters('fep_filter_announcement_before_added', $announcement );
	
	if( empty($announcement['message_title']) || empty($announcement['message_content']) ) {
		return false;
	}
	// Create post array
	$post = array(
	  	'post_title'    => $announcement['message_title'],
	  	'post_content'  => $announcement['message_content'],
	  	'post_status'   => 'publish',
	  	'post_type'   	=> 'fep_announcement'
	);
	
	if( $override && is_array( $override ) ) {
		$post = wp_parse_args( $override, $post );
	}
	 
	$post = apply_filters('fep_filter_announcement_after_override', $post, $announcement );
	
	// Insert the message into the database
	$announcement_id = wp_insert_post( $post );
	
	if( ! $announcement_id || is_wp_error( $announcement_id ) ) {
		return false;
	}
	$inserted_announcement = get_post( $announcement_id );
	
	if( ! empty($announcement['announcement_roles']) && is_array($announcement['announcement_roles']) ) {
		foreach($announcement['announcement_roles'] as $role ) {
			add_post_meta( $announcement_id, '_fep_participant_roles', $role );
		}
	}
	add_post_meta( $announcement_id, '_fep_author', $inserted_announcement->post_author, true);
	
	 do_action('fep_action_announcement_after_added', $announcement_id, $announcement, $inserted_announcement );
	
	return $announcement_id;
}

function fep_backticker_encode($text) {
	$text = $text[1];
    $text = str_replace('&amp;lt;', '&lt;', $text);
    $text = str_replace('&amp;gt;', '&gt;', $text);
	$text = htmlspecialchars($text, ENT_QUOTES);
	$text = preg_replace("|\n+|", "\n", $text);
	$text = nl2br($text);
    $text = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $text);
	$text = preg_replace("/^ /", '&nbsp;', $text);
    $text = preg_replace("/(?<=&nbsp;| |\n) /", '&nbsp;', $text);
    
    return "<code>$text</code>";
}

function fep_backticker_display_code($text) {
    //$text = preg_replace_callback("|`(.*?)`|", "fep_backticker_encode", $text);
	$text = preg_replace_callback('!`(?:\r\n|\n|\r|)(.*?)(?:\r\n|\n|\r|)`!ims', "fep_backticker_encode", $text);
    $text = str_replace('<code></code>', '`', $text);
    return $text;
}

function fep_backticker_code_input_filter( $message ) {

	$message['message_content'] = fep_backticker_display_code($message['message_content']);
	
	return $message;
	}
add_filter( 'fep_filter_message_before_send', 'fep_backticker_code_input_filter', 5);

function fep_autosuggestion_ajax() {
	_deprecated_function( __FUNCTION__, '4.4', 'Fep_Ajax class' );
	
	global $user_ID;

	if( !fep_get_option('show_autosuggest', 1) && !fep_is_user_admin() )
	die();

	if ( check_ajax_referer( 'fep-autosuggestion', 'token', false )) {

	$searchq = $_POST['searchBy'];


	$args = array(
					'search' => "*{$searchq}*",
					'search_columns' => array( 'user_login', 'display_name' ),
					'exclude' => array( $user_ID ),
					'number' => 5,
					'orderby' => 'display_name',
					'order' => 'ASC',
					'fields' => array( 'ID', 'display_name', 'user_nicename' )
		);
	
	$args = apply_filters ('fep_autosuggestion_arguments', $args );
	
	// The Query
	$user_query = new WP_User_Query( $args );
	
if(strlen($searchq)>0)
{
	echo "<ul>";
	if (! empty( $user_query->results ))
	{
		foreach($user_query->results as $user)
		{
				
				?>
				<li><a href="#" onClick="fep_fill_autosuggestion('<?php echo $user->user_nicename; ?>','<?php echo fep_user_name($user->ID); ?>');return false;"><?php echo fep_user_name($user->ID); ?></a></li>
				<?php
			
		}
	}
	else
		echo "<li>".__("No matches found", 'front-end-pm')."</li>";
	echo "</ul>";
}
}
die();
}

//add_action('wp_ajax_fep_autosuggestion_ajax','fep_autosuggestion_ajax');	

function fep_footer_credit()
    {
	$style = '';
	if ( ! fep_get_option('show_branding', 1) ) {
		$style = " style='display: none'";
	}
	echo "<div{$style}><a href='https://www.shamimsplugins.com/products/front-end-pm-pro/' target='_blank'>Front End PM</a></div>";
    }	

add_action('fep_footer_note', 'fep_footer_credit' );

function fep_notification() 
		{
			if ( ! fep_current_user_can( 'access_message' ) )
				return '';
			if ( ! fep_get_option('show_notification', 1) )
				return '';
			
			$unread_count = fep_get_new_message_number();
			$sm = sprintf(_n('%s message', '%s messages', $unread_count, 'front-end-pm'), number_format_i18n($unread_count) );

				$show = '';
				
				$unread_ann_count = fep_get_user_announcement_count( 'unread' );
				$sa = sprintf(_n('%s announcement', '%s announcements', $unread_ann_count, 'front-end-pm'), number_format_i18n($unread_ann_count) );
	
			if ( $unread_count || $unread_ann_count ) {
				$show = __("You have", 'front-end-pm');
	
			if ( $unread_count )
				$show .= "<a href='".fep_query_url('messagebox')."'> $sm</a>";
	
			if ( $unread_count && $unread_ann_count )
				$show .= ' ' .__('and', 'front-end-pm');
	
			if ( $unread_ann_count )
				$show .= "<a href='".fep_query_url('announcements')."'> $sa</a>";
				
				$show .= ' ';
				$show .= __('unread', 'front-end-pm');
			}
			return apply_filters('fep_header_notification', $show);
		}
			

function fep_notification_div() {
	if ( ! fep_current_user_can( 'access_message' ) )
				return;
	if ( ! fep_get_option('show_notification', 1) )
				return;
				
	wp_enqueue_script( 'fep-notification-script' );
	
	$unread_count = fep_get_new_message_number();
	$sm = sprintf(_n('%s message', '%s messages', $unread_count, 'front-end-pm'), number_format_i18n($unread_count) );

	$unread_ann_count = fep_get_new_announcement_number();
	$sa = sprintf(_n('%s announcement', '%s announcements', $unread_ann_count, 'front-end-pm'), number_format_i18n($unread_ann_count) );
	
	$class = 'fep-notification-bar';
	if( !$unread_count && !$unread_ann_count ){
		$class .= ' fep-hide';
	} elseif( get_user_option( '_fep_notification_dismiss' ) ){
		$class .= ' fep-hide';
	}
	
	$show = '<div id="fep-notification-bar" class="'. $class . '"><p>';
	$show .= __("You have", 'front-end-pm');
	
	$class = 'fep_unread_message_count_hide_if_zero';
	if( ! $unread_count )
	$class .= ' fep-hide';
	
	$show .= '<span class="'.$class.'"> <a href="'.fep_query_url('messagebox').'"><span class="fep_unread_message_count_text">'. $sm . '</span></a></span>';
	
	$class = 'fep_hide_if_anyone_zero';
	if( ! $unread_count || ! $unread_ann_count )
	$class .= ' fep-hide';
	
	$show .= '<span class="'.$class.'"> ' .__('and', 'front-end-pm') . '</span>';
	
	$class = 'fep_unread_announcement_count_hide_if_zero';
	if( ! $unread_ann_count )
	$class .= ' fep-hide';
		
	$show .= '<span class="'.$class.'"> <a href="'.fep_query_url('announcements').'"><span class="fep_unread_announcement_count_text">'. $sa . '</span></a></span>';
		
	$show .= ' ';
	$show .= __('unread', 'front-end-pm');
	$show .= '</p>';
	$show .= '<button aria-label="'. esc_attr( 'Dismiss notice', 'front-end-pm' ).'" class="fep-notice-dismiss">×</button>';
	$show .= '</div>';
		
	echo apply_filters('fep_header_notification', $show);
}


add_action('wp_head', 'fep_notification_div', 99 );

function fep_notification_ajax() {
	_deprecated_function( __FUNCTION__, '4.4', 'Fep_Ajax class' );

	if ( check_ajax_referer( 'fep-notification', 'token', false )) {
	
		$notification = fep_notification();
		if ( $notification )
		echo $notification;
	}
	wp_die();
	}

//add_action('wp_ajax_fep_notification_ajax','fep_notification_ajax');
//add_action('wp_ajax_nopriv_fep_notification_ajax','fep_notification_ajax');

function fep_auth_redirect(){
	if( ! fep_page_id() || ( ! is_page( fep_page_id() ) &&  ! is_single( fep_page_id() ) ) ) {
		return;
	}
	
	do_action( 'fep_template_redirect' );
	
	if( apply_filters( 'fep_using_auth_redirect', false ) ) {
		auth_redirect();
	}
}
add_action('template_redirect','fep_auth_redirect', 99 );

add_filter( 'auth_redirect_scheme', 'fep_auth_redirect_scheme' );
function fep_auth_redirect_scheme( $scheme ){

	if( is_admin() || ! fep_page_id() || ( ! is_page( fep_page_id() ) &&  ! is_single( fep_page_id() ) ) ) {
		return $scheme;
	}
	
    return 'logged_in';
}

add_filter( 'map_meta_cap', 'fep_map_meta_cap', 10, 4 );

function fep_map_meta_cap( $caps, $cap, $user_id, $args ) {

	$our_caps = array( 'read_fep_message', 'edit_fep_message', 'delete_fep_message', 'read_fep_announcement', 'edit_fep_announcement', 'delete_fep_announcement' );
	
	/* If editing, deleting, or reading a message or announcement, get the post and post type object. */
	if ( in_array( $cap, $our_caps ) ) {
		$post = get_post( $args[0] );
		$post_type = get_post_type_object( $post->post_type );

		/* Set an empty array for the caps. */
		$caps = array();
	} else {
		return $caps;
	}

	/* If editing a message or announcement, assign the required capability. */
	if ( 'edit_fep_message' == $cap || 'edit_fep_announcement' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->edit_posts;
		else
			$caps[] = $post_type->cap->edit_others_posts;
	}

	/* If deleting a message or announcement, assign the required capability. */
	elseif ( 'delete_fep_message' == $cap || 'delete_fep_announcement' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->delete_posts;
		else
			$caps[] = $post_type->cap->delete_others_posts;
	}

	/* If reading a private message or announcement, assign the required capability. */
	elseif ( 'read_fep_message' == $cap || 'read_fep_announcement' == $cap ) {

		if ( 'private' != $post->post_status )
			$caps[] = 'read';
		elseif ( $user_id == $post->post_author )
			$caps[] = 'read';
		else
			$caps[] = $post_type->cap->read_private_posts;
	}

	/* Return the capabilities required by the user. */
	return $caps;
}

function fep_array_trim( $array )
{
		
	if (!is_array( $array ))
       return trim( $array );
 
    return array_map('fep_array_trim',  $array );
}

function fep_is_pro(){
	return file_exists( FEP_PLUGIN_DIR. 'pro/pro-features.php' );
}

function fep_errors(){
    static $errors; // Will hold global variable safely
    return isset($errors) ? $errors : ($errors = new WP_Error());
}

function fep_success(){
    static $success; // Will hold global variable safely
    return isset($success) ? $success : ($success = new WP_Error());
}

function fep_info_output(){
    
	/* 
	// If conditions are met and errors exist:
    if(!fep_info()->get_error_codes()) return;
	
	$success = array();
	$info = array();
	$errors = array();
	
	// Loop error codes and display errors
    foreach( fep_info()->get_error_codes() as $code ){
	
        $data = fep_info()->get_error_data($code);
        // Display stuff here
		if( 'success' == $data ) {
			$success[] = fep_info()->get_error_message($code);
		} elseif( 'info' == $data ){
			$info[] = fep_info()->get_error_message($code);
		} else {
			$errors[] = fep_info()->get_error_message($code);
		}
    }
	*/
	
	$html = '';
	
	if( fep_success()->get_error_messages() ) {
		$html .= '<div class="fep-success">';
		foreach( fep_success()->get_error_messages() as $s){
			$html .= esc_html($s).'<br />';
		}
		$html .= '</div>';
	}

	if( fep_errors()->get_error_messages() ) {
		$html .= '<div class="fep-wp-error">';
		foreach( fep_errors()->get_error_messages() as $e){
			$html .= '<strong>' . __('Error', 'front-end-pm') . ': </strong>'.esc_html($e).'<br />';
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
	
	if( ! is_array( $template_names ) )
		$template_names = explode( ',', $template_names );
	
	foreach( $template_names as $template_name ){
		
		$template_name = trim( $template_name );

		if ( empty( $template_name ) )
			continue;
		
		if( strpos( $template_name, '../') !== false || strpos( $template_name, '..\\') !== false )
			continue;
		
		foreach( $locations as $location ){
			if( file_exists( $location . $template_name ) ) {
				$template = $location . $template_name;
				break 2;
			}
		}
		
	}
	
	if ( ( true == $load ) && ! empty( $template ) )
		load_template( $template, $require_once );
	
	return apply_filters( 'fep_locate_template', $template, $template_names, $load, $require_once );
}

add_action('wp_loaded', 'fep_form_posted', 20 ); //After Email hook 

function fep_form_posted()
{
	$action = !empty($_POST['fep_action']) ? $_POST['fep_action'] : '';
	
	if( ! $action )
		return;
	
	if ( ! fep_current_user_can('access_message') )
		return;
		
	$menu = Fep_Menu::init()->get_menu();
		
	switch( $action ) {
		case has_action("fep_posted_action_{$action}"):
			do_action("fep_posted_action_{$action}" );
		break;
		case ( 'newmessage' == $action && ! empty( $menu['newmessage'] ) ) :
		case 'shortcode-newmessage' :
			if ( ! fep_current_user_can( 'send_new_message') ){
				fep_errors()->add( 'permission', __("You do not have permission to send new message!", 'front-end-pm') );
				break;
			}
			
			Fep_Form::init()->validate_form_field( $action );
			if( count( fep_errors()->get_error_messages()) == 0 ){
				if( $message_id = fep_send_message() ) {
					$message = get_post( $message_id );
					
					if( 'publish' == $message->post_status ) {
						fep_success()->add( 'publish', __("Message successfully sent.", 'front-end-pm') );
					} else {
						fep_success()->add( 'pending', __("Message successfully sent and waiting for admin moderation.", 'front-end-pm') );
					}
				} else {
					fep_errors()->add( 'undefined', __("Something wrong. Please try again.", 'front-end-pm') );
				}
			}
			
		break;
		case 'reply' :
			
			if( isset( $_GET['fep_id'] ) ){
				$pID = absint( $_GET['fep_id'] );
			} else {
				$pID = !empty($_GET['id']) ? absint($_GET['id']) : 0;
			}
			$parent_id = fep_get_parent_id( $pID );
			
			if ( ! fep_current_user_can( 'send_reply', $parent_id ) ){
				fep_errors()->add( 'permission', __("You do not have permission to send reply to this message!", 'front-end-pm') );
				break;
			}
				
			Fep_Form::init()->validate_form_field( 'reply' );
			if( count( fep_errors()->get_error_messages()) == 0 ){
				if( $message_id = fep_send_message() ) {
					$message = get_post( $message_id );
					
					if( 'publish' == $message->post_status ) {
						fep_success()->add( 'publish', __("Message successfully sent.", 'front-end-pm') );
					} else {
						fep_success()->add( 'pending', __("Message successfully sent and waiting for admin moderation.", 'front-end-pm') );
					}
				} else {
					fep_errors()->add( 'undefined', __("Something wrong. Please try again.", 'front-end-pm') );
				}
			}
			
		break;
		case 'bulk_action' :
		case 'announcement_bulk_action' :
		case 'directory_bulk_action' :
			$posted_bulk_action = ! empty($_POST['fep-bulk-action']) ? $_POST['fep-bulk-action'] : '';
			if( ! $posted_bulk_action )
				break;
			
			$token = ! empty($_POST['token']) ? $_POST['token'] : '';
			
			if ( ! fep_verify_nonce( $token, $action ) ) {
				fep_errors()->add( 'token', __("Invalid Token. Please try again!", 'front-end-pm') );
				break;
			}
			
			do_action( "fep_posted_bulk_{$action}", $posted_bulk_action );

		break;
		/*
		// See Fep_User_Settings Class
		case ( 'settings' == $action && ! empty( $menu['settings'] ) ) :
			
			add_action ('fep_action_form_validated', 'fep_user_settings_save', 10, 2);
			
			Fep_Form::init()->validate_form_field( 'settings' );
			
			if( count( fep_errors()->get_error_messages()) == 0 ){
				fep_success()->add( 'saved', __("Settings successfully saved.", 'front-end-pm') );
			}
			
		break;
		*/
		default:
			do_action( "fep_posted_action" );
		break;
		
	}
	
	if( defined( 'DOING_AJAX' ) && DOING_AJAX ){
		$response = array();
		if( count( fep_errors()->get_error_messages()) > 0){
			$response['fep_return'] = 'error';
		} elseif( count( fep_success()->get_error_messages()) > 0 ){
			$response['fep_return'] = 'success';
		} else {
			$response['fep_return'] = '';
		}
		$response['info'] = fep_info_output();
		
		wp_send_json( $response );
	} elseif( !empty( $_POST['fep_redirect'] ) ){
		wp_safe_redirect( $_POST['fep_redirect'] );
		exit;
	}
}

function fep_user_settings_save( $where, $fields )
{
	if( 'settings' != $where )
		return;
		
	_deprecated_function( __FUNCTION__, '5.3', 'Fep_User_Settings Class' );
	
	if( !$fields || ! is_array( $fields ) )
		return;
	
	$settings = array();
	
	foreach( $fields as $field ) {
		$settings[$field['name']] = $field['posted-value'];
	}
	$settings = apply_filters('fep_filter_user_settings_before_save', $settings );
	
	update_user_option( get_current_user_id(), 'FEP_user_options', $settings); 
}

function fep_get_participants( $message_id ){
	if( empty( $message_id ) || ! is_numeric( $message_id ) )
		return array();
	
	if( 'threaded' == fep_get_message_view() ) {
		$message_id = fep_get_parent_id( $message_id );
	}
	$participants = get_post_meta( $message_id, '_fep_participants' );
	
	if( ! $participants )
		$participants = get_post_meta( $message_id, '_participants' );
	
	return $participants;
}

function fep_get_participant_roles( $announcement_id ){
	if( empty( $announcement_id ) || ! is_numeric( $announcement_id ) )
		return array();
	
	$roles = get_post_meta( $announcement_id, '_fep_participant_roles' );
	
	if( ! $roles )
		$roles = get_post_meta( $announcement_id, '_participant_roles' );
	
	return $roles;
}

function fep_get_message_view(){
	$message_view = fep_get_option('message_view','threaded');
	$message_view = apply_filters( 'fep_get_message_view', $message_view );
	
	if( ! $message_view || ! in_array( $message_view, array( 'threaded', 'individual' ) ) )
		$message_view = 'threaded';
	
	return $message_view;
}

function fep_get_blocked_users_for_user( $userid = '' ){
	$return = array();
	
	if( $blocked_users = fep_get_user_option( 'blocked_users', '', $userid ) ){
		$blocked_users = explode( ',', $blocked_users );
		$return = array_filter( array_map( 'absint', $blocked_users ) );
	}
	return apply_filters( 'fep_get_blocked_users_for_user', $return, $userid );
}

function fep_is_user_blocked_for_user( $userid, $check_id = '' ){
	$blocked_users = fep_get_blocked_users_for_user( $userid );
	
	if( ! $check_id )
	$check_id = get_current_user_id();
	
	if( in_array( $check_id, $blocked_users ) )
	return true;
	
	return false;
}

function fep_block_users_for_user( $user_ids, $userid ='' ){
			
	if( is_numeric( $user_ids ) )
	$user_ids = array( $user_ids );
	
	if( ! $user_ids || ! is_array( $user_ids ) )
	return 0;
	
	$blocked_users = fep_get_blocked_users_for_user( $userid = '' );
	$need_block = array_diff( $user_ids, $blocked_users );
	
	if( $need_block ){
		$blocked_users = array_unique( array_merge( $blocked_users, $need_block ) );
		fep_update_user_option( 'blocked_users', implode( ',', $blocked_users ) );
	}
	return count( $need_block );
}

function fep_unblock_users_for_user( $user_ids, $userid ='' ){
	if( is_numeric( $user_ids ) )
	$user_ids = array( $user_ids );
	
	if( ! $user_ids || ! is_array( $user_ids ) )
	return 0;
	
	$blocked_users = fep_get_blocked_users_for_user( $userid = '' );
	$need_unblock = array_intersect( $blocked_users, $user_ids );
	
	if( $need_unblock ){
		$blocked_users = array_unique( array_diff( $blocked_users, $need_unblock ) );
		fep_update_user_option( 'blocked_users', implode( ',', $blocked_users ) );
	}
	return count( $need_unblock );
}

function fep_sanitize_html_class( $class ){
	if ( $class ){
		if( ! is_array( $class ) )
		$class = explode( ' ', $class );
		$class = array_map( 'sanitize_html_class', $class );
		$class = implode( ' ', array_filter( $class ) );
	}
	if( ! is_string( $class ) )
	$class = '';
	
	return $class;
}

add_filter( 'document_title_parts', 'fep_show_unread_count_in_title', 999 );

function fep_show_unread_count_in_title( $title ){
	if( fep_get_option( 'show_unread_count_in_title', 1 ) && fep_current_user_can( 'access_message' ) ){
		wp_enqueue_script( 'fep-notification-script' );
		
		if( $count = fep_get_new_message_number() ){
			$count = number_format_i18n( $count );
			$title['title'] = "($count) " . $title['title'];
		}
	}
	return $title;
}

add_filter( 'pre_get_document_title', 'fep_pre_get_document_title', 999 );

function fep_pre_get_document_title( $title ){
	
	if( ! empty( $title ) && fep_get_option( 'show_unread_count_in_title', 1 ) && fep_current_user_can( 'access_message' ) ){
		wp_enqueue_script( 'fep-notification-script' );
		
		if( $count = fep_get_new_message_number() ){
			$count = number_format_i18n( $count );
			$title = "($count) " . $title;
		}
	}
	return $title;
}

