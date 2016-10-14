<?php

namespace Listings\Restaurants;

use Listings\Restaurants\Forms\EditRestaurant;
use Listings\Restaurants\Forms\SubmitRestaurant;

class Shortcodes {

	private $restaurant_dashboard_message = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'shortcode_action_handler' ) );
		add_action( 'listings_restaurants_restaurant_dashboard_content_edit', array( $this, 'edit_restaurant' ) );
		add_action( 'listings_restaurants_restaurant_filters_end', array( $this, 'restaurant_filter_restaurant_types' ), 20 );
		add_action( 'listings_restaurants_restaurant_filters_end', array( $this, 'restaurant_filter_results' ), 30 );
		add_action( 'listings_restaurants_output_restaurants_no_results', array( $this, 'output_no_results' ) );
		add_shortcode( 'submit_restaurant_form', array( $this, 'submit_restaurant_form' ) );
		add_shortcode( 'restaurant_dashboard', array( $this, 'restaurant_dashboard' ) );
		add_shortcode( 'restaurants', array( $this, 'output_restaurants' ) );
		add_shortcode( 'restaurant', array( $this, 'output_restaurant' ) );
		add_shortcode( 'restaurant_summary', array( $this, 'output_restaurant_summary' ) );
		add_shortcode( 'restaurant_apply', array( $this, 'output_restaurant_apply' ) );
	}

	/**
	 * Handle actions which need to be run before the shortcode e.g. post actions
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( is_page() && strstr( $post->post_content, '[restaurant_dashboard' ) ) {
			$this->restaurant_dashboard_handler();
		}
	}

	/**
	 * Show the restaurant submission form
	 */
	public function submit_restaurant_form( $atts = array() ) {
		$form = SubmitRestaurant::instance();
		ob_start();
		$form->output($atts);
		return ob_get_clean();
	}

	/**
	 * Handles actions on restaurant dashboard
	 */
	public function restaurant_dashboard_handler() {
		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'listings_restaurants_my_restaurant_actions' ) ) {

			$action = sanitize_title( $_REQUEST['action'] );
			$restaurant_id = absint( $_REQUEST['restaurant_id'] );

			try {
				// Get Restaurant
				$restaurant    = get_post( $restaurant_id );

				// Check ownership
				if ( ! listings_user_can_edit_listing( $restaurant_id ) ) {
					throw new \Exception( __( 'Invalid ID', 'restaurants-listings' ) );
				}

				switch ( $action ) {
					case 'mark_filled' :
						// Check status
						if ( $restaurant->_filled == 1 )
							throw new \Exception( __( 'This position has already been filled', 'restaurants-listings' ) );

						// Update
						update_post_meta( $restaurant_id, '_filled', 1 );

						// Message
						$this->restaurant_dashboard_message = '<div class="listings-message">' . sprintf( __( '%s has been filled', 'restaurants-listings' ), $restaurant->post_title ) . '</div>';
						break;
					case 'mark_not_filled' :
						// Check status
						if ( $restaurant->_filled != 1 ) {
							throw new \Exception( __( 'This position is not filled', 'restaurants-listings' ) );
						}

						// Update
						update_post_meta( $restaurant_id, '_filled', 0 );

						// Message
						$this->restaurant_dashboard_message = '<div class="listings-message">' . sprintf( __( '%s has been marked as not filled', 'restaurants-listings' ), $restaurant->post_title ) . '</div>';
						break;
					case 'delete' :
						// Trash it
						wp_trash_post( $restaurant_id );

						// Message
						$this->restaurant_dashboard_message = '<div class="listings-message">' . sprintf( __( '%s has been deleted', 'restaurants-listings' ), $restaurant->post_title ) . '</div>';

						break;
					case 'duplicate' :
						if ( ! listings_get_permalink( 'submit_restaurant_form' ) ) {
							throw new \Exception( __( 'Missing submission page.', 'restaurants-listings' ) );
						}

						$new_restaurant_id = listings_restaurants_duplicate_listing( $restaurant_id );

						if ( $new_restaurant_id ) {
							wp_redirect( add_query_arg( array( 'restaurant_id' => absint( $new_restaurant_id ) ), listings_get_permalink( 'submit_restaurant_form' ) ) );
							exit;
						}

						break;
					case 'relist' :
						if ( ! listings_get_permalink( 'submit_restaurant_form' ) ) {
							throw new \Exception( __( 'Missing submission page.', 'restaurants-listings' ) );
						}

						// redirect to post page
						wp_redirect( add_query_arg( array( 'restaurant_id' => absint( $restaurant_id ) ), listings_get_permalink( 'submit_restaurant_form' ) ) );
						exit;

						break;
					default :
						do_action( 'listings_restaurants_restaurant_dashboard_do_action_' . $action );
						break;
				}

				do_action( 'listings_restaurants_my_restaurant_do_action', $action, $restaurant_id );

			} catch ( \Exception $e ) {
				$this->restaurant_dashboard_message = '<div class="listings-error">' . $e->getMessage() . '</div>';
			}
		}
	}

	/**
	 * Shortcode which lists the logged in user's restaurants
	 */
	public function restaurant_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			ob_start();
			listings_get_template( 'restaurant-dashboard-login.php' );
			return ob_get_clean();
		}

		extract( shortcode_atts( array(
			'posts_per_page' => '25',
		), $atts ) );

		wp_enqueue_script( 'listings-restaurants-restaurant-dashboard' );

		ob_start();

		// If doing an action, show conditional content if needed....
		if ( ! empty( $_REQUEST['action'] ) ) {
			$action = sanitize_title( $_REQUEST['action'] );

			// Show alternative content if a plugin wants to
			if ( has_action( 'listings_restaurants_restaurant_dashboard_content_' . $action ) ) {
				do_action( 'listings_restaurants_restaurant_dashboard_content_' . $action, $atts );

				return ob_get_clean();
			}
		}

		// ....If not show the restaurant dashboard
		$args     = apply_filters( 'listings_restaurants_get_dashboard_restaurants_args', array(
			'post_type'           => 'restaurant_listing',
			'post_status'         => array( 'publish', 'expired', 'pending' ),
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $posts_per_page,
			'offset'              => ( max( 1, get_query_var('paged') ) - 1 ) * $posts_per_page,
			'orderby'             => 'date',
			'order'               => 'desc',
			'author'              => get_current_user_id()
		) );

		$restaurants = new \WP_Query;

		echo $this->restaurant_dashboard_message;

		$restaurant_dashboard_columns = apply_filters( 'listings_restaurants_restaurant_dashboard_columns', array(
			'restaurant_title' => __( 'Title', 'restaurants-listings' ),
			'filled'    => __( 'Filled?', 'restaurants-listings' ),
			'date'      => __( 'Date Posted', 'restaurants-listings' ),
			'expires'   => __( 'Listing Expires', 'restaurants-listings' )
		) );

		listings_get_template( 'restaurant-dashboard.php', array( 'restaurants' => $restaurants->query( $args ), 'max_num_pages' => $restaurants->max_num_pages, 'restaurant_dashboard_columns' => $restaurant_dashboard_columns ) );

		return ob_get_clean();
	}

	/**
	 * Edit restaurant form
	 */
	public function edit_restaurant() {
		$form = EditRestaurant::instance();
		$form->output();
	}

	/**
	 * output_restaurants function.
	 *
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	public function output_restaurants( $atts ) {
		ob_start();

		extract( $atts = shortcode_atts( apply_filters( 'listings_restaurants_output_restaurants_defaults', array(
			'per_page'                  => get_option( 'listings_restaurants_per_page' ),
			'orderby'                   => 'featured',
			'order'                     => 'DESC',

			// Filters + cats
			'show_filters'              => true,
			'show_categories'           => true,
			'show_category_multiselect' => get_option( 'listings_restaurants_enable_default_category_multiselect', false ),
			'show_pagination'           => false,
			'show_more'                 => true,

			// Limit what restaurants are shown based on category and type
			'categories'                => '',
			'restaurant_types'                 => '',
			'featured'                  => null, // True to show only featured, false to hide featured, leave null to show both.
			'filled'                    => null, // True to show only filled, false to hide filled, leave null to show both/use the settings.

			// Default values for filters
			'location'                  => '',
			'keywords'                  => '',
			'selected_category'         => '',
			'selected_restaurant_types'        => implode( ',', array_values( listings_restaurants_get_types( 'id=>slug' ) ) ),
		) ), $atts ) );

		if ( ! get_option( 'listings_restaurants_enable_categories' ) ) {
			$show_categories = false;
		}

		// String and bool handling
		$show_filters              = $this->string_to_bool( $show_filters );
		$show_categories           = $this->string_to_bool( $show_categories );
		$show_category_multiselect = $this->string_to_bool( $show_category_multiselect );
		$show_more                 = $this->string_to_bool( $show_more );
		$show_pagination           = $this->string_to_bool( $show_pagination );

		if ( ! is_null( $featured ) ) {
			$featured = ( is_bool( $featured ) && $featured ) || in_array( $featured, array( '1', 'true', 'yes' ) ) ? true : false;
		}

		if ( ! is_null( $filled ) ) {
			$filled = ( is_bool( $filled ) && $filled ) || in_array( $filled, array( '1', 'true', 'yes' ) ) ? true : false;
		}

		// Array handling
		$categories         = is_array( $categories ) ? $categories : array_filter( array_map( 'trim', explode( ',', $categories ) ) );
		$restaurant_types          = is_array( $restaurant_types ) ? $restaurant_types : array_filter( array_map( 'trim', explode( ',', $restaurant_types ) ) );
		$selected_restaurant_types = is_array( $selected_restaurant_types ) ? $selected_restaurant_types : array_filter( array_map( 'trim', explode( ',', $selected_restaurant_types ) ) );

		// Get keywords and location from querystring if set
		if ( ! empty( $_GET['search_keywords'] ) ) {
			$keywords = sanitize_text_field( $_GET['search_keywords'] );
		}
		if ( ! empty( $_GET['search_location'] ) ) {
			$location = sanitize_text_field( $_GET['search_location'] );
		}
		if ( ! empty( $_GET['search_category'] ) ) {
			$selected_category = sanitize_text_field( $_GET['search_category'] );
		}

		if ( $show_filters ) {

			listings_get_template( 'restaurant-filters.php', array( 'per_page' => $per_page, 'orderby' => $orderby, 'order' => $order, 'show_categories' => $show_categories, 'categories' => $categories, 'selected_category' => $selected_category, 'restaurant_types' => $restaurant_types, 'atts' => $atts, 'location' => $location, 'keywords' => $keywords, 'selected_restaurant_types' => $selected_restaurant_types, 'show_category_multiselect' => $show_category_multiselect ) );

			listings_get_template( 'restaurant_listing-start.php' );
			listings_get_template( 'restaurant_listing-end.php' );

			if ( ! $show_pagination && $show_more ) {
				echo '<a class="load_more_restaurants" href="#" style="display:none;"><strong>' . __( 'Load more listings', 'restaurants-listings' ) . '</strong></a>';
			}

		} else {

			$restaurants = listings_restaurants_get_listings( apply_filters( 'listings_restaurants_output_restaurants_args', array(
				'search_location'   => $location,
				'search_keywords'   => $keywords,
				'search_categories' => $categories,
				'restaurant_types'         => $restaurant_types,
				'orderby'           => $orderby,
				'order'             => $order,
				'posts_per_page'    => $per_page,
				'featured'          => $featured,
				'filled'            => $filled
			) ) );

			if ( $restaurants->have_posts() ) : ?>

				<?php listings_get_template( 'restaurant_listing-start.php' ); ?>

				<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>
					<?php listings_get_template_part( 'content', 'restaurant_listing' ); ?>
				<?php endwhile; ?>

				<?php listings_get_template( 'restaurant_listing-end.php' ); ?>

				<?php if ( $restaurants->found_posts > $per_page && $show_more ) : ?>

					<?php wp_enqueue_script( 'listings-ajax-filters' ); ?>

					<?php if ( $show_pagination ) : ?>
						<?php echo listings_get_listing_pagination( $restaurants->max_num_pages ); ?>
					<?php else : ?>
						<a class="load_more_restaurants" href="#"><strong><?php _e( 'Load more listings', 'restaurants-listings' ); ?></strong></a>
					<?php endif; ?>

				<?php endif; ?>

			<?php else :
				do_action( 'listings_restaurants_output_restaurants_no_results' );
			endif;

			wp_reset_postdata();
		}

		$data_attributes_string = '';
		$data_attributes        = array(
			'location'        => $location,
			'keywords'        => $keywords,
			'show_filters'    => $show_filters ? 'true' : 'false',
			'show_pagination' => $show_pagination ? 'true' : 'false',
			'per_page'        => $per_page,
			'orderby'         => $orderby,
			'order'           => $order,
			'categories'      => implode( ',', $categories ),
		);
		if ( ! is_null( $featured ) ) {
			$data_attributes[ 'featured' ] = $featured ? 'true' : 'false';
		}
		if ( ! is_null( $filled ) ) {
			$data_attributes[ 'filled' ]   = $filled ? 'true' : 'false';
		}
		foreach ( $data_attributes as $key => $value ) {
			$data_attributes_string .= 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		$restaurant_listings_output = apply_filters( 'listings_restaurants_restaurant_listings_output', ob_get_clean() );

		return '<div class="restaurant_listings" ' . $data_attributes_string . '>' . $restaurant_listings_output . '</div>';
	}

	/**
	 * Output some content when no results were found
	 */
	public function output_no_results() {
		listings_get_template( 'content-no-restaurants-found.php' );
	}

	/**
	 * Get string as a bool
	 * @param  string $value
	 * @return bool
	 */
	public function string_to_bool( $value ) {
		return ( is_bool( $value ) && $value ) || in_array( $value, array( '1', 'true', 'yes' ) ) ? true : false;
	}

	/**
	 * Show restaurant types
	 * @param  array $atts
	 */
	public function restaurant_filter_restaurant_types( $atts ) {
		extract( $atts );

		$restaurant_types          = array_filter( array_map( 'trim', explode( ',', $restaurant_types ) ) );
		$selected_restaurant_types = array_filter( array_map( 'trim', explode( ',', $selected_restaurant_types ) ) );

		listings_get_template( 'restaurant-filter-restaurant-types.php', array( 'restaurant_types' => $restaurant_types, 'atts' => $atts, 'selected_restaurant_types' => $selected_restaurant_types ) );
	}

	/**
	 * Show results div
	 */
	public function restaurant_filter_results() {
		echo '<div class="showing_restaurants"></div>';
	}

	/**
	 * output_restaurant function.
	 *
	 * @access public
	 * @param array $args
	 * @return string
	 */
	public function output_restaurant( $atts ) {
		extract( shortcode_atts( array(
			'id' => '',
		), $atts ) );

		if ( ! $id )
			return;

		ob_start();

		$args = array(
			'post_type'   => 'restaurant_listing',
			'post_status' => 'publish',
			'p'           => $id
		);

		$restaurants = new \WP_Query( $args );

		if ( $restaurants->have_posts() ) : ?>

			<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>

				<h1><?php the_title(); ?></h1>

				<?php listings_get_template_part( 'content-single', 'restaurant_listing' ); ?>

			<?php endwhile; ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="restaurant_shortcode single_restaurant_listing">' . ob_get_clean() . '</div>';
	}

	/**
	 * Restaurant Summary shortcode
	 *
	 * @access public
	 * @param array $args
	 * @return string
	 */
	public function output_restaurant_summary( $atts ) {
		extract( shortcode_atts( array(
			'id'       => '',
			'width'    => '250px',
			'align'    => 'left',
			'featured' => null, // True to show only featured, false to hide featured, leave null to show both (when leaving out id)
			'limit'    => 1
		), $atts ) );

		ob_start();

		$args = array(
			'post_type'   => 'restaurant_listing',
			'post_status' => 'publish'
		);

		if ( ! $id ) {
			$args['posts_per_page'] = $limit;
			$args['orderby']        = 'rand';
			if ( ! is_null( $featured ) ) {
				$args['meta_query'] = array( array(
					'key'     => '_featured',
					'value'   => '1',
					'compare' => $featured ? '=' : '!='
				) );
			}
		} else {
			$args['p'] = absint( $id );
		}

		$restaurants = new \WP_Query( $args );

		if ( $restaurants->have_posts() ) : ?>

			<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>

				<div class="restaurant_summary_shortcode align<?php echo $align ?>" style="width: <?php echo $width ? $width : 'auto'; ?>">

					<?php listings_get_template_part( 'content-summary', 'restaurant_listing' ); ?>

				</div>

			<?php endwhile; ?>

		<?php endif;

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Show the application area
	 */
	public function output_restaurant_apply( $atts ) {
		extract( shortcode_atts( array(
			'id'       => ''
		), $atts ) );

		ob_start();

		$args = array(
			'post_type'   => 'restaurant_listing',
			'post_status' => 'publish'
		);

		if ( ! $id ) {
			return '';
		} else {
			$args['p'] = absint( $id );
		}

		$restaurants = new \WP_Query( $args );

		if ( $restaurants->have_posts() ) : ?>

			<?php while ( $restaurants->have_posts() ) :
				$restaurants->the_post();
				$apply = listings_restaurants_get_application_method();
				?>

				<?php do_action( 'listings_restaurants_before_restaurant_apply_' . absint( $id ) ); ?>

				<?php if ( apply_filters( 'listings_restaurants_show_restaurant_apply_' . absint( $id ), true ) ) : ?>
					<div class="listings-restaurants-application-wrapper">
						<?php do_action( 'listings_restaurants_application_details_' . $apply->type, $apply ); ?>
					</div>
				<?php endif; ?>

				<?php do_action( 'listings_restaurants_after_restaurant_apply_' . absint( $id ) ); ?>

			<?php endwhile; ?>

		<?php endif;

		wp_reset_postdata();

		return ob_get_clean();
	}
}
