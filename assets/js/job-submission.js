jQuery(document).ready(function($) {
	jQuery('body').on( 'click', '.listings-restaurants-remove-uploaded-file', function() {
		jQuery(this).closest( '.listings-restaurants-uploaded-file' ).remove();
		return false;
	});
});