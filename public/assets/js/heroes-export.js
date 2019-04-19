/*global document, jQuery */

(function ($) {
    $(function () {
        $('#export').each(function () {
            let initializeInput,
                form = $(this),
                files = form.find('.files'),
                counter = form.find('.counter'),
                submit = form.find('button'),
                remarks = $('#remarks'),
                heroes = $('#heroes'),
                refresh = form.find('#refresh'),
                updateCounter = function () {
                    counter.html(files.find('input[type="file"]').length);
                };

            initializeInput = function (input) {
                input.on('change', function () {
                    let startIndex, filename, newInput, file,
                        fullPath = input.val();
                    if (fullPath) {

                        // Replace input with a new one
                        newInput = $('<input type="file" name="heroes[]" />')
                        initializeInput(newInput);
                        input.replaceWith(newInput);
                        input.off('change');

                        // Build file line
                        startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                        filename = fullPath.substring(startIndex);
                        if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                            filename = filename.substring(1);
                        }
                        file = $('<li>' + filename + '</li>');
                        file.on('click', function () {
                            file.remove();
                            updateCounter();
                        });
                        file.append(input);
                        files.append(file)

                        submit.show();
                        refresh.show();
                        heroes.html('');
                        updateCounter();
                    }

                });
            };
            initializeInput(form.find('input[type="file"]'));

            refresh.on('click', function () {
                files.html('');
                updateCounter();
                submit.hide();
                refresh.hide();
            });
            form.on('submit', function () {
                $('#loader').show();
                $('#remarks').hide();
            });
        });
    });
}(jQuery));