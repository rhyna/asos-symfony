function showSizes() {
    let productCategoryId = $('#categoryId').val();

    let productSizeList = $('#productSizes').val();

    let productSizes = JSON.parse(productSizeList);

    $.ajax({
        url: '/admin/product/product-sizes',
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
                $('.product-size-list-nosizes').removeClass('product-size-list-nosizes--show');

                $('.product-size-list-empty').addClass('product-size-list-empty--show')

            } else if (sizes.length === 0 && $('#categoryId option:selected').attr('value')) {
                $('.product-size-list-empty').removeClass('product-size-list-empty--show');

                $('.product-size-list-nosizes').addClass('product-size-list-nosizes--show')

            } else {
                $('.product-size-list-empty').removeClass('product-size-list-empty--show');

                $('.product-size-list-nosizes').removeClass('product-size-list-nosizes--show');

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

            if (editSizeResponse['errors']) {
                $('.error-warning--edit').empty();

                editSizeResponse['errors'].forEach(function (error) {
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