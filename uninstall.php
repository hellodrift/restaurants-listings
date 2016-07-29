<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

wp_clear_scheduled_hook( 'listings_restaurants_check_for_expired_jobs' );

wp_trash_post( get_option( 'listings_restaurants_submit_job_form_page_id' ) );
wp_trash_post( get_option( 'listings_restaurants_job_dashboard_page_id' ) );
wp_trash_post( get_option( 'listings_restaurants_jobs_page_id' ) );

$options = array(
    'listings_restaurants_version',
    'listings_restaurants_hide_filled_positions',
    'listings_restaurants_enable_categories',
    'listings_restaurants_enable_default_category_multiselect',
    'listings_restaurants_category_filter_type',
    'listings_restaurants_user_requires_account',
    'listings_restaurants_enable_registration',
    'listings_restaurants_registration_role',
    'listings_restaurants_submission_requires_approval',
    'listings_restaurants_user_can_edit_pending_submissions',
    'listings_restaurants_submission_duration',
    'listings_restaurants_allowed_application_method',
    'listings_restaurants_installed_terms',
    'listings_restaurants_submit_page_slug',
    'listings_restaurants_dashboard_page_slug',
    'listings_restaurants_submit_job_form_page_id',
    'listings_restaurants_job_dashboard_page_id',
    'listings_restaurants_jobs_page_id',
);

foreach ( $options as $option ) {
    delete_option( $option );
}