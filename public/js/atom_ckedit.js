/**
 * @description Atom frontend functionality (rich text editing is provided by Jodit, https://xdsoft.net/jodit/)
 */
$(function () {
    const
        $body = $('body'),
        $atoms = $('.atom'),
        $atomLines = $('.atomline'),
        $atomEntities = $('.atomentity'),
        $atomLineEditorBody = '<div id="atom-line-editor-body"><h3>Edit text</h3><input type="text" id="atom-line-editor"><div><div id="atom-line-editor-cancel-btn">Cancel</div><div id="atom-line-editor-send-btn">Save</div></div></div>',
        $atomEntityEditorBody = '<div id="atom-entity-editor-body"><h3>Edit property</h3><input type="text" id="atom-entity-editor"><div><div id="atom-entity-editor-cancel-btn">Cancel</div><div id="atom-entity-editor-send-btn">Save</div></div></div>',
        $atomConfig = $('#atom-config'),
        $atomEntityConfig = $('#atom-entity-config'),
        atomsEditable = !!JSON.parse(window.localStorage.getItem('atoms_enabled')),
        $atomToggleBtn = '<div class="atoms-' + (atomsEditable ? "enabled" : "disabled") + '" id="atom-toggle-btn" title="Toggle Atoms"></div>',

        saveMsg = function (type, details) {
            $('div.ckeditor-save-msg').remove();
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
            // Use text(), the details come from the server and must not be interpreted as HTML
            return $('<div class="ckeditor-save-msg"></div>').addClass(typeClass).text(typeMsg);
        },

        parseResponse = function (res) {
            if (typeof res === 'string') {
                try {
                    return JSON.parse(res);
                } catch (e) {
                    return {status: 'error'};
                }
            }
            return res || {status: 'error'};
        };

    $body.on('click mouseenter', 'div.ckeditor-save-msg', function () {
        $(this).fadeOut();
    });

    if (atomsEditable) {
        // Atoms (rich text editor)
        if (typeof Jodit === 'undefined') {
            console.error('Atom: the Jodit editor is not loaded, Atoms cannot be edited.');
        } else {
            initAtomEditors();
        }

        // AtomLines
        // Show AtomLine on mouseover
        $atomLines.on('mouseenter', function () {
            $(this).css('outline', '4px dashed #ff8000');
        });
        $atomLines.on('mouseleave focus', function () {
            $(this).css('outline', '0');
        });

        $atomLines.on('click', function (e) {
            e.preventDefault();
            const atomLineId = $(this).attr('id');
            $('#atom-line-editor-body').remove();
            $body.append($atomLineEditorBody);
            const $atomLineEditor = $('#atom-line-editor');
            $atomLineEditor.attr('data-atom-id', atomLineId);
            $atomLineEditor.val($(this).html().trim());
        });

        $body.on('click', '#atom-line-editor-send-btn', function () {
            $body.prepend(saveMsg());
            const $atomLineEditor = $('#atom-line-editor');
            const atomLineId = $atomLineEditor.attr('data-atom-id'),
                atomLineContent = $atomLineEditor.val();
            $.ajax({
                url: $atomConfig.data('save-url'),
                method: 'POST',
                data: {editorID: atomLineId, editabledata: atomLineContent, atomType: 'atomline'}
            }).done(function (res) {
                const responseData = parseResponse(res);
                if (responseData.status === 'ok') {
                    $('span#' + atomLineId).html(atomLineContent);
                    $body.prepend(saveMsg('ok', responseData.details));
                } else {
                    $body.prepend(saveMsg('error', responseData.details));
                }
            }).fail(function () {
                $body.prepend(saveMsg('error'));
            });
            $('#atom-line-editor-body').remove();
        });

        $body.on('click', '#atom-line-editor-cancel-btn', function () {
            $('#atom-line-editor-body').remove();
        });


        // AtomEntities

        // Show AtomEntity on mouseover
        $atomEntities.on('mouseenter', function () {
            $(this).css('outline', '4px dashed red');
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
            const $atomEntityEditor = $('#atom-entity-editor');
            $atomEntityEditor
                .attr('data-atom-entity', atomEntityEntity)
                .attr('data-atom-method', atomEntityMethod)
                .attr('data-atom-id', atomEntityId)
                .val($(this).html().trim());
        });

        $body.on('click', '#atom-entity-editor-send-btn', function () {
            $body.prepend(saveMsg());
            const $atomEntityEditor = $('#atom-entity-editor');
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
            }).done(function (res) {
                const responseData = parseResponse(res);
                if (responseData.status === 'ok') {
                    const escapedEntity = atomEntityEntity.replace(/\\/g, '\\\\');
                    $('div[data-atom-entity="' + escapedEntity + '"][data-atom-id="' + atomEntityId + '"]').html(responseData.content);
                    $body.prepend(saveMsg('ok'));
                } else {
                    $body.prepend(saveMsg('error', responseData.details));
                }
            }).fail(function () {
                $body.prepend(saveMsg('error'));
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
        }).done(function (res) {
            if (res.status === 'ok') {
                window.localStorage.setItem('atoms_enabled', JSON.stringify(res.details));
            }
        }).always(function () {
            window.location.reload();
        });
    });


    /**
     * Turns every Atom into an inline Jodit editor. The editor is created lazily, the first time the
     * mouse enters the Atom (or on click on touch devices), so pages with many Atoms stay fast.
     */
    function initAtomEditors() {
        const
            saveUrl = $atomConfig.data('save-url'),
            uploadUrl = $atomConfig.data('image-upload-url'),
            imageListUrl = $atomConfig.data('image-list-url'),
            // Same image types as the original CKEditor uploadimage plugin, plus WebP
            uploadableImageTypes = /^image\/(jpeg|png|gif|bmp|webp)$/i,
            language = resolveLanguage($atomConfig.data('locale')),
            translations = {
                cs_cz: {
                    'Save (Ctrl+S)': 'Uložit (Ctrl+S)',
                    'Uploaded images': 'Nahrané obrázky',
                    'Loading...': 'Načítání...',
                    'No images have been uploaded yet': 'Zatím nebyly nahrány žádné obrázky',
                    'Images could not be loaded': 'Obrázky se nepodařilo načíst',
                    'Only JPEG, PNG, GIF, BMP and WebP images can be uploaded': 'Nahrát lze pouze obrázky JPEG, PNG, GIF, BMP a WebP',
                    'Upload failed': 'Nahrání se nezdařilo'
                }
            };

        // Show Atom on mouseover
        $atoms.on('mouseenter', function () {
            getEditor(this);
            if (!$(this).hasClass('atom-editing')) {
                $(this).css('outline', '4px dashed #009999');
            }
        });
        $atoms.on('mouseleave focusin', function () {
            $(this).css('outline', '0');
        });
        $atoms.on('click', function () {
            $('#atom-line-editor-body').remove();
            // Touch devices may not fire mouseenter before the click
            if (!$(this).data('atomEditor')) {
                getEditor(this).s.focus();
            }
        });

        function getEditor(element) {
            const $element = $(element);
            let editor = $element.data('atomEditor');
            if (!editor) {
                editor = createEditor(element);
                $element.data('atomEditor', editor);
            }
            return editor;
        }

        function createEditor(element) {
            const editor = Jodit.make(element, {
                // Edit the Atom in place, the page styles stay applied to the content
                inline: true,
                language: language,
                i18n: translations,
                toolbarAdaptive: false,
                toolbarSticky: true,
                statusbar: false,
                // Plain textarea for the HTML source and no beautifier: nothing is downloaded from a CDN
                sourceEditor: 'area',
                beautifyHTML: false,
                // Insert images in their natural size (as CKEditor did)
                imageDefaultWidth: null,
                // YouTube (and Vimeo) embedding via the built-in "video" button
                video: {
                    defaultWidth: 640,
                    defaultHeight: 480
                },
                // Drag & drop, paste and the "Upload" tab of the image button go to the existing upload route
                uploader: {
                    url: uploadUrl,
                    filesVariableName: function () {
                        return 'upload';
                    },
                    // The route accepts a single file in the "upload" field, so each file is sent separately
                    customUploadFunction: function (formData, showProgress) {
                        const errors = [];
                        const images = formData.getAll('upload').filter(function (file) {
                            if (file && uploadableImageTypes.test(file.type)) {
                                return true;
                            }
                            errors.push((file && file.name ? file.name + ': ' : '') + editor.i18n('Only JPEG, PNG, GIF, BMP and WebP images can be uploaded'));
                            return false;
                        });

                        showProgress(10);
                        return Promise.all(images.map(function (file) {
                            return uploadImage(editor, file);
                        })).then(function (results) {
                            showProgress(100);
                            const urls = [];
                            results.forEach(function (result) {
                                if (result.url) {
                                    urls.push(result.url);
                                } else {
                                    errors.push(result.error);
                                }
                            });
                            return {urls: urls, errors: errors};
                        });
                    },
                    isSuccess: function (resp) {
                        return resp.urls.length > 0;
                    },
                    getMessage: function (resp) {
                        return resp.errors.join('\n') || editor.i18n('Upload failed');
                    },
                    process: function (resp) {
                        if (resp.errors.length) {
                            editor.message.error(resp.errors.join('\n'), 6000);
                        }
                        return {
                            baseurl: '',
                            path: '',
                            files: resp.urls,
                            isImages: resp.urls.map(function () {
                                return true;
                            }),
                            error: 0,
                            msg: ''
                        };
                    }
                },
                buttons: [
                    {
                        name: 'atomSave',
                        icon: 'save',
                        tooltip: 'Save (Ctrl+S)',
                        mode: Jodit.constants.MODE_SPLIT, // Enabled in the HTML source view too
                        exec: function (jodit) {
                            saveAtom(jodit);
                        }
                    },
                    '|',
                    'undo', 'redo', '|',
                    'paragraph', 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'eraser', '|',
                    'ul', 'ol', 'outdent', 'indent', 'align', '|',
                    'font', 'fontsize', 'brush', '|',
                    'link', 'image',
                    {
                        name: 'atomImages',
                        icon: 'folder',
                        tooltip: 'Uploaded images',
                        exec: function (jodit) {
                            openImageBrowser(jodit);
                        }
                    },
                    'video', 'table', 'hr', 'symbols', '|',
                    'find', 'source'
                ]
            });

            editor.registerCommand('atomSave', {
                exec: function () {
                    saveAtom(editor);
                    return false; // Prevents the browser "Save page" dialog
                },
                hotkeys: ['ctrl+s', 'cmd+s']
            });

            // Only the Atom that is being edited shows its toolbar
            editor.e.on('focus', function () {
                $atoms.filter('.atom-editing').not(element).removeClass('atom-editing');
                $(element).addClass('atom-editing').css('outline', '0');
            });

            return editor;
        }

        /**
         * Sends one image to the existing upload route (file in the "upload" field, as CKEditor did)
         */
        function uploadImage(editor, file) {
            const data = new FormData();
            data.append('upload', file, file.name);

            return fetch(uploadUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json();
            }).then(function (res) {
                if (res && Number(res.uploaded) === 1 && res.url) {
                    return {url: res.url};
                }
                return {error: file.name + ': ' + ((res && res.error && res.error.message) || editor.i18n('Upload failed'))};
            }).catch(function () {
                return {error: file.name + ': ' + editor.i18n('Upload failed')};
            });
        }

        function saveAtom(editor) {
            // Make sure changes made in the HTML source view are synchronized
            if (editor.getRealMode() !== Jodit.constants.MODE_WYSIWYG) {
                editor.setMode(Jodit.constants.MODE_WYSIWYG);
            }

            $body.prepend(saveMsg());
            $.ajax({
                url: saveUrl,
                method: 'POST',
                data: {
                    editorID: editor.element.id,
                    editabledata: editor.value
                }
            }).done(function (res) {
                const responseData = parseResponse(res);
                $body.prepend(saveMsg(responseData.status === 'ok' ? 'ok' : 'error', responseData.details));
            }).fail(function () {
                $body.prepend(saveMsg('error'));
            });
        }

        /**
         * Lets the user pick one of the already uploaded images (replacement of the CKEditor imagebrowser plugin)
         */
        function openImageBrowser(editor) {
            let pickedImage = null;

            editor.s.save();

            const dialog = editor.dlg({closeOnClickOverlay: true}),
                $grid = $('<div class="atom-image-browser"></div>').text(editor.i18n('Loading...'));

            dialog.e.on(dialog, 'afterClose', function () {
                editor.s.restore();
                if (pickedImage) {
                    editor.s.insertImage(pickedImage, null, editor.o.imageDefaultWidth);
                }
            });

            dialog.setHeader(editor.i18n('Uploaded images'));
            dialog.setContent($grid[0]);
            dialog.setSize(Math.min(800, window.innerWidth - 32), Math.min(560, window.innerHeight - 32));
            dialog.open(true, true); // destroy after close, modal

            $.ajax({
                url: imageListUrl,
                dataType: 'json',
                cache: false
            }).done(function (images) {
                $grid.empty();
                images = Array.isArray(images) ? images.filter(function (item) {
                    return item && typeof item.image === 'string' && item.image !== '';
                }) : [];

                if (!images.length) {
                    $grid.text(editor.i18n('No images have been uploaded yet'));
                    return;
                }

                images.forEach(function (item) {
                    const fileName = item.image.split('/').pop();
                    $('<button type="button" class="atom-image-browser__item"></button>')
                        .attr('title', fileName)
                        .append($('<img alt="" loading="lazy">').attr('src', item.image))
                        .on('click', function () {
                            pickedImage = item.image;
                            dialog.close();
                        })
                        .appendTo($grid);
                });
            }).fail(function () {
                $grid.text(editor.i18n('Images could not be loaded'));
            });
        }

        /**
         * Maps the request locale (e.g. "cs") to a Jodit language key (e.g. "cs_cz")
         */
        function resolveLanguage(locale) {
            const languages = Jodit.lang || {},
                normalized = String(locale || '').toLowerCase().replace('-', '_'),
                short = normalized.split('_')[0];

            if (normalized && languages[normalized]) {
                return normalized;
            }
            if (short && languages[short]) {
                return short;
            }
            const match = short ? Object.keys(languages).filter(function (key) {
                return key.indexOf(short + '_') === 0;
            })[0] : null;

            return match || 'en';
        }
    }
});
