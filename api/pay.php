<?php

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(['message' => 'Method not allowed'], 405);
}

$input = api_input();
$orderId = (int) ($input['order_id'] ?? 0);

if ($orderId <= 0) {
    api_response(['message' => 'Invalid order ID'], 400);
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT o.*, COALESCE(u.username, 'Guest') AS customer_name
     FROM orders o
     LEFT JOIN users u ON o.customer_id = u.id
     WHERE o.id = ? AND o.status IN ('pending', 'processing')
     AND o.payment_status = 'unpaid'"
);

mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    api_response(['message' => 'Pending order not found'], 404);
}

$midtransOrderId = 'KOPI-' . $order['id'];

$payload = [
    'transaction_details' => [
        'order_id' => $midtransOrderId,
        'gross_amount' => (int) round((float) $order['total'])
    ],
    'customer_details' => [
        'first_name' => $order['customer_name']
    ]
];

$ch = curl_init(
    'https://app.sandbox.midtrans.com/snap/v1/transactions'
);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERPWD => MIDTRANS_SERVER_KEY . ':',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$data = json_decode((string) $response, true);

if ($httpCode !== 201 || empty($data['redirect_url'])) {
    api_response([
        'message' => 'Payment gateway error'
    ], 502);
}

api_response([
    'message' => 'Payment link created successfully',
    'redirect_url' => $data['redirect_url']
]);

?>