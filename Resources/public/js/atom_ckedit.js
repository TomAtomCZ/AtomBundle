/**
 *
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
            return '<div class="ckeditor-save-msg alert '+typeClass+'" style="position: fixed;top: 30%;left: 50%;z-index: 9999; opacity: 0.8;font-size: 2.5em;">'+typeMsg+'</div>';
        };

    $('body').on('click', 'div.ckeditor-save-msg', function() {
        $(this).slideUp();
    });

    $atoms.each(function() {
        $(this).attr('contenteditable', true);
    });

    // Show Atom on mouseover
    $atoms.on('mouseenter', function() {
        $(this).css('border', '4px dashed #009999');
    });
    $atoms.on('mouseleave', function() {
        $(this).css('border', '0');
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
});