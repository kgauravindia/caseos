<?php
require_once('../function.php');
session_start();

if (!isset($_SESSION['initiated'])) {
    die("Access Denied");
}

$payment_id = isset($_GET['payment_id']) ? mysqli_real_escape_string($con, $_GET['payment_id']) : '';

if (empty($payment_id)) {
    die("Invalid Payment ID");
}

// Fetch Transaction Details
$transaction = get_all('payment_transactions', '*', ['payment_id' => $payment_id]);

if ($transaction['count'] == 0) {
    die("Transaction Not Found");
}

$txn = $transaction['data'][0];
$user_id = $txn['op_user_id'];
$user = get_data('op_user', $user_id)['data'];

// Format Date
$date = date('d-M-Y H:i', strtotime($txn['transaction_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= $inst_name ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        .receipt-card { background: #fff; max-width: 700px; margin: 0 auto; padding: 40px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #f1f1f1; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #333; font-size: 28px; }
        .header p { margin: 5px 0; color: #777; }
        .receipt-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .receipt-info div { color: #555; font-size: 14px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th { text-align: left; background: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 12px; }
        .table td { padding: 12px; border-bottom: 1px solid #dee2e6; color: #333; }
        .total-section { text-align: right; margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
        .total-section h2 { margin: 0; color: #2ecc71; font-size: 24px; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 12px; }
        .btn-print { margin: 20px 0; text-align: center; }
        .btn { display: inline-block; padding: 10px 25px; background: #34495e; color: #fff; text-decoration: none; border-radius: 5px; }
        @media print {
            .btn-print { display: none; }
            body { background: #fff; padding: 0; }
            .receipt-card { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="btn-print">
        <a href="javascript:window.print()" class="btn">Print Receipt</a>
        <a href="<?= $base_url ?>index" class="btn" style="background:#95a5a6;">Back to Dashboard</a>
    </div>

    <div class="receipt-card">
        <div class="header">
            <h1><?= $inst_name ?></h1>
            <p><?= $full_name ?></p>
            <p>Advocate Case Operating System</p>
        </div>

        <div class="receipt-info">
            <div>
                <strong>Billed To:</strong><br>
                <?= $user['full_name'] ?><br>
                <?= $user['user_email'] ?><br>
                <?= $user['user_mobile'] ?>
            </div>
            <div style="text-align: right;">
                <strong>Receipt Details:</strong><br>
                Date: <?= $date ?><br>
                Txn ID: <?= $txn['payment_id'] ?><br>
                Status: SUCCESS
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Wallet Recharge - Razorpay Payment</td>
                    <td style="text-align: right;">₹<?= number_format($txn['amount'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <p style="margin:0;">Total Recharge Amount</p>
            <h2>₹<?= number_format($txn['amount'], 2) ?></h2>
        </div>

        <div class="footer">
            <p>This is a computer-generated receipt and does not require a physical signature.</p>
            <p>&copy; <?= date('Y') ?> <?= $inst_name ?>. All rights reserved.</p>
        </div>
    </div>

</body>
</html>
