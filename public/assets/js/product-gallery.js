$(document).ready(function () {
    $('.product-gallery span').on('click', function () {
        let largeImage = $(this).attr('data-full');

        $('.product-gallery span.selected').removeClass('selected');

        $(this).addClass('selected');

        let fullImage = $('.product-gallery .full img');

        fullImage.hide();

        fullImage.attr('src', largeImage);

        fullImage.fadeIn();
    });

    $('.product-gallery .full img').on('click', function () {
        let modalImage = $(this).attr('src');

        $.fancybox.open(modalImage);
    });
})