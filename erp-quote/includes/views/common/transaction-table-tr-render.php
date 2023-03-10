<tr class="erp-ac-trn-row-wrap"> <?php
    $item = isset( $item ) ? $item : [];

    foreach ( erp_ac_tran_from_header() as $header_slug => $head ) {
        $row_class = 'col-' . $header_slug;


        switch ( $header_slug ) {
				//TECHCHEF START
		/**
			Production case sync with ERP WooCommerce 
		*/				
			case 'production':
                $get_post = get_post($item['product_id']);
				$checked = ($item['product_id']) ? 'checked="checked"' : '';
                ?>
                <td class="<?php echo $row_class." ".$get_post->post_title?>">
                    <input type="checkbox" class="product_order" name="product_order[]" <?= $checked; ?> >
                    <?php

                    erp_html_form_input( array(
                        'type'  => ($item['product_id']) ? 'text' : 'hidden',
                        'placeholder'  => 'product name',
                        'value' => isset( $get_post->post_title ) ? $get_post->post_title : '',
                        'class' => 'product_val'
                    ) );
                    erp_html_form_input( array(
                        'name'  => 'product_id[]',
                        'type'  => 'hidden',
                        'placeholder'  => 'product name',
                        'value' => isset( $item['product_id'] ) ? $item['product_id'] : '',
                        'class' => 'product_id'
                    ) );
                    ?>
                </td>

                <?php
                break;
				//TECHCHEF ENDS
            case 'account':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php echo $dropdown_html; ?>
                    </td>

                <?php
                break;
			
			case 'sales-account'
				?>			
					<td class="<?php echo $row_class; ?>">
					<?php echo $dropdown_html_sales; ?>
                    </td>
			<?php					
			break;		

            case 'description':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php
                        erp_html_form_input( array(
                            'name'  => 'line_desc[]',
                            'type'  => 'text',
                            'value' => isset( $item['description'] ) ? $item['description'] : '',
                            'custom_attr' => array( 'maxlength' => '40' )

                        ) );
                        ?>
                    </td>
                <?php
                break;

            case 'qty':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php
                        erp_html_form_input( array(
                            'name'        => 'line_qty[]',
                            'type'        => 'number',
                            //'placeholder' => 1,
                            'value'       => isset( $item['qty'] ) && $item['qty'] ? $item['qty'] : 1,
                            'class'       => 'line_qty',
                            'custom_attr' => [ 'min' => 1, 'step' => 'any' ]
                        ) );
                        ?>
                    </td>
                <?php
                break;

            case 'unit_price':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php
                        erp_html_form_input( array(
                            'name'        => 'line_unit_price[]',
                            'type'        => 'text',
                            'placeholder' => erp_ac_get_price_for_field( '0.00', ['symbol' => false] ),
                            'class'       => 'line_price',
                            'value'       => isset( $item['unit_price'] ) && ( $item['unit_price'] > 0 ) ? erp_ac_get_price_for_field( $item['unit_price'], ['symbol' => false] ) : '',
                            'custom_attr'    => [
                                'data-value' => isset( $item['unit_price'] ) ? erp_ac_get_price_for_field( $item['unit_price'], ['symbol' => false] ) : erp_ac_get_price_for_field( '0.00', ['symbol' => false] ),
                            ],
                            'required'    => true,

                           ) );
                        ?>
                    </td>
                <?php
                break;

            case 'tax':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php
                        erp_html_form_input( array(
                            'name'        => 'line_tax[]',
                            'type'        => 'select',
                            'class'       => 'erp-ac-tax-dropdown erp-ac-dropdown-destroy line_tax',
                            //'addon'     => '%',
                            //'addon_pos' => 'after',
                            'value'       => isset( $item['tax'] ) ? $item['tax'] : 0,
                            'options'     => ['-1' => __( '- Select -', 'erp' ) ] + erp_ac_get_tax_dropdown()

                        ) );
                        ?>
                        <?php
                        erp_html_form_input( array(
                            'name'        => 'tax_journal[]',
                            'type'        => 'hidden',
                            'value'       => isset( $item['tax_journal'] ) ? $item['tax_journal'] : 0,

                        ) );

                        erp_html_form_input( array(
                            'name'        => 'tax_amount[]',
                            'type'        => 'hidden',
                            'placeholder' => '0.00',
                            'class'       => 'line_tax_amount',
                            'value'       => isset( $item['tax_rate'] ) ? erp_ac_get_price_for_field( ( $item['tax_rate'] * $item['line_total'] ) /100, ['symbol' => false] ) : 0,
                            'custom_attr' => [
                                'readonly' => 'readonly'
                            ]
                        ));

                        erp_html_form_input( array(
                            'name'        => 'tax_rate[]',
                            'type'        => 'hidden',
                            'placeholder' => '0.00',
                            'class'       => 'line_tax_rate',
                            'value'       => isset( $item['tax_rate'] ) ? $item['tax_rate'] : '0.00',
                            'custom_attr' => [
                                'readonly' => 'readonly'
                            ]
                        ));						
                        ?>
                    </td>
                <?php
                break;

             case 'tax_amount':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php

                        ?>
                    </td>
                <?php
                break; 

            case 'amount':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <?php
                        erp_html_form_input( array(
                            'name'        => 'line_total[]',
                            'placeholder' => erp_ac_get_price_for_field( '0.00', ['symbol' => false] ),
                            'type'        => 'text',
                            'class'       => 'line_total',
                            'value'       => isset( $item['line_total'] ) ? erp_ac_get_price_for_field( $item['line_total'], ['symbol'=>false] ) : erp_ac_get_price_for_field( '0.00', ['symbol' => false] ),
                            'custom_attr' => [
                                'readonly' => 'readonly'
                            ]
                        ) );
                        ?>

                        <?php
                        erp_html_form_input( array(
                            'name'  => 'journals_id[]',
                            'type'  => 'hidden',
                            'value' => isset( $journal['id'] ) ? $journal['id'] : ''
                        ) );
                        ?>

                        <?php
                        erp_html_form_input( array(
                            'name'  => 'items_id[]',
                            'type'  => 'hidden',
                            'value' => isset( $item['id'] ) ? $item['id'] : ''
                        ) );
                        ?>

                    </td>
                <?php
                break;

            case 'action':
                ?>
                    <td class="<?php echo $row_class; ?>">
                        <a href="#" class="remove-line"><span class="dashicons dashicons-trash"></span></a>
                        <!-- <a href="#" class="move-line"><span class="dashicons dashicons-menu"></span></a> -->
                    </td>
                <?php
                break;

            default:
                ?><td><?php do_action( 'erp_ac_trans_form_body_view', $head, $header_slug, $item ); ?></td><?php
                break;
        }
    }

?>

</tr>
