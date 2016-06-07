<?php

if (!class_exists('Fep_Form'))
{
  class Fep_Form
  {
 	private static $instance;
	
	private $priority = 0;
	
	public static function init()
        {
            if(!self::$instance instanceof self) {
                self::$instance = new self;
            }
            return self::$instance;
        }
		
    function actions_filters()
    {
    }
	
	public function form_fields( $where = 'new_message' )
{
	$fields = array(
				'message_to' => array(
					'label'       => __( 'To', 'front-end-pm' ),
					'description' => __( 'Name of the receipent you want to send message.', 'front-end-pm' ),
					'type'        => 'message_to',
					'required'    => true,
					'placeholder' => __( 'Name of the receipent.', 'front-end-pm' ),
					'noscript-placeholder' => __( 'Username of the receipent.', 'front-end-pm' ),
					'value' => '',
					'id' => 'fep-message-to',
					'name' => 'message_to',
					'class' => 'input-text',
					'suggestion' => (fep_get_option('hide_autosuggest') != '1' || current_user_can('manage_options')),
					'priority'    => 5
				),
				'subject' => array(
					'label'       => __( 'Subject', 'front-end-pm' ),
					'description' => __( 'Enter your message subject here', 'front-end-pm' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'Subject', 'front-end-pm' ),
					'minlength'	=> 5,
					'maxlength' => 100,
					'disabled' => false,
					'value' => '',
					'id' => 'subject',
					'name' => 'subject',
					'class' => 'input-text',
					'priority'    => 10
				),
				'message' => array(
					'label'       => __( 'Message', 'front-end-pm' ),
					'type'        => current_user_can('manage_options') ? 'wp_editor' : fep_get_option('editor_type','wp_editor'),
					'required'    => true,
					'minlength'	=> 50,
					'maxlength' => 5000,
					'placeholder' => '',
					'priority'    => 15,
					'value'     => '',
					'where'	=> array( 'new_message', 'reply' )
				),
				'token' => array(
					'type'        => 'token',
					'value'    => fep_create_nonce('fep_message'),
					'token-action'    => 'fep_message',
					'where'    => array( 'new_message', 'reply' )
				),
				'fep_parent_id' => array(
					'type'        => 'fep_parent_id',
					'value'    	=> 0,
					'priority'    => 30,
					'where'	=> array( 'reply' )
				),
				'allow_messages' => array(
					'type'        => 'checkbox',
					'value'    => fep_get_user_option( 'allow_messages', 1),
					'cb_label'    => __("Allow others to send me messages?", 'front-end-pm'),
					'where'    => 'settings'
				),
				'allow_emails' => array(
					'type'        => 'checkbox',
					'value'    => fep_get_user_option( 'allow_emails', 1),
					'cb_label'    => __("Email me when I get new messages?", 'front-end-pm'),
					'where'    => 'settings'
				),
				'allow_ann' => array(
					'type'        => 'checkbox',
					'value'    => fep_get_user_option( 'allow_ann', 1),
					'cb_label'    => __("Email me when new announcement is published?", 'front-end-pm'),
					'where'    => 'settings'
				),
				'settings_token' => array(
					'type'        => 'token',
					'value'    => fep_create_nonce('settings'),
					'token-action'    => 'settings',
					'where'    => 'settings'
				),
					
				);
			if ( '1' == fep_get_option('allow_attachment',0)) {
				$fields['fep_upload'] = array(
					'type'        => 'file',
					'value'    => '',
					'priority'    => 20,
					'where'    => array( 'new_message', 'reply' )
				);
			}
				
		$fields = apply_filters( 'fep_fields_before_process', $fields );

		
		foreach ( $fields as $key => $field )
		{
			if ( empty($field['where']) )
				$field['where'] = array( 'new_message' );
			
			if( is_array($field['where'])){
				if ( ! in_array(  $where, $field['where'] )){
					unset($fields[$key]);
					continue;
				}
			} else {
				if ( $where != $field['where'] ){
					unset($fields[$key]);
					continue;
				}
			}
			$this->priority += 2;
			
			$defaults = array(
					'label'	=> '',
					'key'		=> $key,
					'type'		=> 'text',
					'name'		=> $key,
					'class'		=> '',
					'id'		=> $key,
					'value'		=> '',
					'placeholder'=> '',
					'priority'	=> $this->priority
					);
			$fields[$key] = wp_parse_args( $fields[$key], $defaults);
		}
		
		$fields = apply_filters( 'fep_fields', $fields );

		uasort( $fields, 'fep_sort_by_priority' );

		return $fields;
}
	
function field_output( $field, $errors )
	{
		 if ( !empty($errors->get_error_message($field['id']))) : ?>
		<p class="error">
		<?php echo $errors->get_error_message($field['id']); ?>
		<?php $errors->remove($field['id']); ?>
		</p>
		<?php endif;
		$attrib = ''; 
		 if ( ! empty( $field['required'] ) ) $attrib .= 'required = "required" ';
		 if ( ! empty( $field['readonly'] ) ) $attrib .= 'readonly = "readonly" ';
		 if ( ! empty( $field['disabled'] ) ) $attrib .= 'disabled = "disabled" ';
		
		?><div class="fep-form-field"><?php if ( !empty($field['label']) ) { ?>
			<div class="fep-label"><label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ) ; ?>: <?php if ( ! empty( $field['required'] ) ) : ?><span class="required">*</span><?php endif; ?></label></div>
			<?php } ?>
			<div class="fep-field"><?php
			
		switch( $field['type'] ) {
		
				case 'text' :
				case 'email' :
				case 'url' :
				case 'number' :
				case 'hidden' :
				case 'submit' :
					?><input id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" type="<?php esc_attr_e( $field['type'] ); ?>" name="<?php esc_attr_e( $field['name'] ); ?>" placeholder="<?php esc_attr_e( $field['placeholder'] ); ?>" value="<?php esc_attr_e( $field['posted-value' ] ); ?>" <?php echo $attrib; ?> /><?php

					break;
				case 'auto' : //Not Ready Yet
				 wp_enqueue_script( 'fep-script' );
							?><input id="myAutocomplete" type="text" /><?php

					break;
				case 'message_to' :
							$to = (isset($_REQUEST['to']))? $_REQUEST['to']:'';
							$message_to = ( isset( $_POST['message_to'] ) ) ? esc_attr( $_POST['message_to'] ): fep_get_userdata( $to, 'user_nicename' );
							$message_top = ( isset( $_POST['message_top'] ) ) ? esc_attr( $_POST['message_top'] ): fep_get_userdata($to, 'display_name');

								 if( ! empty($field['suggestion'])) : ?>
							<?php wp_enqueue_script( 'fep-script' ); ?>
							
							<input type="hidden" name="message_to" id="fep-message-to" autocomplete="off" value="<?php echo $message_to; ?>" />
							
							<input type="text" class="<?php echo esc_attr( $field['class'] ); ?>" name="message_top" id="fep-message-top" autocomplete="off" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo $message_top; ?>" />
							<div id="fep-result"></div>
							
						<?php else : ?>
							
							<input type="text" class="<?php echo esc_attr( $field['class'] ); ?>" name="message_to" id="fep-message-top" placeholder="<?php echo esc_attr( $field['noscript-placeholder'] ); ?>" autocomplete="off" value="<?php echo $message_to; ?>" />
							
						<?php endif;

					break;
				case "textarea" :

							?><textarea id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" cols="50" name="<?php esc_attr_e( $field['name'] ); ?>" placeholder="<?php esc_attr_e( $field['placeholder'] ); ?>" <?php echo $attrib; ?>><?php echo esc_textarea( $field['posted-value' ] ); ?></textarea><?php

					break;
					
				case "wp_editor" :
				
							wp_editor( wp_kses_post( $field['posted-value' ] ), $field['id'], array( 'textarea_name' => $field['name'], 'editor_class' => $field['class'], 'teeny' => true, 'media_buttons' => false) );

					break;
					
				case "checkbox" :

							?><label><input id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" name="<?php esc_attr_e( $field['name'] ); ?>" type="checkbox" value="1" <?php checked( '1', $field['posted-value' ] ); ?> /> <?php esc_attr_e( $field['cb_label'] ); ?></label><?php

					break;
					
				case "select" :

							?><select id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" name="<?php esc_attr_e( $field['name'] ); ?>"><?php
									foreach( $field['options'] as $key => $name ) {
										?><option value="<?php esc_attr_e( $key ); ?>" <?php selected( $field['posted-value' ], $key ); ?>><?php esc_attr_e( $name ); ?></option><?php }
							?></select><?php
					break;
				
				case "radio" :

						foreach( $field['options'] as $key => $name ) {
							?><label><input type="radio" class="<?php esc_attr_e( $field['class'] ); ?>" name="<?php esc_attr_e( $field['name'] ); ?>" value="<?php esc_attr_e( $key ); ?>" <?php checked( $field['posted-value' ], $key ); ?> /><?php esc_attr_e( $name ); ?></label><?php }
					break;
					
				case 'token' :
					?><input id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" type="hidden" name="<?php esc_attr_e( $field['name'] ); ?>" value="<?php esc_attr_e( $field['value' ] ); ?>" <?php echo $attrib; ?> /><?php

					break;
				case 'fep_parent_id' :
					?><input id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" type="hidden" name="<?php esc_attr_e( $field['name'] ); ?>" value="<?php esc_attr_e( $field['posted-value' ] ); ?>" <?php echo $attrib; ?> /><?php

					break;
					
				case 'file' :
					wp_enqueue_script( 'fep-attachment-script' );
					?>
						<div id="fep_upload">
							<div id="p-0">
								<input id="<?php esc_attr_e( $field['id'] ); ?>" class="<?php esc_attr_e( $field['class'] ); ?>" type="file" name="<?php esc_attr_e( $field['name'] ); ?>[]" /><a href="#" onclick="fep_remove_element('p-0'); return false;" class="fep-attachment-field"><?php echo __('Remove', 'front-end-pm') ; ?></a>
							</div>
						</div>
						<a id="fep-attachment-field-add" href="#" onclick="fep_add_new_file_field(); return false;"><?php echo __('Add new field', 'front-end-pm') ; ?></a>
						<div id="fep-attachment-note"></div>
						
							<?php
			
					break;
					
				case "action_hook" :

						$field['hook'] = empty($field['hook'] ) ? $field['key'] : $field['hook'] ;
		
						do_action($field['hook'], $field );
						
					break;
				case "function" :
					$field['function'] = empty($field['function'] ) ? $field['key'] : $field['function'];
		
					if(is_callable($field['function']))
					call_user_func($field['function'], $field );
					
				default :
						if ( has_action( 'fep_form_field_output_' . $field['type'] ) ) {
						
							do_action( 'fep_form_field_output_' . $field['type'], $field );
						
						} else {
							printf(__('No Function or Hook defined for %s field type', 'front-end-pm'), $field['type'] );
							}
					
					break;
				
				}
			
		if ( ! empty($field['description']) ) {
			?><div class="description"><?php echo $field['description']; ?></div><?php
		}

				?></div></div><?php 
	}
	
	public function validate( $field, $errors )
		{
			if( ! empty( $field['required']) && empty($field['posted-value']) )
			{
				$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("%s required.", "front-end-pm"), esc_html( $field['label'] )));
			} 
			elseif( (! empty( $field['readonly']) || ! empty( $field['disabled']) /* || $field['type'] == 'hidden' */ ) && $field['value'] != $field['posted-value'] )
			{
				$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("%s can not be changed.", "front-end-pm"), esc_html( $field['label'] )));
			} 
			elseif( ! empty( $field['minlength']) && strlen($field['posted-value']) < absint($field['minlength']) )
			{
				$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("%s minlength %d.", "front-end-pm"), esc_html( $field['label'] ), absint($field['minlength'])));
			} 
			elseif( ! empty( $field['maxlength']) && strlen($field['posted-value']) > absint($field['maxlength']) )
			{
				$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("%s maxlength %d.", "front-end-pm"), esc_html( $field['label'] ), absint($field['maxlength'])));
			}
		
		}
	
	function field_validate( $field, $errors )
	{
			$this->validate( $field, $errors );
			
		switch( $field['type'] ) {
		
				case 'email' :
					if( ! is_email($field['posted-value']) )
						{
							$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("Please provide valid email address for %s.", "front-end-pm"), esc_html( $field['label'] )));
						}
				break;
				case 'number' :
					if( ! is_numeric($field['posted-value']) )
						{
							$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("%s is not a valid number.", "front-end-pm"), esc_html( $field['label'] )));
						}
				break;
				case 'token':
					if (!fep_verify_nonce( $field['posted-value'], $field['token-action'])) {
        				$errors->add( $field['id'], __("Invalid Token. Please try again!", 'front-end-pm'));
					}
				break;
				case 'message_to' :
					if (!empty($_POST['message_to'])) {
					  	$preTo = $_POST['message_to'];
					 } else {
					  	$preTo = ( isset( $_POST['message_top'] ) ) ? $_POST['message_top']: ''; 
					 }
					  
					  $preTo = apply_filters( 'fep_preto_filter', $preTo ); //return user_nicename
					  
					  if( is_array( $preTo ) ) {
					  $_POST['message_to_id'] = array();
					  
					  	foreach ( $preTo as $pre ) {
							if( $to = fep_get_userdata( $pre ) && get_current_user_id() != $to) {
								$_POST['message_to_id'][] = $to;
								if ( fep_get_user_option( 'allow_messages', 1, $to ) != '1') {
									$errors->add( $field['id'] , sprintf(__("%s does not want to receive messages!", 'front-end-pm'), fep_get_userdata( $to, 'display_name', 'id')));
								}
							} else {
								$errors->add( $field['id'] , sprintf(__("Invalid receiver %s.", "front-end-pm"), $pre ) );
							}
						}
					  } else {
					  	$to = $_POST['message_to_id'] = fep_get_userdata( $preTo ); //return ID;
						if (fep_get_user_option( 'allow_messages', 1, $to ) != '1') {
							$errors->add( $field['id'] , sprintf(__("%s does not want to receive messages!", 'front-end-pm'), fep_get_userdata( $to, 'display_name', 'id')));
						}
						if( get_current_user_id() == $to ) {
							$errors->add( $field['id'] , __('You can not message yourself!', 'front-end-pm'));
						}
					  }
					  
					  if ( empty($_POST['message_to_id'])) {
							$errors->add( $field['id'] , __('You must enter a valid recipient!', 'front-end-pm'));
						}

					break;
				case 'fep_parent_id' :
					 if ( empty($field['posted-value']) || $field['posted-value'] != absint($field['posted-value']) || fep_get_parent_id( $field['posted-value'] ) != $field['posted-value'] ) {
					 		$errors->add( $field['id'] , __("Invalid parent ID!", 'front-end-pm'));
					 } elseif ( ! $participants = get_post_meta( $field['posted-value'], '_participants' ) || ! in_array( get_current_user_id(), $participants ) ) {
						  	$errors->add( $field['id'] , __("You do not have permission to send this message!", 'front-end-pm'));
						}
		
					break;
					
				case "checkbox" :
						if( ! empty( $field['multiple' ] ) ) {
							$value = $_POST[$field['name']] = is_array( $field['posted-value'] ) ? $field['posted-value'] : array();
							foreach( $value as $p_value ) {
								if( ! array_key_exists( $p_value, $field['options'] ) ) {
									$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("Invalid value for %s.", "front-end-pm"), esc_html( $field['label'] )));
								}
							}
						} else {
							$_POST[$field['name']] = !empty( $_POST[$field['name']] ) ? 1 : 0;
						}

					break;
					
				case "radio" :
				case "select" :

						if( ! array_key_exists( $field['posted-value'], $field['options'] ) ) {
							$errors->add($field['id'], ! empty($field['error-message'] ) ? $field['error-message'] : sprintf(__("Invalid value for %s.", "front-end-pm"), esc_html( $field['label'] )));
							}
					break;
				
				case "file" :
					$mime = get_allowed_mime_types();

					$size_limit = (int) wp_convert_hr_to_bytes(fep_get_option('attachment_size','4MB'));
					$fields = (int) fep_get_option('attachment_no', 4);
				
					for ($i = 0; $i < $fields; $i++) {
						$tmp_name = isset( $_FILES[$field['name']]['tmp_name'][$i] ) ? basename( $_FILES[$field['name']]['tmp_name'][$i] ) : '' ;
						$file_name = isset( $_FILES[$field['name']]['name'][$i] ) ? basename( $_FILES[$field['name']]['name'][$i] ) : '' ;
				
						//if file is uploaded
						if ( $tmp_name ) {
							$attach_type = wp_check_filetype( $file_name );
							$attach_size = $_FILES[$field['name']]['size'][$i];
				
							//check file size
							if ( $attach_size > $size_limit ) {
								$errors->add('AttachmentSize', sprintf(__( "Attachment (%s) file is too big", 'front-end-pm' ), esc_html($file_name) ));
							}
				
							//check file type
							if ( !in_array( $attach_type['type'], $mime ) ) {
								$errors->add('AttachmentType', sprintf(__( "Invalid attachment file type. Allowed Types are (%s)", 'front-end-pm' ),implode(', ',array_keys($mime))));
							}
						} // if $filename
					}// endfor
					break;
					
				default :
						
					do_action( 'fep_form_field_validate_' . $field['type'], $field, $errors );
					
					do_action( 'fep_form_field_validate', $field, $errors );
	
					break;
				
				}
			
	}
	
	
public function form_field_output( $where = 'new_message', $errors= '', $value = array() )
{
	//$fields = $this->form_fields( $where );
	
	if( ! is_wp_error($errors) )
		$errors = new WP_Error();
		
	ob_start();

		echo '<div class="front-end-pm-form">';
		echo '<form ';
		echo apply_filters( 'fep_form_attribute', 'action="'.esc_url( add_query_arg( false, false ) ).'" method="post" enctype="multipart/form-data"');
		echo '>';

		do_action( 'fep_before_form_fields', $where, $errors );

		foreach ( $this->form_fields( $where ) as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';
			$defaults = array(
								'key'		=> $key,
								'type'		=> $type,
								'name'		=> $key,
								'class'		=> 'input-'. $type,
								'id'		=> $key,
								'value'		=> ''
								);
			$field = wp_parse_args( $field, $defaults);
			if( array_key_exists( $field['name'], $value ) ) {
				$field['value'] = $value[$field['name']];
			}
			$field['posted-value'] = isset( $_REQUEST[$field['name']] ) ? $_REQUEST[$field['name']] : $field['value'];

			if ( has_action( 'fep_form_field_output_' . $field['type'] ) ) {
				do_action( 'fep_form_field_output_' . $field['type'], $field, $errors );
			} else {
				call_user_func( array( $this, 'field_output' ), $field, $errors );
			} 
		}

		do_action( 'fep_after_form_fields', $where, $errors );
		
		echo fep_error($errors);
		
		$button_val = ("settings" == $where ) ? __("Save Changes", "front-end-pm") : __("Send Message", "front-end-pm");
		echo apply_filters( 'fep_form_submit_button', '<button type="submit" name="fep_action" value="'. $where .'">'.$button_val.'</button>' );
		
        echo '</form>';
		echo '</div>';
		
		return apply_filters('fep_filter_form_output', ob_get_clean() );
	}

public function validate_form_field( $where = 'new_message' )
{
		$fields = $this->form_fields( $where );
	
		$errors = new WP_Error();

		foreach ( $fields as $key => $field ) {
			$defaults = array(
								'key' => $key,
								'type' => ! empty( $field['type'] ) ? $field['type'] : 'text',
								'name' => $key,
								'id' => $key,
								'value' => ''
								);
			$field = wp_parse_args( $field, $defaults);
			$field['posted-value'] = isset( $_POST[$field['name']] ) ? $_POST[$field['name']] : '';

			if ( has_action( 'fep_form_field_validate_' . $field['type'] ) ) {
				do_action( 'fep_form_field_validate_' . $field['type'], $field, $errors );
			} else {
				call_user_func( array( $this, 'field_validate' ), $field, $errors);
			}
			$fields[$key] = $field;
		}
		
		do_action('fep_action_validate_form', $where, $errors);
		
		if(count($errors->get_error_codes())==0){
			//No Errors
			do_action('fep_action_form_validated', $where, $fields);
		} else {
		}
		
		return $errors;
	}
		
	
  } //END CLASS
} //ENDIF

