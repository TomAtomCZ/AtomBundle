/**
 * @description Atom frontend functionality
 */
$(function() {
    var $atoms = $('.atom'),
        $atomLines = $('.atomline'),
        $atomEntities = $('.atomentity'),
        $atomLineEditor = '<div id="atom-line-editor-body"><h3>Edit text</h3><input type="text" id="atom-line-editor"><div><div id="atom-line-editor-cancel-btn">Cancel</div><div id="atom-line-editor-send-btn">Save</div></div></div>',
        $atomEntityEditor = '<div id="atom-entity-editor-body"><h3>Edit property</h3><input type="text" id="atom-entity-editor"><div><div id="atom-entity-editor-cancel-btn">Cancel</div><div id="#atom-line-editor-send-btn">Save</div></div></div>',
        $atomConfig = $('#atom-config'),
        $atomEntityConfig = $('#atom-entity-config'),
        atomsEditable = !!JSON.parse(window.localStorage.getItem('atoms_enabled')),
        $atomToggleBtn = '<div class="atoms-' + (atomsEditable ? "enabled" : "disabled") + '" id="atom-toggle-btn" title="Toggle Atoms"></div>',
        saveMsg = function (type) {
            $('div.ckeditor-save-msg').hide();
            var typeClass,
                typeMsg;

            switch (type) {
                case 'ok':
                    typeClass = 'ckeditor-save-msg-ok';
                    typeMsg = 'Saved!';
                    break;
                case 'err':
                    typeClass = 'ckeditor-save-msg-err';
                    typeMsg = 'Error!';
                    break;
                default:
                    typeClass = 'ckeditor-save-msg-saving';
                    typeMsg = 'Saving ...';
                    break;
            }
            return '<div class="ckeditor-save-msg '+typeClass+'">'+typeMsg+'</div>';
        };

    $('body').on('click mouseenter', 'div.ckeditor-save-msg', function() {
        $(this).fadeOut();
    });

    if (atomsEditable) {
        // Mark Atoms for CKEditor
        $atoms.each(function() {
            $(this).attr('contenteditable', true);
        });

        // Show Atom on mouseover
        $atoms.on('mouseenter', function() {
            if(!$($(this)[0]).hasClass('cke_focus')) {
                $(this).css('outline', '4px dashed #009999');
            }
        });
        $atoms.on('mouseleave focus', function() {
            $(this).css('outline', '0');
        });
        $atoms.on('click', function () {
            $('#atom-line-editor-body').remove();
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
        CKEDITOR.config.imageBrowser_listUrl = $atomConfig.data('image-list-url');


        // AtomLines

        // Show AtomLine on mouseover
        $atomLines.on('mouseenter', function() {
            if(!$($(this)[0]).hasClass('cke_focus')) {
                $(this).css('outline', '4px dashed #ff8000');
            }
        });
        $atomLines.on('mouseleave focus', function() {
            $(this).css('outline', '0');
        });

        $atomLines.on('click', function (e) {
            e.preventDefault();
            var atomLineId = $(this).attr('id');
            $('#atom-line-editor-body').remove();
            $('body').append($atomLineEditor);
            $('#atom-line-editor').attr('data-atom-id', atomLineId);
            $('#atom-line-editor').val($(this).html().trim());
        });

        $('body').on('click', '#atom-line-editor-send-btn', function () {
            $('body').prepend(saveMsg());
            var atomLineId = $('#atom-line-editor').attr('data-atom-id'),
                atomLineContent = $('#atom-line-editor').val();
            $.ajax({
                url: $atomConfig.data('save-url'),
                method: 'POST',
                data: {editorID: atomLineId, editabledata: atomLineContent, atomType: 'atomline'}
            }).success(function (res) {
                $('div#' + atomLineId).html(atomLineContent);
                $('body').prepend(saveMsg('ok'));
            }).fail(function (err) {
                $('body').prepend(saveMsg('err'));
                console.log(err); // TODO: remove
            });
            $('#atom-line-editor-body').remove();
        });

        $('body').on('click', '#atom-line-editor-cancel-btn', function () {
            $('#atom-line-editor-body').remove();
        });


        // AtomEntities

        // Show AtomEntity on mouseover
        $atomEntities.on('mouseenter', function() {
            if(!$($(this)[0]).hasClass('cke_focus')) {
                $(this).css('outline', '4px dashed red');
            }
        });
        $atomEntities.on('mouseleave focus', function() {
            $(this).css('outline', '0');
        });

        $atomEntities.on('click', function () {
            var atomEntityEntity = $(this).data('atom-entity'),
                atomEntityMethod = $(this).data('atom-method'),
                atomEntityId = $(this).data('atom-id');
            $('#atom-entity-editor-body').remove();
            $('body').append($atomEntityEditor);
            $('#atom-entity-editor')
                .attr('data-atom-entity', atomEntityEntity)
                .attr('data-atom-method', atomEntityMethod)
                .attr('data-atom-id', atomEntityId)
                .val($(this).html().trim());
        });

        $('body').on('click', '#atom-entity-editor-send-btn', function () {
            $('body').prepend(saveMsg());
            var atomEntityEntity = $('#atom-entity-editor').data('atom-entity'),
                atomEntityMethod = $('#atom-entity-editor').data('atom-method'),
                atomEntityId = $('#atom-entity-editor').data('atom-id'),
                atomEntityContent = $('#atom-entity-editor').val();
            $.ajax({
                url: $atomEntityConfig.data('save-url'),
                method: 'POST',
                data: {
                    entity: atomEntityEntity,
                    method: atomEntityMethod,
                    id: atomEntityId,
                    content: atomEntityContent
                }
            }).success(function (res) {
                $('div[data-atom-entity="'+atomEntityEntity+'"][data-atom-id="'+atomEntityId+'"]').html(atomEntityContent);
                $('body').prepend(saveMsg('ok'));
            }).fail(function (err) {
                $('body').prepend(saveMsg('err'));
                console.log(err); // TODO: remove
            });
            $('#atom-entity-editor-body').remove();
        });

        $('body').on('click', '#atom-entity-editor-cancel-btn', function () {
            $('#atom-entity-editor-body').remove();
        });
    }

    // Atoms Toggle
    $('body').append($atomToggleBtn);
    $('body').on('click', '#atom-toggle-btn', function (event) {
        $.ajax({
            url: $atomConfig.data('toggle-editable'),
            method: 'POST',
            data: {
                enabled: !!JSON.parse(window.localStorage.getItem('atoms_enabled'))
            }
        }).success(function (res) {
            if (res.status == 'ok') {
                window.localStorage.setItem('atoms_enabled', JSON.stringify(res.details));
            }
            console.log('Atoms toggle response: ', res); // TODO: remove
        }).fail(function (err) {
            console.log('Atoms toggle ERROR: ', err); // TODO: remove
        }).always(function () {
            window.location.reload();
        });
    });
});
