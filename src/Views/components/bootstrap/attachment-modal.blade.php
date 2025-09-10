@props([
    'selectedRecipientIndex' => null,
    'perRecipientAttachments' => [],
])

@if ($selectedRecipientIndex !== null)
    <!-- Modal Overlay (click to close) -->
    <div class="{{ mass_mailer_get_modal_classes('overlay', 'bootstrap') }}"
        x-data="{ closeModal() { $wire.closeAttachmentModal() } }"
        @click="if ($event.target === $event.currentTarget) closeModal()"></div>
    <!-- Modal Container -->
    <div class="{{ mass_mailer_get_modal_classes('container', 'bootstrap') }}"
        style="display:block !important;" id="attachmentModal" tabindex="-1" @click.stop>
        <div class="{{ mass_mailer_get_modal_classes('dialog', 'bootstrap') }}">
            <div class="{{ mass_mailer_get_modal_classes('content', 'bootstrap') }}">
                <div class="{{ mass_mailer_get_modal_classes('header', 'bootstrap') }}">
                    <h5 class="text-lg font-semibold">Attachments for Recipient
                        {{ $selectedRecipientIndex + 1 }}</h5>
                    <button type="button" class=" fs-4 close text-gray-400 hover:text-gray-600 text-xl leading-none"
                        wire:click="closeAttachmentModal" aria-label="Close">&times;</button>
                </div>
                <div class="{{ mass_mailer_get_modal_classes('body', 'bootstrap') }}">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Add Attachments</label>
                        <input type="file" multiple
                            wire:model="perRecipientAttachments.{{ $selectedRecipientIndex }}"
                            class="{{ mass_mailer_get_form_classes('file', 'bootstrap') }}"
                            accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif" />
                        @if ($errors->has('perRecipientAttachments.' . $selectedRecipientIndex . '.*'))
                            <div class="text-red-500 text-sm mt-1">
                                @foreach ($errors->get('perRecipientAttachments.' . $selectedRecipientIndex . '.*') as $error)
                                    {{ $error[0] }}<br>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if (isset($perRecipientAttachments[$selectedRecipientIndex]) &&
                            count($perRecipientAttachments[$selectedRecipientIndex]) > 0)
                        <div class="mb-4">
                            <h6 class="text-sm font-semibold mb-2">Current Attachments</h6>
                            <div class="space-y-2">
                                @foreach ($perRecipientAttachments[$selectedRecipientIndex] as $attachmentIndex => $attachment)
                                    <div
                                        class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                                        <div class="flex items-center">
                                            @if (str_contains($attachment->getClientMimeType(), 'image/'))
                                                <i class="fas fa-image mr-3 text-blue-500"></i>
                                            @elseif($attachment->getClientMimeType() === 'application/pdf')
                                                <i class="fas fa-file-pdf mr-3 text-red-500"></i>
                                            @elseif(str_contains($attachment->getClientMimeType(), 'word'))
                                                <i class="fas fa-file-word mr-3 text-blue-500"></i>
                                            @else
                                                <i class="fas fa-file mr-3 text-gray-500"></i>
                                            @endif
                                            <div>
                                                <p class="font-medium text-sm">
                                                    {{ $attachment->getClientOriginalName() }}</p>
                                                <p class="text-xs text-gray-600">
                                                    {{ number_format($attachment->getSize() / 1024, 1) }}
                                                    KB • {{ $attachment->getClientMimeType() }}
                                                </p>
                                            </div>
                                        </div>
                                        <button type="button" class="text-red-500 hover:text-red-700 p-1"
                                            wire:click="removeAttachment({{ $selectedRecipientIndex }}, {{ $attachmentIndex }})"
                                            aria-label="Remove {{ $attachment->getClientOriginalName() }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center text-gray-500 mb-4">
                            <i class="fas fa-paperclip fa-2x mb-2"></i>
                            <p class="text-sm">No attachments added yet</p>
                        </div>
                    @endif
                </div>
                <div class="{{ mass_mailer_get_modal_classes('footer', 'bootstrap') }}">
                    <button type="button"
                        class="{{ mass_mailer_get_color_classes('secondary', 'bootstrap') }} px-4 py-2 rounded-md transition-colors"
                        wire:click="closeAttachmentModal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
