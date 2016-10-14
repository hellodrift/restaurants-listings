<?php

namespace Listings\Restaurants\Ajax\Actions;

use Listings\Ajax\Action;

class GetListings extends Action
{
    public function getActionString()
    {
        return 'get_restaurant_listings';
    }

    public function doAction()
    {
        global $wp_post_types;

        $result            = array();
        $search_location   = sanitize_text_field( stripslashes( $_REQUEST['search_location'] ) );
        $search_keywords   = sanitize_text_field( stripslashes( $_REQUEST['search_keywords'] ) );
        $search_categories = isset( $_REQUEST['search_categories'] ) ? $_REQUEST['search_categories'] : '';
        $filter_restaurant_types  = isset( $_REQUEST['filter_restaurant_type'] ) ? array_filter( array_map( 'sanitize_title', (array) $_REQUEST['filter_restaurant_type'] ) ) : null;
        $types             = listings_restaurants_get_types();
        $post_type_label   = $wp_post_types['restaurant_listing']->labels->name;
        $orderby           = sanitize_text_field( $_REQUEST['orderby'] );

        if ( is_array( $search_categories ) ) {
            $search_categories = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) ) );
        } else {
            $search_categories = array_filter( array( sanitize_text_field( stripslashes( $search_categories ) ) ) );
        }

        $args = array(
            'search_location'    => $search_location,
            'search_keywords'    => $search_keywords,
            'search_categories'  => $search_categories,
            'restaurant_types'          => is_null( $filter_restaurant_types ) || sizeof( $types ) === sizeof( $filter_restaurant_types ) ? '' : $filter_restaurant_types + array( 0 ),
            'orderby'            => $orderby,
            'order'              => sanitize_text_field( $_REQUEST['order'] ),
            'offset'             => ( absint( $_REQUEST['page'] ) - 1 ) * absint( $_REQUEST['per_page'] ),
            'posts_per_page'     => absint( $_REQUEST['per_page'] )
        );

        if ( isset( $_REQUEST['filled'] ) && ( $_REQUEST['filled'] === 'true' || $_REQUEST['filled'] === 'false' ) ) {
            $args['filled'] = $_REQUEST['filled'] === 'true' ? true : false;
        }

        if ( isset( $_REQUEST['featured'] ) && ( $_REQUEST['featured'] === 'true' || $_REQUEST['featured'] === 'false' ) ) {
            $args['featured'] = $_REQUEST['featured'] === 'true' ? true : false;
            $args['orderby']  = 'featured' === $orderby ? 'date' : $orderby;
        }

        ob_start();

        $jobs = listings_restaurants_get_listings( apply_filters( 'listings_restaurants_get_listings_args', $args ) );

        $result['found_restaurants'] = false;

        if ( $jobs->have_posts() ) : $result['found_restaurants'] = true; ?>

            <?php while ( $jobs->have_posts() ) : $jobs->the_post(); ?>

                <?php listings_get_template_part( 'content', 'restaurant_listing' ); ?>

            <?php endwhile; ?>

        <?php else : ?>

            <?php listings_get_template_part( 'content', 'no-restaurants-found' ); ?>

        <?php endif;

        $result['html']    = ob_get_clean();
        $result['showing'] = array();

        // Generate 'showing' text
        $showing_types = array();
        $unmatched     = false;

        foreach ( $types as $type ) {
            if ( is_array( $filter_restaurant_types ) && in_array( $type->slug, $filter_restaurant_types ) ) {
                $showing_types[] = $type->name;
            } else {
                $unmatched = true;
            }
        }

        if ( sizeof( $showing_types ) == 1 ) {
            $result['showing'][] = implode( ', ', $showing_types );
        } elseif ( $unmatched && $showing_types ) {
            $last_type           = array_pop( $showing_types );
            $result['showing'][] = implode( ', ', $showing_types ) . " &amp; $last_type";
        }

        if ( $search_categories ) {
            $showing_categories = array();

            foreach ( $search_categories as $category ) {
                $category_object = get_term_by( is_numeric( $category ) ? 'id' : 'slug', $category, 'restaurant_listing_category' );

                if ( ! is_wp_error( $category_object ) ) {
                    $showing_categories[] = $category_object->name;
                }
            }

            $result['showing'][] = implode( ', ', $showing_categories );
        }

        if ( $search_keywords ) {
            $result['showing'][] = '&ldquo;' . $search_keywords . '&rdquo;';
        }

        $result['showing'][] = $post_type_label;

        if ( $search_location ) {
            $result['showing'][] = sprintf( __( 'located in &ldquo;%s&rdquo;', 'restaurants-listings' ), $search_location );
        }

        if ( 1 === sizeof( $result['showing'] ) ) {
            $result['showing_all'] = true;
        }

        $result['showing'] = apply_filters( 'listings_restaurants_get_listings_custom_filter_text', sprintf( __( 'Showing all %s', 'restaurants-listings' ), implode( ' ', $result['showing'] ) ) );

        // Generate RSS link
        $result['showing_links'] = listings_restaurants_get_filtered_links( array(
            'filter_restaurant_types'  => $filter_restaurant_types,
            'search_location'   => $search_location,
            'search_categories' => $search_categories,
            'search_keywords'   => $search_keywords
        ) );

        // Generate pagination
        if ( isset( $_REQUEST['show_pagination'] ) && $_REQUEST['show_pagination'] === 'true' ) {
            $result['pagination'] = listings_get_listing_pagination( $jobs->max_num_pages, absint( $_REQUEST['page'] ) );
        }

        $result['max_num_pages'] = $jobs->max_num_pages;

        wp_send_json( apply_filters( 'listings_restaurants_get_listings_result', $result, $jobs ) );
    }
}