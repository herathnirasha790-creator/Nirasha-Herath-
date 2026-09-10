<?php
/**
 * Minimal Stripe REST API client using plain cURL — no Composer / stripe-php SDK needed.
 * Talks directly to https://api.stripe.com/v1/... using HTTP Basic Auth (secret key as username).
 */

require_once __DIR__ . '/stripe_config.php';

/**
 * Low level request helper.
 *
 * @param string $method  'GET' | 'POST'
 * @param string $path    e.g. '/payment_intents'
 * @param array  $params  request params (nested arrays are sent as name[sub]=value, same as Stripe expects)
 * @return array           decoded JSON response
 * @throws Exception       on network error or Stripe error response
 */
function stripe_request($method, $path, $params = []) {
    if (!function_exists('curl_init')) {
        throw new Exception('The PHP cURL extension is not enabled on this server.');
    }

    $url = 'https://api.stripe.com/v1' . $path;
    $method = strtoupper($method);

    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
        $params = [];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST' && !empty($params)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Could not reach Stripe: ' . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode >= 400) {
        $msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Stripe error (HTTP ' . $httpCode . ')';
        throw new Exception($msg);
    }

    return $data;
}

/**
 * Creates a PaymentIntent for the given amount (in the smallest currency unit, e.g. cents for USD).
 */
function stripe_create_payment_intent($amount_cents, $currency, $metadata = []) {
    $params = [
        'amount' => $amount_cents,
        'currency' => $currency,
        // Card only — keeps the popup compact (no Direct debit / Cash App Pay / Link tabs & no-scroll).
        'payment_method_types' => ['card'],
        'metadata' => $metadata,
    ];
    return stripe_request('POST', '/payment_intents', $params);
}

/**
 * Retrieves a PaymentIntent by id, so we can check its status server-side before trusting it.
 */
function stripe_retrieve_payment_intent($payment_intent_id) {
    return stripe_request('GET', '/payment_intents/' . urlencode($payment_intent_id), []);
}

/**
 * Refunds a PaymentIntent in full. Used when a booking can't go ahead after payment
 * (e.g. someone else booked the same slot first, or an admin rejects the booking).
 */
function stripe_create_refund($payment_intent_id) {
    return stripe_request('POST', '/refunds', ['payment_intent' => $payment_intent_id]);
}