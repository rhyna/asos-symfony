$('.subbar-item').hover(function () {
    $(this).find($('.subbar-dropdown-menu__wrapper')).addClass('subbar-dropdown-menu__wrapper--show');

    $(this).addClass('subbar-item--active');
}, function () {

    $(this).find($('.subbar-dropdown-menu__wrapper')).removeClass('subbar-dropdown-menu__wrapper--show');

    $(this).removeClass('subbar-item--active');
})

function passEntityInfo(data) {
    let array = JSON.parse(data);

    let entityId = array[0];

    let entityMarker = array[1];

    let modalHiddenIdInput = $('#deleteEntity').find('.delete-entity-modal-entity-id');

    let modalMarkerInput = $('#deleteEntity').find('.delete-entity-modal-entity-marker');

    modalHiddenIdInput.val(entityId);

    modalMarkerInput.html(entityMarker);
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
        onclick="passEntityInfo(JSON.stringify([${size['id']}, '${size['title']}']))">`);

    deleteButton.appendTo(iconsInner);

    let deleteIcon = $('<i class="far fa-trash-alt"></i>');

    deleteIcon.appendTo(deleteButton);
}

function manageSizes(url) {
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
        url: url,
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
                    createSizeItem(size);
                })
            }
        })
        .fail(function (response) {
            alert(response.responseText);
        })
}

if ($('.size-form #categoryId--sizeList').length) {
    manageSizes($(location).attr('href'));
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

            if (addSizeResponse['errors']) {
                $('.error-warning--add').empty();

                addSizeResponse['errors'].forEach(function (error) {
                    let errorMessage = $(`<li class="error-warning-item error-warning-item--add">${error}</li>`)

                    errorMessage.appendTo($('.error-warning--add'));
                })
            } else {
                $('.error-warning--add').empty();

                $('.add-size-modal').modal('hide');

                 manageSizes($(location).attr('href'));
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

if ($('.selectpicker').length) {
    $('.selectpicker').selectpicker();
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







