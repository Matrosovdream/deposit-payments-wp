<?php
class Deposit_extra_product {

    public function get_installment( $post_id ) {

        $data = array();
        $full_payment = get_field('full_payment', $post_id);
        
        if( $full_payment ) {

            $full_payment_discount = get_field('full_payment_discount', $post_id);

            $data = array(
                "type" => "full_payment",
                "discount" => $full_payment_discount
            );

        } else {

            $down_payment = get_field('down_payment', $post_id);
            $type = get_field('type', $post_id);
            $dates = get_field('dates', $post_id);
            $periods = get_field('periods', $post_id);

            if( $type == "Dates" ) {
                $payments = $dates;
            }

            if( $type == "Periods" ) {

                $payments = array();

                $final = date('Y/m/d');
                foreach( $periods as $period ) {

                    if( $period['interval'] == 'Months' ) { 
                        $final = date("Y/m/d", strtotime("+".$period['interval_amount']." month", strtotime($final) ));
                    }

                    if( $period['interval'] == 'Days' ) { 
                        $final = date("Y/m/d", strtotime("+".$period['interval_amount']." day", strtotime($final) ));
                    }

                    $payments[] = array(
                        "payment_amount" => $period['payment_amount'],
                        "date" => $final
                    );

                }

            }

            $data = array(
                "type" => "installments",
                "downpayment" => $down_payment,
                "payments" => $payments
            );

        }

        return $data;   

    }


    public function calculate_payment_dates( $installment_id ) {

        $calc = $this->get_installment( $installment_id );
        return $calc;

    }











}