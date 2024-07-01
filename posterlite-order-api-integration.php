<?php

/**
 * Plugin Name: Posterelite Order API Integration
 * Plugin URI:  https://github.com/shahjalal132/woocommerce-order-api-integration
 * Author:      Shah jalal
 * Author URI:  https://github.com/shahjalal132
 * Description: Order api integration with Posterlite platform
 * Version:     0.1.0
 */

defined( "ABSPATH" ) || exit( "Direct Access Not Allowed" );

// Define plugin path
if ( !defined( 'WOO_ORDER_API_PLUGIN_PATH' ) ) {
    define( 'WOO_ORDER_API_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Define plugin url
if ( !defined( 'WOO_ORDER_API_PLUGIN_URL' ) ) {
    define( 'WOO_ORDER_API_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}

// Include files
require_once WOO_ORDER_API_PLUGIN_PATH . '/inc/woo-create-order.php';
require_once WOO_ORDER_API_PLUGIN_PATH . '/inc/woo-add-fields.php';
require_once WOO_ORDER_API_PLUGIN_PATH . '/inc/woo-helper-functions.php';