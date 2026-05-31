<?php

return [
    'enabled' => filter_var(env('SMS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'api_key' => env('SMS_API_KEY'),
    'partner_id' => env('SMS_PARTNER_ID'),
    'shortcode' => env('SMS_SHORTCODE'),
    'api_url' => env('SMS_API_URL', 'https://bulksms.fornax-technologies.com/api/services/sendsms/'),
    'order_message' => env(
        'SMS_ORDER_MESSAGE',
        'Congratulations :full_name! Order :order_ids placed. Phone: :phone. Email: :email. Total: :order_total via :payment_method. Delivery: :address. Thank you for shopping at :app_name.'
    ),
];
