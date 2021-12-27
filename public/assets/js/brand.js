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