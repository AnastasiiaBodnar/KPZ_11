jQuery(document).ready(function ($) {
    function checkVisibility() {
        $('.kpz-fade-in').each(function () {
            var elementTop = $(this).offset().top;
            var windowBottom = $(window).scrollTop() + $(window).height();

            if (elementTop < windowBottom - 50) {
                $(this).addClass('visible');
            }
        });
    }

    checkVisibility();
    $(window).on('scroll', checkVisibility);
});
