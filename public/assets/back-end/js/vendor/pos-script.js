"use strict";

let elementViewAllHoldOrdersSearch = $(".view_all_hold_orders_search");
let getYesWord = $("#message-yes-word").data("text");
let getNoWord = $("#message-no-word").data("text");
let messageAreYouSure = $("#message-are-you-sure").data("text");

document.addEventListener("keydown", function (event) {
    if (event.altKey && event.code === "KeyO") {
        $("#submit_order").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyZ") {
        $("#payment_close").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyS") {
        $("#order_complete").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyC") {
        emptyCart();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyA") {
        $("#add_new_customer").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyN") {
        $("#submit_new_customer").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyK") {
        $("#short-cut").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyP") {
        $("#print_invoice").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyQ") {
        $("#search").focus();
        $("#-pos-search-box").css("display", "none");
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyE") {
        $("#pos-search-box").css("display", "none");
        $("#extra_discount").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyD") {
        $("#pos-search-box").css("display", "none");
        $("#coupon_discount").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyB") {
        $("#invoice_close").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyX") {
        $(".action-clear-cart").click();
        event.preventDefault();
    }
    if (event.altKey && event.code === "KeyR") {
        $(".action-new-order").click();
        event.preventDefault();
    }
});

$(".search-bar-input").on("keyup", function () {
    $(".pos-search-card").removeClass("d-none").show();
    let name = $(".search-bar-input").val();
    let elementSearchResultBox = $(".search-result-box");
    if (name.length > 0) {
        $("#pos-search-box").removeClass("d-none").show();
        $.get({
            url: $("#route-vendor-products-search-product").data("url"),
            dataType: "json",
            data: {
                name: name,
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                elementSearchResultBox.empty().html(data.result);
                renderSelectProduct();
                renderQuickViewSearchFunctionality();
            },
            complete: function () {
                $("#loading").fadeOut();
            },
        });
    } else {
        elementSearchResultBox.empty().hide();
    }
});

$(".action-category-filter").on("change", (event) => {
    let getUrl = new URL(window.location.href);
    getUrl.searchParams.set("category_id", $(event.target).val());
    window.location.href = getUrl.toString();
});

function getPosMpesaNationalDigits() {
    let digits = ($("#pos_mpesa_phone").val() || "").replace(/\D/g, "");
    if (digits.startsWith("254")) digits = digits.slice(3);
    if (digits.startsWith("0")) digits = digits.slice(1);
    return digits;
}

function getPosMpesaPhoneForSubmit() {
    const national = getPosMpesaNationalDigits();
    if (national.length >= 9) return "254" + national.slice(-9);
    return national;
}

function validatePosMpesaPhone() {
    const national = getPosMpesaNationalDigits();
    if (national.length < 9 || !/^7\d{8}$/.test(national.slice(0, 9))) {
        toastMagic.error(
            $("#message-enter-valid-amount").data("text") ||
                "Please enter a valid M-Pesa phone number (e.g. 712345678)"
        );
        return false;
    }
    return true;
}

function renderCustomerAmountForPay() {
    const national = getPosMpesaNationalDigits();
    const button = $(".action-form-submit");
    button.prop("disabled", national.length < 9);
    const wrapper = $(".place-order-wrapper");
    if (national.length < 9) {
        wrapper.attr("title", "Enter M-Pesa phone number").attr("data-toggle", "tooltip");
        if (!wrapper.data("bs.tooltip")) wrapper.tooltip();
    } else {
        if (wrapper.data("bs.tooltip")) wrapper.tooltip("dispose");
        wrapper.removeAttr("title").removeAttr("data-toggle");
    }
}

function disableOrderPlaceButton() {
    renderCustomerAmountForPay();
}

let posMpesaPollTimer = null;
let posMpesaHasShownResult = false;

const POS_MPESA_STATUS_UI = {
    success: { icon: "✓", color: "#198754", titleKey: "success", showRetry: false },
    cancelled: { icon: "✕", color: "#dc3545", titleKey: "cancelled", showRetry: true },
    wrong_pin: { icon: "✕", color: "#dc3545", titleKey: "wrongPin", showRetry: true },
    insufficient_funds: { icon: "✕", color: "#dc3545", titleKey: "insufficientFunds", showRetry: true },
    timeout: { icon: "✕", color: "#dc3545", titleKey: "timeout", showRetry: true },
    expired: { icon: "✕", color: "#dc3545", titleKey: "expired", showRetry: true },
    system_error: { icon: "✕", color: "#dc3545", titleKey: "systemError", showRetry: true },
    failure: { icon: "✕", color: "#dc3545", titleKey: "failure", showRetry: true },
};

function getPosMpesaLabels() {
    const el = document.getElementById("pos-mpesa-labels");
    if (!el) return {};
    return {
        checkPhone: el.dataset.checkPhone || "Check your phone",
        pending: el.dataset.pending || "M-Pesa prompt sent",
        doNotClose: el.dataset.doNotClose || "Do not close this page",
        success: el.dataset.success || "Payment successful",
        completingOrder: el.dataset.completingOrder || "Completing order",
        cancelled: el.dataset.cancelled || "Payment cancelled",
        wrongPin: el.dataset.wrongPin || "Wrong PIN",
        insufficientFunds: el.dataset.insufficientFunds || "Insufficient funds",
        timeout: el.dataset.timeout || "Payment timed out",
        expired: el.dataset.expired || "Payment expired",
        systemError: el.dataset.systemError || "System error",
        failure: el.dataset.failure || "Payment failed",
        paymentFailed: el.dataset.paymentFailed || "Payment failed",
    };
}

function isPosMpesaFinal(data) {
    return data.is_final === true || data.is_final === 1 || data.is_final === "true";
}

function isPosMpesaSuccess(data) {
    return data.is_success === true || data.is_success === 1 || data.status === "success";
}

function resetPosMpesaModal() {
    posMpesaHasShownResult = false;
    const labels = getPosMpesaLabels();
    $("#pos-mpesa-pending-view").show();
    $("#pos-mpesa-result-view").hide();
    $("#pos-mpesa-spinner").show();
    $("#pos-mpesa-retry-btn").hide();
    $("#pos-mpesa-pending-title").text(labels.checkPhone);
    $("#pos-mpesa-status-message").text(
        $("#route-vendor-pos-mpesa-stk-push").data("pending-text") || labels.pending
    );
    $("#pos-mpesa-pending-hint").text(labels.doNotClose);
    $("#pos-mpesa-result-icon").text("").css("color", "");
    $("#pos-mpesa-result-title").text("");
    $("#pos-mpesa-result-message").text("");
}

function showPosMpesaPendingModal() {
    resetPosMpesaModal();
    $("#posMpesaPendingModal").modal("show");
}

function showPosMpesaFinalResult(data) {
    if (posMpesaHasShownResult) return;
    posMpesaHasShownResult = true;
    stopPosMpesaPolling();
    const labels = getPosMpesaLabels();
    const status = data.status || (isPosMpesaSuccess(data) ? "success" : "failure");
    const ui = POS_MPESA_STATUS_UI[status] || POS_MPESA_STATUS_UI.failure;
    $("#pos-mpesa-pending-view").hide();
    $("#pos-mpesa-spinner").hide();
    $("#pos-mpesa-result-view").show();
    $("#pos-mpesa-result-icon").text(ui.icon).css("color", ui.color);
    $("#pos-mpesa-result-title").text(labels[ui.titleKey] || labels.failure);
    $("#pos-mpesa-result-message").text(data.message || labels[ui.titleKey] || "");
    if (ui.showRetry) $("#pos-mpesa-retry-btn").show();
}

function stopPosMpesaPolling() {
    if (posMpesaPollTimer) {
        clearInterval(posMpesaPollTimer);
        posMpesaPollTimer = null;
    }
}

function completePosOrder(checkoutRequestId) {
    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
    });
    $.post({
        url: $("#route-vendor-pos-mpesa-complete-order").data("url"),
        data: { checkout_request_id: checkoutRequestId },
        beforeSend: function () { $("#loading").fadeIn(); },
        success: function (response) {
            if (Boolean(response.checkProductTypeForWalkingCustomer) === true) {
                $("#posMpesaPendingModal").modal("hide");
                $(".alert--message-for-pos").addClass("show").addClass("active");
                $(".alert--message-for-pos .warning-message").empty().html(response.message);
                $(".offcanvasAddNewCustomer").addClass("active");
            } else if (response.status == 1) {
                location.reload();
            } else {
                showPosMpesaFinalResult({
                    status: "failure",
                    is_final: true,
                    is_success: false,
                    message: response.message || getPosMpesaLabels().failure,
                });
            }
        },
        complete: function () { $("#loading").fadeOut(); },
    });
}

function pollPosMpesaStatusOnce(checkoutRequestId, startedAt) {
    const statusUrl = $("#route-vendor-pos-mpesa-status").data("url");
    const labels = getPosMpesaLabels();
    const clientTimeoutMs = 180000;
    if (posMpesaHasShownResult) { stopPosMpesaPolling(); return; }
    if (Date.now() - startedAt > clientTimeoutMs) {
        showPosMpesaFinalResult({ status: "timeout", is_final: true, is_success: false, message: labels.timeout });
        return;
    }
    $.ajax({
        url: statusUrl,
        method: "GET",
        data: { checkout_request_id: checkoutRequestId },
        dataType: "json",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        success: function (data) {
            if (posMpesaHasShownResult) return;
            if (isPosMpesaSuccess(data)) {
                showPosMpesaFinalResult(data);
                $("#pos-mpesa-result-message").text(
                    (data.message || labels.success) + " — " + labels.completingOrder
                );
                completePosOrder(checkoutRequestId);
                return;
            }
            if (isPosMpesaFinal(data) && !isPosMpesaSuccess(data)) {
                showPosMpesaFinalResult(data);
                return;
            }
            if (data.message) $("#pos-mpesa-status-message").text(data.message);
        },
    });
}

function pollPosMpesaStatus(checkoutRequestId) {
    const startedAt = Date.now();
    stopPosMpesaPolling();
    pollPosMpesaStatusOnce(checkoutRequestId, startedAt);
    posMpesaPollTimer = setInterval(function () {
        pollPosMpesaStatusOnce(checkoutRequestId, startedAt);
    }, 2000);
}

function startPosMpesaPayment() {
    const formData = new FormData(document.getElementById("order-place"));
    formData.set("mpesa_phone", getPosMpesaPhoneForSubmit());
    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
    });
    $.post({
        url: $("#route-vendor-pos-mpesa-stk-push").data("url"),
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () { $("#loading").fadeIn(); },
        success: function (response) {
            if (response.status == 1 && response.checkout_request_id) {
                showPosMpesaPendingModal();
                pollPosMpesaStatus(response.checkout_request_id);
            } else if (Boolean(response.checkProductTypeForWalkingCustomer) === true) {
                $(".alert--message-for-pos").addClass("show").addClass("active");
                $(".alert--message-for-pos .warning-message").empty().html(response.message);
                $(".offcanvasAddNewCustomer").addClass("active");
            } else {
                toastMagic.error(response.message || "M-Pesa STK push failed");
            }
        },
        complete: function () { $("#loading").fadeOut(); },
    });
}
$(".action-customer-change").on("click", function () {
    $.post({
        url: $("#route-vendor-pos-change-customer").data("url"),
        data: {
            _token: $('meta[name="_token"]').attr("content"),
            user_id: $(this).data('id'),
        },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $(".vendor-pos-customer-info").empty().html(data.view);
            $("#cart-summary").empty().html(data?.cart_view);
            viewAllHoldOrders("keyup");
            posUpdateQuantityFunctionality();
            basicFunctionalityForCartSummary();
            renderCustomerAmountForPay()
            removeFromCart();
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
});

$(".action-view-all-hold-orders").on("click", () => viewAllHoldOrders());
elementViewAllHoldOrdersSearch.on("input", () => viewAllHoldOrders("keyup"));

function viewAllHoldOrders(action = null) {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
        },
    });
    $.post({
        url: $("#route-vendor-pos-view-hold-orders").data("url"),
        data: {
            customer: elementViewAllHoldOrdersSearch.val(),
        },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $("#hold-orders-modal-content").empty().html(data.view);
            if (action !== "keyup") {
                $("#hold-orders-modal-btn").click();
            }
            $(".total_hold_orders").text(data.totalHoldOrders);
            renderViewHoldOrdersFunctionality();
            basicFunctionalityForCartSummary();
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
}

function renderSelectProduct() {
    $(".action-get-variant-for-already-in-cart").on("click", function () {
        getVariantForAlreadyInCart($(this).data("action"));
    });

    $(".action-add-to-cart").on("click", function () {
        addToCart();
    });

    $(".action-color-change").on("click", function () {
        let val = $(this).val();
        $(".color-border").removeClass("border-add");
        $("#label-" + val.id).addClass("border-add");
    });

    cartQuantityInitialize();
    getVariantPrice();
    $(".variant-change input").on("change", function () {
        $("#add-to-cart-form input[name=quantity]").val(1);
        $(".cart-qty-field").val(1);
        getVariantPrice();
    });
    $(".variant-change input , .cart-qty-field").on("change", function () {
        getVariantPrice();
    });
    $("#add-to-cart-form .in-cart-quantity-field").on("change", function () {
        getVariantPrice("already_in_cart");
    });
}

renderSelectProduct();
renderQuickViewFunctionality();

function renderQuickViewFunctionality() {
    $(".action-select-product").on("click", function () {
        quickView($(this).data("id"));
    });
}

function renderQuickViewSearchFunctionality() {
    $(".action-select-search-product").on("click", function () {
        quickView($(this).data("id"));
    });
}

function basicFunctionalityForCartSummary() {
    $(".action-empty-alert-show").on("click", () => {
        toastMagic.warning($("#message-cart-is-empty").data("text"));
    });

    $(".action-clear-cart").on("click", () => {
        document.location.href = $("#route-vendor-pos-clear-cart-ids").data(
            "url"
        );
    });

    $(".action-new-order").on("click", () => {
        $(".action-new-order").on("click", () => {
            Swal.fire({
                title: messageAreYouSure,
                text: $("#message-you-want-to-create-new-order").data("text"),
                icon: "warning",
                showCancelButton: true,
                cancelButtonColor: "#dd3333",
                confirmButtonColor: "#161853",
                cancelButtonText: getNoWord,
                confirmButtonText: getYesWord,
                reverseButtons: true,
            }).then((result) => {
                if (result.value) {
                    document.location.href = $("#route-vendor-pos-new-cart-id").data("url");
                }
            });
        });
    });

    $(".action-cart-change").on("click", function () {
        let value = $(this).data("cart");
        let dynamicUrl = $("#route-vendor-pos-change-cart-editable").data(
            "url"
        );
        dynamicUrl = dynamicUrl.replace(":value", `${value}`);
        window.location.href = dynamicUrl;
    });

    $(".action-empty-cart").on("click", function () {
        Swal.fire({
            title: messageAreYouSure,
            text: $("#message-you-want-to-remove-all-items-from-cart").data(
                "text"
            ),
            icon: "warning",
            showCancelButton: true,
            cancelButtonColor: "#dd3333",
            confirmButtonColor: "#161853",
            cancelButtonText: getNoWord,
            confirmButtonText: getYesWord,
            reverseButtons: true,
        }).then((result) => {
            if (result.value) {
                $.post(
                    $("#route-vendor-pos-empty-cart").data("url"),
                    {
                        _token: $('meta[name="_token"]').attr("content"),
                    },
                    function (data) {
                        $("#cart-summary").empty().html(data.view);
                        toastMagic.info($("#message-item-has-been-removed-from-cart").data("text"));
                    }
                );
            }
        });
    });

    $(document).on("input", "#pos_mpesa_phone", function () {
        this.value = (this.value || "").replace(/\D/g, "").slice(0, 10);
        renderCustomerAmountForPay();
    });

    $(".action-form-submit").on("click", function () {
        if (!validatePosMpesaPhone()) return;
        Swal.fire({
            title: messageAreYouSure,
            icon: "warning",
            text: $(this).data("message"),
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: getNoWord,
            confirmButtonText: getYesWord,
            reverseButtons: true,
        }).then(function (result) {
            if (result.value) startPosMpesaPayment();
        });
    });

    function checkedPaidAmount() {
        return validatePosMpesaPhone();
    }

    $('.option-buttons input').on('change', function () {
        renderCustomerAmountForPay();
        let type = $(this).val();
        if ($(this).is(':checked')) {
            $('.cash-change-section').hide();
            if (type === 'cash') {
                $('.cash-change-amount').show();
            } else if (type === 'card') {
                $('.cash-change-card').removeClass('d-none').show();
            } else if (type === 'wallet') {
                let insufficientBalanceMessage = $('#message-insufficient-balance');
                let cashChangeWallet = $('.cash-change-wallet');
                if (parseFloat($('.customer-wallet-balance').val()) < parseFloat($('.total-amount').val())) {
                    insufficientBalanceMessage.text(insufficientBalanceMessage.data('text'));
                }
                cashChangeWallet.show();
                cashChangeWallet.removeClass('d-none').show();
            }
        }
    });

    renderCustomerAmountForPay();

    $('.pos-paid-amount-element').on("keypress", function (event) {
        let charCode = event.which || event.keyCode;
        let inputValue = $(this).val();

        if ((charCode < 48 || charCode > 57) && charCode !== 46) {
            event.preventDefault();
        }

        if (charCode === 46 && inputValue.includes('.')) {
            event.preventDefault();
        }
    }).on("input", function () {
        let minimumAmount = parseFloat($(this).attr('min')) || 0;
        let GivenAmount = parseFloat($(this).val()) || 0;
        let currencyPosition = $(this).data('currency-position');
        let currencySymbol = $(this).data('currency-symbol');
        let decimalPoint = $('#get-decimal-point').data('decimal-point') ?? 2;
        if (GivenAmount < minimumAmount) {
            $('#submit_order').attr('disabled', true);
        } else {
            $('#submit_order').attr('disabled', false);
        }
        let amount = Number(GivenAmount - minimumAmount).toFixed(decimalPoint);
        let result = '';

        if (currencyPosition?.toString() === 'left') {
            result = currencySymbol + amount;
        } else {
            result = amount + currencySymbol;
        }
        $('.pos-change-amount-element').text(result);
    });
}

$(document).on("click", "#pos-extra-discount-remove-vendor", function (event) {
    event.preventDefault();
    let discount = parseFloat($("#dis_amount").val()) || 0;
    let type = $("#type_ext_dis").val();
    if (discount > 0) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.post({
            url: $("#route-vendor-pos-update-discount").data("url"),
            data: {
                discount: 0,
                type: type,
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                $("#add-discount").modal("hide");
                $(".modal-backdrop").addClass("d-none");
                $("#cart-summary").empty().html(data.view);
                if (data.extraDiscountAmount && parseFloat(data.extraDiscountAmount) > 0) {
                    $("#pos-extra-discount-remove-vendor").removeClass("d-none");
                } else {
                    $("#dis_amount").val(0);
                    $("#type_ext_dis").prop("selectedIndex", 0);
                    $("#pos-extra-discount-remove-vendor").addClass("d-none");
                }
                basicFunctionalityForCartSummary();
                posUpdateQuantityFunctionality();
                viewAllHoldOrders("keyup");
                removeFromCart();
            },
            complete: function (){
                $("#loading").fadeOut();
                $(".modal-backdrop").addClass("d-none");
                $(".footer-offset").removeClass("modal-open");
            }
        });
    }else {
        $("#dis_amount").val(0);
    }
});


$(".action-extra-discount").on("click", function (event) {
    let discount = $("#dis_amount").val();
    let type = $("#type_ext_dis").val();
    if (discount.length === 0) {
        toastMagic.error($(this).data('error-message'));
        event.preventDefault();
    } else if (discount > 0) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.post({
            url: $("#route-vendor-pos-update-discount").data("url"),
            data: {
                discount: discount,
                type: type,
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                if (data.extraDiscount === "success") {
                    toastMagic.success($("#message-extra-discount-added-successfully").data("text"));
                } else if (data.extraDiscount === "empty") {
                    toastMagic.warning($("#message-cart-is-empty").data("text"));
                } else {
                    toastMagic.warning($("#message-this-discount-is-not-applied-for-this-amount").data("text"));
                }
                $('#add-discount').modal('hide');
                $(".modal-backdrop").addClass("d-none");
                $("#cart-summary").empty().html(data.view);
                if (data.extraDiscountAmount && parseFloat(data.extraDiscountAmount) > 0) {
                    $("#pos-extra-discount-remove-vendor").removeClass("d-none");
                } else {
                    $("#pos-extra-discount-remove-vendor").addClass("d-none");
                }
                basicFunctionalityForCartSummary();
                posUpdateQuantityFunctionality();
                viewAllHoldOrders("keyup");
                removeFromCart();
                $("#search").focus();
            },
            complete: function () {
                $(".modal-backdrop").addClass("d-none");
                $(".footer-offset").removeClass("modal-open");
                $("#loading").fadeOut();
            },
        });
    } else {
        toastMagic.warning($("#message-amount-can-not-be-negative-or-zero").data("text"));
    }
});

$(".action-coupon-discount").on("click", function (event) {
    let couponCode = $("#coupon_code").val();
    if (couponCode.length === 0) {
        toastMagic.error($(this).data('error-message'));
        event.preventDefault();
    } else {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.post({
            url: $("#route-vendor-pos-coupon-discount").data("url"),
            data: {
                coupon_code: couponCode,
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                if (data.coupon === "success") {
                    toastMagic.success($("#message-coupon-added-successfully").data("text"));
                } else if (data.coupon === "amount_low") {
                    toastMagic.warning($("#message-this-discount-is-not-applied-for-this-amount").data("text"));
                } else if (data.coupon === "cart_empty") {
                    toastMagic.warning($("#message-cart-is-empty").data("text"));
                } else {
                    toastMagic.warning($("#message-coupon-is-invalid").data("text"));
                }
                $('#add-coupon-discount').modal('hide');
                $("#cart-summary").empty().html(data.view);
                $("#search").focus();
                basicFunctionalityForCartSummary();
                posUpdateQuantityFunctionality();
                viewAllHoldOrders("keyup");
                removeFromCart();
            },
            complete: function () {
                $(".modal-backdrop").addClass("d-none");
                $(".footer-offset").removeClass("modal-open");
                $("#loading").fadeOut();
            },
        });
    }
});

basicFunctionalityForCartSummary();
posUpdateQuantityFunctionality();

function posUpdateQuantityFunctionality() {
    $(".action-pos-update-quantity").on("change", function (event) {
        if (!event.originalEvent) return;
         sanitizeAndValidateQuantityInput(this);
        let getKey = $(this).data("product-key");
        let quantity = $(this).val();
        let variant = $(this).data("product-variant");
        getPOSUpdateQuantity(getKey, quantity, event, variant);
    });
    $(document).off("click", ".qty-count").on("click", ".qty-count", function () {
        let $btn = $(this);
        let $input = $btn.closest(".qty-input-group").find(".product-qty");
        let oldValue = parseInt($input.val()) || 1;
        let action = $btn.data("action");
        let min = parseInt($input.attr("min")) || 1;
        let max = parseInt($input.attr("max")) || 1000;
        let newValue = oldValue;

        if (action === "minus") {
            if (oldValue <= min) {
                toastMagic.warning(($('#message-product-quantity-cannot-be-less-then-one').data('text')))
                return;
            } else {
                newValue = oldValue - 1;
            }
        } else if (action === "plus" && oldValue < max) {
            newValue = oldValue + 1;
        }
        $input.val(newValue).trigger({type: "change", originalEvent: true});
    });
}

document.addEventListener('input', function(event) {
    if (event.target.classList.contains('action-pos-update-quantity')) {
        sanitizeAndValidateQuantityInput(event.target);
    }
});


function sanitizeAndValidateQuantityInput(inputElement) {
    inputElement.value = inputElement.value.replace(/[^0-9]/g, '').replace(/^0+/, '');
    const minAttr = inputElement.getAttribute("min");
    const maxAttr = inputElement.getAttribute("max");
    const min = minAttr ? parseInt(minAttr) : 1;
    const max = maxAttr ? parseInt(maxAttr) : Infinity;
    const val = parseInt(inputElement.value);
    if (inputElement.value !== '' && (val < min || val > max)) {
        inputElement.value = val < min ? min : max;
    }
}


function getPOSUpdateQuantity(key, qty, e, variant = null) {
    if (qty !== "") {
        $.post(
            $("#route-vendor-pos-update-quantity").data("url"),
            {
                _token: $('meta[name="_token"]').attr("content"),
                key: key,
                quantity: qty,
                variant: variant,
            },
            function (data) {
                updateQuantityResponseProcess(data);
            }
        );
    } else {
        let element = $(e.target);
        let minValue = parseInt(element.attr("min"));
        $.post(
            $("#route-vendor-pos-update-quantity").data("url"),
            {
                _token: $('meta[name="_token"]').attr("content"),
                key: key,
                quantity: minValue,
                variant: variant,
            },
            function (data) {
                updateQuantityResponseProcess(data);
            }
        );
    }

    if (e.type == "keydown") {
        if (
            $.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
            (e.keyCode == 65 && e.ctrlKey === true) ||
            (e.keyCode >= 35 && e.keyCode <= 39)
        ) {
            return;
        }
        if (
            (e.shiftKey || e.keyCode < 48 || e.keyCode > 57) &&
            (e.keyCode < 96 || e.keyCode > 105)
        ) {
            e.preventDefault();
        }
    }
}

function updateQuantityResponseProcess(data) {
    if (data.productType === "physical" && data.qty < 0) {
        toastMagic.warning($("#message-product-quantity-is-not-enough").data("text"));
    }
    if (data.upQty === "zeroNegative") {
        toastMagic.warning($("#message-product-quantity-cannot-be-zero-in-cart").data("text"));
    }
    if (data.quantityUpdate == 1) {
        toastMagic.success($("#message-product-quantity-updated").data("text"));
    }
    $("#cart-summary").empty().html(data.view);
    updateProductCounts(data);
    basicFunctionalityForCartSummary();
    posUpdateQuantityFunctionality();
    viewAllHoldOrders("keyup");
    removeFromCart();
    removeFromCart();
}

let dropdownSelect = $("#dropdown-order-select");
dropdownSelect.on(
    "click",
    ".dropdown-menu .dropdown-item:not(:last-child)",
    function () {
        let selectedText = $(this).text();
        dropdownSelect.find(".dropdown-toggle").text(selectedText);
    }
);

$("#order-place").submit(function (eventObj) {
    eventObj.preventDefault();
    let customerValue = $("#customer").val();
    if (customerValue) {
        $(this).append(
            '<input type="hidden" name="user_id" value="' +
            customerValue +
            '" /> '
        );
    }
    return true;
});

$(function () {
    $(document).on("click", "input[type=number]", function () {
        this.select();
    });
});

window.addEventListener("click", function (event) {
    let searchResultBoxes =
        document.getElementsByClassName("search-result-box");
    for (let i = 0; i < searchResultBoxes.length; i++) {
        let searchResultBox = searchResultBoxes[i];
        if (
            event.target !== searchResultBox &&
            !searchResultBox.contains(event.target)
        ) {
            searchResultBox.style.display = "none";
        }
    }
});

function renderViewHoldOrdersFunctionality() {
    $(".action-cancel-customer-order").on("click", function () {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.post({
            url: $("#route-vendor-pos-cancel-order").data("url"),
            data: {
                cart_id: $(this).data("cart-id"),
            },
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                $("#hold-orders-modal-content").empty().html(data.view);
                toastMagic.info(data.message);
                location.reload();
            },
            complete: function () {
                $("#loading").fadeOut();
            },
        });
    });
}

$(".action-print-invoice").on("click", function () {
    printDiv($(this).data("value"));
});

function printDiv(divName) {
    let printContents = document.getElementById(divName).innerHTML;
    let originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}

$(".action-print-pos-invoice").on("click", function () {
    const divName = $(this).data("print");
    printSpecificSectionWithPrintArea(divName)
});

function printSpecificSectionWithPrintArea(selector) {
    try {
        $(selector).printThis();
    } catch (e) {
        console.error("Printing failed:", e);
    }
}

const renderRippleEffect = () => {
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement("span");
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.classList.add("ripple");
        const ripple = button.getElementsByClassName("ripple")[0];
        if (ripple) {
            ripple.remove();
        }
        button.appendChild(circle);
    }
    const buttons = document.getElementsByClassName("btn-number");
    for (const button of buttons) {
        button.addEventListener("click", createRipple);
    }
};

function quickView(product_id) {
    $.ajax({
        url: $("#route-vendor-pos-quick-view").data("url"),
        type: "GET",
        data: {
            product_id: product_id,
        },
        dataType: "json",
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (data) {
            $("#quick-view-modal").empty().html(data.view);
            $('#quick-view-modal [data-toggle="tooltip"]').tooltip({
                container: 'body'
            });
            initSliderWithZoom();
            renderSelectProduct();
            renderRippleEffect();
            closeAlertMessage();
            $("#quick-view").modal("show");
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
}

function getVariantForAlreadyInCart(event = null) {
    let current_val = parseFloat($(".in-cart-quantity-field").val());

    if (current_val > 0) {
        $(".in-cart-quantity-minus").removeAttr("disabled");
        if (event == "plus") {
            $(".in-cart-quantity-field").val(current_val + 1);
        } else {
            $(".in-cart-quantity-field").val(current_val - 1);
            if (current_val <= 2) {
                $(".in-cart-quantity-minus").attr("disabled", true);
            }
        }
    } else {
        $(".in-cart-quantity-minus").attr("disabled", true);
    }
    getVariantPrice("already_in_cart");
}

function checkAddToCartValidity() {
    var names = {};
    $("#add-to-cart-form input:radio").each(function () {
        names[$(this).attr("name")] = true;
    });
    var count = Object.keys(names).length;
    if (count === 0) {
        return true;
    }
    var checkedCount = 0;
    $.each(names, function (name) {
        if ($("input:radio[name='" + name + "']:checked").length > 0) {
            checkedCount++;
        }
    });
    return checkedCount === count;
}

function cartQuantityInitialize() {
    $(".btn-number").click(function (e) {
        e.preventDefault();

        let fieldName = $(this).attr("data-field");
        let type = $(this).attr("data-type");
        let input = $("input[name='" + fieldName + "']");
        let currentVal = parseInt(input.val());

        if (!isNaN(currentVal)) {
            if (type == "minus") {
                if (currentVal > input.attr("min")) {
                    input.val(currentVal - 1).change();
                }
                if (parseInt(input.val()) == input.attr("min")) {
                    $(this).attr("disabled", true);
                }
            } else if (type == "plus") {
                if (currentVal < input.attr("max")) {
                    input.val(currentVal + 1).change();
                }
                if (parseInt(input.val()) == input.attr("max")) {
                    $(this).attr("disabled", true);
                }
            }
        } else {
            input.val(0);
        }
    });

    $(".input-number").focusin(function () {
        $(this).data("oldValue", $(this).val());
    });

    $(".input-number").change(function () {
        sanitizeAndValidateQuantityInput(this);
        let minValue = parseInt($(this).attr("min"));
        let maxValue = parseInt($(this).attr("max"));
        let valueCurrent = parseInt($(this).val());
        let name = $(this).attr("name");
        if (valueCurrent >= minValue) {
            $(".btn-number[data-type='minus'][data-field='" + name + "']").removeAttr("disabled");
        } else {
            sanitizeAndValidateQuantityInput(this);
            $(this).val($(this).data("oldValue"));
        }
        if (valueCurrent <= maxValue) {
            $(".btn-number[data-type='plus'][data-field='" + name + "']").removeAttr("disabled");
        }
    });
    $(".in-cart-quantity-field").keydown(function(){
        sanitizeAndValidateQuantityInput(this);
    });
    $(".in-cart-quantity-field").on('change',function(){
        sanitizeAndValidateQuantityInput(this);
    });
    $(".input-number").keydown(function (e) {
        if (
            $.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
            (e.keyCode == 65 && e.ctrlKey === true) ||
            (e.keyCode >= 35 && e.keyCode <= 39)
        ) {
            return;
        }
        if (
            (e.shiftKey || e.keyCode < 48 || e.keyCode > 57) &&
            (e.keyCode < 96 || e.keyCode > 105)
        ) {
            e.preventDefault();
        }
       sanitizeAndValidateQuantityInput(this);
    });
}

function updateProductDetailsTopSection(response) {
    let formSelector = ".add-to-cart-details-form";
    $(formSelector).find(".discounted-unit-price").html(response?.discounted_unit_price);
    $(formSelector).find(".product-details-chosen-price-amount").html(response?.price);
    $(formSelector).find(".product-total-unit-price").html(response?.discount_amount > 0 ? response?.total_unit_price : "");

    if (response?.discount_amount > 0) {
        if (response?.discount_type === 'flat') {
            $(formSelector).find(".discounted_badge").html(`${response?.discount}`);
        } else {
            $(formSelector).find(".discounted_badge").html(`- ${response?.discount}`);
        }
        $(formSelector).find(".discounted-badge-element").removeClass('d-none');
    } else {
        $(formSelector).find(".discounted-badge-element").addClass('d-none');
    }
}

function getVariantPrice(type = null) {
    if (
        $("#add-to-cart-form input[name=quantity]").val() > 0 &&
        checkAddToCartValidity()
    ) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.ajax({
            type: "POST",
            url:
                $("#route-vendor-pos-get-variant-price").data("url") +
                (type ? "?type=" + type : ""),
            data: $("#add-to-cart-form").serializeArray(),
            success: function (response) {
                if (response.quantity !== 0 && response.quantity !== undefined) {
                    $('input[name="quantity"]').attr('max', response.quantity);
                    $(".current-stock-qty").text(response.quantity);
                }
                updateProductDetailsTopSection(response);

                let price;
                let discount;
                if (response.allVariationsOutOfStock) {
                    $(".quick-view-modal-add-cart-button").text($("#message-stock-out").data("text")).prop("disabled", true);
                    $(".default-quantity-system").addClass("d-none");
                    $(".in-cart-quantity-system").addClass("d-none");
                    return;
                } else {
                    $(".quick-view-modal-add-cart-button").prop("disabled", false);
                }
                if (response.inCartStatus == 0) {
                    $(".default-quantity-system").removeClass("d-none");
                    $(".quick-view-modal-add-cart-button").text(
                        $("#message-add-to-cart").data("text")
                    );
                    $(".in-cart-quantity-system")
                        .addClass("d--none");
                    $(".default-quantity-system")
                        .removeClass("d--none");
                    price = response.price;
                    stockStatus(response.quantity, 'cart-qty-field-plus', 'cart-qty-field')
                } else {
                    $(".default-quantity-system")
                        .addClass("d--none");
                    $(".in-cart-quantity-system")
                        .removeClass("d--none");
                    $(".quick-view-modal-add-cart-button").text(
                        $("#message-update-to-cart").data("text")
                    );

                    if (type == null) {
                        $(".in-cart-quantity-field").val(response.inCartData.quantity);
                        response.inCartData.quantity == 1
                            ? buttonDisableOrEnableFunction('in-cart-quantity-minus', true)
                            : "";
                        price = response.inCartData.price;
                    } else {
                        price = response.price;
                    }

                    stockStatus(response.quantity, 'in-cart-quantity-plus', 'in-cart-quantity-field')
                }
                setProductData('add-to-cart-details-form', price, response?.discount_text);
            },
        });
    }
}
function updateProductCounts(data) {
    $(".product-add-count").addClass("d-none").text("");
    if (data.productCounts) {
        $.each(data.productCounts, function(productId, count) {
            const countElement = $("#product-added-to-cart-" + productId);
            if (countElement.length) {
                countElement.text(count).removeClass("d-none");
            }
        });
    }
}
function addToCart(form_id = "add-to-cart-form") {
    if (checkAddToCartValidity()) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
            },
        });
        $.post({
            url: $("#route-vendor-pos-add-to-cart").data("url"),
            data: $("#" + form_id).serializeArray(),
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (data) {
                if (data.data == 1) {
                    $("#cart-summary").empty().html(data.view);
                    toastMagic.success($("#message-cart-updated").data("text"));
                    data.inCartData && data.inCartData == 1
                        ? $(".in-cart-quantity-field").val(data.requestQuantity)
                        : "";
                    updateProductCounts(data);
                    removeFromCart();
                    basicFunctionalityForCartSummary();
                    return false;
                } else if (data.data == 0) {
                    Swal.fire({
                        icon: "warning",
                        title: $("#message-cart-word").data("text"),
                        text: $("#message-sorry-product-is-out-of-stock").data(
                            "text"
                        ),
                    });
                    updateProductCounts(data);
                    return false;
                } else if (data.data == 'custom-error') {
                    Swal.fire({
                        icon: "warning",
                        title: data?.title ?? $("#message-cart-word").data("text"),
                        text: data?.text ?? $("#message-sorry-product-is-out-of-stock").data(
                            "text"
                        ),
                    });
                    updateProductCounts(data);
                    return false;
                } else {
                    $(".in-cart-quantity-field").val(data.quantity);
                    getVariantPrice();
                    setTimeout(function () {
                        $(".cart-qty-field").val(1);
                    }, 500);
                }
                $(".close-quick-view-modal").click();

                toastMagic.success($("#message-item-has-been-added-in-your-cart").data("text"));
                $("#cart-summary").empty().html(data.view);
                updateProductCounts(data);
                viewAllHoldOrders("keyup");
                $(".search-result-box").empty().hide();
                $("#search").val("");
                posUpdateQuantityFunctionality();
                removeFromCart();
            },
            complete: function () {
                $("#loading").fadeOut();
            },
        });
    } else {
        Swal.fire({
            icon: "warning",
            title: $("#message-cart-word").data("text"),
            text: $("#message-please-choose-all-the-options").data("text"),
        });
    }
}
function removeFromCart() {
    $(".remove-from-cart").on("click", function () {
        let id = $(this).data("id");
        let variant = $(this).data("variant");
        $.post(
            $("#route-vendor-pos-remove-cart").data("url"),
            {
                _token: $('meta[name="_token"]').attr("content"),
                id: id,
                variant: variant,
            },
            function (data) {
                $("#cart-summary").empty().html(data.view);
                if (data.errors) {
                    for (let index = 0; index < data.errors.length; index++) {
                        setTimeout(() => {
                            toastMagic.error(data.errors[index].message);
                        }, index * 500);
                    }
                } else {
                    toastMagic.info($("#message-item-has-been-removed-from-cart").data("text"));
                    viewAllHoldOrders("keyup");
                }
                updateProductCounts(data);
                posUpdateQuantityFunctionality();
                posUpdateQuantityFunctionality();
                removeFromCart();
            }
        );
    });
}
removeFromCart();

$(".js-example-matcher").select2({
    matcher: matchCustom,
});

function matchCustom(params, data) {
    if ($.trim(params.term) === "") {
        return data;
    }
    if (typeof data.text === "undefined") {
        return null;
    }

    if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        let modifiedData = $.extend({}, data, true);
        return modifiedData;
    }
    return null;
}

function closeAlertMessage() {
    $('.close-alert-message').on('click', function () {
        $('.pos-alert-message').addClass('d-none');
    })
}

function productStockMessage(type,) {
    $('.product-stock-message').empty().html($('#get-product-stock-message').data(type))
    $('.pos-alert-message').removeClass('d-none');
}
function stockStatus(quantity, buttonDisableOrEnableClassName, inputQuantityClassName) {
    let stockOutMessage = $("#message-stock-out").data("text");
    let stockInMessage = $("#message-stock-id").data("text");
    let elementStockStatusInQuickView = $(".stock-status-in-quick-view");
    let inputQuantity = $('.' + inputQuantityClassName);
    if (quantity <= 0) {
        elementStockStatusInQuickView.removeClass("text-success").addClass("text-danger");
        elementStockStatusInQuickView.html(
            `<i class="tio-checkmark-circle-outlined"></i> ` +
            stockOutMessage
        );
        productStockMessage('out-of-stock')
        buttonDisableOrEnableFunction(buttonDisableOrEnableClassName, true);
        inputQuantity.val(1);
        $(".btn-number[data-type='minus']").attr('disabled', true);
    } else if (inputQuantity.val() >= quantity) {
        productStockMessage('limited-stock');
        buttonDisableOrEnableFunction(buttonDisableOrEnableClassName, true);
        inputQuantity.val(quantity);
    }
    else {
        $('.pos-alert-message').addClass('d-none');
        elementStockStatusInQuickView.removeClass("text-danger").addClass("text-success");
        elementStockStatusInQuickView.html(
            `<i class="tio-checkmark-circle-outlined"></i> ` +
            stockInMessage
        );
        buttonDisableOrEnableFunction(buttonDisableOrEnableClassName, false);
    }
}

function setProductData(parentClass, price, discount) {
    $('.' + parentClass + ' ' + '.set-discount-amount').html(discount);
}
$('.close-alert--message-for-pos').on('click', function () {
    $('.alert--message-for-pos').removeClass('active');
})

// ---- swipper slider and zoom
function initSliderWithZoom() {
    $(".easyzoom").each(function () {
        $(this).easyZoom();
    });

    new Swiper(".quickviewSlider2", {
        slidesPerView: 1,
        spaceBetween: 10,
        loop: false,
        thumbs: {
            swiper: new Swiper(".quickviewSliderThumb2", {
                spaceBetween: 10,
                slidesPerView: 'auto',
                watchSlidesProgress: true,
                navigation: {
                    nextEl: ".swiper-quickview-button-next",
                    prevEl: ".swiper-quickview-button-prev",
                },
            }),
        },
    });
}
