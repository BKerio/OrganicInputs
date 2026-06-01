<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminPosMpesaService
{
    public static function cacheKey(string $checkoutRequestId): string
    {
        return 'admin_pos_mpesa_' . $checkoutRequestId;
    }

    public static function getPending(string $checkoutRequestId): ?array
    {
        return Cache::get(self::cacheKey($checkoutRequestId));
    }

    public static function putPending(string $checkoutRequestId, array $data): void
    {
        Cache::put(self::cacheKey($checkoutRequestId), $data, now()->addMinutes(30));
    }

    public static function applyStkCallback(
        string $checkoutRequestId,
        int $resultCode,
        ?string $resultDesc = null,
        array $callbackPayload = [],
    ): bool {
        $pending = self::getPending($checkoutRequestId);
        if (!$pending) {
            return false;
        }

        if (!MpesaStkResult::isFinal($resultCode) && !empty($pending['stk_result']['is_final'])) {
            return true;
        }

        if (!MpesaStkResult::isFinal($resultCode)) {
            return true;
        }

        $resultPayload = MpesaStkResult::payload($resultCode, $resultDesc);
        $pending['stk_result'] = $resultPayload;
        $pending['paid'] = MpesaStkResult::isSuccess($resultCode);
        $pending['callback_received_at'] = now()->toIso8601String();

        if ($pending['paid']) {
            $pending['mpesa_receipt'] = self::extractReceiptNumber($callbackPayload);
        }

        self::putPending($checkoutRequestId, $pending);

        Log::info('Admin POS M-Pesa STK callback applied', [
            'checkout_request_id' => $checkoutRequestId,
            'status' => $resultPayload['status'],
            'result_code' => $resultCode,
        ]);

        return true;
    }

    public static function resolveStatusPayload(array $pending): ?array
    {
        if (!empty($pending['stk_result']) && ($pending['stk_result']['is_final'] ?? false)) {
            return $pending['stk_result'];
        }

        if (!empty($pending['paid'])) {
            return array_merge(
                MpesaStkResult::payload(0),
                ['is_success' => true]
            );
        }

        return null;
    }

    private static function extractReceiptNumber(array $callback): ?string
    {
        foreach ($callback['CallbackMetadata']['Item'] ?? [] as $item) {
            if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                return $item['Value'] ?? null;
            }
        }

        return null;
    }
}
