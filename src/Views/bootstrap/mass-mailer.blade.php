<div class="mass-mailer-container position-relative">
    @include('mass-mailer::components.shared.external-libraries')
    @if ($hasEmailCredentials)
        <div class="row g-4">
            @include('mass-mailer::components.shared.errors', ['framework' => 'bootstrap'])
            <!-- Left Panel -->
            <div class="col-lg-4">
                <div class="card rounded shadow-sm h-100 d-flex flex-column">
                    <div class="card-body  p-4">
                        @include('mass-mailer::components.shared.variables-section', [
                            'framework' => 'bootstrap',
                        ])
                        <hr>
                        <label class="form-label fw-bold">Upload CSV (optional)</label>
                        @include('mass-mailer::components.shared.filepond', [
                            'wireModel' => 'csvFile',
                            'accept' => '.csv,.txt',
                            'multiple' => false,
                        ])
                        <hr>
                        <label class="form-label fw-bold">Attachments</label>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox"
                                wire:model.live.debounce="useAttachmentPaths" id="useAttachmentPaths">
                            <label class="form-check-label" for="useAttachmentPaths">
                                use attachments' path
                            </label>
                        </div>

                        @if (!$useAttachmentPaths)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.live.debounce="sameAttachmentForAll" id="sameAttachment">
                                <label class="form-check-label" for="sameAttachment">
                                    Same attachment for all recipients
                                </label>
                            </div>
                        @endif

                        @if ($this->sameAttachmentForAll && !$useAttachmentPaths)
                            <div class="mt-2">
                                @include('mass-mailer::components.shared.filepond', [
                                    'wireModel' => 'globalAttachments',
                                    'multiple' => true,
                                ])
                                @error('globalAttachments.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if ($useAttachmentPaths)
                            <div class="mt-3">
                                <label class="form-label fw-bold">Upload Attachment Files</label>
                                @include('mass-mailer::components.shared.filepond', [
                                    'wireModel' => 'attachmentFiles',
                                    'multiple' => true,
                                ])
                                @error('attachmentFiles.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror

                                <!-- Display uploaded files -->
                                {{-- @if (count($attachmentFiles) > 0)
                                    <div class="mt-3">
                                        <p class="form-label fw-bold">Uploaded Files:</p>
                                        <div class="list-group">
                                            @foreach ($attachmentFiles as $index => $file)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span>{{ $file->getClientOriginalName() }}</span>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        wire:click="removeUploadedAttachment({{ $index }})">
                                                        Remove
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif --}}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Right Panel -->
            <div class="col-lg-8 d-flex flex-column">
                <div class="card rounded shadow-sm  d-flex flex-column">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center ">
                            <h2 class="fs-5 fw-bold mb-3 ">Compose Email</h2>
                            @if (config('mass-mailer.multiple_senders'))
                                <div class="d-flex align-items-center gap-2 position-absolute" style="top: 10px; right: 10px;">
                                    <select wire:model.live="selectedSenderId" class="form-select form-select-sm" style="width: 250px;">
                                        <option value="">Select Sender</option>
                                        @foreach ($senders as $index => $sender)
                                            <option value="{{ $sender['id'] ?? $index }}">
                                                {{ $sender['name'] }} <{{ $sender['email'] }}>
                                            </option>
                                        @endforeach
                                        <option value="add-new">+ Add New Sender</option>
                                    </select>
                                    {{-- <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setShowAddSenderForm(true)">
                                        <i class="bi bi-plus-circle"></i> +
                                    </button> --}}
                                </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <input id="email-subject" type="text" wire:model.live="subject"
                                class="{{ mass_mailer_get_form_classes('input', 'bootstrap') }}"
                                placeholder="Subject" />
                            @error('subject')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row px-3 " wire:ignore>
                            <div id="quill-editor" class="form-control flex-grow-1 mb-2" style="min-height: 210px;"
                                ondrop="insertVariableQuill(event)" ondragover="event.preventDefault()">
                            </div>
                            @error('body')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        @include('mass-mailer::components.shared.button-group', [
                            'framework' => 'bootstrap',
                            'showPreview' => $showPreview,
                            'sending' => $sending,
                            'recipients' => $recipients,
                            'subject' => $subject,
                        ])
                    </div>
                </div>
                <!-- Recipients Table -->
                @include('mass-mailer::components.bootstrap.recipients-table', [
                    'variables' => $variables,
                    'recipients' => $this->recipients,
                    'sameAttachmentForAll' => $this->sameAttachmentForAll,
                    'perRecipientAttachments' => $this->perRecipientAttachments,
                    'useAttachmentPaths' => $this->useAttachmentPaths,
                    'attachmentFiles' => $this->attachmentFiles,
                ])
            </div>

            <!-- Modal -->
            @if ($showPreview)
                @include('mass-mailer::components.bootstrap.preview-modal', [
                    'previewContent' => $previewContent,
                    'previewEmail' => $previewEmail,
                ])
            @endif

            <!-- Attachment Modal -->
            @include('mass-mailer::components.bootstrap.attachment-modal', [
                'selectedRecipientIndex' => $selectedRecipientIndex,
                'perRecipientAttachments' => $this->perRecipientAttachments,
            ])

            <!-- Add New Sender Modal -->
            @if ($showAddSenderForm)
                <div class="modal fade show" style="display: block;" tabindex="-1" wire:ignore.self>
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Sender</h5>
                                <button type="button" class="btn-close" wire:click="closeAddSenderForm" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form wire:submit.prevent="saveNewSender">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" wire:model="newSenderName" class="form-control">
                                            @error('newSenderName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" wire:model="newSenderEmail" class="form-control">
                                            @error('newSenderEmail') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" wire:model="newSenderHost" class="form-control">
                                            @error('newSenderHost') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Port</label>
                                            <input type="number" wire:model="newSenderPort" class="form-control">
                                            @error('newSenderPort') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" wire:model="newSenderUsername" class="form-control">
                                            @error('newSenderUsername') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" wire:model="newSenderPassword" class="form-control">
                                            @error('newSenderPassword') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Encryption</label>
                                            <select wire:model="newSenderEncryption" class="form-select">
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                            </select>
                                            @error('newSenderEncryption') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" wire:click="closeAddSenderForm">Cancel</button>
                                <button type="button" class="btn btn-primary" wire:click="saveNewSender">
                                    <span wire:loading.remove>Save Sender</span>
                                    <span wire:loading>Saving...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-backdrop fade show"></div>
            @endif

            <!-- Scripts -->
            @include('mass-mailer::components.shared.quill-script', ['framework' => 'bootstrap'])

        </div>
    @else
        <div class="{{ mass_mailer_get_alert_classes('error', 'bootstrap') }}" role="alert">
            <h4 class="alert-heading">Mass Mailer Disabled</h4>
            <p>The Mass Mailer feature is currently disabled. Please check your configuration or contact your
                administrator.</p>
        </div>
    @endif
</div>
