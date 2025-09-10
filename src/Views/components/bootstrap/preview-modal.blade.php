@props([
    'previewContent' => null,
    'previewEmail' => null,
])
<!-- Modal Overlay (click to close) -->
<div class="{{ mass_mailer_get_modal_classes('overlay', 'bootstrap') }}"
    style="display:block !important;"
    x-data="{ closeModal() { $wire.closePreview() } }"
    @click="if ($event.target === $event.currentTarget) closeModal()"></div>
<div class="{{ mass_mailer_get_modal_classes('container', 'bootstrap') }}" id="previewModal" tabindex="-1"
    style="display:block !important; ;" @click.stop>
    <div class="{{ mass_mailer_get_modal_classes('dialog', 'bootstrap') }}">
        <div class="{{ mass_mailer_get_modal_classes('content', 'bootstrap') }}">
            <div class="{{ mass_mailer_get_modal_classes('header', 'bootstrap') }}">
                <h5 class="text-lg font-semibold">Preview</h5>
                <button type="button" class="close fs-4 text-gray-400 hover:text-gray-600 text-xl leading-none" data-dismiss="modal" aria-label="Close"
                    wire:click="closePreview">&times;</button>
            </div>
            <div class="{{ mass_mailer_get_modal_classes('body', 'bootstrap') }}">
                @if ($previewContent)
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="flex justify-between items-center p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold mb-0">{{ $this->subject }}</h3>
                        </div>
                        <div class="p-4">
                            <p class="mb-2"><strong>To:</strong> {{ $previewEmail ?: 'N/A' }}</p>
                            <p>{!! $previewContent !!}</p>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-sm text-gray-600">
                            Preview generated on {{ now()->toDateTimeString() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
