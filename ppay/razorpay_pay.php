<?php
require_once('../system/op_lib.php');
require_once('../function.php');
require_once('../system/razorpay-php/Razorpay.php');

use Razorpay\Api\Api;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

if (isset($_POST['amount']) && $_POST['amount'] > 0) {
    $amount = $_POST['amount'] * 100; // Razorpay expects amount in paise
    $customer_name = $_POST['name'] ?? '';
    $customer_email = $_POST['email'] ?? '';
    $customer_mobile = $_POST['mobile'] ?? '';
    $demand_id = $_POST['demand_id'] ?? (date('ymdhis') . rand(1000, 9999));

    // Create Razorpay Order
    $orderData = [
        'receipt'         => $demand_id,
        'amount'          => $amount,
        'currency'        => RZP_CURRENCY,
        'payment_capture' => 1 // auto capture
    ];

    try {
        $razorpayOrder = $api->order->create($orderData);
        $razorpayOrderId = $razorpayOrder['id'];
    } catch (Exception $e) {
        require_once('../system/op_error_handler.php');
        render_caseos_error("Payment Gateway Error", "Unable to create Razorpay payment order. Please verify API keys or try again.", $e->getMessage(), 500);
    }

    // Insert into payment_orders table
    insert_data('payment_orders', [
        'op_user_id' => $_SESSION['user_id'] ?? 0,
        'order_id'   => $razorpayOrderId,
        'amount'     => $_POST['amount'],
        'currency'   => RZP_CURRENCY,
        'receipt'    => $demand_id,
        'status'     => 'created'
    ]);

    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
    $_SESSION['recharge_amount'] = $_POST['amount'];
    $_SESSION['demand_id'] = $demand_id;

    $displayAmount = $amount / 100;
} else {
    header("Location: ../public/recharge");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connecting to Payment Gateway - <?= htmlspecialchars($inst_name ?? 'CaseOS') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
        }
        .gateway-card {
            background: #ffffff;
            border-radius: 1.5rem;
            max-width: 480px;
            width: 100%;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            text-align: center;
        }
        .spinner-custom {
            width: 3.5rem;
            height: 3.5rem;
            border-width: 0.35rem;
        }
        .order-badge {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="gateway-card">
        <!-- Spinner -->
        <div class="mb-4">
            <div class="spinner-border text-primary spinner-custom" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <h4 class="fw-bold text-dark mb-1">Opening Secure Payment Gateway</h4>
        <p class="text-muted small mb-0">Please wait while we connect to Razorpay. Do not refresh or close this tab.</p>

        <!-- Order Summary Pill -->
        <div class="order-badge small">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Order ID:</span>
                <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($demand_id) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Recharge Amount:</span>
                <span class="fw-bold text-success fs-6">₹<?= number_format((float)$displayAmount, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Account:</span>
                <span class="text-dark fw-semibold"><?= htmlspecialchars($customer_name ?: 'User') ?></span>
            </div>
        </div>

        <!-- Hidden Razorpay Checkout Form -->
        <form action="razorpay_verify.php" method="POST" id="razorpay-form">
            <script
                src="https://checkout.razorpay.com/v1/checkout.js"
                data-key="<?php echo RAZORPAY_KEY_ID; ?>"
                data-amount="<?php echo $amount; ?>"
                data-currency="<?php echo RZP_CURRENCY; ?>"
                data-order_id="<?php echo $razorpayOrderId; ?>"
                data-buttontext="Pay with Razorpay"
                data-name="<?= htmlspecialchars($inst_name ?? 'CaseOS') ?>"
                data-description="Wallet Recharge - <?= htmlspecialchars($demand_id) ?>"
                data-image="<?= $base_url ?>img/icons/icon-48x48.png"
                data-prefill.name="<?php echo htmlspecialchars($customer_name); ?>"
                data-prefill.email="<?php echo htmlspecialchars($customer_email); ?>"
                data-prefill.contact="<?php echo htmlspecialchars($customer_mobile); ?>"
                data-theme.color="#2563eb"
            ></script>
            <input type="hidden" name="shopping_order_id" value="<?php echo htmlspecialchars($demand_id); ?>">
            <button id="pay-button" style="display:none;" type="submit">Pay with Razorpay</button>
        </form>

        <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-2" onclick="triggerPayment()">
                <i data-feather="external-link" class="me-1" style="width: 14px; height: 14px;"></i> Click here if gateway did not open
            </button>
            <a href="../public/recharge" class="btn btn-link btn-sm text-decoration-none text-muted">
                Cancel & Return to Wallet
            </a>
        </div>

        <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-center gap-2 text-muted small" style="font-size: 0.72rem;">
            <img src="https://razorpay.com/assets/razorpay-glyph-bold.svg" height="14" alt="Razorpay">
            <span>Powered by Razorpay Payments &bull; PCI-DSS Level 1 Compliant</span>
        </div>
    </div>

    <script>
        feather.replace();
        function triggerPayment() {
            var btn = document.querySelector('.razorpay-payment-button');
            if (btn) {
                btn.click();
            } else {
                var hiddenBtn = document.getElementById('pay-button');
                if (hiddenBtn) hiddenBtn.click();
            }
        }
        window.addEventListener('load', function() {
            setTimeout(triggerPayment, 600);
        });
    </script>
</body>
</html>
