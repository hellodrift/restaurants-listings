<?php
global $wp_post_types;

switch ( $restaurant->post_status ) :
	case 'publish' :
		printf( __( '%s listed successfully. To view your listing <a href="%s">click here</a>.', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, get_permalink( $restaurant->ID ) );
	break;
	case 'pending' :
		printf( __( '%s submitted successfully. Your listing will be visible once approved.', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, get_permalink( $restaurant->ID ) );
	break;
	default :
		do_action( 'listings_restaurants_restaurant_submitted_content_' . str_replace( '-', '_', sanitize_title( $restaurant->post_status ) ), $restaurant );
	break;
endswitch;

do_action( 'listings_restaurants_restaurant_submitted_content_after', sanitize_title( $restaurant->post_status ), $restaurant );