<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Shortcodes
  {
	private static $instance;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
	
    function actions_filters()
    	{
			//ADD SHORTCODES
			add_shortcode( 'front-end-pm', array(fep_main_class::init(), 'main_shortcode_output' )); //for FRONT END PM
			add_shortcode( 'fep_shortcode_new_message_count', array($this, 'new_message_count' ) );
			add_shortcode( 'fep_shortcode_new_announcement_count', array($this, 'new_announcement_count' ) );
			add_shortcode( 'fep_shortcode_message_to', array($this, 'message_to') );
			add_shortcode( 'fep_shortcode_new_message_form', array($this, 'new_message_form') );

    	}
	
	function new_message_count( $atts = array(), $content = null, $tag = '' ){
		$atts = array_change_key_case((array)$atts, CASE_LOWER);
		$atts = shortcode_atts( array(
				'show_bracket'		=> '1',
				'hide_if_zero'		=> '1',
				'ajax'				=> '1',
				'class'				=> 'fep-font-red',
			), $atts, $tag );
		return fep_get_new_message_button( $atts );
	}
	
	function new_announcement_count( $atts = array(), $content = null, $tag = '' ){
		$atts = array_change_key_case((array)$atts, CASE_LOWER);
		$atts = shortcode_atts( array(
				'show_bracket'		=> '1',
				'hide_if_zero'		=> '1',
				'ajax'				=> '1',
				'class'				=> 'fep-font-red',
			), $atts, $tag );
		return fep_get_new_announcement_button( $atts );
	}
	
	function message_to( $atts, $content = null ) {
		$atts = shortcode_atts( array(
				'to'		=> '{current-post-author}',
				'subject'		=> '{current-post-title}',
				'text'		=> __('Contact','front-end-pm' ),
				'class'		=> 'fep-button'
			), $atts, 'fep_shortcode_message_to' );
			
			if( '{current-post-author}' == $atts['to'] ){
				$atts['to'] = get_the_author_meta('user_nicename');
			} elseif( '{current-author}' == $atts['to'] ){
				if( $nicename = fep_get_userdata( get_query_var( 'author_name' ), 'user_nicename' ) ){
					$atts['to'] = $nicename;
				} elseif( $nicename = fep_get_userdata( get_query_var( 'author' ), 'user_nicename', 'id' ) ){
					$atts['to'] = $nicename;
				}
				unset( $nicename );
			} elseif( '{um-current-author}' == $atts['to'] && function_exists( 'um_profile_id' ) ){
				$atts['to'] = fep_get_userdata( um_profile_id(), 'user_nicename', 'id' );
			} else {
				$atts['to'] = esc_html( $atts['to'] );
			}
			
			if( '{current-post-title}' == $atts['subject'] ){
				$atts['subject'] = urlencode( get_the_title() );
			} elseif( ! empty( $atts['subject'] ) ) {
				$atts['subject'] = urlencode( $atts['subject'] );
			} else {
				$atts['subject'] = false;
			}
			
			if( empty( $atts['to'] ) )
				return '';
	
		return '<a href="' . fep_query_url('newmessage', array( 'fep_to' => $atts['to'], 'message_title' => $atts['subject'] ) ) . '" class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $atts['text'] ) . '</a>';
	}
	
	function new_message_form( $atts, $content = null ){
		$atts = shortcode_atts( array(
				'to'			=> '{current-post-author}',
				'subject' 		=> '',
				'ajax'			=> '1',
				'heading'		=> __('Contact','front-end-pm' )
			), $atts, 'fep_shortcode_new_message_form' );
			
			if( '{current-post-author}' == $atts['to'] ){
				$atts['to'] = get_the_author_meta('user_nicename');
			} elseif( '{current-author}' == $atts['to'] ){
				if( $nicename = fep_get_userdata( get_query_var( 'author_name' ), 'user_nicename' ) ){
					$atts['to'] = $nicename;
				} elseif( $nicename = fep_get_userdata( get_query_var( 'author' ), 'user_nicename', 'id' ) ){
					$atts['to'] = $nicename;
				}
				unset( $nicename );
			} elseif( '{um-current-author}' == $atts['to'] && function_exists( 'um_profile_id' ) ){
				$atts['to'] = fep_get_userdata( um_profile_id(), 'user_nicename', 'id' );
			} else {
				$atts['to'] = esc_html( $atts['to'] );
			}
			
			if( '{current-post-title}' == $atts['subject'] ){
				$atts['subject'] = get_the_title();
			}
			
			extract( $atts );
			
			$to_id = fep_get_userdata( $to );
			
			if( ! fep_current_user_can('send_new_message_to', $to_id ) )
				return '';
			
			if( ! empty( $ajax )){
				wp_enqueue_script( 'fep-shortcode-newmessage' );
				add_filter( 'fep_form_attribute', array( $this, 'fep_form_attribute'), 10, 2 );
				add_filter( 'fep_form_submit_button', array( $this, 'show_ajax_img'), 10, 2 );
			}
			
			$template = fep_locate_template( 'shortcode_newmessage_form.php');
	  
		  ob_start();
		  include( $template );
		  return ob_get_clean();
	}
	
	function fep_form_attribute( $form_attr, $where ){
		if( 'shortcode-newmessage' == $where ){
			$form_attr['class'] = $form_attr['class'] . ' shortcode-newmessage-ajax';
		}
		return $form_attr;
	}
	
	function show_ajax_img( $button, $where ){
		if( 'shortcode-newmessage' == $where ){
			$button = $button . '<img src="'. FEP_PLUGIN_URL . 'assets/images/loading.gif" class="fep-ajax-img" style="display:none;"/>';
		}
		return $button;
	}
	
 } //END CLASS

add_action('init', array(Fep_Shortcodes::init(), 'actions_filters'));
