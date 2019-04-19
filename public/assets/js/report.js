/*global document, jQuery */

(function ($) {
    $(function () {
        const locale = 'fr',
            debug = true,
            download = $('#download'),
            refresh = $('#refresh'),
            date = $('#date'),
            reportInput = $('#report-value'),
            dateInput = $('#date-value');
        let loadCropper = function (data, target) {
                let cropper,
                    handler = $('#cropper'),
                    image = handler.find('img.croppable'),
                    importBtn = handler.find('#import'),
                    closeBtn = handler.find('#cancel'),
                    closeFct = function () {
                        $(document).off('keyup.import');
                        cropper.destroy();
                        handler.removeClass('open');
                    },
                    importFct = function () {
                        target.find('canvas').remove();
                        target.append(cropper.getCroppedCanvas({
                            maxWidth: 640
                        }));
                        cropper.destroy();
                        refresh.css('display', 'inline-block');
                        download.css('display', 'inline-block');
                        target.parent().addClass('selected');
                        closeFct();
                    };

                image.attr('src', data);
                cropper = new Cropper(image[0], {
                    rotatable: false,
                    viewMode: 1,
                    ready: function () {
                        let imageSize = cropper.getImageData(),
                            containerSize = cropper.getContainerData(),
                            width = target.attr('data-width') * imageSize.width,
                            height = target.attr('data-aspect') * width;
                        cropper.setCropBoxData({
                            left: (containerSize.width / 2) - (imageSize.width / 2) + target.attr('data-left') * imageSize.width,
                            top: (containerSize.height / 2) - (imageSize.height / 2) + target.attr('data-top') * imageSize.height,
                            width: width,
                            height: height
                        });
                    },
                    cropend: function () {
                        if (debug) {
                            let imageSize = cropper.getImageData(),
                                containerSize = cropper.getContainerData(),
                                cropSize = cropper.getCropBoxData();
                            console.log({
                                aspect: cropSize.height / cropSize.width,
                                left: (cropSize.left - (containerSize.width / 2) + (imageSize.width / 2)) / imageSize.width,
                                top: (cropSize.top - (containerSize.height / 2) + (imageSize.height / 2)) / imageSize.height,
                                width: cropSize.width / imageSize.width
                            });
                        }
                    }
                });
                $(document).on('keyup.import', function (event) {
                    if (event.keyCode === 13) {
                        importFct()
                    }
                });
                importBtn
                    .off('click.import')
                    .on('click.import', importFct);
                closeBtn
                    .off('click.close')
                    .on('click.close', closeFct)
                handler.addClass('open');
            },
            loadImage = function (input, handler) {
                if (input.files && input.files[0]) {
                    let reader = new FileReader();

                    reader.onload = function (e) {
                        loadCropper(e.target.result, handler);
                    };

                    reader.readAsDataURL(input.files[0]);
                }
            },
            initialize = function () {
                $('input[type="file"]').each(function () {
                    let changeCallback,
                        input = $(this),
                        handler = input.parents('div.image'),
                        label = handler.find('label'),
                        resetInput = function (input) {
                            let id = 'file' + Math.round(new Date().getTime() + (Math.random() * 100));
                            // Replace current input to reset his value.
                            $(input).replaceWith($('<input type="file" id="' + id + '"/>').on('change.loading', changeCallback));
                        };
                    changeCallback = function () {
                        loadImage(this, label);
                        resetInput(this);
                    };
                    handler.removeClass('selected');

                    // Initial input reset to prevent browser to load previous data.
                    resetInput(input);
                });
                download.on('submit', function () {
                    let dateHeight = 40,
                        currentDate = date.datetimepicker('getValue'),
                        gap = 5,
                        width = 1000000,
                        height = 0,
                        link = document.createElement("a"),
                        canvas = document.createElement('canvas'),
                        context = canvas.getContext('2d'),
                        images = $('div.image canvas'),
                        y = gap + dateHeight;
                    if (images.length > 0) {

                        // Calculate width and height of the final canvas
                        images.each(function () {
                            if (width > this.width) {
                                width = this.width;
                            }
                        }).each(function () {
                            height += this.height / this.width * width;
                        });
                        canvas.height = height + ((images.length + 1) * gap) + dateHeight;
                        canvas.width = width + (2 * gap);


                        // Black background
                        context.fillStyle = '#000000';
                        context.fillRect(0, 0, canvas.width, canvas.height);

                        // Write the date on top
                        moment.locale(locale);
                        context.font = "bold 24px Arial ";
                        context.textAlign = "center"
                        context.fillStyle = "#ffffff";
                        context.fillText(moment(currentDate).format('LLLL'), width / 2, 24, width);


                        // Include all images
                        images.each(function () {
                            let imageHeight = this.height / this.width * width;
                            context.drawImage(this, gap, y, width, imageHeight);
                            y += imageHeight + gap;
                        });

                        reportInput.val(canvas.toDataURL("image/png"));
                        dateInput.val(moment(currentDate).toISOString());
                        setTimeout(function(){
                            reportInput.val('');
                        }, 500);
                    }
                });
                refresh.on('click', function () {
                    $('div.image')
                        .removeClass('selected')
                        .find('canvas').remove();
                    download.hide();
                    refresh.hide();
                });

                $.datetimepicker.setLocale(locale);
                date.datetimepicker({
                    format: 'd.m.Y H:i',
                    defaultDate: new Date(),
                    value: new Date()
                });

                $(document).on('paste', function (event) {
                    let index,
                        items = (event.clipboardData || event.originalEvent.clipboardData).items;
                    for (index in items) {
                        if (items[index].kind === 'file') {
                            let blob = items[index].getAsFile(),
                                reader = new FileReader();
                            reader.onload = function (event) {
                                if (event.target.result.match(/^data:image\//)) {
                                    let targets = $('div.image .target');
                                    targets
                                        .off('click.paste')
                                        .on('click.paste', function () {
                                            loadCropper(event.target.result, $(this).parent().find('label'));
                                        }).show();
                                }
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                });
            };
        initialize();
    });
}(jQuery));