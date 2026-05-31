@php($kenyaLocations = config('kenya_locations'))
@php($kenyaCounties = $kenyaLocations['counties'] ?? [])

<input type="hidden" id="physical_product" name="physical_product" value="{{ $physical_product_view ? 'yes' : 'no' }}">

<div class="px-3 px-md-0 mb-3">
    <h4 class="pb-2 mt-2 fs-18">{{ translate('delivery_details') }}</h4>
    <p class="text-muted fs-13 mb-0">{{ translate('quick_checkout_for_mpesa_fill_dropdowns_below') }}</p>
</div>

@php($checkoutShippingOptions = $checkoutShippingOptions ?? ['is_inhouse' => false, 'items' => []])
@if(($physical_product_view ?? false) && count($checkoutShippingOptions['items'] ?? []) > 0)
    <div class="px-3 px-md-0 mb-3">
        <h4 class="pb-2 fs-16">{{ translate('shipping_method') }}</h4>
        @foreach($checkoutShippingOptions['items'] as $shippingGroup)
            <div class="mb-3">
                @if(!($checkoutShippingOptions['is_inhouse'] ?? false))
                    <label class="form-label fs-13 text-muted mb-1">{{ $shippingGroup['seller_label'] }}</label>
                @endif
                <select class="form-control kenya-checkout-shipping-method"
                        data-cart-group="{{ $shippingGroup['cart_group_id'] }}">
                    @foreach($shippingGroup['methods'] as $method)
                        <option value="{{ $method['id'] }}"
                            {{ (int) $shippingGroup['selected_id'] === (int) $method['id'] ? 'selected' : '' }}>
                            {{ $method['title'] }} ({{ $method['duration'] }}) — {{ webCurrencyConverter(amount: $method['cost']) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
@endif

<form method="post" class="card __card" id="address-form">
    <input type="hidden" name="shipping_method_id" id="shipping_method_id" value="0">
    <input type="hidden" name="country" id="country" value="{{ $kenyaLocations['default_country'] }}">
    <input type="hidden" name="city" id="city" value="">
    <input type="hidden" name="zip" id="zip" value="{{ $kenyaLocations['default_zip'] }}">
    <input type="hidden" name="address" id="address" value="">
    <input type="hidden" name="address_type" value="home">
    <input type="hidden" name="latitude" value="-1.286389">
    <input type="hidden" name="longitude" value="36.817223">

    <div class="card-body p-20">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ translate('full_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="contact_person_name" id="name" required
                       value="{{ trim((auth('customer')->user()->f_name ?? '') . ' ' . (auth('customer')->user()->l_name ?? '')) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">{{ translate('mpesa_phone_number') }} <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="phone" name="phone" required
                       placeholder="07XXXXXXXX">
                <small class="text-muted">{{ translate('you_will_receive_mpesa_prompt_on_this_number') }}</small>
            </div>

            @if(!auth('customer')->check())
                <div class="col-12">
                    <label class="form-label">{{ translate('email') }} <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
            @endif

            <div class="col-md-4">
                <label class="form-label">{{ translate('county') }} <span class="text-danger">*</span></label>
                <select class="form-control" id="kenya_county" required>
                    <option value="">{{ translate('select_county') }}</option>
                    @foreach(array_keys($kenyaCounties) as $countyName)
                        <option value="{{ $countyName }}" {{ $countyName === 'Nairobi' ? 'selected' : '' }}>{{ $countyName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">{{ translate('town') }} <span class="text-danger">*</span></label>
                <select class="form-control" id="kenya_town" required disabled>
                    <option value="">{{ translate('select_town') }}</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">{{ translate('delivery_area') }} <span class="text-danger">*</span></label>
                <select class="form-control" id="kenya_area" required disabled>
                    <option value="">{{ translate('select_area') }}</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">{{ translate('landmark_optional') }}</label>
                <input type="text" class="form-control" id="kenya_landmark"
                       placeholder="{{ translate('e.g._near_supermarket_or_estate_gate') }}">
            </div>

            <div class="col-12">
                <div class="alert alert-info mb-0 py-2 fs-13">
                    <i class="tio-info"></i>
                    {{ translate('country') }}: <strong>{{ $kenyaLocations['default_country'] }}</strong>
                    &mdash; {{ translate('pay_with_mpesa_on_next_step') }}
                </div>
            </div>
        </div>
    </div>
</form>

<input type="checkbox" id="same_as_shipping_address" name="same_as_shipping_address" class="d-none" checked>
<div id="billing-address-form" class="d-none"></div>

<span id="kenya-locations-data" data-json="{{ json_encode($kenyaCounties) }}"></span>
