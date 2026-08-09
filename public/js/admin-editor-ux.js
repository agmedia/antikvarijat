(function () {
    'use strict';

    document.documentElement.setAttribute('data-admin-editor-ux', 'loaded');

    let activeEditor = null;
    let storedScrollPosition = 0;

    function fullscreenIcon(expanded) {
        const path = expanded
            ? 'M4.5 3.75a.75.75 0 0 0-1.5 0V7.5c0 .414.336.75.75.75H7.5a.75.75 0 0 0 0-1.5H5.56l2.47-2.47a.75.75 0 0 0-1.06-1.06L4.5 5.69V3.75Zm11.25-.75a.75.75 0 0 0-.75.75v1.94l-2.47-2.47a.75.75 0 0 0-1.06 1.06l2.47 2.47H12a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.75a.75.75 0 0 0-.75-.75ZM3.75 11.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75H7.5a.75.75 0 0 0 0-1.5H5.56l2.47-2.47a.75.75 0 0 0-1.06-1.06L4.5 14.44V12.5a.75.75 0 0 0-.75-.75Zm8.25 0a.75.75 0 0 0 0 1.5h1.94l-2.47 2.47a.75.75 0 1 0 1.06 1.06L15 14.31v1.94a.75.75 0 0 0 1.5 0V12.5a.75.75 0 0 0-.75-.75H12Z'
            : 'M3.75 3a.75.75 0 0 0-.75.75V7.5a.75.75 0 0 0 1.5 0V5.56l2.47 2.47a.75.75 0 0 0 1.06-1.06L5.56 4.5H7.5a.75.75 0 0 0 0-1.5H3.75Zm8.25 0a.75.75 0 0 0 0 1.5h1.94l-2.47 2.47a.75.75 0 0 0 1.06 1.06L15 5.56V7.5a.75.75 0 0 0 1.5 0V3.75a.75.75 0 0 0-.75-.75H12ZM4.5 12.5a.75.75 0 0 0-1.5 0v3.75c0 .414.336.75.75.75H7.5a.75.75 0 0 0 0-1.5H5.56l2.47-2.47a.75.75 0 0 0-1.06-1.06L4.5 14.44V12.5Zm8.03-.53a.75.75 0 0 0-1.06 1.06l2.47 2.47H12a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V12.5a.75.75 0 0 0-1.5 0v1.94l-2.47-2.47Z';

        return '<svg class="admin-editor-fullscreen-icon" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="' + path + '"></path></svg>';
    }

    function setButtonState(button, expanded) {
        button.setAttribute('aria-pressed', expanded ? 'true' : 'false');
        button.setAttribute('title', expanded ? 'Zatvori cijeli zaslon (Esc)' : 'Uredi preko cijelog zaslona');
        button.innerHTML = fullscreenIcon(expanded);
    }

    function closeFullscreen() {
        if (!activeEditor) {
            return;
        }

        activeEditor.classList.remove('admin-editor-is-fullscreen');
        const button = activeEditor.querySelector('.admin-editor-fullscreen-toggle');
        if (button) {
            setButtonState(button, false);
            button.focus();
        }

        activeEditor = null;
        document.body.classList.remove('admin-editor-fullscreen-open');
        window.scrollTo(0, storedScrollPosition);
    }

    function toggleFullscreen(editor, button) {
        if (activeEditor === editor) {
            closeFullscreen();
            return;
        }

        if (activeEditor) {
            closeFullscreen();
        }

        storedScrollPosition = window.scrollY;
        activeEditor = editor;
        editor.classList.add('admin-editor-is-fullscreen');
        document.body.classList.add('admin-editor-fullscreen-open');
        setButtonState(button, true);

        const editable = editor.querySelector('[contenteditable="true"], textarea');
        if (editable) {
            window.setTimeout(function () { editable.focus(); }, 30);
        }
    }

    function updateCount(editable, counter) {
        const text = (editable.innerText || editable.textContent || '').replace(/\s+/g, ' ').trim();
        const words = text ? text.split(' ').length : 0;
        counter.textContent = words + (words === 1 ? ' riječ' : ' riječi') + ' · ' + text.length + ' znakova';
    }

    function enhanceCkEditor5(editor) {
        if (editor.dataset.adminEditorEnhanced === 'true') {
            return;
        }

        const toolbarItems = editor.querySelector('.ck-toolbar__items, .ck-toolbar');
        const editable = editor.querySelector('[contenteditable="true"]');
        if (!toolbarItems || !editable) {
            return;
        }

        editor.dataset.adminEditorEnhanced = 'true';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ck ck-button admin-editor-fullscreen-toggle';
        button.setAttribute('aria-label', 'Uredi preko cijelog zaslona');
        setButtonState(button, false);
        button.addEventListener('click', function (event) {
            event.preventDefault();
            toggleFullscreen(editor, button);
        });
        toolbarItems.appendChild(button);

        const footer = document.createElement('div');
        footer.className = 'admin-editor-footer';
        footer.innerHTML = '<span><i class="fa-duotone fa-keyboard"></i> Uređivač teksta</span><span class="admin-editor-count"></span>';
        editor.appendChild(footer);

        const counter = footer.querySelector('.admin-editor-count');
        updateCount(editable, counter);
        editable.addEventListener('input', function () { updateCount(editable, counter); });
    }

    function enhanceCkEditor4(editor) {
        if (editor.dataset.adminEditorEnhanced === 'true') {
            return;
        }

        const toolbar = editor.querySelector('.cke_top');
        if (!toolbar) {
            return;
        }

        editor.dataset.adminEditorEnhanced = 'true';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'admin-editor-fullscreen-toggle admin-editor-fullscreen-toggle-legacy';
        button.setAttribute('aria-label', 'Uredi preko cijelog zaslona');
        setButtonState(button, false);
        button.addEventListener('click', function (event) {
            event.preventDefault();
            toggleFullscreen(editor, button);
        });
        toolbar.appendChild(button);
    }

    function enhanceEditors(root) {
        (root || document).querySelectorAll('.ck-editor').forEach(enhanceCkEditor5);
        (root || document).querySelectorAll('.cke').forEach(enhanceCkEditor4);
    }

    function init() {
        enhanceEditors(document);

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }

                    if (node.matches && node.matches('.ck-editor')) {
                        enhanceCkEditor5(node);
                    } else if (node.matches && node.matches('.cke')) {
                        enhanceCkEditor4(node);
                    } else {
                        enhanceEditors(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        window.setTimeout(function () { enhanceEditors(document); }, 250);
        window.setTimeout(function () { enhanceEditors(document); }, 1000);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && activeEditor) {
            event.preventDefault();
            closeFullscreen();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('load', function () { enhanceEditors(document); }, { once: true });
})();
