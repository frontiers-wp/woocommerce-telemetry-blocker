<?php
/**
 * @package       WooCommerce Telemetry Blocke
 * @author        Edwin Bekedam
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 */
if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

/**
 * Remove Option on uninstalling/deleting the Plugin.
 */
function woocommerce_telemetry_blocker_plugin_uninstall() {
	delete_option( 'woocommerce_telemetry_blocker_plugin_version' );
    delete_transient( 'woo_privacy_telemetry_purged' );
}
/**
 * Remove Fields on uninstalling/deleting the Plugin.
 */
delete_option('woocommerce_telemetry_blocke_fields');