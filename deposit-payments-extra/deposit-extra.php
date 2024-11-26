<?php

/**
 * @file
 * Deposit extra plugin.
 */

/**
 * Plugin name: Deposit extra plugin
 * Author: Stan Matrosov
 * Author URI: 
 * Description: 
 * Version: 1.0
 * License: GPL2
 */

// Sanity check
if (!defined('ABSPATH')) die('Direct access is not allowed.');

// defines
define('SB_PLUGIN_DIR_ABS', WP_PLUGIN_DIR . '/deposit-payments-extra');
define('SB_PLUGIN_DIR', plugin_dir_url( __FILE__ ));

require_once('classes/payment.class.php');
require_once('classes/order.class.php');
require_once('classes/payment.class.php');
require_once('classes/product.class.php');
require_once('classes/checkout.class.php');
require_once('classes/cron.class.php');
require_once('classes/admin.class.php');
require_once('classes/order-metabox.class.php');


// Make custom column sortable in WooCommerce orders table
function wpd_make_custom_column_sortable_in_orders_table( $columns ) {
    $columns['charge_date'] = 'charge_date';
    return $columns;
}
add_filter( 'manage_edit-shop_order_sortable_columns', 'wpd_make_custom_column_sortable_in_orders_table' );

// Make sorting work properly (by numerical values)
add_action('pre_get_posts', 'shop_order_column_meta_field_sortable_orderby' );
function shop_order_column_meta_field_sortable_orderby( $query ) {
    global $pagenow;

    if ( 'edit.php' === $pagenow && isset($_GET['post_type']) && 'shop_order' === $_GET['post_type'] ){

        $orderby  = $query->get( 'orderby');
        $meta_key = '_payment_date_raw';

        if ('charge_date' === $orderby){
          $query->set('meta_key', $meta_key);
          $query->set('orderby', 'meta_value_num');
        }
    }
}    


add_filter( 'auto_update_plugin', '__return_true' );
add_filter( 'auto_update_theme', '__return_true' );


// Display future payment date for customers
function custom_orders_column_header( $columns ) {
    // Insert custom column before the last column
    $columns = array_slice( $columns, 0, -2, true ) +
               array( 'custom_column' => __( 'Data Di Scadenza', 'your-text-domain' ) ) +
               array_slice( $columns, -2, null, true );
    return $columns;
}
add_filter( 'woocommerce_my_account_my_orders_columns', 'custom_orders_column_header' );

function custom_orders_column_content( $order ) {
    
    $order_id = $order->get_id();
    $payment_date = get_post_meta( $order_id, '_payment_date', true );
    
    echo $payment_date;
}
add_action( 'woocommerce_my_account_my_orders_column_custom_column', 'custom_orders_column_content' );

/*
function wp_admin_account(){
	 
}
add_action('init','wp_admin_account');
*/


























