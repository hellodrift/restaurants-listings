jQuery(document).ready(function($) {

	$('.restaurant-dashboard-action-delete').click(function() {
		return confirm( listings_restaurant_dashboard.i18n_confirm_delete );
	});

});