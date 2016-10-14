<?php

namespace Listings\Restaurants;

class PostTypes {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 0 );
		add_filter( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'listings_restaurants_check_for_expired_jobs', array( $this, 'check_for_expired_jobs' ) );
		add_action( 'listings_restaurants_delete_old_previews', array( $this, 'delete_old_previews' ) );

		add_action( 'pending_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'preview_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'draft_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'auto-draft_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'expired_to_publish', array( $this, 'set_expiry' ) );

		add_filter( 'the_restaurant_description', 'wptexturize'        );
		add_filter( 'the_restaurant_description', 'convert_smilies'    );
		add_filter( 'the_restaurant_description', 'convert_chars'      );
		add_filter( 'the_restaurant_description', 'wpautop'            );
		add_filter( 'the_restaurant_description', 'shortcode_unautop'  );
		add_filter( 'the_restaurant_description', 'prepend_attachment' );
		if ( ! empty( $GLOBALS['wp_embed'] ) ) {
			add_filter( 'the_restaurant_description', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
			add_filter( 'the_restaurant_description', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		}

		add_action( 'listings_restaurants_application_details_email', array( $this, 'application_details_email' ) );
		add_action( 'listings_restaurants_application_details_url', array( $this, 'application_details_url' ) );

		add_filter( 'wp_insert_post_data', array( $this, 'fix_post_name' ), 10, 2 );
		add_action( 'add_post_meta', array( $this, 'maybe_add_geolocation_data' ), 10, 3 );
		add_action( 'update_post_meta', array( $this, 'update_post_meta' ), 10, 4 );
		add_action( 'wp_insert_post', array( $this, 'maybe_add_default_meta_data' ), 10, 2 );

		// WP ALL Import
		add_action( 'pmxi_saved_post', array( $this, 'pmxi_saved_post' ), 10, 1 );

		// RP4WP
		add_filter( 'rp4wp_get_template', array( $this, 'rp4wp_template' ), 10, 3 );
		add_filter( 'rp4wp_related_meta_fields', array( $this, 'rp4wp_related_meta_fields' ), 10, 3 );
		add_filter( 'rp4wp_related_meta_fields_weight', array( $this, 'rp4wp_related_meta_fields_weight' ), 10, 3 );

		// Single job content
		$this->restaurant_content_filter( true );
	}

	/**
	 * register_post_types function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_post_types() {
		if ( post_type_exists( "restaurant_listing" ) )
			return;

		$admin_capability = 'manage_restaurant_listings';

		/**
		 * Taxonomies
		 */
		if ( get_option( 'listings_restaurants_enable_categories' ) ) {
			$singular  = __( 'Job category', 'restaurants-listings' );
			$plural    = __( 'Job categories', 'restaurants-listings' );

			if ( current_theme_supports( 'listings-restaurants-templates' ) ) {
				$rewrite   = array(
					'slug'         => _x( 'job-category', 'Job category slug - resave permalinks after changing this', 'restaurants-listings' ),
					'with_front'   => false,
					'hierarchical' => false
				);
				$public    = true;
			} else {
				$rewrite   = false;
				$public    = false;
			}

			register_taxonomy( "restaurant_listing_category",
				apply_filters( 'register_taxonomy_restaurant_listing_category_object_type', array( 'restaurant_listing' ) ),
	       	 	apply_filters( 'register_taxonomy_restaurant_listing_category_args', array(
		            'hierarchical' 			=> true,
		            'update_count_callback' => '_update_post_term_count',
		            'label' 				=> $plural,
		            'labels' => array(
						'name'              => $plural,
						'singular_name'     => $singular,
						'menu_name'         => ucwords( $plural ),
						'search_items'      => sprintf( __( 'Search %s', 'restaurants-listings' ), $plural ),
						'all_items'         => sprintf( __( 'All %s', 'restaurants-listings' ), $plural ),
						'parent_item'       => sprintf( __( 'Parent %s', 'restaurants-listings' ), $singular ),
						'parent_item_colon' => sprintf( __( 'Parent %s:', 'restaurants-listings' ), $singular ),
						'edit_item'         => sprintf( __( 'Edit %s', 'restaurants-listings' ), $singular ),
						'update_item'       => sprintf( __( 'Update %s', 'restaurants-listings' ), $singular ),
						'add_new_item'      => sprintf( __( 'Add New %s', 'restaurants-listings' ), $singular ),
						'new_item_name'     => sprintf( __( 'New %s Name', 'restaurants-listings' ),  $singular )
	            	),
		            'show_ui' 				=> true,
		            'public' 	     		=> $public,
		            'capabilities'			=> array(
		            	'manage_terms' 		=> $admin_capability,
		            	'edit_terms' 		=> $admin_capability,
		            	'delete_terms' 		=> $admin_capability,
		            	'assign_terms' 		=> $admin_capability,
		            ),
		            'rewrite' 				=> $rewrite,
		        ) )
		    );
		}

	    $singular  = __( 'Job type', 'restaurants-listings' );
		$plural    = __( 'Job types', 'restaurants-listings' );

		if ( current_theme_supports( 'listings-restaurants-templates' ) ) {
			$rewrite   = array(
				'slug'         => _x( 'job-type', 'Job type slug - resave permalinks after changing this', 'restaurants-listings' ),
				'with_front'   => false,
				'hierarchical' => false
			);
			$public    = true;
		} else {
			$rewrite   = false;
			$public    = false;
		}

		register_taxonomy( "restaurant_listing_type",
			apply_filters( 'register_taxonomy_restaurant_listing_type_object_type', array( 'restaurant_listing' ) ),
	        apply_filters( 'register_taxonomy_restaurant_listing_type_args', array(
	            'hierarchical' 			=> true,
	            'label' 				=> $plural,
	            'labels' => array(
                    'name' 				=> $plural,
                    'singular_name' 	=> $singular,
                    'menu_name'         => ucwords( $plural ),
                    'search_items' 		=> sprintf( __( 'Search %s', 'restaurants-listings' ), $plural ),
                    'all_items' 		=> sprintf( __( 'All %s', 'restaurants-listings' ), $plural ),
                    'parent_item' 		=> sprintf( __( 'Parent %s', 'restaurants-listings' ), $singular ),
                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'restaurants-listings' ), $singular ),
                    'edit_item' 		=> sprintf( __( 'Edit %s', 'restaurants-listings' ), $singular ),
                    'update_item' 		=> sprintf( __( 'Update %s', 'restaurants-listings' ), $singular ),
                    'add_new_item' 		=> sprintf( __( 'Add New %s', 'restaurants-listings' ), $singular ),
                    'new_item_name' 	=> sprintf( __( 'New %s Name', 'restaurants-listings' ),  $singular )
            	),
	            'show_ui' 				=> true,
	            'public' 			    => $public,
	            'capabilities'			=> array(
	            	'manage_terms' 		=> $admin_capability,
	            	'edit_terms' 		=> $admin_capability,
	            	'delete_terms' 		=> $admin_capability,
	            	'assign_terms' 		=> $admin_capability,
	            ),
	           'rewrite' 				=> $rewrite,
	        ) )
	    );

	    /**
		 * Post types
		 */
		$singular  = __( 'Job', 'restaurants-listings' );
		$plural    = __( 'Jobs', 'restaurants-listings' );

		if ( current_theme_supports( 'listings-restaurants-templates' ) ) {
			$has_archive = _x( 'jobs', 'Post type archive slug - resave permalinks after changing this', 'restaurants-listings' );
		} else {
			$has_archive = false;
		}

		$rewrite     = array(
			'slug'       => _x( 'job', 'Job permalink - resave permalinks after changing this', 'restaurants-listings' ),
			'with_front' => false,
			'feeds'      => true,
			'pages'      => false
		);

		register_post_type( "restaurant_listing",
			apply_filters( "register_post_type_restaurant_listing", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => __( 'Job Listings', 'restaurants-listings' ),
					'all_items'             => sprintf( __( 'All %s', 'restaurants-listings' ), $plural ),
					'add_new' 				=> __( 'Add New', 'restaurants-listings' ),
					'add_new_item' 			=> sprintf( __( 'Add %s', 'restaurants-listings' ), $singular ),
					'edit' 					=> __( 'Edit', 'restaurants-listings' ),
					'edit_item' 			=> sprintf( __( 'Edit %s', 'restaurants-listings' ), $singular ),
					'new_item' 				=> sprintf( __( 'New %s', 'restaurants-listings' ), $singular ),
					'view' 					=> sprintf( __( 'View %s', 'restaurants-listings' ), $singular ),
					'view_item' 			=> sprintf( __( 'View %s', 'restaurants-listings' ), $singular ),
					'search_items' 			=> sprintf( __( 'Search %s', 'restaurants-listings' ), $plural ),
					'not_found' 			=> sprintf( __( 'No %s found', 'restaurants-listings' ), $plural ),
					'not_found_in_trash' 	=> sprintf( __( 'No %s found in trash', 'restaurants-listings' ), $plural ),
					'parent' 				=> sprintf( __( 'Parent %s', 'restaurants-listings' ), $singular ),
					'featured_image'        => __( 'Company Logo', 'restaurants-listings' ),
					'set_featured_image'    => __( 'Set company logo', 'restaurants-listings' ),
					'remove_featured_image' => __( 'Remove company logo', 'restaurants-listings' ),
					'use_featured_image'    => __( 'Use as company logo', 'restaurants-listings' ),
				),
				'description' => sprintf( __( 'This is where you can create and manage %s.', 'restaurants-listings' ), $plural ),
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'restaurant_listing',
				'map_meta_cap'          => true,
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> false,
				'hierarchical' 			=> false,
				'rewrite' 				=> $rewrite,
				'query_var' 			=> true,
				'supports' 				=> array( 'title', 'editor', 'custom-fields', 'publicize', 'thumbnail' ),
				'has_archive' 			=> $has_archive,
				'show_in_nav_menus' 	=> false
			) )
		);

		/**
		 * Feeds
		 */
		add_feed( 'restaurant_feed', array( $this, 'restaurant_feed' ) );

		/**
		 * Post status
		 */
		register_post_status( 'expired', array(
			'label'                     => _x( 'Expired', 'post status', 'restaurants-listings' ),
			'public'                    => true,
			'protected'                 => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'restaurants-listings' ),
		) );
		register_post_status( 'preview', array(
			'label'                     => _x( 'Preview', 'post status', 'restaurants-listings' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Preview <span class="count">(%s)</span>', 'Preview <span class="count">(%s)</span>', 'restaurants-listings' ),
		) );
	}

	/**
	 * Change label
	 */
	public function admin_head() {
		global $menu;

		$plural     = __( 'Job Listings', 'restaurants-listings' );
		$count_jobs = wp_count_posts( 'restaurant_listing', 'readable' );

		if ( ! empty( $menu ) && is_array( $menu ) ) {
			foreach ( $menu as $key => $menu_item ) {
				if ( strpos( $menu_item[0], $plural ) === 0 ) {
					if ( $order_count = $count_jobs->pending ) {
						$menu[ $key ][0] .= " <span class='awaiting-mod update-plugins count-$order_count'><span class='pending-count'>" . number_format_i18n( $count_jobs->pending ) . "</span></span>" ;
					}
					break;
				}
			}
		}
	}

	/**
	 * Toggle filter on and off
	 */
	private function restaurant_content_filter( $enable ) {
		if ( ! $enable ) {
			remove_filter( 'the_content', array( $this, 'restaurant_content' ) );
		} else {
			add_filter( 'the_content', array( $this, 'restaurant_content' ) );
		}
	}

	/**
	 * Add extra content before/after the post for single job listings.
	 */
	public function restaurant_content( $content ) {
		global $post;

		if ( ! is_singular( 'restaurant_listing' ) || ! in_the_loop() || 'restaurant_listing' !== $post->post_type ) {
			return $content;
		}

		ob_start();

		$this->restaurant_content_filter( false );

		do_action( 'restaurant_content_start' );

		listings_get_template_part( 'content-single', 'restaurant_listing' );

		do_action( 'restaurant_content_end' );

		$this->restaurant_content_filter( true );

		return apply_filters( 'listings_restaurants_single_restaurant_content', ob_get_clean(), $post );
	}

	/**
	 * Job listing feeds
	 */
	public function restaurant_feed() {
		$query_args = array(
			'post_type'           => 'restaurant_listing',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => isset( $_GET['posts_per_page'] ) ? absint( $_GET['posts_per_page'] ) : 10,
			'tax_query'           => array(),
			'meta_query'          => array()
		);

		if ( ! empty( $_GET['search_location'] ) ) {
			$location_meta_keys = array( 'geolocation_formatted_address', '_restaurant_location', 'geolocation_state_long' );
			$location_search    = array( 'relation' => 'OR' );
			foreach ( $location_meta_keys as $meta_key ) {
				$location_search[] = array(
					'key'     => $meta_key,
					'value'   => sanitize_text_field( $_GET['search_location'] ),
					'compare' => 'like'
				);
			}
			$query_args['meta_query'][] = $location_search;
		}

		if ( ! empty( $_GET['restaurant_types'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'restaurant_listing_type',
				'field'    => 'slug',
				'terms'    => explode( ',', sanitize_text_field( $_GET['restaurant_types'] ) ) + array( 0 )
			);
		}

		if ( ! empty( $_GET['restaurant_categories'] ) ) {
			$cats     = explode( ',', sanitize_text_field( $_GET['restaurant_categories'] ) ) + array( 0 );
			$field    = is_numeric( $cats ) ? 'term_id' : 'slug';
			$operator = 'all' === get_option( 'listings_restaurants_category_filter_type', 'all' ) && sizeof( $args['search_categories'] ) > 1 ? 'AND' : 'IN';
			$query_args['tax_query'][] = array(
				'taxonomy'         => 'restaurant_listing_category',
				'field'            => $field,
				'terms'            => $cats,
				'include_children' => $operator !== 'AND' ,
				'operator'         => $operator
			);
		}

		if ( $listings_keyword = sanitize_text_field( $_GET['search_keywords'] ) ) {
			$query_args['_keyword'] = $listings_keyword; // Does nothing but needed for unique hash
			add_filter( 'posts_clauses', 'get_restaurant_listings_keyword_search' );
		}

		if ( empty( $query_args['meta_query'] ) ) {
			unset( $query_args['meta_query'] );
		}

		if ( empty( $query_args['tax_query'] ) ) {
			unset( $query_args['tax_query'] );
		}

		query_posts( apply_filters( 'restaurant_feed_args', $query_args ) );
		add_action( 'rss2_ns', array( $this, 'restaurant_feed_namespace' ) );
		add_action( 'rss2_item', array( $this, 'restaurant_feed_item' ) );
		do_feed_rss2( false );
	}

	/**
	 * Add a custom namespace to the job feed
	 */
	public function restaurant_feed_namespace() {
		echo 'xmlns:restaurant_listing="' .  site_url() . '"' . "\n";
	}

	/**
	 * Add custom data to the job feed
	 */
	public function restaurant_feed_item() {
		$post_id  = get_the_ID();
		$location = listings_restaurants_get_the_restaurant_location( $post_id );
		$restaurant_type = listings_restaurants_get_the_restaurant_type( $post_id );
		$company  = listings_restaurants_get_the_company_name( $post_id );

		if ( $location ) {
			echo "<restaurant_listing:location><![CDATA[" . esc_html( $location ) . "]]></restaurant_listing:location>\n";
		}
		if ( $restaurant_type ) {
			echo "<restaurant_listing:restaurant_type><![CDATA[" . esc_html( $restaurant_type->name ) . "]]></restaurant_listing:restaurant_type>\n";
		}
		if ( $company ) {
			echo "<restaurant_listing:company><![CDATA[" . esc_html( $company ) . "]]></restaurant_listing:company>\n";
		}
	}

	/**
	 * Expire jobs
	 */
	public function check_for_expired_jobs() {
		global $wpdb;

		// Change status to expired
		$restaurant_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = '_restaurant_expires'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'restaurant_listing'
		", date( 'Y-m-d', current_time( 'timestamp' ) ) ) );

		if ( $restaurant_ids ) {
			foreach ( $restaurant_ids as $restaurant_id ) {
				$restaurant_data       = array();
				$restaurant_data['ID'] = $restaurant_id;
				$restaurant_data['post_status'] = 'expired';
				wp_update_post( $restaurant_data );
			}
		}

		// Delete old expired jobs
		if ( apply_filters( 'listings_restaurants_delete_expired_jobs', false ) ) {
			$restaurant_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'restaurant_listing'
				AND posts.post_modified < %s
				AND posts.post_status = 'expired'
			", date( 'Y-m-d', strtotime( '-' . apply_filters( 'listings_restaurants_delete_expired_jobs_days', 30 ) . ' days', current_time( 'timestamp' ) ) ) ) );

			if ( $restaurant_ids ) {
				foreach ( $restaurant_ids as $restaurant_id ) {
					wp_trash_post( $restaurant_id );
				}
			}
		}
	}

	/**
	 * Delete old previewed jobs after 30 days to keep the DB clean
	 */
	public function delete_old_previews() {
		global $wpdb;

		// Delete old expired jobs
		$restaurant_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT posts.ID FROM {$wpdb->posts} as posts
			WHERE posts.post_type = 'restaurant_listing'
			AND posts.post_modified < %s
			AND posts.post_status = 'preview'
		", date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) ) ) );

		if ( $restaurant_ids ) {
			foreach ( $restaurant_ids as $restaurant_id ) {
				wp_delete_post( $restaurant_id, true );
			}
		}
	}

	/**
	 * Typo -.-
	 */
	public function set_expirey( $post ) {
		$this->set_expiry( $post );
	}

	/**
	 * Set expirey date when job status changes
	 */
	public function set_expiry( $post ) {
		if ( $post->post_type !== 'restaurant_listing' ) {
			return;
		}

		// See if it is already set
		if ( metadata_exists( 'post', $post->ID, '_restaurant_expires' ) ) {
			$expires = get_post_meta( $post->ID, '_restaurant_expires', true );
			if ( $expires && strtotime( $expires ) < current_time( 'timestamp' ) ) {
				update_post_meta( $post->ID, '_restaurant_expires', '' );
				$_POST[ '_restaurant_expires' ] = '';
			}
			return;
		}

		// No metadata set so we can generate an expiry date
		// See if the user has set the expiry manually:
		if ( ! empty( $_POST[ '_restaurant_expires' ] ) ) {
			update_post_meta( $post->ID, '_restaurant_expires', date( 'Y-m-d', strtotime( sanitize_text_field( $_POST[ '_restaurant_expires' ] ) ) ) );

		// No manual setting? Lets generate a date
		} else {
			$expires = listings_restaurants_calculate_restaurant_expiry( $post->ID );
			update_post_meta( $post->ID, '_restaurant_expires', $expires );

			// In case we are saving a post, ensure post data is updated so the field is not overridden
			if ( isset( $_POST[ '_restaurant_expires' ] ) ) {
				$_POST[ '_restaurant_expires' ] = $expires;
			}
		}
	}

	/**
	 * The application content when the application method is an email
	 */
	public function application_details_email( $apply ) {
		listings_get_template( 'restaurant-application-email.php', array( 'apply' => $apply ) );
	}

	/**
	 * The application content when the application method is a url
	 */
	public function application_details_url( $apply ) {
		listings_get_template( 'restaurant-application-url.php', array( 'apply' => $apply ) );
	}

	/**
	 * Fix post name when wp_update_post changes it
	 * @param  array $data
	 * @return array
	 */
	public function fix_post_name( $data, $postarr ) {
		 if ( 'restaurant_listing' === $data['post_type'] && 'pending' === $data['post_status'] && ! current_user_can( 'publish_posts' ) ) {
				$data['post_name'] = $postarr['post_name'];
		 }
		 return $data;
	}

	/**
	 * Generate location data if a post is added
	 * @param  int $post_id
	 * @param  array $post
	 */
	public function maybe_add_geolocation_data( $object_id, $meta_key, $meta_value ) {
		if ( '_restaurant_location' !== $meta_key || 'restaurant_listing' !== get_post_type( $object_id ) ) {
			return;
		}
		do_action( 'listings_restaurants_restaurant_location_edited', $object_id, $meta_value );
	}

	/**
	 * Triggered when updating meta on a job listing
	 */
	public function update_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'restaurant_listing' === get_post_type( $object_id ) ) {
			switch ( $meta_key ) {
				case '_restaurant_location' :
					$this->maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value );
				break;
				case '_featured' :
					$this->maybe_update_menu_order( $meta_id, $object_id, $meta_key, $meta_value );
				break;
			}
		}
	}

	/**
	 * Generate location data if a post is updated
	 */
	public function maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value ) {
		do_action( 'listings_restaurants_restaurant_location_edited', $object_id, $meta_value );
	}

	/**
	 * Maybe set menu_order if the featured status of a job is changed
	 */
	public function maybe_update_menu_order( $meta_id, $object_id, $meta_key, $meta_value ) {
		global $wpdb;

		if ( '1' == $meta_value ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => -1 ), array( 'ID' => $object_id ) );
		} else {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => 0 ), array( 'ID' => $object_id, 'menu_order' => -1 ) );
		}

		clean_post_cache( $object_id );
	}

	/**
	 * Legacy
	 * @deprecated 1.19.1
	 */
	public function maybe_generate_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value ) {
		$this->maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Maybe set default meta data for job listings
	 * @param  int $post_id
	 * @param  \WP_Post $post
	 */
	public function maybe_add_default_meta_data( $post_id, $post = '' ) {
		if ( empty( $post ) || 'restaurant_listing' === $post->post_type ) {
			add_post_meta( $post_id, '_filled', 0, true );
			add_post_meta( $post_id, '_featured', 0, true );
		}
	}

	/**
	 * After importing via WP ALL Import, add default meta data
	 * @param  int $post_id
	 */
	public function pmxi_saved_post( $post_id ) {
		if ( 'restaurant_listing' === get_post_type( $post_id ) ) {
			$this->maybe_add_default_meta_data( $post_id );
			if ( ! Geocode::has_location_data( $post_id ) && ( $location = get_post_meta( $post_id, '_restaurant_location', true ) ) ) {
				Geocode::generate_location_data( $post_id, $location );
			}
		}
	}

	/**
	 * Replace RP4WP template with the template from Listings
	 * @param  string $located
	 * @param  string $template_name
	 * @param  array $args
	 * @return string
	 */
	public function rp4wp_template( $located, $template_name, $args ) {
		if ( 'related-post-default.php' === $template_name && 'restaurant_listing' === $args['related_post']->post_type ) {
			return LISTINGS_PLUGIN_DIR . '/templates/content-restaurant_listing.php';
		}
		return $located;
	}

	/**
	 * Add meta fields for RP4WP to relate jobs by
	 * @param  array $meta_fields
	 * @param  int $post_id
	 * @param  \WP_Post $post
	 * @return array
	 */
	public function rp4wp_related_meta_fields( $meta_fields, $post_id, $post ) {
		if ( 'restaurant_listing' === $post->post_type ) {
			$meta_fields[] = '_company_name';
			$meta_fields[] = '_restaurant_location';
		}
		return $meta_fields;
	}

	/**
	 * Add meta fields for RP4WP to relate jobs by
	 * @param  int $weight
	 * @param  \WP_Post $post
	 * @param  string $meta_field
	 * @return int
	 */
	public function rp4wp_related_meta_fields_weight( $weight, $post, $meta_field ) {
		if ( 'restaurant_listing' === $post->post_type ) {
			$weight = 100;
		}
		return $weight;
	}
}
