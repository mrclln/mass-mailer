@props(['libraries' => ['quill', 'sweetalert', 'fontawesome']])

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

{{-- Quill CSS --}}
@if (in_array('quill', $libraries) && isset($config['quill']))
    <link href="{{ $config['quill']['css'] }}" rel="stylesheet">
@endif

{{-- SweetAlert2 JS --}}
@if (in_array('sweetalert', $libraries) && isset($config['sweetalert']))
    <script src="{{ $config['sweetalert']['js'] }}"></script>
@endif

{{-- Quill JS --}}
@if (in_array('quill', $libraries) && isset($config['quill']))
    <script src="{{ $config['quill']['js'] }}"></script>
@endif
@if ($frameworkJs)
    <script src="{{ $frameworkJs }}" crossorigin="anonymous"></script>
@endif
