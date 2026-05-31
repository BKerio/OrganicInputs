<?php

namespace App\Services;

class MpesaStkResult
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_INSUFFICIENT_FUNDS = 'insufficient_funds';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_WRONG_PIN = 'wrong_pin';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SYSTEM_ERROR = 'system_error';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_PENDING = 'pending';

    /** @var array<int, string> */
    private const CODE_MAP = [
        0 => self::STATUS_SUCCESS,
        1 => self::STATUS_INSUFFICIENT_FUNDS,
        1032 => self::STATUS_CANCELLED,
        1037 => self::STATUS_TIMEOUT,
        2001 => self::STATUS_WRONG_PIN,
        1019 => self::STATUS_EXPIRED,
        1025 => self::STATUS_SYSTEM_ERROR,
    ];

    /** Result codes that mean the STK request is still in progress */
    private const PENDING_CODES = [4999];

    public static function isPending(int $resultCode): bool
    {
        return in_array($resultCode, self::PENDING_CODES, true);
    }

    public static function isFinal(int $resultCode): bool
    {
        return !self::isPending($resultCode);
    }

    public static function resolve(int $resultCode): string
    {
        return self::CODE_MAP[$resultCode] ?? self::STATUS_FAILURE;
    }

    public static function isSuccess(int $resultCode): bool
    {
        return $resultCode === 0;
    }

    public static function messageKey(string $status): string
    {
        return match ($status) {
            self::STATUS_SUCCESS => 'mpesa_payment_successful',
            self::STATUS_INSUFFICIENT_FUNDS => 'mpesa_insufficient_funds',
            self::STATUS_CANCELLED => 'mpesa_payment_cancelled',
            self::STATUS_TIMEOUT => 'mpesa_payment_timeout',
            self::STATUS_WRONG_PIN => 'mpesa_wrong_pin',
            self::STATUS_EXPIRED => 'mpesa_payment_expired',
            self::STATUS_SYSTEM_ERROR => 'mpesa_system_error',
            default => 'mpesa_payment_failed',
        };
    }

    public static function message(int $resultCode, ?string $resultDesc = null): string
    {
        $status = self::resolve($resultCode);
        $message = translate(self::messageKey($status));

        if ($resultDesc && $status === self::STATUS_FAILURE) {
            return $message . ' (' . $resultDesc . ')';
        }

        return $message;
    }

    /** @return array{status: string, result_code: int, message: string, is_final: bool, is_success: bool} */
    public static function payload(int $resultCode, ?string $resultDesc = null): array
    {
        if (self::isPending($resultCode)) {
            return [
                'status' => self::STATUS_PENDING,
                'result_code' => $resultCode,
                'message' => translate('an_mpesa_prompt_has_been_sent_enter_your_pin_to_complete_payment'),
                'is_final' => false,
                'is_success' => false,
            ];
        }

        $status = self::resolve($resultCode);

        return [
            'status' => $status,
            'result_code' => $resultCode,
            'message' => self::message($resultCode, $resultDesc),
            'is_final' => true,
            'is_success' => $status === self::STATUS_SUCCESS,
        ];
    }
}
