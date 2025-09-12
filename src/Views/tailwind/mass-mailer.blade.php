<div class="min-h-screen bg-gray-50">
    @include('mass-mailer::components.shared.external-libraries')
    <div class="relative max-w-7xl mx-auto px-4 py-8">
        @if ($hasEmailCredentials)
            @include('mass-mailer::components.shared.errors', ['framework' => 'tailwind'])
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-3 ">
                    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-4 h-full">
                        @include('mass-mailer::components.shared.variables-section', [
                            'framework' => 'tailwind',
                        ])

                        <hr class="my-4">

                        <label class="block text-sm font-bold mb-2">Upload CSV (optional)</label>
                        <input type="file" wire:model="csvFile"
                            class="{{ mass_mailer_get_form_classes('file', 'tailwind') }} mb-4" accept=".csv" />

                        <hr class="my-4">

                        <label class="block text-sm font-bold mb-2">Attachments</label>
                        <div class="flex items-center mb-4">
                            <input class="mr-2" type="checkbox" wire:model.live.debounce="sameAttachmentForAll"
                                id="sameAttachment">
                            <label for="sameAttachment" class="text-sm">Same attachment for all recipients</label>
                        </div>

                        @if ($this->sameAttachmentForAll)
                            <div>
                                <input type="file" multiple wire:model="globalAttachments"
                                    class="{{ mass_mailer_get_form_classes('file', 'tailwind') }}" />
                                @error('globalAttachments.*')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Modal -->
                    @if ($showPreview)
                        @include('mass-mailer::components.tailwind.preview-modal', [
                            'previewContent' => $previewContent,
                            'previewEmail' => $previewEmail,
                        ])
                    @endif

                    <!-- Attachment Modal -->
                    @include('mass-mailer::components.tailwind.attachment-modal', [
                        'selectedRecipientIndex' => $selectedRecipientIndex,
                        'perRecipientAttachments' => $this->perRecipientAttachments,
                    ])
                </div>
                <!-- Main Area (9/12) -->
                <div class="col-span-12 lg:col-span-9 ">
                    <!-- Compose Email Card -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                        <div class="p-6 relative">
                            <div class="flex items-start justify-between mb-4">
                                <h2 class="text-lg font-bold">Compose Email</h2>
                                @if (config('mass-mailer.multiple_senders'))
                                    <div class="relative">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul
                                            class="dropdown-menu absolute right-0 mt-2 w-64 z-50 bg-white border border-gray-200 rounded shadow">
                                            @foreach (config('mass-mailer.senders') as $index => $sender)
                                                <li
                                                    class="{{ $index == $selectedSender ? 'bg-gray-100 font-semibold' : '' }}">
                                                    <a href="#"
                                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        wire:click="selectSender({{ $index }})">
                                                        <i
                                                            class="bi {{ $index == $selectedSender ? 'bi-check-circle' : 'bi-circle' }} mr-2"></i>
                                                        {{ $sender['name'] }} &lt;{{ $sender['email'] }}&gt;
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <div class="mb-4">
                                <input type="text" wire:model.live="subject"
                                    class="{{ mass_mailer_get_form_classes('input', 'tailwind') }}"
                                    placeholder="Subject" />
                                @error('subject')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4" wire:ignore>
                                <div id="quill-editor" class="w-full border border-gray-300 rounded-md"
                                    style="min-height: 210px;" ondrop="insertVariableQuill(event)"
                                    ondragover="event.preventDefault()"></div>
                                @error('body')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            @include('mass-mailer::components.shared.button-group', [
                                'framework' => 'tailwind',
                                'showPreview' => $showPreview,
                                'sending' => $sending,
                                'recipients' => $recipients,
                                'subject' => $subject,
                            ])
                        </div>
                    </div>

                    <!-- Recipients Table -->
                    @include('mass-mailer::components.tailwind.recipients-table', [
                        'variables' => $variables,
                        'recipients' => $this->recipients,
                        'sameAttachmentForAll' => $this->sameAttachmentForAll,
                        'perRecipientAttachments' => $this->perRecipientAttachments,
                    ])
                </div>

            </div>
            @include('mass-mailer::components.shared.quill-script', ['framework' => 'tailwind'])
    </div>
@else
    <div class="{{ mass_mailer_get_alert_classes('error', 'tailwind') }}" role="alert">
        <h4 class="text-red-800 font-semibold mb-2">Mass Mailer Disabled</h4>
        <p class="text-red-700">The Mass Mailer feature is currently disabled. Please check your configuration or
            contact your administrator.</p>
    </div>
    @endif
</div>
</div>
