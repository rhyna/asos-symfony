$('.subbar-item').hover(function () {
    $(this).find($('.subbar-dropdown-menu__wrapper')).addClass('subbar-dropdown-menu__wrapper--show');

    $(this).addClass('subbar-item--active');
}, function () {

    $(this).find($('.subbar-dropdown-menu__wrapper')).removeClass('subbar-dropdown-menu__wrapper--show');

    $(this).removeClass('subbar-item--active');
})

$('.entity-form-option--disabled').prop('disabled', true);

function deleteEntityImage(buttonElement) {
    let button = $(buttonElement);

    let entityType = button.data('type');

    let entityId = button.data('id');

    let imageName = button.data('image');

    let image = button.closest('.form-image');

    $.ajax({
        url: '/admin/delete-' + entityType + '-image.php',
        type: 'POST',
        data: {
            id: entityId,
            image: imageName,
        },
    })
        .done(function (response) {
            $(image).find(".entity-form-image")
                .html('No image')
                .addClass('entity-form-image--deleted')
                .css('background-image', '');
            $(button).addClass('entity-form-delete-image-button--deleted');
        })
        .fail(function (response) {
            alert(response.responseText);
        })
}

function passEntityId(entityId) {
    let modalHiddenInput = $('#deleteEntity').find('.delete-entity-modal-entity-id');

    modalHiddenInput.val(entityId);
}

function deleteEntity(form, entityType) {
    let url = form.action;

    let id = $('#deleteEntity').find('.delete-entity-modal-entity-id')[0].defaultValue;

    let onDeletionModal = $('#onDeletionResponse');

    let identifier = $('.entity-list-item').find("a[href*='id=" + id + "']");

    let categoryId = null;

    if (entityType === 'size') {
        identifier = $('.entity-list-item .size-item[data-id="' + id + '"]');

        categoryId = $('#categoryId--sizeList option:selected').val();
    }

    let listItemElement = identifier.closest('.entity-list-item__wrapper');

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            id: id,
            categoryId: categoryId,
        },
    })
        .done(function (response) {
            $('#deleteEntity').modal('hide');

            onDeletionModal.find('.modal-title').html('');

            onDeletionModal.find('.modal-body').html(response);

            onDeletionModal.modal('show');

            listItemElement.remove();
        })
        .fail(function (response) {
            $('#deleteEntity').modal('hide');

            onDeletionModal.find('.modal-title').html('An error occurred');

            onDeletionModal.find('.modal-body').html(response.responseText);

            onDeletionModal.modal('show');
        })
}

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

function addBrandFromProduct(form) {
    let url = form.action;

    let productId = $('.add-brand-modal').find("input[name='productId']").val();

    let productMode = $('.add-brand-modal').find("input[name='productMode']").val();

    let title = $('.add-brand-modal').find("input[name='title']").val();

    let descriptionWomen = $('.add-brand-modal').find("textarea[name='descriptionWomen']").val();

    let descriptionMen = $('.add-brand-modal').find("textarea[name='descriptionMen']").val();

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            productId: productId,
            productMode: productMode,
            title: title,
            descriptionWomen: descriptionWomen,
            descriptionMen: descriptionMen,
        },
    })
        .done(function (response) {
            $('#addBrand').modal('hide');

            let newOption = JSON.parse(response);

            let option = $("<option></option>");

            option.val(newOption['id']).html(newOption['title']);

            $('select#brand').append(option);

            $('option[value=' + newOption['id'] + ']').attr('selected', 'selected');

        })
        .fail(function (response) {
            alert(response.responseText);
        })
}

function showSizes() {
    let productCategoryId = $('#categoryId').val();

    let productSizeList = $('#productSizes').val();

    let productSizes = JSON.parse(productSizeList);

    $.ajax({
        url: '../admin/get-product-sizes.php',
        type: 'POST',
        data: {
            categoryId: productCategoryId,
        },
    })
        .done(function (response) {
            let sizes = JSON.parse(response);

            $('.product-size-list__content').remove();

            let content = "<div class='product-size-list__content'></div>";

            $('.product-size-list').append(content);

            $('.product-size-list__content').removeClass('product-size-list__content--show');

            if (sizes.length === 0 && !$('#categoryId option:selected').attr('value')) {
                $('.product-size-list-empty').addClass('product-size-list-empty--show')
            } else {
                $('.product-size-list-empty').removeClass('product-size-list-empty--show');

                $('.product-size-list__content').addClass('product-size-list__content--show');
            }

            sizes.forEach(function (size) {

                size['id'] = Number(size['id']);

                let sizeItem = "<div class='product-size-item' data-id='" + size['id'] + "'>";

                $('.product-size-list__content').append(sizeItem);

                let currentItem = $('.product-size-item[data-id=' + size['id'] + ']');

                let checkbox = '<input type="checkbox" class="form-control" name="sizes[]" id="size-' + size['id'] + '" value="' + size['id'] + '">';

                currentItem.append(checkbox);

                let label = '<label for="size-' + size['id'] + '">' + size['title'] + '</label>';

                currentItem.append(label);

                productSizes.forEach(function (productSize) {
                    if (productSize === size['id']) {
                        currentItem.find("input[type='checkbox']").prop('checked', true);
                    }
                })
            })
        })
        .fail(function (response) {
            alert(response.responseText);
        })
}

if ($('.product-form #categoryId').length) {
    showSizes();

    $(document).on('change', '.product-size-item input[type="checkbox"]', function () {
        let checkedSizes = $('.product-size-item input[type="checkbox"]:checked');

        let ids = [];

        checkedSizes.each(function (key, item) {
            if ($(item).is(':checked')) {
                ids.push(Number($(item).val()));
            }
        });

        $('#productSizes').val(JSON.stringify(ids));
    })
}

function createSizeItem(size) {
    let listItemWrapper = $('<div class="entity-list-item__wrapper"></div>');

    listItemWrapper.appendTo('.entity-list-content');

    let listItem = $('<div class="entity-list-item"></div>');

    listItem.appendTo(listItemWrapper);

    let listItemRow = $('<div class="row entity-list-item__row"></div>');

    listItemRow.appendTo(listItem);

    let listItemCol = $('<div class="col"></div>');

    listItemCol.appendTo(listItemRow);

    let sizeItem = $(`<div class="size-item" data-id="${size['id']}"></div>`);

    sizeItem.appendTo(listItemCol);

    let sizeItemInner = $(`<span 
        class="size-item__inner"
        data-toggle="modal"
        data-target="#editSize"
        onclick="passSize('${size['title']}', ${size['id']}, ${size['sortOrder']})"
        >${size['title']}</span>`)

    sizeItemInner.appendTo(sizeItem);

    if (!size['sortOrder']) {
        size['sortOrder'] = 0;
    }

    let sizeOrder = $(`<div class="col-3 size-item-order" data-id="${size['id']}">${size['sortOrder']}</div>`);

    sizeOrder.appendTo(listItemRow);

    let icons = $(`<div class="col-1 entity-list-item-icons" data-id="${size['id']}"></div>`);

    icons.appendTo(listItemRow);

    let iconsInner = $('<div class="entity-list-item-icons__inner">');

    iconsInner.appendTo(icons);

    let editButton = $('<button ' +
        'type="button" ' +
        'data-toggle="modal" ' +
        'data-target="#editSize" ' +
        `onclick="passSize('${size['title']}', ${size['id']}, ${size['sortOrder']})">`);

    editButton.appendTo(iconsInner);

    let editIcon = $('<i class="far fa-edit"></i>');

    editIcon.appendTo(editButton);

    let deleteButton = $(`<button type="button"
        data-toggle="modal"
        data-target="#deleteEntity"
        onclick="passEntityId(${size['id']})">`);

    deleteButton.appendTo(iconsInner);

    let deleteIcon = $('<i class="far fa-trash-alt"></i>');

    deleteIcon.appendTo(deleteButton);
}

function manageSizes() {
    let productCategoryId = $('#categoryId--sizeList option:selected').val();

    let productCategoryTitle = $('#categoryId--sizeList option:selected').text().trim().replace('-- ', '');

    $('.add-size-modal input#categoryId--addSize').val(productCategoryId);

    $('.add-size-modal #categoryTitle--addSize').html(productCategoryTitle);

    if (!productCategoryId) {
        $('.product-size-list-empty--manageSizes').addClass('product-size-list-empty--show')

        return;

    } else {
        $('.product-size-list-empty--manageSizes').removeClass('product-size-list-empty--show');
    }

    $.ajax({
        url: '../admin/manage-sizes.php',
        type: 'POST',
        data: {
            categoryId: productCategoryId,
        },
    })
        .done(function (response) {
            let sizes = JSON.parse(response);

            $('.entity-list-content ').remove();

            let listContent = $('<div class="entity-list-content"></div>');

            listContent.appendTo('.entity-list--size');

            $('.add-size-button').addClass('add-size-button--show');

            if (sizes.length === 0) {
                $('.product-size-list__header').removeClass('product-size-list__header--show');
            } else {
                $('.product-size-list__header').addClass('product-size-list__header--show');

                sizes.forEach(function (size) {
                    size['id'] = Number(size['id']);

                    createSizeItem(size);
                })
            }
        })
        .fail(function (response) {
            alert(response.responseText);
        })
}

if ($('.size-form #categoryId--sizeList').length) {
    manageSizes();
}

function addSize(form) {
    let categoryId = $('.add-size-modal input#categoryId--addSize').val();

    let size = $('.add-size-modal #size--addSize').val();

    let sortOrder = $('.add-size-modal #sortOrder--addSize').val();

    $.ajax({
        url: form.action,
        type: 'POST',
        data: {
            categoryId: categoryId,
            size: size,
            sortOrder: sortOrder,
        },
    })
        .done(function (response) {
            let addSizeResponse = JSON.parse(response);

            if (addSizeResponse['errorMessages']) {
                $('.error-warning--add').empty();

                addSizeResponse['errorMessages'].forEach(function (error) {
                    let errorMessage = $(`<li class="error-warning-item error-warning-item--add">${error}</li>`)

                    errorMessage.appendTo($('.error-warning--add'));
                })
            } else {
                $('.error-warning--add').empty();

                $('.add-size-modal').modal('hide');

                createSizeItem(addSizeResponse);

                manageSizes();
            }

            $(".add-size-modal").on("hidden.bs.modal", function () {
                $('.add-size-modal #size--addSize').val('');

                $('.add-size-modal #sortOrder--addSize').val('');

                $('.error-warning--add').empty();
            });
        })
        .fail(function (response) {
            alert(response.responseText);
        })
}

function passSize(sizeTitle, sizeId, sortOrder) {
    $('#editSizeForm input#size--editSize').val(sizeTitle);

    $('#editSizeForm input#size--editSize').attr('data-id', sizeId);

    $('#editSizeForm input#sortOrder--editSize').val(sortOrder);
}

function editSize(form) {
    let sizeTitle = $('.edit-size-modal #size--editSize').val();

    let sizeId = $('.edit-size-modal #size--editSize').attr('data-id');

    let sortOrder = $('.edit-size-modal #sortOrder--editSize').val();

    $.ajax({
        url: form.action,
        type: 'POST',
        data: {
            sizeTitle: sizeTitle,
            sizeId: sizeId,
            sortOrder: sortOrder,
        },
    })
        .done(function (response) {
            let editSizeResponse = JSON.parse(response);

            if (editSizeResponse['errorMessages']) {
                $('.error-warning--edit').empty();

                editSizeResponse['errorMessages'].forEach(function (error) {
                    let errorMessage = $(`<li class="error-warning-item error-warning-item--edit">${error}</li>`)

                    errorMessage.appendTo($('.error-warning--edit'));
                })

            } else {
                $('.error-warning--edit').empty();

                $('.edit-size-modal').modal('hide');

                if (editSizeResponse['sortOrder'] === null) {
                    editSizeResponse['sortOrder'] = 0;
                }

                let sizeItemInner = $('.size-item[data-id=' + sizeId + '] .size-item__inner');

                sizeItemInner.html(editSizeResponse['title']);

                sizeItemInner.attr('onclick', `passSize('${editSizeResponse['title']}', ${sizeId}, ${editSizeResponse['sortOrder']})`);

                $(`.size-item-order[data-id=${sizeId}]`).html(editSizeResponse['sortOrder']);

                $(`.entity-list-item-icons[data-id=${sizeId}] button[data-target="#editSize"]`)
                    .attr('onclick', `passSize('${editSizeResponse['title']}', ${sizeId}, ${editSizeResponse['sortOrder']})`);
            }

            $(".edit-size-modal").on("hidden.bs.modal", function () {
                $('.edit-size-modal #size--editSize').val('');

                $('.add-size-modal #sortOrder--editSize').val('');

                $('.error-warning--edit').empty();
            });
        })

        .fail(function (response) {
            alert(response.responseText);
        })
}

if ($('.selectpicker').length) {
    $('.selectpicker').selectpicker();
}

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

if (typeof ClassicEditor !== 'undefined') {
    ClassicEditor.create(document.querySelector('#productDetails'))
        .then(editor => {
            window.productDetails = editor;
        })
        .catch(err => {
            console.error(err.stack);
        });

    ClassicEditor.create(document.querySelector('#lookAfterMe'))
        .then(editor => {
            window.lookAfterMe = editor;
        })
        .catch(err => {
            console.error(err.stack);
        });

    ClassicEditor.create(document.querySelector('#aboutMe'))
        .then(editor => {
            window.aboutMe = editor;
        })
        .catch(err => {
            console.error(err.stack);
        });
}

if ($('.text-collapsible--product').length) {
    if ($('.text-collapsible--product').height() < 400) {
        $('.text-collapsible-toggle').css('display', 'none');

        $('.text-collapsible').addClass('text-collapsible--no-collapse');
    }
}

if ($('.text-collapsible--catalog').length) {
    if ($('.text-collapsible--catalog').height() < 70) {
        $('.text-collapsible-toggle').css('display', 'none');

        $('.text-collapsible').addClass('text-collapsible--no-collapse');
    }
}

if ($('.text-collapsible-toggle').length) {
    $('.text-collapsible-toggle').on('click', function () {
        $('.text-collapsible').toggleClass('text-collapsible--expanded');

        if ($('.text-collapsible').hasClass('text-collapsible--expanded')) {
            $('.text-collapsible-toggle').html('View less');
        } else {
            $('.text-collapsible-toggle').html('View more');
        }
    });
}







