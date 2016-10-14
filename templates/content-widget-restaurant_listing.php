<li <?php listings_restaurants_restaurant_listing_class(); ?>>
	<a href="<?php listings_restaurants_the_restaurant_permalink(); ?>">
		<div class="position">
			<h3><?php the_title(); ?></h3>
		</div>
		<ul class="meta">
			<li class="location"><?php listings_restaurants_the_restaurant_location( false ); ?></li>
			<li class="company"><?php listings_restaurants_the_company_name(); ?></li>
			<li class="restaurant-type <?php echo listings_restaurants_the_restaurant_type() ? sanitize_title( listings_restaurants_the_restaurant_type()->slug ) : ''; ?>"><?php listings_restaurants_the_restaurant_type(); ?></li>
		</ul>
	</a>
</li>