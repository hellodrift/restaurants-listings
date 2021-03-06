jQuery( document ).ready( function ( $ ) {

	var xhr = [];

	$( '.restaurant_listings' ).on( 'update_results', function ( event, page, append, loading_previous ) {
		var data         = '';
		var target       = $( this );
		var form         = target.find( '.restaurant_filters' );
		var showing      = target.find( '.showing_restaurants' );
		var results      = target.find( '.restaurant_listings' );
		var per_page     = target.data( 'per_page' );
		var orderby      = target.data( 'orderby' );
		var order        = target.data( 'order' );
		var featured     = target.data( 'featured' );
		var filled       = target.data( 'filled' );
		var index        = $( 'div.restaurant_listings' ).index(this);

		if ( index < 0 ) {
			return;
		}

		if ( xhr[index] ) {
			xhr[index].abort();
		}

		if ( ! append ) {
			$( results ).addClass( 'loading' );
			$( 'li.restaurant_listing, li.no_restaurant_listings_found', results ).css( 'visibility', 'hidden' );

			// Not appending. If page > 1, we should show a load previous button so the user can get to earlier-page listings if needed
			if ( page > 1 && true != target.data( 'show_pagination' ) ) {
				$( results ).before( '<a class="load_more_restaurants load_previous" href="#"><strong>' + listings_ajax_filters.i18n_load_prev_listings + '</strong></a>' );
			} else {
				target.find( '.load_previous' ).remove();
			}

			target.find( '.load_more_restaurants' ).data( 'page', page );
		}

		if ( true == target.data( 'show_filters' ) ) {

			var filter_restaurant_type = [];

			$( ':input[name="filter_restaurant_type[]"]:checked, :input[name="filter_restaurant_type[]"][type="hidden"], :input[name="filter_restaurant_type"]', form ).each( function () {
				filter_restaurant_type.push( $( this ).val() );
			} );

			var categories = form.find( ':input[name^="search_categories"]' ).map( function () {
			return $( this ).val();
			} ).get();
			var keywords   = '';
			var location   = '';
			var $keywords  = form.find( ':input[name="search_keywords"]' );
			var $location  = form.find( ':input[name="search_location"]' );

			// Workaround placeholder scripts
			if ( $keywords.val() !== $keywords.attr( 'placeholder' ) ) {
				keywords = $keywords.val();
			}

			if ( $location.val() !== $location.attr( 'placeholder' ) ) {
				location = $location.val();
			}

			data = {
				lang: listings_ajax_filters.lang,
				search_keywords: keywords,
				search_location: location,
				search_categories: categories,
				filter_restaurant_type: filter_restaurant_type,
				per_page: per_page,
				orderby: orderby,
				order: order,
				page: page,
				featured: featured,
				filled: filled,
				show_pagination: target.data( 'show_pagination' ),
				form_data: form.serialize()
			};

		} else {

			var categories = target.data( 'categories' );
			var keywords   = target.data( 'keywords' );
			var location   = target.data( 'location' );

			if ( categories ) {
				categories = categories.split( ',' );
			}

			data = {
				lang: listings_ajax_filters.lang,
				search_categories: categories,
				search_keywords: keywords,
				search_location: location,
				per_page: per_page,
				orderby: orderby,
				order: order,
				page: page,
				featured: featured,
				filled: filled,
				show_pagination: target.data( 'show_pagination' )
			};

		}

		xhr[index] = $.ajax( {
			type: 'POST',
			url: listings_ajax_filters.ajax_url.toString().replace( "%%endpoint%%", "get_restaurant_listings" ),
			data: data,
			success: function ( result ) {
				if ( result ) {
					try {
						if ( result.showing ) {
							$( showing ).show().html( '<span>' + result.showing + '</span>' + result.showing_links );
						} else {
							$( showing ).hide();
						}

						if ( result.showing_all ) {
							$( showing ).addClass( 'listings-restaurants-showing-all' );
						} else {
							$( showing ).removeClass( 'listings-restaurants-showing-all' );
						}

						if ( result.html ) {
							if ( append && loading_previous ) {
								$( results ).prepend( result.html );
							} else if ( append ) {
								$( results ).append( result.html );
							} else {
								$( results ).html( result.html );
							}
						}

						if ( true == target.data( 'show_pagination' ) ) {
							target.find('.listings-pagination').remove();

							if ( result.pagination ) {
								target.append( result.pagination );
							}
						} else {
							if ( ! result.found_restaurants || result.max_num_pages <= page ) {
								$( '.load_more_restaurants:not(.load_previous)', target ).hide();
							} else if ( ! loading_previous ) {
								$( '.load_more_restaurants', target ).show();
							}
							$( '.load_more_restaurants', target ).removeClass( 'loading' );
							$( 'li.restaurant_listing', results ).css( 'visibility', 'visible' );
						}

						$( results ).removeClass( 'loading' );

						target.triggerHandler( 'updated_results', result );

					} catch ( err ) {
						if ( window.console ) {
							console.log( err );
						}
					}
				}
			},
			error: function ( jqXHR, textStatus, error ) {
				if ( window.console && 'abort' !== textStatus ) {
					console.log( textStatus + ': ' + error );
				}
			},
			statusCode: {
				404: function() {
					if ( window.console ) {
						console.log( "Error 404: Ajax Endpoint cannot be reached. Go to Settings > Permalinks and save to resolve." );
					}
				}
			}
		} );
	} );

	$( '#search_keywords, #search_location, .restaurant_types :input, #search_categories, .listings-restaurants-filter' ).change( function() {
		var target   = $( this ).closest( 'div.restaurant_listings' );
		target.triggerHandler( 'update_results', [ 1, false ] );
		listings_store_state( target, 1 );
	} )

	.on( "keyup", function(e) {
		if ( e.which === 13 ) {
			$( this ).trigger( 'change' );
		}
	} );

	$( '.restaurant_filters' ).on( 'click', '.reset', function () {
		var target = $( this ).closest( 'div.restaurant_listings' );
		var form = $( this ).closest( 'form' );

		form.find( ':input[name="search_keywords"], :input[name="search_location"], .listings-restaurants-filter' ).not(':input[type="hidden"]').val( '' ).trigger( 'chosen:updated' );
		form.find( ':input[name^="search_categories"]' ).not(':input[type="hidden"]').val( 0 ).trigger( 'chosen:updated' );
		$( ':input[name="filter_restaurant_type[]"]', form ).not(':input[type="hidden"]').attr( 'checked', 'checked' );

		target.triggerHandler( 'reset' );
		target.triggerHandler( 'update_results', [ 1, false ] );
		listings_store_state( target, 1 );

		return false;
	} );

	$( document.body ).on( 'click', '.load_more_restaurants', function() {
		var target           = $( this ).closest( 'div.restaurant_listings' );
		var page             = parseInt( $( this ).data( 'page' ) || 1 );
		var loading_previous = false;

		$(this).addClass( 'loading' );

		if ( $(this).is('.load_previous') ) {
			page             = page - 1;
			loading_previous = true;
			if ( page === 1 ) {
				$(this).remove();
			} else {
				$( this ).data( 'page', page );
			}
		} else {
			page = page + 1;
			$( this ).data( 'page', page );
			listings_store_state( target, page );
		}

		target.triggerHandler( 'update_results', [ page, true, loading_previous ] );
		return false;
	} );

	$( 'div.restaurant_listings' ).on( 'click', '.listings-pagination a', function() {
		var target = $( this ).closest( 'div.restaurant_listings' );
		var page   = $( this ).data( 'page' );

		listings_store_state( target, page );

		target.triggerHandler( 'update_results', [ page, false ] );

		$( "body, html" ).animate({
            scrollTop: target.offset().top
        }, 600 );

		return false;
	} );

	if ( $.isFunction( $.fn.chosen ) ) {
		if ( listings_ajax_filters.is_rtl == 1 ) {
			$( 'select[name^="search_categories"]' ).addClass( 'chosen-rtl' );
		}
		$( 'select[name^="search_categories"]' ).chosen({ search_contains: true });
	}

	if ( window.history && window.history.pushState ) {
		$supports_html5_history = true;
	} else {
		$supports_html5_history = false;
	}

	var location = document.location.href.split('#')[0];

	function listings_store_state( target, page ) {
		if ( $supports_html5_history ) {
			var form  = target.find( '.restaurant_filters' );
			var data  = $( form ).serialize();
			var index = $( 'div.restaurant_listings' ).index( target );
			window.history.replaceState( { id: 'listings_state', page: page, data: data, index: index }, '', location + '#s=1' );
		}
	}

	// Inital restaurant and form population
	$(window).on( "load", function( event ) {
		$( '.restaurant_filters' ).each( function() {
			var target      = $( this ).closest( 'div.restaurant_listings' );
			var form        = target.find( '.restaurant_filters' );
			var inital_page = 1;
			var index       = $( 'div.restaurant_listings' ).index( target );

	   		if ( window.history.state && window.location.hash ) {
	   			var state = window.history.state;
	   			if ( state.id && 'listings_state' === state.id && index == state.index ) {
					inital_page = state.page;
					form.deserialize( state.data );
					form.find( ':input[name^="search_categories"]' ).not(':input[type="hidden"]').trigger( 'chosen:updated' );
				}
	   		}

			target.triggerHandler( 'update_results', [ inital_page, false ] );
	   	});
	});
} );
