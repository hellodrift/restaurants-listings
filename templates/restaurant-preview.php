<form method="post" id="job_preview" action="<?php echo esc_url( $form->get_action() ); ?>">
    <div class="restaurant_listing_preview_title">
        <input type="submit" name="continue" id="job_preview_submit_button" class="button listings-jobs-button-submit-listing" value="<?php echo apply_filters( 'submit_job_step_preview_submit_text', __( 'Submit Listing', 'listings-jobs' ) ); ?>" />
        <input type="submit" name="edit_job" class="button listings-jobs-button-edit-listing" value="<?php _e( 'Edit listing', 'listings-jobs' ); ?>" />
        <h2><?php _e( 'Preview', 'listings-jobs' ); ?></h2>
    </div>
    <div class="restaurant_listing_preview single_restaurant_listing">
        <h1><?php the_title(); ?></h1>

        <?php listings_get_template_part( 'content-single', 'restaurant_listing' ); ?>

        <input type="hidden" name="job_id" value="<?php echo esc_attr( $form->get_job_id() ); ?>" />
        <input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ); ?>" />
        <input type="hidden" name="listings_form" value="<?php echo $form->get_form_name(); ?>" />
    </div>
</form>
