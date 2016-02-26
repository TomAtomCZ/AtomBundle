/**
 *
 */
$(function() {
    var $atoms = $('.atom'),
        $atomConfig = $('#atom-config');
    CKEDITOR.config.toolbar = [
        { name: 'tools', items: [ 'Maximize', 'ShowBlocks', 'Source' ] },
        { name: 'clipboard', items: [ 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
        { name: 'editing', items: [ 'Find', 'Replace' ] },
        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
        { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
        { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak' ] },
        { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
        { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
        { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'Language' ] }
    ];

    CKEDITOR.disableAutoInline = true;

    $atoms.each(function() {
        $(this).attr('contenteditable', true);
        var atomId = $(this).attr('id');
        CKEDITOR.inline(atomId, {
            on: {
                blur: function(result) {
                    var html = result.editor.getData();

                    $.ajax({
                        url: $atomConfig.data('save-url'),
                        type: 'POST',
                        data: {
                            name: atomId,
                            body: html
                        },
                        dataType: 'html'
                    }).done(function(result) {
                        console.log(result);
                    }).fail(function(error) {
                        console.log(error);
                    });
                }
            }
        });
    });
});