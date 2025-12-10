<?php
/**
 * Plugin Name:       FluentCart Kashier Payment Gateway
 * Plugin URI:        https://developers.kashier.io/
 * Description:       Accept payments via Kashier in FluentCart.
 * Version:           1.0.0
 * Author:            Antigravity
 * Text Domain:       fluent-cart-kashier
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  fluent-cart
 * License:           GPLv3
 */

namespace FluentCartKashier;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'FLUENT_CART_KASHIER_VERSION', '1.0.0' );
define( 'FLUENT_CART_KASHIER_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLUENT_CART_KASHIER_URL', plugin_dir_url( __FILE__ ) );
define( 'FLUENT_CART_KASHIER_FILE', __FILE__ );

/**
 * Initialize the plugin.
 */
function init_plugin() {
    // Check if FluentCart is active
    if ( ! defined( 'FLUENTCART_VERSION' ) ) {
        return;
    }

    require_once FLUENT_CART_KASHIER_DIR . 'includes/class-fluent-cart-kashier.php';
    
    // Initialize the main class
    \FluentCartKashier\Includes\Fluent_Cart_Kashier::get_instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\init_plugin' );
