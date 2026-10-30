<?php
function get_store_exchange_rates($con) {
    static $rates = null;
    if ($rates !== null) {
        return $rates;
    }

    $result = mysqli_query($con, "SELECT lrd_per_usd FROM shop_settings WHERE id = 1");
    if (!$result) {
        throw new RuntimeException('Unable to load currency exchange rates: ' . mysqli_error($con));
    }
    $settings = mysqli_fetch_assoc($result);
    $rates = [
        'lrd_per_usd' => (float)($settings['lrd_per_usd'] ?? 0)
    ];

    return $rates;
}

function format_store_price($lrd_amount, $con) {
    $rates = get_store_exchange_rates($con);
    if ($rates['lrd_per_usd'] <= 0) {
        return 'Exchange rates not configured';
    }

    $lrd_amount = (float)$lrd_amount;
    $usd_amount = $lrd_amount / $rates['lrd_per_usd'];

    return 'USD $' . number_format($usd_amount, 2) . ' / LRD L$' . number_format($lrd_amount, 2);
}

function format_store_price_from_usd($usd_amount, $con) {
    $rates = get_store_exchange_rates($con);
    if ($rates['lrd_per_usd'] <= 0) {
        return 'Exchange rates not configured';
    }

    $lrd_amount = (float)$usd_amount * $rates['lrd_per_usd'];

    return 'USD $' . number_format((float)$usd_amount, 2) . ' / LRD L$' . number_format($lrd_amount, 2);
}
