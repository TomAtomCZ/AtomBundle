/**
 * @description Atom frontend functionality
 */
$(function() {
    var $atoms = $('.atom'),
        $atomConfig = $('#atom-config'),
        saveMsg = function (type) {
            $('div.ckeditor-save-msg').hide();
            var typeClass,
                typeMsg;

            switch (type) {
                case 'ok':
                    typeClass = 'ckeditor-save-msg-ok alert-success';
                    typeMsg = 'Saved!';
                    break;
                case 'err':
                    typeClass = 'ckeditor-save-msg-err alert-danger';
                    typeMsg = 'Error!';
                    break;
                default:
                    typeClass = 'ckeditor-save-msg-saving alert-info';
                    typeMsg = 'Saving ...';
                    break;
            }
            return '<div class="ckeditor-save-msg alert '+typeClass+'" style="position: fixed;top: 45%;z-index: 99999;width: calc(100% - 6em);margin: 0 3em;padding: 1.3em;text-align: center;opacity: 0.8;font-size: 2.5em;">'+typeMsg+'</div>';
        };

    $('body').on('click', 'div.ckeditor-save-msg', function() {
        $(this).slideUp();
    });

    // Mark Atoms for CKEditor
    $atoms.each(function() {
        $(this).attr('contenteditable', true);
    });

    // Show Atom on mouseover
    $atoms.on('mouseenter', function() {
        if(!$($(this)[0]).hasClass('cke_focus')) {
            $(this).css('border', '4px dashed #009999');
            $(this).css('margin', '-4px');
        }
    });
    $atoms.on('mouseleave focus', function() {
        $(this).css('border', '0');
        $(this).css('margin', '0');
    });

    CKEDITOR.config.inlinesave = {
        postUrl: $atomConfig.data('save-url'),
        postData: {}, // editorID, editabledata
        useJSON: false,
        useColorIcon: true,
        onSave: function() {
            $('body').prepend(saveMsg());
            return true;
        },
        onSuccess: function() {
            $('body').prepend(saveMsg('ok'));
            return true;
        },
        onFailure: function() {
            $('body').prepend(saveMsg('err'));
            return false;
        }
    };
    CKEDITOR.config.uploadUrl = $atomConfig.data('image-upload-url');
});