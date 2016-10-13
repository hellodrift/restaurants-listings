<?php

if ( ! function_exists( 'listings_restaurants_get_listings' ) ) :
    /**
     * Queries job listings with certain criteria and returns them
     *
     * @access public
     * @return void
     */
    function listings_restaurants_get_listings( $args = array() ) {
        global $wpdb, $listings_keyword;

        $args = wp_parse_args( $args, array(
            'search_location'   => '',
            'search_keywords'   => '',
            'search_categories' => array(),
            'restaurant_types'         => array(),
            'offset'            => 0,
            'posts_per_page'    => 20,
            'orderby'           => 'date',
            'order'             => 'DESC',
            'featured'          => null,
            'filled'            => null,
            'fields'            => 'all'
        ) );

        $query_args = array(
            'post_type'              => 'restaurant_listing',
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => 1,
            'offset'                 => absint( $args['offset'] ),
            'posts_per_page'         => intval( $args['posts_per_page'] ),
            'orderby'                => $args['orderby'],
            'order'                  => $args['order'],
            'tax_query'              => array(),
            'meta_query'             => array(),
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'cache_results'          => false,
            'fields'                 => $args['fields']
        );

        // WPML workaround
        if ( ( strstr( $_SERVER['REQUEST_URI'], '/listings-ajax/' ) || ! empty( $_GET['listings-ajax'] ) ) && isset( $_POST['lang'] ) ) {
            do_action( 'wpml_switch_language', sanitize_text_field( $_POST['lang'] ) );
        }

        if ( $args['posts_per_page'] < 0 ) {
            $query_args['no_found_rows'] = true;
        }

        if ( ! empty( $args['search_location'] ) ) {
            $location_meta_keys = array( 'geolocation_formatted_address', '_restaurant_location', 'geolocation_state_long' );
            $location_search    = array( 'relation' => 'OR' );
            foreach ( $location_meta_keys as $meta_key ) {
                $location_search[] = array(
                    'key'     => $meta_key,
                    'value'   => $args['search_location'],
                    'compare' => 'like'
                );
            }
            $query_args['meta_query'][] = $location_search;
        }

        if ( ! is_null( $args['featured'] ) ) {
            $query_args['meta_query'][] = array(
                'key'     => '_featured',
                'value'   => '1',
                'compare' => $args['featured'] ? '=' : '!='
            );
        }

        if ( ! is_null( $args['filled'] ) || 1 === absint( get_option( 'listings_restaurants_hide_filled_positions' ) ) ) {
            $query_args['meta_query'][] = array(
                'key'     => '_filled',
                'value'   => '1',
                'compare' => $args['filled'] ? '=' : '!='
            );
        }

        if ( ! empty( $args['restaurant_types'] ) ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'restaurant_listing_type',
                'field'    => 'slug',
                'terms'    => $args['restaurant_types']
            );
        }

        if ( ! empty( $args['search_categories'] ) ) {
            $field    = is_numeric( $args['search_categories'][0] ) ? 'term_id' : 'slug';
            $operator = 'all' === get_option( 'listings_restaurants_category_filter_type', 'all' ) && sizeof( $args['search_categories'] ) > 1 ? 'AND' : 'IN';
            $query_args['tax_query'][] = array(
                'taxonomy'         => 'restaurant_listing_category',
                'field'            => $field,
                'terms'            => array_values( $args['search_categories'] ),
                'include_children' => $operator !== 'AND' ,
                'operator'         => $operator
            );
        }

        if ( 'featured' === $args['orderby'] ) {
            $query_args['orderby'] = array(
                'menu_order' => 'ASC',
                'date'       => 'DESC'
            );
        }

        $listings_keyword = sanitize_text_field( $args['search_keywords'] );

        if ( ! empty( $listings_keyword ) && strlen( $listings_keyword ) >= apply_filters( 'listings_restaurants_get_listings_keyword_length_threshold', 2 ) ) {
            $query_args['_keyword'] = $listings_keyword; // Does nothing but needed for unique hash
            add_filter( 'posts_clauses', 'listings_get_keyword_search' );
        }

        $query_args = apply_filters( 'listings_restaurants_get_listings', $query_args, $args );

        if ( empty( $query_args['meta_query'] ) ) {
            unset( $query_args['meta_query'] );
        }

        if ( empty( $query_args['tax_query'] ) ) {
            unset( $query_args['tax_query'] );
        }

        // Polylang LANG arg
        if ( function_exists( 'pll_current_language' ) ) {
            $query_args['lang'] = pll_current_language();
        }

        // Filter args
        $query_args = apply_filters( 'get_restaurant_listings_query_args', $query_args, $args );

        // Generate hash
        $to_hash         = json_encode( $query_args ) . apply_filters( 'wpml_current_language', '' );
        $query_args_hash = 'jm_' . md5( $to_hash ) . \Listings\CacheHelper::get_transient_version( 'get_restaurant_listings' );

        do_action( 'before_get_restaurant_listings', $query_args, $args );

        if ( false === ( $result = get_transient( $query_args_hash ) ) ) {
            $result = new WP_Query( $query_args );
            set_transient( $query_args_hash, $result, DAY_IN_SECONDS * 30 );
        }

        do_action( 'after_get_restaurant_listings', $query_args, $args );

        remove_filter( 'posts_clauses', 'listings_get_keyword_search' );

        return $result;
    }
endif;

if ( ! function_exists( 'listings_restaurants_get_types' ) ) :
    /**
     * Get job listing types
     *
     * @access public
     * @return array
     */
    function listings_restaurants_get_types( $fields = 'all' ) {
        return get_terms( "restaurant_listing_type", array(
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => false,
            'fields'     => $fields
        ) );
    }
endif;

if ( ! function_exists( 'listings_restaurants_get_filtered_links' ) ) :
    /**
     * Shows links after filtering jobs
     */
    function listings_restaurants_get_filtered_links( $args = array() ) {
        $restaurant_categories = array();
        $types          = listings_restaurants_get_types();

        // Convert to slugs
        if ( $args['search_categories'] ) {
            foreach ( $args['search_categories'] as $category ) {
                if ( is_numeric( $category ) ) {
                    $category_object = get_term_by( 'id', $category, 'restaurant_listing_category' );
                    if ( ! is_wp_error( $category_object ) ) {
                        $restaurant_categories[] = $category_object->slug;
                    }
                } else {
                    $restaurant_categories[] = $category;
                }
            }
        }

        $links = apply_filters( 'listings_restaurants_filters_showing_links', array(
            'reset' => array(
                'name' => __( 'Reset', 'restaurants-listings' ),
                'url'  => '#'
            ),
            'rss_link' => array(
                'name' => __( 'RSS', 'restaurants-listings' ),
                'url'  => listings_restaurants_get_rss_link( apply_filters( 'listings_restaurants_get_listings_custom_filter_rss_args', array(
                    'restaurant_types'       => isset( $args['filter_restaurant_types'] ) ? implode( ',', $args['filter_restaurant_types'] ) : '',
                    'search_location' => $args['search_location'],
                    'restaurant_categories'  => implode( ',', $restaurant_categories ),
                    'search_keywords' => $args['search_keywords'],
                ) ) )
            )
        ), $args );

        if ( sizeof( $args['filter_restaurant_types'] ) === sizeof( $types ) && ! $args['search_keywords'] && ! $args['search_location'] && ! $args['search_categories'] && ! apply_filters( 'listings_restaurants_get_listings_custom_filter', false ) ) {
            unset( $links['reset'] );
        }

        $return = '';

        foreach ( $links as $key => $link ) {
            $return .= '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $key ) . '">' . $link['name'] . '</a>';
        }

        return $return;
    }
endif;

if ( ! function_exists( 'listings_restaurants_get_rss_link' ) ) :
    /**
     * Get the Job Listing RSS link
     *
     * @return string
     */
    function listings_restaurants_get_rss_link( $args = array() ) {
        $rss_link = add_query_arg( urlencode_deep( array_merge( array( 'feed' => 'restaurant_feed' ), $args ) ), home_url() );
        return $rss_link;
    }
endif;

if ( ! function_exists( 'listings_restaurants_get_listing_post_statuses' ) ) :
    /**
     * Get post statuses used for jobs
     *
     * @access public
     * @return array
     */
    function listings_restaurants_get_listing_post_statuses() {
        return apply_filters( 'restaurant_listing_post_statuses', array(
            'draft'           => _x( 'Draft', 'post status', 'restaurants-listings' ),
            'expired'         => _x( 'Expired', 'post status', 'restaurants-listings' ),
            'preview'         => _x( 'Preview', 'post status', 'restaurants-listings' ),
            'pending'         => _x( 'Pending approval', 'post status', 'restaurants-listings' ),
            'pending_payment' => _x( 'Pending payment', 'post status', 'restaurants-listings' ),
            'publish'         => _x( 'Active', 'post status', 'restaurants-listings' ),
        ) );
    }
endif;

/**
 * Outputs the jobs status
 *
 * @return void
 */
function listings_restaurants_restaurant_status( $post = null ) {
    echo listings_restaurants_get_restaurant_status( $post );
}

/**
 * Gets the jobs status
 *
 * @return string
 */
function listings_restaurants_get_restaurant_status( $post = null ) {
    $post     = get_post( $post );
    $status   = $post->post_status;
    $statuses = listings_restaurants_get_listing_post_statuses();

    if ( isset( $statuses[ $status ] ) ) {
        $status = $statuses[ $status ];
    } else {
        $status = __( 'Inactive', 'restaurants-listings' );
    }

    return apply_filters( 'listings_restaurants_restaurant_status', $status, $post );
}

if ( ! function_exists( 'listings_restaurants_get_featured_restaurant_ids' ) ) :
    /**
     * Gets the ids of featured jobs.
     *
     * @access public
     * @return array
     */
    function listings_restaurants_get_featured_restaurant_ids() {
        return get_posts( array(
            'posts_per_page' => -1,
            'post_type'      => 'restaurant_listing',
            'post_status'    => 'publish',
            'meta_key'       => '_featured',
            'meta_value'     => '1',
            'fields'         => 'ids'
        ) );
    }
endif;

/**
 * Duplicate a listing.
 * @param  int $post_id
 * @return int 0 on fail or the post ID.
 */
function listings_restaurants_duplicate_listing( $post_id ) {
    if ( empty( $post_id ) || ! ( $post = get_post( $post_id ) ) ) {
        return 0;
    }

    /** @var $wpdb \wpdb */
    global $wpdb;

    /**
     * Duplicate the post.
     */
    $new_post_id = wp_insert_post( array(
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
        'post_author'    => $post->post_author,
        'post_content'   => $post->post_content,
        'post_excerpt'   => $post->post_excerpt,
        'post_name'      => $post->post_name,
        'post_parent'    => $post->post_parent,
        'post_password'  => $post->post_password,
        'post_status'    => 'preview',
        'post_title'     => $post->post_title,
        'post_type'      => $post->post_type,
        'to_ping'        => $post->to_ping,
        'menu_order'     => $post->menu_order
    ) );

    /**
     * Copy taxonomies.
     */
    $taxonomies = get_object_taxonomies( $post->post_type );

    foreach ( $taxonomies as $taxonomy ) {
        $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
        wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
    }

    /*
     * Duplicate post meta, aside from some reserved fields.
     */
    $post_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $post_id ) );

    if ( ! empty( $post_meta ) ) {
        $post_meta = wp_list_pluck( $post_meta, 'meta_value', 'meta_key' );
        foreach ( $post_meta as $meta_key => $meta_value ) {
            if ( in_array( $meta_key, apply_filters( 'listings_restaurants_duplicate_listing_ignore_keys', array( '_filled', '_featured', '_restaurant_expires', '_restaurant_duration', '_package_id', '_user_package_id' ) ) ) ) {
                continue;
            }
            update_post_meta( $new_post_id, $meta_key, $meta_value );
        }
    }

    update_post_meta( $new_post_id, '_filled', 0 );
    update_post_meta( $new_post_id, '_featured', 0 );

    return $new_post_id;
}

/**
 * Calculate and return the job expiry date
 * @param  int $restaurant_id
 * @return string
 */
function listings_restaurants_calculate_restaurant_expiry( $restaurant_id ) {
    // Get duration from the product if set...
    $duration = get_post_meta( $restaurant_id, '_restaurant_duration', true );

    // ...otherwise use the global option
    if ( ! $duration ) {
        $duration = absint( get_option( 'listings_restaurants_submission_duration' ) );
    }

    if ( $duration ) {
        return date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
    }

    return '';
}

if ( ! function_exists( 'listings_restaurants_get_listing_categories' ) ) :
    /**
     * Get job categories
     *
     * @access public
     * @return array
     */
    function listings_restaurants_get_listing_categories() {
        if ( ! get_option( 'listings_restaurants_enable_categories' ) ) {
            return array();
        }

        return get_terms( "restaurant_listing_category", array(
            'orderby'       => 'name',
            'order'         => 'ASC',
            'hide_empty'    => false,
        ) );
    }
endif;