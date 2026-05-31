<?php

namespace App\Services;

use App\Models\SystemConfig;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected Client $httpClient;

    public function __construct(?Client $client = null)
    {
        $this->httpClient = $client ?: new Client([
            'timeout' => 10,
        ]);
    }

    public function sendSms(string $phoneNumber, string $message): bool
    {
        try {
            if (!SystemConfig::getValue('sms_enabled', false)) {
                Log::warning('SMS service is disabled');
                return false;
            }

            $apiKey = SystemConfig::getValue('sms_api_key');
            $partnerId = SystemConfig::getValue('sms_partner_id');
            $shortcode = SystemConfig::getValue('sms_shortcode');
            $apiUrl = SystemConfig::getValue('sms_api_url');

            if (!$apiKey || !$partnerId || !$shortcode || !$apiUrl) {
                Log::warning('SMS service is not fully configured');
                return false;
            }

            $msisdn = $this->normalizeMsisdn($phoneNumber);

            $payload = [
                'apikey' => $apiKey,
                'partnerID' => $partnerId,
                'message' => $message,
                'shortcode' => $shortcode,
                'mobile' => $msisdn,
                'msisdn' => $msisdn,
            ];

            $response = $this->httpClient->post(
                $apiUrl,
                [
                    'form_params' => $payload,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );

            $statusOk = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
            $body = (string) $response->getBody();
            $bodyArray = json_decode($body, true);

            Log::info('Bulk SMS response', [
                'status' => $response->getStatusCode(),
                'body' => $body,
                'success' => $statusOk && (isset($bodyArray['success']) ? $bodyArray['success'] : $statusOk),
            ]);

            $success = $statusOk;
            if (is_array($bodyArray) && isset($bodyArray['success'])) {
                $success = (bool) $bodyArray['success'];
            } elseif (is_array($bodyArray) && isset($bodyArray['status']) && $bodyArray['status'] === 'success') {
                $success = true;
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('SMS send failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function normalizeMsisdn(string $phoneNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '254' . $digits;
        }

        return $digits;
    }
}
