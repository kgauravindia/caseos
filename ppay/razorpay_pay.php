<?php
require_once('../system/op_lib.php');
require_once('../system/razorpay-php/Razorpay.php');

use Razorpay\Api\Api;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

if (isset($_POST['amount']) && $_POST['amount'] > 0) {
    $amount = $_POST['amount'] * 100; // Razorpay expects amount in paise
    $customer_name = $_POST['name'];
    $customer_email = $_POST['email'];
    $customer_mobile = $_POST['mobile'];
    $demand_id = $_POST['demand_id'];

    // Create Razorpay Order
    $orderData = [
        'receipt'         => $demand_id,
        'amount'          => $amount,
        'currency'        => RZP_CURRENCY,
        'payment_capture' => 1 // auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];

    // Insert into payment_orders table
    insert_data('payment_orders', [
        'user_id' => $_SESSION['user_id'],
        'order_id' => $razorpayOrderId,
        'amount' => $_POST['amount'],
        'currency' => RZP_CURRENCY,
        'receipt' => $demand_id,
        'status' => 'created'
    ]);

    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
    $_SESSION['recharge_amount'] = $_POST['amount'];
    $_SESSION['demand_id'] = $demand_id;

    $displayAmount = $amount / 100;
} else {
    die("Invalid access.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Wait for payment...</title>
    <meta name="viewport" content="width=device-width">
</head>
<body onload="document.getElementById('pay-button').click();">
    <div style="text-align: center; margin-top: 50px;">
        <h2>Redirecting to Payment Gateway...</h2>
        <p>Please do not refresh this page.</p>
        <button id="pay-button" style="display:none;">Pay with Razorpay</button>
    </div>

    <form action="razorpay_verify.php" method="POST" id="razorpay-form">
        <script
            src="https://checkout.razorpay.com/v1/checkout.js"
            data-key="<?php echo RAZORPAY_KEY_ID; ?>"
            data-amount="<?php echo $amount; ?>"
            data-currency="<?php echo RZP_CURRENCY; ?>"
            data-order_id="<?php echo $razorpayOrderId; ?>"
            data-buttontext="Pay with Razorpay"
            data-name="<?= $inst_name ?>"
            data-description="Wallet Recharge - <?= $demand_id ?>"
            data-image="<?= $base_url ?>img/icons/icon-48x48.png"
            data-prefill.name="<?php echo $customer_name; ?>"
            data-prefill.email="<?php echo $customer_email; ?>"
            data-prefill.contact="<?php echo $customer_mobile; ?>"
            data-theme.color="#384350"
        ></script>
        <input type="hidden" name="shopping_order_id" value="<?php echo $demand_id; ?>">
    </form>
</body>
</html>
