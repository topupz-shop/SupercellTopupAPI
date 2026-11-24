<?php
// Supercell top-up with the Topupz API (PHP 8, curl extension).
// Usage: TOPUPZ_API_KEY=tpz_... php topup.php player@gmail.com

const BASE = 'https://api.topupz.shop/api/v1/reseller';

function api(string $method, string $path, ?array $body = null): mixed
{
    $ch = curl_init(BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-Key: ' . getenv('TOPUPZ_API_KEY'),
            'X-REGION-KEY: default',
        ],
        CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
    ]);
    $json = json_decode(curl_exec($ch), true);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (empty($json['success'])) {
        throw new RuntimeException("$status " . ($json['message'] ?? 'error'));
    }
    return $json['data'];
}

$email = $argv[1];
print_r(api('GET', '/balance'));

// 54 = Brawl Stars, 55 = Clash of Clans, 56 = Clash Royale, 57 = Hay Day
$products = api('GET', '/products?category_id=57');
$pack = $products[0];
echo "Buying: {$pack['name']} {$pack['price']} {$pack['currency']}\n";

$order = api('POST', '/orders', [
    'product_id' => $pack['id'],
    'fields' => ['supercell_id_email' => $email],
]);
echo "Order {$order['id']} {$order['status']}\n";
// When $order['fulfillment']['requires_otp'] is true:
// api('POST', "/orders/{$order['id']}/otp", ['otp' => '123456']);

// Saved accounts: every completed order saves the game account it used.
// Filter by email, order_id or game; sort by date or order_id, asc or desc.
$saved = api('GET', '/accounts?' . http_build_query([
    'game' => 57, 'email' => $email, 'sort' => 'order_id', 'order' => 'asc',
]));
foreach ($saved['items'] as $account) {
    echo "Saved: {$account['id']} {$account['title']} (last order {$account['last_order_id']})
";
}
if (! empty($saved['items'])) {
    // Order again without sending the fields: they come from the saved account.
    $again = api('POST', '/orders', ['product_id' => $pack['id'], 'account_id' => $saved['items'][0]['id']]);
    echo "Repeat order {$again['id']} {$again['status']}
";
}
