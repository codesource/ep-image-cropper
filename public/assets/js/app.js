/*global document, jQuery */

(function ($) {
    $(function () {
        const locale = 'fr',
            debug = false;
        $('#menu').each(function () {
            let menu = $(this),
                toggle = menu.find('.toggle');
            toggle.on('click', function () {
                menu.toggleClass('active');
            });
        });
    });
}(jQuery));