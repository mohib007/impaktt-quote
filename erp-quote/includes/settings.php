<?php
namespace IMPAKTT\ERP\QUOTE;
use WeDevs\ERP\Framework\ERP_Settings_Page;
/**
 * Settings class
 *
 * @since 1.0.0
 *
 * @package WPERP|QUOTE
 */
class QUOTE_Settings extends ERP_Settings_Page {
    /**
     * Constructor function
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->id            = 'erp-quote';
        $this->label         = __( 'QUOTE', 'erp-quote' );
        $this->single_option = true;
        $this->sections      = $this->get_sections();

    }

    /**
     * Get registered tabs
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_sections() {

        $sections = [
            'quote' => __( 'Order Settings', 'erp-quote' )
        ];


        return $sections;
    }


    /**
     * Get sections fields
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_section_fields( $section = '' ) {

		$statuses = wc_get_order_statuses();
		$departments = erp_hr_get_departments();
		$allDepartments = array();
	
		foreach($departments as $dept){
		 $allDepartments[$dept->id]  = $dept->name;
		}
		
			
		if ((!is_plugin_active('woocommerce/woocommerce.php'))){
			wp_die('Requires woocommerce plugin activated to create job orders.');
		}	
			

            $fields['quote'] = [
                [
                    'title' => __( '', 'erp-quote' ),
                    'type' => 'title',
                ]
			];

			
			$fields['quote'][] = [
				'title' => 'Status of order',
				'type' => 'select',
				'options' => $statuses,
				'id' => 'erp_quote_order_status',
				'desc' => __( 'Status of an order while creating, on the time of accepting quote.', 'erp-quote' ),
				'class' => 'erp-select2',
				'tooltip' => true,
				'default' => ''
			];

			$fields['quote'][] = [
				'title' => 'Create QUOTE Notification',
				'type' => 'select',
				'multiple' => 1,
				'options' => $allDepartments,
				'id' => 'erp_quote_create_quote',
				'desc' => __( '', 'erp-quote' ),
				'class' => 'erp-select2',
				'tooltip' => true,
				'default' => ''
			];
		
			$fields['quote'][] = [
				'title' => 'Won QUOTE Notification',
				'type' => 'select',
				'multiple' => 1,
				'options' => $allDepartments,
				'id' => 'erp_quote_won_quote',
				'desc' => __( '', 'erp-quote' ),
				'class' => 'erp-select2',
				'tooltip' => true,
				'default' => ''
			];
			
			$fields['quote'][] = [
				'title' => 'Ship QUOTE Notification to Accounts',
				'type' => 'checkbox',
				'id' => 'erp_quote_ship_quote',
				'desc' => __( '', 'erp-quote' ),
				'class' => '',
				'tooltip' => true,
				'default' => ''
			];

			$fields['quote'][] = [
				'title' => 'Update QUOTE Notification to Accounts',
				'type' => 'checkbox',
				'id' => 'erp_quote_update_quote',
				'desc' => __( '', 'erp-quote' ),
				'class' => '',
				'tooltip' => true,
				'default' => ''
			];

            $fields['quote'][] =
                [
                    'title'   => __( 'Sales Quote Terms and Conditions' ),
                    'type'    => 'textarea',
                    'id'      => 'sales_quote_terms_and_condition',
                    'desc'    => __( 'Add default Terms and condition for sales quote.', 'erp' ),
                ];
            $fields['quote'][] =
                [
                    'title'   => __( 'Purchase Requisition Terms and Conditions' ),
                    'type'    => 'textarea',
                    'id'      => 'expense_quote_terms_and_condition',
                    'desc'    => __( 'Add default Terms and condition for purchase requisition.', 'erp' ),
                ];

        $fields['quote'][] = [
                'type'  => 'sectionend',
                'id'    => 'erp_woocommerce_script_styling_options'
			];
			
			/* Payment option edited MSA*/
	


        return $fields[$section];
    }

 
}

// return new \WeDevs\ERP\WooCommerce\WooCommerce_Settings();

