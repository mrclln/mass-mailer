@props(['framework' => 'bootstrap', 'showPreview' => false, 'sending' => false, 'recipients' => [], 'subject' => ''])

@if($framework === 'tailwind')
    <div class="flex justify-end gap-3 mt-4">
        <button
            class="{{ mass_mailer_get_color_classes('secondary', $framework) }} px-4 py-2 rounded-md transition-colors {{ mass_mailer_get_button_loading_classes($framework) }}"
            wire:click="clearForm" wire:loading.attr="disabled" wire:target="sendMassMail,previewMails">
            <i class="fas fa-trash mr-2"></i> Clear Form
        </button>
        <button
            class="{{ mass_mailer_get_color_classes('secondary', $framework) }} px-4 py-2 rounded-md transition-colors {{ mass_mailer_get_button_loading_classes($framework) }}"
            wire:click="previewMails" wire:loading.attr="disabled"
            @if (empty($recipients) || !$subject || $sending) disabled @endif>
            <span wire:loading.remove>
                <i class="fas fa-eye mr-2"></i> Preview
            </span>
            <span wire:loading>
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading...
            </span>
        </button>
        <button
            class="{{ mass_mailer_get_color_classes('primary', $framework) }} px-4 py-2 rounded-md transition-colors {{ mass_mailer_get_button_loading_classes($framework) }}"
            wire:click="sendMassMail" wire:loading.attr="disabled"
            wire:target="sendMassMail,subject" @if (empty($recipients) || !$subject) disabled @endif>
            <span wire:loading.remove wire:target='sendMassMail'>
                <i class="fas fa-paper-plane mr-2"></i> Send Mass Mail
            </span>
            <span wire:loading wire:target="sendMassMail">
                <i class="fas fa-spinner fa-spin mr-2"></i> Sending...
            </span>
        </button>
    </div>
@else
    <div class="d-flex justify-content-end gap-2 mb-4">
        <button class="btn {{ mass_mailer_get_color_classes('secondary', $framework) }} me-2 {{ mass_mailer_get_button_loading_classes($framework) }}" wire:click="clearForm" wire:loading.attr="disabled" wire:target="sendMassMail,previewMails">
            <i class="fas fa-trash me-2 fs-5"></i>
            Clear Form
        </button>
        <button class="btn {{ mass_mailer_get_color_classes('secondary', $framework) }} {{ mass_mailer_get_button_loading_classes($framework) }}" wire:click="previewMails" wire:loading.attr="disabled"
            @if(empty($recipients) || !$subject || $sending) disabled @endif>
            <span wire:loading.remove>
                <i class="fas fa-eye me-2 fs-5"></i>
                Preview
            </span>
            <span wire:loading>
                <i class="fas fa-spinner fa-spin me-2 fs-5"></i>
                Loading...
            </span>
        </button>
        <button class="btn {{ mass_mailer_get_color_classes('primary', $framework) }} {{ mass_mailer_get_button_loading_classes($framework) }}" wire:click="sendMassMail" wire:loading.attr="disabled"
            wire:target="sendMassMail,subject" @if(empty($recipients) || !$subject) disabled @endif>
            <span wire:loading.remove wire:target='sendMassMail'>
                <i class="fas fa-paper-plane me-2 fs-5"></i>
                Send Mass Mail
            </span>
            <span wire:loading wire:target="sendMassMail">
                <i class="fas fa-spinner fa-spin me-2 fs-5"></i>
                Sending...
            </span>
        </button>
    </div>
@endif
