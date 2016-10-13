<?php global $listings; ?>

<a href="<?php the_permalink(); ?>">
	<div class="job-type <?php echo listings_restaurants_the_restaurant_type() ? sanitize_title( listings_restaurants_the_restaurant_type()->slug ) : ''; ?>"><?php listings_restaurants_the_restaurant_type(); ?></div>

	<?php if ( $logo = listings_restaurants_get_the_company_logo() ) : ?>
		<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php listings_restaurants_the_company_name(); ?>" title="<?php listings_restaurants_the_company_name(); ?> - <?php listings_restaurants_the_company_tagline(); ?>" />
	<?php endif; ?>

	<div class="job_summary_content">

		<h1><?php the_title(); ?></h1>

		<p class="meta"><?php listings_restaurants_the_restaurant_location( false ); ?> &mdash; <date><?php printf( __( 'Posted %s ago', 'listings-jobs' ), human_time_diff( get_post_time( 'U' ), current_time( 'timestamp' ) ) ); ?></date></p>

	</div>
</a>
