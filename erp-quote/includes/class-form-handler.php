<?php
namespace WeDevs\ERP\Accounting;

/**
 * Handle the form submissions
 *
 * @package Package
 * @subpackage Sub Package
 */
class Form_Handler_QUOTE extends Form_Handler{
    public static $errors;
    /**
     * Hook 'em all
     */
    public function __construct() {

        add_action( 'erp_action_ac-new-quote', array( $this, 'transaction_form' ) );
       // add_action( "load-{$accounting}_page_erp-accounting-sales", array( $this, 'sales_bulk_action') );
        $accounting = sanitize_title( __( 'Accounting', 'erp' ) );
		add_filter( 'quote_transaction_data_process_via_submit_bid', array( $this, 'transaction_data_process') );
    }

   
    /**
     * Bulk action for sales list table
     *
     * @since  1.1.0
     *
     * @return void
     */
    function sales_bulk_action() {

        if ( ! $this->verify_current_page_screen( 'erp-accounting-sales', 'bulk-sales' ) ) {
            return;
        }

        if ( ! isset( $_REQUEST['transaction_id'] ) || ! isset( $_REQUEST['submit_sales_bulk_action'] ) ) {
            return;
        }

        $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

        foreach ( $_REQUEST['transaction_id'] as $key => $trans_id ) {
            switch ( $action ) {
                case 'delete':
                    erp_ac_remove_transaction( $trans_id );
                    break;

                case 'void':
                    erp_ac_update_transaction_to_void( $trans_id );
                    break;

                default:
                    erp_ac_update_transaction( $trans_id, ['status' => $action] );
                    break;
            }
        }

        wp_safe_redirect( $_REQUEST['_wp_http_referer'] );
    }

 
    /**
     * Redirect after transaction list table submit for search
     *
     * @since  1.1.0
     *
     * @return void
     */
    function bulk_search() {
        $redirect_to = add_query_arg( array( 's' => $_POST['s'] ), $_POST['_wp_http_referer'] );
        wp_redirect( $redirect_to );
        exit();
    }

    /**
     * Check is current page actions
     *
     * @since 0.1
     *
     * @param  integer $page_id
     * @param  integer $bulk_action
     *
     * @return boolean
     */
    public function verify_current_page_screen( $page_id, $bulk_action ) {

        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! isset( $_GET['page'] ) ) {
            return false;
        }

        if ( $_GET['page'] != $page_id ) {
            return false;
        }

        if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $bulk_action ) ) {
            return false;
        }

        return true;
    }


 
    /**
     * Transaction form data
     *
     * @since  1.1.0
     *
     * @return void
     */
    public function transaction_form() {

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'erp-ac-trans-new' ) ) {
            die( __( 'Are you cheating?', 'erp' ) );
        }

        if ( ! current_user_can( 'read' ) ) {
            wp_die( __( 'Permission Denied!', 'erp' ) );
        }

		if($_POST['enable_invoice'] == 'no')
			$_POST['order_id'] = -1;

        $insert_id = $this->transaction_data_process( $_POST );
        $page_url  = isset( $_POST['_wp_http_referer'] ) ? $_POST['_wp_http_referer'] : '';

        if ( is_wp_error( $insert_id ) ) {
            self::$errors = $insert_id->get_error_message();
            $redirect_to  = add_query_arg( array( 'message' => $insert_id ), $page_url );
            wp_safe_redirect( $redirect_to );
            exit;

        } else {
            $redirect_to = add_query_arg( array( 'msg' => 'success' ), $page_url );
        }

        if ( $_POST['redirect'] == 'same_page' ) {
            $redirect_to = remove_query_arg( ['transaction_id'], wp_unslash( $_SERVER['REQUEST_URI'] ) );

        } else if ( $_POST['redirect'] == 'single_page' ) {

            if ( $_POST['type'] == 'quote' ) {
                $redirect_to = erp_ac_get_quote_invoice_url( $insert_id );
			}
        }

        $redirect_to = apply_filters( 'erp_ac_redirect_after_transaction', $redirect_to, $insert_id, $_POST );
        wp_safe_redirect( $redirect_to );
        exit;
    }

    /**
     * Handle the transaction new and edit form
     *
     * @return void
     */
    public function transaction_data_process( $postdata ) {

        $status          = erp_ac_quote_get_btn_status( $postdata );
        $errors          = array();
        $insert_id       = 0;
        $field_id        = isset( $postdata['field_id'] ) ? intval( $postdata['field_id'] ) : 0;
        $page            = isset( $postdata['page'] ) ? sanitize_text_field( $postdata['page'] ) : '';
        $type            = isset( $postdata['type'] ) ? sanitize_text_field( $postdata['type'] ) : '';
        $form_type       = isset( $postdata['form_type'] ) ? sanitize_text_field( $postdata['form_type'] ) : '';
        $account_id      = isset( $postdata['account_id'] ) ? intval( $postdata['account_id'] ) : 0;
        $status          = isset( $postdata['status'] ) ? sanitize_text_field( $postdata['status'] ) : 'closed';
        $user_id         = isset( $postdata['user_id'] ) ? intval( $postdata['user_id'] ) : 0;
        $billing_address = isset( $postdata['billing_address'] ) ? wp_kses_post( $postdata['billing_address'] ) : '';
        $ref             = isset( $postdata['ref'] ) ? sanitize_text_field( $postdata['ref'] ) : '';
        $issue_date      = isset( $postdata['issue_date'] ) ? sanitize_text_field( $postdata['issue_date'] ) : '';
        $due_date        = isset( $postdata['due_date'] ) ? sanitize_text_field( $postdata['due_date'] ) : '';
        $summary         = isset( $postdata['summary'] ) ? wp_kses_post( $postdata['summary'] ) : '';
        $total           = isset( $postdata['price_total'] ) ? sanitize_text_field( erp_ac_format_decimal( $postdata['price_total'] ) ) : '';
        $files           = isset( $postdata['files'] ) ? maybe_serialize( $postdata['files'] ) : '';
        $currency        = isset( $postdata['currency'] ) ? sanitize_text_field( $postdata['currency'] ) : 'PKR';
        $transaction_id  = isset( $postdata['id'] ) ? $postdata['id'] : false;
        $line_account    = isset( $postdata['line_account'] ) ? $postdata['line_account'] : array();
        $page_url        = admin_url( 'admin.php?page=' . $page );
        $items_id        = isset( $postdata['items_id'] ) ? $postdata['items_id'] : [];
        $journals_id     = isset( $postdata['journals_id'] ) ? $postdata['journals_id'] : [];
        $partial_id      = isset( $postdata['partial_id'] ) ? $postdata['partial_id'] : [];
        $sub_total       = isset( $postdata['sub_total'] ) ? $postdata['sub_total'] : '0.00';
        $QUOTE         = isset( $postdata['quote-no'] ) ? $postdata['quote-no'] : 0;

        // some basic validation
        if ( ! $issue_date ) {
            return new \WP_Error( 'required_issue_date', __( 'Error: Issue Date is required', 'erp' ) );
        }

        if ( ! $account_id ) {
            return new \WP_Error( 'required_account_id', __( 'Error: Account ID is required', 'erp' ) );
        }

        if ( ! $total ) {
            return new \WP_Error( 'required_total_amount', __( 'Error: Total is required', 'erp' ) );
        }
        $thousand_seperator = erp_ac_get_price_thousand_separator();

        $fields = [
            'id'              => $transaction_id,
            'partial_id'      => $partial_id,
            'items_id'        => $items_id,
            'journals_id'     => $journals_id,
            'type'            => $type,
            'form_type'       => $form_type,
            'account_id'      => $account_id,
            'status'          => $status,
            'user_id'         => $user_id,
            'billing_address' => $billing_address,
            'ref'             => $ref,
            'issue_date'      => $issue_date,
            'due_date'        => $due_date,
            'summary'         => $summary,
            'total'           => str_replace( $thousand_seperator, '', $total ),
            'sub_total'       => str_replace( $thousand_seperator, '', $sub_total ),
            'quote_number'  => $QUOTE,
            'trans_total'     => str_replace( $thousand_seperator, '', $total ),
            'files'           => $files,
            'currency'        => $currency,
            'line_total'      => isset( $postdata['line_total'] ) ? str_replace( $thousand_seperator, '', $postdata['line_total'] ) : array()
        ];

        // set invoice and vendor credit for due to full amount
        if ( $this->is_due_trans( $form_type, $postdata ) ) { //in_array( $form_type, [ 'invoice', 'vendor_credit' ] ) ) {
            $fields['due'] = str_replace( $thousand_seperator, '', $total );
        }

        $items = [];

        foreach ( $line_account as $key => $acc_id) {

            $line_total = erp_ac_format_decimal( $postdata['line_total'][ $key ] );

            if ( ! $acc_id || ! $line_total ) {
                continue;
            }
				
				$items[] = apply_filters( 'erp_ac_quote_transaction_lines', [
					'item_id'     => isset( $postdata['items_id'][$key] ) ? $postdata['items_id'][$key] : [],
					'journal_id'  => isset( $postdata['journal_id'][$key] ) ? $postdata['journal_id'][$key] : 0,
					'product_id'  => isset( $postdata['product_id'][$key] ) ? $postdata['product_id'][$key] : 0,				
					'account_id'  => (int) $acc_id,
					'line_account'  => isset( $postdata['line_account'][$key] ) ? $postdata['line_account'][$key] : 0,
					'description' => sanitize_text_field( $postdata['line_desc'][ $key ] ),
					'qty'         => $postdata['line_qty'][ $key ],
					'unit_price'  => str_replace( $thousand_seperator, '', erp_ac_format_decimal( $postdata['line_unit_price'][ $key ] ) )  ,
					'discount'    => erp_ac_format_decimal( $postdata['line_discount'][ $key ] ),
					'tax'         => isset( $postdata['line_tax'][$key] ) ? $postdata['line_tax'][$key] : 0,
					'tax_rate'    => isset( $postdata['tax_rate'][$key] ) ? str_replace( $thousand_seperator, '', $postdata['tax_rate'][$key] ) : 0,
					'tax_amount'  => isset( $postdata['tax_amount'][$key] ) ? str_replace( $thousand_seperator, '', $postdata['tax_amount'][$key] ) : 0,
					'line_total'  => str_replace( $thousand_seperator, '', erp_ac_format_decimal( $line_total ) ),
					'tax_journal' => isset( $postdata['tax_journal'][$key] ) ? $postdata['tax_journal'][$key] : 0
				], $key, $postdata );

				
        }		

        // New or edit?
        if ( ! $field_id ) {
            $insert_id = erp_ac_quote_insert_transaction( $fields, $items );
        }

        return $insert_id;
    }
	
}
 