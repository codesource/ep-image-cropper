$(function () {
    ClassicEditor.create(
        document.getElementById('message-editor'),
        {
            toolbar: [ 'bold', 'italic', 'link' ],
            extraPlugins: 'emoji'
        }
    );
});