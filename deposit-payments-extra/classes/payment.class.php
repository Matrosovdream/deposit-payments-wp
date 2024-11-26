<?php
use Stripe_Error;

class Deposit_extra_payment {

    public function __construct() {

        $this->setStripeSettings();

    }

    private function setStripeSettings() {

        require_once( SB_PLUGIN_DIR_ABS.'/vendor/autoload.php' );

        $stripe_settings = get_option('woocommerce_stripe_settings');

        //$stripe_settings['testmode'] = 'yes'; // remove

        if( !is_array( $stripe_settings ) || $stripe_settings['enabled'] != 'yes' ) { return false; }

        if( $stripe_settings['testmode'] == 'yes' ) {
            $sk = $stripe_settings['test_secret_key'];
        } else {
            $sk = $stripe_settings['secret_key'];
        }

        if( $sk ) {
            \Stripe\Stripe::setApiKey( $sk );
        }        

    }

    public function create_payment_intent( $data, $confirm=false ) {

        try {
            
            $intent = \Stripe\PaymentIntent::create($data);
            $res = $intent;

            if( $confirm ) {
                $this->confirm_payment_intent( $intent->id );
            }

            return $intent;

        } catch (\Stripe\Error\Base $e) {

        } catch (Exception $e) {
            $res = array("error" => true, "message" => $e->getMessage());
        }

        return $res;

    }

    public function confirm_payment_intent( $intent_id ) {

        $intent = \Stripe\PaymentIntent::retrieve( $intent_id );
        
        try {
            
            $confirm = $intent->confirm();

            if( $confirm['status'] == 'succeeded' ) {
                $res = array("success" => true, "message" => "");
            } else {
                $res = array("error" => true, "message" => "");
            }

        } catch (\Stripe\Error\Base $e) {
            $res = array("error" => true, "message" => $e->getMessage());
        } catch (Exception $e) {
            $res = array("error" => true, "message" => $e->getMessage());
        }

        return $res;

    }

}