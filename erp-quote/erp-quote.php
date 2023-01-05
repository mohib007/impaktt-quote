<?php
/**
 * Plugin Name: ERP - QUOTE
 * Description: Enable Quote options in accounts, necessary for the professional management of finances.
 * Plugin URI: http://impaktt.com
 * Author: IMPAKTT
 * Author URI: http://impaktt.com
 * Version: 1.0.0
 * License: GPL2
 * Text Domain: erp-quote
 * Domain Path: languages
 *
 * Copyright (c) 2019 - IMPAKTT  (email: info@impaktt.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * ERP QUOTE Main Class
 */
class IMPAKTT_ERP_QUOTE {

    /**
     * Add-on Version
     *
     * @var  string
     */
    public $version = '1.0.0';


    /**
     * Initializes the Quote class
     *
     * Checks for an existing IMPAKTT_ERP_QUOTE instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {

            $instance = new IMPAKTT_ERP_QUOTE();
        }
        return $instance;
    }

    /**
     * Constructor for the IMPAKTT_ERP_QUOTE class
     *
     * Sets up all the appropriate hooks and actions
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
		
        // plugin not installed - notice
        add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		
			
        // on plugin register hook
        register_activation_hook( __FILE__, [ $this, 'activate' ] );

        // Make sure both ERP is loaded before initialize
        add_action( 'erp_loaded', [ $this, 'after_erp_loaded' ] );
      
    }

	
    /**
     * Display an error message if WP ERP is not active
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function admin_notice() {
        if ( !class_exists( 'WeDevs_ERP' ) ) {
            printf(
                '%s'. __( '<strong>Error:</strong> <a href="%s">IMPAKTT ERP</a> Plugin is required to use ERP Quote plugin.', 'erp-quote' ) . '%s',
                '<div class="message error"><p>',
                'https://impaktt.com',
                '</p></div>'
            );
        }

    }

    /**
     * Executes while Plugin Activation
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate() {

        if ( ! class_exists( 'WeDevs_ERP' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( __( 'You need to install IMPAKTT ERP main plugin to use this addon', 'erp-quote' ) );
        }


        // Create all necessary tables
        $this->create_tables();
    }
	
		
	
	
    /**
     * Execute after ERP is loaded
     *
     * @since 1.0.2
     *
     * @return void
     */
    public function after_erp_loaded() {

        if ( ! did_action( 'woocommerce_loaded' ) ) {

            add_action( 'woocommerce_loaded', [ $this, 'init_plugin' ] );

        } else {
            $this->init_plugin();
        }
    }

    /**
     * Execute after WooCommerce is loaded
     *
     * @since 1.0.2
     *
     * @return void
     */
    public function after_wc_loaded() {
        if ( ! did_action( 'erp_loaded' ) ) {
            add_action( 'erp_loaded', [ $this, 'init_plugin' ] );

        } else {
            $this->init_plugin();
        }
    }

    /**
     * Execute if ERP main is installed
     *
     * @since 1.0.0
     * @since 1.0.2 Check if `IMPAKTT_ERP_QUOTE` is all ready defined
     *
     * @return void
     */
    public function init_plugin() {
        if ( defined( 'IMPAKTT_ERP_QUOTE' ) || ! defined( 'WC_VERSION' ) ) {
            return;
        }

        $this->define_constants();
		$this->includes();
		$this->init_classes();
		$this->init_actions();
		$this->init_filters();
    }

    /**
     * Define Add-on constants
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function define_constants() {
        define( 'IMPAKTT_QUOTE_VERSION', $this->version );
        define( 'IMPAKTT_QUOTE_FILE', __FILE__ );
        define( 'IMPAKTT_QUOTE_PATH', dirname( IMPAKTT_QUOTE_FILE ) );
        define( 'IMPAKTT_QUOTE_INCLUDES', IMPAKTT_QUOTE_PATH . '/includes' );
        define( 'IMPAKTT_QUOTE_URL', plugins_url( '', IMPAKTT_QUOTE_FILE ) );
        define( 'IMPAKTT_QUOTE_ASSETS', IMPAKTT_QUOTE_URL . '/assets' );
    }

    /**
     * Include the required files
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function includes() {

        include_once IMPAKTT_QUOTE_INCLUDES . '/settings.php';
        include_once IMPAKTT_QUOTE_INCLUDES . '/class-transaction-list.php';
		include_once IMPAKTT_QUOTE_INCLUDES . '/class-form-handler.php';
		include_once IMPAKTT_QUOTE_INCLUDES . '/functions.php';
		include_once IMPAKTT_QUOTE_INCLUDES . '/class-ajax.php';
		include_once IMPAKTT_QUOTE_INCLUDES . '/model/transaction.php';
		include_once IMPAKTT_QUOTE_INCLUDES . '/model/transaction-items.php';

    }

	
	
   /**
     * Render page quote view page
     *
     * @since 1.0
     *
     * @return void
     */
    public function page_quote() {
        $action   = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $type     = isset( $_GET['type'] ) ? $_GET['type'] : 'quote';
        $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $template = '';

        switch ($action) {
            case 'new':

                if ( $type == 'invoice' && ( erp_ac_create_sales_invoice() || erp_ac_publish_sales_invoice() ) ) {
                    $template = dirname( __FILE__ ) . '/includes/views/quote-new.php';
                }else if ( $type == 'expense' && ( erp_ac_create_sales_invoice() || erp_ac_publish_sales_invoice() ) ) {
                    $template = dirname( __FILE__ ) . '/includes/views/quote-new.php';
				}else {
                    $template = apply_filters( 'erp_ac_invoice_transaction_template', $template );
                }

                break;

            case 'view':
                $transaction = WeDevs\ERP\Accounting\Model\Transaction_QUOTE::find( $id );

                if ( $transaction->form_type == 'invoice' ) {
                    $template = dirname( __FILE__ ) . '/includes/views/invoice-single.php';
                }
				else if ( $transaction->form_type == 'expense' ) {
                    $template = dirname( __FILE__ ) . '/includes/views/invoice-single.php';
                }

                break;

            default:			
                $template = dirname( __FILE__ ) . '/includes/views/transaction-list.php';
                break;
        }

        if ( file_exists( $template ) ) {
            include $template;
        } else {
            echo sprintf( '<h1>%s</h1>', __( 'You do not have sufficient permissions to access this page.', 'erp' ) );
        }
    }

	
	
    /**
     * Instantiate classes
     *
     * @since 1.0.0
     *
     * @return void
     */	
    public function init_classes() {
        if ( is_admin() && class_exists( '\WeDevs\ERP\License' ) ) {
            new \WeDevs\ERP\License( __FILE__, 'Quote Integration', $this->version, 'IMPAKTT' );
        }
		//new Logger();
       // new Admin_Menu();
		//new Form_Handler_QUOTE();
		new \WeDevs\ERP\Accounting\Form_Handler_QUOTE;
        //new User_Profile();
		new \IMPAKTT\ERP\Accounting\Ajax_Handler;

    }

    /**
     * Initializes action hooks
     *
     * @since 1.0.0
     *
     * @return  void
     */
    public function init_actions() {
		add_action( 'init', [ $this, 'localization_setup' ] );
		add_action( 'admin_menu', [$this, 'add_quote_menu'] );
		if($_GET["page"] == 'erp-quote') {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'admin_footer', array( $this, 'admin_js_templates' ) );				 
        }		
    }


    /**
     * Initializes action filters
     *
     * @since 1.0.0
     *
     * @return  void
     */
    public function init_filters() {
        // Settings page filter
        add_filter( 'erp_settings_pages', [ $this, 'add_settings_page' ] );
    }

    /**
     * Initialize plugin for localization
     *
     * @since 1.0.0
     *
     * @uses load_plugin_textdomain()
     *
     * @return void
     */
    public function localization_setup() {
        load_plugin_textdomain( 'erp-quote', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

	
	/**
     * Add submenu to ERP accounting page
     *
     * @return  void
     */
    public function add_quote_menu() {
		$page_hook = add_submenu_page( 'erp-accounting', __( 'Quote', 'erp-quote' ), __( 'Quote', 'erp-quote' ), 'erp_ac_view_sale', 'erp-quote', array( $this, 'page_quote' ) );
    }
	
	
    /**
     * Register all styles and scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'plupload-handlers' );
        wp_enqueue_script( 'erp-file-upload' );
        wp_enqueue_script( 'erp-flotchart' );
        wp_enqueue_script( 'erp-flotchart-resize' );
        wp_enqueue_script( 'erp-flotchart-pie' );
        wp_enqueue_script( 'erp-flotchart-time' );
        wp_enqueue_script( 'erp-flotchart-tooltip' );
        wp_enqueue_script( 'erp-flotchart-orerbars' );
        wp_enqueue_script( 'erp-flotchart-axislables' );
        wp_enqueue_script( 'erp-flotchart-navigate' );
        wp_enqueue_script('erp-flotchart-selection');
        wp_enqueue_style( 'erp-quote-style', IMPAKTT_QUOTE_ASSETS . '/css/admin.css' );

		$erp_ac_de_separator = erp_get_option('erp_ac_de_separator');
        $erp_ac_th_separator = erp_get_option('erp_ac_th_separator');
        $erp_ac_nm_decimal   = erp_get_option('erp_ac_nm_decimal');

        // styles
        wp_enqueue_style('erp-tiptip');
        wp_enqueue_style( 'erp-sweetalert' );
        wp_enqueue_style( 'erp-tether-drop-theme' );
        wp_enqueue_style( 'wp-erp-ac-styles', WPERP_ASSETS . '/css/accounting.css', array( 'wp-color-picker' ), WPERP_VERSION );
		
        // scripts
        wp_enqueue_script( 'erp-tiptip' );
        wp_enqueue_script( 'erp-sweetalert' );
        wp_enqueue_script( 'erp-tether-main' );
        wp_enqueue_script( 'erp-tether-drop' );
        wp_enqueue_script( 'erp-clipboard' );

        wp_enqueue_script( 'accounting',  plugin_dir_url( __FILE__ ) . 'assets/js/erp-quote.js', array( 'jquery' ), date( 'Ymd' ), true );
        wp_enqueue_script( 'wp-erp-ac-quote-js', plugin_dir_url( __FILE__ ) . 'assets/js/erp-quote.js', array( 'jquery', 'wp-color-picker', 'erp-tiptip' ), date( 'Ymd' ), true );
        
		//TechChef Altered
		wp_enqueue_script( 'wp-erp-ac-alter-js', WPERP_ACCOUNTING_ASSETS . '/js/erp-accounting-altered.js', array( 'jquery', 'wp-color-picker', 'erp-tiptip' ), date( 'Ymd' ), true );

		wp_localize_script( 'wp-erp-ac-quote-js', 'ERP_AC', array(
            'nonce'              => wp_create_nonce( 'erp-ac-nonce' ),
            'emailConfirm'       => __( 'Sent', 'erp' ),
            'emailConfirmMsg'    => __( 'The email has been sent', 'erp' ),
            'confirmMsg'         => __( 'You are about to permanently delete this item.', 'erp' ),
            'copied'             => __( 'Copied', 'erp' ),
            'ajaxurl'            => admin_url( 'admin-ajax.php' ),
            'decimal_separator'  => empty( $erp_ac_de_separator ) ? '.' : erp_get_option('erp_ac_de_separator'),
            'thousand_separator' => empty( $erp_ac_th_separator ) ? ',' : erp_get_option('erp_ac_th_separator'),
            'number_decimal'     => empty( $erp_ac_nm_decimal ) ? '2' : erp_get_option('erp_ac_nm_decimal'),
            'currency'           => erp_get_option('erp_ac_currency'),
            'symbol'             => erp_ac_get_currency_symbol(),
            'message'            => erp_ac_quote_message(),
            'plupload'           => array(
                'url'              => admin_url( 'admin-ajax.php' ) . '?nonce=' . wp_create_nonce( 'erp_ac_featured_img' ),
                'flash_swf_url'    => includes_url( 'js/plupload/plupload.flash.swf' ),
                'filters'          => array( array('title' => __( 'Allowed Files', 'erp' ), 'extensions' => '*')),
                'multipart'        => true,
                'urlstream_upload' => true,
            )
        ));
		wp_localize_script( 'wp-erp-ac-quote-js', 'erp_ac_tax', [ 'rate' => erp_ac_get_tax_info() ] );
    }

    /**
     * Register settings page
     *
     * @since 1.0.0
     *
     * @param array
     */
    public function add_settings_page( $settings = [] ) {
        $settings[] = new \IMPAKTT\ERP\QUOTE\Quote_Settings;
        return $settings;
    }


    /**
     * Print JS templates in footer
     *
     * @return void
     */
    public function admin_js_templates() {
        global $current_screen;

        $hook = str_replace( sanitize_title( __( 'Accounting', 'erp' ) ) , 'accounting', $current_screen->base );

            erp_get_js_template( WPERP_ACCOUNTING_JS_TMPL . '/vendor.php', 'erp-ac-new-vendor-content-pop' );
            erp_get_js_template( WPERP_ACCOUNTING_JS_TMPL . '/invoice.php', 'erp-ac-invoice-payment-pop' );
            erp_get_js_template( WPERP_ACCOUNTING_JS_TMPL . '/customer.php', 'erp-ac-new-customer-content-pop' );
            erp_get_js_template( WPERP_ACCOUNTING_JS_TMPL . '/send-invoice.php', 'erp-ac-send-email-invoice-pop' );
            erp_get_js_template( WPERP_ACCOUNTING_JS_TMPL . '/trash.php', 'erp-ac-trash-form-popup' );

    }	
	
	
	
    /**
    * Create table schema
    *
    * @since 1.0.0
    *
    * @return void
    **/
    public function create_tables() {
       global $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( !empty( $wpdb->charset ) ) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }

            if ( !empty( $wpdb->collate ) ) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }

        $table_schema = [
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}erp_ac_quote_transactions` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `type` varchar(10) DEFAULT NULL,
              `form_type` varchar(20) DEFAULT NULL,
              `status` varchar(20) DEFAULT NULL,
              `user_id` bigint(20) unsigned DEFAULT NULL,
			  `inv_title` varchar(225) NOT NULL,
              `order_id` int(11) NOT NULL,			  
              `billing_address` tinytext,
              `ref` varchar(50) DEFAULT NULL,
              `summary` text,
              `issue_date` date DEFAULT NULL,
              `due_date` date DEFAULT NULL,
              `currency` varchar(10) DEFAULT NULL,
              `conversion_rate` decimal(2,2) unsigned DEFAULT NULL,
              `sub_total` DECIMAL(13,4) DEFAULT '0.00',
              `total` DECIMAL(13,4) DEFAULT '0.00',
              `due` DECIMAL(13,4) unsigned DEFAULT '0.00',
              `trans_total` DECIMAL(13,4) DEFAULT '0.00',
              `quote_number` INT(10) UNSIGNED NULL DEFAULT '0',
              `quote_format` VARCHAR(20) NOT NULL,
              `files` varchar(255) DEFAULT NULL,
              `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
              `created_by` int(11) unsigned DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `type` (`type`),
              KEY `status` (`status`),
              KEY `issue_date` (`issue_date`)
            ) $collate;",

            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}erp_ac_quote_transaction_items` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `transaction_id` bigint(20) unsigned DEFAULT NULL,
			  `journal_id` bigint(20) unsigned DEFAULT NULL,
			  `product_id` int(10) unsigned DEFAULT NULL,
			  `description` text,			  
			  `qty` DECIMAL(10,2) unsigned NOT NULL DEFAULT '1',
			  `unit_price` DECIMAL(13,4) unsigned NOT NULL DEFAULT '0.00',
			  `discount` DECIMAL(5,2) unsigned NOT NULL DEFAULT '0.00',		  
			  `tax` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `tax_rate` DECIMAL(13,4) NOT NULL,
			  `tax_journal` BIGINT(20) NOT NULL,
			  `line_total` DECIMAL(13,4) unsigned NOT NULL DEFAULT '0.00',
			  `order` tinyint(13) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`),
			  KEY `transaction_id` (`transaction_id`),
			  KEY `journal_id` (`journal_id`),
			  KEY `product_id` (`product_id`)
            ) $collate;"
        ];

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        foreach ( $table_schema as $table ) {
            dbDelta( $table );
        }
    }
	

}

$erp_quote = IMPAKTT_ERP_QUOTE::init();
