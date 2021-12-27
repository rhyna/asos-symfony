$('.entity-form-option--disabled').prop('disabled', true);

function deleteEntityImage(buttonElement, url) {
    let button = $(buttonElement);

    let entityType = button.data('type');

    let entityId = button.data('id');

    let imageName = button.data('image');

    let image = button.closest('.form-image');

    $.ajax({
        url: url,
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

function deleteEntity(form, entityType) {
    let url = form.action;

    let id = $('#deleteEntity').find('.delete-entity-modal-entity-id')[0].defaultValue;

    let onDeletionModal = $('#onDeletionResponse');

    let identifier = $('.entity-list-item[data-id="' + id + '"]')

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