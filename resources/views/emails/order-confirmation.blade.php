<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('order_placed') }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f6f8; color: #333; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .message { font-size: 15px; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
<div class="card">
    <p class="message">{{ $messageBody }}</p>
</div>
</body>
</html>
