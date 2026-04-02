<?php
require_once('../system/op_lib.php');
require_once('../system/razorpay-php/Razorpay.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$success = true;
$error = "Payment Failed";

if (!empty($_POST['razorpay_payment_id'])) {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    try {
        $attributes = array(
            'razorpay_order_id' => $_SESSION['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        );

        $api->utility->verifyPaymentSignature($attributes);
    } catch (SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
} else {
    $success = false;
}

if ($success === true) {
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    $razorpay_order_id = $_POST['razorpay_order_id'];
    $razorpay_signature = $_POST['razorpay_signature'];
    $demand_id = $_SESSION['demand_id'];
    $amount = $_SESSION['recharge_amount'];
    
    // Log the success raw data
    insert_data('payment_logs', [
        'advocate_id' => $_SESSION['user_id'],
        'log_data' => json_encode($_POST),
        'status' => 'SUCCESS'
    ]);

    // Find User From Demand ID / Order
    $payment_record = get_all('online_payment', '*', ['demand_id' => $demand_id]);
    
    if($payment_record['count'] > 0) {
        $payment_data = $payment_record['data'][0];
        $user_id = $payment_data['created_by'];
        $record_id = $payment_data['id'];

        // Prevent Duplicate Processing
        if($payment_data['status'] !== 'SUCCESS') {
            
            // 1. Update legacy online_payment status record
            update_data('online_payment', [
                'status' => 'SUCCESS',
                'amount' => $amount,
                'payment_id' => $razorpay_payment_id
            ], $record_id);

            // 2. Update new payment_orders table
            $order = get_data('payment_orders', $razorpay_order_id, null, 'order_id')['data'];
            update_data('payment_orders', [
                'status' => 'paid',
                'payment_id' => $razorpay_payment_id,
                'signature' => $razorpay_signature
            ], $order['id']);

            // 3. Insert into new payment_transactions table
            insert_data('payment_transactions', [
                'advocate_id' => $user_id,
                'order_id' => $razorpay_order_id,
                'payment_id' => $razorpay_payment_id,
                'amount' => $amount,
                'currency' => RZP_CURRENCY,
                'status' => 'success'
            ]);

            // 4. Use Core Function to update Wallet balance and history
            wallet_amount($user_id, $amount, 'ADD', "Wallet Recharge via Razorpay ($razorpay_payment_id)");

        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Status</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
    <style>
        body { text-align: center; padding: 40px 0; background: #EBF0F5; font-family: "Nunito Sans", sans-serif; }
        .card { background: white; padding: 60px; border-radius: 4px; box-shadow: 0 2px 3px #C8D0D8; display: inline-block; margin: 0 auto; }
        h1 { font-weight: 900; font-size: 40px; margin-bottom: 10px; }
        .success h1 { color: #88B04B; }
        .error h1 { color: #E74C3C; }
        p { font-size: 20px; color: #404F5E; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #384350; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="card <?= $success ? 'success' : 'error' ?>">
        <?php if ($success): ?>
            <div style="border-radius:200px; height:200px; width:200px; background: #F8FAF5; margin:0 auto;">
                <i style="color: #9ABC66; font-size: 100px; line-height: 200px;">✓</i>
            </div>
            <h1>Success</h1>
            <p>Your wallet has been recharged with ₹<?= $amount ?>.</p>
            <p>Payment ID: <?= $razorpay_payment_id ?></p>
        <?php else: ?>
            <div style="border-radius:200px; height:200px; width:200px; background: #FDEDEC; margin:0 auto;">
                <i style="color: #E74C3C; font-size: 100px; line-height: 200px;">✗</i>
            </div>
            <h1>Failed</h1>
            <p><?= $error ?></p>
        <?php endif; ?>
        <br>
        <a href="<?= $base_url ?>index" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>
