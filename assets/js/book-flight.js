var $ = jQuery

jQuery(document).ready(function ($) {
    $(document).on('keydown', 'input[type="date"]', function (e) {
        e.preventDefault();
    });
});


