/**
 * @description Atom frontend functionality
 */
$(function () {
    const
        $body = $('body'),
        $atoms = $('.atom'),
        $atomLines = $('.atomline'),
        $atomEntities = $('.atomentity'),
        $atomLineEditorBody = '<div id="atom-line-editor-body"><h3>Edit text</h3><input type="text" id="atom-line-editor"><div><div id="atom-line-editor-cancel-btn">Cancel</div><div id="atom-line-editor-send-btn">Save</div></div></div>',
        $atomEntityEditorBody = '<div id="atom-entity-editor-body"><h3>Edit property</h3><input type="text" id="atom-entity-editor"><div><div id="atom-entity-editor-cancel-btn">Cancel</div><div id="#atom-line-editor-send-btn">Save</div></div></div>',
        $atomConfig = $('#atom-config'),
        $atomEntityConfig = $('#atom-entity-config'),
        atomsEditable = !!JSON.parse(window.localStorage.getItem('atoms_enabled')),
        $atomToggleBtn = '<div class="atoms-' + (atomsEditable ? "enabled" : "disabled") + '" id="atom-toggle-btn" title="Toggle Atoms"></div>',
        $atomLineEditor = $('#atom-line-editor'),
        $atomEntityEditor = $('#atom-entity-editor'),

        saveMsg = function (type, details) {
            $('div.ckeditor-save-msg').hide();
            let typeClass,
                typeMsg;

            switch (type) {
                case 'ok':
                    typeClass = 'ckeditor-save-msg-ok';
                    typeMsg = 'Saved!';
                    break;
                case 'error':
                    typeClass = 'ckeditor-save-msg-err';
                    typeMsg = 'Error!';
                    break;
                default:
                    typeClass = 'ckeditor-save-msg-saving';
                    typeMsg = 'Saving ...';
                    break;
            }
            // Add details to the message
            if (details) {
                typeMsg += ' (' + details + ')';
            }
            return '<div class="ckeditor-save-msg ' + typeClass + '">' + typeMsg + '</div>';
        };

    $body.on('click mouseenter', 'div.ckeditor-save-msg', function () {
        $(this).fadeOut();
    });

    if (atomsEditable) {
        // Mark Atoms for CKEditor
        $atoms.each(function () {
            $(this).attr('contenteditable', true);
        });

        // Show Atom on mouseover
        $atoms.on('mouseenter', function () {
            if (!$($(this)[0]).hasClass('cke_focus')) {
                $(this).css('outline', '4px dashed #009999');
            }
        });
        $atoms.on('mouseleave focus', function () {
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
            onSave: function () {
                $body.prepend(saveMsg());
                return true;
            },
            onSuccess: function (editor, data) {
                const responseData = JSON.parse(data);
                $body.prepend(saveMsg(responseData.status, responseData.details));
                return true;
            },
            onFailure: function (editor, data) {
                const responseData = JSON.parse(data);
                $body.prepend(saveMsg(responseData.status, responseData.details));
                return false;
            }
        };

        CKEDITOR.config.uploadUrl = $atomConfig.data('image-upload-url');
        CKEDITOR.config.imageBrowser_listUrl = $atomConfig.data('image-list-url');

        // AtomLines
        // Show AtomLine on mouseover
        $atomLines.on('mouseenter', function () {
            if (!$($(this)[0]).hasClass('cke_focus')) {
                $(this).css('outline', '4px dashed #ff8000');
            }
        });
        $atomLines.on('mouseleave focus', function () {
            $(this).css('outline', '0');
        });

        $atomLines.on('click', function (e) {
            e.preventDefault();
            const atomLineId = $(this).attr('id');
            $('#atom-line-editor-body').remove();
            $body.append($atomLineEditorBody);
            $atomLineEditor.attr('data-atom-id', atomLineId);
            $atomLineEditor.val($(this).html().trim());
        });

        $body.on('click', '#atom-line-editor-send-btn', function () {
            $body.prepend(saveMsg());

            const atomLineId = $atomLineEditor.attr('data-atom-id'),
                atomLineContent = $atomLineEditor.val();
            $.ajax({
                url: $atomConfig.data('save-url'),
                method: 'POST',
                data: {editorID: atomLineId, editabledata: atomLineContent, atomType: 'atomline'}
            }).success(function (res) {
                $('div#' + atomLineId).html(atomLineContent);
                $body.prepend(saveMsg('ok'));
                console.log(res)

            }).fail(function (err) {
                $body.prepend(saveMsg('error'));
                console.log(err); // TODO: remove
            });
            $('#atom-line-editor-body').remove();
        });

        $body.on('click', '#atom-line-editor-cancel-btn', function () {
            $('#atom-line-editor-body').remove();
        });


        // AtomEntities

        // Show AtomEntity on mouseover
        $atomEntities.on('mouseenter', function () {
            if (!$($(this)[0]).hasClass('cke_focus')) {
                $(this).css('outline', '4px dashed red');
            }
        });
        $atomEntities.on('mouseleave focus', function () {
            $(this).css('outline', '0');
        });

        $atomEntities.on('click', function () {
            const atomEntityEntity = $(this).data('atom-entity'),
                atomEntityMethod = $(this).data('atom-method'),
                atomEntityId = $(this).data('atom-id');
            $('#atom-entity-editor-body').remove();
            $body.append($atomEntityEditorBody);
            $atomEntityEditor
                .attr('data-atom-entity', atomEntityEntity)
                .attr('data-atom-method', atomEntityMethod)
                .attr('data-atom-id', atomEntityId)
                .val($(this).html().trim());
        });

        $body.on('click', '#atom-entity-editor-send-btn', function () {
            $body.prepend(saveMsg());
            const atomEntityEntity = $atomEntityEditor.data('atom-entity'),
                atomEntityMethod = $atomEntityEditor.data('atom-method'),
                atomEntityId = $atomEntityEditor.data('atom-id'),
                atomEntityContent = $atomEntityEditor.val();
            $.ajax({
                url: $atomEntityConfig.data('save-url'),
                method: 'POST',
                data: {
                    entity: atomEntityEntity,
                    method: atomEntityMethod,
                    id: atomEntityId,
                    content: atomEntityContent
                }
            }).success(function () {
                $('div[data-atom-entity="' + atomEntityEntity + '"][data-atom-id="' + atomEntityId + '"]').html(atomEntityContent);
                $body.prepend(saveMsg('ok'));
            }).fail(function (err) {
                $body.prepend(saveMsg('error'));
                console.log(err); // TODO: remove
            });
            $('#atom-entity-editor-body').remove();
        });

        $body.on('click', '#atom-entity-editor-cancel-btn', function () {
            $('#atom-entity-editor-body').remove();
        });
    }

    // Atoms Toggle
    $body.append($atomToggleBtn);
    $body.on('click', '#atom-toggle-btn', function () {
        $.ajax({
            url: $atomConfig.data('toggle-editable'),
            method: 'POST',
            data: {
                enabled: !!JSON.parse(window.localStorage.getItem('atoms_enabled'))
            }
        }).success(function (res) {
            if (res.status === 'ok') {
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
