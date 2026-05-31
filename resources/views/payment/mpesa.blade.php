@extends('payment.layouts.master')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-3">{{ translate('pay_with_mpesa') }}</h4>
                        <p class="text-muted text-center mb-4">
                            {{ translate('enter_your_mpesa_number_to_receive_a_payment_prompt') }}
                        </p>

                        @if(!empty($error))
                            <div class="alert alert-danger">{{ $error }}</div>
                        @endif

                        <div class="mb-3 text-center">
                            <strong>{{ translate('amount') }}:</strong>
                            {{ webCurrencyConverter(amount: $payment_data->payment_amount) }}
                        </div>

                        <form method="post" action="{{ route('mpesa.pay', ['payment_id' => $payment_data->id]) }}">
                            @csrf
                            <input type="hidden" name="payment_id" value="{{ $payment_data->id }}">
                            <div class="mb-3">
                                <label class="form-label" for="mpesa_phone">{{ translate('mpesa_phone_number') }}</label>
                                <input type="tel"
                                       class="form-control"
                                       id="mpesa_phone"
                                       name="mpesa_phone"
                                       value="{{ old('mpesa_phone', $default_phone ?: session('checkout_mpesa_phone')) }}"
                                       placeholder="07XXXXXXXX or 2547XXXXXXXX"
                                       required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                {{ translate('send_payment_request') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
