<?php

class FEP_menu_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'fep_menu_widget', // Base ID
			__( 'FEP Menu Widget', 'fep' ), // Name
			array( 'description' => __( 'Front End PM Menu Widget', 'fep' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		
		echo "<div id='fep-menu'>";
		do_action('fep_menu_button');
		echo "</div>";
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'FEP Menu Widget', 'fep' );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

}

// register FEP_menu_widget widget
function register_fep_menu_widget() {
if ( is_user_logged_in() )
    register_widget( 'FEP_menu_widget' );
}
add_action( 'widgets_init', 'register_fep_menu_widget' );

class FEP_text_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'fep_text_widget', // Base ID
			__( 'FEP Text Widget', 'fep' ), // Name
			array( 'description' => __( 'Front End PM Text Widget', 'fep' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $user_ID;
		
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		$show_messagebox = isset( $instance['show_messagebox'] ) ? $instance['show_messagebox'] : false;
		$show_announcement = isset( $instance['show_announcement'] ) ? $instance['show_announcement'] : false;
		
			echo __('Welcome', 'fep') . ' ' . fep_get_userdata( $user_ID, 'display_name', 'id' ). '<br />';
			
			echo __('You have', 'fep');
		
		if ( $show_messagebox )
			{
				$New_mgs = fep_get_new_message_number();
				$sm = ( $New_mgs > 1 ) ? 's': '';
				echo "<a href='".fep_action_url('messagebox')."'>".sprintf(__(" %d new message%s", 'fep'), $New_mgs, $sm ).'</a>';
				
			}
		if ( $show_messagebox && $show_announcement )
				echo __(' and', 'fep');
				
		if ( $show_announcement )
			{
				$New_ann = 0;
			if( class_exists('fep_announcement_class') )
				$New_ann = fep_announcement_class::init()->getAnnouncementsNum();
				$sa = ( $New_ann > 1 ) ? 's': '';
				
				echo "<a href='".fep_action_url('announcements')."'>".sprintf(__(" %d new announcement%s", 'fep'), $New_ann, $sa ).'</a>';
			}
	
			
		do_action('fep_text_widget');
		
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title =  isset( $instance['title'] ) ? $instance['title'] : __( 'FEP Text Widget', 'fep' );
		$show_messagebox =  isset( $instance['show_messagebox'] ) ? $instance['show_messagebox'] : false;
		$show_announcement =  isset( $instance['show_announcement'] ) ? $instance['show_announcement'] : false;
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
    	<input class="checkbox" type="checkbox" <?php checked( $show_messagebox, 1 ); ?> id="<?php echo $this->get_field_id( 'show_messagebox' ); ?>" name="<?php echo $this->get_field_name( 'show_messagebox' ); ?>" value="1"/>
    	<label for="<?php echo $this->get_field_id( 'show_messagebox' ); ?>"><?php _e('Show Messagebox?', 'fep'); ?></label>
		</p>
		<p>
    	<input class="checkbox" type="checkbox" <?php checked( $show_announcement, 1 ); ?> id="<?php echo $this->get_field_id( 'show_announcement' ); ?>" name="<?php echo $this->get_field_name( 'show_announcement' ); ?>" value="1"/>
    	<label for="<?php echo $this->get_field_id( 'show_announcement' ); ?>"><?php _e('Show Announcement?', 'fep'); ?></label>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['show_messagebox'] = ( ! empty( $new_instance['show_messagebox'] ) ) ? strip_tags( $new_instance['show_messagebox'] ) : '';
		$instance['show_announcement'] = ( ! empty( $new_instance['show_announcement'] ) ) ? strip_tags( $new_instance['show_announcement'] ) : '';

		return $instance;
	}

}

// register FEP_menu_widget widget
function register_fep_text_widget() {
if ( is_user_logged_in() )
    register_widget( 'FEP_text_widget' );
}
add_action( 'widgets_init', 'register_fep_text_widget' );


class FEP_empty_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'fep_empty_widget', // Base ID
			__( 'FEP Empty Widget', 'fep' ), // Name
			array( 'description' => __( 'Front End PM Empty Widget', 'fep' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		$show_help = isset( $instance['show_help'] ) ? $instance['show_help'] : false;
		
		if ( $show_help )
			{
				echo "Use <code>add_action('fep_empty_widget_{$this->number}', 'your_function' );</code> to hook to only this widget where 'your_function' is your defined function.";
				echo "<br />Use <code>add_action('fep_empty_widget', 'your_function' );</code> to hook to all FEP Empty widget where 'your_function' is your defined function";
			}
		
		do_action('fep_empty_widget_' . $this->number);
		do_action('fep_empty_widget');
		
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'FEP Empty Widget', 'fep' );
		$show_help = isset( $instance['show_help'] ) ? $instance['show_help'] : false;
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		
		<p>
    	<input class="checkbox" type="checkbox" <?php checked( $show_help, 1 ); ?> id="<?php echo $this->get_field_id( 'show_help' ); ?>" name="<?php echo $this->get_field_name( 'show_help' ); ?>" value="1"/>
    	<label for="<?php echo $this->get_field_id( 'show_help' ); ?>"><?php _e('Display help to configure this widget in front end?', 'fep'); ?></label>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['show_help'] = ( ! empty( $new_instance['show_help'] ) ) ? strip_tags( $new_instance['show_help'] ) : '';

		return $instance;
	}

}

// register FEP_menu_widget widget
function register_fep_empty_widget() {
if ( is_user_logged_in() )
    register_widget( 'FEP_empty_widget' );
}
add_action( 'widgets_init', 'register_fep_empty_widget' );

?>