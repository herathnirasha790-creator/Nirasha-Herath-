/* ============================================================================
 * Royal Estate — Stripe Payment Integration
 * ----------------------------------------------------------------------------
 * Include this ONE file on any page that has a booking "Confirm Booking" /
 * "Submit Event Request" / "Submit Package Request" form:
 *
 *     <script src="assets/stripe-payment.js"></script>
 *
 * It will:
 *   1. Load Stripe.js automatically (no need to add the CDN tag yourself)
 *   2. Inject a payment popup (re-using the site's own .modal-overlay / .btn-* CSS)
 *   3. Expose window.startBookingPayment(options) — call this instead of
 *      posting straight to *-process.php from your booking form's submit handler.
 * ==========================================================================*/

(function () {
    // pk_ keys are PUBLISHABLE by design — safe to ship in frontend JS.
    var STRIPE_PUBLISHABLE_KEY = 'pk_test_51TvLhiRGiYQbuvnvfx5WtvBDQmI83HAFTyzvg8fDmsTOJTJqlgdl0tpDlJjl4OpIptCXAc7jpVFgGyVdtW0T3MKz00SFk3qExU';

    var stripeInstance = null;
    var elementsGroup = null;
    var paymentElement = null;
    var isProcessing = false;
    var activeOptions = null;
    var activeFormData = null;
    var currentClientSecret = null;

    // ------------------------------------------------------------------
    // Load Stripe.js once, then create the Stripe() instance.
    // ------------------------------------------------------------------
    function ensureStripeJsLoaded(callback) {
        if (window.Stripe) {
            if (!stripeInstance) stripeInstance = window.Stripe(STRIPE_PUBLISHABLE_KEY);
            callback();
            return;
        }
        var existing = document.getElementById('stripeJsSdk');
        if (existing) {
            existing.addEventListener('load', function () {
                stripeInstance = window.Stripe(STRIPE_PUBLISHABLE_KEY);
                callback();
            });
            return;
        }
        var script = document.createElement('script');
        script.id = 'stripeJsSdk';
        script.src = 'https://js.stripe.com/v3/';
        script.onload = function () {
            stripeInstance = window.Stripe(STRIPE_PUBLISHABLE_KEY);
            callback();
        };
        document.head.appendChild(script);
    }

    // ------------------------------------------------------------------
    // Inject the payment modal markup once.
    // ------------------------------------------------------------------
    function ensureModalMarkup() {
        if (document.getElementById('stripePaymentModal')) return;

        var html = ''
            + '<div class="modal-overlay" id="stripePaymentModal">'
            + '  <div class="modal-container" style="max-width:480px;">'
            + '    <button type="button" class="modal-close" id="spCloseBtn"><i class="fas fa-times"></i></button>'
            + '    <div class="modal-body">'
            + '      <div class="modal-content-wrapper" style="width:100%;padding:2rem;">'
            + '        <h2 class="modal-title" style="margin-bottom:0.2rem;"><i class="fas fa-lock" style="color:#c5a263;"></i> Secure Payment</h2>'
            + '        <div id="spItemTitle" style="font-weight:600;color:#2c1810;margin-top:0.5rem;"></div>'
            + '        <div id="spItemSubtitle" style="color:#777;font-size:0.85rem;margin-bottom:0.8rem;"></div>'
            + '        <div class="modal-price" id="spAmount" style="margin-bottom:1.2rem;">$0</div>'

            + '        <div id="spLoadingState" style="text-align:center;padding:2.5rem 0;">'
            + '          <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#c5a263;"></i>'
            + '          <p style="margin-top:0.8rem;color:#666;" id="spLoadingText">Preparing secure payment...</p>'
            + '        </div>'

            + '        <div id="spFormState" style="display:none;">'
            + '          <div id="stripePaymentElement" style="margin-bottom:1rem;"></div>'
            + '          <div id="spCardError" class="alert-error-modal" style="display:none;"></div>'
            + '          <button type="button" id="spPayBtn" class="btn-confirm-booking" style="width:100%;">'
            + '            <i class="fas fa-lock"></i> <span id="spPayBtnLabel">Pay Now</span>'
            + '          </button>'
            + '          <p style="text-align:center;font-size:0.72rem;color:#999;margin-top:0.7rem;">'
            + '            <i class="fas fa-shield-alt"></i> Secured by Stripe &middot; Test mode: card 4242 4242 4242 4242, any future date/CVC'
            + '          </p>'
            + '        </div>'

            + '        <div id="spSuccessState" style="display:none;text-align:center;padding:1.5rem 0;">'
            + '          <i class="fas fa-check-circle" style="font-size:2.6rem;color:#28a745;"></i>'
            + '          <p style="margin-top:0.8rem;font-weight:600;color:#2c1810;">Payment successful!</p>'
            + '          <p style="color:#666;font-size:0.9rem;">Finalizing your booking...</p>'
            + '        </div>'

            + '        <div id="spErrorState" style="display:none;text-align:center;padding:1.5rem 0;">'
            + '          <i class="fas fa-exclamation-circle" style="font-size:2.6rem;color:#dc3545;"></i>'
            + '          <p style="margin-top:0.8rem;font-weight:600;color:#2c1810;" id="spErrorText">Something went wrong.</p>'
            + '          <button type="button" id="spCloseErrorBtn" class="btn-confirm-booking" style="margin-top:1rem;">Close</button>'
            + '        </div>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        document.body.insertAdjacentHTML('beforeend', html);

        document.getElementById('spCloseBtn').addEventListener('click', closePaymentModal);
        document.getElementById('spCloseErrorBtn').addEventListener('click', closePaymentModal);
        document.getElementById('stripePaymentModal').addEventListener('click', function (e) {
            if (e.target === this && !isProcessing) closePaymentModal();
        });
        document.getElementById('spPayBtn').addEventListener('click', handlePayClick);
    }

    function showState(state) {
        ['spLoadingState', 'spFormState', 'spSuccessState', 'spErrorState'].forEach(function (id) {
            document.getElementById(id).style.display = (id === state) ? 'block' : 'none';
        });
    }

    function openPaymentModal() {
        ensureModalMarkup();
        document.getElementById('stripePaymentModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        showState('spLoadingState');
        document.getElementById('spLoadingText').textContent = 'Preparing secure payment...';
    }

    function closePaymentModal() {
        var modal = document.getElementById('stripePaymentModal');
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
        if (paymentElement) {
            try { paymentElement.unmount(); } catch (e) {}
            paymentElement = null;
        }
        elementsGroup = null;
        activeOptions = null;
        activeFormData = null;
        currentClientSecret = null;
        isProcessing = false;
    }

    // ------------------------------------------------------------------
    // Public entry point.
    // ------------------------------------------------------------------
    window.startBookingPayment = function (options) {
        if (!options || !options.form || !options.bookingType || !options.finalizeEndpoint) {
            console.error('startBookingPayment: missing required options (form, bookingType, finalizeEndpoint)');
            return;
        }

        var formData = new FormData(options.form);
        formData.append('booking_type', options.bookingType);

        fetch('create-payment-intent.php', { method: 'POST', body: formData })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    if (typeof options.onValidationError === 'function') {
                        options.onValidationError(data.error);
                    } else {
                        alert(data.error);
                    }
                    return;
                }

                activeOptions = options;
                activeFormData = formData;
                currentClientSecret = data.client_secret;

                openPaymentModal();
                ensureStripeJsLoaded(function () {
                    renderPaymentForm(data);
                });
            })
            .catch(function () {
                if (typeof options.onValidationError === 'function') {
                    options.onValidationError('Could not start payment. Please check your connection and try again.');
                } else {
                    alert('Could not start payment. Please try again.');
                }
            });
    };

    function renderPaymentForm(data) {
        document.getElementById('spItemTitle').textContent = (data.summary && data.summary.title) ? data.summary.title : '';
        document.getElementById('spItemSubtitle').textContent = (data.summary && data.summary.subtitle) ? data.summary.subtitle : '';
        document.getElementById('spAmount').innerHTML = 'LKR ' + Number(data.amount).toFixed(2) + ' <small>' + (data.currency || 'lkr').toUpperCase() + '</small>';

        var appearance = {
            theme: 'stripe',
            variables: {
                colorPrimary: '#c5a263',
                colorText: '#2c1810',
                fontFamily: 'Poppins, sans-serif',
                borderRadius: '10px'
            }
        };

        // ✅ FIX: Remove the problematic 'fields' option completely
        // Or set billing_details.address to 'auto' (default is 'auto' anyway)
        // By not passing 'fields', Stripe uses the default behavior (auto)
        elementsGroup = stripeInstance.elements({
            clientSecret: data.client_secret,
            appearance: appearance
        });

        paymentElement = elementsGroup.create('payment');
        paymentElement.mount('#stripePaymentElement');

        showState('spFormState');
        document.getElementById('spCardError').style.display = 'none';
        setPayButtonLoading(false);
    }

    function setPayButtonLoading(loading) {
        var btn = document.getElementById('spPayBtn');
        var label = document.getElementById('spPayBtnLabel');
        isProcessing = loading;
        btn.disabled = loading;
        btn.style.opacity = loading ? '0.7' : '1';
        label.textContent = loading ? 'Processing...' : 'Pay Now';
    }

    function handlePayClick() {
        if (isProcessing || !stripeInstance || !elementsGroup) return;
        setPayButtonLoading(true);
        document.getElementById('spCardError').style.display = 'none';

        stripeInstance.confirmPayment({
            elements: elementsGroup,
            confirmParams: {
                return_url: window.location.href
            },
            redirect: 'if_required'
        }).then(function (result) {
            if (result.error) {
                setPayButtonLoading(false);
                var errDiv = document.getElementById('spCardError');
                errDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + result.error.message;
                errDiv.style.display = 'block';
                return;
            }

            if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                showState('spSuccessState');
                finalizeBooking(result.paymentIntent.id);
            } else {
                setPayButtonLoading(false);
                var errDiv2 = document.getElementById('spCardError');
                errDiv2.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Payment was not completed. Please try again.';
                errDiv2.style.display = 'block';
            }
        });
    }

    function finalizeBooking(paymentIntentId) {
        var options = activeOptions;
        var formData = activeFormData;
        formData.append('payment_intent_id', paymentIntentId);

        fetch(options.finalizeEndpoint, { method: 'POST', body: formData })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    closePaymentModal();
                    if (typeof options.onSuccess === 'function') options.onSuccess(data);
                } else {
                    showState('spErrorState');
                    document.getElementById('spErrorText').textContent = data.error || 'Your payment went through, but the booking could not be finalized.';
                }
            })
            .catch(function () {
                showState('spErrorState');
                document.getElementById('spErrorText').textContent = 'Your payment went through, but we could not confirm the booking. Please contact support with your payment reference: ' + paymentIntentId;
            });
    }
})();