<div class="wrap erp-accounting">
    <div id="erp-accounting">
    <?php
    if ( erp_ac_create_sales_payment() || erp_ac_create_sales_invoice() ) {
        ?>
        <h2>
            <?php _e( 'Quote Transactions', 'erp' ); ?>

            <?php
			$form_types = [
					'invoice' => [
						'name'        => 'invoice',
						'label'       => __( 'Sale Quote', 'erp' ),
						'description' => __( 'Add sale quote', 'erp' ),
						'type'        => 'debit'
					],
					'expense' => [
						'name'        => 'expense',
						'label'       => __( 'Purchase Order', 'erp' ),
						'description' => __( 'Add Purchase Order', 'erp' ),
						'type'        => 'credit'
					],					
				];

            if ( $form_types ) {
                foreach ($form_types as $key => $form) {
                    do_action( 'erp_ac_invoice_transaction_action', $key, $form );
                    if ( 'invoice' == $key && ( erp_ac_create_sales_invoice() || erp_ac_publish_sales_invoice() ) ) {
                        printf( '<a class="add-new-h2" href="%s%s" title="%s">%s</a> ', admin_url( 'admin.php?page=erp-quote&action=new&type=' ), $key, esc_attr( $form['description'] ), $form['label'] );
                    } 
					else if ( 'expense' == $key && ( erp_ac_create_sales_invoice() || erp_ac_publish_sales_invoice() ) ) {
                        printf( '<a class="add-new-h2" href="%s%s" title="%s">%s</a> ', admin_url( 'admin.php?page=erp-quote&action=new&type=' ), $key, esc_attr( $form['description'] ), $form['label'] );
                    }
					else {
                        do_action( 'erp_ac_invoice_transaction_after_action', $key, $form );
                    }
                }
            }
            ?>
        </h2>

        <?php
    }

    if ( erp_ac_view_sales_summary() ) {
        include_once dirname( dirname( __FILE__ ) ) . '/views/common/transaction-chart.php';
    }
    ?>

    <form method="get" class="erp-accounting-tbl-form">
        <input type="hidden" name="page" value="erp-quote">

        <?php
        $list_table = new WeDevs\ERP\Accounting\QUOTE_Transaction_List_Table();
        $list_table->prepare_items();
        $list_table->views();
        $list_table->display();
        ?>
    </form>
    </div>
</div>