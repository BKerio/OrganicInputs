<div class="modal fade" id="posMpesaPendingModal" tabindex="-1" role="dialog" aria-hidden="true"
     data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-4 text-center" id="pos-mpesa-status-card">
                <div id="pos-mpesa-pending-view">
                    <h5 class="mb-3" id="pos-mpesa-pending-title">{{ translate('check_your_phone') }}</h5>
                    <p class="text-muted mb-4" id="pos-mpesa-status-message">
                        {{ translate('an_mpesa_prompt_has_been_sent_enter_your_pin_to_complete_payment') }}
                    </p>
                    <div class="spinner-border text-primary mb-3" id="pos-mpesa-spinner" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="small text-muted mb-0" id="pos-mpesa-pending-hint">
                        {{ translate('do_not_close_this_page_until_payment_is_confirmed') }}
                    </p>
                </div>
                <div id="pos-mpesa-result-view" style="display: none;">
                    <div id="pos-mpesa-result-icon" class="mb-3" style="font-size: 3rem; line-height: 1;"></div>
                    <h5 class="mb-3" id="pos-mpesa-result-title"></h5>
                    <p class="text-muted mb-4" id="pos-mpesa-result-message"></p>
                    <button type="button" class="btn btn--primary px-4" id="pos-mpesa-retry-btn" style="display: none;" data-dismiss="modal">
                        {{ translate('try_again') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
