<?php

namespace App\Models;

class SystemConfig
{
    private static array $configKeyMap = [
        'mpesa_env' => 'env',
        'mpesa_consumer_key' => 'consumer_key',
        'mpesa_consumer_secret' => 'consumer_secret',
        'mpesa_shortcode' => 'shortcode',
        'mpesa_till_no' => 'tillno',
        'mpesa_passkey' => 'passkey',
        'mpesa_callback_url' => 'callback_url',
        'mpesa_transaction_type' => 'transaction_type',
        'sms_enabled' => 'enabled',
        'sms_api_key' => 'api_key',
        'sms_partner_id' => 'partner_id',
        'sms_shortcode' => 'shortcode',
        'sms_api_url' => 'api_url',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $configKey = self::$configKeyMap[$key] ?? null;

        if ($configKey !== null) {
            $configFile = str_starts_with($key, 'sms_') ? 'sms' : 'mpesa';
            $value = config($configFile . '.' . $configKey);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}
