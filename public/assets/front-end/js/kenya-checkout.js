'use strict';

const kenyaLocationsElement = document.getElementById('kenya-locations-data');
if (kenyaLocationsElement) {
    const kenyaLocations = JSON.parse(kenyaLocationsElement.dataset.json || '{}');
    const countySelect = document.getElementById('kenya_county');
    const townSelect = document.getElementById('kenya_town');
    const areaSelect = document.getElementById('kenya_area');
    const landmarkInput = document.getElementById('kenya_landmark');
    const cityInput = document.getElementById('city');
    const addressInput = document.getElementById('address');

    function fillSelect(select, options, placeholder) {
        select.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = placeholder;
        select.appendChild(defaultOption);
        options.forEach((option) => {
            const opt = document.createElement('option');
            opt.value = option;
            opt.textContent = option;
            select.appendChild(opt);
        });
        select.disabled = options.length === 0;
    }

    function updateHiddenAddressFields() {
        const county = countySelect?.value || '';
        const town = townSelect?.value || '';
        const area = areaSelect?.value || '';
        const landmark = landmarkInput?.value?.trim() || '';

        if (cityInput) {
            cityInput.value = town;
        }
        if (addressInput) {
            const parts = [area, landmark, town, county, 'Kenya'].filter(Boolean);
            addressInput.value = parts.join(', ');
        }
    }

    function loadTownsAndAreas(countyName) {
        const countyData = kenyaLocations[countyName];
        if (!countyData) {
            fillSelect(townSelect, [], 'Select town');
            fillSelect(areaSelect, [], 'Select area');
            updateHiddenAddressFields();
            return;
        }

        fillSelect(townSelect, countyData.towns || [], 'Select town');
        fillSelect(areaSelect, countyData.areas || [], 'Select area');
        updateHiddenAddressFields();
    }

    countySelect?.addEventListener('change', function () {
        loadTownsAndAreas(this.value);
    });

    townSelect?.addEventListener('change', updateHiddenAddressFields);
    areaSelect?.addEventListener('change', updateHiddenAddressFields);
    landmarkInput?.addEventListener('input', updateHiddenAddressFields);

    if (countySelect?.value) {
        loadTownsAndAreas(countySelect.value);
        if (countySelect.value === 'Nairobi' && townSelect) {
            townSelect.value = 'Nairobi CBD';
            areaSelect.value = 'CBD';
            updateHiddenAddressFields();
        }
    }

    const originalCheckoutFromShipping = typeof checkoutFromShipping === 'function' ? checkoutFromShipping : null;
    window.checkoutFromShipping = function () {
        updateHiddenAddressFields();

        const addressValue = addressInput?.value?.trim() || '';
        if (!countySelect?.value || !townSelect?.value || !areaSelect?.value || !addressValue) {
            toastr.error('Please select county, town and delivery area', {
                CloseButton: true,
                ProgressBar: true,
            });
            return;
        }

        if (originalCheckoutFromShipping) {
            originalCheckoutFromShipping();
        }
    };

    document.getElementById('address-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        updateHiddenAddressFields();
    });

    document.querySelectorAll('.kenya-checkout-shipping-method').forEach(function (select) {
        select.addEventListener('change', function () {
            const shippingMethodId = this.value;
            const cartGroupId = this.dataset.cartGroup;
            const setShippingUrl = document.getElementById('route-set-shipping-id')?.dataset?.url;

            if (!shippingMethodId || !setShippingUrl) {
                return;
            }

            $.get({
                url: setShippingUrl,
                dataType: 'json',
                data: {
                    id: shippingMethodId,
                    cart_group_id: cartGroupId || 'all_cart_group',
                },
                beforeSend: function () {
                    $('#loading').addClass('d-grid');
                },
                success: function () {
                    location.reload();
                },
                complete: function () {
                    $('#loading').removeClass('d-grid');
                },
            });
        });
    });
}
