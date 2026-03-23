(function($) {
    'use strict';

    $(function() {
        $('.flavor-pages-admin .nav-tab').on('click', function(e) {
            e.preventDefault();

            var target = $(this).attr('href');
            if (!target) {
                return;
            }

            $('.flavor-pages-admin .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.flavor-pages-admin .flavor-tab-content').removeClass('flavor-tab-content--active');
            $(target).addClass('flavor-tab-content--active');
        });
    });
})(jQuery);
