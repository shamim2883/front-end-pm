<?php

if (!class_exists('fep_admin_class'))
{
  class fep_admin_class
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
	add_action('admin_menu', array(&$this, 'addAdminPage'));
	add_filter('plugin_action_links', array(&$this, 'add_settings_link'), 10, 2 );
    }



/******************************************ADMIN SETTINGS PAGE BEGIN******************************************/

    function addAdminPage()
    {
	add_menu_page('Front End PM', 'Front End PM', 'manage_options', 'fep-admin-settings', array(&$this, 'admin_settings'),plugins_url( 'front-end-pm/images/msgBox.gif' ));
	add_submenu_page('fep-admin-settings', 'Front End PM - ' .__('Settings','fep'), __('Settings','fep'), 'manage_options', 'fep-admin-settings', array(&$this, 'admin_settings'));
	add_submenu_page('fep-admin-settings', 'Front End PM - ' .__('Instruction','fep'), __('Instruction','fep'), 'manage_options', 'fep-instruction', array(&$this, "dispInstructionPage"));
	
    }
	
    function admin_settings()
    {
	  $token = fep_create_nonce();
	  $url = 'https://shamimbiplob.wordpress.com/contact-us/';
	  $actionURL = admin_url( 'admin.php?page=fep-admin-settings' );
	  $ReviewURL = 'https://wordpress.org/support/view/plugin-reviews/front-end-pm';
	  $capUrl = 'http://codex.wordpress.org/Roles_and_Capabilities';
	  
	  if(isset($_POST['fep-admin-settings_submit'])){ 
		$errors = $this->admin_settings_action();
		if(count($errors->get_error_messages())>0){
			echo fep_error($errors);
		}
		else{
		echo'<div id="message" class="updated fade">' .__("Options successfully saved.", 'fep'). ' </div>';}}
		
      echo "<div id='poststuff'>

		<div id='post-body' class='metabox-holder columns-2'>

		<!-- main content -->
		<div id='post-body-content'>
		<div class='postbox'><div class='inside'>
	  	  <h2>".__("Front End PM Settings", 'fep')."</h2>
		  <h5>".sprintf(__("If you like this plugin please <a href='%s' target='_blank'>Review in Wordpress.org</a> and give 5 star", 'fep'),esc_url($ReviewURL))."</h5>
          <form method='post' action='$actionURL'>
          <table class='widefat'>
          <thead>
          <tr><th width='50%'>".__("Setting", 'fep')."</th><th width='50%'>".__("Value", 'fep')."</th></tr>
          </thead>
          <tr><td>".__("Max messages a user can keep in box? (0 = Unlimited)", 'fep')."<br /><small>".__("Admins always have Unlimited", 'fep')."</small></td><td><input type='text' name='num_messages' value='".fep_get_option('num_messages',50)."' /><br/> ".__("Default",'fep').": 50</td></tr>
          <tr><td>".__("Messages to show per page", 'fep')."<br/><small>".__("Do not set this to 0!", 'fep')."</small></td><td><input type='text' name='messages_page' value='".fep_get_option('messages_page',15)."' /><br/> ".__("Default",'fep').": 15</td></tr>
		  <tr><td>".__("Maximum user per page in Directory", 'fep')."<br/><small>".__("Do not set this to 0!", 'fep')."</small></td><td><input type='text' name='user_page' value='".fep_get_option('user_page',50)."' /><br/> ".__("Default",'fep').": 50</td></tr>
		  <tr><td>".__("Time delay between two messages send by a user in minutes (0 = No delay required)", 'fep')."<br/><small>".__("Admins have no restriction", 'fep')."</small></td><td><input type='text' name='time_delay' value='".fep_get_option('time_delay',5)."' /><br/> ".__("Default",'fep').": 5</td></tr>
		  <tr><td>".__("Block Username", 'fep')."<br /><small>".__("Separated by comma", 'fep')."</small></td><td><TEXTAREA name='have_permission'>".fep_get_option('have_permission')."</TEXTAREA></td></tr>
		  <tr><td>".__("Custom CSS", 'fep')."<br /><small>".__("add or override", 'fep')."</small></td><td><TEXTAREA name='custom_css'>".trim(fep_get_option('custom_css'))."</TEXTAREA></td></tr>
		  
		  <tr><td>".__("Editor Type", 'fep')."<br /><small>".__("Admin alwayes have Wp Editor", 'fep')."</small></td><td><select name='editor_type'>
		  <option value='wp_editor' ".selected(fep_get_option('editor_type','teeny'), 'wp_editor',false).">Wp Editor</option>
		  <option value='teeny' ".selected(fep_get_option('editor_type','teeny'), 'teeny',false).">Wp Editor (Teeny)</option>
		  <option value='textarea' ".selected(fep_get_option('editor_type','teeny'), 'textarea',false).">Textarea</option></select></td></tr>
		  
		  <tr><td>".__("Minimum Capability to use messaging", 'fep')."<br /><small>".sprintf(__("see <a href='%s' target='_blank'>WORDPRESS CAPABILITIES</a> to get capabilities (use only one capability)", 'fep'),esc_url($capUrl))."</small></td><td><input type='text' size='30' name='min_cap' value='".fep_get_option('min_cap','read')."' /><br /><small>".__("Keep blank if allowed for all users", 'fep')."</small></td></tr>";
		  
		  do_action('fep_admin_setting_form');
		  
		  echo "
		  <tr><td>".__("Valid email address for \"to\" field of announcement email", 'fep')."<br /><small>".__("All users email will be in \"Bcc\" field", 'fep')."</small></td><td><input type='text' size='30' name='ann_to' value='".fep_get_option('ann_to', get_bloginfo('admin_email'))."' /></td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='notify_ann' value='1' ".checked(fep_get_option('notify_ann',0), '1', false)." /> ".__("Send email to all users when a new announcement is published?", 'fep')."</td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='hide_directory' value='1' ".checked(fep_get_option('hide_directory',0), '1', false)." /> ".__("Hide Directory from front end?", 'fep')."<br /><small>".__("Always shown to Admins", 'fep')."</small></td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='hide_autosuggest' value='1' ".checked(fep_get_option('hide_autosuggest',0), '1', false)." /> ".__("Hide Autosuggestion when typing recipient name?", 'fep')."<br /><small>".__("Always shown to Admins", 'fep')."</small></td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='disable_new' value='1' ".checked(fep_get_option('disable_new',0), '1', false)." /> ".__("Disable \"send new message\" for all users except admins?", 'fep')."<br /><small>".__("Users can send reply", 'fep')."</small></td></tr>
		  <tr><td colspan='2'><input type='checkbox' name='hide_notification' value='1' ".checked(fep_get_option('hide_notification',0), '1', false)." /> ".__("Hide site wide notification in header?", 'fep')."</td></tr>
          <tr><td colspan='2'><input type='checkbox' name='hide_branding' value='1' ".checked(fep_get_option('hide_branding',0), '1', false)." /> ".__("Hide Branding Footer?", 'fep')."</td></tr>
          <tr><td colspan='2'><span><input class='button-primary' type='submit' name='fep-admin-settings_submit' value='".__("Save Options", 'fep')."' /></span></td><td><input type='hidden' name='token' value='$token' /></td></tr>
          </table>
		  </form>
		  <ul>".sprintf(__("For paid support pleasse visit <a href='%s' target='_blank'>Front End PM</a>", 'fep'),esc_url($url))."</ul>
          </div></div></div>
		  ". $this->fep_admin_sidebar(). "
		  </div></div>";
    }

function fep_admin_sidebar()
	{
		return '<div id="postbox-container-1" class="postbox-container">


				<div class="postbox">
					<h3 class="hndle" style="text-align: center;">
						<span>'. __( "Plugin Author", "fepcf" ). '</span>
					</h3>

					<div class="inside">
						<div style="text-align: center; margin: auto">
						<strong>Shamim Hasan</strong><br />
						Know php, MySql, css, javascript, html. Expert in WordPress. <br /><br />
								
						You can hire for plugin customization, build custom plugin or any kind of wordpress job via <br> <a
								href="https://shamimbiplob.wordpress.com/contact-us/"><strong>Contact Form</strong></a>
					</div>
				</div>
			</div>

				<div class="postbox">
					<h3 class="hndle" style="text-align: center;">
						<span>'. __( "Some Useful Links", "fepcf" ). '</span>
					</h3><div class="inside">
						<div style="text-align: center; margin: auto">
							<p>Some useful links are bellow to work with this plugin.</p>
						<ul>
							<li><a href="http://frontendpm.blogspot.com/2015/03/front-end-pm.html" target="_blank">Front End PM</a></li>
							<li><a href="http://frontendpm.blogspot.com/2015/03/front-end-pm-actions.html" target="_blank">Front End PM actions</a></li>
							<li><a href="http://frontendpm.blogspot.com/2015/03/front-end-pm-filters.html" target="_blank">Front End PM filters</a></li>
							<li><a href="http://frontendpm.blogspot.com/2015/03/changelog-of-front-end-pm.html" target="_blank">Changelog</a></li>
							<li><a href="http://frontendpm.blogspot.com/2015/03/frequently-asked-questions.html" target="_blank">FAQ</a></li>
						</ul></div>
					</div>
				</div>
				</div>';
	}


    function admin_settings_action()
    {
      if (isset($_POST['fep-admin-settings_submit']))
      {
	  $errors = new WP_Error();
	  $options = $_POST;
	  
	  if (!ctype_digit($options['num_messages']) || !ctype_digit($options['messages_page']) || !ctype_digit($options['user_page']) || !ctype_digit($options['time_delay']))
	  $errors->add('invalid_int', __('First four fields support only positive numbers!', 'fep'));
	  
	  if (!is_email($options['ann_to']))
	  $errors->add('invalid_ann_to', __('Please enter a valid email address for announcement to field!', 'fep'));
	   
	  if (!fep_verify_nonce($options['token']))
	  $errors->add('invalid_token', __('Invalid Token. Please try again!', 'fep'));
	  
	  do_action('fep_action_admin_setting_before_save', $errors);
	  
	  $options = apply_filters('fep_filter_admin_setting_before_save',$options);
	  //var_dump($options);
		
		if( current_user_can('manage_options') && (count($errors->get_error_codes())==0)){
        update_option('FEP_admin_options', $options);
        }
		return $errors;
      }
      return false;
    }
	
	function dispInstructionPage()
	{
	$url = 'https://shamimbiplob.wordpress.com/contact-us/';
	if (isset($_POST['fep-create-page']))
			{
			$this->fep_createPage_action();
			}
	echo "<div id='poststuff'>

		<div id='post-body' class='metabox-holder columns-2'>

		<!-- main content -->
		<div id='post-body-content'>
		<div class='postbox'><div class='inside'>
          <h2>".__("Front End PM Setup Instruction", 'fep')."</h2>
          <p><ul><li>".__("Create a new page.", 'fep')."</li>
          <li>".__("Paste following code under the HTML tab of the page editor", 'fep')."<code>[front-end-pm]</code></li>
          <li>".__("Publish the page.", 'fep')."</li>
		  <li>".__("Or you can create a page below.", 'fep')."</li>
		  <li>".sprintf(__("For paid support pleasse visit <a href='%s' target='_blank'>Front End PM</a>", 'fep'),esc_url($url))."</li>
          </ul></p>
		  <h2>".__("Create Page For \"Front End PM\"", 'fep')."</h2>
		  ".$this->fep_createPage()."</div></div></div>
		  ". $this->fep_admin_sidebar(). "
		  </div></div>";
		  }
	
	function fep_createPage(){
	$token = fep_create_nonce( 'fep-create-page' );
	$form = "<p>
      <form action='".admin_url( 'admin.php?page=fep-instruction' )."' method='post'>
      ".__("Title of \"Front End PM\" Page", 'fep').":<br/>
      <input type='text' name='fep-create-page-title' value='' /><br/>
	  <strong>".__("Slug", 'fep')."</strong>: <em>".__("If blank, slug will be automatically created based on Title", 'fep')."</em><br/>
      <input type='text' name='fep-create-page-slug' value='' /><br/>
	  <input type='hidden' name='token' value='$token' /><br/>
      <input class='button-primary' type='submit' name='fep-create-page' value='".__("Create Page", 'fep')."' />
      </form></p>";

      return $form;
    }

	function fep_createPage_action(){
	if (isset($_POST['fep-create-page'])){
      	$titlePre = wp_strip_all_tags($_POST['fep-create-page-title']);
		$title = utf8_encode($titlePre);
		$slugPre = wp_strip_all_tags($_POST['fep-create-page-slug']);
		$slug = utf8_encode($slugPre);
		
		delete_transient( 'fep_page_id' );
		
		if (fep_page_id() !=''){
		echo "<div id='message' class='error'><p>" .sprintf(__("Already created page <a href='%s'>%s </a> for \"Front End PM\". Please use that page instead!", 'fep'),get_permalink(fep_page_id()),get_the_title(fep_page_id()))."</p></div>";
        return;}
		if (!$title){
          echo "<div id='message' class='error'><p>" .__("You must enter a valid Title!", 'fep')."</p></div>";
        return;}
		// Check if a form has been sent
	  	if (!fep_verify_nonce($_POST['token'], 'fep-create-page'))
     	 {
	 	 echo "<div id='message' class='error'><p>" .__("Invalid Token. Please try again!", 'fep')."</p></div>";
        return;
      	}
		
		$fep_page = array(
  		'post_title'    => $title,
		'post_name'    => $slug,
  		'post_content'  => '[front-end-pm]',
  		'post_status'   => 'publish',
  		'post_type' => 'page'
		);
	$pageID = wp_insert_post( $fep_page );
	if($pageID == 0){
	echo "<div id='message' class='error'><p>" .__("Something wrong.Please try again to create page!", 'fep')."</p></div>";
        return;
		} else {
		echo "<div id='message' class='updated'><p>" .sprintf(__("Page <a href='%s'>%s </a> for \"Front End PM\" successfully created!", 'fep'),get_permalink($pageID),get_the_title($pageID))."</p></div>";
		
		set_transient('fep_page_id', $pageID, 60*60*24);
        return;}
		
		}
	}
	
function add_settings_link( $links, $file ) {
	//add settings link in plugins page
	$plugin_file = 'front-end-pm/front-end-pm.php';
	if ( $file == $plugin_file ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=fep-admin-settings' ) . '">' .__( 'Settings', 'fep' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
/******************************************ADMIN SETTINGS PAGE END******************************************/

  } //END CLASS
} //ENDIF

add_action('wp_loaded', array(fep_admin_class::init(), 'actions_filters'));
?>