<?php
$form_type = isset( $_GET['type'] ) ? $_GET['type'] : false;

$customer_id    = isset( $_GET['customer'] ) && $_GET['customer'] == 'true' ? intval( $_GET['id'] ) : false;

$transaction_id = isset( $_GET['transaction_id'] ) ? intval( $_GET['transaction_id'] ) : false;

$transaction    = [];
$jor_itms       = [];
$cancel_url     = erp_ac_quote_get_sales_url();

if ( $transaction_id ) {
	if(!$form_type)
		 wp_die( __( 'Type not selected!', 'erp' ) );
	
    $transaction = erp_ac_get_all_transaction_quote([
        'id'        => $transaction_id,
        'form_type' => $form_type,
        'status'    => [ 'in' => ['draft', 'awaiting_approval'] ],
        'join'      => ['items'],
        'type'      => ['quote'],
        'output_by' => 'array'
    ]);
	
	if(is_array($transaction[0]))
		$transaction = $transaction[0];

if ( !$transaction_id ) {
    $transaction = reset( $transaction );
}
    $user_id = isset( $transaction['user_id'] ) ? intval( $transaction['user_id'] ) : false;	
	
if($transaction['order_id'] == -1)
	$enable_inv    = 'no';
else
	$enable_inv    = 'yes';
	
	
    foreach ( $transaction["items"] as $key => $item ) {
        $journal_id = $item['journal_id'];
		$item['line_account'] = $item['account_id'];		
        $jor_itms['item'][] = $item;
		$jor_itms['line_account'][]['line_account'] = $item['line_account'];
		$jor_itms['journal'][]['ledger_id'] = $item['account_id'];
    }

}
$items_for_tax = isset( $transaction['items'] ) ? $transaction['items'] : [];
$tax_labels    = erp_ac_get_trans_unit_tax_rate( $items_for_tax );
?>

<div class="wrap erp-ac-form-wrap">
    <h2><?php _e( 'New Quote', 'erp' ); ?></h2>

    <?php erp_ac_view_error_message(); ?>

    <?php

	if($form_type == "invoice"){
		$dropdown_sales = erp_ac_get_chart_dropdown([
			'exclude'  => [1, 2, 3],

		] );
	   $accounts_receivable_id = WeDevs\ERP\Accounting\Model\Ledger::code('120')->first()->id;
		$filter_option = reset( $dropdown );
		$options       = wp_list_pluck( $filter_option['options'], 'code' );
		$code_key      = array_search( '475', $options );

		unset( $dropdown_sales[4]['options'][$code_key] );	   
	}else{
		$accounts_receivable_id = WeDevs\ERP\Accounting\Model\Ledger::code('200')->first()->id;
		$dropdown = erp_ac_get_chart_dropdown([
			'exclude'  => [4,5],
			'excludeType'  => [6],
			'excludeLedger'  => [8,1],
		] );		
		
	}

	if($form_type == "invoice"){
		$classProduce = 'erp-ac-vendor_credit-form';
		$dropdown_html = erp_ac_render_account_dropdown_html( $dropdown_sales, array(
			'name'     => 'line_account[]',
			'selected' => isset( $journal_id ) ? $journal_id : false,
			'class'    => 'erp-select2'
		) );
	}else{
		$classProduce = 'erp-ac-vendor_credit-form';
		$dropdown_html = erp_ac_render_account_dropdown_html( $dropdown, array(
			'name'     => 'line_account[]',
			'selected' => isset( $journal_id ) ? $journal_id : false,
			'class'    => 'erp-select2'
		) );
	}


     ?>

    <form action="" method="post" class="erp-form erp-ac-transaction-form <?= $classProduce; ?>">

        <ul class="form-fields block" style="width:100%;">

		    <li>
                <ul class="erp-form-fields block full-col">
					<li class="erp-form-field">
                        <?php
                        erp_html_form_input( array(
                            'label' => __( 'Title', 'erp' ),
                            'name'  => 'inv_title',
                            'type'  => 'text',
                            'class' => 'inv_title',
                            'value' => isset( $transaction['inv_title'] ) ? $transaction['inv_title'] : ''
                        ) );
                        ?>
                    </li>
				</ul>
			</li>
		
            <li>
                <ul class="erp-form-fields two-col block">
                    <li class="erp-form-field erp-ac-replace-wrap">
                        <div class="erp-ac-replace-content">
                            <?php
					if($form_type == "invoice"){	
                            erp_html_form_input( array(
                                'label'       => __( 'Customer', 'erp' ),
                                'name'        => 'user_id',
                                'placeholder' => __( 'Select a payee', 'erp' ),
                                'type'        => 'select',
                                'id'          => 'erp-ac-select-user-for-assign-contact',
                                'required'    => true,
                                //'class'       => 'erp-select2 erp-ac-customer-drop erp-ac-not-found-in-drop',
                                'value'       => $user_id ? $user_id : '',
                                'options'     => [ '' => __( 'Search', 'erp' ) ] + erp_get_peoples_array( ['type' => 'customer', 'number' => '-1' ] ),
                                'custom_attr' => [
                                    'data-content' => 'erp-ac-new-customer-content-pop',
                                    'data-type'    => 'customer'
                                ],
                            ) );

                            if ( erp_ac_create_customer() ) {
                                ?>
                                <div><a href="#" data-content="erp-ac-new-customer-content-pop" class="erp-ac-not-found-btn-in-drop erp-ac-more-customer"><?php _e( 'Create New', 'erp' ); ?></a></div>
                                <?php
                            }
					}else{
                            erp_html_form_input( array(
                                'label'       => __( 'Vendor', 'erp' ),
                                'name'        => 'user_id',
                                'type'        => 'select',
                                'required'    => true,
                                'id'          => 'erp-ac-select-user-for-assign-contact',
                                //'class'       => 'erp-select2 erp-ac-vendor-drop erp-ac-not-found-in-drop',
                                'options'     => [ '' => __( 'Search by vendor', 'erp' ) ] + erp_ac_get_vendors(),
                                'custom_attr' => [
                                    'data-placeholder' => __( 'Search by vendor', 'erp' ),
                                    'data-content' => 'erp-ac-new-vendor-content-pop',
                                    'data-type' => 'vendor'
                                ],
                                'value' => isset( $user_id ) ? $user_id : ''
                            ) );
                            ?>
                            <div><a href="#" data-content="erp-ac-new-vendor-content-pop" class="erp-ac-not-found-btn-in-drop erp-ac-more-customer"><?php _e( 'Create New', 'erp' ); ?></a></div>
					<?php
					}
					?>
                        </div>
                    </li>

                    <li class="erp-form-field">
                        <?php
                        erp_html_form_input( array(
                            'label' => __( 'Reference', 'erp' ),
                            'name'  => 'ref',
                            'type'  => 'text',
                            'class' => 'erp-ac-reference-field',
                            'addon' => '#',
                            'value' => isset( $transaction['ref'] ) ? $transaction['ref'] : ''
                        ) );
                        ?>
                    </li>
                    <li class="erp-form-field">
                        <?php
                        erp_html_form_input( array(
                            'label'    => __( 'QUOTE Number', 'erp' ),
                            'name'     => 'quote-no',
                            'type'     => 'text',
                            'required' => true,
                            'custom_attr' => [
                                'data-old_val' => isset( $transaction['invoice_number']  ) ? erp_ac_get_quote_number( $transaction['invoice_number'], $transaction['invoice_format'] ) : erp_ac_get_auto_generated_quote( 'quote' ),
                            ],
                            'class'    => 'erp-ac-check-quote-number',
                            'value'    => isset( $transaction['invoice_number']  ) ? erp_ac_get_quote_number( $transaction['invoice_number'], $transaction['invoice_format'] ) : erp_ac_get_auto_generated_quote( 'quote' )
                        ) );
                        ?>
                    </li>
                </ul>
            </li>

            <li>
                <ul class="erp-form-fields two-col block clearfix">
                    <li class="erp-form-field">
                        <?php
                        erp_html_form_input( array(
                            'label'       => __( 'Created Date', 'erp' ),
                            'name'        => 'issue_date',
                            'placeholder' => date( 'Y-m-d' ),
                            'type'        => 'text',
                            'required' => true,
                            'class'       => 'erp-date-picker-from',
                            'value' => isset( $transaction['issue_date'] ) ? $transaction['issue_date'] : date( 'Y-m-d', strtotime( current_time( 'mysql' ) ) )
                        ) );
                        ?>
                    </li>

                    <li class="erp-form-field">
                        <?php
                        erp_html_form_input( array(
                            'label'       => __( 'Due Date', 'erp' ),
                            'name'        => 'due_date',
                            'placeholder' => date( 'Y-m-d' ),
                            'type'        => 'text',
                            'required'    => true,
                            'class'       => 'erp-date-picker-to',
                            'value' => isset( $transaction['due_date'] ) ? $transaction['due_date'] : ''
                        ) );
                        ?>
                    </li>

                </ul>
            </li>

            <li class="erp-form-field">
                <?php
                erp_html_form_input( array(
                    'label'       => __( 'Billing Address', 'erp' ),
                    'name'        => 'billing_address',
                    'placeholder' => '',
                    'type'        => 'textarea',
                    'custom_attr' => [
                        'rows' => 3,
                        'cols' => 30
                    ],
                    'value' => isset( $transaction['billing_address'] ) ? $transaction['billing_address'] : ''
                ) );
                ?>
            </li>

        </ul>

        <?php include dirname( dirname( __FILE__ ) ) . '/views/common/transaction-table.php'; ?>
        <?php include dirname( dirname( __FILE__ ) ) . '/views/common/memo.php'; ?>

        <input type="hidden" name="field_id" value="0">
		<input type="hidden" name="account_id" value="<?php echo $accounts_receivable_id; ?>">
        <input type="hidden" name="status" value="awaiting_approval">
        <input type="hidden" name="type" value="quote">
        <input type="hidden" name="form_type" value="<?php echo $form_type; ?>">
        <input type="hidden" name="page" value="erp-quote">
        <input type="hidden" name="erp-action" value="ac-new-quote">

        <?php erp_html_form_input( array(
            'name'        => 'id',
            'type'        => 'hidden',
            'value'       => $transaction_id
        ) ); ?>

        <?php wp_nonce_field( 'erp-ac-trans-new' ); ?>

        <input type="submit" name="submit_erp_ac_trans" style="display: none;">
        <input type="hidden" id="erp-ac-btn-status" name="btn_status" value="">
        <input type="hidden" id="erp-ac-redirect" name="redirect" value="0">

        <div class="erp-ac-btn-group-wrap">

            <div class="erp-button-bar-left">
                <?php
                    if ( isset( $transaction['status'] ) && $transaction['status'] == 'pending' ) {
                        ?>
                        <button type="button" data-redirect="0" data-btn_status="save_and_submit_for_approval" class="button erp-ac-trns-form-submit-btn">
                            <?php _e( 'Save', 'erp' ); ?>
                        </button>
                        <?php
                    } else if ( isset( $transaction['status'] ) && $transaction['status'] == 'awaiting_payment' ) {

                    } else if ( ! isset( $transaction['status'] ) || ( isset( $transaction['status'] ) && $transaction['status'] == 'draft' ) ) {
                        ?>
                        <div class="button-group erp-button-group">
                             <button type="button" data-redirect="0" data-btn_status="save_and_draft" class="button erp-ac-trns-form-submit-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php _e( 'Save as Draft', 'erp' ); ?>
                            </button>
                            <button type="button" class="button erp-dropdown-toggle" data-toggle="erp-dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="caret"></span>
                            </button>
                            <ul class="erp-dropdown-menu">
                                <li><a class="erp-ac-trns-form-submit-btn" data-redirect="0" data-btn_status="save_and_draft" href="#"><?php _e( 'Save as Draft', 'erp' ); ?></a></li>
                                <li><a class="erp-ac-trns-form-submit-btn" data-redirect="same_page" data-btn_status="save_and_add_another" href="#"><?php _e( 'Save & add another', 'erp' ); ?></a></li>
                            </ul>

                        </div>
                        <?php
                    }
                ?>
            </div>

            <div class="erp-button-bar-right">
                <?php
                if ( ! isset( $transaction['status'] ) || ( isset( $transaction['status'] ) && $transaction['status'] == 'draft' ) ) {
                    ?>
                    <div class="button-group erp-button-group">
                        <button  data-redirect="single_page" data-btn_status="save_and_submit_for_approval" type="button" class="button button-primary erp-ac-trns-form-submit-btn">
                            <?php _e( 'Submit for Approval', 'erp' ); ?>
                        </button>
                        <button type="button" class="button button-primary erp-dropdown-toggle" data-toggle="erp-dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                        </button>
                        <ul class="erp-dropdown-menu">
                            <li><a class="erp-ac-trns-form-submit-btn" data-redirect="single_page" data-btn_status="save_and_submit_for_approval" href="#"><?php _e( 'Submit for Approval', 'erp' ); ?></a></li>
                            <li><a class="erp-ac-trns-form-submit-btn" data-redirect="same_page" data-btn_status="approve_and_add_another" href="#"><?php _e( 'Submit for approval & add another', 'erp' ); ?></a></li>
                        </ul>
                    </div>
                    <?php
                } else if ( ( isset( $transaction['status'] ) && $transaction['status'] == 'awaiting_approval' ) ) {
                    ?>
                    <div class="button-group erp-button-group">
                        <button  data-redirect="single_page" data-btn_status="save_and_submit_for_approval" type="button" class="button button-primary erp-ac-trns-form-submit-btn">
                            <?php _e( 'Submit for Approval', 'erp' ); ?>
                        </button>
                        <button type="button" class="button button-primary erp-dropdown-toggle" data-toggle="erp-dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                        </button>
                        <ul class="erp-dropdown-menu">
                            <li><a class="erp-ac-trns-form-submit-btn" data-redirect="single_page" data-btn_status="save_and_submit_for_approval" href="#"><?php _e( 'Submit for Approval', 'erp' ); ?></a></li>
                            <li><a class="erp-ac-trns-form-submit-btn" data-redirect="same_page" data-btn_status="approve_and_add_another" href="#"><?php _e( 'Submit for approval & add another', 'erp' ); ?></a></li>
                        </ul>
                    </div>
                    <?php
                } else if ( ( isset( $transaction['status'] ) && $transaction['status'] == 'awaiting_payment' ) ) {
                    ?>
                    <div class="button-group erp-button-group">
                        <button  data-redirect="single_page" data-btn_status="save_and_submit_for_payment" type="button" class="button button-primary erp-ac-trns-form-submit-btn">
                            <?php _e( 'Submit for Payment', 'erp' ); ?>
                        </button>
                        <button type="button" class="button button-primary erp-dropdown-toggle" data-toggle="erp-dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                        </button>
                        <ul class="erp-dropdown-menu">
                            <li><a class="erp-ac-trns-form-submit-btn" data-redirect="single_page" data-btn_status="save_and_submit_for_payment" href="#"><?php _e( 'Submit for Payment', 'erp' ); ?></a></li>
                            <li><a class="erp-ac-trns-form-submit-btn" data-redirect="same_page" data-btn_status="save_and_submit_for_payment" href="#"><?php _e( 'Submit for Payment & Add another', 'erp' ); ?></a></li>
                        </ul>
                    </div>
                    <?php
                }
                ?>

                <a href="<?php echo esc_url( $cancel_url ); ?>" class="button"><?php _e( 'Cancel', 'erp' ); ?></a>
            </div>

        </div>

    </form>
</div>
