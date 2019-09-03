<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FEP_WP_List_Table extends WP_List_Table {
	
	private $message_type;
	
	public function __construct( $type = 'message' ) {
		
		$this->message_type = $type;
		// Set parent defaults
		parent::__construct( array(
			'singular' => 'fep-message',
			'plural'   => 'fep-messages',
			'ajax'     => false,
		) );

	}

	protected function get_primary_column_name() {
		return 'mgs_title';
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case has_filter( "fep_admin_table_column_content_{$column_name}" ):
				$value = apply_filters( "fep_admin_table_column_content_{$column_name}", '', $item );
				break;
			case 'recipient_count':
				$participants = $item->get_participants();
				$value = count( $participants );
				break;
			case 'read_count':
				$value = 0;
				$participants = $item->get_participants();
				foreach( $participants as $participant ){
					if( $participant->mgs_read ){
						$value++;
					}
				}
				break;
			case 'deleted_count':
				$value = 0;
				$participants = $item->get_participants();
				foreach( $participants as $participant ){
					if( $participant->mgs_deleted ){
						$value++;
					}
				}
				break;
			case 'mgs_author':
				$value = fep_user_name( fep_get_message_field( $column_name ) );
				break;
			case 'mgs_title':
				$value = fep_get_the_title();
				break;
			case 'mgs_content':
				$value = fep_get_the_content();
				break;
			case 'mgs_last_reply_excerpt':
				$value = fep_get_the_excerpt();
				break;
			case 'mgs_created':
				$value = fep_get_the_date();
				break;
			case 'mgs_last_reply_time':
				$value = fep_get_the_date( 'updated' );
				break;
			case 'mgs_status':
				$value = fep_get_message_status();
				break;
			case 'mgs_parent':
				if ( fep_get_message_field( $column_name ) ) {
					$value = '<a class="thickbox" href="' . esc_url( add_query_arg( array( 'page' => $_REQUEST['page'], 'action' => 'view', 'fep_id' => fep_get_message_field( $column_name ) ), admin_url( 'admin.php' ) ) . '&TB_iframe=true&width=700&height=550' ) . '">' . fep_get_message_field( $column_name ) . '</a>';
				} else {
					$value = 0;
				}
				
				break;
	
			default:
				$value = fep_get_message_field( $column_name );
				break;
		}
		return $value;
	}
	
	protected function column_cb( $item ) { ?>
		<label class="screen-reader-text" for="cb-select-<?php echo fep_get_the_id(); ?>"><?php
			printf( __( 'Select %s' ), fep_get_the_id() );
		?></label>
		<input id="cb-select-<?php echo fep_get_the_id(); ?>" type="checkbox" name="fep_id[]" value="<?php echo fep_get_the_id(); ?>" />
		<?php 
	}
	
	protected function column_recipients( $item ) {
		fep_participants_view( $item->mgs_id );
	}

	public function get_columns() {
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'mgs_title'     => __( 'Subject', 'front-end-pm' ),
			'mgs_author' 	=> __( 'Sender', 'front-end-pm' ),
			'recipients' 	=> __( 'Recipients', 'front-end-pm' ),
			'mgs_created'  	=> __( 'Sent', 'front-end-pm' ),
			'mgs_status'  	=> __( 'Status', 'front-end-pm' ),
		);
		if( 'announcement' == $this->message_type ){
			$columns['recipient_count'] = __( 'Recipients Count', 'front-end-pm' );
			$columns['read_count'] = __( 'Read Count', 'front-end-pm' );
			$columns['deleted_count'] = __( 'Deleted Count', 'front-end-pm' );
		} else {
			$columns['mgs_parent'] = __( 'Parent', 'front-end-pm' );
		}

		return apply_filters( 'fep_admin_table_columns', $columns, $this->message_type );
	}
	
	protected function get_sortable_columns() {
		$columns = array(
			'mgs_title'     => [ 'mgs_title', false ],
			'mgs_author' 	=> [ 'mgs_author', false ],
			'mgs_created'  	=> [ 'mgs_created', true ],
			'mgs_parent' 	=> [ 'mgs_parent', false ],
		);

		return apply_filters( 'fep_admin_table_sortable_columns', $columns, $this->message_type );
	}

	public function get_bulk_actions( $which = '' ) {
		$actions = array(
			'bulk_delete' => __( 'Delete', 'front-end-pm' ),
		);
		foreach ( fep_get_statuses( $this->message_type ) as $status => $label ) {
			$actions[ "bulk_status-change-to-{$status}" ] = sprintf( __( 'Status change to %s', 'front-end-pm' ), $label );
		}
		return $actions;
	}

	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		//$this->get_column_info();
		$per_page = isset( $_REQUEST['per_page'] ) ? (int) $_REQUEST['per_page'] : 20;
		$status   = isset( $_REQUEST['mgs_status'] ) ? trim( $_REQUEST['mgs_status'] ) : 'any';
		
		$args = array(
			'mgs_type'     => $this->message_type,
			'paged'        => $this->get_pagenum(),
			'per_page'     => $per_page,
			'count_total'  => false,
			'mgs_status'   => $status,
			'orderby'      => isset( $_REQUEST['orderby'] ) ? trim( $_REQUEST['orderby'] ) : 'mgs_created',
			'order'        => isset( $_REQUEST['order'] ) ? trim( $_REQUEST['order'] ) : '',
			's'            => isset( $_REQUEST['s'] ) ? trim( $_REQUEST['s'] ) : '',
		);
		$args = apply_filters( 'fep_table_prepare_items_args', $args, $this->message_type );
		
		$this->items       = new FEP_Message_Query( $args );
		
		$this->set_pagination_args( array(
			'total_items' => $this->get_total_count( $this->message_type, $status ),
			'per_page' => $per_page,
		) );
	}
	
	protected function get_edit_link( $args, $label, $class = '' ) {
		$args['page'] = $_REQUEST['page'];
		$url          = add_query_arg( $args, 'admin.php' );

		$class_html = $aria_current = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}
	
	protected function get_views() {
		$status_links = [];
		$class        = '';
		if( empty( $_REQUEST['mgs_status'] ) ) {
			$class = 'current';
		}
		
		$status_links['all'] = $this->get_edit_link( [], sprintf( 'All <span class="count">(%s)</span>', number_format_i18n( $this->get_total_count( $this->message_type, 'any' ) ) ), $class );
		
		foreach ( fep_get_statuses( $this->message_type ) as $slug => $name ) {
			if ( ! $this->get_total_count( $this->message_type, $slug ) ) {
				continue;
			}
			if( isset( $_REQUEST['mgs_status'] ) && $slug === $_REQUEST['mgs_status'] ) {
				$class = 'current';
			} else {
				$class = '';
			}
			$status_links[ $slug ] = $this->get_edit_link( [ 'mgs_status' => $slug ], sprintf( '%s <span class="count">(%s)</span>', $name, number_format_i18n( $this->get_total_count( $this->message_type, $slug ) ) ), $class );
		}
		
		return $status_links;
	}
	
	private function get_total_count( $type, $status ) {
		global $wpdb;
		$counts = wp_cache_get( $type, 'fep_counts' );
		
		if ( ! is_array( $counts ) ) {
			$counts = [];
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT mgs_status, COUNT(*) AS num_mgs FROM {$wpdb->fep_messages} WHERE mgs_type = %s GROUP BY mgs_status", $type ) );
			foreach ( $results as $row ) {
				$counts[ $row->mgs_status ] = $row->num_mgs;
			}
			$counts['any'] = array_sum( $counts );
			
			wp_cache_set( $type, $counts, 'fep_counts' );
		}
		$return = 0;
		if( isset( $counts[ $status ] ) ) {
			$return = absint( $counts[ $status ] );
		}
		
		return apply_filters( 'fep_count_mgs_admin', $return, $type, $status );
	}
	
	public function has_items() {
		return $this->items->have_messages();
	}
	
	public function display_rows() {
		
		while ( $this->items->have_messages() ) {
			$this->items->the_message();
			
			$this->single_row( fep_get_current_message() );
		}
	}
	
	protected function handle_row_actions( $post, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}
		$actions = [];

		$actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'page' => $_REQUEST['page'], 'action' => 'delete', 'fep_id' => fep_get_the_id() ), admin_url( 'admin.php' ) ), 'delete-fep-message-' . fep_get_the_id() ) . '" class="fep_delete_a" >' . __( 'Delete', 'front-end-pm' ) . '</a>';
		
		$actions['edit'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'fep-edit', 'fep_id' => fep_get_the_id() ), admin_url( 'admin.php' ) ) ) . '" >' . __( 'Edit', 'front-end-pm' ) . '</a>';
		
		$actions['view'] = '<a class="thickbox" href="' . esc_url( add_query_arg( array( 'page' => $_REQUEST['page'], 'action' => 'view', 'fep_id' => fep_get_the_id() ), admin_url( 'admin.php' ) ) . '&TB_iframe=true&width=700&height=550' ) . '">' . __( 'View', 'front-end-pm' ) . '</a>';
		
		if( 'announcement' == $this->message_type ){
			$view = 'view_announcement';
		} else {
			$view = 'viewmessage';
		}
		$actions['view-frontend'] = '<a href="' . fep_query_url( $view, array( 'fep_id' => fep_get_the_id() ) ) . '">' . __( 'View in Front-end', 'front-end-pm' ) . '</a>';

		return $this->row_actions( $actions );
	}
}
