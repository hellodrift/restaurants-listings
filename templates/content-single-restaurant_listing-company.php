<?php
/**
 * Single view Company information box
 *
 * Hooked into single_restaurant_listing_start priority 30
 *
 * @since  1.14.0
 */

if ( ! listings_restaurants_get_the_company_name() ) {
	return;
}
?>
<div class="company" itemscope itemtype="http://data-vocabulary.org/Organization">
	<?php listings_restaurants_the_company_logo(); ?>

	<p class="name">
		<?php if ( $website = listings_restaurants_get_the_company_website() ) : ?>
			<a class="website" href="<?php echo esc_url( $website ); ?>" itemprop="url" target="_blank" rel="nofollow"><?php _e( 'Website', 'listings-jobs' ); ?></a>
		<?php endif; ?>
		<?php listings_restaurants_the_company_twitter(); ?>
		<?php listings_restaurants_the_company_name( '<strong itemprop="name">', '</strong>' ); ?>
	</p>
	<?php listings_restaurants_the_company_tagline( '<p class="tagline">', '</p>' ); ?>
	<?php listings_restaurants_the_company_video(); ?>
</div>