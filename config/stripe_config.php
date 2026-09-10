<?php
/**
 * Stripe configuration.
 *
 * ⚠️ These are TEST MODE keys (sk_test_ / pk_test_) — they cannot move real money.
 * When you go live, replace them with your LIVE keys (sk_live_ / pk_live_) and:
 *   - never commit this file to a public git repo (add it to .gitignore)
 *   - keep STRIPE_SECRET_KEY on the server only — it must never be sent to the browser
 *   - STRIPE_PUBLISHABLE_KEY is safe to expose in frontend JS/HTML by design
 */

define('STRIPE_SECRET_KEY', 'sk_test_51TvLhiRGiYQbuvnvbGnpmdasM4d8wkzqoqK4xNUZmTkWiFZ4kaDTmDkueKHi4QiBjvaX3JMTScmegjP187Yx2eR900Py989HRP');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51TvLhiRGiYQbuvnvfx5WtvBDQmI83HAFTyzvg8fDmsTOJTJqlgdl0tpDlJjl4OpIptCXAc7jpVFgGyVdtW0T3MKz00SFk3qExU');

// Currency used across the whole booking system (matches the "LKR" prices shown on the site).
// LKR uses 2 decimal places just like USD, so the *100 "smallest unit" math elsewhere
// in the codebase (create-payment-intent.php, room-booking-process.php, event-booking-process.php)
// did not need to change.
define('STRIPE_CURRENCY', 'lkr');
