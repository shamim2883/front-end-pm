<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Form {
	private static $instance;
	
	private $priority = 0;
	
	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
		
	function actions_filters(){
	}
	
	public function form_fields( $where = 'newmessage' ){
		$wp_roles = wp_roles()->roles;
		$roles = array();
		foreach( $wp_roles as $role => $role_info ){
			$roles[ $role ] = translate_user_role( $role_info['name'] );
		}
		$roles = apply_filters( 'fep_filter_to_roles_to_create_announcement', $roles );
		$fields = array(
			'message_to' => array(
				'label'					=> __( 'To', 'front-end-pm' ),
				//'description'			=> __( 'Name of the recipient you want to send message.', 'front-end-pm' ),
				'type'					=> 'message_to',
				'required'				=> true,
				'placeholder'			=> __( 'Name of the recipient.', 'front-end-pm' ),
				'noscript-placeholder'	=> __( 'Username of the recipient.', 'front-end-pm' ),
				'value'					=> '',
				'id' 					=> 'fep-message-to',
				'name'					=> 'message_to',
				'class' 				=> 'input-text',
				'suggestion'			=> ( fep_get_option( 'show_autosuggest', 1 ) || fep_is_user_admin() ),
				'priority'				=> 5,
			),
			'announcement_roles' => array(
				'label'					=> __( 'To Roles', 'front-end-pm' ),
				'type'					=> 'checkbox',
				'multiple'				=> true,
				'options'				=> $roles,
				'required'				=> true,
				'priority'				=> 7,
				'where'					=> 'new_announcement',
			),
			'message_title' => array(
				'label'					=> __( 'Subject', 'front-end-pm' ),
				//'description'			=> __( 'Enter your message subject here', 'front-end-pm' ),
				'type'					=> 'text',
				'required'				=> true,
				'placeholder'			=> __( 'Subject', 'front-end-pm' ),
				'minlength'				=> 5,
				'maxlength'				=> 100,
				'disabled'				=> false,
				'value'					=> '',
				'id'					=> 'message_title',
				'name'					=> 'message_title',
				'class'					=> 'input-text',
				'priority'				=> 10,
				'where'					=> array( 'newmessage', 'shortcode-newmessage', 'new_announcement' ),
			),
			'message_content' => array(
				'label'					=> __( 'Message', 'front-end-pm' ),
				'type'					=> ( 'shortcode-newmessage' == $where ) ? 'textarea' : fep_get_option( 'editor_type','wp_editor' ),
				//Ajax form submit creating problem with wp_editor
				'required'				=> true,
				'minlength'				=> 10,
				'maxlength'				=> 5000,
				'placeholder'			=> '',
				'priority'				=> 15,
				'value'					=> '',
				'where'					=> array( 'newmessage', 'reply', 'shortcode-newmessage', 'new_announcement' ),
			),
			'shortcode-message-to' => array(
				'type'					=> 'shortcode-message-to',
				'name'					=> 'message_to',
				'value'					=> '',
				'where'					=> 'shortcode-newmessage',
			),
			'token' => array(
				'type'			=> 'wp_token',
				'value'			=> wp_create_nonce( 'fep-form' ),
				'token-action'	=> 'fep-form',
				'where'			=> array( 'newmessage', 'reply', 'shortcode-newmessage', 'new_announcement', 'settings' ),
			),
			'fep_parent_id' => array(
				'type'			=> 'fep_parent_id',
				'value'			=> 0,
				'priority'		=> 30,
				'where'			=> array( 'reply' ),
			),
			'allow_messages' => array(
				'type'			=> 'checkbox',
				'value'			=> fep_get_user_option( 'allow_messages', 1),
				'cb_label'		=> __("Allow others to send me messages?", 'front-end-pm' ),
				'priority'		=> 10,
				'where'			=> 'settings'
			),
			'allow_emails' => array(
				'type'			=> 'checkbox',
				'value'			=> fep_get_user_option( 'allow_emails', 1),
				'cb_label'		=> __("Email me when I get new messages?", 'front-end-pm' ),
				'priority'		=> 20,
				'where'			=> 'settings'
			),
			'allow_ann' => array(
				'type'			=> 'checkbox',
				'value'			=> fep_get_user_option( 'allow_ann', 1),
				'cb_label'		=> __("Email me when new announcement is published?", 'front-end-pm' ),
				'priority'		=> 30,
				'where'			=> 'settings'
			),
			'fep_action' => array(
				'type'  => 'hidden',
				'value' => $where,
				'where' => array( 'newmessage', 'reply', 'shortcode-newmessage', 'new_announcement', 'settings' ),
			),
		);
		if ( fep_get_option( 'block_other_users', 1 ) ) {
			$fields['blocked_users'] = array(
				'label'			=> __( 'Blocked Users', 'front-end-pm' ),
				'type'			=>  'text',
				'value'			=> '', //fep_get_user_option( 'blocked_users', '' ),
				'priority'		=> 40,
				'where'			=> 'settings',
			);
		}
		if ( '1' == fep_get_option( 'allow_attachment', 1 ) ) {
			$fields['fep_upload'] = array(
				'type'        => 'file',
				'value'    => '',
				'priority'    => 20,
				'where'    => array( 'newmessage', 'reply', 'shortcode-newmessage', 'new_announcement' )
			);
		}
		$fields = apply_filters( 'fep_form_fields', $fields, $where );
		foreach ( $fields as $key => $field ){
			if ( empty( $field['where'] ) ){
				$field['where'] = array( 'newmessage' );
			}
			if( is_array( $field['where'] ) ){
				if ( ! in_array(  $where, $field['where'] ) ){
					unset( $fields[ $key ] );
					continue;
				}
			} else {
				if ( $where != $field['where'] ){
					unset( $fields[ $key ] );
					continue;
				}
			}
			$this->priority += 2;
			$defaults = array(
				'label'			=> '',
				'key'			=> $key,
				'type'			=> 'text',
				'name'			=> $key,
				'class'			=> '',
				'id'			=> $key,
				'value'			=> '',
				'placeholder'	=> '',
				'priority'		=> $this->priority,
			);
			$fields[ $key ] = wp_parse_args( $field, $defaults );
		}
		$fields = apply_filters( 'fep_form_fields_after_process', $fields, $where );
		uasort( $fields, 'fep_sort_by_priority' );
		return $fields;
	}
	
	function field_output( $field, $errors ){
		if ( $errors->get_error_message( $field['id'] ) ){
			printf( '<div class="fep-error">%s</div>', $errors->get_error_message( $field['id'] ) );
			$errors->remove( $field['id'] );
		}
		$attrib = ''; 
		if ( ! empty( $field['required'] ) ) $attrib .= ' required = "required"';
		if ( ! empty( $field['readonly'] ) ) $attrib .= ' readonly = "readonly"';
		if ( ! empty( $field['disabled'] ) ) $attrib .= ' disabled = "disabled"';
		if ( ! empty( $field['minlength'] ) ) $attrib .= ' minlength = "' . absint( $field['minlength'] ) . '"';
		if ( ! empty( $field['maxlength'] ) ) $attrib .= ' maxlength = "' . absint( $field['maxlength'] ) . '"';
		if ( ! empty( $field['multiple'] ) && 'select' == $field['type'] ) $attrib .= ' multiple = "multiple"';

		$attrib = apply_filters( 'fep_filter_form_field_attrib', $attrib, $field, $errors );
		 
		if ( ! empty( $field['class'] ) ){
			$field['class'] = fep_sanitize_html_class( $field['class'] );
		}
		
		?>
		<div class="fep-form-field fep-form-field-<?php echo esc_attr( $field['id'] ); ?>"><?php if ( ! empty( $field['label'] ) ) { ?>
			<div class="fep-label"><label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ) ; ?>: <?php if ( ! empty( $field['required'] ) ) : ?><span class="required">*</span><?php endif; ?></label></div>
			<?php } ?>
			<div class="fep-field"><?php
			
			switch( $field['type'] ) {
			
				case has_action( 'fep_form_field_output_' . $field['type'] ):
					do_action( 'fep_form_field_output_' . $field['type'], $field, $errors );
					break;
				case 'text' :
				case 'email' :
				case 'url' :
				case 'number' :
				case 'hidden' :
				case 'submit' :
					printf( '<input type="%1$s" id="%2$s" class="%3$s" name="%4$s" placeholder="%5$s" value="%6$s" %7$s />',
						esc_attr( $field['type'] ),
						esc_attr( $field['id'] ),
						$field['class'],
						esc_attr( $field['name'] ),
						esc_attr( $field['placeholder'] ),
						esc_attr( $field['posted-value' ] ),
						$attrib
					);
					break;
				case 'message_to' :
					if( isset( $_REQUEST['fep_to'] ) ) {
						$to = $_REQUEST['fep_to'];
					} elseif( isset( $_REQUEST['to'] ) ) {
						$to = $_REQUEST['to'];
					} else {
						$to = '';
					}
					if( ! empty( $field['posted-value' ] ) ) {
						$message_to = fep_get_userdata( $field['posted-value' ], 'user_nicename' );
						$message_top = fep_user_name( fep_get_userdata( $message_to, 'ID' ) );
					} elseif( $to ){
						$support = array(
							'nicename' 	=> true,
							'id' 		=> true,
							'email' 	=> true,
							'login' 	=> true,
						);
						$support = apply_filters( 'fep_message_to_support', $support );
						if ( ! empty( $support['nicename'] ) && $user = fep_get_userdata( $to, 'user_nicename' ) ) {
							$message_to = $user;
							$message_top = fep_user_name( fep_get_userdata( $user, 'ID' ) );
						} elseif( is_numeric( $to ) && ! empty( $support['id'] ) && $user = fep_get_userdata( $to, 'user_nicename', 'id' ) ) {
							$message_to = $user;
							$message_top = fep_user_name( fep_get_userdata( $user, 'ID' ) );
						} elseif ( is_email( $to ) && ! empty( $support['email'] ) && $user = fep_get_userdata( $to, 'user_nicename', 'email' ) ) {
							$message_to = $user;
							$message_top = fep_user_name( fep_get_userdata( $user, 'ID' ) );
						} elseif ( ! empty( $support['login'] ) && $user = fep_get_userdata( $to, 'user_nicename', 'login' ) ) {
							$message_to = $user;
							$message_top = fep_user_name( fep_get_userdata( $user, 'ID' ) );
						} else {
							$message_to = '';
							$message_top = '';
						}
					} else {
						$message_to = '';
						$message_top = '';
					}
					if( ! empty( $field['suggestion'] ) ) : ?>
						<?php wp_enqueue_script( 'fep-script' ); ?>
						<input type="hidden" name="message_to" id="fep-message-to" autocomplete="off" value="<?php echo esc_attr( $message_to ); ?>" />
						<input type="text" class="<?php echo $field['class']; ?>" name="message_top" id="fep-message-top" autocomplete="off" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $message_top ); ?>" />
						<div id="fep-result"></div>
					<?php else : ?>
						<input type="text" class="<?php echo $field['class']; ?>" name="message_to" id="fep-message-top" placeholder="<?php echo esc_attr( $field['noscript-placeholder'] ); ?>" autocomplete="off" value="<?php echo esc_attr( $message_to ); ?>" />
					<?php endif;
					break;
				case "textarea":
					printf( '<textarea id="%1$s" class="%2$s" name="%3$s" placeholder="%4$s" %5$s >%6$s</textarea>',
						esc_attr( $field['id'] ),
						$field['class'],
						esc_attr( $field['name'] ),
						esc_attr( $field['placeholder'] ),
						$attrib,
						esc_textarea( $field['posted-value' ] )
					);
					break;
				case "wp_editor" :
					wp_editor( wp_kses_post( $field['posted-value' ] ), $field['id'], array( 'textarea_name' => $field['name'], 'editor_class' => $field['class'], 'media_buttons' => false ) );
					break;
				case "teeny" :
					wp_editor( wp_kses_post( $field['posted-value' ] ), $field['id'], array( 'textarea_name' => $field['name'], 'editor_class' => $field['class'], 'teeny' => true, 'media_buttons' => false) );
					break;
						
				case "checkbox" :
					if( ! empty( $field['multiple' ] ) ) {
						foreach( $field['options' ] as $key => $name ) {
							printf( '<label><input type="%1$s" id="%2$s" class="%3$s" name="%4$s" value="%5$s" %6$s /> %7$s</label>',
								'checkbox',
								esc_attr( $field['id'] ),
								$field['class'],
								esc_attr( $field['name'] . '[]' ),
								esc_attr( $key ),
								checked( in_array( $key, (array) $field['posted-value' ] ), true, false ),
								esc_attr( $name )
							);
						}
					} else {
						printf( '<label><input type="%1$s" id="%2$s" class="%3$s" name="%4$s" value="%5$s" %6$s /> %7$s</label>',
							'checkbox',
							esc_attr( $field['id'] ),
							$field['class'],
							esc_attr( $field['name'] ),
							'1',
							checked( $field['posted-value' ], '1', false ),
							esc_attr( $field['cb_label'] )
						);
					}
					break;
				case "select" :
					if( ! empty( $field['multiple' ] ) ) {
						?>
						<select id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]"<?php echo $attrib; ?>>
							<?php foreach( $field['options'] as $key => $name ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( $key, (array) $field['posted-value' ] ), true ); ?>><?php echo esc_attr( $name ); ?></option>
							<?php } ?>
						</select>
						<?php
					} else {
						?>
						<select id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>"<?php echo $attrib; ?>>
							<?php foreach( $field['options'] as $key => $name ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $field['posted-value' ], $key ); ?>><?php echo esc_attr( $name ); ?></option>
							<?php } ?>
						</select>
						<?php
					}
					break;
				case "radio" :
					foreach( $field['options'] as $key => $name ) { ?>
						<label><input type="radio" class="<?php echo $field['class']; ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $field['posted-value' ], $key ); ?> /> <?php echo esc_attr( $name ); ?></label><br />
					<?php }
					break;
				case 'token' :
				case 'wp_token' :
				case 'shortcode-message-to' :
					printf( '<input type="%1$s" id="%2$s" class="%3$s" name="%4$s" value="%5$s" %6$s />',
						'hidden',
						esc_attr( $field['id'] ),
						$field['class'],
						esc_attr( $field['name'] ),
						esc_attr( $field['value' ] ),
						$attrib
					);
					break;
				case 'fep_parent_id' :
					printf( '<input type="%1$s" id="%2$s" class="%3$s" name="%4$s" value="%5$s" %6$s />',
						'hidden',
						esc_attr( $field['id'] ),
						$field['class'],
						esc_attr( $field['name'] ),
						esc_attr( $field['posted-value' ] ),
						$attrib
					);
					break;
				case 'file' :
					wp_enqueue_script( 'fep-attachment-script' );
					?>
						<div id="fep_upload">
							<div class="fep-attachment-field-div">
								<input class="fep-attachment-field-input" type="file" name="<?php echo esc_attr( $field['name'] ); ?>[]" /><a href="#" class="fep-attachment-field-a"><?php echo __( 'Remove', 'front-end-pm' ); ?></a>
							</div>
						</div>
						<a id="fep-attachment-field-add" href="#"><?php echo __( 'Add more files', 'front-end-pm' ) ; ?></a>
						<div id="fep-attachment-note"></div>
					<?php
					break;
				case "action_hook" :
					$field['hook'] = empty( $field['hook'] ) ? $field['key'] : $field['hook'] ;
					do_action( $field['hook'], $field );
					break;
				case "function" :
					$field['function'] = empty( $field['function'] ) ? $field['key'] : $field['function'];
					if( is_callable( $field['function'] ) ){
						call_user_func( $field['function'], $field );
					}
					break;
				default :
						printf(__( 'No Function or Hook defined for %s field type', 'front-end-pm' ), $field['type'] );
					break;
			}
			if ( ! empty( $field['description'] ) ) {
				?><div class="description"><?php echo  wp_kses_post( $field['description'] ); ?></div><?php
			}

		?>
			</div>
		</div><?php 
	}
	
	public function validate( $field, $errors ){
		if( ! empty( $field['required'] ) && empty( $field['posted-value'] ) ){
			$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf( __( '%s required.', 'front-end-pm' ), esc_html( $field['label'] ) ) );
		} elseif( (! empty( $field['readonly'] ) || ! empty( $field['disabled'] ) /* || $field['type'] == 'hidden' */ ) && $field['value'] != $field['posted-value'] ){
			$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf( __( '%s can not be changed.', 'front-end-pm' ), esc_html( $field['label'] ) ) );
		} elseif( ! empty( $field['minlength'] ) && strlen( $field['posted-value'] ) < absint( $field['minlength'] ) ){
			$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf( __( '%s minlength %d.', 'front-end-pm' ), esc_html( $field['label'] ), absint( $field['minlength'] ) ) );
		} elseif( ! empty( $field['maxlength'] ) && strlen( $field['posted-value'] ) > absint( $field['maxlength'] ) ){
			$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf( __( '%s maxlength %d.', 'front-end-pm' ), esc_html( $field['label'] ), absint( $field['maxlength'] ) ) );
		}
	}
	
	function field_validate( $field, $errors ){
		$this->validate( $field, $errors );
			
		switch( $field['type'] ) {
			case has_action( 'fep_form_field_validate_' . $field['type'] ):
				do_action( 'fep_form_field_validate_' . $field['type'], $field, $errors );
				break;
			case 'email' :
				if( $field['posted-value'] && ! is_email( $field['posted-value'] ) ){
					$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf(__("Please provide valid email address for %s.", 'front-end-pm' ), esc_html( $field['label'] ) ) );
				}
				break;
			case 'number' :
				if( $field['posted-value'] && ! is_numeric( $field['posted-value'] ) ){
					$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf(__("%s is not a valid number.", 'front-end-pm' ), esc_html( $field['label'] ) ) );
				}
				break;
			case 'token':
				if ( ! fep_verify_nonce( $field['posted-value'], $field['token-action'] ) ) {
					$errors->add( $field['id'], __("Invalid Token. Please try again!", 'front-end-pm' ) );
				}
			break;
			case 'wp_token':
				if ( ! wp_verify_nonce( $field['posted-value'], $field['token-action'] ) ) {
					$errors->add( $field['id'], __("Invalid Token. Please try again!", 'front-end-pm' ) );
				}
			break;
			case 'message_to' :
			case 'shortcode-message-to' :
				if ( ! empty( $_POST['message_to'] ) ) {
					$preTo = $_POST['message_to'];
				} else {
					$preTo = ( isset( $_POST['message_top'] ) ) ? $_POST['message_top']: ''; 
				}
				$preTo = apply_filters( 'fep_preto_filter', $preTo ); //return user_nicename
				
				if( ! is_array( $preTo ) ){
					$preTo = array( $preTo );
				}
				$_POST['message_to_id'] = array();
				
				foreach ( $preTo as $pre ) {
					$to = fep_get_userdata( $pre );
					
					if( $to && get_current_user_id() != $to) {
						$_POST['message_to_id'][] = $to;
						if ( ! fep_current_user_can( 'send_new_message_to', $to ) ) {
							$errors->add( $field['id'] .'-permission' , sprintf(__("%s does not want to receive messages!", 'front-end-pm' ), fep_user_name( $to ) ) );
						}
					} else {
						$errors->add( $field['id'] , sprintf(__( 'Invalid receiver "%s".', 'front-end-pm' ), $pre ) );
					}
				}
				if ( empty( $_POST['message_to_id'] ) ) {
					$errors->add( $field['id'] , __( 'You must enter a valid recipient!', 'front-end-pm' ) );
				}
				break;
			case 'fep_parent_id' :
				 if ( empty( $field['posted-value'] ) || ! is_numeric( $field['posted-value'] ) || fep_get_parent_id( $field['posted-value'] ) != $field['posted-value'] ) {
					$errors->add( $field['id'] , __("Invalid parent ID!", 'front-end-pm' ) );
				 } elseif ( ! fep_current_user_can( 'send_reply', $field['posted-value'] ) ) {
					$errors->add( $field['id'] , __("You do not have permission to send this message!", 'front-end-pm' ) );
				}
				break;
			case "checkbox" :
				if( ! empty( $field['multiple' ] ) ) {
					$value = $_POST[ $field['name'] ] = is_array( $field['posted-value'] ) ? $field['posted-value'] : array();
					foreach( $value as $p_value ) {
						if( ! array_key_exists( $p_value, $field['options'] ) ) {
							$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf(__("Invalid value for %s.", 'front-end-pm' ), esc_html( $field['label'] ) ) );
						}
					}
				} else {
					$_POST[ $field['name'] ] = !empty( $_POST[ $field['name'] ] ) ? 1 : 0;
				}
				break;
			case "radio" :
				if( $field['posted-value'] && ! array_key_exists( $field['posted-value'], $field['options'] ) ) {
					$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf(__("Invalid value for %s.", 'front-end-pm' ), esc_html( $field['label'] ) ) );
				}
				break;
			case "select" :
				if( ! empty( $field['multiple' ] ) ) {
					$value = $_POST[ $field['name'] ] = is_array( $field['posted-value'] ) ? $field['posted-value'] : array();
					foreach( $value as $p_value ) {
						if( ! array_key_exists( $p_value, $field['options'] ) ) {
							$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf(__("Invalid value for %s.", 'front-end-pm' ), esc_html( $field['label'] ) ) );
						}
					}
				} else {
					if( $field['posted-value'] && ! array_key_exists( $field['posted-value'], $field['options'] ) ) {
						$errors->add( $field['id'], ! empty( $field['error-message'] ) ? $field['error-message'] : sprintf(__("Invalid value for %s.", 'front-end-pm' ), esc_html( $field['label'] ) ) );
					}
				}
				break;
			case "file" :
				$mime = get_allowed_mime_types();
				$size_limit = (int) wp_convert_hr_to_bytes(fep_get_option( 'attachment_size','4MB' ) );
				$fields = (int) fep_get_option( 'attachment_no', 4);
				
				if( ! isset( $_FILES[ $field['name'] ] ) || empty( $_FILES[ $field['name'] ]['tmp_name'] ) )
					break;
				
				if( ! is_array( $_FILES[ $field['name'] ] ) || ! is_array( $_FILES[ $field['name'] ]['tmp_name'] ) ){
					$errors->add( 'AttachmentNotArray', __( 'Invalid Attachment', 'front-end-pm' ) );
					break;
				}
					
				if( $fields < count( $_FILES[ $field['name'] ]['tmp_name'] ) ){
					$errors->add( 'AttachmentCount', sprintf( __( 'Maximum %s allowed', 'front-end-pm' ), sprintf(_n( '%s file', '%s files', $fields, 'front-end-pm' ), number_format_i18n( $fields ) ) ) );
					break;
				}
				foreach( $_FILES[ $field['name'] ]['tmp_name'] as $key => $tmp_name ) {
					$file_name = isset( $_FILES[$field['name']]['name'][ $key ] ) ? basename( $_FILES[$field['name']]['name'][ $key ] ) : '' ;
			
					//if file is uploaded
					if ( $tmp_name ) {
						$attach_type = wp_check_filetype( $file_name, $mime );
						$attach_size = $_FILES[ $field['name'] ]['size'][ $key ];
						//check file size
						if ( $attach_size > $size_limit ) {
							$errors->add( 'AttachmentSize', sprintf(__( 'Attachment (%1$s) file is too big. Maximum file size allowed %2$s', 'front-end-pm' ), esc_html( $file_name), fep_get_option( 'attachment_size','4MB' ) ) );
						}
						//check file type
						if ( empty( $attach_type['type'] ) ) {
							$errors->add( 'AttachmentType', sprintf(__( "Invalid attachment file type. Allowed Types are (%s)", 'front-end-pm' ),implode( ', ',array_keys( $mime) ) ) );
						}
					} // if $tmp_name
				}// endforeach
				break;
			default :
				do_action( 'fep_form_field_validate', $field, $errors );
				break;
		}	
	}
	
	
	public function form_field_output( $where = 'newmessage', $errors= '', $value = array() ){
		$fields = $this->form_fields( $where );
		if( ! is_wp_error( $errors) ){
			$errors = fep_errors();
		}
		if( isset( $_GET['fep_id'] ) ){
			$id = absint( $_GET['fep_id'] );
		} elseif( isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
		} else {
			$id = 0;
		}
		$form_attr = array(
			'method'	=> 'post',
			'class'		=> "fep-form fep-form-{$where}"
		);
		if( 'settings' == $where ) {
			$form_attr['action'] = fep_query_url( 'settings' );
		} elseif( 'newmessage' == $where ) {
			$form_attr['action'] = fep_query_url( 'newmessage' );
		} elseif( 'reply' == $where && $id ) {
			$form_attr['action'] = fep_query_url( 'viewmessage', array( 'fep_id' => $id ) );
		} else {
			$form_attr['action'] = esc_url( add_query_arg( false, false ) );
		}
		if( isset( $fields['fep_upload'] ) ) {
			$form_attr['enctype'] = 'multipart/form-data';
		}
		$form_attr = apply_filters( 'fep_form_attribute', $form_attr, $where );
		
		$attr = array();
		foreach ( $form_attr as $k => $v ) {
			$attr[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
		
		ob_start();

		echo '<div class="front-end-pm-form">';
		echo '<form ';
		echo implode( ' ', $attr );
		echo '>';

		do_action( 'fep_before_form_fields', $where, $errors );

		foreach ( $fields as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';
			$defaults = array(
				'key'		=> $key,
				'type'		=> $type,
				'name'		=> $key,
				'class'		=> 'input-'. $type,
				'id'		=> $key,
				'value'		=> '',
			);
			$field = wp_parse_args( $field, $defaults);
			if( array_key_exists( $field['name'], $value ) ) {
				$field['value'] = $value[ $field['name'] ];
			}
			$field['posted-value'] = isset( $_REQUEST[ $field['name'] ] ) ? stripslashes_deep( $_REQUEST[ $field['name'] ] ) : $field['value'];
			
			$field = apply_filters( 'fep_filter_form_field_before_output', $field, $where );
			
			if ( has_action( 'fep_form_field_init_output_' . $field['type'] ) ) {
				do_action( 'fep_form_field_init_output_' . $field['type'], $field, $errors );
			} else {
				call_user_func( array( $this, 'field_output' ), $field, $errors );
			} 
		}
		do_action( 'fep_after_form_fields', $where, $errors, $fields );
		
		echo fep_error( $errors );
		
		if ( apply_filters( 'fep_filter_ajax_form_submit', true, $where ) ) {
			wp_enqueue_script( 'fep-form-submit' );
			echo '<div class="fep-progress-bar"><div class="fep-progress-bar-inner"></div></div>';
			echo '<div class="fep-ajax-response"></div>';
		}
		
		if( 'settings' == $where ) {
			$button_val = __( 'Save Changes', 'front-end-pm' );
		} elseif( 'reply' == $where ) {
			$button_val = __( 'Reply', 'front-end-pm' );
		} else {
			$button_val = __( 'Send Message', 'front-end-pm' );
		}
		echo apply_filters( 'fep_form_submit_button', '<button type="submit" class="fep-button">' . esc_html( $button_val ) . '</button>', $where );
		
		echo '</form>';
		echo '</div>';
		
		return apply_filters( 'fep_filter_form_output', ob_get_clean(), $where );
	}

	public function validate_form_field( $where = 'newmessage' ){
		$fields = $this->form_fields( $where );
		$errors = fep_errors();
		foreach ( $fields as $key => $field ) {
			$defaults = array(
				'key' => $key,
				'type' => ! empty( $field['type'] ) ? $field['type'] : 'text',
				'name' => $key,
				'id' => $key,
				'value' => '',
			);
			$field = wp_parse_args( $field, $defaults );
			$field['posted-value'] = isset( $_POST[ $field['name'] ] ) ? $_POST[ $field['name'] ] : '';

			if ( has_action( 'fep_form_field_init_validate_' . $field['type'] ) ) {
				do_action( 'fep_form_field_init_validate_' . $field['type'], $field, $errors );
			} else {
				call_user_func( array( $this, 'field_validate' ), $field, $errors);
			}
			$fields[ $key ] = $field;
		}
		
		do_action( 'fep_action_validate_form', $where, $errors, $fields );
		
		if( count( $errors->get_error_codes() ) == 0 ){
			//No Errors
			do_action( 'fep_action_form_validated', $where, $fields );
		} else {
		}
		return $errors;
	}
}

