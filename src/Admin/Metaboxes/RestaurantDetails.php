<?php

namespace Listings\Restaurants\Admin\Metaboxes;

use Listings\Admin\Metabox;
use Listings\Geocode;

class RestaurantDetails extends Metabox
{
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
		add_action( 'listings_restaurants_save_restaurant_listing', array( $this, 'save_restaurant_listing_data' ), 20, 2 );
	}

	/**
	 * restaurant_listing_fields function.
	 *
	 * @access public
	 * @return void
	 */
	public function restaurant_listing_fields() {
		global $post;

		$current_user = wp_get_current_user();

		$fields = array(
			'_restaurant_location' => array(
				'label' => __( 'Location', 'restaurants-listings' ),
				'placeholder' => __( 'e.g. "London"', 'restaurants-listings' ),
				'description' => __( 'Leave this blank if the location is not important.', 'restaurants-listings' ),
				'priority'    => 1
			),
			'_application' => array(
				'label'       => __( 'Application Email or URL', 'restaurants-listings' ),
				'placeholder' => __( 'URL or email which applicants use to apply', 'restaurants-listings' ),
				'description' => __( 'This field is required for the "application" area to appear beneath the listing.', 'restaurants-listings' ),
				'value'       => metadata_exists( 'post', $post->ID, '_application' ) ? get_post_meta( $post->ID, '_application', true ) : $current_user->user_email,
				'priority'    => 2
			),
			'_company_name' => array(
				'label'       => __( 'Company Name', 'restaurants-listings' ),
				'placeholder' => '',
				'priority'    => 3
			),
			'_company_website' => array(
				'label'       => __( 'Company Website', 'restaurants-listings' ),
				'placeholder' => '',
				'priority'    => 4
			),
			'_company_tagline' => array(
				'label'       => __( 'Company Tagline', 'restaurants-listings' ),
				'placeholder' => __( 'Brief description about the company', 'restaurants-listings' ),
				'priority'    => 5
			),
			'_company_twitter' => array(
				'label'       => __( 'Company Twitter', 'restaurants-listings' ),
				'placeholder' => '@yourcompany',
				'priority'    => 6
			),
			'_company_video' => array(
				'label'       => __( 'Company Video', 'restaurants-listings' ),
				'placeholder' => __( 'URL to the company video', 'restaurants-listings' ),
				'type'        => 'file',
				'priority'    => 8
			),
			'_filled' => array(
				'label'       => __( 'Position Filled', 'restaurants-listings' ),
				'type'        => 'checkbox',
				'priority'    => 9,
				'description' => __( 'Filled listings will no longer accept applications.', 'restaurants-listings' ),
			)
		);
		if ( $current_user->has_cap( 'manage_restaurant_listings' ) ) {
			$fields['_featured'] = array(
				'label'       => __( 'Featured Listing', 'restaurants-listings' ),
				'type'        => 'checkbox',
				'description' => __( 'Featured listings will be sticky during searches, and can be styled differently.', 'restaurants-listings' ),
				'priority'    => 10
			);
			$fields['_restaurant_expires'] = array(
				'label'       => __( 'Listing Expiry Date', 'restaurants-listings' ),
				'priority'    => 11,
				'classes'     => array( 'listings-restaurants-datepicker' ),
				'placeholder' => _x( 'yyyy-mm-dd', 'Date format placeholder', 'restaurants-listings' ),
				'value'       => metadata_exists( 'post', $post->ID, '_restaurant_expires' ) ? get_post_meta( $post->ID, '_restaurant_expires', true ) : listings_restaurants_calculate_restaurant_expiry( $post->ID ),
			);
		}
		if ( $current_user->has_cap( 'edit_others_restaurant_listings' ) ) {
			$fields['_restaurant_author'] = array(
				'label'    => __( 'Posted by', 'restaurants-listings' ),
				'type'     => 'author',
				'priority' => 12
			);
		}

		$fields = apply_filters( 'listings_restaurants_restaurant_listing_data_fields', $fields );

		uasort( $fields, array( $this, 'sort_by_priority' ) );

		return $fields;
	}

	/**
	 * Sort array by priority value
	 */
	protected function sort_by_priority( $a, $b ) {
	    if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
	        return 0;
	    }
	    return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	/**
	 * add_meta_boxes function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_meta_boxes() {
		global $wp_post_types;

		add_meta_box( 'restaurant_listing_data', sprintf( __( '%s Data', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name ), array( $this, 'restaurant_listing_data' ), 'restaurant_listing', 'normal', 'high' );
	}

	/**
	 * restaurant_listing_data function.
	 *
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	public function restaurant_listing_data( $post ) {
		global $post, $thepostid;

		$thepostid = $post->ID;

		echo '<div class="listings_restaurants_meta_data">';

		wp_nonce_field( 'save_meta_data', 'listings_restaurants_nonce' );

		do_action( 'listings_restaurants_restaurant_listing_data_start', $thepostid );

		foreach ( $this->restaurant_listing_fields() as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';

			if ( has_action( 'listings_input_' . $type ) ) {
				do_action( 'listings_input_' . $type, $key, $field );
			} elseif ( method_exists( $this, 'input_' . $type ) ) {
				call_user_func( array( $this, 'input_' . $type ), $key, $field );
			}
		}

		do_action( 'listings_restaurants_restaurant_listing_data_end', $thepostid );

		echo '</div>';
	}

	/**
	 * save_post function.
	 *
	 * @access public
	 * @param mixed $post_id
	 * @param mixed $post
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
		if ( is_int( wp_is_post_revision( $post ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post ) ) ) return;
		if ( empty($_POST['listings_restaurants_nonce']) || ! wp_verify_nonce( $_POST['listings_restaurants_nonce'], 'save_meta_data' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( $post->post_type != 'restaurant_listing' ) return;

		do_action( 'listings_restaurants_save_restaurant_listing', $post_id, $post );
	}

	/**
	 * save_restaurant_listing_data function.
	 *
	 * @access public
	 * @param mixed $post_id
	 * @param mixed $post
	 * @return void
	 */
	public function save_restaurant_listing_data( $post_id, $post ) {
		global $wpdb;

		// These need to exist
		add_post_meta( $post_id, '_filled', 0, true );
		add_post_meta( $post_id, '_featured', 0, true );

		// Save fields
		foreach ( $this->restaurant_listing_fields() as $key => $field ) {
			// Expirey date
			if ( '_restaurant_expires' === $key ) {
				if ( ! empty( $_POST[ $key ] ) ) {
					update_post_meta( $post_id, $key, date( 'Y-m-d', strtotime( sanitize_text_field( $_POST[ $key ] ) ) ) );
				} else {
					update_post_meta( $post_id, $key, '' );
				}
			}

			// Locations
			elseif ( '_restaurant_location' === $key ) {
				if ( update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) ) ) {
					// Location data will be updated by hooked in methods
				} elseif ( apply_filters( 'listings_restaurants_geolocation_enabled', true ) && ! Geocode::has_location_data( $post_id ) ) {
					Geocode::generate_location_data( $post_id, sanitize_text_field( $_POST[ $key ] ) );
				}
			}

			elseif ( '_restaurant_author' === $key ) {
				$wpdb->update( $wpdb->posts, array( 'post_author' => $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : 0 ), array( 'ID' => $post_id ) );
			}

			elseif ( '_application' === $key ) {
				update_post_meta( $post_id, $key, sanitize_text_field( urldecode( $_POST[ $key ] ) ) );
			}

			// Everything else
			else {
				$type = ! empty( $field['type'] ) ? $field['type'] : '';

				switch ( $type ) {
					case 'textarea' :
						update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) );
					break;
					case 'checkbox' :
						if ( isset( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, 1 );
						} else {
							update_post_meta( $post_id, $key, 0 );
						}
					break;
					default :
						if ( ! isset( $_POST[ $key ] ) ) {
							continue;
						} elseif ( is_array( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) );
						} else {
							update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
						}
					break;
				}
			}
		}
	}
}