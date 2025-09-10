<div class="mass-mailer-container">
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
                        <input id="csv-file" type="file" wire:model="csvFile"
                            class="{{ mass_mailer_get_form_classes('file', 'bootstrap') }} mb-2" accept=".csv" />
                        <hr>
                        <label class="form-label fw-bold">Attachments</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                wire:model.live.debounce="sameAttachmentForAll" id="sameAttachment">
                            <label class="form-check-label" for="sameAttachment">
                                Same attachment for all recipients
                            </label>
                        </div>
                        @if ($this->sameAttachmentForAll)
                            <div class="mt-2">
                                <input id="global-attachments" type="file" multiple wire:model="globalAttachments"
                                    class="{{ mass_mailer_get_form_classes('file', 'bootstrap') }}" />
                                @error('globalAttachments.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Right Panel -->
            <div class="col-lg-8 d-flex flex-column">
                <div class="card rounded shadow-sm  d-flex flex-column">
                    <div class="card-body p-4">
                        <h2 class="fs-5 fw-bold mb-3 ">Compose Email</h2>
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
