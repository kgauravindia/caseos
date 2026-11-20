<?php
require_once('../system/op_lib.php');
require_once('../function.php');
require_once('../system/razorpay-php/Razorpay.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$success = true;
$error = "Payment Verification Failed";
$new_wallet_balance = 0;

if (!empty($_POST['razorpay_payment_id'])) {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    try {
        $attributes = array(
            'razorpay_order_id'   => $_SESSION['razorpay_order_id'] ?? '',
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature'  => $_POST['razorpay_signature'] ?? ''
        );

        $api->utility->verifyPaymentSignature($attributes);
    } catch (SignatureVerificationError $e) {
        $success = false;
        $error = 'Signature Verification Error: ' . $e->getMessage();
    }
} else {
    $success = false;
    $error = "Payment was not authorized or cancelled by user.";
}

if ($success === true) {
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    $razorpay_order_id   = $_POST['razorpay_order_id'] ?? ($_SESSION['razorpay_order_id'] ?? '');
    $razorpay_signature  = $_POST['razorpay_signature'] ?? '';
    $demand_id           = $_SESSION['demand_id'] ?? '';
    $amount              = $_SESSION['recharge_amount'] ?? 0;
    $session_uid         = $_SESSION['user_id'] ?? 0;
    
    // Log the success data to payment_logs
    insert_data('payment_logs', [
        'op_user_id' => $session_uid,
        'amount'     => $amount,
        'order_id'   => $razorpay_order_id,
        'payment_id' => $razorpay_payment_id,
        'signature'  => $razorpay_signature,
        'status'     => 'success',
        'plan_type'  => 'CASEOS'
    ]);

    // Find User From Demand ID / Order
    $payment_record = get_all('online_payment', '*', ['demand_id' => $demand_id]);
    
    if (($payment_record['count'] ?? 0) > 0) {
        $payment_data = $payment_record['data'][0];
        $user_id = $payment_data['created_by'] ?: $session_uid;
        $record_id = $payment_data['id'];

        // Prevent Duplicate Processing
        if (($payment_data['status'] ?? '') !== 'SUCCESS') {
            
            // 1. Update legacy online_payment status record
            try {
                update_data('online_payment', [
                    'status'     => 'SUCCESS',
                    'amount'     => $amount,
                    'payment_id' => $razorpay_payment_id
                ], $record_id);
            } catch (\Throwable $e) {
                error_log("online_payment update notice: " . $e->getMessage());
            }

            // 2. Update new payment_orders table
            try {
                $order = get_data('payment_orders', $razorpay_order_id, null, 'order_id')['data'] ?? null;
                if ($order && !empty($order['id'])) {
                    update_data('payment_orders', [
                        'status'     => 'paid',
                        'payment_id' => $razorpay_payment_id,
                        'signature'  => $razorpay_signature
                    ], $order['id']);
                }
            } catch (\Throwable $e) {
                error_log("payment_orders update notice: " . $e->getMessage());
            }

            // 3. Insert into payment_transactions table
            try {
                insert_data('payment_transactions', [
                    'op_user_id' => $user_id,
                    'order_id'   => $razorpay_order_id,
                    'payment_id' => $razorpay_payment_id,
                    'amount'     => $amount,
                    'currency'   => RZP_CURRENCY,
                    'notes'      => json_encode($_POST),
                    'status'     => 'success',
                    'plan_type'  => 'CASEOS'
                ]);
            } catch (\Throwable $e) {
                error_log("payment_transactions insert notice: " . $e->getMessage());
            }

            // 4. Update Wallet balance and history
            wallet_amount($user_id, $amount, 'ADD', "Wallet Recharge via Razorpay ($razorpay_payment_id)");
        }
        $new_wallet_balance = get_data('op_user', $user_id, 'wallet')['data'] ?? 0;
    } else {
        // Direct recharge without pre-existing online_payment record
        wallet_amount($session_uid, $amount, 'ADD', "Wallet Recharge via Razorpay ($razorpay_payment_id)");
        $new_wallet_balance = get_data('op_user', $session_uid, 'wallet')['data'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment <?= $success ? 'Successful' : 'Failed' ?> - <?= htmlspecialchars($inst_name ?? 'CaseOS') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            color: #334155;
        }
        .status-card {
            background: #ffffff;
            border-radius: 1.5rem;
            max-width: 520px;
            width: 100%;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            text-align: center;
            animation: fadeIn 0.35s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .icon-circle-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        .receipt-summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 1.25rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        .btn-premium {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.65rem 1.25rem;
            transition: all 0.2s ease;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="status-card">
        <?php if ($success): ?>
            <!-- Success Icon -->
            <div class="icon-circle-lg bg-success bg-opacity-10 text-success">
                <i data-feather="check-circle" style="width: 44px; height: 44px;"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Recharge Successful!</h3>
            <p class="text-muted small mb-0">Your payment has been verified and your wallet has been credited.</p>

            <!-- Receipt Breakdown -->
            <div class="receipt-summary small">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Recharged Amount</span>
                    <strong class="text-success fs-5">₹<?= number_format((float)$amount, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Updated Balance</span>
                    <strong class="text-dark">₹<?= number_format((float)$new_wallet_balance, 2) ?></strong>
                </div>
                <hr class="my-2 border-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted">Payment ID</span>
                    <span class="font-monospace fw-semibold text-primary"><?= htmlspecialchars($razorpay_payment_id) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Date & Time</span>
                    <span class="text-dark"><?= date('d M Y, h:i A') ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2 mb-3">
                <a href="<?= $base_url ?>system/recharge_receipt?payment_id=<?= urlencode($razorpay_payment_id) ?>" target="_blank" class="btn btn-primary btn-premium shadow-sm">
                    <i data-feather="file-text" class="me-1" style="width: 16px; height: 16px;"></i> View & Download Tax Invoice
                </a>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="<?= $base_url ?>index" class="btn btn-light border btn-premium w-100 text-dark">
                            <i data-feather="home" class="me-1" style="width: 14px; height: 14px;"></i> Dashboard
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= $base_url ?>public/recharge" class="btn btn-light border btn-premium w-100 text-dark">
                            <i data-feather="plus-circle" class="me-1" style="width: 14px; height: 14px;"></i> Add More
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Failure Icon -->
            <div class="icon-circle-lg bg-danger bg-opacity-10 text-danger">
                <i data-feather="x-circle" style="width: 44px; height: 44px;"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Payment Unsuccessful</h3>
            <p class="text-muted small mb-3"><?= htmlspecialchars($error) ?></p>

            <div class="alert alert-light border small text-muted text-start mb-4">
                No funds were deducted from your account. If amount was debited by your bank, it will be automatically refunded within 3-5 business days.
            </div>

            <div class="d-grid gap-2">
                <a href="<?= $base_url ?>public/recharge" class="btn btn-primary btn-premium shadow-sm">
                    <i data-feather="refresh-cw" class="me-1" style="width: 16px; height: 16px;"></i> Try Payment Again
                </a>
                <a href="<?= $base_url ?>index" class="btn btn-light border btn-premium text-dark">
                    <i data-feather="arrow-left" class="me-1" style="width: 16px; height: 16px;"></i> Return to Dashboard
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-4 pt-3 border-top text-muted small" style="font-size: 0.72rem;">
            <?= htmlspecialchars($inst_name ?? 'CaseOS') ?> &bull; Secured with 256-bit Encryption
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
