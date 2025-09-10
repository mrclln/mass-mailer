<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px;">
        {!! $body !!}
    </div>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;">
        <p>This email was sent via {{ config('app.name') }}.</p>
        <p>If you no longer wish to receive these emails, please contact the sender.</p>
        <p style="font-size: 0.85em; color: #6c757d;">
            Powered by <a href="https://github.com/mrclln/mass-mailer" target="_blank">mrclln/mass-mailer</a>.
        </p>
    </div>
</body>

</html>
