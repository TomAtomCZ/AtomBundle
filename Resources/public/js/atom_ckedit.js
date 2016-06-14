/**
 * @description Atom frontend functionality
 */
$(function() {
    var $atoms = $('.atom'),
        $atomLines = $('.atomline'),
        $atomEntities = $('.atomentity'),
        $atomLineEditor = '<div style="position: fixed;top: 45%;z-index: 99999;width: calc(100% - 6em);margin: 0 3em;padding: 4em;background: #fff;" id="atomLineEditorBody"><h3>AtomLine editor</h3><input type="text" id="atomLineEditor" style="width: 100%;"><div style="margin-top: 1.5em;"><span id="atomLineEditorSendBtn" style="text-align: center; padding: 1em; background: green;">Save</span><span id="atomLineEditorCancelBtn" style="text-align: center; padding: 1em; margin-left: 1em; background: red;">Cancel</span></div></div>',
        $atomEntityEditor = '<div style="position: fixed;top: 45%;z-index: 99999;width: calc(100% - 6em);margin: 0 3em;padding: 4em;background: #fff;" id="atomEntityEditorBody"><h3>AtomEntity editor</h3><input type="text" id="atomEntityEditor" style="width: 100%;"><div style="margin-top: 1.5em;"><span id="atomEntityEditorSendBtn" style="text-align: center; padding: 1em; background: green;">Save</span><span id="atomEntityEditorCancelBtn" style="text-align: center; padding: 1em; margin-left: 1em; background: red;">Cancel</span></div></div>',
        $atomConfig = $('#atom-config'),
        $atomEntityConfig = $('#atom-entity-config'),
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
        $(this).fadeOut();
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
    $atoms.on('click', function () {
        $('#atomLineEditorBody').remove();
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


    // AtomLines

    // Show AtomLine on mouseover
    $atomLines.on('mouseenter', function() {
        if(!$($(this)[0]).hasClass('cke_focus')) {
            $(this).css('border', '4px dashed #ff8000');
            $(this).css('margin', '-4px');
        }
    });
    $atomLines.on('mouseleave focus', function() {
        $(this).css('border', '0');
        $(this).css('margin', '0');
    });

    $atomLines.on('click', function () {
        var atomLineId = $(this).attr('id');
        $('#atomLineEditorBody').remove();
        $('body').append($atomLineEditor);
        $('#atomLineEditor').attr('data-atom-id', atomLineId);
        $('#atomLineEditor').val($(this).html().trim());
    });

    $('body').on('click', '#atomLineEditorSendBtn', function () {
        $('body').prepend(saveMsg());
        var atomLineId = $('#atomLineEditor').attr('data-atom-id'),
            atomLineContent = $('#atomLineEditor').val();
        $.ajax({
            url: $atomConfig.data('save-url'),
            method: 'POST',
            data: {editorID: atomLineId, editabledata: atomLineContent}
        }).success(function (res) {
            $('div#' + atomLineId).html(atomLineContent);
            $('body').prepend(saveMsg('ok'));
        }).fail(function (err) {
            $('body').prepend(saveMsg('err'));
            console.log(err); // TODO: remove
        });
        $('#atomLineEditorBody').remove();
    });

    $('body').on('click', '#atomLineEditorCancelBtn', function () {
        $('#atomLineEditorBody').remove();
    });


    // AtomEntities

    // Show AtomEntity on mouseover
    $atomEntities.on('mouseenter', function() {
        if(!$($(this)[0]).hasClass('cke_focus')) {
            $(this).css('border', '4px dashed red');
            $(this).css('margin', '-4px');
        }
    });
    $atomEntities.on('mouseleave focus', function() {
        $(this).css('border', '0');
        $(this).css('margin', '0');
    });

    $atomEntities.on('click', function () {
        var atomEntityEntity = $(this).data('atom-entity'),
            atomEntityMethod = $(this).data('atom-method'),
            atomEntityId = $(this).data('atom-id');
        $('#atomEntityEditorBody').remove();
        $('body').append($atomEntityEditor);
        $('#atomEntityEditor')
            .attr('data-atom-entity', atomEntityEntity)
            .attr('data-atom-method', atomEntityMethod)
            .attr('data-atom-id', atomEntityId)
            .val($(this).html().trim());
    });

    $('body').on('click', '#atomEntityEditorSendBtn', function () {
        $('body').prepend(saveMsg());
        var atomEntityEntity = $('#atomEntityEditor').data('atom-entity'),
            atomEntityMethod = $('#atomEntityEditor').data('atom-method'),
            atomEntityId = $('#atomEntityEditor').data('atom-id'),
            atomEntityContent = $('#atomEntityEditor').val();
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
        $('#atomEntityEditorBody').remove();
    });

    $('body').on('click', '#atomEntityEditorCancelBtn', function () {
        $('#atomEntityEditorBody').remove();
    });
});