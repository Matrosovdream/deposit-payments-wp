<?php
Class Deposit_extra_order {

    public function __construct() {

        //add_action( 'woocommerce_order_status_completed', array($this, 'woocommerce_new_order_func') );
        add_action( 'woocommerce_thankyou', array($this, 'woocommerce_new_order_func') );

    }


    public function woocommerce_new_order_func( $order_id ) {

        $productClass = new Deposit_extra_product;

        $parent_order_id = $order_id;
        $order = wc_get_order( $parent_order_id );

        $order->update_meta_data('_deposit_orders_created', false);

        if( $order->get_meta('_deposit_orders_created') ) { return false; }

        $currency = $order->get_currency();
        $items = $order->get_items();

        foreach( $items as $product ) {

            $selected_plan = $product->get_meta('selected_plan');
            $product_full_price = $product->get_meta('product_full_price');
            $product_id = $product->get_product_id();

            $installment = $productClass->get_installment( $selected_plan );

            /*
            echo "<pre>";
            print_r($installment);
            echo "</pre>";
            die();
            */

            if( is_array($installment['payments']) && count( $installment['payments'] ) > 0 ) {

                foreach( $installment['payments'] as $key=>$payment_plan ) {

                    $count = $key+1;
                    $orders[] = $this->create_order_by_plan( $payment_plan, $product_full_price, $parent_order_id, $product_id, $count );

                }

            }


        } 

        if( is_countable( $orders ) > 0 ) {
            $order->add_meta_data('_deposit_orders_created', true);
            $order->add_meta_data('_deposit_orders', $orders);
            $order->save();
        }
        

    }


    public function create_order_by_plan( $payment, $product_full_price, $parent_order_id, $product_id, $installment_number ) {

        $parent_order = wc_get_order( $parent_order_id );

        $payment_method = $parent_order->get_payment_method();

        $payment_sum = $product_full_price * $payment['payment_amount'] / 100;

        $data = array(
            'amount' => $payment_sum,
            'replace_product_id' => $product_id,
            'installment_number' => $installment_number
        );

        // Create order
        $order_id = $this->create_child_order( $parent_order_id, $data );

        // Just for Stripe method
        if( $payment_method == 'stripe' ) {

            // Add intent
            $data = [
                'amount' => $payment_sum * 100, // * 100
                'currency' => $parent_order->get_currency(),
                'description' => 'International School of Self Awareness - Ordine '.$order_id.' ( '.$payment['date'].' )',
            ];
            $intent = $this->create_order_intent( $parent_order_id, $data );


            $stripe_data = $this->get_order_payment_details( $parent_order_id );

            // Update Order meta
            update_post_meta($order_id, '_is_paid', false);

            update_post_meta( $order_id, '_payment_method', 'stripe' );
            update_post_meta( $order_id, '_payment_method_title', 'Credit Card (Stripe)' );
            update_post_meta( $order_id, '_stripe_customer_id', $stripe_data['stripe_customer'] );
            update_post_meta( $order_id, '_stripe_source_id', $stripe_data['stripe_payment_method'] );
            update_post_meta( $order_id, '_stripe_intent_id', $intent->id );
            update_post_meta( $order_id, '_stripe_charge_captured', 'no' );
            update_post_meta( $order_id, '_stripe_currency', $parent_order->get_currency() );
            update_post_meta( $order_id, '_transaction_id', '' ); // Charged money ID transaction

        }

        update_post_meta($order_id, '_payment_date', $payment['date']);
        update_post_meta($order_id, '_payment_date_raw', strtotime($payment['date']));
        
        
        return $order_id;

    }


    public function create_order_intent( $order_id, $data ) {

        $order = wc_get_order( $order_id );

        $stripe_data = $this->get_order_payment_details( $order_id );

        $data['payment_method'] = $stripe_data['stripe_payment_method'];
        $data['customer'] = $stripe_data['stripe_customer'];

        $pay = new Deposit_extra_payment;
        $intent = $pay->create_payment_intent( $data );

        return $intent;

    }

    public function get_order_payment_details( $order_id ) {

        $data = array(
            "stripe_customer" => get_post_meta( $order_id, '_stripe_customer_id', true ),
            "stripe_payment_method" => get_post_meta( $order_id, '_stripe_source_id', true ),
        );
        

        return $data;


    }


    public function create_child_order( $parent_order_id=false, $data ) {

        if( !$parent_order_id ) { return false; }
        
        $original_order = wc_get_order( $parent_order_id );
        
        $user_id = $original_order->get_user_id();

      
        global $woocommerce;
      
        // Now we create the order
        $order = wc_create_order();
        $ORDER_ID = $order->get_id();
      
        // Billing, shipping
        $order->set_address( $original_order->get_address( 'billing' ), 'billing' );
        $order->set_address( $original_order->get_address( 'shipping' ), 'shipping' );
      
        

        if( $data['replace_product_id'] ) {
            $product = wc_get_product( $data['replace_product_id'] );
            $product->set_price( $data['amount'] );
      
            $order->add_product( $product, 1 ); 
        } else {

            // Products
            $items = $original_order->get_items();
            foreach ( $items as $item ) {
            $product = wc_get_product( $item->get_product_id() );
            $product->set_price( $data['amount'] );
        
            $order->add_product( $product, $item->get_quantity() ); 
            }

        }

        $order->set_parent_id( $parent_order_id );

        $order->set_payment_method( $original_order->get_payment_method() );

        $order->add_order_note( __( 'Installment order for '.$parent_order_id.'.', 'deposit-payments-extra' ) );
        $order->add_meta_data( '_installment_order_for', $parent_order_id );
        $order->add_meta_data( '_parent_order', $parent_order_id );
        $order->add_meta_data( 'installment_number', $data['installment_number'] );
      
        $order->set_customer_id($user_id);
        $order->calculate_totals();
        $order->save();
      
        return $ORDER_ID; 
      
    }

    public function cancel_subsription( $order_id ) {
        $this->set_sub_orders_status( $order_id, $status="cancelled", $note="Subscription cancelled" );
        //$this->set_sub_orders_status( $order_id, $status="completed", $note="Subscription cancelled" );
    }

    public function block_subsription( $order_id ) {
        $this->set_sub_orders_status( $order_id, $status="on-hold", $note="Subscription blocked" );
    }

    public function unblock_subsription( $order_id ) {
        $this->set_sub_orders_status( $order_id, $status="scheduled-payment", $note="Subscription unblocked" );
    }

    private function set_sub_orders_status( $order_id, $status, $status_note ) {

        $order = wc_get_order( $order_id );
        $sub_orders = $order->get_meta('_deposit_orders', true);

        if( !$sub_orders ) { return false; }

        foreach( $sub_orders as $sub_order_id ) {
            $this->set_order_status( $sub_order_id, $status, $status_note );
        }

        $this->set_order_status( $order_id, $status,$status_note );

    }

    private function set_order_status( $order_id, $status, $note="" ) {

        $order = wc_get_order($order_id);
        $order->set_status($status, $note);
        $order->save();

    }

    public static function chargeOrder( $order_id ) {

        $payment = new Deposit_extra_payment;

        $order = wc_get_order( $order_id );

        $intent_id = $order->get_meta('_stripe_intent_id', true);

        if( 
            !$intent_id ||
            !self::validatePaymentIntent( $order_id )
            ) { return false; }

        $charge = $payment->confirm_payment_intent( $intent_id );

        $status = '';
        $message = '';

        if( $charge['success'] ) {
            $order->update_meta_data('_is_paid', true, true);

            $order->set_status('processing');
            $order->save();

            $status = 'success';
            $message = 'Payment successful';
        }

        if( $charge['error'] ) {
            $order->set_status('failed', $charge['message']);
            $order->save();

            $status = 'fail';
            $message = $charge['message'];
        }

        return array(
            'status' => $status,
            'message' => $message
        );

    }

    public static function validatePaymentIntent( $order_id ) {

        $order = wc_get_order( $order_id );

        // If the user is the owner of the order
        if( get_current_user_id() == $order->get_user_id() ) {
            return true;
        }

    }

    /*
    public function create_installments( $order_id=false)  {

        if( !$order_id ) { return false; }

        $orders = array();
        foreach( $installments as $item ) {
            $orders[] = $this->create_child_order( $order_id, $item );
        }
  
        return $orders;
        
    }
    */

}

new Deposit_extra_order;