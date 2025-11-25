@props([
    'wireModel',
    'multiple' => false,
    'accept' => null,
    'label' => null,
    'maxSize' => null,
    'allowImagePreview' => true,
])

@php
    $wireModel = $wireModel ?? $attributes->wire('model')->value();
    $acceptAttr = $accept ? "accept=\"{$accept}\"" : '';
    $multipleAttr = $multiple ? 'true' : 'false';
    $maxSizeAttr = $maxSize ? $maxSize : config('mass-mailer.attachments.max_size', 10240);
@endphp

<div wire:ignore x-data x-init="FilePond.registerPlugin(FilePondPluginImagePreview);

FilePond.setOptions({
    credits: false,
    allowMultiple: {{ $multipleAttr }},
    {{ $allowImagePreview ? 'allowImagePreview: true,' : 'allowImagePreview: false,' }}
    {{ $accept ? "acceptedFileTypes: '{$accept}'," : '' }}
    maxFileSize: '{{ $maxSizeAttr }}KB',
    server: {
        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
            @this.upload('{{ $wireModel }}', file, load, error, progress)
        },
        revert: (filename, load) => {
            @this.removeUpload('{{ $wireModel }}', filename, load)
        },
    },
});

FilePond.create($refs.input);">
    <input type="file" x-ref="input" {{ $acceptAttr }} {{ $multiple ? 'multiple' : '' }}>
</div>
