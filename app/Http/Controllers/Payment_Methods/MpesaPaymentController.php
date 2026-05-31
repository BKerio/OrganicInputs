<?php

namespace App\Http\Controllers\Payment_Methods;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\MpesaService;
use App\Services\MpesaStkResult;
use App\Traits\Processor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MpesaPaymentController extends Controller
{
    use Processor;

    public function __construct(
        private readonly PaymentRequest $payment,
        private readonly User $user,
        private readonly MpesaService $mpesaService,
    ) {
    }

    public function pay(Request $request): View|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $paymentData = $this->payment::where(['id' => $request['payment_id'], 'is_paid' => 0])->first();
        if (!$paymentData) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $payer = json_decode($paymentData['payer_information']);
        $defaultPhone = $payer->phone ?? '';

        if ($request->isMethod('post')) {
            $phoneValidator = Validator::make($request->all(), [
                'mpesa_phone' => 'required|string|min:9|max:15',
            ]);

            if ($phoneValidator->fails()) {
                return view('payment.mpesa', [
                    'payment_data' => $paymentData,
                    'payer' => $payer,
                    'default_phone' => $defaultPhone,
                    'error' => translate('please_update_your_phone_number'),
                ]);
            }

            $reference = 'ORD-' . substr($paymentData->id, 0, 8);
            $stkResponse = $this->mpesaService->stkPush(
                phone: $request['mpesa_phone'],
                amount: $paymentData->payment_amount,
                reference: $reference
            );

            if (($stkResponse['ResponseCode'] ?? '') !== '0') {
                $message = $stkResponse['errorMessage']
                    ?? $stkResponse['ResponseDescription']
                    ?? translate('Something_went_wrong');

                return view('payment.mpesa', [
                    'payment_data' => $paymentData,
                    'payer' => $payer,
                    'default_phone' => $request['mpesa_phone'],
                    'error' => $message,
                ]);
            }

            $checkoutRequestId = $stkResponse['CheckoutRequestID'] ?? null;
            $additionalData = json_decode($paymentData->additional_data, true) ?? [];
            $additionalData['mpesa_checkout_request_id'] = $checkoutRequestId;
            $additionalData['mpesa_merchant_request_id'] = $stkResponse['MerchantRequestID'] ?? null;
            unset($additionalData['mpesa_callback']);

            $this->payment::where(['id' => $paymentData->id])->update([
                'transaction_id' => $checkoutRequestId,
                'additional_data' => json_encode($additionalData),
            ]);

            return view('payment.mpesa-pending', [
                'payment_data' => $paymentData,
                'checkout_request_id' => $checkoutRequestId,
                'status_url' => route('mpesa.status', ['payment_id' => $paymentData->id]),
                'retry_url' => route('mpesa.pay', ['payment_id' => $paymentData->id]),
            ]);
        }

        return view('payment.mpesa', [
            'payment_data' => $paymentData,
            'payer' => $payer,
            'default_phone' => $defaultPhone,
            'error' => null,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $paymentData = $this->findPaymentRequest($request['payment_id']);

        if (!$paymentData) {
            return response()->json(['status' => 'not_found', 'is_final' => true], 404);
        }

        if ($paymentData->is_paid) {
            return response()->json([
                'status' => MpesaStkResult::STATUS_SUCCESS,
                'result_code' => 0,
                'message' => translate('mpesa_payment_successful'),
                'is_final' => true,
                'is_success' => true,
            ]);
        }

        $additionalData = json_decode($paymentData->additional_data, true) ?? [];
        $callback = $additionalData['mpesa_callback'] ?? null;

        if (is_array($callback) && !empty($callback['status']) && ($callback['is_final'] ?? true)) {
            return response()->json($this->formatStatusResponse($callback));
        }

        $checkoutRequestId = $additionalData['mpesa_checkout_request_id']
            ?? $paymentData->transaction_id
            ?? null;

        if ($checkoutRequestId) {
            $queryResponse = $this->mpesaService->stkPushQuery($checkoutRequestId);
            $resultCode = (int) ($queryResponse['ResultCode'] ?? -1);
            $resultDesc = $queryResponse['ResultDesc'] ?? null;

            if (array_key_exists('ResultCode', $queryResponse) && MpesaStkResult::isFinal($resultCode)) {
                $paymentData = $this->processStkOutcome(
                    paymentData: $paymentData,
                    resultCode: $resultCode,
                    resultDesc: $resultDesc,
                    checkoutRequestId: $checkoutRequestId,
                    source: 'query',
                    callbackPayload: $queryResponse,
                );

                $additionalData = json_decode($paymentData->additional_data, true) ?? [];
                $callback = $additionalData['mpesa_callback'] ?? null;

                if (is_array($callback)) {
                    return response()->json($this->formatStatusResponse($callback));
                }
            }
        }

        return response()->json([
            'status' => MpesaStkResult::STATUS_PENDING,
            'result_code' => null,
            'message' => translate('an_mpesa_prompt_has_been_sent_enter_your_pin_to_complete_payment'),
            'is_final' => false,
            'is_success' => false,
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        Log::info('M-Pesa STK callback received', ['body' => $request->all()]);

        $callback = $request->input('Body.stkCallback') ?? $request->input('stkCallback');

        if (!$callback || !is_array($callback)) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = (int) ($callback['ResultCode'] ?? 1);
        $resultDesc = $callback['ResultDesc'] ?? null;

        $paymentData = $this->findPaymentByCheckoutId($checkoutRequestId);

        if (!$paymentData) {
            Log::warning('M-Pesa callback: payment not found', ['checkout_request_id' => $checkoutRequestId]);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        if (!$paymentData->is_paid) {
            $this->processStkOutcome(
                paymentData: $paymentData,
                resultCode: $resultCode,
                resultDesc: $resultDesc,
                checkoutRequestId: $checkoutRequestId,
                source: 'callback',
                callbackPayload: $callback,
            );
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function complete(Request $request)
    {
        $paymentData = $this->findPaymentRequest($request['payment_id']);

        if (!$paymentData) {
            return redirect()->route('payment-fail');
        }

        if ($paymentData->is_paid) {
            return $this->payment_response($paymentData, 'success');
        }

        return $this->payment_response($paymentData, 'fail');
    }

    private function processStkOutcome(
        PaymentRequest $paymentData,
        int $resultCode,
        ?string $resultDesc,
        ?string $checkoutRequestId,
        string $source,
        array $callbackPayload = [],
    ): PaymentRequest {
        if ($paymentData->is_paid) {
            return $paymentData;
        }

        $resultPayload = MpesaStkResult::payload($resultCode, $resultDesc);

        if (!$resultPayload['is_final']) {
            return $paymentData;
        }

        $additionalData = json_decode($paymentData->additional_data, true) ?? [];
        $additionalData['mpesa_callback'] = array_merge($resultPayload, [
            'result_desc' => $resultDesc,
            'checkout_request_id' => $checkoutRequestId,
            'source' => $source,
            'processed_at' => now()->toIso8601String(),
        ]);

        if (MpesaStkResult::isSuccess($resultCode)) {
            $receiptNumber = $this->extractReceiptNumber($callbackPayload);

            $this->payment::where(['id' => $paymentData->id])->update([
                'payment_method' => 'mpesa',
                'is_paid' => 1,
                'transaction_id' => $receiptNumber ?? $checkoutRequestId,
                'additional_data' => json_encode($additionalData),
            ]);

            $paymentData = $this->payment::find($paymentData->id);

            if ($paymentData && function_exists($paymentData->success_hook)) {
                call_user_func($paymentData->success_hook, $paymentData);
            }
        } else {
            $this->payment::where(['id' => $paymentData->id])->update([
                'additional_data' => json_encode($additionalData),
            ]);

            $paymentData = $this->payment::find($paymentData->id);

            if ($paymentData && function_exists($paymentData->failure_hook)) {
                call_user_func($paymentData->failure_hook, $paymentData);
            }
        }

        return $this->payment::find($paymentData->id) ?? $paymentData;
    }

    /** @param array<string, mixed> $callback */
    private function formatStatusResponse(array $callback): array
    {
        return [
            'status' => $callback['status'],
            'result_code' => (int) ($callback['result_code'] ?? -1),
            'message' => $callback['message'] ?? translate('mpesa_payment_failed'),
            'is_final' => (bool) ($callback['is_final'] ?? true),
            'is_success' => ($callback['status'] ?? '') === MpesaStkResult::STATUS_SUCCESS,
        ];
    }

    private function findPaymentRequest(?string $paymentId): ?PaymentRequest
    {
        if (!$paymentId) {
            return null;
        }

        return $this->payment::where('id', $paymentId)->first();
    }

    private function findPaymentByCheckoutId(?string $checkoutRequestId): ?PaymentRequest
    {
        if (!$checkoutRequestId) {
            return null;
        }

        $payment = $this->payment::where('transaction_id', $checkoutRequestId)->first();
        if ($payment) {
            return $payment;
        }

        return $this->payment::query()
            ->where('additional_data', 'like', '%"mpesa_checkout_request_id":"' . $checkoutRequestId . '"%')
            ->first();
    }

    private function extractReceiptNumber(array $callback): ?string
    {
        foreach ($callback['CallbackMetadata']['Item'] ?? [] as $item) {
            if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                return $item['Value'] ?? null;
            }
        }

        return null;
    }
}
