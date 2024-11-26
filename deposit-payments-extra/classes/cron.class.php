<?php
Class Deposit_extra_cron {

    public function __construct() {

        add_action( 'admin_head', array($this, 'cron_activation') );
        //add_action( 'my_hourly_event', 'do_this_hourly' );

        add_filter( 'cron_schedules', array($this, 'isa_add_every_three_minutes') );

    }

    public function cron_activation() {

        if( ! wp_next_scheduled( 'charge_deposit_orders' ) ) {
            wp_schedule_event( time(), 'every_three_minutes', 'charge_deposit_orders');
        }

    }

    function isa_add_every_three_minutes( $schedules ) {
        $schedules['every_three_minutes'] = array(
                'interval'  => 60,
                'display'   => __( 'Every 1 Minutes', 'textdomain' )
        );
        return $schedules;
    }

    function charge_deposit_orders() {

        $payment = new Deposit_extra_payment;

        $args = array(
            'post_type'   => 'shop_order',
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key'     => '_is_paid',
                    'value'   => '',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_installment_order_for',
                    'compare' => 'EXISTS',
                ),
            ),
            'posts_per_page' => -1
        );
        $query = new WP_Query($args);
        $posts = $query->posts;

        if( count( $posts ) > 0 ) {

            foreach( $posts as $post ) {

                $order = wc_get_order($post->ID);
                $status = $order->get_status();

                if( $status == 'on-hold' || $status == 'cancelled' ) { continue; }

                //echo $post->ID; echo ' - '; echo $order->get_status(); echo "<br/>"; continue;
    
                $is_paid = $order->get_meta('_is_paid');
                $payment_date = $order->get_meta('_payment_date');
                $intent_id = $order->get_meta('_stripe_intent_id');

                if( !$payment_date || $payment_date == '' ) { continue; }
                if( !$intent_id ) { continue; }

                $payment_date = strtotime( $payment_date );
                $current_date = strtotime( date('Y/m/d') );
    
                if( $payment_date <= $current_date && !$is_paid ) {

                    echo $post->ID; echo "<br/>";
                    //echo $intent_id;
    
                    $charge = $payment->confirm_payment_intent( $intent_id );

                    echo "<pre>";
                    print_r($charge);
                    echo "</pre>";

                    if( $charge['success'] ) {
                        $order->update_meta_data('_is_paid', true, true);

                        $order->set_status('processing');
                        $order->save();
                    }

                    if( $charge['error'] ) {
                        $order->set_status('failed', $charge['message']);
                        $order->save();
                    }         
                    

                }
    
            }

        }

    }

    private function get_valid_payment_statuses() {

        $statuses = wc_get_order_statuses();
        unset( $statuses['wc-on-hold'], $statuses['wc-cancelled'] );

        $set = [];
        foreach( $statuses as $status=>$title ) {
            $set[] = $status;
        }
        return $set;

    }



}

new Deposit_extra_cron;


// More reliable way
add_action('init', 'init_charge_orders');
function init_charge_orders() {

    if( $_GET['charge_orders'] ) {

        $cron = new Deposit_extra_cron;
        $cron->charge_deposit_orders();

        die();

    }    

}

