@props(['framework' => 'bootstrap'])

@php
    $tinymceConfig = config('mass-mailer-ui.libraries.tinymce.config', []);
@endphp

{{-- TinyMCE CDN --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>

@script
<script>
    // ---------- CLICK HANDLER ----------
    window.handleVariableClick = function(variable) {
        if (!variable?.trim()) return;

        if (!window.tinymceEditor) {
            console.log('TinyMCE not ready yet, retrying...');
            setTimeout(() => window.handleVariableClick(variable), 200);
            return;
        }

        const text = ` @{{ ${variable} }} `;
        window.tinymceEditor.focus();
        window.tinymceEditor.execCommand('mceInsertContent', false, text);
        $wire.set('body', window.tinymceEditor.getContent());
    };

    // ---------- DRAG START HANDLER ----------
    window.handleDragStart = function(event, variable) {
        event.dataTransfer.setData('application/x-massmailer-variable', variable);
        event.dataTransfer.setData('text/plain', ''); // prevent browser default
        event.dataTransfer.effectAllowed = 'copy';
    };

    window.closePreviewModal = function() {
        $wire.call('closePreview');
    };

    window.closeAttachmentModal = function() {
        $wire.call('closeAttachmentModal');
    };

    function initTinyMCE() {
        if (window.tinymceEditor) return;
        if (typeof tinymce === 'undefined') return;

        tinymce.init({
            selector: '#tinymce-editor',
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap preview anchor',
                'searchreplace visualblocks code fullscreen insertdatetime media table help wordcount'
            ].join(' '),
            toolbar: [
                'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                'link image media table | removeformat | code | searchreplace | preview | fullscreen | help'
            ].join(' | '),
            branding: false,
            resize: false,
            statusbar: true,
            placeholder: "Type your email body here... You can drag variables.",
            content_style: `
                body { font-family: system-ui, -apple-system, sans-serif; font-size:14px; line-height:1.6; }
                p { margin:0 0 10px 0; }
            `,
            paste_data_images: false,
            automatic_uploads: false,
            readonly: false,
            setup: function(editor) {

                // --- Assign global reference here ---
                window.tinymceEditor = editor;

                // ---------- SYNC WITH LIVEWIRE ----------
                editor.on('Change KeyUp Undo Redo', function() {
                    $wire.set('body', editor.getContent());
                });

                // ---------- HANDLE DRAG DROP VARIABLES ----------
                editor.on('drop', function(e) {
                    const varName = e.dataTransfer.getData('application/x-massmailer-variable');
                    if (!varName) return;

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const text = ` @{{ ${varName} }} `;
                    editor.focus();
                    editor.execCommand('mceInsertContent', false, text);
                    $wire.set('body', editor.getContent());
                });

                editor.on('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });

                // ---------- CLEAN PASTE FROM GMAIL / OUTLOOK ----------
                editor.on('PastePostProcess', function(e) {
                    // Remove inline Gmail margins and empty divs
                    e.node.querySelectorAll('*').forEach(node => {
                        node.removeAttribute('style');
                        if (node.tagName === 'DIV' && node.innerHTML.trim() === '') {
                            node.remove();
                        }
                    });
                });
            }
        }).then(editors => {
            // Ensure we have global reference after initialization promise resolves
            if (editors && editors.length) {
                window.tinymceEditor = editors[0];
            }
        });
    }

    // ---------- INITIALIZATION ----------
    function tryInit() {
        if (typeof tinymce !== 'undefined' && document.getElementById('tinymce-editor') && !window.tinymceEditor) {
            initTinyMCE();
        }
    }

    setTimeout(tryInit, 100);
    document.addEventListener('DOMContentLoaded', tryInit);
    window.addEventListener('load', tryInit);
    setTimeout(tryInit, 1000);
    setTimeout(tryInit, 2000);
    if (window.Livewire) {
        document.addEventListener('livewire:load', tryInit);
    }
</script>
@endscript
