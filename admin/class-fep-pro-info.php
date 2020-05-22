<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( fep_is_pro() ) {
	return;
}

class Fep_Pro_Info {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		add_filter( 'fep_admin_settings_tabs', array( $this, 'admin_settings_tabs' ) );
		add_filter( 'fep_settings_fields', array( $this, 'settings_fields' ) );
		add_action( 'fep_admin_settings_field_output_oa_admins', array( $this, 'field_output_oa_admins' ) );
		add_action( 'fep_admin_settings_field_output_gm_groups', array( $this, 'field_output_gm_groups' ) );
		add_action( 'fep_admin_settings_field_output_rtr_block', array( $this, 'field_output_rtr_block' ) );
	}

	function email_legends( $where = 'newmessage', $post = '', $value = 'description', $user_email = '' ) {
		$legends = array(
			'subject' => array(
				'description' => __( 'Subject', 'front-end-pm' ),
			),
			'message' => array(
				'description' => __( 'Full Message', 'front-end-pm' ),
			),
			'message_url' => array(
				'description' => __( 'URL of message', 'front-end-pm' ),
				'where'	=> array( 'newmessage', 'reply' ),
			),
			'announcement_url' => array(
				'description' => __( 'URL of announcement', 'front-end-pm' ),
				'where' => 'announcement',
			),
			'sender' => array(
				'description' => __( 'Sender', 'front-end-pm' ),
			),
			'receiver' => array(
				'description' => __( 'Receiver', 'front-end-pm' ),
			),
			'site_title' => array(
			'description' => __( 'Website title', 'front-end-pm' ),
			),
			'site_url' => array(
			'description' => __( 'Website URL', 'front-end-pm' ),
			),
		);
		$ret = array();
		foreach( $legends as $k => $legend ) {
			if ( empty( $legend['where'] ) ) {
				$legend['where'] = array( 'newmessage', 'reply', 'announcement' );
			}
			if ( is_array( $legend['where'] ) ) {
				if ( ! in_array( $where, $legend['where'] ) ) {
					continue;
				}
			} else {
				if ( $where != $legend['where'] ) {
					continue;
				}
			}
			if ( 'description' == $value ) {
				$ret[ $k ] = '<code>{{' . $k . '}}</code> = ' . $legend['description'];
			}
		}
		return $ret;
	}

	function admin_settings_tabs( $tabs ) {
		$tabs['email_piping'] = array(
			'section_title'		=> __( 'Email Piping/POP3', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_emails',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 53,
			'tab_output'		=> false,
		);
		$tabs['eb_newmessage'] = array(
			'section_title'		=> __( 'New Message email', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_emails',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 55,
			'tab_output'		=> false,
		);
		$tabs['eb_reply'] = array(
			'section_title'		=> __( 'Reply Message email', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_emails',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 65,
			'tab_output'		=> false,
		);
		$tabs['eb_announcement'] = array(
			'section_title'		=> __( 'Announcement email', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_emails',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 75,
			'tab_output'		=> false,
		);
		$tabs['mr_multiple_recipients'] = array(
			'section_title'		=> __( 'Multiple Recipients', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_recipient',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 10,
			'tab_output'		=> false,
		);
		$tabs['oa_admins'] = array(
			'section_title'		=> __( 'Only Admins', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_recipient',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 15,
			'tab_output'		=> false,
		);
		$tabs['gm_groups'] = array(
			'section_title'		=> __( 'Groups', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_recipient',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 20,
			'tab_output'		=> false,
		);
		$tabs['rtr_block'] = array(
			'section_title'		=> __( 'Role to Role Block', 'front-end-pm' ),
			'section_page'		=> 'fep_settings_security',
			'section_callback'	=> array( $this, 'section_callback' ),
			'priority'			=> 35,
			'tab_output'		=> false,
		);
		return $tabs;
	}

	function section_callback( $section ) {
		static $added = false;
		if ( ! $added ) : ?>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					jQuery( '.fep_admin_div_need_pro' ).each( function() {
						jQuery( this ).css({
							height: jQuery( this ).next( 'table' ).height(), 
							width: jQuery( this ).next( 'table' ).width()
						});
						jQuery( this ).show();
					});
				});
			</script>
			<style type="text/css">
				.fep_admin_div_need_pro {
					-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
					background: #ffffff url('<?php echo FEP_PLUGIN_URL . 'assets/images/pro_only.png'; ?>') no-repeat center center;
					cursor: pointer;
					display: none;
					filter: alpha(opacity=50);
					opacity: 0.5;
					position: absolute;
					z-index: 99;
				}
			</style>
			<?php
			$added = true;
		endif;
		echo '<div class="notice notice-warning inline"><p>' . sprintf( __( 'Following features only available in PRO version. <a href="%s">Upgrade to PRO</a>' ),  function_exists( 'fep_fs' ) ? fep_fs()->get_upgrade_url() : esc_url( 'https://www.shamimsplugins.com/products/front-end-pm-pro/?utm_campaign=admin&utm_source=pro_features&utm_medium=links' ) ) . '</p></div>';
		?><div class="fep_admin_div_need_pro" onclick="window.location.href = '<?php echo function_exists( 'fep_fs' ) ? fep_fs()->get_upgrade_url() : 'https://www.shamimsplugins.com/products/front-end-pm-pro/?utm_campaign=admin&utm_source=pro_features&utm_medium=image' ?>'"></div>
		<?php
	}

	function settings_fields( $fields ) {
		$fields['ep_enable'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'email_piping',
			'value'		=> fep_get_option( 'ep_enable', 0 ),
			//'description' => __( 'Can users send message to other users.', 'front-end-pm' ),
			'label'		=> __( 'Enable', 'front-end-pm' ),
			'cb_label'	=> __( 'Enable email piping?', 'front-end-pm' ),
		);
		$fields['ep_email'] = array(
			'type'		 => 'email',
			'section'	 => 'email_piping',
			'value'		 => fep_get_option( 'ep_email', get_bloginfo( 'admin_email' ) ),
			'description'=> __( 'Use this email as email piping.', 'front-end-pm' ),
			'label'		 => __( 'Piping Email', 'front-end-pm' ),
		);
		$fields['ep_clean_reply'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'email_piping',
			'value'		=> fep_get_option( 'ep_clean_reply', 1 ),
			'label'		=> __( 'Clean reply quote', 'front-end-pm' ),
			'cb_label'	=> __( 'Clean reply quote from email?', 'front-end-pm' ),
		);
		$templates = array(
			'default' => __( 'Default', 'front-end-pm' ),
		);
		$fields['eb_newmessage_template'] = array(
			'section'	 => 'eb_newmessage',
			'value'		 => fep_get_option( 'eb_newmessage_template', 'default' ),
			'label'		 => __( 'New message email template', 'front-end-pm' ),
			'type'		 => 'select',
			//'description'=> __( 'Admin alwayes have WP Editor.', 'front-end-pm' ),
			'options' 	 => apply_filters( 'fep_eb_templates', $templates, 'newmessage' ),
		);
		$fields['eb_newmessage_subject'] = array(
			'section'	=> 'eb_newmessage',
			'value'		=> fep_get_option( 'eb_newmessage_subject', '' ),
			'label'		=> __( 'New message subject.', 'front-end-pm' ),
		);
		$fields['eb_newmessage_content'] = array(
			'type'		 => 'teeny',
			'section'	 => 'eb_newmessage',
			'value'		 => fep_get_option( 'eb_newmessage_content', '' ),
			'description'=> implode( '<br />', $this->email_legends() ),
			'label'		 => __( 'New message content.', 'front-end-pm' ),
		);
		$fields['eb_newmessage_attachment'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'eb_newmessage',
			'value'		=> fep_get_option( 'eb_newmessage_attachment', 0 ),
			'label'		=> __( 'Send Attachments', 'front-end-pm' ),
			'cb_label' 	=> __( 'Send attachments with new message email?', 'front-end-pm' ),
		);
		$fields['eb_reply_template'] = array(
			'section'	 => 'eb_reply',
			'value'		 => fep_get_option( 'eb_reply_template', 'default' ),
			'label'		 => __( 'Reply message email template', 'front-end-pm' ),
			'type'		 =>	'select',
			//'description' => __( 'Admin alwayes have Wp Editor.', 'front-end-pm' ),
			'options'	 => apply_filters( 'fep_eb_templates', $templates, 'reply' ),
		);
		$fields['eb_reply_subject'] = array(
			'section'	=> 'eb_reply',
			'value'		=> fep_get_option( 'eb_reply_subject', '' ),
			'label'		=> __( 'Reply subject.', 'front-end-pm' ),
		);
		$fields['eb_reply_content'] = array(
			'type'		 => 'teeny',
			'section'	 => 'eb_reply',
			'value'		 => fep_get_option( 'eb_reply_content', '' ),
			'description'=> implode( '<br />', $this->email_legends( 'reply' ) ),
			'label'		 => __( 'Reply content.', 'front-end-pm' ),
		);
		$fields['eb_reply_attachment'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'eb_reply',
			'value'		=> fep_get_option( 'eb_reply_attachment', 0 ),
			'label'		=> __( 'Send Attachments', 'front-end-pm' ),
			'cb_label'	=> __( 'Send attachments with reply message email?', 'front-end-pm' ),
		);
		$fields['eb_announcement_interval'] = array(
			'type'		 => 'number',
			'section'	 => 'eb_announcement',
			'value'		 => fep_get_option( 'eb_announcement_interval', 60 ),
			'label'		 => __( 'Sending Interval.', 'front-end-pm' ),
			'description'=> __( 'Announcement sending Interval in minutes.', 'front-end-pm' ),
		);
		$fields['eb_announcement_email_per_interval'] = array(
			'type'		 => 'number',
			'section'	 => 'eb_announcement',
			'value'		 => fep_get_option( 'eb_announcement_email_per_interval', 50 ),
			'label'		 => __( 'Emails send per interval.', 'front-end-pm' ),
			'description'=> __( 'Announcement emails send per interval.', 'front-end-pm' ),
		);
		$fields['eb_announcement_template'] = array(
			'section'	 => 'eb_announcement',
			'value'		 => fep_get_option( 'eb_announcement_template', 'default' ),
			'label'		 => __( 'Announcement email template', 'front-end-pm' ),
			'type'		 =>	'select',
			//'description'=> __( 'Admin alwayes have WP Editor.', 'front-end-pm' ),
			'options'	 => apply_filters( 'fep_eb_templates', $templates, 'announcement' ),
		);
		$fields['eb_announcement_subject'] = array(
			'section'	=> 'eb_announcement',
			'value'		=> fep_get_option( 'eb_announcement_subject', '' ),
			'label'		=> __( 'Announcement subject.', 'front-end-pm' ),
		);
		$fields['eb_announcement_content'] = array(
			'type'		 => 'teeny',
			'section'	 => 'eb_announcement',
			'value'		 => fep_get_option( 'eb_announcement_content', '' ),
			'description'=> implode( '<br />', $this->email_legends( 'announcement' ) ),
			'label'		 => __( 'Announcement content.', 'front-end-pm' ),
		);
		$fields['eb_announcement_attachment'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'eb_announcement',
			'value'		=> fep_get_option( 'eb_announcement_attachment', 0 ),
			'label'		=> __( 'Send Attachments', 'front-end-pm' ),
			'cb_label'	=> __( 'Send attachments with announcement email?', 'front-end-pm' ),
		);
		$fields['mr-max-recipients'] = array(
			'type'		 =>	'number',
			'section'	 => 'mr_multiple_recipients',
			'value'		 => fep_get_option( 'mr-max-recipients', 5 ),
			'description'=> __( 'Maximum recipients per message.', 'front-end-pm' ),
			'label'		 => __( 'Max recipients', 'front-end-pm' ),
		);
		$fields['mr-message'] = array(
			'type'		 => 'select',
			'section'	 => 'mr_multiple_recipients',
			'value'		 => fep_get_option( 'mr-message', 'same-message' ),
			'description'=> __( 'How messages will be sent to recipients?', 'front-end-pm' ),
			'label'		 => __( 'Message type', 'front-end-pm' ),
			'options' => array(
				'same-message'		=> __( 'Same Message', 'front-end-pm' ),
				'separate-message'	=> __( 'Separate Message', 'front-end-pm' ),
			),
		);
		$fields['read_receipt'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'mr_multiple_recipients',
			'value'		=> fep_get_option( 'read_receipt', 1 ),
			'label'		=> __( 'Read Receipt', 'front-end-pm' ),
			'cb_label'	=> __( 'Show read receipt bottom of every message?', 'front-end-pm' ),
		);
		$fields['oa-can-send-to-admin'] = array(
			'type'		 => 'checkbox',
			'class'		 => '',
			'section'	 => 'oa_admins',
			'value'		 => fep_get_option( 'oa-can-send-to-admin', 0 ),
			'description'=> __( 'Can users send message to admin?', 'front-end-pm' ),
			'label'		 => __( 'Can send to admin?', 'front-end-pm' ),
		);
		$fields['oa_admins'] = array(
			'type'		 => 'oa_admins',
			'section'	 => 'oa_admins',
			'value'		 => fep_get_option( 'oa_admins', array() ),
			'description'=> __( 'Do not forget to save.', 'front-end-pm' ),
			'label'		 => __( 'Admins', 'front-end-pm' ),
		);
		$fields['oa_admins_frontend'] = array(
			'type'		 => 'select',
			'section'	 => 'oa_admins',
			'value'		 => fep_get_option( 'oa_admins_frontend', 'dropdown' ),
			'description'=> __( 'Select how you want to see in frontend.', 'front-end-pm' ),
			'label'		 => __( 'Show in front end as', 'front-end-pm' ),
			'options'	 => array(
				'dropdown'	=> __( 'Dropdown', 'front-end-pm' ),
				'radio'		=> __( 'Radio Button', 'front-end-pm' ),
			),
		);
		$fields['can-send-to-group'] = array(
			'type'		=> 'checkbox',
			'class'		=> '',
			'section'	=> 'gm_groups',
			'value'		=> fep_get_option( 'can-send-to-group', 0 ),
			'cb_label'	=> __( 'Can users send message to group?', 'front-end-pm' ),
			'label'		=> __( 'Can send to group?', 'front-end-pm' ),
		);
		$fields['can-add-to-group'] = array(
			'type'		=>	'checkbox',
			'class'		=> '',
			'section'	=> 'gm_groups',
			'value'		=> fep_get_option( 'can-add-to-group', 0 ),
			'cb_label'	=> __( 'Can users add themself to group.', 'front-end-pm' ),
			'label'		=> __( 'Can add to group', 'front-end-pm' ),
		);
		$fields['gm_groups'] = array(
			'type'		 => 'gm_groups',
			'section'	 => 'gm_groups',
			'value'		 => fep_get_option( 'gm_groups', array() ),
			'description'=> __( 'Do not forget to save.', 'front-end-pm' ),
			'label'		 => __( 'Groups', 'front-end-pm' ),
		);
		$fields['gm_frontend'] = array(
			'type'		 => 'select',
			'section'	 => 'gm_groups',
			'value'		 => fep_get_option( 'gm_frontend', 'dropdown' ),
			'description'=> __( 'Select how you want to see in frontend.', 'front-end-pm' ),
			'label'		 => __( 'Show in front end as', 'front-end-pm' ),
			'options'	 => array(
				'dropdown'	=> __( 'Dropdown', 'front-end-pm' ),
				'radio'		=> __( 'Radio Button', 'front-end-pm' ),
			),
		);
		$fields['rtr_block'] = array(
			'type' 		 => 'rtr_block',
			'section'	 => 'rtr_block',
			'value'		 => fep_get_option( 'rtr_block', array() ),
			'description'=> __( 'Do not forget to save.', 'front-end-pm' ),
		);
		return $fields;
	}

	function field_output_oa_admins( $field ) {
		?>
		<div>
			<span><input type="text" placeholder="<?php esc_attr_e( 'Display as', 'front-end-pm' ); ?>" value="" /></span>
			<span><input type="text" placeholder="<?php esc_attr_e( 'Username', 'front-end-pm' ); ?>" value="" /></span>
			<span><input type="button" class="button button-small" value="<?php esc_attr_e( 'Remove', 'front-end-pm' ); ?>" /></span>
		</div>
		<div><input type="button" class="button" value="<?php esc_attr_e( 'Add More', 'front-end-pm' ); ?>" /></div>
		<?php
	}

	function field_output_gm_groups() {
		?>
		<div><input type="button" class="button" value="<?php esc_attr_e( 'Add More', 'front-end-pm' ); ?>" /></div>
		<?php
	}

	function field_output_rtr_block( $field ) {
		?>
		<table>
			<th><?php _e( 'From Role', 'front-end-pm' ); ?></th>
			<th><?php _e( 'To Role', 'front-end-pm' ); ?></th>
			<th><?php _e( 'Block For', 'front-end-pm' ); ?></th>
			<th><?php _e( 'Remove', 'front-end-pm' ); ?></th>
		</table>
		<div>
			<span><select><option value=""><?php _e( 'Select Role', 'front-end-pm' ); ?></option></select></span>
			<span><select><option value=""><?php _e( 'Select Role', 'front-end-pm' ); ?></option></select></span>
			<span><select><option value=""><?php _e( 'Select For', 'front-end-pm' ); ?></option></select></span>
			<span><input type="button" class="button button-small" value="<?php esc_attr_e( 'Remove', 'front-end-pm' ); ?>" /></span>
		</div>
		<div><input type="button" class="button" value="<?php esc_attr_e( 'Add More', 'front-end-pm' ); ?>" /></div>
		<?php
	}

	function to_use_wp_online_translation() {
		__( 'Send Message to admin', 'front-end-pm' );
		__( 'Send Message to group', 'front-end-pm' );
	}
} //END CLASS
add_action( 'admin_init', array( Fep_Pro_Info::init(), 'actions_filters' ) );
