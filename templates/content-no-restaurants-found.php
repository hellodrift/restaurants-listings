<?php if ( defined( 'DOING_AJAX' ) ) : ?>
	<li class="no_restaurant_listings_found"><?php _e( 'There are no listings matching your search.', 'restaurants-listings' ); ?></li>
<?php else : ?>
	<p class="no_restaurant_listings_found"><?php _e( 'There are currently no vacancies.', 'restaurants-listings' ); ?></p>
<?php endif; ?>