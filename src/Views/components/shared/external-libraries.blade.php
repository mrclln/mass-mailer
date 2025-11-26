@props(['libraries' => ['tinymce', 'sweetalert', 'fontawesome', 'filepond']])

@php
    $config = config('mass-mailer-ui.libraries', []);
    $framework = config('mass-mailer-ui.framework');
    $frameworkCss = config("mass-mailer-ui.frameworks.{$framework}.css");
    $frameworkJs = config("mass-mailer-ui.frameworks.{$framework}.js");

@endphp

@if ($frameworkCss)
    <link href="{{ $frameworkCss }}" rel="stylesheet">
@endif
{{-- FontAwesome CSS --}}
@if (in_array('fontawesome', $libraries) && isset($config['fontawesome']))
    <link href="{{ $config['fontawesome']['css'] }}" rel="stylesheet">
@endif

{{-- TinyMCE CSS (handled by CDN, no separate CSS needed) --}}
@if (in_array('tinymce', $libraries) && isset($config['tinymce']))
    {{-- TinyMCE includes its own CSS via CDN --}}
@endif

{{-- FilePond CSS --}}
@if (in_array('filepond', $libraries))
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
@endif

{{-- SweetAlert2 JS --}}
@if (in_array('sweetalert', $libraries) && isset($config['sweetalert']))
    <script src="{{ $config['sweetalert']['js'] }}"></script>
@endif

{{-- TinyMCE JS (now loaded inline for reliability) --}}
@if (in_array('tinymce', $libraries) && isset($config['tinymce']))
    {{-- TinyMCE CDN is now loaded inline in tinymce-script.blade.php for better reliability --}}
@endif

{{-- FilePond JS --}}
@if (in_array('filepond', $libraries))
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
    <script src="https://unpkg.com/filepond/dist/filepond.js"></script>
@endif

@if ($frameworkJs)
    <script src="{{ $frameworkJs }}" crossorigin="anonymous"></script>
@endif
