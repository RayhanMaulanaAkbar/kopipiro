<?php

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(['message' => 'Method not allowed'], 405);
}

$notification = api_input();

$orderId = $notification['order_id'] ?? '';
$statusCode = $notification['status_code'] ?? '';
$grossAmount = $notification['gross_amount'] ?? '';
$signatureKey = $notification['signature_key'] ?? '';

if (
    $orderId === '' ||
    $statusCode === '' ||
    $grossAmount === '' ||
    $signatureKey === ''
) {
    api_response(['message' => 'Incomplete notification'], 400);
}

$expectedSignature = hash(
    'sha512',
    $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY
);

if (!hash_equals($expectedSignature, $signatureKey)) {
    api_response(['message' => 'Invalid signature'], 403);
}

$transactionStatus = $notification['transaction_status'] ?? '';
$fraudStatus = $notification['fraud_status'] ?? 'accept';

$paymentStatus = null;

if (
    $statusCode === '200' &&
    (
        $transactionStatus === 'settlement' ||
        (
            $transactionStatus === 'capture' &&
            $fraudStatus === 'accept'
        )
    )
) {
    $paymentStatus = 'paid';
} elseif ($transactionStatus === 'pending') {
    $paymentStatus = 'unpaid';
} elseif ($transactionStatus === 'expire') {
    $paymentStatus = 'expired';
} elseif ($transactionStatus === 'cancel' || $transactionStatus === 'deny') {
    $paymentStatus = 'failed';
}

if ($paymentStatus !== null) {

    if (!preg_match('/^KOPI-([1-9][0-9]*)$/', $orderId, $matches)) {
        api_response(['message' => 'Invalid order ID'], 400);
    }

    $databaseOrderId = (int) $matches[1];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE orders
         SET payment_status = ?
         WHERE id = ?
         AND total = ?
         AND payment_status = 'unpaid'"
    );

    $amount = (float) $grossAmount;

    mysqli_stmt_bind_param(
        $stmt,
        "sid",
        $paymentStatus,
        $databaseOrderId,
        $amount
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

api_response(['ok' => true]);

?>