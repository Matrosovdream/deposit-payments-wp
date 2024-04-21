<?php
Class Deposit_extra_checkout {

    public function __construct() {

        // Single product 
        add_action('woocommerce_before_add_to_cart_button', array($this, 'woocommerce_before_add_to_cart_form_func'));

        // Checkout form
        add_action( 'woocommerce_before_calculate_totals', array($this, 'add_custom_item_price'), 5 );
        add_action( 'woocommerce_checkout_update_order_meta', array($this, 'add_custom_meta_to_order'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_custom_hiden_order_item_meta_data'), 20, 4 );
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'plugin_republic_add_cart_item_data'), 10, 3 );
        add_filter( 'woocommerce_get_item_data', array($this, 'iconic_display_engraving_text_cart'), 10, 2 );
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'filter_wc_order_item_display_meta_key'), 20, 3 );
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'filter_wc_order_item_display_meta_value'), 20, 3 );
        add_action( 'wp_footer', array($this, 'footer_checkout_js') );
        add_filter('gettext', array($this, 'change_save_to_account_text'), 100, 3 );
        //add_filter( 'wc_stripe_force_save_source', '__return_true' ); // No need for now
        
        // Admin order's list
        add_filter( 'manage_edit-shop_order_columns', array($this, 'custom_shop_order_column'), 20 );
        add_action( 'manage_shop_order_posts_custom_column' , array($this, 'custom_orders_list_column_content'), 20, 2 );

        // Admin order page
        add_action('add_meta_boxes', array($this, 'suborders_meta_box'));
        

    }

    function woocommerce_before_add_to_cart_form_func() {

        global $post;
    
        $plans = get_field('plans', $post->ID);
        $test_mode = get_field('test_mode', 'options');

        // Test mode, visible just for admins/managers
        if( current_user_can('editor') || current_user_can('administrator') ) { $admin = true; }
        if( $test_mode && !$admin ) { return false; }

        if( is_array($plans) && count( $plans ) > 0 ) { 
                
            echo "<p>".__( 'Select a plan', 'deposit-payments-extra')."</p>";
            echo "<select name='selected_plan' style='width: 100%;'>";
            foreach( $plans as  $plan ) {
                echo "<option value='".$plan->ID."'>".$plan->post_title."</option>";
            }
            echo "</select>";

        }
    
    }

    function plugin_republic_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        if( isset( $_POST['selected_plan'] ) ) {
            $cart_item_data['selected_plan'] = sanitize_text_field( $_POST['selected_plan'] );
        }
        return $cart_item_data;
    }

    function add_custom_item_price( $cart_object ) {

        $prod_extra = new Deposit_extra_product;
    
        foreach ( $cart_object->get_cart() as $item_values ) {
    
            $selected_plan = $item_values['selected_plan'];
    
            if( $selected_plan ) {
    
                $installment = $prod_extra->get_installment( $selected_plan );
    
                $product_id = $item_values['data']->get_id();
                $product = wc_get_product( $product_id );
                $original_price = $product->get_price();
    
                if( $installment['type'] == 'full_payment' && isset( $installment['discount'] ) ) {
    
                    $discount_price = $original_price - ($original_price * $installment['discount'] / 100);
                    $item_values['data']->set_price( $discount_price );
    
                }
    
                if( $installment['type'] == 'installments' && isset( $installment['downpayment'] ) ) {
    
                    $discount_price = $original_price * $installment['downpayment'] / 100;
                    $item_values['data']->set_price( $discount_price );
    
                }
    
                $item_values['data']->add_meta_data('selected_plan', $selected_plan, true);
                $item_values['data']->add_meta_data('installment_exists', true, true);
                $item_values['data']->add_meta_data('installment_data', json_encode($installment), true);
                $item_values['data']->add_meta_data('product_full_price', $original_price, true);
    
            }
    
        }
    
        return $cart_object;
    
    }

    function add_custom_meta_to_order( $order_id, $data ) {

        $order = wc_get_order($order_id);
        
        foreach ( WC()->cart->get_cart() as $cart_item ) {
          if ( isset($cart_item['data']) ) {
            $product = $cart_item['data'];
            
            if ( $product->get_meta('installment_exists') ) {
    
                $meta_save = array("selected_plan", "installment_exists", "installment_data", "product_full_price");
    
                foreach( $meta_save as $key ) {
                    $val = $product->get_meta($key, true);
                    $order->add_meta_data($key, $val, true);
                }
    
                $order->save();
                break;
            }
          }
        }
    
    }

    function iconic_display_engraving_text_cart( $item_data, $cart_item ) {

        $sel_plan = $cart_item['selected_plan'];

        if( $sel_plan ) {

            $post = get_post( $sel_plan );

            $item_data[] = array(
                'key'     => __( 'Selected plan', 'deposit-payments-extra'),
                'value'   => $post->post_title,
                'display' => '',
            );


            $prod = new Deposit_extra_product;
            $prod->calculate_payment_dates( $sel_plan );


        }

        return $item_data;

    }

    function add_custom_hiden_order_item_meta_data( $item, $cart_item_key, $values, $order ) {

        $product = $values['data'];
    
        echo $product->get_meta('selected_plan');
            
        if ( $product->get_meta('installment_exists') ) {
    
            $meta_save = array("selected_plan", /*"installment_exists", "installment_data",*/ "product_full_price");
    
            foreach( $meta_save as $key ) {
                $item->update_meta_data( $key, $product->get_meta( $key ) );
            }
    
        }
    
    }

    function filter_wc_order_item_display_meta_key( $display_key, $meta, $item ) {
    
        // Set user meta custom field as order item meta
        if( $meta->key === 'selected_plan' )
            $display_key = __( 'Selected plan', 'deposit-payments-extra');
    
        if( $meta->key === 'product_full_price' )
            $display_key = __( 'Full price', 'deposit-payments-extra');    
    
        return $display_key;    
    }

    function filter_wc_order_item_display_meta_value( $meta_value, $meta, $item ) {
    
        if( $meta->key === 'selected_plan' ) { 
            $post = get_post( $meta_value );
            return $post->post_title;
        }
    
        if( $meta->key === 'product_full_price' ) { 
            return wc_price( $meta_value );
        }
    
        return $meta_value;    
    }
    
    // Making new payment save checkbox checked by default
    function footer_checkout_js() {

        echo "
            <script>
                jQuery('#wc-stripe-new-payment-method').prop('checked', true);
            </script>
            ";

    }

    function change_save_to_account_text( $translated_text, $text, $domain ) {
        if( $text === 'Save payment information to my account for future purchases.' && $domain == 'woocommerce' && is_checkout() )
        {
            $translated_text = __(
                'By providing your card details, you allow Self Awareness Institute to charge your card for future payments 
                in accordance with the relevant terms and conditions.', $domain );
        }
        return $translated_text;
    }

    function custom_shop_order_column($columns) {
        $reordered_columns = array();

        foreach( $columns as $key => $column){
            $reordered_columns[$key] = $column;
            if( $key ==  'order_status' ){

                $reordered_columns['charge_date'] = __( 'Charge date', 'deposit-payments-extra');
            }
        }
        return $reordered_columns;
    }

    function custom_orders_list_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'charge_date' :
                $val = get_post_meta( $post_id, '_payment_date', true );
                if(!empty($val))
                    echo date('d/m/Y', strtotime($val));

                break;

        }
    }

    function suborders_meta_box() {
        add_meta_box( 'suborders', 'Deposit Sub-orders', array($this, 'suborders_metabox_callback'), 'shop_order', 'normal', 'high', $callback_args );
    }
    
    function suborders_metabox_callback( $post, $metabox ) {
    
        $order = wc_get_order( $post->ID );
        $sub_orders = $order->get_meta('_deposit_orders');
    
        foreach( $sub_orders as $order_id ) {
            echo "
            <p>
                <a href='/wp-admin/post.php?post=".$order_id."&action=edit'>#".$order_id."</a>
            </p>
            ";
        }
    
    }
    
    


}

new Deposit_extra_checkout();