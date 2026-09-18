<?php if (!defined('ABSPATH')) die();

use Frontiers\woocommerce_telemetry_blocker\Plugin;

function woocommerce_telemetry_blocker_nonce_field(){
	Plugin::instance()->nonceField();
}

