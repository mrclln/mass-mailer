@props(['framework' => 'bootstrap'])

@php
    $quillConfig = config('mass-mailer-ui.libraries.quill.config', []);
@endphp

@script
<script>
    let quill = null;

    // Make functions globally available
    window.handleDragStart = function(event, variable) {
        console.log('Drag start for variable:', variable);
        event.dataTransfer.setData('text/plain', variable);
        event.dataTransfer.effectAllowed = 'copy';
    };

    window.insertVariableQuill = function(e) {
        console.log('Drop event triggered');
        e.preventDefault();

        // Check if Quill is properly initialized
        if (!quill) {
            console.log('Quill not initialized, initializing now...');
            initQuill();
            setTimeout(() => window.insertVariableQuill(e), 500);
            return;
        }

        if (!quill.root) {
            console.log('Quill root not found, retrying...');
            setTimeout(() => window.insertVariableQuill(e), 200);
            return;
        }

        const varName = e.dataTransfer.getData('text/plain');

        if (varName && varName.trim()) {
            try {
                // Get current cursor position
                quill.focus();
                let range = quill.getSelection();
                if (!range) {
                    const length = quill.getLength();
                    range = { index: length, length: 0 };
                }

                // Insert variable at cursor position
                const textToInsert = ' @{{ ' + varName + ' }} ';
                quill.insertText(range.index, textToInsert);

                // Update Livewire body
                $wire.set('body', quill.root.innerHTML);

                // Set cursor position after the inserted text
                const newIndex = range.index + textToInsert.length;
                quill.setSelection(newIndex, 0);

            } catch (error) {
                console.error('Error inserting variable:', error);
            }
        } else {
            console.log('No variable data received or empty');
        }
    };

    window.handleVariableClick = function(variable) {
        console.log('Variable click triggered for:', variable);

        // Check if Quill is properly initialized
        if (!quill) {
            console.log('Quill not initialized, initializing now...');
            initQuill();
            setTimeout(() => window.handleVariableClick(variable), 500);
            return;
        }

        if (!quill.root) {
            console.log('Quill root not found, retrying...');
            setTimeout(() => window.handleVariableClick(variable), 200);
            return;
        }

        if (variable && variable.trim()) {
            try {
                // Get current cursor position
                quill.focus();
                let range = quill.getSelection();
                if (!range) {
                    const length = quill.getLength();
                    range = { index: length, length: 0 };
                }

                // Insert variable at cursor position
                const textToInsert = ' @{{ ' + variable + ' }} ';
                quill.insertText(range.index, textToInsert);

                // Update Livewire body
                $wire.set('body', quill.root.innerHTML);

                // Set cursor position after the inserted text
                const newIndex = range.index + textToInsert.length;
                quill.setSelection(newIndex, 0);

            } catch (error) {
                console.error('Error inserting variable:', error);
            }
        } else {
            console.log('No variable data received or empty');
        }
    };

    window.closePreviewModal = function() {
        $wire.call('closePreview');
    };

    window.closeAttachmentModal = function() {
        $wire.call('closeAttachmentModal');
    };

    // Initialize Quill using Livewire's proper event system
    $wire.on('mount', () => {
        // Wait for Quill script to be loaded
        if (typeof Quill !== 'undefined') {
            initQuill();
        } else {
            // Wait for Quill to load
            const checkQuill = setInterval(() => {
                if (typeof Quill !== 'undefined') {
                    clearInterval(checkQuill);
                    initQuill();
                }
            }, 100);
        }
    });

    function initQuill() {
        if (quill) return;

        const editorElement = document.getElementById('quill-editor');
        if (!editorElement) {
            console.log('Quill editor element not found, retrying...');
            setTimeout(() => initQuill(), 100);
            return;
        }

        console.log('Initializing Quill...');
        try {
            // Use configuration from config file
            const config = @json($quillConfig);

            quill = new Quill('#quill-editor', config);

            console.log('Quill initialized successfully');

            // Sync with Livewire
            quill.on('text-change', () => {
                $wire.set('body', quill.root.innerHTML);
            });
        } catch (error) {
            console.error('Error initializing Quill:', error);
            setTimeout(() => initQuill(), 500);
        }
    }

    // Listen for Livewire events to clear Quill
    $wire.on('clearMassMailForm', () => {
        if (quill) {
            quill.root.innerHTML = '';
            $wire.set('body', '');
        }
    });

    // Fallback initialization attempts
    // Try immediate initialization
    if (typeof Quill !== 'undefined' && !quill && document.getElementById('quill-editor')) {
        console.log('Quill available and editor element found, attempting immediate initialization...');
        initQuill();
    }

    // Window load fallback
    window.addEventListener('load', () => {
        if (typeof Quill !== 'undefined' && !quill && document.getElementById('quill-editor')) {
            console.log('Window loaded, attempting Quill initialization...');
            initQuill();
        }
    });

    // DOM ready fallback
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Quill !== 'undefined' && !quill && document.getElementById('quill-editor')) {
            console.log('DOM ready, attempting Quill initialization...');
            initQuill();
        }
    });

    // Final fallback - try after a short delay
    setTimeout(() => {
        if (typeof Quill !== 'undefined' && !quill && document.getElementById('quill-editor')) {
            console.log('Final fallback: attempting Quill initialization after delay...');
            initQuill();
        }
    }, 1000);
</script>
@endscript
