<?php global $post; ?>
<div class="single_restaurant_listing" itemscope itemtype="http://schema.org/JobPosting">
	<meta itemprop="title" content="<?php echo esc_attr( $post->post_title ); ?>" />

	<?php if ( get_option( 'listings_restaurants_hide_expired_content', 1 ) && 'expired' === $post->post_status ) : ?>
		<div class="listings-info"><?php _e( 'This listing has expired.', 'listings-jobs' ); ?></div>
	<?php else : ?>
		<?php
			/**
			 * single_restaurant_listing_start hook
			 *
			 * @hooked restaurant_listing_meta_display - 20
			 * @hooked restaurant_listing_company_display - 30
			 */
			do_action( 'single_restaurant_listing_start' );
		?>

		<div class="restaurant_description" itemprop="description">
			<?php echo apply_filters( 'the_restaurant_description', get_the_content() ); ?>
		</div>

		<?php if ( listings_restaurants_candidates_can_apply() ) : ?>
			<?php listings_get_template( 'restaurant-application.php' ); ?>
		<?php endif; ?>

		<?php
			/**
			 * single_restaurant_listing_end hook
			 */
			do_action( 'single_restaurant_listing_end' );
		?>
	<?php endif; ?>
</div>
