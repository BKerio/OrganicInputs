
<?php
$totalCartItemProduct = 0;
foreach ($cartItems['cartItemValue'] as $key => $item) {
    if (is_array($item)) {
        $totalCartItemProduct++;
    }
}
$isExpanded = $totalCartItemProduct > 0;
?>
<form action="{{route('vendor.pos.order-place') }}" method="post" id='order-place'>
    @csrf
    <div class="d-flex flex-column gap-20 pb-9">
        <div class="">
            <button
                class="btn-collapse d-flex gap-3 align-items-center justify-content-between bg-section2 rounded-top rounded-on-collapse text-dark border-0 p-3 w-100 {{ $isExpanded ? '' : 'collapsed' }}"
                type="button"
                data-toggle="collapse"
                data-target="#collapsecCartList"
                aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                <span class="fw-medium">{{ translate('Cart_Item_List') }}</span>
                @if($isExpanded)
                    <div class="btn-collapse-icon">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                @endif
            </button>
            <div class="collapse {{ $isExpanded ? 'show' : '' }}" id="collapsecCartList">
                <div>
                    <div class="table-responsive pos-cart-table max-h-300">
                        <table class="table align-middle m-0 text-dark table-borderless tr-border-bottom">
                            <tbody>
                                @foreach($cartItems['cartItemValue'] as $key => $item)
                                    @if(is_array($item))
                                        <tr>
                                            <td class="overflow-hidden">
                                                <div class="media d-flex align-items-center gap-2"
                                                     data-toggle="tooltip"
                                                     title="{{ $item['name'] }}">
                                                    <img class="avatar border rounded object-fit-cover"
                                                         src="{{ getStorageImages(path:$item['image'], type: 'backend-product') }}"
                                                         alt="{{ $item['name'] . ' ' . translate('image') }}">
                                                    <div class="media-body">
                                                        <h5 class="text-hover-primary mb-0 d-flex flex-wrap gap-2 fw-medium fs-12 min-w-130">
                                                            {{ Str::limit($item['name'], 12) }}
                                                        </h5>
                                                        <small>{{ Str::limit($item['variant'], 20) }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div
                                                    class="qty-input-group form-control d-flex gap-2 align-items-center w-max-content">
                                                    <button type="button" class="qty-count qty-count--minus fs-18"
                                                            data-action="minus">-
                                                    </button>
                                                    <input class="product-qty text-center action-pos-update-quantity"
                                                           type="number" name="product-qty" min="1"
                                                           value="{{$item['quantity']}}" data-key="{{$key}}"
                                                           data-product-key="{{ $item['id'] }}"
                                                           data-product-variant="{{ $item['variant'] }}">
                                                    <button type="button" class="qty-count qty-count--add fs-18"
                                                            data-action="plus">+
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-medium">
                                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount:$item['productSubtotal']), currencyCode: getCurrencyCode()) }}
                                                </div>
                                            </td>
                                            <td class="pe-3">
                                                <div class="d-flex justify-content-end">
                                                    <a href="javascript:" data-id="{{$item['id']}}"
                                                       data-variant="{{$item['variant']}}"
                                                       class="btn btn-danger btn-circle remove-from-cart"
                                                       style="--size: 20px;">
                                                        <i class="fi fi-rr-cross-small mt-2px"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="">
            <div class="bg-section2 p-3 rounded-top mb-2">
                <div class="fw-medium">{{ translate('Billing_Summary') }}</div>
            </div>

            <div class="">
                <dl>
                    <div class="d-flex gap-2 justify-content-between px-3 py-2">
                        <dt class="text-dark text-capitalize fw-normal">{{ translate('sub_total') }} :</dt>
                        <dd>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['subtotal'] + $cartItems['discountOnProduct']), currencyCode: getCurrencyCode()) }}</dd>
                    </div>

                    <div class="d-flex gap-2 justify-content-between px-3 py-2">
                        <dt class="text-dark text-capitalize fw-normal">{{ translate('product_Discount') }} :</dt>
                        <dd>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: round($cartItems['discountOnProduct'], 2)), currencyCode: getCurrencyCode()) }}</dd>
                    </div>

                    <div class="d-flex gap-2 justify-content-between px-3 py-2">
                        <dt class="title-color gap-2 text-capitalize fw-normal">{{ translate('coupon_Discount') }}:
                        </dt>
                        <dd>
                            <button id="coupon_discount"
                                    class="btn btn-sm p-0 border-0 d-flex gap-3 text--primary fw-normal shadow-none"
                                    type="button" data-toggle="modal" data-target="#add-coupon-discount">
                                <i class="fi fi-rr-pencil"></i>
                                <span class="text-underline">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['couponDiscount']), currencyCode: getCurrencyCode()) }}
                        </span>
                            </button>
                        </dd>
                    </div>

                    <div class="d-flex gap-2 justify-content-between px-3 py-2">
                        <dt class="text-dark text-capitalize fw-normal">{{ translate('extra_Discount') }} :</dt>
                        <dd>
                            <div class="d-flex align-items-center gap-3">
                                @if($cartItems['extraDiscount'] > 0)
                                    <a href="#" class="text-danger lh-1 " id="pos-extra-discount-remove-vendor">
                                        <i class="fi fi-rr-trash"></i>
                                    </a>
                                @endif
                                <button id="extra_discount"
                                        class="btn btn-sm p-0 border-0 d-flex gap-3 text--primary fw-normal shadow-none"
                                        type="button" data-toggle="modal" data-target="#add-discount">
                                    <i class="fi fi-rr-pencil"></i>
                                    <span class="text-underline">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['extraDiscount']), currencyCode: getCurrencyCode()) }}
                                    </span>
                                </button>
                            </div>
                        </dd>
                    </div>

                    @php($systemTaxConfig = getTaxModuleSystemTypesConfig())
                    @if($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included'])
                        <div class="d-flex gap-2 justify-content-between px-3 py-2">
                            <dt class="text-dark text-capitalize fw-normal">{{ translate('VAT/TAX') }} :</dt>
                            <dd>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: round($cartItems['totalTax'] ?? 0, 2)), currencyCode: getCurrencyCode()) }}</dd>
                        </div>
                    @endif

                    <div class="d-flex gap-2 justify-content-between px-3 py-2">
                        <dt class="fs-18 text-dark fw-semibold text-capitalize">{{ translate('total') }} :</dt>
                        <dd class="fs-18 text-dark fw-semibold">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($cartItems['total'] + ($cartItems['totalTax'] ?? 0) - $cartItems['couponDiscount'])), currencyCode: getCurrencyCode()) }}
                        </dd>
                    </div>
                </dl>
            </div>
            <input type="hidden" class="form-control total-amount" name="amount" min="0" step="0.01"
                   value="{{usdToDefaultCurrency(amount: $cartItems['total']+ ($cartItems['totalTax'] ?? 0) -$cartItems['couponDiscount'])}}"
                   readonly>
            <div class="p-3 bg-section rounded">
                @php($mpesaConfigured = app(\App\Services\MpesaService::class)->isConfigured())
                @php($defaultMpesaPhone = preg_replace('/\D/', '', $summaryData['currentCustomerData']->phone ?? ''))
                @php($defaultMpesaPhone = str_starts_with($defaultMpesaPhone, '254') ? substr($defaultMpesaPhone, 3) : (str_starts_with($defaultMpesaPhone, '0') ? substr($defaultMpesaPhone, 1) : $defaultMpesaPhone))
                <div>
                    <div class="text-dark fw-medium text-capitalize d-flex mb-3">{{ translate('paid_By') }}:</div>
                    <input type="radio" value="mpesa" id="mpesa" name="type" hidden checked>
                    <label for="mpesa" class="btn btn--primary fw-normal btn-sm mb-3">
                        {{ translate('M-Pesa') }} STK Push
                    </label>
                    @if(!$mpesaConfigured)
                        <div class="alert alert-warning py-2 fs-12 mb-3">
                            {{ translate('mpesa_is_not_configured_set_mpesa_variables_in_env') }}
                        </div>
                    @endif
                </div>
                <div class="pos-mpesa-section">
                    <label class="form-label text-capitalize fw-normal mb-2" for="pos_mpesa_phone">
                        {{ translate('mpesa_phone_number') }} <span class="text-danger">*</span>
                    </label>
                    <div class="pos-mpesa-phone-wrap">
                        <div class="input-group pos-mpesa-phone-group">
                            <span class="input-group-text pos-mpesa-dial-code" aria-hidden="true">
                                <span class="pos-mpesa-flag">🇰🇪</span>
                                <span class="fw-semibold">+254</span>
                            </span>
                            <input type="text"
                                   inputmode="numeric"
                                   autocomplete="tel"
                                   class="form-control pos-mpesa-phone-input"
                                   id="pos_mpesa_phone"
                                   placeholder="712345678"
                                   value="{{ $defaultMpesaPhone }}"
                                   maxlength="10"
                                   data-intl-initialized="true"
                                   {{ $totalCartItemProduct <= 0 ? 'disabled' : '' }}>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2 fs-12">
                        {{ translate('you_will_receive_mpesa_prompt_on_this_number') }}
                    </small>
                </div>
            </div>

            <?php
            $cartQty = $cartItems['countItem'] ?? 0;
            $clearCartTooltip = $cartQty < 1 ? translate('no_product_added_in_the_cart') : '';
            $holdOrderTooltip = $cartQty < 1 ? translate('no_product_added_in_the_cart_to_hold_order') : '';
            ?>
            <div class="d-flex gap-2 gap-sm-3 align-items-stretch bottom-absolute-buttons shadow-toast z-1">
                @if($cartItems['countItem'] > 0)
                    <button type="button" class="btn btn-outline-danger btn-block m-0 fs-12-mobile p-2 min-h-40 rounded text-nowrap  pos-clear-cart-btn"   data-toggle="modal"
                            data-target="#clearCartModal">
                        {{ translate('clear_Cart')}}
                    </button>
                    <button type="button" class="btn bg-info text-white btn-block m-0 fs-12-mobile p-2 min-h-40 rounded text-nowrap pos-hold-btn"  data-toggle="modal"
                            data-target="#holdOrderModal">
                        {{ translate('hold')}}
                    </button>
                <div class="place-order-wrapper w-100">
                    <button id="submit_order" type="button" class="btn btn--primary btn-block m-0 fs-12-mobile p-2 min-h-40 action-form-submit" data-message="{{ translate('want_to_place_this_order').'?'}}">
                        <i class="fa fa-shopping-bag"></i>
                        {{ translate('place_Order') }}
                    </button>
                </div>
                @else
                    <div title="{{ $clearCartTooltip }}" data-toggle="tooltip" class="flex-grow-1">
                        <button type="button"
                                class="btn btn-outline-danger btn-block m-0 fs-12-mobile p-2 min-h-40 rounded text-nowrap action-clear-cart pos-clear-cart-btn"
                                disabled>
                            {{ translate('clear_Cart')}}
                        </button>
                    </div>
                    <div title="{{ $holdOrderTooltip }}" data-toggle="tooltip" class="flex-grow-1">
                        <button type="button"
                                class="btn bg-info text-white btn-block m-0 fs-12-mobile p-2 min-h-40 rounded text-nowrap action-hold pos-hold-btn"
                                disabled>
                            {{ translate('hold')}}
                        </button>
                    </div>
                    <div title="{{ $clearCartTooltip }}" data-toggle="tooltip" class="flex-grow-1">
                        <button id="submit_order" type="button"
                                class="btn btn--primary btn-block m-0 fs-12-mobile p-2 min-h-40 action-form-submit disabled opacity-75"
                                disabled>
                            <i class="fa fa-shopping-bag"></i>
                            {{ translate('place_Order') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</form>

@include('vendor-views.pos.partials.modals._clear-cart-modal')
@include('vendor-views.pos.partials.modals._hold-order-modal')

@push('css_or_js')
    <style>
        .pos-mpesa-phone-wrap { width: 100%; }
        .pos-mpesa-phone-group {
            flex-wrap: nowrap;
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
        }
        .pos-mpesa-phone-group .pos-mpesa-dial-code {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #f1f5f9;
            border: 1px solid #dee2e6;
            border-right: 0;
            color: #334155;
            font-size: 0.875rem;
            padding: 0.65rem 0.75rem;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .pos-mpesa-phone-group .pos-mpesa-flag { font-size: 1.1rem; line-height: 1; }
        .pos-mpesa-phone-group .pos-mpesa-phone-input {
            border: 1px solid #dee2e6;
            border-left: 0;
            padding: 0.65rem 0.85rem;
            font-size: 1rem;
            min-width: 0;
            flex: 1 1 auto;
        }
        .pos-mpesa-phone-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .pos-mpesa-phone-group:focus-within .pos-mpesa-dial-code,
        .pos-mpesa-phone-group:focus-within .pos-mpesa-phone-input {
            border-color: #86b7fe;
        }
    </style>
@endpush

@push('script_2')
    <script>
        'use strict';
        $('#type_ext_dis').on('change', function () {
            let type = $('#type_ext_dis').val();
            if (type === 'amount') {
                $('#dis_amount').attr('placeholder', 'Ex: 500');
            } else if (type === 'percent') {
                $('#dis_amount').attr('placeholder', 'Ex: 10%');
            }
        });
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
@endpush
