<?php
class Deposit_extra_order_actions {

	public static $instance;

	/**
	 * Main Metabox Instance.
	 *
	 * Ensures only one instance of the Metabox is loaded or can be loaded.
	 *
	 * @return Metabox - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {

        // Metabox
		add_action( 'add_meta_boxes', array( $this, 'order_sub_actions' ) );

        // JS
        add_action( 'admin_print_footer_scripts', array($this, 'order_sub_actions_js'), 99 );
        add_action( 'wp_enqueue_scripts', array($this, 'deposits_script'), 99 );

        // Ajax backend actions
        add_action( 'wp_ajax_cancel_subscription', array($this, 'cancel_subscription_callback') );
        add_action( 'wp_ajax_block_subscription', array($this, 'block_subscription_callback') );
        add_action( 'wp_ajax_unblock_subscription', array($this, 'unblock_subscription_callback') );
        
	}

	public function order_sub_actions() {
		add_meta_box(
			'address_validation_actions',
			__( 'Subscription actions', 'textdomain' ),
			array( $this, 'render_order_sub_actions' ),
			'shop_order',
			'side',
			'high'
		);
	}

	public function render_order_sub_actions( $order_id ) {
		
        $order = wc_get_order( $order_id );
        $meta = $order->get_meta_data();
        $sub_orders = $order->get_meta('_deposit_orders', true);

        $data = $order->get_data();

        if( !$sub_orders ) { return false; }

        // Blocked
        if( $data['status'] == 'on-hold' ) { $blocked = true; } else { $blocked = false; };

        // Cancelled
        if( $data['status'] == 'cancelled' ) { $cancelled = true; } else { $cancelled = false; };

        /*
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        */
		?>

		<div class="action-subscriptions">
            <?php if( $cancelled ) { ?>
			    <p style="color: red; font-size: 16px;">Subscription cancelled</p>
            <?php } else { ?>
                <button type="button" class="button cancel-subscription">Cancel subscription</button>
            <?php } ?>
		</div>

        <?php if( !$cancelled ) { ?>
            <br/>
            <div class="action-subscriptions">
                <?php if( $blocked ) { ?>
                    <button type="button" class="button unblock-subscription">Unblock subscription</button>
                <?php } else { ?>
                    <button type="button" class="button block-subscription">Block subscription</button>
                <?php } ?>    
            </div>
        <?php } ?>

		<?php
	}

    public function cancel_subscription_callback() {

        $order = new Deposit_extra_order();
        $order->cancel_subsription( $_POST['order_id'] );

        wp_die();
    }

    public function block_subscription_callback() {
        
        $order = new Deposit_extra_order();
        $order->block_subsription( $_POST['order_id'] );
        
        wp_die();
    }

    public function unblock_subscription_callback() {
        
        $order = new Deposit_extra_order();
        $order->unblock_subsription( $_POST['order_id'] );
        
        wp_die();
    }

    public function order_sub_actions_js() {

        $order_id = $_GET['post'];
        ?>
        <script>
        jQuery(document).ready( function( $ ){

            jQuery('.cancel-subscription').click(function() {

                var data = {
                    action: 'cancel_subscription',
                    order_id: <?php echo $order_id; ?>
                };
    
                jQuery.post( ajaxurl, data, function( response ){
                    window.location.reload();
                });
    
                return false;
    
            });

            jQuery('.block-subscription').click(function() {

                var data = {
                    action: 'block_subscription',
                    order_id: <?php echo $order_id; ?>
                };

                jQuery.post( ajaxurl, data, function( response ) {
                    window.location.reload();
                });

                return false;

            });
            
            jQuery('.unblock-subscription').click(function() {

                var data = {
                    action: 'unblock_subscription',
                    order_id: <?php echo $order_id; ?>
                };

                jQuery.post( ajaxurl, data, function( response ){
                    window.location.reload();
                });

                return false;

            });
    
            
        } );
        </script>
        <?php
    }

    public function deposits_script() {

        wp_localize_script( 'deposits-script', 'myajax',
            array(
                'url' => admin_url('admin-ajax.php')
            )
        );
    
    }

}

new Deposit_extra_order_actions();