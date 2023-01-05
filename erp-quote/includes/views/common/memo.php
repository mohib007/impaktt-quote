<ul class="form-fields erp-list no-style">

    <li class="erp-form-field">
        <?php
        if($_GET["type"] == "invoice"){
            $termandconditions = get_option('sales_quote_terms_and_condition');
        }else if($_GET["type"] == "expense"){
            $termandconditions = get_option('expense_quote_terms_and_condition');
        }else{
            $termandconditions = "";
        }

        erp_html_form_input( array(
            'label'       => __( 'Memo', 'erp' ),
            'name'        => 'summary',
            'placeholder' => __( 'Internal information', 'erp' ),
            'type'        => 'textarea',
            'value'       => isset( $transaction['summary'] ) ? $transaction['summary'] : $termandconditions,
            'custom_attr' => [
                'rows' => 3,
                'cols' => 45
            ]
        ) );
        ?>
    </li>

    <li class="erp-form-field">
        <?php
        erp_html_form_input( array(
            'label'       => __( 'Attachment', 'erp' ),
            'name'        => 'file',
            'id'          => 'erp-ac-upload-file',
            'type'        => 'file',
            'custom_attr' => [ 'transaction_id' => isset( $transaction['id'] ) ? intval( $transaction['id'] ) : 0 ],
            //'callback'    => [ 'after_uploaded' => 'test.afterImageUpload' ],
            'value'       => isset( $transaction['files'] ) ?  maybe_unserialize( $transaction['files'] ) : [],
        ) );
        ?>
    </li>
</ul>
