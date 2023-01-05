<?php
namespace WeDevs\ERP\Accounting;

if ( ! class_exists ( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class
 */
class QUOTE_Transaction_List_Table extends Sales_Transaction_List_Table {
 
   private $page_status = '';

    function __construct() {
		 
        global $page_status;
        \WP_List_Table::__construct([
            'singular' => 'quote',
            'plural'   => 'quote',
            'ajax'     => false
        ]);

        $this->type = 'quote';
        $this->slug = 'erp-quote';
    }

    /**
     * Get the column names
     *
     * @return array
     */
    function get_columns() {
        $section = isset( $_GET['section'] ) ? $_GET['section'] : false;

        $columns = array(
           // 'cb'         => '<input type="checkbox" />',
            'issue_date' => __( 'Date', 'erp' ),
            'form_type'  => __( 'Type', 'erp' ),
            'ref'  => __( 'Ref', 'erp' ),
            'user_id'    => __( 'User', 'erp' ),
            'due_date'   => __( 'Due Date', 'erp' ),
            'total'      => __( 'Total', 'erp' ),
            'status'     => __( 'Status', 'erp' ),
        );

        if ( $section == 'awaiting_approval' || 'awaiting-approval' || $section == 'draft' || $section == 'won' || $section == 'lost' || $section == 'void' ) {
            $action = [ 'cb' => '<input type="checkbox" />'];
            $columns = array_merge( $action, $columns );
        }

        return $columns;
    }



    /**
     * Count sales status
     *
     * @since  1.1.6
     *
     * @return  array
     */
    function get_counts() {
        global $wpdb;
        $cache_key = 'erp-ac-sales-trnasction-counts-' . get_current_user_id();
        $results = wp_cache_get( $cache_key, 'erp' );
        $type = isset( $_REQUEST['form_type'] ) ? $_REQUEST['form_type'] : false;
        $start = isset( $_GET['start_date'] ) ? $_GET['start_date'] :  date( 'Y-m-d', strtotime( erp_financial_start_date() ) );
        $end = isset( $_GET['end_date'] ) ? $_GET['end_date'] : date( 'Y-m-d', strtotime( erp_financial_end_date() ) );

        if ( false === $results ) {
            $trans = new \WeDevs\ERP\Accounting\Model\Transaction_QUOTE();
            $db = new \WeDevs\ORM\Eloquent\Database();

            if ( $type ) {
                $results = $trans->select( array( 'status', $db->raw('COUNT(id) as num') ) )
                            ->where( 'type', '=', $this->type )
                            ->where( 'form_type', '=', $type )
                            ->where( 'issue_date', '>=', $start )
                            ->where( 'issue_date', '<=', $end )
                            ->groupBy('status')
                            ->get()->toArray();
            } else {
                $results = $trans->select( array( 'status', $db->raw('COUNT(id) as num') ) )
                            ->where( 'type', '=', $this->type )
                            ->where( 'issue_date', '>=', $start )
                            ->where( 'issue_date', '<=', $end )
                            ->groupBy('status')
                            ->get()->toArray();
            }

            wp_cache_set( $cache_key, $results, 'erp' );
        }

        $count = [];

        foreach ( $results as $key => $value ) {
            $count[$value['status']] = $value['num'];
        }

        return $count;
    }

    /**
     * Field for bulk action
     *
     * @since  1.1.6
     *
     * @return void
     */
    public function bulk_actions( $which = '' ) {
        $section = isset( $_GET['section'] ) ? $_GET['section'] : false;
        $type    = [];

        if ( 'top' == $which && $this->items ) {
            if ( $section == 'draft' ) {
                $type = [
                    'awaiting_approval'  => __( 'Approve', 'erp' ),
                    'delete' => __( 'Delete', 'erp' )
                ];
            } else if ( $section == 'won' ) {
                $type = [
                    'void'  => __( 'Void', 'erp' ),
                ];
            } else if ( $section == 'lost' ) {
                $type = [
                    'void'  => __( 'Void', 'erp' ),
                ];
            } else if ( $section == 'void' ) {
                $type = [
                    'delete'  => __( 'Delete', 'erp' ),
                ];
            } else if ( $section == 'awaiting-approval' || $section == 'awaiting_approval') {
                $type = [
                    'won'  => __( 'won', 'erp' ),
                    'lost'  => __( 'lost', 'erp' ),
                    'void'  => __( 'Void', 'erp' ),
                ];
            }

            if ( $section ) {
                erp_html_form_input([
                    'name'    => 'action',
                    'type'    => 'select',
                    'options' => [ '-1' => __( 'Bulk Actions', 'erp' ) ] + $type
                ]);

                submit_button( __( 'Action', 'erp' ), 'button', 'submit_sales_bulk_action', false );
            }

        }
    }

    /**
     * Filters
     *
     * @param  string  $which
     *
     * @return void
     */
    public function extra_tablenav( $which ) {

	if ( 'top' == $which ) {
            echo '<div class="alignleft mishu actions">';

            $type = [];

            $all_types = $this->get_form_types();
            $types = [];

            foreach ($all_types as $key => $type) {
                $types[ $key ] = $type['label'];
            }

            erp_html_form_input([
                'name'    => 'form_type',
                'type'    => 'select',
                'value'   => isset( $_REQUEST['form_type'] ) && ! empty( $_REQUEST['form_type'] ) ? strtolower( $_REQUEST['form_type'] ) : '',
                'options' => [ '' => __( 'All Types', 'erp' ) ] + $types
            ]);

            erp_html_form_input([
                'name'        => 'user_id',
                'type'        => 'hidden',
                'class'       => 'erp-ac-customer-search',
                'placeholder' => __( 'Search for Customer', 'erp' ),
            ]);

            erp_html_form_input([
                'name'        => 'start_date',
                'class'       => 'erp-date-field',
                'value'       => isset( $_REQUEST['start_date'] ) && !empty( $_REQUEST['start_date'] ) ? $_REQUEST['start_date'] : '',
                'placeholder' => __( 'Start Date', 'erp' )
            ]);

            erp_html_form_input([
                'name'        => 'end_date',
                'class'       => 'erp-date-field',
                'value'       => isset( $_REQUEST['end_date'] ) && !empty( $_REQUEST['end_date'] ) ? $_REQUEST['end_date'] : '',
                'placeholder' => __( 'End Date', 'erp' )
            ]);

            erp_html_form_input([
                'name'        => 'ref',
                'value'       => isset( $_REQUEST['ref'] ) && ! empty( $_REQUEST['ref'] ) ? $_REQUEST['ref'] : '',
                'placeholder' => __( 'Ref No.', 'erp' )
            ]);

            submit_button( __( 'Filter', 'erp' ), 'button', 'submit_filter_sales', false );

            echo '</div>';
        }
    }

    /**
     * Get section for sales table list
     *
     * @since  1.1.6
     *
     * @return array
     */
    public function get_section() {
        $counts = $this->get_counts();

        $section = [
            'all'   => [
                'label' => __( 'All', 'erp' ),
                'count' => array_sum( $counts),
                'url'   => erp_ac_get_section_sales_url()
            ],

            'draft' => [
                'label' => __( 'Draft', 'erp' ),
                'count' => isset( $counts['draft'] ) ? intval( $counts['draft'] ) : 0,
                'url'   => erp_ac_get_section_sales_url( 'draft' )
            ],

            'awaiting_approval' => [
                'label' => __( 'Awaiting Approval', 'erp' ),
                'count' => isset( $counts['awaiting_approval'] ) ? intval( $counts['awaiting_approval'] ) : 0,
                'url'   => erp_ac_get_section_sales_url( 'awaiting_approval' )
            ],

            'won' => [
                'label' => __( 'Won', 'erp' ),
                'count' => isset( $counts['won'] ) ? intval( $counts['won'] ) : 0,
                'url'   => erp_ac_get_section_sales_url( 'won' )
            ],
           
            'lost' => [
                'label' => __( 'Lost', 'erp' ),
                'count' => isset( $counts['lost'] ) ? intval( $counts['lost'] ) : 0,
                'url'   => erp_ac_get_section_sales_url( 'closed' )
            ],

            'void' => [
                'label' => __( 'Void', 'erp' ),
                'count' => isset( $counts['void'] ) ? intval( $counts['void'] ) : 0,
                'url'   => erp_ac_get_section_sales_url( 'void' )
            ]
        ];

        return $section;
    }

    /**
     * Set the views
     *
     * @return array
     */
    public function get_views() {
        $counts       = $this->get_section();
        $status_links = array();
        $section      = isset( $_REQUEST['section'] ) ? $_REQUEST['section'] : 'all';

        foreach ( $counts as $key => $value ) {
            $key   = str_replace( '_', '-', $key );
            $class = ( $key == $section ) ? 'current' : 'status-' . $key;
            $status_links[ $key ] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>', $value['url'], $class, $value['label'], $value['count'] );
        }

        return $status_links;
    }

	
	    /**
     * Get all transactions
     *
     * @param  array  $args
     *
     * @return array
     */
    protected function get_quote_transactions( $args ) {
        return erp_ac_get_all_transaction_quote( $args );
    }
	
	
    /**
     * Get transaction count
     *
     * @param  array  $args
     *
     * @return int
     */
    protected function get_quote_transaction_count( $args ) {
        return erp_ac_quote_get_transaction_count( $args );
    }
	
	 /**
     * Prepare the class items
     *
     * @return void
     */
    function prepare_items() {

        $columns               = $this->get_columns();
        $hidden                = array( );
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page              = 25;
        $current_page          = $this->get_pagenum();
        $offset                = ( $current_page -1 ) * $per_page;
        $this->page_status     = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '2';

        // only ncessary because we have sample data
        $args = array(
            'type'   => $this->type,
            'offset' => $offset,
            'number' => $per_page,
        );

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = $_REQUEST['orderby'];
            $args['order']   = $_REQUEST['order'] ;
        }

        // search params
        if ( isset( $_REQUEST['start_date'] ) && !empty( $_REQUEST['start_date'] ) ) {
           $args['start_date'] = $_REQUEST['start_date'];
        }

        if ( isset( $_REQUEST['end_date'] ) && !empty( $_REQUEST['end_date'] ) ) {
           $args['end_date'] = $_REQUEST['end_date'];
        }

        if ( isset( $_REQUEST['form_type'] ) && ! empty( $_REQUEST['form_type'] ) ) {
            if ( $_REQUEST['form_type'] == 'deleted' ) {
                $args['status'] = $_REQUEST['form_type'];
            } else {
                $args['form_type'] = $_REQUEST['form_type'];
            }
        }

        if ( isset( $_REQUEST['ref'] ) && ! empty( $_REQUEST['ref'] ) ) {
            $args['ref'] = $_REQUEST['ref'];
        }

        if ( 'quote' == $args['type'] && ! erp_ac_view_other_sales() ) {
            $args['created_by'] = get_current_user_id();
        }

        $this->items = $this->get_quote_transactions( $args );
		
        $this->set_pagination_args( array(
            'total_items' => $this->get_quote_transaction_count( $args ),
            'per_page'    => $per_page
        ) );
    }
	
	
    /**
     * Get form types
     *
     * @return array
     */
    public function get_form_types() {
        return erp_ac_get_quote_form_types();
    }

	
	 /**
     * Render the user_id column
     *
     * @since  1.1.6
     *
     * @param  object  $item
     *
     * @return string
     */
    public function column_user_id( $item ) {
        $url               = admin_url( 'admin.php?page=' . $this->slug . '&action=view&id=' . $item->id );
        $user_display_name = '';
        $actions           = array();
        if ( ! $item->user_id ) {
            $user_display_name = __( '(no customer)', 'erp' );
        } else {
            $transaction = \WeDevs\ERP\Accounting\Model\Transaction_QUOTE::find( $item->id );
            $user_display_name = ( NULL !== $transaction->user ) ? $transaction->user->first_name . ' ' . $transaction->user->last_name : '--';
        }

        return sprintf( '<a href="%1$s">%2$s</a> %3$s', $url, $user_display_name, $this->row_actions( $actions ) );
    }
	
	
	 /**
     * Render the issue date column
     *
     * @since  1.1.6
     *
     * @param  object  $item
     *
     * @return string
     */
    function column_issue_date( $item ) {
        if ( $item->status == 'draft' ) {
            $actions['approval'] = sprintf( '<a class="erp-accountin-trns-quote-row-bulk-action" data-status="%1s" data-id="%2d" href="#">%4s</a>', 'awaiting_approval', $item->id, __( 'Submit for approval', 'erp' ) );
        }

        if ( $item->status == 'awaiting_approval' ) {
            $actions['won'] = sprintf( '<a class="erp-accountin-trns-quote-row-bulk-action" data-id="%1$s" data-status="%2$s" href="#">%3$s</a>', $item->id, 'won', __( 'Won', 'erp' ) );
            $actions['lost'] = sprintf( '<a class="erp-accountin-trns-quote-row-bulk-action" data-id="%1$s" data-status="%2$s" href="#">%3$s</a>', $item->id, 'lost', __( 'Lost', 'erp' ) );
        }

        if ( $item->status == 'awaiting_approval' || $item->status == 'lost' || $item->status == 'won' ) {
            $actions['void'] = sprintf( '<a class="erp-accountin-trns-quote-row-bulk-action" data-id="%1$s" data-status="%2$s" href="#">%3$s</a>', $item->id, 'void', __( 'Void', 'erp' ) );
        }

        if ( $item->status == 'void' ) {
            //$actions['draft'] = sprintf( '<a class="erp-accountin-trns-quote-row-bulk-action" data-id="%1$s" data-status="%2$s" href="#">%3$s</a>', $item->id, 'draft', __( 'Draft', 'erp' ) );
        }

        if ( $item->status == 'draft' || $item->status == 'awaiting_approval' ) {
            $url   = admin_url( 'admin.php?page='.$this->slug.'&action=new&type=' . $item->form_type . '&transaction_id=' . $item->id );
            $actions['edit'] = sprintf( '<a href="%1s">%2s</a>', $url, __( 'Edit', 'erp' ) );
        }

        if ( $item->status == 'draft' || $item->status == 'void' ) {
            $actions['delete'] = sprintf( '<a href="#" class="erp-accountin-trns-quote-row-bulk-action" data-status="%s" data-id="%d" title="%s">%s</a>', 'delete', $item->id, __( 'Delete', 'erp' ), __( 'Delete', 'erp' ) );
        }

        if ( isset( $actions ) && count( $actions ) ) {
            return sprintf( '<a href="%1$s">%2$s</a> %3$s', admin_url( 'admin.php?page=' . $this->slug . '&action=view&id=' . $item->id ), erp_format_date( $item->issue_date ), $this->row_actions( $actions ) );
        } else {
            return sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=' . $this->slug . '&action=view&id=' . $item->id ), erp_format_date( $item->issue_date ) );
        }
    }
	
		 /**
     * Render the user_id column
     *
     * @since  1.1.6
     *
     * @param  object  $item
     *
     * @return string
     */
    public function column_status( $item ) {
		
		switch ( $item->status ) {
            case 'void':
				$status = 'Void';
                break;
				
            case 'draft':
				$status = 'Draft';
                break;

            case 'won':
				$status = 'Won';
                break;

            case 'lost':
				$status = 'Lost';
                break;				

            default:
                $status = 'Awaiting For Approval';
                break;
        }

        return $status;
    }
	
}
