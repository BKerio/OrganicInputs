<?php

return [
    'email_enabled' => filter_var(env('ORDER_CONFIRMATION_EMAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    // Uses SMS_ORDER_MESSAGE when ORDER_CONFIRMATION_EMAIL_SUBJECT is not set
    'email_subject' => env('ORDER_CONFIRMATION_EMAIL_SUBJECT'),
];
