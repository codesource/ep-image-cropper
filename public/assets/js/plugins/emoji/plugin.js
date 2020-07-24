CKEDITOR.plugins.add('emoji', {
    requires: 'dialog',
    icons: 'emoji',
    hidpi: true,
    init: function (editor) {
        editor.config.smiley_path = editor.config.smiley_path || (this.path + 'icons/');
        editor.addCommand('emoji', new CKEDITOR.dialogCommand('emoji', {
            allowedContent: 'img[alt,height,!src,title,width]',
            requiredContent: 'img'
        }));
        editor.ui.addButton && editor.ui.addButton('Emoji', {
            label: editor.lang.emoji.toolbar,
            command: 'emoji',
            toolbar: 'insert,50'
        });
        CKEDITOR.dialog.add('emoji', this.path + 'dialogs/emoji.js');
    }
});

CKEDITOR.config.emoji_images = [
    'hankey.png', 'smile.png', 'grin.png', 'lol.png', 'rofl.png', 'sad.png', 'crying.png', 'blush.png',
    'rolleyes.png', 'kiss.png', 'love.png', 'geek.png', 'monocle.png', 'think.png', 'tongue.png', 'cool.png',
    'angry.png', 'evil.png', 'thumbsup.png', 'thumbsdown.png'
];

CKEDITOR.config.emoji_descriptions = [
    'hankey', 'smile', 'grin', 'lol', 'rofl', 'sad', 'crying', 'blush',
    'rolleyes', 'kiss', 'love', 'geek', 'monocle', 'think', 'tongue', 'cool',
    'angry', 'evil', 'thumbsup', 'thumbsdown'
];