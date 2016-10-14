<?php

namespace Listings\Restaurants\Forms;

use Listings\Forms\Form;

class SubmitRestaurant extends Form {

	public    $form_name = 'submit-restaurant';
	protected $restaurant_id;
	protected $preview_job;

	/** @var SubmitRestaurant The single instance of the class */
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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'process' ) );

		$this->steps  = (array) apply_filters( 'submit_restaurant_steps', array(
			'submit' => array(
				'name'     => __( 'Submit Details', 'restaurants-listings' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10
				),
			'preview' => array(
				'name'     => __( 'Preview', 'restaurants-listings' ),
				'view'     => array( $this, 'preview' ),
				'handler'  => array( $this, 'preview_handler' ),
				'priority' => 20
			),
			'done' => array(
				'name'     => __( 'Done', 'restaurants-listings' ),
				'view'     => array( $this, 'done' ),
				'priority' => 30
			)
		) );

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		// Get step/job
		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( $_POST['step'], array_keys( $this->steps ) );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( $_GET['step'], array_keys( $this->steps ) );
		}

		$this->restaurant_id = ! empty( $_REQUEST['restaurant_id'] ) ? absint( $_REQUEST[ 'restaurant_id' ] ) : 0;

		// Allow resuming from cookie.
		if ( ! $this->restaurant_id && ! empty( $_COOKIE['listings-restaurants-submitting-restaurant-id'] ) && ! empty( $_COOKIE['listings-restaurants-submitting-restaurant-key'] ) ) {
			$restaurant_id     = absint( $_COOKIE['listings-restaurants-submitting-restaurant-id'] );
			$restaurant_status = get_post_status( $restaurant_id );

			if ( 'preview' === $restaurant_status && get_post_meta( $restaurant_id, '_submitting_key', true ) === $_COOKIE['listings-restaurants-submitting-restaurant-key'] ) {
				$this->restaurant_id = $restaurant_id;
			}
		}

		// Load job details
		if ( $this->restaurant_id ) {
			$restaurant_status = get_post_status( $this->restaurant_id );
			if ( 'expired' === $restaurant_status ) {
				if ( ! listings_user_can_edit_listing( $this->restaurant_id ) ) {
					$this->restaurant_id = 0;
					$this->step   = 0;
				}
			} elseif ( ! in_array( $restaurant_status, apply_filters( 'listings_restaurants_valid_submit_restaurant_statuses', array( 'preview' ) ) ) ) {
				$this->restaurant_id = 0;
				$this->step   = 0;
			}
		}
	}

	/**
	 * Get the submitted job ID
	 * @return int
	 */
	public function get_restaurant_id() {
		return absint( $this->restaurant_id );
	}

	/**
	 * init_fields function.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$allowed_application_method = get_option( 'listings_restaurants_allowed_application_method', '' );
		switch ( $allowed_application_method ) {
			case 'email' :
				$application_method_label       = __( 'Application email', 'restaurants-listings' );
				$application_method_placeholder = __( 'you@yourdomain.com', 'restaurants-listings' );
			break;
			case 'url' :
				$application_method_label       = __( 'Application URL', 'restaurants-listings' );
				$application_method_placeholder = __( 'http://', 'restaurants-listings' );
			break;
			default :
				$application_method_label       = __( 'Application email/URL', 'restaurants-listings' );
				$application_method_placeholder = __( 'Enter an email address or website URL', 'restaurants-listings' );
			break;
		}

		$this->fields = apply_filters( 'submit_restaurant_form_fields', array(
			'job' => array(
				'restaurant_title' => array(
					'label'       => __( 'Job Title', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1
				),
				'restaurant_location' => array(
					'label'       => __( 'Location', 'restaurants-listings' ),
					'description' => __( 'Leave this blank if the location is not important', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'e.g. "London"', 'restaurants-listings' ),
					'priority'    => 2
				),
				'restaurant_type' => array(
					'label'       => __( 'Job type', 'restaurants-listings' ),
					'type'        => 'term-select',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 3,
					'default'     => 'full-time',
					'taxonomy'    => 'restaurant_listing_type'
				),
				'restaurant_category' => array(
					'label'       => __( 'Job category', 'restaurants-listings' ),
					'type'        => 'term-multiselect',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 4,
					'default'     => '',
					'taxonomy'    => 'restaurant_listing_category'
				),
				'restaurant_description' => array(
					'label'       => __( 'Description', 'restaurants-listings' ),
					'type'        => 'wp-editor',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 5
				),
				'application' => array(
					'label'       => $application_method_label,
					'type'        => 'text',
					'required'    => true,
					'placeholder' => $application_method_placeholder,
					'priority'    => 6
				)
			),
			'company' => array(
				'company_name' => array(
					'label'       => __( 'Company name', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'Enter the name of the company', 'restaurants-listings' ),
					'priority'    => 1
				),
				'company_website' => array(
					'label'       => __( 'Website', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'http://', 'restaurants-listings' ),
					'priority'    => 2
				),
				'company_tagline' => array(
					'label'       => __( 'Tagline', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'Briefly describe your company', 'restaurants-listings' ),
					'maxlength'   => 64,
					'priority'    => 3
				),
				'company_video' => array(
					'label'       => __( 'Video', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'A link to a video about your company', 'restaurants-listings' ),
					'priority'    => 4
				),
				'company_twitter' => array(
					'label'       => __( 'Twitter username', 'restaurants-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( '@yourcompany', 'restaurants-listings' ),
					'priority'    => 5
				),
				'company_logo' => array(
					'label'       => __( 'Logo', 'restaurants-listings' ),
					'type'        => 'file',
					'required'    => false,
					'placeholder' => '',
					'priority'    => 6,
					'ajax'        => true,
					'multiple'    => false,
					'allowed_mime_types' => array(
						'jpg'  => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'gif'  => 'image/gif',
						'png'  => 'image/png'
					)
				)
			)
		) );

		if ( ! get_option( 'listings_restaurants_enable_categories' ) || wp_count_terms( 'restaurant_listing_category' ) == 0 ) {
			unset( $this->fields['job']['restaurant_category'] );
		}
	}

	/**
	 * Validate the posted fields
	 *
	 * @return bool on success, WP_ERROR on failure
	 */
	protected function validate_fields( $values ) {
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new \WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'restaurants-listings' ), $field['label'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = $values[ $group_key ][ $key ];
					} else {
						$check_value = empty( $values[ $group_key ][ $key ] ) ? array() : array( $values[ $group_key ][ $key ] );
					}
					foreach ( $check_value as $term ) {
						if ( ! term_exists( $term, $field['taxonomy'] ) ) {
							return new \WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'restaurants-listings' ), $field['label'] ) );
						}
					}
				}
				if ( 'file' === $field['type'] && ! empty( $field['allowed_mime_types'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( array( $values[ $group_key ][ $key ] ) );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							$file_url  = current( explode( '?', $file_url ) );
							$file_info = wp_check_filetype( $file_url );

							if ( ! is_numeric( $file_url ) && $file_info && ! in_array( $file_info['type'], $field['allowed_mime_types'] ) ) {
								throw new \Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'restaurants-listings' ), $field['label'], $file_info['ext'], implode( ', ', array_keys( $field['allowed_mime_types'] ) ) ) );
							}
						}
					}
				}
			}
		}

		// Application method
		if ( isset( $values['job']['application'] ) && ! empty( $values['job']['application'] ) ) {
			$allowed_application_method = get_option( 'listings_restaurants_allowed_application_method', '' );
			$values['job']['application'] = str_replace( ' ', '+', $values['job']['application'] );
			switch ( $allowed_application_method ) {
				case 'email' :
					if ( ! is_email( $values['job']['application'] ) ) {
						throw new \Exception( __( 'Please enter a valid application email address', 'restaurants-listings' ) );
					}
				break;
				case 'url' :
					// Prefix http if needed
					if ( ! strstr( $values['job']['application'], 'http:' ) && ! strstr( $values['job']['application'], 'https:' ) ) {
						$values['job']['application'] = 'http://' . $values['job']['application'];
					}
					if ( ! filter_var( $values['job']['application'], FILTER_VALIDATE_URL ) ) {
						throw new \Exception( __( 'Please enter a valid application URL', 'restaurants-listings' ) );
					}
				break;
				default :
					if ( ! is_email( $values['job']['application'] ) ) {
						// Prefix http if needed
						if ( ! strstr( $values['job']['application'], 'http:' ) && ! strstr( $values['job']['application'], 'https:' ) ) {
							$values['job']['application'] = 'http://' . $values['job']['application'];
						}
						if ( ! filter_var( $values['job']['application'], FILTER_VALIDATE_URL ) ) {
							throw new \Exception( __( 'Please enter a valid application email address or URL', 'restaurants-listings' ) );
						}
					}
				break;
			}
		}

		return apply_filters( 'submit_restaurant_form_validate_fields', true, $this->fields, $values );
	}

	/**
	 * restaurant_types function.
	 */
	private function restaurant_types() {
		$options = array();
		$terms   = listings_restaurants_get_types();
		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}
		return $options;
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$this->init_fields();

		// Load data if neccessary
		if ( $this->restaurant_id ) {
			$job = get_post( $this->restaurant_id );
			foreach ( $this->fields as $group_key => $group_fields ) {
				foreach ( $group_fields as $key => $field ) {
					switch ( $key ) {
						case 'restaurant_title' :
							$this->fields[ $group_key ][ $key ]['value'] = $job->post_title;
						break;
						case 'restaurant_description' :
							$this->fields[ $group_key ][ $key ]['value'] = $job->post_content;
						break;
						case 'restaurant_type' :
							$this->fields[ $group_key ][ $key ]['value'] = current( wp_get_object_terms( $job->ID, 'restaurant_listing_type', array( 'fields' => 'ids' ) ) );
						break;
						case 'restaurant_category' :
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $job->ID, 'restaurant_listing_category', array( 'fields' => 'ids' ) );
						break;
						case 'company_logo' :
							$this->fields[ $group_key ][ $key ]['value'] = has_post_thumbnail( $job->ID ) ? get_post_thumbnail_id( $job->ID ) : get_post_meta( $job->ID, '_' . $key, true );
						break;
						default:
							$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $job->ID, '_' . $key, true );
						break;
					}
				}
			}

			$this->fields = apply_filters( 'submit_restaurant_form_fields_get_restaurant_data', $this->fields, $job );

		// Get user meta
		} elseif ( is_user_logged_in() && empty( $_POST['submit_job'] ) ) {
			if ( ! empty( $this->fields['company'] ) ) {
				foreach ( $this->fields['company'] as $key => $field ) {
					$this->fields['company'][ $key ]['value'] = get_user_meta( get_current_user_id(), '_' . $key, true );
				}
			}
			if ( ! empty( $this->fields['job']['application'] ) ) {
				$allowed_application_method = get_option( 'listings_restaurants_allowed_application_method', '' );
				if ( $allowed_application_method !== 'url' ) {
					$current_user = wp_get_current_user();
					$this->fields['job']['application']['value'] = $current_user->user_email;
				}
			}
			$this->fields = apply_filters( 'submit_restaurant_form_fields_get_user_data', $this->fields, get_current_user_id() );
		}

		wp_enqueue_script( 'listings-restaurants-restaurant-submission' );

		listings_get_template( 'restaurant-submit.php', array(
			'form'               => $this->form_name,
			'restaurant_id'             => $this->get_restaurant_id(),
			'action'             => $this->get_action(),
			'restaurant_fields'         => $this->get_fields( 'job' ),
			'company_fields'     => $this->get_fields( 'company' ),
			'step'               => $this->get_step(),
			'submit_button_text' => apply_filters( 'submit_restaurant_form_submit_button_text', __( 'Preview', 'restaurants-listings' ) )
		) );
	}

	/**
	 * Submit Step is posted
	 */
	public function submit_handler() {
		try {
			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			if ( empty( $_POST['submit_job'] ) ) {
				return;
			}

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new \Exception( $return->get_error_message() );
			}

			// Account creation
			if ( ! is_user_logged_in() ) {
				$create_account = false;

				if ( listings_enable_registration() ) {
					if ( listings_user_requires_account() ) {
						if ( ! listings_generate_username_from_email() && empty( $_POST['create_account_username'] ) ) {
							throw new \Exception( __( 'Please enter a username.', 'restaurants-listings' ) );
						}
						if ( empty( $_POST['create_account_email'] ) ) {
							throw new \Exception( __( 'Please enter your email address.', 'restaurants-listings' ) );
						}
					}
					if ( ! empty( $_POST['create_account_email'] ) ) {
						$create_account = listings_create_account( array(
							'username' => empty( $_POST['create_account_username'] ) ? '' : $_POST['create_account_username'],
							'email'    => $_POST['create_account_email'],
							'role'     => get_option( 'listings_restaurants_registration_role' )
						) );
					}
				}

				if ( is_wp_error( $create_account ) ) {
					throw new \Exception( $create_account->get_error_message() );
				}
			}

			if ( listings_user_requires_account() && ! is_user_logged_in() ) {
				throw new \Exception( __( 'You must be signed in to post a new listing.' ) );
			}

			// Update the job
			$this->save_job( $values['job']['restaurant_title'], $values['job']['restaurant_description'], $this->restaurant_id ? '' : 'preview', $values );
			$this->update_restaurant_data( $values );

			// Successful, show next step
			$this->step ++;

		} catch ( \Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Update or create a job listing from posted data
	 *
	 * @param  string $post_title
	 * @param  string $post_content
	 * @param  string $status
	 * @param  array $values
	 * @param  bool $update_slug
	 */
	protected function save_job( $post_title, $post_content, $status = 'preview', $values = array(), $update_slug = true ) {
		$restaurant_data = array(
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'restaurant_listing',
			'comment_status' => 'closed'
		);

		if ( $update_slug ) {
			$restaurant_slug   = array();

			// Prepend with company name
			if ( apply_filters( 'submit_restaurant_form_prefix_post_name_with_company', true ) && ! empty( $values['company']['company_name'] ) ) {
				$restaurant_slug[] = $values['company']['company_name'];
			}

			// Prepend location
			if ( apply_filters( 'submit_restaurant_form_prefix_post_name_with_location', true ) && ! empty( $values['job']['restaurant_location'] ) ) {
				$restaurant_slug[] = $values['job']['restaurant_location'];
			}

			// Prepend with job type
			if ( apply_filters( 'submit_restaurant_form_prefix_post_name_with_restaurant_type', true ) && ! empty( $values['job']['restaurant_type'] ) ) {
				$restaurant_slug[] = $values['job']['restaurant_type'];
			}

			$restaurant_slug[]            = $post_title;
			$restaurant_data['post_name'] = sanitize_title( implode( '-', $restaurant_slug ) );
		}

		if ( $status ) {
			$restaurant_data['post_status'] = $status;
		}

		$restaurant_data = apply_filters( 'submit_restaurant_form_save_restaurant_data', $restaurant_data, $post_title, $post_content, $status, $values );

		if ( $this->restaurant_id ) {
			$restaurant_data['ID'] = $this->restaurant_id;
			wp_update_post( $restaurant_data );
		} else {
			$this->restaurant_id = wp_insert_post( $restaurant_data );

			if ( ! headers_sent() ) {
				$submitting_key = uniqid();

				setcookie( 'listings-restaurants-submitting-restaurant-id', $this->restaurant_id, false, COOKIEPATH, COOKIE_DOMAIN, false );
				setcookie( 'listings-restaurantssubmitting-restaurant-key', $submitting_key, false, COOKIEPATH, COOKIE_DOMAIN, false );

				update_post_meta( $this->restaurant_id, '_submitting_key', $submitting_key );
			}
		}
	}

	/**
	 * Create an attachment
	 * @param  string $attachment_url
	 * @return int attachment id
	 */
	protected function create_attachment( $attachment_url ) {
		include_once( ABSPATH . 'wp-admin/includes/image.php' );
		include_once( ABSPATH . 'wp-admin/includes/media.php' );

		$upload_dir     = wp_upload_dir();
		$attachment_url = str_replace( array( $upload_dir['baseurl'], WP_CONTENT_URL, site_url( '/' ) ), array( $upload_dir['basedir'], WP_CONTENT_DIR, ABSPATH ), $attachment_url );

		if ( empty( $attachment_url ) || ! is_string( $attachment_url ) ) {
			return 0;
		}

		$attachment     = array(
			'post_title'   => get_the_title( $this->restaurant_id ),
			'post_content' => '',
			'post_status'  => 'inherit',
			'post_parent'  => $this->restaurant_id,
			'guid'         => $attachment_url
		);

		if ( $info = wp_check_filetype( $attachment_url ) ) {
			$attachment['post_mime_type'] = $info['type'];
		}

		$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $this->restaurant_id );

		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
			return $attachment_id;
		}

		return 0;
	}

	/**
	 * Set job meta + terms based on posted values
	 *
	 * @param  array $values
	 */
	protected function update_restaurant_data( $values ) {
		// Set defaults
		add_post_meta( $this->restaurant_id, '_filled', 0, true );
		add_post_meta( $this->restaurant_id, '_featured', 0, true );

		$maybe_attach = array();

		// Loop fields and save meta and term data
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Save taxonomies
				if ( ! empty( $field['taxonomy'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( $this->restaurant_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( $this->restaurant_id, array( $values[ $group_key ][ $key ] ), $field['taxonomy'], false );
					}

				// Company logo is a featured image
				} elseif ( 'company_logo' === $key ) {
					$attachment_id = is_numeric( $values[ $group_key ][ $key ] ) ? absint( $values[ $group_key ][ $key ] ) : $this->create_attachment( $values[ $group_key ][ $key ] );
					set_post_thumbnail( $this->restaurant_id, $attachment_id );
					update_user_meta( get_current_user_id(), '_company_logo', $attachment_id );

				// Save meta data
				} else {
					update_post_meta( $this->restaurant_id, '_' . $key, $values[ $group_key ][ $key ] );

					// Handle attachments
					if ( 'file' === $field['type'] ) {
						if ( is_array( $values[ $group_key ][ $key ] ) ) {
							foreach ( $values[ $group_key ][ $key ] as $file_url ) {
								$maybe_attach[] = $file_url;
							}
						} else {
							$maybe_attach[] = $values[ $group_key ][ $key ];
						}
					}
				}
			}
		}

		$maybe_attach = array_filter( $maybe_attach );

		// Handle attachments
		if ( sizeof( $maybe_attach ) && apply_filters( 'listings_restaurants_attach_uploaded_files', true ) ) {
			// Get attachments
			$attachments     = get_posts( 'post_parent=' . $this->restaurant_id . '&post_type=attachment&fields=ids&post_mime_type=image&numberposts=-1' );
			$attachment_urls = array();

			// Loop attachments already attached to the job
			foreach ( $attachments as $attachment_id ) {
				$attachment_urls[] = wp_get_attachment_url( $attachment_id );
			}

			foreach ( $maybe_attach as $attachment_url ) {
				if ( ! in_array( $attachment_url, $attachment_urls ) ) {
					$this->create_attachment( $attachment_url );
				}
			}
		}

		// And user meta to save time in future
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_company_name', isset( $values['company']['company_name'] ) ? $values['company']['company_name'] : '' );
			update_user_meta( get_current_user_id(), '_company_website', isset( $values['company']['company_website'] ) ? $values['company']['company_website'] : '' );
			update_user_meta( get_current_user_id(), '_company_tagline', isset( $values['company']['company_tagline'] ) ? $values['company']['company_tagline'] : '' );
			update_user_meta( get_current_user_id(), '_company_twitter', isset( $values['company']['company_twitter'] ) ? $values['company']['company_twitter'] : '' );
			update_user_meta( get_current_user_id(), '_company_video', isset( $values['company']['company_video'] ) ? $values['company']['company_video'] : '' );
		}

		do_action( 'listings_restaurants_update_restaurant_data', $this->restaurant_id, $values );
	}

	/**
	 * Preview Step
	 */
	public function preview() {
		global $post, $restaurant_preview;

		if ( $this->restaurant_id ) {
			$restaurant_preview       = true;
			$post              = get_post( $this->restaurant_id );
			$post->post_status = 'preview';

			setup_postdata( $post );

			listings_get_template( 'restaurant-preview.php', array(
				'form' => $this
			) );

			wp_reset_postdata();
		}
	}

	/**
	 * Preview Step Form handler
	 */
	public function preview_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_job'] ) ) {
			$this->step --;
		}

		// Continue = change job status then show next screen
		if ( ! empty( $_POST['continue'] ) ) {
			$job = get_post( $this->restaurant_id );

			if ( in_array( $job->post_status, array( 'preview', 'expired' ) ) ) {
				// Reset expiry
				delete_post_meta( $job->ID, '_restaurant_expires' );

				// Update job listing
				$update_job                  = array();
				$update_job['ID']            = $job->ID;
				$update_job['post_status']   = apply_filters( 'submit_restaurant_post_status', get_option( 'listings_restaurants_submission_requires_approval' ) ? 'pending' : 'publish', $job );
				$update_job['post_date']     = current_time( 'mysql' );
				$update_job['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_job['post_author']   = get_current_user_id();

				wp_update_post( $update_job );
			}

			$this->step ++;
		}
	}

	/**
	 * Done Step
	 */
	public function done() {
		do_action( 'listings_restaurants_restaurant_submitted', $this->restaurant_id );
		listings_get_template( 'restaurant-submitted.php', array( 'job' => get_post( $this->restaurant_id ) ) );
	}
}
