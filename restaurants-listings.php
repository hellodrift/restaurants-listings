<?php
/**
 * Plugin Name: Listings - Restaurants
 * Description: Adds Restaurants Listings functionality to the Listings plugin.
 * Version: 0.0.1
 * Author: OpenTute+
 * Text Domain: listings-restaurants
 */

// Define constants
define( 'LISTINGS_RESTAURANTS_VERSION', '0.0.1' );
define( 'LISTINGS_RESTAURANTS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'LISTINGS_RESTAURANTS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'LISTINGS_RESTAURANTS_PLUGIN_FILE', __FILE__ );

/**
 * @return \Listings\Restaurants\Plugin
 */
function listings_restaurants() {
    static $instance;
    if ( is_null( $instance ) ) {
        $instance = new \Listings\Restaurants\Plugin();
        $instance->hooks();
    }
    return $instance;
}

function __load_listings_restaurants() {
    if( version_compare( PHP_VERSION, '5.3', '<' ) ) {
        include('helpers/php-fallback.php');
        $fallback = new Listings_PHP_Fallback( 'Listings Restaurants' );
        $fallback->trigger_notice();
        return;
    }

    $GLOBALS['listings_restaurants'] = listings_restaurants();
}

// autoloader
require 'vendor/autoload.php';

register_activation_hook( basename( dirname( LISTINGS_RESTAURANTS_PLUGIN_FILE ) ) . '/' . basename( LISTINGS_RESTAURANTS_PLUGIN_FILE ), function() {
    \Listings\Restaurants\Install::install();
});

// create plugin object
add_action( 'listings_init', '__load_listings_restaurants', 10 );
