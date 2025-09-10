<div class="m-0 p-0">
    @if ($hasEmailCredentials)
        <div x-data="massMailer()" class="row g-4">
            <!-- Left Panel -->
            <div class="col-lg-4">
                <div class="card rounded shadow-sm h-100 d-flex flex-column">
                    <div class="card-body  p-4">

                        <h2 class="fs-5 fw-bold mb-3 ">Variables</h2>
                        <p class="text-muted small">Drag variables into the email content or add manually.</p>

                        <form @submit.prevent="addVariable" class="mb-3">
                            <div class="input-group">
                                <input type="text" x-model="newVariable" class="form-control"
                                    placeholder="Variable Name" />
                                <button type="submit" class="btn btn-light-primary btn-active-primary">
                                    <i class="fas fa-code me-2 fs-5"></i>
                                    Add</button>
                            </div>
                        </form>

                        <template x-for="(variable, index) in variables" :key="variable">
                            <div class="d-flex justify-content-between align-items-center bg-light rounded shadow-sm p-2 mb-2"
                                draggable="true" @dragstart="draggedVariable = variable">
                                <span x-text="variable"></span>
                                <button type="button" class="btn btn-sm btn-icon btn-light-danger"
                                    @click="deleteVariable(index)">
                                    &times;
                                </button>
                            </div>
                        </template>

                        <hr>

                        <label class="form-label fw-bold">Upload CSV (optional)</label>
                        <input type="file" @change="handleCSV($event)" class="form-control mb-2" accept=".csv" />

                        <hr>

                        <label class="form-label fw-bold">Attachments</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" x-model="sameAttachmentForAll"
                                id="sameAttachment">
                            <label class="form-check-label" for="sameAttachment">
                                Same attachment for all recipients
                            </label>
                        </div>

                        <template x-if="sameAttachmentForAll">
                            <div class="mt-2">
                                <input type="file" multiple wire:model="globalAttachments" class="form-control" />
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="col-lg-8 d-flex flex-column">
                <div class="card rounded shadow-sm  d-flex flex-column">
                    <div class="card-body p-4">

                        <h2 class="fs-5 fw-bold mb-3 ">Compose Email</h2>

                        <input type="text" x-model="subject" class="form-control mb-3" placeholder="Subject" />

                        {{-- <textarea x-model="body" @drop.prevent="insertVariable" @dragover.prevent class="form-control flex-grow-1 mb-3"
                    style="min-height: 250px;" placeholder="Type your email body here... You can drag variables."></textarea> --}}
                        <div class="row px-3 " wire:ignore>
                            <div id="quill-editor" class="form-control flex-grow-1 mb-3" style="min-height: 210px;"
                                @drop.prevent="insertVariableQuill($event)" @dragover.prevent>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mb-4">
                            <button class="btn btn-secondary" @click="previewMails"
                                :disabled="!recipients.length || !subject || !body">
                                <i class="fas fa-eye me-2 fs-5"></i>
                                Preview
                            </button>

                            <button class="btn btn-flex btn-light-primary btn-active-primary" @click="sendMassMail"
                                :disabled="!recipients.length || !subject || !body">
                                <i class="fas fa-paper-plane me-2 fs-5"></i>
                                Send Mass Mail
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recipients Table -->
                {{-- <div class="mt-4">
            <h5 class="fw-bold">Recipients Table</h5>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-bordered table-sm align-middle text-sm">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <template x-for="variable in variables" :key="variable">
                                <th x-text="variable"></th>
                            </template>
                            <template x-if="!sameAttachmentForAll">
                                <th>Attachments</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(recipient, index) in recipients" :key="index">
                            <tr>
                                <td x-text="index + 1"></td>
                                <template x-for="variable in variables" :key="variable">
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                            x-model="recipient[variable]" />
                                    </td>
                                </template>

                                <template x-if="!sameAttachmentForAll">
                                    <td>
                                        <input type="file" multiple :wire:model="`perRecipientAttachments.${index}`"
                                            class="form-control mt-1" />
                                    </td>
                                </template>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>
        </div> --}}
                <!-- Recipients Table -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body px-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold mb-0">Recipients Table</h5>
                            <button type="button" class="btn btn-flex btn-sm btn-light-primary"
                                @click="addEmptyRecipient">
                                <i class="fas fa-user-plus me-2 fs-5"></i> Add Recipient
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-bordered table-sm align-middle text-sm rounded-1 border-1">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <template x-for="variable in variables" :key="variable">
                                            <th x-text="variable"></th>
                                        </template>
                                        <template x-if="!sameAttachmentForAll">
                                            <th>Attachments</th>
                                        </template>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(recipient, index) in recipients" :key="index">
                                        <tr class="bg-body">
                                            <td x-text="index + 1"></td>
                                            <template x-for="variable in variables" :key="variable">
                                                <td>
                                                    <input type="text" class="form-control form-control-sm"
                                                        x-model="recipient[variable]" />
                                                </td>
                                            </template>

                                            <template x-if="!sameAttachmentForAll">
                                                <td>
                                                    <input type="file" multiple
                                                        :wire:model="`perRecipientAttachments.${index}`"
                                                        class="form-control mt-1" />
                                                </td>
                                            </template>

                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-icon btn-light-danger"
                                                    @click="removeRecipient(index)">
                                                    <span class="fs-4">&times;</span>
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
            <div class="modal fade" id="previewModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="previewArea">
                            <!-- Filled by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>
        @verbatim
            <script>
                window.massMailer = () => {
                    return {
                        newVariable: '',
                        variables: ['email', 'first_name', 'last_name'],
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
                                            header: 1
                                        }, {
                                            header: 2
                                        }], // custom button values
                                        [{
                                            list: 'ordered'
                                        }, {
                                            list: 'bullet'
                                        }],
                                        [{
                                            script: 'sub'
                                        }, {
                                            script: 'super'
                                        }], // superscript/subscript
                                        [{
                                            indent: '-1'
                                        }, {
                                            indent: '+1'
                                        }], // outdent/indent
                                        [{
                                            direction: 'rtl'
                                        }], // text direction

                                        [{
                                            size: ['small', false, 'large', 'huge']
                                        }], // custom dropdown
                                        [{
                                            header: [1, 2, 3, 4, 5, 6, false]
                                        }],

                                        [{
                                            color: []
                                        }, {
                                            background: []
                                        }], // dropdown with defaults from theme
                                        [{
                                            font: []
                                        }],
                                        [{
                                            align: []
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
                            const range = this.quill.getSelection(true);
                            if (range) {
                                this.quill.insertText(range.index, `{{ ${this . draggedVariable} }}`, "user");
                                this.draggedVariable = '';
                                this.body = this.quill.root.innerHTML;
                            }
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
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">${subj}</h3>

                            </div>
                            <div class="card-body">
                                <p><strong>To:</strong> ${recipient.email || 'N/A'}</p>
                                <p>${content.replace(/\n/g, '<br>')}</p>
                            </div>
                            <div class="card-footer">
                                Preview generated on ${new Date().toLocaleString()}
                            </div>
                        </div>
                    `;

                            new bootstrap.Modal(document.getElementById('previewModal')).show();
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
                        }
                    };
                }
            </script>
        @endverbatim
    @else
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Email Credentials Required</h4>
            <p>Before you can use the <strong>Mass Mailer</strong> tool, you need to configure your email sending
                credentials. This ensures your messages are sent securely and properly authenticated through your chosen
                email provider.</p>
            <p>To get started, please go here: <a href="{{ route('user.credentials.mass-mailer') }}" class="alert-link">Mass Mailer
                    Credentials</a>  and complete the required setup.</p>
            <hr>
            <p class="mb-0">Once your credentials are saved, you’ll be able to access and use the Mass Mailer tool
                without any issues. <strong>Click the link above to proceed with the setup.</strong></p>
        </div>
    @endif
</div>
