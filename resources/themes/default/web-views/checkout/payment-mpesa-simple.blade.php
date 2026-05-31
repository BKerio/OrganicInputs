@php($mpesaGateway = $payment_gateways_list->firstWhere('key_name', 'mpesa'))
@php($mpesaImgPath = dynamicAsset(path: 'public/assets/back-end/img/modal/payment-methods/mpesa.png'))

<div class="px-3 px-md-0">
    <div class="bg-primary-light rounded p-4 mb-3 text-center">
        <img width="48" src="{{ $mpesaImgPath }}" alt="M-Pesa" class="mb-2" onerror="this.src='{{ dynamicAsset(path: 'public/assets/back-end/img/modal/payment-methods/paystack.png') }}'">
        <h5 class="mb-1">{{ translate('pay_with_mpesa') }}</h5>
        <p class="text-muted fs-13 mb-2">{{ translate('total') }}: <strong>{{ webCurrencyConverter(amount: $amount) }}</strong></p>
        @if(!empty($checkoutMpesaPhone))
            <p class="fs-13 mb-0">{{ translate('mpesa_prompt_will_be_sent_to') }}: <strong>{{ $checkoutMpesaPhone }}</strong></p>
        @endif
    </div>

    @if($mpesaGateway && $digital_payment['status'] == 1)
        <form method="post" class="digital_payment" id="mpesa_form" action="{{ route('customer.web-payment-request') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : session('guest_id') }}">
            <input type="hidden" name="customer_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : session('guest_id') }}">
            <input type="hidden" name="payment_method" value="mpesa">
            <input type="hidden" name="payment_platform" value="web">
            <input type="hidden" name="external_redirect_link" value="{{ route('web-payment-success') }}">

            <button type="submit" class="btn btn--primary btn-block btn-lg">
                {{ translate('pay_now_with_mpesa') }}
                <i class="tio-chevron-right ml-1"></i>
            </button>
        </form>

        @if($cashOnDeliveryBtnShow && $cash_on_delivery['status'])
            <div class="text-center my-3 text-muted fs-12">{{ translate('or') }}</div>
            <form action="{{ route('checkout-complete') }}" method="get" id="cash_on_delivery_form_simple">
                <input type="hidden" name="payment_method" value="cash_on_delivery">
                <button type="submit" class="btn btn-outline-secondary btn-block">
                    {{ translate('cash_on_Delivery') }}
                </button>
            </form>
        @endif
    @else
        <div class="alert alert-warning mb-0">
            {{ translate('mpesa_is_not_enabled_please_enable_it_in_admin_payment_methods') }}
        </div>
    @endif
</div>
