<?php
Class SB_checkout {

    private $redirect_url = ''; 

    function __construct() {

        $this->redirect_url = get_option('sb_checkout_external_url');
        
        // JS scripts
        add_action('wp_footer', array($this, 'includeFooterScripts'));

        // Ajax requests
        if( wp_doing_ajax() ){
            add_action( 'wp_ajax_sb_checkout_redirect', array($this, 'sb_checkout_redirect_func') );
            add_action( 'wp_ajax_nopriv_sb_checkout_redirect', array($this, 'sb_checkout_redirect_func') );
        }

    }

    public function includeFooterScripts() {

        wp_enqueue_script( 'sb_checkout_scripts', SB_PLUGIN_DIR . 'include/JS/scripts.js?time='.time(), array( 'jquery' ), 1.1, true);

    }

    public function sb_checkout_redirect_func() {

        $data = $_POST;

        if( !$data['post_id'] ) { return false; }

        $sb_product = new SB_product;
        $product = $sb_product->get_product_by_post_ID( $data['post_id'] );

        if( is_array($product) ) {

            $encr = new SB_encrypt;
            $encrypted_line = $encr->encrypt_data( json_encode($product) );
            $redirect_url = $this->redirect_url.'?data='.$encrypted_line;
    
            $res = array(
                "encrypted_line" => $encrypted_line,
                "redirect_url" => $redirect_url
            );

        } else {
            $res = array(
                "error" => "No product attached to package."
            );
        }
        
        echo wp_send_json($res);
        wp_die();

    }

}

new SB_checkout;