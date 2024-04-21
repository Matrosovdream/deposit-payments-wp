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





























