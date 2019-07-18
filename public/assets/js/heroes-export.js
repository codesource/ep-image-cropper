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
                    counter.html(files.find('li').length);
                };

            initializeInput = function (input) {
                input.on('change', function () {
                    let startIndex, filename, newInput, file, closer,
                        inputFiles = input.get(0).files,
                        fullPath = input.val(),
                        appendFile = function (filename) {
                            file = $('<li>' + filename + '</li>');
                            closer = $('<i class="close"></i>').appendTo(file);
                            closer.on('click', function () {
                                $(this).parent().remove();
                                updateCounter();
                                if(files.find('li').length === 0){
                                    submit.hide();
                                    refresh.hide();
                                }
                            });
                            file.append('<input type="hidden" name="sorting[]" value="' + filename + '" />');
                            files.append(file);
                            return file;
                        };
                    if (inputFiles || fullPath) {
                        // Replace input with a new one
                        newInput = $('<input type="file" name="heroes[]" multiple="multiple"/>')
                        initializeInput(newInput);
                        input.replaceWith(newInput);
                        input.off('change');
                        if (inputFiles) {
                            $.each(inputFiles, function () {
                                appendFile(this.name);
                            });
                        } else {
                            // Build file line
                            startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                            filename = fullPath.substring(startIndex);
                            if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                                filename = filename.substring(1);
                            }
                            appendFile(filename);
                        }

                        submit.show();
                        refresh.show();
                        heroes.html('');
                        form.append(input.hide());
                        updateCounter();
                    }
                });
            };
            initializeInput(form.find('input[type="file"]'));
            files.sortable();

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