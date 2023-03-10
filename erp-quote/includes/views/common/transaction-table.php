<table class="widefat erp-ac-transaction-table payment-voucher-table erp-ac-transaction-form-table">
    <thead>
        <tr>
            <?php
            foreach ( erp_ac_tran_from_header() as $header_slug => $head ) {
                ?><th class="<?php echo 'col-' . $header_slug; ?>"><?php echo $head ?></th><?php

            }
            ?>
        </tr>
    </thead>

    <tbody>
        <?php
        $lilne_total         = 0;
        $journals            = [];
        $Sjournals            = [];
        $jor_itms['journal'] = isset( $jor_itms['journal'] ) ? $jor_itms['journal'] : [];
        $jor_itms['line_account'] = isset( $jor_itms['line_account'] ) ? $jor_itms['line_account'] : [];


        foreach ( $jor_itms['journal'] as  $journal ) {
            $journals[$journal['id']] = $journal;
        }
		foreach ( $jor_itms['line_account'] as $key => $Sjournal ) {

			if(!is_array($Sjournal['line_account'])){
				$Sjournals[$Sjournal['journal_id']] = $Sjournal['line_account'];
			}
        }


        if ( isset( $jor_itms['item'] ) ) {
            $lilne_total = abs( $jor_itms['main']['debit'] - $jor_itms['main']['credit'] );

            foreach (  $jor_itms['item'] as $key => $item  ) {
				if($form_type == "expense"){
					$dropdown_html = erp_ac_render_account_dropdown_html( $dropdown, array(
						'name'     => 'line_account[]',
						'selected' => isset( $item['journal_id'] ) ? $item['journal_id'] : false,
						'class'    => 'erp-select2 erp-ac-account-dropdown'
					) );
				}else{
					$dropdown_html = erp_ac_render_account_dropdown_html( $dropdown_sales, array(
						'name'     => 'line_account[]',
						'selected' => isset( $item['journal_id'] ) ? $item['journal_id'] : 0,
						'class'    => 'erp-select2 erp-ac-sale-account-dropdown'
					) );				
				}
				

                include dirname( __FILE__ ) . '/transaction-table-tr-render.php';
            }

        } else {

            for ($i = 0; $i < 1; $i++) {
                include dirname( __FILE__ ) . '/transaction-table-tr-render.php';
            }
        }

        $sub_total = isset( $transaction['sub_total'] ) ? erp_ac_get_price_for_field( $transaction['sub_total'], ['symbol' => false] ) : '0.00';
        $line_total = isset( $transaction['total'] ) ? erp_ac_get_price_for_field( $transaction['total'], ['symbol' => false] ) : '0.00';

        ?>
    </tbody>
    <tfoot>
        <?php
        $amount_position = array_search( 'amount', array_keys( erp_ac_tran_from_header() ) );
        foreach ( $tax_labels as $tax_id => $tax_label ) {
            ?>

            <tr  class="erp-ac-tr-wrap">
                <th colspan="<?php echo $amount_position; ?>" class="align-right">
                    <span class="erp-ac-tax-text"><?php echo $tax_label['label'] ;?></span>
                </th>
                <th class="col-amount">
                    <input type="text" name="tax_total" data-tax_id="<?php echo $tax_id; ?>" class="erp-ac-tax-total price-total" readonly value="<?php echo erp_ac_get_price_for_field( $tax_label['total_amount'], ['symbol' => false] ); ?>">
                </th>
                <th>&nbsp;</th>
            </tr>
            <?php
        }

        ?>
        <tr class="erp-ac-price-total-wrap">
            <th colspan="<?php echo $amount_position; ?>" class="align-right col_adjust_sub"><?php _e( 'Subtotal', 'erp' ); ?></th>
            <th class="col-amount">
                <input type="text" name="sub_total" placeholder="0.00" class="sub-total" readonly value="<?php echo $sub_total; ?>">
            </th>
            <th>&nbsp;</th>
        </tr>
        <tr class="">
            <th><a href="#" class="button add-line"><?php _e( '+ Add Line', 'erp' ); ?></a></th>
            <th colspan="<?php echo $amount_position - 1; ?>" class="align-right col_adjust_total"><?php _e( 'Total', 'erp' ); ?></th>
            <th class="col-amount">
                <input type="text" name="price_total" class="price-total" readonly value="<?php echo erp_ac_get_price_for_field( $line_total, ['symbol'=>false] ); ?>">
            </th>
            <th>&nbsp;</th>
        </tr>
    </tfoot>
</table>

<table id="erp-ac-hidden-tax-table" style="display: none;">
    <tr data-tax_id="" class="erp-ac-tr-wrap">
        <th colspan="<?php echo $amount_position; ?>" class="align-right">
            <span class="erp-ac-tax-text"></span>
        </th>
        <th class="col-amount">
            <input type="text" name="tax_total" data-tax_id="" class="erp-ac-tax-total" readonly value="">
        </th>
        <th>&nbsp;</th>
    </tr>
</table>
