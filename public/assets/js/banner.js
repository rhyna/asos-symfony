function updateBanner(id) {
    let currentBannerId = id;

    let selectedBannerPlace = $('select[name="banner-place"]').val();

    let bannerPlaceModal = $('.banner-place-modal');

    $.ajax({
        url: '../admin/taken-banner-places.php',
        type: 'GET',
    })
        .done(function (response) {
            let takenBannerPlaces = JSON.parse(response);

            let needToShowModal = false;

            takenBannerPlaces.forEach(function (item) {
                if (Number(item.bannerPlaceId) === Number(selectedBannerPlace) && Number(item.bannerId) !== Number(currentBannerId)) {
                    needToShowModal = true;
                }
            })

            if (needToShowModal) {
                bannerPlaceModal.modal('show');

                bannerPlaceModal.find('.submit-banner-data').on('click',
                    function () {
                        $('#bannerForm').submit();

                    })

            } else {
                $('#bannerForm').submit();
            }
        })

        .fail(function (response) {
            alert(response.responseText);
        })
}