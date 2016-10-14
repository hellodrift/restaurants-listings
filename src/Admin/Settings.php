<?php

namespace Listings\Restaurants\Admin;

class Settings {
    public function hooks()
    {
        add_filter( 'listings_settings', array($this, 'settings' ) );
        add_action( 'listings_after_settings', array($this, 'after_settings') );
    }

    public function settings( $settings )
    {
        // Prepare roles option
        $roles         = get_editable_roles();
        $account_roles = array();

        foreach ( $roles as $key => $role ) {
            if ( $key == 'administrator' ) {
                continue;
            }
            $account_roles[ $key ] = $role['name'];
        }

        $settings = array_merge( $settings, array(
            'restaurant_listings' => array(
                __( 'Job Listings', 'listings_restaurants' ),
                array(
                    array(
                        'name'        => 'listings_restaurants_per_page',
                        'std'         => '10',
                        'placeholder' => '',
                        'label'       => __( 'Listings Per Page', 'restaurants-listings' ),
                        'desc'        => __( 'How many listings should be shown per page by default?', 'restaurants-listings' ),
                        'attributes'  => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_hide_filled_positions',
                        'std'        => '0',
                        'label'      => __( 'Filled Positions', 'restaurants-listings' ),
                        'cb_label'   => __( 'Hide filled positions', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, filled positions will be hidden from archives.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_hide_expired_content',
                        'std'        => '1',
                        'label'      => __( 'Expired Listings', 'restaurants-listings' ),
                        'cb_label'   => __( 'Hide content within expired listings', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, the content within expired listings will be hidden. Otherwise, expired listings will be displayed as normal (without the application area).', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_enable_categories',
                        'std'        => '0',
                        'label'      => __( 'Categories', 'restaurants-listings' ),
                        'cb_label'   => __( 'Enable categories for listings', 'restaurants-listings' ),
                        'desc'       => __( 'Choose whether to enable categories. Categories must be setup by an admin to allow users to choose them during submission.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_enable_default_category_multiselect',
                        'std'        => '0',
                        'label'      => __( 'Multi-select Categories', 'restaurants-listings' ),
                        'cb_label'   => __( 'Enable category multiselect by default', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, the category select box will default to a multiselect on the [jobs] shortcode.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_category_filter_type',
                        'std'        => 'any',
                        'label'      => __( 'Category Filter Type', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, the category select box will default to a multiselect on the [jobs] shortcode.', 'restaurants-listings' ),
                        'type'       => 'select',
                        'options' => array(
                            'any'  => __( 'Jobs will be shown if within ANY selected category', 'restaurants-listings' ),
                            'all' => __( 'Jobs will be shown if within ALL selected categories', 'restaurants-listings' ),
                        )
                    ),
                ),
            ),
            'restaurant_submission' => array(
                __( 'Job Submission', 'restaurants-listings' ),
                array(
                    array(
                        'name'       => 'listings_restaurants_user_requires_account',
                        'std'        => '1',
                        'label'      => __( 'Account Required', 'restaurants-listings' ),
                        'cb_label'   => __( 'Submitting listings requires an account', 'restaurants-listings' ),
                        'desc'       => __( 'If disabled, non-logged in users will be able to submit listings without creating an account.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_enable_registration',
                        'std'        => '1',
                        'label'      => __( 'Account Creation', 'restaurants-listings' ),
                        'cb_label'   => __( 'Allow account creation', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, non-logged in users will be able to create an account by entering their email address on the submission form.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_generate_username_from_email',
                        'std'        => '1',
                        'label'      => __( 'Account Username', 'restaurants-listings' ),
                        'cb_label'   => __( 'Automatically Generate Username from Email Address', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, a username will be generated from the first part of the user email address. Otherwise, a username field will be shown.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_registration_role',
                        'std'        => 'employer',
                        'label'      => __( 'Account Role', 'restaurants-listings' ),
                        'desc'       => __( 'If you enable registration on your submission form, choose a role for the new user.', 'restaurants-listings' ),
                        'type'       => 'select',
                        'options'    => $account_roles
                    ),
                    array(
                        'name'       => 'listings_restaurants_submission_requires_approval',
                        'std'        => '1',
                        'label'      => __( 'Moderate New Listings', 'restaurants-listings' ),
                        'cb_label'   => __( 'New listing submissions require admin approval', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, new submissions will be inactive, pending admin approval.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_user_can_edit_pending_submissions',
                        'std'        => '0',
                        'label'      => __( 'Allow Pending Edits', 'restaurants-listings' ),
                        'cb_label'   => __( 'Submissions awaiting approval can be edited', 'restaurants-listings' ),
                        'desc'       => __( 'If enabled, submissions awaiting admin approval can be edited by the user.', 'restaurants-listings' ),
                        'type'       => 'checkbox',
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_submission_duration',
                        'std'        => '30',
                        'label'      => __( 'Listing Duration', 'restaurants-listings' ),
                        'desc'       => __( 'How many <strong>days</strong> listings are live before expiring. Can be left blank to never expire.', 'restaurants-listings' ),
                        'attributes' => array()
                    ),
                    array(
                        'name'       => 'listings_restaurants_allowed_application_method',
                        'std'        => '',
                        'label'      => __( 'Application Method', 'restaurants-listings' ),
                        'desc'       => __( 'Choose the contact method for listings.', 'restaurants-listings' ),
                        'type'       => 'select',
                        'options'    => array(
                            ''      => __( 'Email address or website URL', 'restaurants-listings' ),
                            'email' => __( 'Email addresses only', 'restaurants-listings' ),
                            'url'   => __( 'Website URLs only', 'restaurants-listings' ),
                        )
                    )
                )
            ),
        ) );

        $settings['listings_pages'][1][] = array(
            'name' => 'listings_restaurants_submit_restaurant_form_page_id',
            'std' => '',
            'label' => __('Submit Job Form Page', 'restaurants-listings'),
            'desc' => __('Select the page where you have placed the [submit_restaurant_form] shortcode. This lets the plugin know where the form is located.', 'restaurants-listings'),
            'type' => 'page'
        );
        $settings['listings_pages'][1][] = array(
            'name' => 'listings_restaurants_restaurant_dashboard_page_id',
            'std' => '',
            'label' => __('Job Dashboard Page', 'restaurants-listings'),
            'desc' => __('Select the page where you have placed the [restaurant_dashboard] shortcode. This lets the plugin know where the dashboard is located.', 'restaurants-listings'),
            'type' => 'page'
        );
        $settings['listings_pages'][1][] = array(
            'name' => 'listings_restaurants_restaurants_page_id',
            'std' => '',
            'label' => __('Job Listings Page', 'restaurants-listings'),
            'desc' => __('Select the page where you have placed the [jobs] shortcode. This lets the plugin know where the job listings page is located.', 'restaurants-listings'),
            'type' => 'page'
        );

        return $settings;
    }

    public function after_settings()
    {
        ?>
        <script type="text/javascript">
        jQuery('.nav-tab-wrapper a:first').click();
			jQuery('#setting-listings_restaurants_enable_registration').change(function(){
                if ( jQuery( this ).is(':checked') ) {
                    jQuery('#setting-listings_restaurants_registration_role').closest('tr').show();
                    jQuery('#setting-listings_restaurants_registration_username_from_email').closest('tr').show();
                } else {
                    jQuery('#setting-listings_restaurants_registration_role').closest('tr').hide();
                    jQuery('#setting-listings_restaurants_registration_username_from_email').closest('tr').hide();
                }
            }).change();
		</script>
        <?php
    }
}