<div>
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <div>
        @if ($hasEmailCredentials)
            <div x-data="massMailer()" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                <!-- Sidebar (3/12) -->
                <div class="md:col-span-3 p-4">
                    <div class="bg-white rounded-lg shadow-sm p-6 h-full">
                        <h2 class="text-lg font-bold mb-3">Variables</h2>
                        <p class="text-gray-600 text-sm mb-4">Drag variables into the email content or add manually.</p>

                        <form @submit.prevent="addVariable" class="mb-4">
                            <div class="flex">
                                <input type="text" x-model="newVariable"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Variable Name" />
                                <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-r-md transition-colors">
                                    <i class="fas fa-code mr-2"></i> Add
                                </button>
                            </div>
                        </form>

                        <template x-for="(variable, index) in variables" :key="variable">
                            <div class="flex justify-between items-center bg-gray-100 rounded shadow-sm p-3 mb-2 cursor-move"
                                draggable="true" @dragstart="handleDragStart($event, variable)">
                                <span x-text="variable" class="font-medium"></span>
                                <button type="button" class="text-red-500 hover:text-red-700 p-1"
                                    @click="deleteVariable(index)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>

                        <hr class="my-4">

                        <label class="block text-sm font-bold mb-2">Upload CSV (optional)</label>
                        <input type="file" @change="handleCSV($event)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md mb-4" accept=".csv" />

                        <hr class="my-4">

                        <label class="block text-sm font-bold mb-2">Attachments</label>
                        <div class="flex items-center mb-4">
                            <input class="mr-2" type="checkbox" x-model="sameAttachmentForAll" id="sameAttachment">
                            <label for="sameAttachment" class="text-sm">Same attachment for all recipients</label>
                        </div>

                        <template x-if="sameAttachmentForAll">
                            <div>
                                <input type="file" multiple wire:model="globalAttachments"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Main Area (9/12) -->
                <div class="md:col-span-9 p-4">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Compose Email -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-bold mb-3">Compose Email</h2>

                            <input type="text" x-model="subject"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Subject" />

                            <div class="px-0" wire:ignore>
                                <div id="quill-editor" class="w-full border border-gray-300 rounded-md mb-3"
                                    style="min-height: 210px;" @drop.prevent="insertVariableQuill($event)"
                                    @dragover.prevent></div>
                            </div>

                            <div class="flex justify-end gap-3 mt-4">
                                <button
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors"
                                    @click.stop="previewMails" :disabled="!recipients.length || !subject || !body">
                                    <i class="fas fa-eye mr-2"></i> Preview
                                </button>

                                <button
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors"
                                    @click="sendMassMail" :disabled="!recipients.length || !subject || !body">
                                    <i class="fas fa-paper-plane mr-2"></i> Send Mass Mail
                                </button>
                            </div>
                        </div>

                        <!-- Recipients -->
                        <div class="bg-white rounded-lg shadow-sm p-6 overflow-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h5 class="text-lg font-bold mb-0">Recipients Table</h5>
                                <button type="button"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition-colors"
                                    @click="addEmptyRecipient">
                                    <i class="fas fa-user-plus mr-2"></i> Add Recipient
                                </button>
                            </div>
                            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                                <table class="w-full border border-gray-300 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="border border-gray-300 px-3 py-2 text-left">#</th>
                                            <template x-for="variable in variables" :key="variable">
                                                <th class="border border-gray-300 px-3 py-2 text-left"
                                                    x-text="variable"></th>
                                            </template>
                                            <template x-if="!sameAttachmentForAll">
                                                <th class="border border-gray-300 px-3 py-2 text-left">Attachments</th>
                                            </template>
                                            <th class="border border-gray-300 px-3 py-2 text-left">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(recipient, index) in recipients" :key="index">
                                            <tr class="bg-white hover:bg-gray-50">
                                                <td class="border border-gray-300 px-3 py-2" x-text="index + 1"></td>
                                                <template x-for="variable in variables" :key="variable">
                                                    <td class="border border-gray-300 px-3 py-2">
                                                        <input type="text"
                                                            class="w-full px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                            x-model="recipient[variable]" />
                                                    </td>
                                                </template>

                                                <template x-if="!sameAttachmentForAll">
                                                    <td class="border border-gray-300 px-3 py-2">
                                                        <input type="file" multiple
                                                            :wire:model="`perRecipientAttachments.${index}`"
                                                            class="w-full text-sm" />
                                                    </td>
                                                </template>

                                                <td class="border border-gray-300 px-3 py-2 text-center">
                                                    <button type="button" class="text-red-500 hover:text-red-700 p-1"
                                                        @click="removeRecipient(index)">
                                                        <i class="fas fa-times text-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal -->
                <div id="previewModal" class="fixed inset-0  z-[1050] bg-black bg-opacity-50 backdrop-blur-sm"  onclick="closePreviewModal()">

                    <!-- Centering wrapper -->
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div
                            class="bg-white border border-gray-300 rounded-lg shadow-lg transform opacity-0 scale-95 transition-all duration-300 ease-out max-w-4xl w-full text-xs">

                            <!-- Header -->
                            <div class="flex justify-between items-center p-3 border-b border-gray-200">
                                <h5 class="text-sm font-semibold">Preview</h5>
                                <button type="button" class="text-gray-400 hover:text-gray-600 text-lg leading-none"
                                    onclick="closePreviewModal()">&times;</button>
                            </div>

                            <!-- Scrollable body -->
                            <div id="previewArea" class="p-4 max-h-[70vh] overflow-y-auto">
                                <!-- Filled by JS -->
                            </div>
                        </div>
                    </div>
                </div>



            </div>
            <script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>
            <script>
                function closePreviewModal() {
                    const modal = document.getElementById('previewModal');
                    const modalContent = modal.querySelector('.bg-white');
                    modalContent.classList.remove('opacity-100', 'scale-100');
                    modalContent.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex', 'items-center', 'justify-center');
                    }, 300);
                }
            </script>
            @verbatim
                <script>
                    window.massMailer = () => {
                        return {
                            newVariable: '',
                            variables: ["email", "first_name", "last_name"],
                            recipients: [{
                                email: '',
                                first_name: '',
                                last_name: ''
                            }],
                            subject: '',
                            body: '', // plain HTML version of the editor
                            attachments: [],
                            perRecipientAttachments: [],
                            sameAttachmentForAll: true,
                            draggedVariable: '',
                            quill: null,

                            init() {
                                this.quill = new Quill('#quill-editor', {
                                    theme: 'snow',
                                    placeholder: "Type your email body here... You can drag variables.",
                                    modules: {
                                        toolbar: [
                                            ['bold', 'italic', 'underline', 'strike'], // toggled buttons
                                            ['blockquote', 'code-block'],

                                            [{
                                                'header': 1
                                            }, {
                                                'header': 2
                                            }], // custom button values
                                            [{
                                                'list': 'ordered'
                                            }, {
                                                'list': 'bullet'
                                            }],
                                            [{
                                                'script': 'sub'
                                            }, {
                                                'script': 'super'
                                            }], // superscript/subscript
                                            [{
                                                'indent': '-1'
                                            }, {
                                                'indent': '+1'
                                            }], // outdent/indent
                                            [{
                                                'direction': 'rtl'
                                            }], // text direction

                                            [{
                                                'size': ['small', false, 'large', 'huge']
                                            }], // custom dropdown
                                            [{
                                                'header': [1, 2, 3, 4, 5, 6, false]
                                            }],

                                            [{
                                                'color': []
                                            }, {
                                                'background': []
                                            }], // dropdown with defaults from theme
                                            [{
                                                'font': []
                                            }],
                                            [{
                                                'align': []
                                            }],

                                            ['clean'], // remove formatting button
                                            ['link'] // link only, no image/video
                                        ]
                                    }
                                });

                                // Sync editor content with body
                                this.quill.on('text-change', () => {
                                    this.body = this.quill.root.innerHTML;
                                });
                            },

                            insertVariableQuill(e) {
                                e.preventDefault();

                                // Check if Quill is initialized and ready
                                if (!this.quill || !this.quill.root) {
                                    console.warn('Quill editor not ready yet');
                                    // Retry after a short delay
                                    setTimeout(() => {
                                        this.insertVariableQuill(e);
                                    }, 100);
                                    return;
                                }

                                const data = e.dataTransfer.getData('text/plain');
                                const variable = data || this.draggedVariable;

                                if (variable) {
                                    try {
                                        // Focus the editor first
                                        this.quill.focus();

                                        // Small delay to ensure focus is complete
                                        setTimeout(() => {
                                            try {
                                                // Get current selection or cursor position
                                                let range = this.quill.getSelection();

                                                if (!range) {
                                                    // If no selection, get the current length and place at end
                                                    const length = this.quill.getLength();
                                                    range = {
                                                        index: length,
                                                        length: 0
                                                    };
                                                }

                                                // Insert the variable
                                                this.quill.insertText(range.index, `{{ ${variable} }}`);

                                                // Move cursor after the inserted text
                                                const newIndex = range.index + `{{ ${variable} }}`.length;
                                                this.quill.setSelection(newIndex);

                                                // Update the body content
                                                this.body = this.quill.root.innerHTML;
                                                this.draggedVariable = '';

                                            } catch (innerError) {
                                                console.error('Error in delayed insertion:', innerError);
                                                // Fallback: append to body
                                                this.body += `{{ ${variable} }}`;
                                            }
                                        }, 10);

                                    } catch (error) {
                                        console.error('Error inserting variable into Quill:', error);
                                        // Fallback: append to body
                                        this.body += `{{ ${variable} }}`;
                                    }
                                }
                            },

                            handleDragStart(e, variable) {
                                this.draggedVariable = variable;
                                e.dataTransfer.setData('text/plain', variable);
                                e.dataTransfer.effectAllowed = 'copy';
                            },

                            addVariable() {
                                let v = this.newVariable.trim().toLowerCase().replace(/[^a-z0-9_]/g, '');
                                if (v && !this.variables.includes(v)) {
                                    this.variables.push(v);
                                    this.newVariable = '';
                                    this.recipients.forEach(r => r[v] = '');
                                }
                            },

                            addEmptyRecipient() {
                                let newRecipient = {};
                                this.variables.forEach(v => newRecipient[v] = '');
                                this.recipients.push(newRecipient);
                                this.perRecipientAttachments.push([]);
                            },

                            removeRecipient(index) {
                                this.recipients.splice(index, 1);
                                this.perRecipientAttachments.splice(index, 1);
                            },

                            insertVariable(e) {
                                const textarea = e.target;
                                const start = textarea.selectionStart;
                                const end = textarea.selectionEnd;
                                const text = textarea.value;
                                textarea.value = text.substring(0, start) + '{{ ' + this.draggedVariable + ' }}' + text.substring(
                                    end);
                                this.draggedVariable = '';
                                this.body = textarea.value;
                            },
                            handleCSV(e) {
                                const file = e.target.files[0];
                                if (!file) return;

                                Papa.parse(file, {
                                    header: true,
                                    skipEmptyLines: true,
                                    complete: (results) => {
                                        if (!results || !results.data || results.data.length === 0) return;

                                        // Replace variables instead of appending
                                        const headers = results.meta.fields.map(h => h.trim().toLowerCase());
                                        this.variables = headers;

                                        const parsedRecipients = results.data.map(row => {
                                            const cleanedRow = {};
                                            headers.forEach(header => {
                                                cleanedRow[header] = row[header]?.trim() ?? '';
                                            });
                                            return cleanedRow;
                                        });

                                        this.recipients = parsedRecipients;

                                        // Reset per-recipient attachments
                                        this.perRecipientAttachments = new Array(this.recipients.length).fill([]);
                                    }
                                });
                            },
                            deleteVariable(index) {
                                this.variables.splice(index, 1);
                            },

                            handleGlobalAttachment(e) {
                                this.attachments = [...e.target.files];
                            },

                            handlePerRecipientAttachment(e, index) {
                                this.perRecipientAttachments[index] = [...e.target.files];
                            },

                            previewMails() {
                                const area = document.getElementById('previewArea');
                                area.innerHTML = '';

                                if (this.recipients.length === 0) return;

                                const recipient = this.recipients[0];
                                let content = this.body;
                                let subj = this.subject;

                                this.variables.forEach(variable => {
                                    const value = recipient[variable] || '';
                                    content = content.replaceAll('{{ ' + variable + ' }}', value);
                                    subj = subj.replaceAll('{{ ' + variable + ' }}', value);
                                });

                                area.innerHTML = `
                            <div class="bg-white rounded-lg shadow-sm">
                                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold mb-0">${subj}</h3>
                                </div>
                                <div class="p-4">
                                    <p class="mb-2"><strong>To:</strong> ${recipient.email || 'N/A'}</p>
                                    <p>${content.replace(/\n/g, '<br>')}</p>
                                </div>
                                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-sm text-gray-600">
                                    Preview generated on ${new Date().toLocaleString()}
                                </div>
                            </div>
                        `;

                                // Show modal
                                const modal = document.getElementById('previewModal');
                                modal.classList.remove('hidden');
                                modal.classList.add('flex', 'items-center', 'justify-center');
                                const modalContent = modal.querySelector('.bg-white');
                                modalContent.classList.remove('opacity-0', 'scale-95');
                                modalContent.classList.add('opacity-100', 'scale-100');
                            },

                            sendMassMail() {
                                const payload = this.recipients.map((recipient) => {
                                    return recipient; // only pass recipient data
                                });

                                Livewire.dispatch('sendMassMail', {
                                    payload: payload,
                                    subjectTemplate: this.subject,
                                    bodyTemplate: this.body,
                                    sameAttachmentForAll: this.sameAttachmentForAll
                                });
                            },

                            closePreviewModal() {
                                const modal = document.getElementById('previewModal');
                                const modalContent = modal.querySelector('.bg-white');
                                modalContent.classList.remove('opacity-100', 'scale-100');
                                modalContent.classList.add('opacity-0', 'scale-95');
                                setTimeout(() => {
                                    modal.classList.add('hidden');
                                    modal.classList.remove('flex', 'items-center', 'justify-center');
                                }, 300);
                            }
                        };
                    }
                </script>
            @endverbatim
        @else
            <div class="bg-red-50 border border-red-200 rounded-md p-4" role="alert">
                <h4 class="text-red-800 font-semibold mb-2">Mass Mailer Disabled</h4>
                <p class="text-red-700">The Mass Mailer feature is currently disabled. Please check your configuration
                    or
                    contact your administrator.</p>
            </div>
        @endif
    </div>
</div>
