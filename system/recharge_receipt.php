<?php
require_once('../function.php');

if (!isset($_SESSION['initiated'])) {
    render_caseos_error("Access Denied", "You must be logged in to access payment invoices & receipts.", null, 403);
    exit;
}

$payment_id = isset($_GET['payment_id']) ? mysqli_real_escape_string($con, trim($_GET['payment_id'])) : '';
$order_id   = isset($_GET['order_id']) ? mysqli_real_escape_string($con, trim($_GET['order_id'])) : '';
$txn_id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($payment_id) && empty($order_id) && $txn_id <= 0) {
    render_caseos_error("Invalid Request", "No payment or transaction reference was specified.", null, 400);
    exit;
}

// 1. Fetch from payment_transactions
$txn = null;
if (!empty($payment_id)) {
    $res = get_all('payment_transactions', '*', ['payment_id' => $payment_id]);
    if (($res['count'] ?? 0) > 0) {
        $txn = $res['data'][0];
    }
}
if (!$txn && !empty($order_id)) {
    $res = get_all('payment_transactions', '*', ['order_id' => $order_id]);
    if (($res['count'] ?? 0) > 0) {
        $txn = $res['data'][0];
    }
}
if (!$txn && $txn_id > 0) {
    $res = get_data('payment_transactions', $txn_id);
    if (($res['count'] ?? 0) > 0) {
        $txn = $res['data'];
    }
}

// 2. Fallback to online_payment table if transaction row is not in payment_transactions
if (!$txn && !empty($payment_id)) {
    $res = get_all('online_payment', '*', ['payment_id' => $payment_id]);
    if (($res['count'] ?? 0) > 0) {
        $legacy = $res['data'][0];
        $txn = [
            'id'               => $legacy['id'],
            'op_user_id'       => $legacy['created_by'] ?: ($_SESSION['user_id'] ?? 0),
            'order_id'         => $legacy['demand_id'] ?? ('ORD-' . $legacy['id']),
            'payment_id'       => $legacy['payment_id'] ?? $payment_id,
            'amount'           => $legacy['amount'] ?? 0,
            'currency'         => 'INR',
            'status'           => $legacy['status'] ?? 'SUCCESS',
            'transaction_date' => $legacy['created_at'] ?? date('Y-m-d H:i:s'),
            'payment_method'   => 'Razorpay Online'
        ];
    }
}

// 3. Fallback to payment_orders table
if (!$txn && !empty($order_id)) {
    $res = get_all('payment_orders', '*', ['order_id' => $order_id]);
    if (($res['count'] ?? 0) > 0) {
        $order = $res['data'][0];
        $txn = [
            'id'               => $order['id'],
            'op_user_id'       => $order['op_user_id'] ?: ($_SESSION['user_id'] ?? 0),
            'order_id'         => $order['order_id'],
            'payment_id'       => $order['payment_id'] ?: 'Pending',
            'amount'           => $order['amount'] ?? 0,
            'currency'         => $order['currency'] ?? 'INR',
            'status'           => strtoupper($order['status'] ?? 'SUCCESS'),
            'transaction_date' => $order['created_at'] ?? date('Y-m-d H:i:s'),
            'payment_method'   => 'Razorpay Online'
        ];
    }
}

if (!$txn) {
    $ref = $payment_id ?: ($order_id ?: $txn_id);
    render_caseos_error("Receipt Not Found", "No payment transaction was found for reference <code>" . htmlspecialchars($ref) . "</code>.", null, 404);
    exit;
}

$user_id = $txn['op_user_id'] ?: ($_SESSION['user_id'] ?? 0);
$user = get_data('op_user', $user_id)['data'] ?? [];

// Organization Details
$company_name = !empty($inst_name) ? $inst_name : "CaseOS Legal Operating System";
$company_entity = "OfferPlant Technologies Private Limited";
$company_address = !empty($inst_address) ? $inst_address : "2nd Floor, Godrej Building, Salempur, Chapra, Bihar - 841301, India";
$company_email = !empty($inst_email) ? $inst_email : "support@caseos.olaw.in";
$company_phone = !empty($inst_contact) ? $inst_contact : "+91 9431426600";
$company_gstin = !empty($inst_gst) ? $inst_gst : "10AAFCO3646Q1ZG";
$company_sac = "998313"; // Information Technology & Software Consulting

// Transaction Metadata
$amount = (float)($txn['amount'] ?? 0);
$currency = $txn['currency'] ?? 'INR';
$txn_date = !empty($txn['transaction_date']) ? strtotime($txn['transaction_date']) : time();
$formatted_date = date('d F, Y', $txn_date);
$formatted_time = date('h:i A (T)', $txn_date);
$invoice_number = "INV-" . date('Ym', $txn_date) . "-" . str_pad($txn['id'] ?? 1, 5, '0', STR_PAD_LEFT);
$receipt_number = "RCP-" . strtoupper(substr(md5($txn['payment_id'] ?? $invoice_number), 0, 8));
$payment_status = strtoupper($txn['status'] ?? 'SUCCESS');
$is_paid = in_array($payment_status, ['SUCCESS', 'PAID', 'CAPTURED']);

// Amount in Indian Currency Words
function amount_to_words_inr($number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $digits_1 = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 
                 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $digits_2 = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    if ($no == 0) {
        $result = 'Zero';
    } else {
        $num_str = (string)$no;
        $last3 = (int)substr($num_str, -3);
        $remaining = substr($num_str, 0, -3);
        
        $chunks = [];
        if ($remaining !== '' && $remaining !== false) {
            while (strlen($remaining) > 0) {
                if (strlen($remaining) >= 2) {
                    $chunks[] = (int)substr($remaining, -2);
                    $remaining = substr($remaining, 0, -2);
                } else {
                    $chunks[] = (int)$remaining;
                    $remaining = '';
                }
            }
        }
        
        $place_names = ['', 'Thousand', 'Lakh', 'Crore'];
        $parts = [];
        
        if ($last3 > 0) {
            $h = floor($last3 / 100);
            $rem = $last3 % 100;
            $h_str = '';
            if ($h > 0) {
                $h_str .= $digits_1[$h] . ' Hundred';
            }
            if ($rem > 0) {
                if ($h_str !== '') $h_str .= ' and ';
                if ($rem < 20) {
                    $h_str .= $digits_1[$rem];
                } else {
                    $h_str .= $digits_2[floor($rem / 10)] . ($rem % 10 > 0 ? ' ' . $digits_1[$rem % 10] : '');
                }
            }
            $parts[] = $h_str;
        }
        
        foreach ($chunks as $idx => $val) {
            if ($val > 0) {
                $unit = $place_names[$idx + 1] ?? '';
                $val_str = '';
                if ($val < 20) {
                    $val_str = $digits_1[$val];
                } else {
                    $val_str = $digits_2[floor($val / 10)] . ($val % 10 > 0 ? ' ' . $digits_1[$val % 10] : '');
                }
                array_unshift($parts, $val_str . ' ' . $unit);
            }
        }
        $result = implode(' ', $parts);
    }
    
    $paise_str = '';
    if ($point > 0) {
        $paise_val = (int)$point;
        if ($paise_val < 20) {
            $p_text = $digits_1[$paise_val];
        } else {
            $p_text = $digits_2[floor($paise_val / 10)] . ($paise_val % 10 > 0 ? ' ' . $digits_1[$paise_val % 10] : '');
        }
        $paise_str = ' and ' . $p_text . ' Paise';
    }
    
    return 'Rupees ' . trim($result) . $paise_str . ' Only';
}

$amount_words = amount_to_words_inr($amount);

// Verification URL & QR Code
$verify_url = $base_url . "system/recharge_receipt?payment_id=" . urlencode($txn['payment_id'] ?? '');
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=110x110&margin=0&data=" . urlencode($verify_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice #<?= htmlspecialchars($invoice_number) ?> - CaseOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --primary-accent: #2563eb;
            --success: #059669;
            --success-light: #ecfdf5;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-600: #475569;
            --slate-800: #1e293b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f1f5f9;
            color: var(--slate-800);
            line-height: 1.5;
            padding: 30px 15px;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
        }

        /* Top Action Toolbar */
        .action-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        .action-toolbar .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .btn-primary {
            background: var(--primary-accent);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-outline {
            background: #fff;
            color: var(--slate-800);
            border-color: var(--slate-200);
        }
        .btn-outline:hover {
            background: var(--slate-100);
            border-color: var(--slate-400);
        }

        /* Invoice Sheet */
        .invoice-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08), 0 0 1px 1px rgba(0, 0, 0, 0.04);
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        .invoice-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #059669 100%);
        }

        /* Header Section */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--slate-200);
            padding-bottom: 32px;
            margin-bottom: 32px;
            gap: 20px;
        }

        .brand-box h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .brand-box h1 span {
            color: var(--primary-accent);
        }
        .brand-meta {
            font-size: 12px;
            color: var(--slate-600);
            max-width: 380px;
            line-height: 1.6;
        }

        .invoice-badge-box {
            text-align: right;
        }
        .badge-type {
            display: inline-block;
            background: var(--slate-100);
            color: var(--slate-800);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid var(--slate-200);
        }
        .invoice-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }
        .invoice-number-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--primary-accent);
            font-weight: 600;
        }

        /* Meta Information Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
            margin-bottom: 36px;
        }
        .meta-col h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--slate-400);
            margin-bottom: 12px;
            border-bottom: 1px solid var(--slate-100);
            padding-bottom: 6px;
        }
        .client-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .client-info-line {
            font-size: 13px;
            color: var(--slate-600);
            line-height: 1.6;
        }

        .txn-meta-list {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 6px 12px;
            font-size: 13px;
        }
        .txn-meta-label {
            color: var(--slate-400);
            font-weight: 500;
        }
        .txn-meta-val {
            color: var(--slate-800);
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            word-break: break-all;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--success-light);
            color: var(--success);
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(5, 150, 105, 0.2);
        }
        .status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success);
        }

        /* Items Table */
        .table-wrap {
            margin-bottom: 28px;
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            overflow: hidden;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .invoice-table th {
            background: var(--slate-50);
            color: var(--slate-600);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--slate-200);
        }
        .invoice-table td {
            padding: 18px 16px;
            font-size: 13px;
            border-bottom: 1px solid var(--slate-100);
            vertical-align: top;
        }
        .invoice-table tr:last-child td {
            border-bottom: none;
        }
        .item-title {
            font-weight: 700;
            color: var(--primary);
            font-size: 14px;
            margin-bottom: 4px;
        }
        .item-desc {
            font-size: 12px;
            color: var(--slate-600);
            line-height: 1.5;
        }

        /* Totals & QR Section */
        .calculation-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 32px;
        }
        .words-and-qr {
            flex: 1;
            display: flex;
            gap: 20px;
            align-items: center;
            background: var(--slate-50);
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px dashed var(--slate-200);
        }
        .qr-box img {
            width: 84px;
            height: 84px;
            border-radius: 8px;
            border: 1px solid var(--slate-200);
            background: #fff;
            padding: 4px;
        }
        .words-content {
            font-size: 12px;
            color: var(--slate-600);
        }
        .words-content strong {
            display: block;
            color: var(--primary);
            font-size: 13px;
            margin-top: 2px;
            font-style: italic;
        }

        .totals-box {
            width: 320px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            color: var(--slate-600);
        }
        .totals-row.grand-total {
            border-top: 2px solid var(--slate-800);
            margin-top: 10px;
            padding-top: 12px;
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }
        .totals-row.grand-total .amount-highlight {
            color: var(--primary-accent);
            font-size: 22px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Seal & Signatory */
        .seal-sign-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 28px;
            border-top: 1px solid var(--slate-200);
            margin-top: 24px;
        }
        .digital-stamp {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .stamp-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--success);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
        }
        .stamp-text {
            font-size: 11px;
            color: var(--slate-600);
            line-height: 1.4;
        }
        .stamp-text strong {
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signatory-box {
            text-align: right;
            font-size: 12px;
            color: var(--slate-600);
        }
        .signatory-box .company {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 30px;
        }
        .signatory-box .auth-sign {
            font-weight: 600;
            color: var(--slate-800);
            border-top: 1px solid var(--slate-400);
            padding-top: 4px;
            display: inline-block;
            min-width: 180px;
        }

        /* Disclaimer Footer */
        .invoice-footer {
            margin-top: 36px;
            text-align: center;
            font-size: 11px;
            color: var(--slate-400);
            line-height: 1.6;
        }
        .invoice-footer a {
            color: var(--primary-accent);
            text-decoration: none;
        }

        /* Toast notification */
        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 9999;
        }
        #toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Print Optimization */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000 !important;
            }
            .action-toolbar, #toast {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                border-radius: 0 !important;
                padding: 30px !important;
            }
            .invoice-card::before {
                display: none !important;
            }
            @page {
                margin: 12mm;
                size: A4 portrait;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- Action Toolbar -->
        <div class="action-toolbar">
            <div class="btn-group">
                <a href="<?= $base_url ?>public/recharge_receipts_list" class="btn btn-outline">
                    &larr; Statements & History
                </a>
                <a href="<?= $base_url ?>public/recharge" class="btn btn-outline">
                    + New Recharge
                </a>
            </div>
            <div class="btn-group">
                <button onclick="copyInvoiceLink()" class="btn btn-outline" id="copyBtn">
                    Share / Copy Link
                </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Print / Save as PDF
                </button>
            </div>
        </div>

        <!-- Main Invoice Sheet -->
        <div class="invoice-card">
            
            <!-- Header -->
            <div class="header-section">
                <div class="brand-box">
                    <h1>Case<span>OS</span></h1>
                    <div class="brand-meta">
                        <strong><?= htmlspecialchars($company_entity) ?></strong><br>
                        <?= htmlspecialchars($company_address) ?><br>
                        GSTIN: <strong><?= htmlspecialchars($company_gstin) ?></strong> &bull; SAC: <strong><?= htmlspecialchars($company_sac) ?></strong><br>
                        Support: <?= htmlspecialchars($company_email) ?> | Helpline: <?= htmlspecialchars($company_phone) ?>
                    </div>
                </div>

                <div class="invoice-badge-box">
                    <span class="badge-type">ORIGINAL TAX INVOICE</span>
                    <h2 class="invoice-title">RECEIPT</h2>
                    <div class="invoice-number-tag"><?= htmlspecialchars($invoice_number) ?></div>
                </div>
            </div>

            <!-- Metadata Information -->
            <div class="meta-grid">
                
                <!-- Client Details -->
                <div class="meta-col">
                    <h3>Billed To (Client / Advocate)</h3>
                    <div class="client-name"><?= htmlspecialchars($user['full_name'] ?: ($user['user_name'] ?? 'Authorized User')) ?></div>
                    <div class="client-info-line">
                        <strong>User ID:</strong> <?= htmlspecialchars($user['user_name'] ?? ('USR-' . $user_id)) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($user['user_email'] ?? 'Registered Account') ?><br>
                        <strong>Mobile:</strong> <?= htmlspecialchars($user['user_mobile'] ?? 'Verified') ?><br>
                        <strong>Account Role:</strong> <?= htmlspecialchars($user['user_type'] ?? 'ADVOCATE') ?>
                    </div>
                </div>

                <!-- Invoice Meta -->
                <div class="meta-col">
                    <h3>Transaction Summary</h3>
                    <div class="txn-meta-list">
                        <span class="txn-meta-label">Invoice Date:</span>
                        <span class="txn-meta-val"><?= htmlspecialchars($formatted_date) ?></span>

                        <span class="txn-meta-label">Issue Time:</span>
                        <span class="txn-meta-val"><?= htmlspecialchars($formatted_time) ?></span>

                        <span class="txn-meta-label">Payment ID:</span>
                        <span class="txn-meta-val"><?= htmlspecialchars($txn['payment_id'] ?? '—') ?></span>

                        <span class="txn-meta-label">Order Ref:</span>
                        <span class="txn-meta-val"><?= htmlspecialchars($txn['order_id'] ?? '—') ?></span>

                        <span class="txn-meta-label">Payment Mode:</span>
                        <span class="txn-meta-val"><?= htmlspecialchars($txn['payment_method'] ?? 'Razorpay Gateway') ?></span>

                        <span class="txn-meta-label">Status:</span>
                        <div>
                            <span class="status-badge">
                                <span class="dot"></span>
                                <?= htmlspecialchars($payment_status) ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Itemized Table -->
            <div class="table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Description of Legal Software Services</th>
                            <th style="width: 90px;">SAC</th>
                            <th style="width: 60px; text-align: center;">Qty</th>
                            <th style="width: 120px; text-align: right;">Rate (₹)</th>
                            <th style="width: 130px; text-align: right;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="color: var(--slate-400); font-weight: 600;">01</td>
                            <td>
                                <div class="item-title">CaseOS Cloud Platform Wallet Credit</div>
                                <div class="item-desc">
                                    Prepaid credits top-up for automated case hearing alerts, cause lists synchronization, legal document storage, and e-Courts client communications.
                                </div>
                            </td>
                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--slate-600);"><?= htmlspecialchars($company_sac) ?></td>
                            <td style="text-align: center; font-weight: 600;">1</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;"><?= number_format($amount, 2) ?></td>
                            <td style="text-align: right; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: var(--primary);">₹<?= number_format($amount, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Calculation and QR Section -->
            <div class="calculation-section">
                
                <div class="words-and-qr">
                    <div class="qr-box">
                        <img src="<?= htmlspecialchars($qr_code_url) ?>" alt="Verification QR Code">
                    </div>
                    <div class="words-content">
                        <span>Invoice Amount in Words:</span>
                        <strong><?= htmlspecialchars($amount_words) ?></strong>
                        <div style="font-size: 11px; color: var(--slate-400); margin-top: 6px;">
                            Scan QR code to verify this transaction digitally on the CaseOS portal.
                        </div>
                    </div>
                </div>

                <div class="totals-box">
                    <div class="totals-row">
                        <span>Taxable Amount</span>
                        <span style="font-family: 'JetBrains Mono', monospace;">₹<?= number_format($amount, 2) ?></span>
                    </div>
                    <div class="totals-row">
                        <span>CGST (0% - Included)</span>
                        <span style="font-family: 'JetBrains Mono', monospace;">₹0.00</span>
                    </div>
                    <div class="totals-row">
                        <span>SGST / IGST (0% - Included)</span>
                        <span style="font-family: 'JetBrains Mono', monospace;">₹0.00</span>
                    </div>
                    <div class="totals-row grand-total">
                        <span>Total Paid</span>
                        <span class="amount-highlight">₹<?= number_format($amount, 2) ?></span>
                    </div>
                </div>

            </div>

            <!-- Digital Seal & Signatory -->
            <div class="seal-sign-section">
                <div class="digital-stamp">
                    <div class="stamp-badge">&check;</div>
                    <div class="stamp-text">
                        <strong>Digitally Verified</strong><br>
                        Certified Electronic Transaction Record<br>
                        Authentication Ref: <?= htmlspecialchars($receipt_number) ?>
                    </div>
                </div>

                <div class="signatory-box">
                    <div class="company">For <?= htmlspecialchars($company_entity) ?></div>
                    <div class="auth-sign">Authorized Signatory</div>
                </div>
            </div>

            <!-- Legal Disclaimer Footer -->
            <div class="invoice-footer">
                This is a computer-generated official tax invoice under Section 65B(4) of the Indian Evidence Act and Information Technology Act, 2000. It requires no physical signature.<br>
                For any inquiries or dispute resolutions, please write to <a href="mailto:<?= htmlspecialchars($company_email) ?>"><?= htmlspecialchars($company_email) ?></a> or visit <a href="<?= htmlspecialchars($base_url) ?>"><?= htmlspecialchars($base_url) ?></a>.
            </div>

        </div>

    </div>

    <div id="toast">Invoice link copied to clipboard!</div>

    <script>
        function copyInvoiceLink() {
            var url = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    showToast();
                });
            } else {
                var temp = document.createElement('input');
                document.body.appendChild(temp);
                temp.value = url;
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                showToast();
            }
        }

        function showToast() {
            var t = document.getElementById('toast');
            t.className = "show";
            setTimeout(function() { t.className = t.className.replace("show", ""); }, 2500);
        }
    </script>
</body>
</html>
