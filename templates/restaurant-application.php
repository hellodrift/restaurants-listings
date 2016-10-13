<?php if ( $apply = listings_restaurants_get_application_method() ) :
	wp_enqueue_script( 'listings-jobs-application' );
	?>
	<div class="restaurant_application application">
		<?php do_action( 'listings_restaurants_application_start', $apply ); ?>
		
		<input type="button" class="application_button button" value="<?php _e( 'Apply for job', 'listings-jobs' ); ?>" />
		
		<div class="application_details">
			<?php
				/**
				 * listings_restaurants_application_details_email or listings_restaurants_application_details_url hook
				 */
				do_action( 'listings_restaurants_application_details_' . $apply->type, $apply );
			?>
		</div>
		<?php do_action( 'listings_restaurants_application_end', $apply ); ?>
	</div>
<?php endif; ?>
