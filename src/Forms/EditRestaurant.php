<?php

namespace Listings\Restaurants\Forms;

class EditRestaurant extends SubmitRestaurant {

	public $form_name           = 'edit-restaurant';

	/** @var EditRestaurant The single instance of the class */
	protected static $_instance = null;

	/**
	 * Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->restaurant_id = ! empty( $_REQUEST['restaurant_id'] ) ? absint( $_REQUEST[ 'restaurant_id' ] ) : 0;

		if  ( ! listings_user_can_edit_listing( $this->restaurant_id ) ) {
			$this->restaurant_id = 0;
		}
	}

	/**
	 * Get the submitted restaurant ID
	 * @return int
	 */
	public function get_restaurant_id() {
		return absint( $this->restaurant_id );
	}

	/**
	 * output function.
	 */
	public function output( $atts = array() ) {
		$this->submit_handler();
		$this->submit();
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$restaurant = get_post( $this->restaurant_id );

		if ( empty( $this->restaurant_id  ) || ( $restaurant->post_status !== 'publish' && ! listings_user_can_edit_pending_submissions() ) ) {
			echo wpautop( __( 'Invalid listing', 'restaurants-listings' ) );
			return;
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {
					if ( 'restaurant_title' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $restaurant->post_title;

					} elseif ( 'restaurant_description' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $restaurant->post_content;

					} elseif ( 'company_logo' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = has_post_thumbnail( $restaurant->ID ) ? get_post_thumbnail_id( $restaurant->ID ) : get_post_meta( $restaurant->ID, '_' . $key, true );

					} elseif ( ! empty( $field['taxonomy'] ) ) {
						$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $restaurant->ID, $field['taxonomy'], array( 'fields' => 'ids' ) );

					} else {
						$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $restaurant->ID, '_' . $key, true );
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_restaurant_form_fields_get_restaurant_data', $this->fields, $restaurant );

		wp_enqueue_script( 'listings-restaurants-restaurant-submission' );

		listings_get_template( 'restaurant-submit.php', array(
			'form'               => $this->form_name,
			'restaurant_id'             => $this->get_restaurant_id(),
			'action'             => $this->get_action(),
			'restaurant_fields'         => $this->get_fields( 'restaurant' ),
			'company_fields'     => $this->get_fields( 'company' ),
			'step'               => $this->get_step(),
			'submit_button_text' => __( 'Save changes', 'restaurants-listings' )
			) );
	}

	/**
	 * Submit Step is posted
	 */
	public function submit_handler() {
		if ( empty( $_POST['submit_restaurant'] ) ) {
			return;
		}

		try {

			// Get posted values
			$values = $this->get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new \Exception( $return->get_error_message() );
			}

			// Update the restaurant
			$this->save_restaurant( $values['restaurant']['restaurant_title'], $values['restaurant']['restaurant_description'], '', $values, false );
			$this->update_restaurant_data( $values );

			// Successful
			switch ( get_post_status( $this->restaurant_id ) ) {
				case 'publish' :
					echo '<div class="listings-message">' . __( 'Your changes have been saved.', 'restaurants-listings' ) . ' <a href="' . get_permalink( $this->restaurant_id ) . '">' . __( 'View &rarr;', 'restaurants-listings' ) . '</a>' . '</div>';
				break;
				default :
					echo '<div class="listings-message">' . __( 'Your changes have been saved.', 'restaurants-listings' ) . '</div>';
				break;
			}

		} catch ( \Exception $e ) {
			echo '<div class="listings-error">' . $e->getMessage() . '</div>';
			return;
		}
	}
}
