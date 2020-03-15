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
                exportUrl = '/heroes-export.html/export',
                finalizeUrl = '/heroes-export.html/finalize',
                updateCounter = function () {
                    counter.html(files.find('li').length);
                };

            initializeInput = function (input) {
                input.on('change', function () {
                    let startIndex, filename, newInput, file, closer, formData,
                        inputFiles = input.get(0).files,
                        appendFile = function (filename, data) {
                            file = $('<li><span></span>' + filename + '</li>');
                            closer = $('<i class="close"></i>').appendTo(file);
                            closer.on('click', function () {
                                $(this).parent().remove();
                                updateCounter();
                                if (files.find('li').length === 0) {
                                    submit.hide();
                                    refresh.hide();
                                }
                            });
                            files.append(file);
                            return file;
                        };
                    if (inputFiles) {
                        // Replace input with a new one
                        newInput = $('<input type="file" name="heroes[]" multiple="multiple"/>')
                        initializeInput(newInput);
                        input.replaceWith(newInput);
                        input.off('change');
                        $.each(inputFiles, function () {
                            file = appendFile(this.name);
                            formData = new FormData();
                            formData.append('heroes', this);
                            file.data('file', formData);
                        });

                        submit.css('display', 'block');
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

            submit.on('click', function () {
                let lines = files.find('li'),
                    count = lines.length,
                    keys = [],
                    finalize = function () {
                        $.ajax({
                            url: finalizeUrl,
                            type: 'POST',
                            data: {
                                keys: keys
                            },
                            success: function (data) {
                                if(data.files) {
                                    $.each(data.files, function () {
                                        heroes.append('<form action="/heroes-export.html/download" method="post">' +
                                            '<input type="hidden" name="key" value="' + this.file + '"/>' +
                                            '<input type="hidden" name="color" value="' + this.color + '"/>' +
                                            '<button type="submit">' +
                                            '<img src="/temp/' + this.file + '" alt=""/>' +
                                            '</button>' +
                                            '</form>');
                                    });
                                }
                                lines.remove();
                            },
                            complete: function () {
                                $('#loader').hide();
                            }
                        })
                    };
                $('#remarks').hide();
                $('#loader').show();
                submit.hide();
                lines.each(function () {
                    let file = $(this),
                        progressBar = file.children('span'),
                        formData = file.data('file');
                    $.ajax({
                        url: exportUrl,
                        type: 'POST',
                        xhr: function () {
                            let xhr = $.ajaxSettings.xhr();
                            if (xhr.upload) {
                                xhr.upload.addEventListener('progress', function (event) {
                                    let percent = 0,
                                        position = event.loaded || event.position,
                                        total = event.total;
                                    if (event.lengthComputable) {
                                        percent = Math.ceil(position / total * 90);
                                    }
                                    progressBar.css('width', percent + '%');
                                    progressBar.text(percent + '%');
                                }, false);
                            }
                            return xhr;
                        },
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function () {
                            file.addClass('loading');
                        },
                        success: function (data) {
                            let key = data.key;
                            if (key) {
                                keys.push(data.key);
                                file.addClass('success');
                            } else {
                                file.addClass('error');
                            }
                            if (keys.length === count) {
                                finalize();
                            }
                        },
                        complete: function () {
                            progressBar.css('width', '100%');
                            progressBar.text('100%');
                        }
                    });
                });
            });
        });
    });
}(jQuery));