<?php

namespace Listings\Restaurants;

use Listings\Ajax\Handler;
use Listings\Restaurants\Admin\Admin;
use Listings\Restaurants\Ajax\Actions\GetListings;
use Listings\Restaurants\Forms\EditRestaurant;
use Listings\Restaurants\Forms\SubmitRestaurant;

class Plugin {
    public function __construct()
    {
        if (is_admin()) {
            new Admin();
        }

        // Register template path for this plugin
        listings()->template->register_template_path(LISTINGS_RESTAURANTS_PLUGIN_DIR . '/templates/');
        listings()->forms->register_form(new EditRestaurant());
        listings()->forms->register_form(new SubmitRestaurant());

        // Register Ajax actions
        listings()->ajax->registerAction(new GetListings() );

        $this->install = new Install();
        $this->post_types = new PostTypes();
        $this->shortcodes = new Shortcodes();
    }

    public function hooks()
    {
        // Switch theme
        add_action( 'after_switch_theme', 'flush_rewrite_rules', 15 );
        add_action( 'after_switch_theme', array( $this->post_types, 'register_post_types' ), 11 );

        add_action( 'widgets_init', array( $this, 'widgets_init' ) );

        // Actions
        add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action( 'admin_init', array( $this, 'updater' ) );
    }
    
    public function updater() {
        if ( version_compare( LISTINGS_RESTAURANTS_VERSION, get_option( 'listings_restaurants_version' ), '>' ) ) {
            Install::install();
            flush_rewrite_rules();
        }
    }

    public function load_plugin_textdomain() {
        load_textdomain( 'restaurants-listings', WP_LANG_DIR . "/listings-restaurants/listings-restaurants-" . apply_filters( 'plugin_locale', get_locale(), 'restaurants-listings' ) . ".mo" );
        load_plugin_textdomain( 'restaurants-listings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Widgets init
     */
    public function widgets_init() {
        register_widget( 'Listings\Restaurants\Widgets\RecentRestaurants' );
        register_widget( 'Listings\Restaurants\Widgets\FeaturedRestaurants' );
    }

    public function enqueue_scripts() {
        $ajax_url         = Handler::get_endpoint();
        $ajax_filter_deps = array( 'jquery', 'jquery-deserialize' );
        $ajax_data 		  = array(
            'ajax_url'                => $ajax_url,
            'is_rtl'                  => is_rtl() ? 1 : 0,
            'i18n_load_prev_listings' => __( 'Load previous listings', 'listings' ),
        );

        wp_enqueue_style( 'restaurants-listings', LISTINGS_RESTAURANTS_PLUGIN_URL . '/assets/css/frontend.css' );

        wp_register_script( 'listings-ajax-filters', LISTINGS_RESTAURANTS_PLUGIN_URL . '/assets/js/ajax-filters.min.js', $ajax_filter_deps, LISTINGS_RESTAURANTS_VERSION, true );
        wp_localize_script( 'listings-ajax-filters', 'listings_ajax_filters', $ajax_data );
        wp_enqueue_script( 'listings-restaurant-application', LISTINGS_RESTAURANTS_PLUGIN_URL . '/assets/js/job-application.min.js', array( 'jquery' ), LISTINGS_RESTAURANTS_VERSION, true );
        wp_enqueue_script( 'listings-restaurant-submission', LISTINGS_RESTAURANTS_PLUGIN_URL . '/assets/js/job-submission.min.js', array( 'jquery' ), LISTINGS_RESTAURANTS_VERSION, true );

        wp_register_script( 'listings-restaurant-dashboard', LISTINGS_RESTAURANTS_PLUGIN_URL . '/assets/js/job-dashboard.min.js', array( 'jquery' ), LISTINGS_RESTAURANTS_VERSION, true );
        wp_localize_script( 'listings-restaurant-dashboard', 'listings_restaurant_dashboard', array(
            'i18n_confirm_delete' => __( 'Are you sure you want to delete this listing?', 'listings' )
        ) );
        wp_enqueue_script( 'listings-restaurant-dashboard');
    }
}