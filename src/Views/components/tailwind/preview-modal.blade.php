@props([
    'previewContent' => null,
    'previewEmail' => null,
])

@if ($previewContent)
    <!-- Modal Overlay (click to close) -->
    <div class="{{ mass_mailer_get_modal_classes('overlay', 'tailwind') }}"
        x-data="{ closeModal() { $wire.closePreview() } }"
        @click="if ($event.target === $event.currentTarget) closeModal()"></div>

    <!-- Modal Container -->
    <div class="{{ mass_mailer_get_modal_classes('container', 'tailwind') }}">

        <!-- Centering wrapper -->
        <div class="{{ mass_mailer_get_modal_classes('dialog', 'tailwind') }}" @click.stop>
            <div
                class="{{ mass_mailer_get_modal_classes('content', 'tailwind') }} max-w-4xl w-full">

                <!-- Header -->
                <div class="{{ mass_mailer_get_modal_classes('header', 'tailwind') }}">
                    <h5 class="text-lg font-semibold">Preview</h5>
                    <button type="button"
                        class="text-gray-400 hover:text-gray-600 text-lg leading-none"
                        wire:click="closePreview" wire:loading.attr="disabled">&times;</button>
                </div>

                <!-- Scrollable body -->
                <div class="{{ mass_mailer_get_modal_classes('body', 'tailwind') }}">
                    <div class="bg-white rounded-lg shadow-sm">
                        <div
                            class="flex justify-between items-center p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold mb-0">{{ $this->subject }}</h3>
                        </div>
                        <div class="p-4">
                            <p class="mb-2"><strong>To:</strong> {{ $previewEmail ?: 'N/A' }}
                            </p>
                            <p>{!! $previewContent !!}</p>
                        </div>
                        <div
                            class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-sm text-gray-600">
                            Preview generated on {{ now()->toDateTimeString() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
