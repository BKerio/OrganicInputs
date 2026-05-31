<?php

namespace App\Services;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    private string $baseUrl;
    private ?string $consumerKey;
    private ?string $consumerSecret;
    private ?string $shortcode;
    private ?string $tillno;
    private ?string $passkey;
    private ?string $callbackUrl;
    private string $env;

    public function __construct()
    {
        $this->env = (string) SystemConfig::getValue('mpesa_env', 'sandbox');
        $this->baseUrl = $this->env === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $this->consumerKey = SystemConfig::getValue('mpesa_consumer_key');
        $this->consumerSecret = SystemConfig::getValue('mpesa_consumer_secret');
        $this->shortcode = (string) SystemConfig::getValue('mpesa_shortcode');
        $this->tillno = (string) SystemConfig::getValue('mpesa_till_no');
        $this->passkey = SystemConfig::getValue('mpesa_passkey');
        $this->callbackUrl = SystemConfig::getValue('mpesa_callback_url');
    }

    public function isConfigured(): bool
    {
        return !empty($this->consumerKey)
            && !empty($this->consumerSecret)
            && !empty($this->shortcode)
            && !empty($this->passkey)
            && !empty($this->callbackUrl);
    }

    /**
     * Safaricom sandbox (174379) only accepts CustomerPayBillOnline for STK Push.
     * Live: PayBill → CustomerPayBillOnline | Till/Buy Goods → CustomerBuyGoodsOnline
     */
    private function resolveTransactionType(): string
    {
        $configured = trim((string) SystemConfig::getValue('mpesa_transaction_type', ''));

        if ($this->isSandboxShortcode()) {
            return 'CustomerPayBillOnline';
        }

        if (in_array($configured, ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'], true)) {
            return $configured;
        }

        if ($this->tillno !== '' && $this->tillno !== $this->shortcode) {
            return 'CustomerBuyGoodsOnline';
        }

        return 'CustomerPayBillOnline';
    }

    private function isSandboxShortcode(): bool
    {
        return $this->env === 'sandbox' || $this->shortcode === '174379';
    }

    /** @return array{BusinessShortCode: string, PartyB: string, TransactionType: string} */
    private function resolveStkIdentifiers(string $transactionType): array
    {
        if ($transactionType === 'CustomerBuyGoodsOnline') {
            $till = $this->tillno !== '' ? $this->tillno : $this->shortcode;

            return [
                'BusinessShortCode' => $this->shortcode,
                'PartyB' => $till,
                'TransactionType' => 'CustomerBuyGoodsOnline',
            ];
        }

        return [
            'BusinessShortCode' => $this->shortcode,
            'PartyB' => $this->shortcode,
            'TransactionType' => 'CustomerPayBillOnline',
        ];
    }

    private function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        if (!$response->successful()) {
            Log::error('M-Pesa OAuth failed', ['body' => $response->body()]);
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }

    public function stkPush(string $phone, float|int $amount, string $reference = 'Payment'): array
    {
        if (!$this->isConfigured()) {
            return [
                'errorMessage' => 'M-Pesa is not configured. Set MPESA_* variables in .env',
            ];
        }

        $phone = preg_replace('/\D/', '', $phone);
        $phone = preg_replace('/^0/', '254', $phone);
        if (str_starts_with($phone, '7')) {
            $phone = '254' . $phone;
        }

        $transactionType = $this->resolveTransactionType();
        $identifiers = $this->resolveStkIdentifiers($transactionType);

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $token = $this->getAccessToken();
        if (!$token) {
            return ['errorMessage' => 'Unable to authenticate with M-Pesa'];
        }

        $payload = [
            'BusinessShortCode' => $identifiers['BusinessShortCode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $identifiers['TransactionType'],
            'Amount' => (int) ceil($amount),
            'PartyA' => $phone,
            'PartyB' => $identifiers['PartyB'],
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => substr($reference, 0, 12),
            'TransactionDesc' => substr($reference, 0, 13),
        ];

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

        $body = $response->json() ?? [];
        if (!$response->successful() || ($body['ResponseCode'] ?? '') !== '0') {
            Log::error('M-Pesa STK push failed', [
                'env' => $this->env,
                'transaction_type' => $identifiers['TransactionType'],
                'payload' => $payload,
                'body' => $body,
            ]);
        }

        return $body;
    }

    public function stkPushQuery(string $checkoutRequestId): array
    {
        if (!$this->isConfigured() || $checkoutRequestId === '') {
            return ['errorMessage' => 'M-Pesa query not configured'];
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $token = $this->getAccessToken();
        if (!$token) {
            return ['errorMessage' => 'Unable to authenticate with M-Pesa'];
        }

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/mpesa/stkpushquery/v1/query', $payload);

        $body = $response->json() ?? [];

        if (!$response->successful()) {
            Log::warning('M-Pesa STK query HTTP error', ['body' => $body, 'checkout_request_id' => $checkoutRequestId]);
        }

        return $body;
    }
}
