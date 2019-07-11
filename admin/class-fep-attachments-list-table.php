<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FEP_Attachments_List_Table extends WP_List_Table {
	
	public function __construct() {
		// Set parent defaults
		parent::__construct( array(
			'singular' => 'fep-attachment',
			'plural'   => 'fep-attachments',
			'ajax'     => false,
		) );

	}

	protected function get_primary_column_name() {
		return 'att_name';
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case has_filter( "fep_admin_attachment_table_column_content_{$column_name}" ):
				$value = apply_filters( "fep_admin_attachment_table_column_content_{$column_name}", '', $item );
				break;
			case 'att_name':
				$value = esc_html( basename( $item->att_file ) );
				break;
			case 'att_thumbnail':
				if( 0 === stripos( $item->att_mime, 'image/') ){
					$src = fep_query_url( 'view-download', [ 'fep_id' => $item->att_id, 'fep_parent_id' => $item->mgs_id ] );
				} else {
					$src = wp_mime_type_icon( $item->att_mime );
				}
				$value = '<img src="' . $src . '" width="200px" height="150px" />';
				break;
			case 'mgs_id':
				$value = '<a class="thickbox" href="' . esc_url( admin_url( "admin.php?page=fep-all-messages&action=view&fep_id=$item->mgs_id&TB_iframe=true&width=700&height=550" ) ) . '">' . $item->mgs_id . '</a>';
				break;
	
			default:
				$value = esc_html( $item->$column_name );
				break;
		}
		return $value;
	}
	
	protected function column_cb( $item ) { ?>
		<label class="screen-reader-text" for="cb-select-<?php echo $item->att_id; ?>"><?php
			printf( __( 'Select %s' ), $item->att_id );
		?></label>
		<input id="cb-select-<?php echo $item->att_id; ?>" type="checkbox" name="fep_id[<?php echo (int) $item->mgs_id; ?>][]" value="<?php echo $item->att_id; ?>" />
		<?php 
	}

	public function get_columns() {
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'att_thumbnail' => __( 'Thubmnail', 'front-end-pm' ),
			'att_name'      => __( 'Name', 'front-end-pm' ),
			'att_mime'      => __( 'Mime', 'front-end-pm' ),
			'att_status' 	=> __( 'Status', 'front-end-pm' ),
			'mgs_id'        => __( 'Message id', 'front-end-pm' ),
		);

		return apply_filters( 'fep_admin_attachment_table_columns', $columns );
	}
	
	protected function get_sortable_columns() {
		$columns = array(
			'att_name'   => [ 'att_file', false ],
			'att_status' => [ 'att_status', false ],
			'mgs_id'  	 => [ 'mgs_id', false ],
		);

		return apply_filters( 'fep_admin_attachment_table_sortable_columns', $columns );
	}

	public function get_bulk_actions( $which = '' ) {
		$actions = array(
			'bulk_delete' => __( 'Delete', 'front-end-pm' ),
		);
		foreach ( fep_get_statuses( 'attachment' ) as $status => $label ) {
			$actions[ "bulk_status-change-to-{$status}" ] = sprintf( __( 'Status change to %s', 'front-end-pm' ), $label );
		}
		return $actions;
	}

	public function prepare_items() {
		global $wpdb;
		
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		//$this->get_column_info();
		$per_page = isset( $_REQUEST['per_page'] ) ? (int) $_REQUEST['per_page'] : 20;
		
		$value = [];
		$query = 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . FEP_ATTACHMENT_TABLE . ' WHERE 1=1';
		
		if( ! empty( $_REQUEST['att_status'] ) && 'any' != $_REQUEST['att_status'] ){
			$query .= ' AND att_status = %s';
			$value[] = $_REQUEST['att_status'];
		}
		if( ! empty( $_REQUEST['s'] ) ){
			if( is_numeric( $_REQUEST['s'] ) ){
				$query .= ' AND mgs_id = %d';
				$value[] = $_REQUEST['s'];
			}
		}
		$orderby  = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'att_id';
		$order    = ( isset( $_REQUEST['order'] ) && 'ASC' == strtoupper( $_REQUEST['order'] ) ) ? 'ASC' : 'DESC';
		
		$query .= " ORDER BY $orderby $order";
		
		$query .= ' LIMIT %d, %d';
		$value[] = ( $this->get_pagenum() - 1 ) * $per_page;
		$value[] = $per_page;
		
		$this->items   = $wpdb->get_results( $wpdb->prepare( $query, $value ) );
		
		$this->set_pagination_args( array(
			'total_items' => $wpdb->get_var( 'SELECT FOUND_ROWS()' ),
			'per_page' => $per_page,
		) );
	}
	
	public function has_items() {
		return count( $this->items );
	}
	
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}
		$actions = [];

		$actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'page' => $_REQUEST['page'], 'action' => 'delete', 'fep_id' => $item->att_id, 'fep_parent_id' => $item->mgs_id ), admin_url( 'admin.php' ) ), 'delete-fep-attachment-' . $item->att_id ) . '" class="fep_delete_a" >' . __( 'Delete', 'front-end-pm' ) . '</a>';
		
		$actions['view'] = '<a class="thickbox" title="' . __( 'View Attachment', 'front-end-pm' ) . '" href="' . fep_query_url( 'view-download', [ 'fep_id' => $item->att_id, 'fep_parent_id' => $item->mgs_id ] ) . '&TB_iframe=true&width=700&height=550' . '">' . __( 'View', 'front-end-pm' ) . '</a>';	
		$actions['download'] = '<a title="' . __( 'Download Attachment', 'front-end-pm' ) . '" href="' . fep_query_url( 'download', [ 'fep_id' => $item->att_id, 'fep_parent_id' => $item->mgs_id ] ) . '">' . __( 'Download', 'front-end-pm' ) . '</a>';	

		return $this->row_actions( $actions );
	}
	
	protected function extra_tablenav( $which ) { ?>
		<div class="alignleft actions"><?php
		if ( 'top' === $which ) { ?>
			<select name="att_status">
				<option value=""><?php _e( 'Show All', 'front-end-pm' ); ?></option>
				<?php foreach ( fep_get_statuses( 'attachment' ) as $key => $value ): ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, isset( $_REQUEST['att_status'] ) ? $_REQUEST['att_status'] : '' );?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
			submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'att-query-submit' ) );
		} ?>
		</div><?php
	}
}
