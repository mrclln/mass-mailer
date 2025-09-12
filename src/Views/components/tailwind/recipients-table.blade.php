@props([
    'variables' => [],
    'recipients' => [],
    'sameAttachmentForAll' => true,
    'perRecipientAttachments' => [],
    'selectedSender' => 0,
])

<!-- Recipients Table Card -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 ">
    <div class="relative px-6 py-4 border-b border-gray-200">
        <h5 class="text-lg font-bold">Recipients Table</h5>
    </div>
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <div></div>
            <button type="button"
                class="{{ mass_mailer_get_color_classes('primary', 'tailwind') }} px-3 py-1 rounded-md text-sm transition-colors"
                wire:click="addEmptyRecipient" wire:loading.attr="disabled">
                <i class="fas fa-user-plus mr-2"></i> Add Recipient
            </button>
        </div>
        <div class="{{ mass_mailer_get_table_classes('container', 'tailwind') }}">
            <table class="{{ mass_mailer_get_table_classes('table', 'tailwind') }}">
                <thead class="{{ mass_mailer_get_table_classes('thead', 'tailwind') }}">
                    <tr>
                        <th class="{{ mass_mailer_get_table_classes('th', 'tailwind') }}">#</th>
                        @foreach ($variables as $variable)
                            <th class="{{ mass_mailer_get_table_classes('th', 'tailwind') }}">
                                {{ $variable }}</th>
                        @endforeach
                        @if (!$sameAttachmentForAll)
                            <th class="{{ mass_mailer_get_table_classes('th', 'tailwind') }}">
                                Attachments</th>
                        @endif
                        <th class="{{ mass_mailer_get_table_classes('th', 'tailwind') }}">
                            Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recipients as $index => $recipient)
                        <tr class="{{ mass_mailer_get_table_classes('tr', 'tailwind') }}">
                            <td class="{{ mass_mailer_get_table_classes('td', 'tailwind') }}">
                                {{ $index + 1 }}</td>
                            @foreach ($variables as $variable)
                                <td class="{{ mass_mailer_get_table_classes('td', 'tailwind') }}">
                                    <input type="text"
                                        class="{{ mass_mailer_get_form_classes('input', 'tailwind') }} {{ mass_mailer_get_button_size_classes('sm', 'tailwind') }}"
                                        wire:model.live="recipients.{{ $index }}.{{ $variable }}" />
                                    @if ($variable === 'email' && $errors->has('recipients.' . $index . '.email'))
                                        <div class="text-red-500 text-xs mt-1">
                                            {{ $errors->first('recipients.' . $index . '.email') }}
                                        </div>
                                    @endif
                                </td>
                            @endforeach

                            @if (!$sameAttachmentForAll)
                                <td
                                    class="{{ mass_mailer_get_table_classes('td', 'tailwind') }} text-center">
                                    <button type="button"
                                        class="{{ mass_mailer_get_color_classes('primary', 'tailwind') }} px-2 py-1 rounded text-sm transition-colors"
                                        wire:click="openAttachmentModal({{ $index }})"
                                        aria-label="Manage attachments for recipient {{ $index + 1 }}">
                                        <i class="fas fa-paperclip mr-1"></i>
                                        @if (isset($perRecipientAttachments[$index]) && count($perRecipientAttachments[$index]) > 0)
                                            <span
                                                class="bg-red-500 text-white text-xs px-1 rounded ml-1">{{ count($perRecipientAttachments[$index]) }}</span>
                                        @endif
                                    </button>
                                    @if ($errors->has('perRecipientAttachments.' . $index . '.*'))
                                        <div class="text-red-500 text-xs mt-1">
                                            @foreach ($errors->get('perRecipientAttachments.' . $index . '.*') as $error)
                                                {{ $error[0] }}<br>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endif

                            <td
                                class="{{ mass_mailer_get_table_classes('td', 'tailwind') }} text-center">
                                <button type="button" class="text-red-500 hover:text-red-700 p-1"
                                    wire:click="removeRecipient({{ $index }})"
                                    wire:loading.attr="disabled">
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
