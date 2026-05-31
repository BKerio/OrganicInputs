@extends('payment.layouts.master')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4 text-center" id="mpesa-status-card">
                        <div id="mpesa-pending-view">
                            <h4 class="mb-3">{{ translate('check_your_phone') }}</h4>
                            <p class="text-muted mb-4" id="mpesa-status-message">
                                {{ translate('an_mpesa_prompt_has_been_sent_enter_your_pin_to_complete_payment') }}
                            </p>
                            <div class="spinner-border text-primary mb-4" id="mpesa-spinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="small text-muted mb-0">{{ translate('do_not_close_this_page_until_payment_is_confirmed') }}</p>
                        </div>

                        <div id="mpesa-result-view" style="display: none;">
                            <div id="mpesa-result-icon" class="mb-3" style="font-size: 3rem; line-height: 1;"></div>
                            <h4 class="mb-3" id="mpesa-result-title"></h4>
                            <p class="text-muted mb-4" id="mpesa-result-message"></p>
                            <div class="d-flex flex-column gap-2">
                                <a href="{{ $retry_url }}" id="mpesa-retry-btn" class="btn btn-primary" style="display: none;">
                                    {{ translate('try_again') }}
                                </a>
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                    {{ translate('back_to_home') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            'use strict';

            const statusUrl = @json($status_url);
            const completeUrl = @json(route('mpesa.complete', ['payment_id' => $payment_data->id]));
            const pollIntervalMs = 2000;
            const clientTimeoutMs = 180000;
            const startedAt = Date.now();
            let pollTimer = null;
            let hasShownResult = false;

            const pendingView = document.getElementById('mpesa-pending-view');
            const resultView = document.getElementById('mpesa-result-view');
            const retryBtn = document.getElementById('mpesa-retry-btn');
            const resultIcon = document.getElementById('mpesa-result-icon');
            const resultTitle = document.getElementById('mpesa-result-title');
            const resultMessage = document.getElementById('mpesa-result-message');

            if (!pendingView || !resultView) {
                console.error('M-Pesa status UI elements not found');
                return;
            }

            function stopPolling() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            function showResultView() {
                pendingView.style.display = 'none';
                resultView.style.display = 'block';
            }

            function showFailureResult(data) {
                if (hasShownResult) return;
                hasShownResult = true;
                stopPolling();
                showResultView();

                resultIcon.textContent = '✕';
                resultIcon.style.color = '#dc3545';
                resultTitle.textContent = @json(translate('payment_failed'));
                resultMessage.textContent = data.message || @json(translate('mpesa_payment_failed'));
                retryBtn.style.display = 'block';
            }

            function showSuccessRedirect() {
                if (hasShownResult) return;
                hasShownResult = true;
                stopPolling();
                showResultView();

                resultIcon.textContent = '✓';
                resultIcon.style.color = '#198754';
                resultTitle.textContent = @json(translate('mpesa_payment_successful'));
                resultMessage.textContent = @json(translate('redirecting_to_order_confirmation'));
                window.location.href = completeUrl;
            }

            function isFinalFailure(data) {
                return data.is_final === true || data.is_final === 1 || data.is_final === 'true';
            }

            function isSuccess(data) {
                return data.is_success === true || data.is_success === 1 || data.status === 'success';
            }

            function pollPaymentStatus() {
                if (hasShownResult) {
                    stopPolling();
                    return;
                }

                if (Date.now() - startedAt > clientTimeoutMs) {
                    showFailureResult({ message: @json(translate('mpesa_payment_timeout')) });
                    return;
                }

                fetch(statusUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (isSuccess(data)) {
                            showSuccessRedirect();
                            return;
                        }

                        if (isFinalFailure(data) && !isSuccess(data)) {
                            showFailureResult(data);
                        }
                    })
                    .catch(function (err) {
                        console.error('M-Pesa status poll error', err);
                    });
            }

            pollTimer = setInterval(pollPaymentStatus, pollIntervalMs);
            pollPaymentStatus();
        })();
    </script>
@endpush
