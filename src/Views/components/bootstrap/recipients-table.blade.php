@props([
    'variables' => [],
    'recipients' => [],
    'sameAttachmentForAll' => true,
    'perRecipientAttachments' => [],
])

<!-- Recipients Table -->
<div class="card shadow-sm mt-4">
    <div class="card-body px-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-bold mb-0">Recipients Table</h5>
            <button type="button"
                class="btn {{ mass_mailer_get_color_classes('primary', 'bootstrap') }}"
                wire:click="addEmptyRecipient">
                <i class="fas fa-user-plus me-2 fs-5"></i> Add Recipient
            </button>
        </div>
        <div class="{{ mass_mailer_get_table_classes('container', 'bootstrap') }}"
            style="max-height: 300px; overflow-y: auto;">
            <table class="{{ mass_mailer_get_table_classes('table', 'bootstrap') }}">
                <thead class="{{ mass_mailer_get_table_classes('thead', 'bootstrap') }}">
                    <tr>
                        <th class="{{ mass_mailer_get_table_classes('th', 'bootstrap') }}">#</th>
                        @foreach ($variables as $variable)
                            <th class="{{ mass_mailer_get_table_classes('th', 'bootstrap') }}">
                                {{ $variable }}</th>
                        @endforeach
                        @if (!$sameAttachmentForAll)
                            <th class="{{ mass_mailer_get_table_classes('th', 'bootstrap') }}">
                                Attachments</th>
                        @endif
                        <th class="{{ mass_mailer_get_table_classes('th', 'bootstrap') }}">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recipients as $index => $recipient)
                        <tr class="{{ mass_mailer_get_table_classes('tr', 'bootstrap') }}">
                            <td class="{{ mass_mailer_get_table_classes('td', 'bootstrap') }}">
                                {{ $index + 1 }}</td>
                            @foreach ($variables as $variable)
                                <td class="{{ mass_mailer_get_table_classes('td', 'bootstrap') }}">
                                    <input type="text"
                                        class="{{ mass_mailer_get_form_classes('input', 'bootstrap') }} {{ mass_mailer_get_button_size_classes('sm', 'bootstrap') }}"
                                        wire:model.live="recipients.{{ $index }}.{{ $variable }}" />
                                    @if ($variable === 'email' && $errors->has('recipients.' . $index . '.email'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('recipients.' . $index . '.email') }}
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                            @if (!$sameAttachmentForAll)
                                <td
                                    class="{{ mass_mailer_get_table_classes('td', 'bootstrap') }} text-center">
                                    <button type="button"
                                        class="btn {{ mass_mailer_get_button_size_classes('sm', 'bootstrap') }} {{ mass_mailer_get_color_classes('primary', 'bootstrap') }}"
                                        wire:click="openAttachmentModal({{ $index }})"
                                        aria-label="Manage attachments for recipient {{ $index + 1 }}">
                                        <i class="fas fa-paperclip fs-5"></i>
                                        @if (isset($perRecipientAttachments[$index]) && count($perRecipientAttachments[$index]) > 0)
                                            <span
                                                class="badge bg-primary ms-1">{{ count($perRecipientAttachments[$index]) }}</span>
                                        @endif
                                    </button>
                                </td>
                            @endif
                            <td
                                class="{{ mass_mailer_get_table_classes('td', 'bootstrap') }} text-center">
                                <button type="button"
                                    class="btn {{ mass_mailer_get_button_size_classes('sm', 'bootstrap') }} {{ mass_mailer_get_color_classes('danger', 'bootstrap') }}"
                                    wire:click="removeRecipient({{ $index }})">
                                    <span class="fs-6">&times;</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
