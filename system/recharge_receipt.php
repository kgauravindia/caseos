<?php
require_once('../function.php');
// session_start() removed as it is already started in op_config.php

if (!isset($_SESSION['initiated'])) {
    render_caseos_error("Access Denied", "You must be logged in to access payment receipts.", null, 403);
}

$payment_id = isset($_GET['payment_id']) ? mysqli_real_escape_string($con, trim($_GET['payment_id'])) : '';

if (empty($payment_id)) {
    render_caseos_error("Invalid Request", "No payment ID was specified in the request.", null, 400);
}

// Fetch Transaction Details
$transaction = get_all('payment_transactions', '*', ['payment_id' => $payment_id]);

if (($transaction['count'] ?? 0) == 0) {
    render_caseos_error("Receipt Not Found", "No payment record was found for reference <code>" . htmlspecialchars($payment_id) . "</code>.", null, 404);
}

$txn = $transaction['data'][0];
$user_id = $txn['op_user_id'];
$user = get_data('op_user', $user_id)['data'];

// Format Date
$date = date('d F, Y h:i A', strtotime($txn['transaction_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - CaseOS | Case Operating System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --success: #27ae60;
            --bg: #f5f7fa;
            --card-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 40px 20px; color: #333; line-height: 1.6; }
        .receipt-container { max-width: 800px; margin: 0 auto; position: relative; }
        
        .receipt-card { background: #fff; border-radius: 12px; box-shadow: var(--card-shadow); padding: 60px; position: relative; overflow: hidden; }
        .receipt-card::before { content: ""; position: absolute; top: 0; left:0; right:0; height: 6px; background: linear-gradient(90deg, #2c3e50, #27ae60); }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 50px; }
        .brand h1 { margin: 0; font-size: 28px; font-weight: 700; color: var(--primary); letter-spacing: -0.5px; }
        .brand p { margin: 4px 0; color: #7f8c8d; font-size: 14px; font-weight: 500; }
        .receipt-label { text-align: right; }
        .receipt-label h2 { margin: 0; color: var(--success); font-size: 18px; text-transform: uppercase; letter-spacing: 2px; }
        .receipt-label p { margin: 4px 0; color: #95a5a6; font-size: 12px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; border-top: 1px solid #f0f0f0; padding-top: 30px; }
        .info-block h4 { color: #95a5a6; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; margin-bottom: 12px; }
        .info-block p { margin: 0; font-size: 14px; font-weight: 600; color: var(--secondary); }
        .info-block .sub-text { font-weight: 400; color: #7f8c8d; font-size: 13px; margin-top: 4px; }

        .txn-table { width: 100%; border-collapse: collapse; margin: 40px 0; border-radius: 8px; overflow: hidden; background: #fff; }
        .txn-table th { background: #f8f9fa; color: #2c3e50; font-size: 13px; font-weight: 600; text-align: left; padding: 16px; border-bottom: 2px solid #edf2f7; }
        .txn-table td { padding: 20px 16px; border-bottom: 1px solid #f1f4f8; font-size: 15px; }
        
        .amount-summary { margin-left: auto; max-width: 300px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .summary-row.total { border-top: 2px solid #333; margin-top: 15px; padding-top: 15px; }
        .summary-row.total h3 { margin: 0; font-size: 24px; color: var(--success); }
        
        .footer { margin-top: 60px; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 30px; }
        .footer p { margin: 0; font-size: 13px; color: #7f8c8d; }
        .footer .initiative { color: #2c3e50; font-weight: 600; margin-top: 5px; }
        
        .action-bar { margin-bottom: 30px; display: flex; justify-content: center; gap: 15px; }
        .btn { padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; display: inline-flex; align-items: center; }
        .btn-primary { background: var(--primary); color: white; border: 1px solid var(--primary); }
        .btn-secondary { background: white; color: var(--primary); border: 1px solid #dcdde1; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        @media print {
            .action-bar, .receipt-card::before { display: none; }
            body { padding: 0; background: white; }
            .receipt-card { box-shadow: none; border: 1px solid #ddd; padding: 40px; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="action-bar">
            <a href="javascript:window.print()" class="btn btn-primary">Download as PDF / Print</a>
            <a href="<?= $base_url ?>index" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="receipt-card">
            <div class="header">
                <div class="brand">
                    <h1>CaseOS | Case Operating System</h1>
                    <p style="font-size: 12px; max-width: 250px;">2nd Floor Godrej Building, Salempur Chapra Bihar 841301 INDIA</p>
                </div>
                <div class="receipt-label">
                    <h2>PAYMENT RECEIPT</h2>
                    <p>Generated on <?= date('d M Y') ?></p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-block">
                    <h4>BILLED TO</h4>
                    <p><?= $user['full_name'] ?></p>
                    <div class="sub-text">
                        ID: <?= $user['user_name'] ?><br>
                        <?= $user['user_email'] ?><br>
                        <?= $user['user_mobile'] ?>
                    </div>
                </div>
                <div class="info-block" style="text-align: right;">
                    <h4>TRANSACTION DETAILS</h4>
                    <p>ID: <?= $txn['payment_id'] ?></p>
                    <div class="sub-text">
                        Order ID: <?= $txn['order_id'] ?><br>
                        Date: <?= $date ?><br>
                        Status: <span style="color: var(--success); font-weight: 700;">CONFIRMED</span>
                    </div>
                </div>
            </div>

            <table class="txn-table">
                <thead>
                    <tr>
                        <th>ITEM DESCRIPTION</th>
                        <th style="text-align: right;">AMOUNT (INR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 500;">
                            Wallet Recharge<br>
                            <span style="font-size: 12px; color: #95a5a6; font-weight: 400;">Funds added via Secured Razorpay Gateway</span>
                        </td>
                        <td style="text-align: right; font-weight: 700;">₹<?= number_format($txn['amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="amount-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($txn['amount'], 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (%)</span>
                    <span>₹0.00</span>
                </div>
                <div class="summary-row total">
                    <h3>Total Paid</h3>
                    <h3>₹<?= number_format($txn['amount'], 2) ?></h3>
                </div>
            </div>

            <div class="footer">
                <div style="margin-bottom: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: left;">
                    <h4 style="margin-top: 0; color: var(--primary); font-size: 14px;">Contact Us</h4>
                    <p style="font-size: 13px; color: #333; margin-bottom: 5px;"><strong>Jaldi se contact karein!</strong></p>
                    <p style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                        Do you need more information about our services or have any queries? <br>
                        Then get in touch with us quickly!
                    </p>
                </div>
                <p>Thank you for using Case Operating System.</p>
                <p class="initiative">An initiative of OfferPlant Technologies Private Limited.</p>
                <p style="font-size: 10px; margin-top: 20px;">This is a system-generated document and does not require a physical signature.</p>
            </div>
        </div>
    </div>

</body>
</html>
