<?php
/**
 * Namespace declaration for the Simply_Hide_Author plugin/module.
 * Ensures encapsulation and avoids naming collisions.
 */
namespace woocommerce_telemetry_blocker;

/**
 * @package       WooCommerce Telemetry Blocker
 * @author        Edwin Bekedam
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 */
if (!defined('ABSPATH')) die();


foreach( glob( plugin_dir_path( __FILE__ )."inc/mu/*.php" ) as $woocommerce_telemetry_blocker_file ){
	include_once $woocommerce_telemetry_blocker_file;
}

require_once plugin_dir_path(dirname(__FILE__)) . 'inc/mu/woo-config.php';
