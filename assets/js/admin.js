jQuery(document).ready(function($) {
    // Datepicker
    $('input#_restaurant_expires').datepicker({
        altFormat: 'yy-mm-dd',
        dateFormat: listings_restaurants_admin.date_format,
        minDate: 0
    });

    $('input#_restaurant_expires').each(function () {
        if ($(this).val()) {
            var date = new Date($(this).val());
            $(this).datepicker("setDate", date);
        }
    });
});