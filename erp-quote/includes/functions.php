<?php
add_action('erp_ac_add_trans_form_header', 'woo_erp_quote_tran_from_header');
function woo_erp_quote_tran_from_header($header){
	global $current_screen;
		$addtional_headers = $header;		
    if($current_screen->base == 'accounting_page_erp-quote')
	{
		//unset($header['tax_amount']);
		unset($header['discount']);
		unset($header['tax_amount']);
		$addtional_headers = $header;		
		$add_header = array(
			'production' => __( 'Product', 'erp' ),

		);	
		
		// combine the two arrays
		$addtional_headers = array_merge($add_header, $header);
	}
	return apply_filters( 'erp_ac_trans_form_header', $addtional_headers );
}


function erp_ac_get_quote_form_types() {
    $form_types = [
        'invoice' => [
            'name'        => 'invoice',
            'label'       => __( 'Invoice Quote', 'erp' ),
            'description' => __( 'Quotes before generating receivables', 'erp' ),
            'type'        => ''
        ],
		'expense' => [
            'name'        => 'expense',
            'label'       => __( 'Expense Quote', 'erp' ),
            'description' => __( 'Quotes before generating payable', 'erp' ),
            'type'        => ''
        ],
    ];

    return apply_filters( 'erp_ac_get_quote_form_types', $form_types );
}
do_action( 'erp_ac_get_quote_form_types' );
/**
 * Get all transaction
 *
 * @param $args array
 *
 * @return array
 */
function erp_ac_get_all_transaction_quote( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'type'       => 'quote',
        'number'     => 20,
        'offset'     => 0,
        'orderby'    => 'issue_date',
        'order'      => 'DESC',
        'output_by'  => 'object'
    );

    $args            = wp_parse_args( $args, $defaults );

    $cache_key       = 'erp-ac-quote-transaction-all-' . md5( serialize( $args ) );
    $items           = wp_cache_get( $cache_key, 'erp' );
    $financial_start = date( 'Y-m-d', strtotime( erp_financial_start_date() ) );
    $financial_end   = date( 'Y-m-d', strtotime( erp_financial_end_date() ) );

    if ( false === $items ) {
        $transaction = new WeDevs\ERP\Accounting\Model\Transaction_QUOTE();
        $db          = new \WeDevs\ORM\Eloquent\Database();

        if ( isset( $args['select'] ) && count( $args['select'] ) ) {
            $transaction = $transaction->select( $args['select'] );
        }

        if ( isset( $args['join'] ) && count( $args['join'] ) ) {

            $transaction = $transaction->with( $args['join'] );
        }

        if ( isset( $args['with_ledger'] ) && $args['with_ledger'] ) {
            $transaction = $transaction->with( ['journals' => function( $q ) {
                return $q->with('ledger');
            }] );
        }

        if ( isset( $args['user_id'] ) &&  is_array( $args['user_id'] ) && array_key_exists( 'in', $args['user_id'] ) ) {
            $transaction = $transaction->whereIn( 'user_id', $args['user_id']['in'] );
        } else if ( isset( $args['user_id'] ) &&  is_array( $args['user_id'] ) && array_key_exists( 'not_in', $args['user_id'] ) ) {
            $transaction = $transaction->whereNotIn( 'user_id', $args['user_id']['not_in'] );
        } else if ( isset( $args['user_id'] ) &&  ! is_array( $args['user_id'] ) ) {
            $transaction = $transaction->where( 'user_id', '=', $args['user_id'] );
        }

        if ( isset( $args['created_by'] ) &&  is_array( $args['created_by'] ) && array_key_exists( 'in', $args['created_by'] ) ) {
            $transaction = $transaction->whereIn( 'created_by', $args['created_by']['in'] );
        } else if ( isset( $args['created_by'] ) &&  is_array( $args['created_by'] ) && array_key_exists( 'not_in', $args['created_by'] ) ) {
            $transaction = $transaction->whereNotIn( 'created_by', $args['created_by']['not_in'] );
        } else if ( isset( $args['created_by'] ) &&  ! is_array( $args['created_by'] ) ) {
            $transaction = $transaction->where( 'created_by', '=', $args['created_by'] );
        }

        if ( isset( $args['start_date'] ) && ! empty( $args['start_date'] ) ) {
            $transaction = $transaction->where( 'issue_date', '>=', $args['start_date'] );
        } else {
            //$transaction = $transaction->where( 'issue_date', '>=', $financial_start );
        }

        if ( isset( $args['end_date'] ) && ! empty( $args['end_date'] ) ) {
            $transaction = $transaction->where( 'issue_date', '<=', $args['end_date'] );
        } else {
            $transaction = $transaction->where( 'issue_date', '<=', $financial_end );
        }

        if ( isset( $args['start_due'] ) && ! empty( $args['start_due'] ) ) {
            $transaction = $transaction->where( 'due_date', '>=', $args['start_due'] );
        }

        if ( isset( $args['end_due'] ) && ! empty( $args['end_due'] ) ) {
            $transaction = $transaction->where( 'due_date', '<=', $args['end_due'] );
        }

        if ( isset( $args['ref'] ) && ! empty( $args['ref'] ) ) {
            $transaction = $transaction->where( 'ref', '=', $args['ref'] );
        }

        if ( isset( $args['status'] ) &&  is_array( $args['status'] ) && array_key_exists( 'in', $args['status'] ) ) {
            $transaction = $transaction->where( function($q)use($args) {
                $q->whereNull( 'status' )
                  ->orWhereIn( 'status', $args['status']['in'] );
            } );
            //$transaction = $transaction->whereIn( 'status', $args['status']['in'] );
        } else if ( isset( $args['status'] ) &&  is_array( $args['status'] ) && array_key_exists( 'not_in', $args['status'] ) ) {
            $transaction = $transaction->where( function($q)use($args) {
                $q->whereNull( 'status' )
                  ->orWhereNotIn( 'status', $args['status']['not_in'] );
            } );
        } else if ( isset( $args['status'] ) &&  ! is_array( $args['status'] ) ) {
            $transaction = $transaction->where( 'status', '=', $args['status'] );
        }

        if ( isset( $args['form_type'] ) &&  is_array( $args['form_type'] ) && array_key_exists( 'in', $args['form_type'] ) ) {
            $transaction = $transaction->whereIn( 'form_type', $args['form_type']['in'] );
        } else if ( isset( $args['form_type'] ) &&  is_array( $args['form_type'] ) && array_key_exists( 'not_in', $args['form_type'] ) ) {
            $transaction = $transaction->whereNotIn( 'form_type', $args['form_type']['not_in'] );
        } else if ( isset( $args['form_type'] ) &&  ! is_array( $args['form_type'] ) ) {
            $transaction = $transaction->where( 'form_type', '=', $args['form_type'] );
        }

        if ( isset( $args['wherein'] ) && is_array( $args['wherein'] ) ) {
            foreach ( $args['wherein'] as $field => $value ) {
                $transaction = $transaction->whereIn( $field, $value );
            }
        }

        if ( isset( $args['parent'] ) ) {
            $transaction = $transaction->where( 'parent', '=', $args['parent'] );
        }

        if ( isset( $args['id'] ) && ! empty( $args['id'] ) ) {
            $transaction = $transaction->where( 'id', '=', $args['id'] );
        } else if ( $args['type'] != 'any' ) {
            $transaction = $transaction->type( $args['type'] );
        }

        if ( $args['number'] != -1 ) {
            $transaction = $transaction->skip( $args['offset'] )->take( $args['number'] );
        }

        if ( isset( $args['groupby'] ) && ! empty( $args['groupby'] ) ) {
            $items = $transaction->orderBy( $args['orderby'], $args['order'] )
                ->orderBy( 'created_at', $args['order'] )
                ->get()
                ->groupBy( $args['groupby'] )
                ->toArray();

        } else {
            $items = $transaction->orderBy( $args['orderby'], $args['order'] )
                ->orderBy( 'created_at', $args['order'] )
                ->get()
                ->toArray();
        }

        if ( $args['output_by'] == 'object' ) {
            $items = erp_array_to_object( $items );
        }

        wp_cache_set( $cache_key, $items, 'erp' );
    }

    return $items;
	
}
do_action( 'erp_ac_get_all_transaction_quote', $args );


function erp_ac_quote_message( $message_ke = false ) {
    $message = array(
        'confirm'       => __( 'Create Invoice? Sit tight, we will create all for you!', 'erp' ),
        'new_customer'  => __( 'New Customer', 'erp' ),
        'new_vendor'    => __( 'New Vendor', 'erp' ),
        'new'           => __( 'Create New', 'erp' ),
        'transaction'   => __( 'Transaction History', 'erp' ),
        'processing'    => __( 'Processing please wait!', 'erp' ),
        'new_tax'       => __( 'Tax Rates', 'erp' ),
        'tax_item'      => __( 'Tax item details', 'erp' ),
        'tax_update'    => __( 'Tax Update', 'erp' ),
        'tax_deleted'   => __( 'Your tax record has been deleted successfully', 'erp' ),
        'delete'        => __( 'Are you sure you want to delete this? This cannot be undone.', 'erp' ),
        'void'          => __( 'Are you sure you want to mark this transaction as void? This action can not be reversed!', 'erp' ),
        'won'          => __( 'Glad!, you are going to Won this QUOTE?', 'erp' ),
        'lost'          => __( 'Are you sure you want to mark this transaction as Lost? This action can not be reversed!', 'erp' ),
        'restore'       => __( 'Yes, restore it!', 'erp' ),
        'cancel'        => __( 'Cancel', 'erp' ),
        'error'         => __( 'Error!', 'erp' ),
        'alreadyExist'  => __( 'Already exists as a customer or vendor', 'erp' ),
        'transaction_status' => __( 'Transaction Status', 'erp' ),
        'submit'        => __( 'Submit', 'erp' ),
        'redo'          => __( 'Yes, redo it!', 'erp' ),
        'yes'           => __( 'Yes, do it!', 'erp' ),
        'no_result'     => __( 'No Result Found!', 'erp' ),
        'search'        => __( 'Search', 'erp' )
    );

    if ( $message_ke ) {
        return apply_filters( 'erp_ac_quote_message', $message[$message_ke] );
    } else {
        return apply_filters( 'erp_ac_quote_message', $message );
    }

}

/**
 * Update transaction
 *
 * @param  int $id
 * @param  array $args
 *
 * @since  1.1.1
 *
 * @return  boolen
 */
function erp_ac_quote_update_transaction( $id, $args ) {
    \WeDevs\ERP\Accounting\Model\Transaction_QUOTE::find( $id )->update( $args );

    
	$blogusers = get_users( array('role'=>'erp_ac_manager') );
        //send email to all
        $is_enabled = get_option("erp_quote_update_quote");
        if ($is_enabled ) {
            
        
        $deparment = get_option("erp_quote_create_quote");
			if($deparment){
				$total_employees = \WeDevs\ERP\HRM\Models\Employee::where( array( 'status' => 'active', 'department' => $deparment ) )->get()->toArray();
				$employees = wp_list_pluck($total_employees,'user_id');
				$user_query = new WP_User_Query( array( 'include' => $employees ) );
				$users = $user_query->get_results();
				$emailArray = array();
				if ( ! empty( $users) ) {
					foreach ($users as $user ) {
						$emailArray[] = $user->user_email;
					}
					
					//email sent
			   

					$headers = "";
					$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";

					$erp_is_imap_active = erp_is_imap_active();
					$reply_to_name      = erp_crm_get_email_from_name();
					
					if ( $erp_is_imap_active ) {
						$imap_options = get_option( 'erp_settings_erp-email_imap', [] );
						$reply_to     = $imap_options['username'];
					} else {
						$reply_to      = erp_crm_get_email_from_address();
					}
					$current_user = wp_get_current_user();

					$headers .= "Reply-To: {$reply_to_name} <$reply_to>" . "\r\n";
				 
					$email_body = 'QUOTE #'.$id.' has been updated by ' .  $current_user->display_name;
					$emailArray = implode(', ', $emailArray);
					// Send email a contact
					wp_mail( $emailArray, 'QUOTE #'.$id.' has been updated', $email_body, $headers, [] );
				} 
			}
        }
	
	return;
	
}
do_action( 'erp_ac_quote_update_transaction', $id, $args );

/**
 * Remove transaction.
 *
 * @param  int $id
 *
 * @since  1.1.1
 *
 * @return  boolen
 */
function erp_ac_quote_remove_transaction( $id ) {

    $delete = \WeDevs\ERP\Accounting\Model\Transaction_QUOTE::where( 'id', '=', $id )->delete();
    \WeDevs\ERP\Accounting\Model\Transaction_Items_QUOTE::where( 'transaction_id', '=', $id )->delete();
   
    return $delete;
}
do_action( 'erp_ac_quote_remove_transaction', $id );


/**
 * Remove transaction items before update.
 *
 * @param  int $id
 *
 * @since  1.1.2
 *
 * @return  boolen
 */
function erp_ac_quote_remove_transaction_items( $id ) {

    $delete = \WeDevs\ERP\Accounting\Model\Transaction_Items_QUOTE::where( 'transaction_id', '=', $id )->delete();
   
    return $delete;
}
do_action( 'erp_ac_quote_remove_transaction_items', $id );


/**
 * Remove transaction.
 *
 * @param  int $id
 *
 * @since  1.1.1
 *
 * @return  boolen
 */
function erp_ac_quote_won_transaction( $id, $Bstatus ) {
	$Create_Inv = array();
	$trans = new \WeDevs\ERP\Accounting\Model\Transaction_QUOTE();
	$trans_items = new \WeDevs\ERP\Accounting\Model\Transaction_Items_QUOTE();
	$db = new \WeDevs\ORM\Eloquent\Database();
	$quote_format = erp_ac_get_quote_format( 'quote' );
	$woo_products = array();
	$args = $trans->select( array( '*' ) )
				->where( 'id', '=', $id )
				->get()->toArray();
				
	$args_items = $trans_items->select( array( '*' ) )
				->where( 'transaction_id', '=', $id )
				->orderBy('id')		
				->get()->toArray();

	$Sargs = $args[0];
	
	$paid_via = erp_get_employees(['s' => get_current_user_id()], 'byUserId')[0]->id;
		if(is_null($paid_via)){
			$paid_via = 0;
		}
	$Sargs['paid_via'] = $paid_via;	
	if($args[0]['form_type'] == 'invoice'){
		$Create_Inv['type'] = 'sales';
		$Create_Inv['form_type'] = 'invoice';
		$Create_Inv['account_id'] = 1;
		//if woocommerce disable or not required order number in quote.
		$Sargs['ref'] = erp_ac_replace_id_invoice_format( $id, $args[0]['quote_format'] );
	}else{
		$Create_Inv['type'] = 'expense';
		$Create_Inv['form_type'] = 'vendor_credit';
		$Create_Inv['account_id'] = 8;
		$Sargs['ref'] = erp_ac_replace_id_invoice_format( $id, $args[0]['quote_format'] );
	}

	$Sargs['id'] = '';
	$Sargs['type'] = $Create_Inv['type'];
	$Sargs['form_type'] = $Create_Inv['form_type'];
	$Sargs['account_id'] = $Create_Inv['account_id'];
	$Sargs['status'] = 'awaiting_payment';

	$Sargs['due'] = $args[0]['trans_total'];
	$Sargs['created_at'] = current_time( 'mysql' );
	$issue_date = current_time( 'Y-m-d' );
	$Sargs['issue_date'] = $issue_date;
	
	$Sitems = array();
	$s=0;
	
		foreach($args_items as $key => $items) {	

					// Preparing items for sales invoice
					$Sitems[$key] = $items;
					$Sitems[$key]['id'] = '';
					$Sitems[$key]['item_id'] = '';
					$Sitems[$key]['account_id'] = $items['journal_id'];

					unset($Sitems[$key]['transaction_id']);
					unset($Sitems[$key]['journal_id']);
					$Sitems[$key]['unit_price'] = $items['unit_price'];
					
					$Sitems[$key]['line_total'] = $Sitems[$key]['unit_price'] * $items['qty'];		
					$Sitems[$key]['line_total'] = $Sitems[$key]['line_total'] + (( $Sitems[$key]['line_total'] / 100) * $items['tax_rate']);		
					
					
					// When WooCommerce Option enable (MSA)			
					/*
					//prepare woocommerce order items
					if($items['product_id'] != 0){
						$woo_products[$key] = get_product($items['product_id']);
						// Change the product name & price
						$woo_products[$key]->set_price( $Sitems[$key]['unit_price'] );

					}
					else{
						$woo_products[$key] = get_product((int)get_option('wc_quote_custom_product_id'));				
						// Change the product name & price
						$woo_products[$key]->set_name( $Sitems[$key]['description'] );					
						$woo_products[$key]->set_price( $Sitems[$key]['unit_price'] );					
					}

					$reasonRes = 'Reserve for QUOTE';
    				add_reserve_stock($id, $items['product_id'],  $items['qty'], $reasonRes );			
					*/
			}

		//creating invoice/vendor credit for customer as per margin
		

		$Stransaction = erp_ac_insert_transaction( $Sargs, $Sitems );

		// When WooCommerce Option enable (MSA)				
		/**	
		if (isset( $Stransaction )){
			//preparing data for woocommerce
			global $woocommerce;		
			$company = new \WeDevs\ERP\Company();
			$user = new \WeDevs\ERP\People( intval( $Sargs['user_id'] ) );
			$default_password = wp_generate_password();
			
			if (!$Wuser = get_user_by('login', $user->email)) $Wuser = wp_create_user( $user->email, $default_password, $user->email );

			
			$address = array(
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
				'company'    => $user->company,
				'email'      => $user->email,
				'phone'      => $user->phone,
				'address_1'  => $user->street_1,
				'address_2'  => $user->street_2,
				'city'       => $user->city,
				'state'      => $user->state,
				'postcode'   => $user->postal_code,
				'country'    => $user->country
			);

			// Now we create the order
			$wc_order = wc_create_order(array('customer_id' => $Wuser->id));
			
			foreach ($woo_products as $key => $woo_product){
			// The add_product() function below is located in /plugins/woocommerce/includes/abstracts/abstract_wc_order.php
			$wc_order->add_product( $woo_product, $Sitems[$key]['qty']); // 
			}
			$wc_order->set_address( $address, 'billing' );
			$wc_order->set_address( $address, 'shipping' );
			$wc_order->calculate_totals();
			$wc_order->set_status("on-hold", "QUOTE order id: ".$id, TRUE);  			
			$wc_order->add_order_note( 'Order for: ' . $Sargs['inv_title'], 3 );		
			$wc_order->add_order_note( 'Sales invoice id: '.$Stransaction, 2 );				
			
			$wc_order->save();
			
			$order_id = trim(str_replace('#', '', $wc_order->get_order_number()));
			$order_wc = new WC_Order($order_id);
			$order_wc->payment_complete($order_id);
			$order_wc->update_status('pending', '');
			//update order id in the vendor credits
			erp_quote_insert_order_id( $Stransaction, $VTransactions, $id, $order_id);

		}	**/	

	//update QUOTE & order status finally
	erp_ac_quote_update_transaction( $id, $Bstatus );
	
    //return $Stransaction;
	return $id;
}
do_action( 'erp_ac_quote_won_transaction', $id );


/**
 * Update order ids
 *
 * @param arrays  
 *
 * @return null
 */
function erp_quote_insert_order_id( $Sid, $Vids, $bid, $oid ) {
 global $wpdb;
 $table = 'erp_ac_transactions';
 $Btable = 'erp_ac_quote_transactions';

 $Vids = implode(',', $Vids);

 $wpdb->query( 'UPDATE ' . $wpdb->prefix . $table . ' SET `order_id` = ' . $Sid . ' where `id` in (' . $Vids . ')');
 $wpdb->query( 'UPDATE ' . $wpdb->prefix . $table . ' SET `order_id` = ' . $oid . ' where `id` = ' . $Sid);
 
 $invoice_format = erp_ac_get_invoice_format( 'invoice' );
 $invoice_no = strstr($invoice_format, '-', true) . '-' . $Sid;

 $wpdb->query( 'UPDATE ' . $wpdb->prefix . $Btable . ' SET `order_id` = ' . $oid . ', `ref` = "' . $invoice_no . '" where `id` = ' . $bid );

 return;
}



function erp_ac_replace_id_invoice_format( $submit_invoice, $invoice_format ) {
    //was found
	$pattern = str_replace( '{id}', '([0-9]+)', $invoice_format ); // QUOTE-([0-9])+-QUOTE
	$submit_invoice = (int)$submit_invoice;
    preg_match( "/${pattern}/", $submit_invoice, $match );

    $id            = isset( $match[1] ) ? $match[1] : false;
    $check_invoice = false;

    if ( $id === false ) {
		$check_invoice = str_replace( '{id}', $submit_invoice, $invoice_format );

		$quote_number = $check_invoice;

		return $quote_number;
    }
	else{
		return 0;
	}
}
add_action( 'erp_ac_replace_id_invoice_format', 'erp_ac_replace_id_invoice_format', 10 , 2);


/**
 * Fetch all transaction from database
 *
 * @return array
 */
function erp_ac_quote_get_transaction_count( $args, $user_id = 0 ) {
    $status    = isset( $args['status'] ) ? $args['status'] : false;
    $cache_key = 'erp-ac-' . $args['type'] . '-' . $user_id . '-count';
    $count     = wp_cache_get( $cache_key, 'erp' );
    $end       = isset( $args['end_date'] ) ? $args['end_date'] : date( 'Y-m-d', strtotime( erp_financial_end_date() ) );

    if ( false === $count ) {
        $trans = new WeDevs\ERP\Accounting\Model\Transaction_QUOTE();

        if ( $user_id ) {
            $trans = $trans->where( 'user_id', '=', $user_id );
        }

        if ( $status ) {
            $trans = $trans->where( 'status', '=', $args['status'] );
        }

        if ( isset( $args['start_date'] ) ) {
            $trans = $trans->where( 'issue_date', '>=', $args['start_date'] );
        }

        $trans = $trans->where( 'issue_date', '<=', $end );
        $count = $trans->type( $args['type'] )->count();
    }

    return (int) $count;
}
do_action( 'erp_ac_quote_get_transaction_count', $args, $user_id );


function erp_ac_quote_get_btn_status( $postdata ) {
    $status = false;
    if ( $postdata['form_type'] == 'quote' ) {
        $status = erp_ac_get_status_according_with_btn( $postdata['btn_status'] );

    }

    return apply_filters( 'erp_ac_quote_trans_status', $status, $postdata );
}
do_action( 'erp_ac_quote_get_btn_status', $postdata );

/**
 * Fetch a single transaction from database
 *
 * @param int   $id
 *
 * @return array
 */
function erp_ac_quote_get_transaction( $id = 0, $args = [] ) {
    if ( ! intval( $id ) ) {
        return false;
    }

    $args['id']        = $id;
    $args['output_by'] = isset( $args['output_by'] ) && ! empty( $args['output_by'] ) ? $args['output_by'] : 'array';
    $cache_key         = 'erp-ac-transaction' . md5( serialize( $args ) );
    $transaction       = wp_cache_get( $cache_key, 'erp' );

    if ( false === $transaction ) {
        $transaction = erp_ac_get_all_transaction( $args );
        $transaction = reset( $transaction );

        wp_cache_set( $cache_key, $transaction, 'erp' );
        // $transaction = WeDevs\ERP\Accounting\Model\Transaction::find( $id ); //->toArray();

        // if ( ! empty( $transaction ) ) {
        //     $transaction = $transaction->toArray();
        // }
    }

    return $transaction;
}
do_action( 'erp_ac_quote_get_transaction', $id, $args );


/**
 * Chck from DB is invoice number unique or not
 *
 * @param  string  $invoice_number
 * @param  stirng  $form_type
 * @param  boolean $is_update
 * @param  mixed $trns_id
 *
 * @return boolean
 */
function erp_ac_quote_check_invoice_number_unique( $invoice, $form_type, $is_update = false, $trns_id = false ) {
    $invoice_format = erp_ac_get_quote_format( $form_type );
    $invoice_number = erp_ac_get_quote_num_fromat_from_submit_invoice( $invoice, $invoice_format );

    if ( $is_update ) {
        $trans = new \WeDevs\ERP\Accounting\Model\Transaction_QUOTE();
         if ( $invoice_number == 0 ) {
            $trans = $trans->where( 'quote_format', '=', $invoice )
                ->where( 'form_type', '=', $form_type )
                ->where( 'id', '!=', $trns_id )
                ->get()
                ->toArray();
        } else {
            $trans = $trans->where( 'quote_number', '=', $invoice_number )
                ->where( 'form_type', '=', $form_type )
                ->where( 'id', '!=', $trns_id )
                ->get()
                ->toArray();
        }

    } else {

        $trans = new \WeDevs\ERP\Accounting\Model\Transaction_QUOTE();
        if ( $invoice_number == 0 ) {
            $trans = $trans->where( 'quote_format', '=', $invoice )
                ->where( 'form_type', '=', $form_type )
                ->get()
                ->toArray();
        } else {
            $trans = $trans->where( 'quote_number', '=', $invoice_number )
                ->where( 'form_type', '=', $form_type )
                ->get()
                ->toArray();
        }
    }

    if ( $trans ) {
        return false;
    }

    return true;
}
do_action( 'erp_ac_quote_check_invoice_number_unique', $invoice, $form_type, $is_update, $trns_id );




/**
 * Insert a new transaction
 *
 * @param array $args
 * @param array $items
 *
 * @since 1.2.0 In case of update transaction, check if exists before update
 *
 * @return int/boolen
 */
function erp_ac_quote_insert_transaction( $args = [], $items = [] ) {
    global $wpdb;

    if ( ! $items ) {
        return new WP_Error( 'no-items', __( 'No transaction items found', 'erp' ) );
    }

    $defaults = array(
        'id'              => null,
        'type'            => '',
        'form_type'       => '',
        'account_id'      => '',
        'status'          => '',
        'user_id'         => '',
        'inv_title'      => isset($_POST['inv_title']) != '' ? $_POST['inv_title'] : '',	
	    'order_id'      => isset($_POST['order_id']) != '' ? $_POST['order_id'] : 0,
        'billing_address' => '',
        'ref'             => '',
        'issue_date'      => '',
        'summary'         => '',
        'total'           => '',
        'sub_total'       => '0.00',
        'quote_number'  => erp_ac_get_auto_generated_quote( 'quote' ),
        'quote_format'  => erp_ac_get_quote_format( 'quote' ),
        'files'           => '',
        'currency'        => '',
        'created_by'      => get_current_user_id(),
        'created_at'      => current_time( 'mysql' )
    );

    $args       = wp_parse_args( $args, $defaults ); //strpos($mystring, $findme);
    $is_update  = $args['id'] && ! is_array( $args['id'] ) ? true : false;

    $permission = er_ac_insert_transaction_permiss( $args, $is_update );

    if ( is_wp_error( $permission ) ) {
        return $permission;
    }

    $quote = erp_ac_get_quote_num_fromat_from_submit_invoice( $args['quote_number'], $args['quote_format'] );

    if ( $quote == 0 ) {
        $args['quote_number'] = $args['quote_number'];
        $args['quote_format'] = 0;
    } else {
        $args['quote_number'] = $quote;
    }

    $table_name = $wpdb->prefix . 'erp_ac_quote_transactions';

    $register_type = apply_filters( 'erp_ac_quote_register_type', [ 'quote' ] );

    // get valid transaction type and form type
    if ( ! in_array( $args['type'], $register_type ) ) {
        return new WP_Error( 'invalid-trans-type', __( 'Error: Invalid transaction type.', 'erp' ) );
    }

    if ( $args['type'] == 'quote' ) {
        $form_types = erp_ac_get_quote_form_types();
    }

    $form_types = apply_filters( 'erp_ac_quote_form_types', $form_types, $args );

    if ( ! array_key_exists( $args['form_type'], $form_types ) ) {
        return new WP_Error( 'invalid-form-type', __( 'Error: Invalid form type', 'erp' ) );
    }

    $form_type = $form_types[ $args['form_type'] ];

    // some basic validation
    if ( empty( $args['issue_date'] ) ) {
        return new WP_Error( 'no-issue_date', __( 'No Issue Date provided.', 'erp' ) );
    }
    if ( empty( $args['total'] ) ) {
        return new WP_Error( 'no-total', __( 'No Total provided.', 'erp' ) );
    }

    // remove row id to determine if new or update
    $row_id          = (int) $args['id'];
    $main_account_id = (int) $args['account_id'];

    //unset( $args['id'] );
    unset( $args['account_id'] );

    // BEGIN INSERTION
    try {
        $wpdb->query( 'START TRANSACTION' );

        if ( $is_update ) {

            $trans = WeDevs\ERP\Accounting\Model\Transaction_QUOTE::find( $args['id'] );

            if ( $trans ) {
                $trans->update( $args );
                $trans_id    = $trans ? $args['id'] : false;
                erp_ac_update_quote_number( $args['form_type'] );
				erp_ac_quote_remove_transaction_items($args['id']);
            }
        } else {
            $trans    = WeDevs\ERP\Accounting\Model\Transaction_QUOTE::create( $args );

            $trans_id = $trans->id;
			
            if ( $trans->id ) {
                erp_ac_update_quote_number( $args['form_type'] );
            }
        }

        if ( empty( $trans_id ) ) {
            throw new Exception( __( 'Could not create transaction', 'erp' ) );
        }
	


        // enter the transaction items
        $order           = 1;
        $item_entry_type = ( $form_type['type'] == 'credit' ) ? 'debit' : 'credit';

        foreach ( $items as $key => $item ) {
			$journal_id = 0;
            $tax_id  = erp_ac_tax_update( $item, $item_entry_type, $args, $trans_id );
            $item_id = erp_ac_quote_item_update( $item, $args, $trans_id, $journal_id, $tax_id, $order );

            if ( ! $item_id ) {
                throw new Exception( __( 'Could not insert transaction item', 'erp' ) );
            }

            $order++;
        }

        if ( $is_update ) {
            $tax_jor_id = wp_list_pluck( $items, 'tax_journal' );

        }

        $wpdb->query( 'COMMIT' );


        do_action( 'erp_ac_new_transaction', $trans_id, $args, $items );

        // Transaction type hook eg: erp_ac_new_transaction_quote
        do_action( "erp_ac_new_transaction_{$args['type']}", $trans_id, $args, $items );
        //check if enabled
        //get department users in array
        //send email to all
        $deparment = get_option("erp_quote_create_quote");
        if($deparment){
            $total_employees = \WeDevs\ERP\HRM\Models\Employee::where( array( 'status' => 'active', 'department' => $deparment ) )->get()->toArray();
            $employees = wp_list_pluck($total_employees,'user_id');
            $user_query = new WP_User_Query( array( 'include' => $employees ) );
            $users = $user_query->get_results();
            $emailArray = array();
            if ( ! empty( $users) ) {
                foreach ($users as $user ) {
                    $emailArray[] = $user->user_email;
                }
                
                //email sent
           

                $headers = "";
                $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";

                $erp_is_imap_active = erp_is_imap_active();
                $reply_to_name      = erp_crm_get_email_from_name();
                
                if ( $erp_is_imap_active ) {
                    $imap_options = get_option( 'erp_settings_erp-email_imap', [] );
                    $reply_to     = $imap_options['username'];
                } else {
                    $reply_to      = erp_crm_get_email_from_address();
                }
                

                $headers .= "Reply-To: {$reply_to_name} <$reply_to>" . "\r\n";

                

               /* $message_id = md5( uniqid( time() ) ) . '.' . $emailArray . '.' . '.r2@' . $_SERVER['HTTP_HOST'];

                $custom_headers = [
                    "In-Reply-To" => "<{$message_id}>",
                    "References" => "<{$message_id}>",
                ];*/

                $query = [
                    'action' => 'erp_crm_track_email_opened',
                    'aid'    => $data['id'],
                ];

                $email_url  = add_query_arg( $query, admin_url('admin-ajax.php') );
                $img_url    = '<img src="' . $email_url . '" width="1" height="1" style="display:none;" />';

                $email_body = 'QUOTE has been created.';
                $emailArray = implode(', ', $emailArray);
                // Send email a contact
                wp_mail( $emailArray, 'QUOTE has been created', $email_body, $headers, [] );
            } 
        }
        
        return $trans_id;

    } catch (Exception $e) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_error( 'final-exception', $e->getMessage() );
    }

    return false;
}
do_action( 'erp_ac_quote_insert_transaction', $args, $items );



/**
 * Update transaction item
 *
 * @since  1.1.5
 *
 * @param  array $item
 * @param  array $args
 * @param  int $trans_id
 * @param  int $journal_id
 * @param  int $tax_journal
 * @param  int $order
 *
 * @return int
 */
function erp_ac_quote_item_update( $item, $args, $trans_id, $journal_id, $tax_journal, $order ) {

        $trans_item = WeDevs\ERP\Accounting\Model\Transaction_Items_QUOTE::create([
			'journal_id'  => $item['account_id'],				
            'product_id'     => isset( $item['product_id'] ) ? $item['product_id'] : '',
            'transaction_id' => $trans_id,
            'description'    => $item['description'],
            'qty'            => $item['qty'],
            'unit_price'     => $item['unit_price'],
            'discount'       => $item['discount'],
            'tax'            => isset( $item['tax'] ) ? $item['tax'] : 0,
            'tax_rate'       => isset( $item['tax_rate'] ) ? $item['tax_rate'] : '0.00',
            'line_total'     => $item['line_total'],
            'order'          => $order,
            'tax_journal'    => $tax_journal
        ]);

        $trans_item_id = $trans_item ? $trans_item->id : false;

    return $trans_item_id;
}
do_action( 'erp_ac_quote_item_update', $item, $args, $trans_id, $journal_id, $tax_journal, $order );


/**
 * Update Invoice number
 *
 * @param  string $form_type
 *
 * @return void
 */
function erp_ac_update_quote_number( $form_type ) {

    $invoice_number = '';
	$invoice_number = get_option( 'erp_ac_sales_quote_number', 1 );
    $get_invoice_number = WeDevs\ERP\Accounting\Model\Transaction_QUOTE::select('quote_number')
    //    ->where( 'form_type', '=', $form_type )
        ->where( 'quote_number', '>=', $invoice_number )
        ->get()->toArray();
    $get_invoice_number = wp_list_pluck( $get_invoice_number, 'quote_number' );
    $status = true;

    while( $status ) {
        if ( in_array( $invoice_number, $get_invoice_number ) ) {
            $invoice_number = $invoice_number + 1;
			update_option( 'erp_ac_sales_quote_number', $invoice_number );
        } else {
            $status = false;			
        }
    }

}
do_action( 'erp_ac_update_quote_number', $form_type );

/**
 * Get invoice number and format fron transaction submit value
 *
 * @param  string $submit_invoice
 * @param  string $invoice_format
 *
 * @return array
 */
function erp_ac_get_quote_num_fromat_from_submit_invoice( $submit_invoice, $invoice_format ) {
    //was found
    $pattern = str_replace( '{id}', '([0-9]+)', $invoice_format ); // QUOTE-([0-9])+-QUOTE

    preg_match( "/${pattern}/", $submit_invoice, $match );

    $id            = isset( $match[1] ) ? $match[1] : false;
    $check_invoice = false;

    if ( $id === false ) {
        return 0;
    }

    $check_invoice = str_replace( '{id}', $id, $invoice_format );

    $quote_number = $check_invoice == $submit_invoice ? intval( $id ) : 0;

    return $quote_number;
}
do_action( 'erp_ac_get_quote_num_fromat_from_submit_invoice', $submit_invoice, $invoice_format );


/**
 * Get QUOTE number
 *
 * @param  int $quote_number
 * @param  string $quote_number
 *
 * @return string
 */
function erp_ac_get_quote_number( $quote_number, $invoice_format ) {
    if ( $quote_number != 0 ) {
        return  str_replace( '{id}', erp_ac_quote_num_str_pad( $quote_number ), $invoice_format );
    } else {
        return $invoice_format;
    }
}
do_action( 'erp_ac_get_quote_number', $quote_number, $invoice_format );

/**
 * Str pad for quote number
 *
 * @param  int $quote
 *
 * @since  1.1.2
 *
 * @return string
 */
function erp_ac_quote_num_str_pad( $quote_number ) {
    return str_pad( $quote_number, 4, '0', STR_PAD_LEFT );
}
do_action( 'erp_ac_quote_num_str_pad', $quote_number );

/**
 * Get QUOTE prefix
 *
 * @param  string $form_type
 * @param  int $id
 *
 * @since  1.1.2
 *
 * @return string
 */
function erp_ac_get_auto_generated_quote( $form_type ) {
    $quote_number = erp_ac_generate_quote_id( $form_type );
    $quote_number = erp_ac_quote_num_str_pad( $quote_number );
    $prefix         = erp_ac_get_quote_format( $form_type );
    return str_replace( '{id}', $quote_number, $prefix );
}
do_action( 'erp_ac_get_auto_generated_quote', $form_type );

/**
 * Get quote prefix
 *
 * @param  string $form_type
 *
 * @since  1.1.2
 *
 * @return string
 */
function erp_ac_get_quote_format( $form_type ) {
    if ( $form_type == 'quote' ) {
        return erp_get_option( 'erp_ac_quote', false, 'PO-{id}' );
    }
    return false;
}
do_action( 'erp_ac_get_quote_format', $form_type );

/**
 * Generate quote id
 *
 * @param  string $form_type
 *
 * @return int
 */
function erp_ac_generate_quote_id( $form_type = '' ) {

    $invoice_number = false;

    if ( $form_type == 'quote' ) {
        $invoice_number = get_option( 'erp_ac_sales_quote_number', 1 );
    } 

    return str_pad( $invoice_number, 4, '0', STR_PAD_LEFT );
}
do_action( 'erp_ac_generate_quote_id', $form_type);

/**
 * Get url for quote menu
 *
 * @param  str $content
 *
 * @since 1.1.0
 *
 * @return str
 */
function erp_ac_quote_get_sales_url( $content = false ) {
	if ( ! current_user_can( 'erp_ac_view_sale' ) ) {
		return apply_filters( 'erp_ac_quote_get_sales_url', $content );
	}

	if ( $content ) {
		$url = sprintf( '<a href="%s">%s</a>', $_SERVER['REQUEST_URI'], $content );
	} else {
		$url = $_SERVER['REQUEST_URI'];
	}

	return apply_filters( 'erp_ac_quote_get_sales_url', $url, $content );
}


/**
 * Get url for sales payment
 *
 * @param  int $transaction_id
 *
 * @since 1.1.0
 *
 * @return str
 */
function erp_ac_get_quote_invoice_url( $transaction_id ) {
	$url_args = [
		'page'   => 'erp-quote',
		'action' => 'view',
		'id'     => $transaction_id
	];

	$url = add_query_arg( $url_args, admin_url( 'admin.php' ) );

	return apply_filters( 'slaes_payment_invoice_url', $url, $transaction_id );
}
function custom_admin_js() {
    echo '<script>jQuery("#erp_quote_create_quote").attr("multiple","multiple"); </script>';
    echo '<script>jQuery("#erp_quote_won_quote").attr("multiple","multiple"); </script>';
}
add_action('admin_footer', 'custom_admin_js');