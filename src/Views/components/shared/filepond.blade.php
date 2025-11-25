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
    $uniqueId = 'filepond_' . uniqid();
@endphp
<style>
    .filepond--credits {
        display: none;
    }
</style>
<div wire:ignore x-data x-init="FilePond.registerPlugin(FilePondPluginImagePreview);

const pond{{ $uniqueId }} = FilePond.create($refs.{{ $uniqueId }});
pond{{ $uniqueId }}.setOptions({
    credits: false,
    allowMultiple: {{ $multipleAttr }},
    {{ $allowImagePreview ? 'allowImagePreview: true,' : 'allowImagePreview: false,' }}
    {{ $accept ? "acceptedFileTypes: '{$accept}'," : '' }}
    maxFileSize: '{{ $maxSizeAttr }}KB',
    name: '{{ $wireModel }}',
    server: {
        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
            console.log('FilePond processing file:', file.name, 'for field:', fieldName, 'wireModel:', '{{ $wireModel }}');
            // Upload to the correct Livewire property
            @this.upload('{{ $wireModel }}', file, load, error, progress)
        },
        revert: (filename, load) => {
            console.log('FilePond reverting file:', filename, 'for field:', '{{ $wireModel }}');
            @this.removeUpload('{{ $wireModel }}', filename, load)
        }
    },
    onaddfile: (error, file) => {
        console.log('File added to {{ $wireModel }}:', file.filename, 'type:', file.fileType, 'size:', file.fileSize);
    }
});">
    <input type="file" x-ref="{{ $uniqueId }}" {{ $acceptAttr }} {{ $multiple ? 'multiple' : '' }}
        wire:model="{{ $wireModel }}" name="{{ $wireModel }}">
</div>
