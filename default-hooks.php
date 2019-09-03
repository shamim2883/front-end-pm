<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', 'fep_register_metadata_table', 15 );
add_action( 'plugins_loaded', 'fep_create_database', 20 );

add_action( 'after_setup_theme', 'fep_include_require_files' );
add_action( 'after_setup_theme', 'fep_translation' );
add_action( 'wp_enqueue_scripts', 'fep_enqueue_scripts' );
add_action( 'wp_enqueue_scripts', 'fep_common_scripts' );
add_action( 'admin_enqueue_scripts', 'fep_common_scripts' );

add_action( 'wp_head', 'fep_notification_div', 99 );
add_action( 'fep_footer_note', 'fep_footer_credit' );
add_action( 'template_redirect','fep_auth_redirect', 99 );
add_filter( 'auth_redirect_scheme', 'fep_auth_redirect_scheme' );

add_filter( 'document_title_parts', 'fep_show_unread_count_in_title', 999 );
add_filter( 'pre_get_document_title', 'fep_pre_get_document_title', 999 );

add_filter( 'fep_pre_save_mgs_title', 'wp_strip_all_tags' );
//add_filter( 'fep_pre_save_mgs_title', 'trim' );

add_filter( 'fep_pre_save_mgs_content', 'convert_invalid_entities' );
//add_filter( 'fep_pre_save_mgs_content', 'wp_targeted_link_rel' );
add_filter( 'fep_pre_save_mgs_content', 'wp_kses_post' );
add_filter( 'fep_pre_save_mgs_content', 'balanceTags', 50 );

add_filter( 'fep_pre_save_mgs_last_reply_excerpt', 'convert_invalid_entities' );
//add_filter( 'fep_pre_save_mgs_last_reply_excerpt', 'wp_targeted_link_rel' );
add_filter( 'fep_pre_save_mgs_last_reply_excerpt', 'wp_kses_post' );
add_filter( 'fep_pre_save_mgs_last_reply_excerpt', 'balanceTags', 50 );

add_filter( 'fep_pre_save_mgs_type', 'sanitize_key' );
add_filter( 'fep_pre_save_mgs_status', 'sanitize_key' );

add_filter( 'fep_filter_message_before_send', 'fep_backticker_code_input_filter', 5 );
add_action( 'wp_loaded', 'fep_form_posted', 20 ); //After Email hook
add_action( 'fep_transition_post_status', 'fep_delete_counts_cache', 10, 3);
add_action( 'fep_transition_post_status', 'fep_send_message_transition_post_status', 10, 3);

// Display filters
add_filter( 'fep_get_the_title', 'wptexturize' );
add_filter( 'fep_get_the_title', 'convert_chars' );
add_filter( 'fep_get_the_title', 'trim' );

global $wp_embed;
add_filter( 'fep_get_the_content', array( $wp_embed, 'run_shortcode' ), 8 );
add_filter( 'fep_get_the_content', array( $wp_embed, 'autoembed'), 8 );
add_filter( 'fep_get_the_content', 'wptexturize' );
add_filter( 'fep_get_the_content', 'convert_smilies', 20 );
add_filter( 'fep_get_the_content', 'wpautop' );
add_filter( 'fep_get_the_content', 'shortcode_unautop' );

add_filter( 'fep_get_the_excerpt', 'wptexturize' );
add_filter( 'fep_get_the_excerpt', 'convert_smilies' );
add_filter( 'fep_get_the_excerpt', 'convert_chars' );

add_filter( 'fep_get_the_date', 'fep_format_date' );

