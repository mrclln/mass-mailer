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
                        @include('mass-mailer::components.shared.filepond', [
                            'wireModel' => 'csvFile',
                            'accept' => '.csv,.txt',
                            'multiple' => false,
                        ])

                        <hr class="my-4">

                        <label class="block text-sm font-bold mb-2">Attachments</label>

                        <div class="flex items-center mb-4">
                            <input class="mr-2" type="checkbox" wire:model.live.debounce="useAttachmentPaths"
                                id="useAttachmentPaths">
                            <label for="useAttachmentPaths" class="text-sm">use attachments' path</label>
                        </div>

                        @if (!$useAttachmentPaths)
                            <div class="flex items-center mb-4">
                                <input class="mr-2" type="checkbox" wire:model.live.debounce="sameAttachmentForAll"
                                    id="sameAttachment">
                                <label for="sameAttachment" class="text-sm">Same attachment for all recipients</label>
                            </div>
                        @endif

                        @if ($this->sameAttachmentForAll && !$useAttachmentPaths)
                            <div>
                                @include('mass-mailer::components.shared.filepond', [
                                    'wireModel' => 'globalAttachments',
                                    'multiple' => true,
                                ])
                                @error('globalAttachments.*')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if ($useAttachmentPaths)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload Attachment Files
                                </label>
                                @include('mass-mailer::components.shared.filepond', [
                                    'wireModel' => 'attachmentFiles',
                                    'multiple' => true,
                                ])
                                @error('attachmentFiles.*')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror

                                <!-- Display uploaded files -->
                                @if (count($attachmentFiles) > 0)
                                    <div class="mt-3">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Uploaded Files:</p>
                                        <div class="space-y-1">
                                            @foreach ($attachmentFiles as $index => $file)
                                                <div class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded">
                                                    <span class="text-sm">{{ $file->getClientOriginalName() }}</span>
                                                    <button type="button"
                                                        class="text-red-500 hover:text-red-700 text-sm"
                                                        wire:click="removeUploadedAttachment({{ $index }})">
                                                        Remove
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
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
                                    <div class="flex items-center gap-2">
                                        <select wire:model.live="selectedSenderId" class="text-sm border border-gray-300 rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Sender</option>
                                            @foreach ($senders as $index => $sender)
                                                <option value="{{ $sender['id'] ?? $index }}">
                                                    {{ $sender['name'] }} <{{ $sender['email'] }}>
                                                </option>
                                            @endforeach
                                            <option value="add-new">+ Add New Sender</option>
                                        </select>
                                        {{-- <button type="button" class="text-blue-600 hover:text-blue-800 text-sm" wire:click="setShowAddSenderForm(true)">
                                            <i class="fas fa-plus-circle"></i>
                                        </button> --}}
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
                        'useAttachmentPaths' => $this->useAttachmentPaths,
                        'attachmentFiles' => $this->attachmentFiles,
                    ])
                </div>

            </div>
            @include('mass-mailer::components.shared.quill-script', ['framework' => 'tailwind'])
    </div>

    <!-- Add New Sender Modal -->
    @if ($showAddSenderForm)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:ignore.self>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" wire:click="closeAddSenderForm"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Add New Sender
                                </h3>
                                <form wire:submit.prevent="saveNewSender" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                        <input type="text" wire:model="newSenderName" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('newSenderName') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" wire:model="newSenderEmail" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('newSenderEmail') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                                        <input type="text" wire:model="newSenderHost" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('newSenderHost') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                                        <input type="number" wire:model="newSenderPort" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('newSenderPort') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                        <input type="text" wire:model="newSenderUsername" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('newSenderUsername') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                        <input type="password" wire:model="newSenderPassword" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('newSenderPassword') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                                        <select wire:model="newSenderEncryption" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                        </select>
                                        @error('newSenderEncryption') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="saveNewSender" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Sender
                        </button>
                        <button type="button" wire:click="closeAddSenderForm" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@else
    <div class="{{ mass_mailer_get_alert_classes('error', 'tailwind') }}" role="alert">
        <h4 class="text-red-800 font-semibold mb-2">Mass Mailer Disabled</h4>
        <p class="text-red-700">The Mass Mailer feature is currently disabled. Please check your configuration or
            contact your administrator.</p>
    </div>
    @endif
</div>
</div>
