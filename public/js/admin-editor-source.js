(function (window, document) {
    'use strict';

    const attachedEditors = new WeakSet();

    function setButtonState(button, sourceMode) {
        const label = sourceMode ? 'Prikaži vizualni prikaz' : 'Prikaži HTML izvor';

        button.setAttribute('aria-label', label);
        button.setAttribute('aria-pressed', sourceMode ? 'true' : 'false');
        button.setAttribute('title', label);
        button.classList.toggle('ck-on', sourceMode);
    }

    function attach(editor, options) {
        if (! editor || attachedEditors.has(editor)) {
            return;
        }

        const editorElement = editor.ui && editor.ui.view ? editor.ui.view.element : null;
        const editable = editor.ui && editor.ui.getEditableElement ? editor.ui.getEditableElement() : null;
        const main = editorElement ? editorElement.querySelector('.ck-editor__main') : null;
        const toolbar = editorElement ? editorElement.querySelector('.ck-toolbar__items, .ck-toolbar') : null;

        if (! editorElement || ! editable || ! main || ! toolbar) {
            return;
        }

        attachedEditors.add(editor);

        const settings = options || {};
        const source = document.createElement('textarea');
        source.className = 'admin-editor-source';
        source.hidden = true;
        source.spellcheck = false;
        source.setAttribute('aria-label', settings.label || 'HTML izvor sadržaja');
        main.appendChild(source);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ck ck-button admin-editor-source-toggle';
        button.innerHTML = '<span class="admin-editor-source-icon" aria-hidden="true">&lt;/&gt;</span>';
        setButtonState(button, false);
        toolbar.appendChild(button);

        let sourceMode = false;

        function showVisualEditor() {
            editor.setData(source.value);
            editor.updateSourceElement();
            source.hidden = true;
            editable.classList.remove('admin-editor-visual-hidden');
            sourceMode = false;
            setButtonState(button, false);
            editable.dispatchEvent(new Event('input', { bubbles: true }));
            editable.focus();
        }

        function showSourceEditor() {
            source.value = editor.getData();
            editable.classList.add('admin-editor-visual-hidden');
            source.hidden = false;
            sourceMode = true;
            setButtonState(button, true);
            source.focus();
            source.setSelectionRange(0, source.value.length);
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();

            if (sourceMode) {
                showVisualEditor();
            } else {
                showSourceEditor();
            }
        });

        const form = editor.sourceElement ? editor.sourceElement.closest('form') : null;
        if (form) {
            form.addEventListener('submit', function () {
                if (sourceMode) {
                    editor.setData(source.value);
                }

                editor.updateSourceElement();
            });
        }
    }

    window.AdminEditorSource = { attach: attach };
})(window, document);
