<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Utils\OrderManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderConfirmationEmailService
{
    public function send(array $context, ?string $recipientEmail): bool
    {
        if (!config('order_confirmation.email_enabled')) {
            Log::info('Order confirmation email is disabled');
            return false;
        }

        $recipientEmail = trim($recipientEmail ?? '');
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Order confirmation email skipped: no valid recipient email');
            return false;
        }

        try {
            $message = OrderManager::formatOrderConfirmationMessage($context);

            $subjectTemplate = config('order_confirmation.email_subject');
            $subject = $subjectTemplate
                ? OrderManager::formatOrderConfirmationMessage($context, $subjectTemplate)
                : $message;

            Mail::to($recipientEmail)->send(new OrderConfirmationMail($message, $subject));

            Log::info('Order confirmation email sent', [
                'to' => $recipientEmail,
                'order_ids' => $context['order_ids'] ?? null,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Order confirmation email failed: ' . $e->getMessage());
            return false;
        }
    }
}
