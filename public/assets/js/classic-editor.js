if (typeof ClassicEditor !== 'undefined') {
    if (document.querySelector('#productDetails')) {
        ClassicEditor.create(document.querySelector('#productDetails'))
            .then(editor => {
                window.productDetails = editor;
            })
            .catch(err => {
                console.error(err.stack);
            });
    }

    if (document.querySelector('#lookAfterMe')) {
        ClassicEditor.create(document.querySelector('#lookAfterMe'))
            .then(editor => {
                window.lookAfterMe = editor;
            })
            .catch(err => {
                console.error(err.stack);
            });
    }

    if (document.querySelector('#aboutMe')) {
        ClassicEditor.create(document.querySelector('#aboutMe'))
            .then(editor => {
                window.aboutMe = editor;
            })
            .catch(err => {
                console.error(err.stack);
            });
    }
}