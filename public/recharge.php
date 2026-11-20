<?php 
require_once("../system/all_header.php"); 

$table_name = "online_payment";
$userdata = get_data('op_user', $user_id)['data'] ?? [];
$current_wallet = get_data('op_user', $user_id, 'wallet')['data'] ?? 0;

if (isset($_GET["link"]) && $_GET["link"] != "") {
    $branch = decode($_GET["link"]);
    $id = $branch["id"];
    $isedit = "yes";
} else {
    $demand_id = date('ymdhis') . rand(1000, 9999);
    $branch = insert_row($table_name);
    $id = $branch["id"] ?? 0;
    if ($id) {
        update_data($table_name, ['demand_id' => $demand_id], $id);
    }
    $isedit = "no";
}

if ($id) {
    $res = get_data($table_name, $id);
    if (($res["count"] ?? 0) > 0 && ($res["status"] ?? '') == "success") {
        extract($res["data"]);
    }
}
?>

<style>
    .wallet-hero-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 1.25rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        padding: 1.75rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.25);
    }
    .wallet-hero-card::after {
        content: "";
        position: absolute;
        top: -40px;
        right: -40px;
        width: 160px;
        height: 160px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .recharge-card {
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        background: #fff;
    }
    .plan-card {
        border: 2px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.25s ease;
        position: relative;
        text-align: center;
    }
    .plan-card:hover {
        border-color: #3b82f6;
        background: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.12);
    }
    .plan-card.active {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18);
    }
    .plan-card.active .plan-amount {
        color: #1d4ed8;
    }
    .plan-badge {
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
    }
    .plan-amount {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
    }
    .plan-desc {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 500;
    }
    .custom-amount-group .input-group-text {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-right: none;
        color: #475569;
        font-weight: 700;
        border-top-left-radius: 0.75rem;
        border-bottom-left-radius: 0.75rem;
        padding: 0.75rem 1rem;
    }
    .custom-amount-group .form-control {
        border: 1px solid #cbd5e1;
        border-left: none;
        border-top-right-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
        font-weight: 700;
        font-size: 1.25rem;
        color: #0f172a;
        padding: 0.75rem 1rem;
    }
    .custom-amount-group .form-control:focus {
        border-color: #3b82f6;
        box-shadow: none;
    }
    .pay-btn-modern {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        border: none;
        padding: 0.9rem;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 1.05rem;
        letter-spacing: 0.3px;
        transition: all 0.25s ease;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }
    .pay-btn-modern:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-2px);
        box-shadow: 0 14px 25px rgba(37, 99, 235, 0.4);
        color: #fff;
    }
    .payment-pill {
        display: inline-flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 4px 10px;
        font-size: 0.72rem;
        font-weight: 600;
        color: #475569;
    }
</style>

<main class="content">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-dark fw-bold">
                    <i class="align-middle me-2 text-primary" data-feather="credit-card"></i>Wallet Recharge
                </h1>
                <p class="text-muted small mb-0">Top up your CaseOS account balance instantly via secure online payment.</p>
            </div>
            <a href="recharge_receipts_list" class="btn btn-outline-primary btn-sm shadow-sm rounded-pill px-3">
                <i class="align-middle me-1" data-feather="clock" style="width: 14px; height: 14px;"></i> Payment History
            </a>
        </div>

        <div class="row justify-content-center g-4">
            <!-- Left Column: Recharge Form -->
            <div class="col-lg-7 col-xl-6">
                <!-- Current Balance Card -->
                <div class="wallet-hero-card mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 1px;">Current Available Balance</span>
                            <h2 class="display-6 fw-bold mb-0 text-white mt-1">₹<?= number_format((float)$current_wallet, 2) ?></h2>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill small">
                                <i class="align-middle me-1" data-feather="shield" style="width: 14px; height: 14px;"></i> Active Wallet
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Main Recharge Card -->
                <div class="card recharge-card">
                    <div class="card-body p-4 p-md-4">
                        <form method="POST" action="../ppay/razorpay_pay.php" id="rechargeForm">
                            <input name="demand_id" type="hidden" value="<?= htmlspecialchars($demand_id) ?>">

                            <!-- Select Preset Amount -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small text-uppercase" style="letter-spacing: 0.5px;">Select Amount to Add</label>
                                <div class="row g-2">
                                    <div class="col-6 col-sm-3">
                                        <div class="plan-card" onclick="selectPlan(500, this)">
                                            <div class="plan-amount">₹500</div>
                                            <div class="plan-desc">Basic Top-up</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="plan-card active" onclick="selectPlan(1000, this)">
                                            <span class="plan-badge">Popular</span>
                                            <div class="plan-amount">₹1,000</div>
                                            <div class="plan-desc">Advocate Plan</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="plan-card" onclick="selectPlan(2500, this)">
                                            <div class="plan-amount">₹2,500</div>
                                            <div class="plan-desc">Professional</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="plan-card" onclick="selectPlan(5000, this)">
                                            <span class="plan-badge" style="background: linear-gradient(135deg, #10b981, #059669);">Value</span>
                                            <div class="plan-amount">₹5,000</div>
                                            <div class="plan-desc">Law Firm</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Custom Amount Input -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-muted small">Or Enter Custom Amount (₹)</label>
                                <div class="input-group custom-amount-group">
                                    <span class="input-group-text">₹</span>
                                    <input name="amount" id="amountInput" class="form-control" placeholder="Enter amount" type="number" min="10" max="100000" value="1000" oninput="updateSummary()" required>
                                </div>
                                <div class="form-text small text-muted">Minimum recharge: ₹10 &bull; Instant wallet credit upon payment completion.</div>
                            </div>

                            <!-- User Info Summary -->
                            <div class="mb-4 p-3 bg-light bg-opacity-75 rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Billing Name:</span>
                                    <span class="fw-bold text-dark small"><?= htmlspecialchars($userdata['full_name'] ?? 'User') ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Email Address:</span>
                                    <span class="text-dark small"><?= htmlspecialchars($userdata['user_email'] ?? '') ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Mobile:</span>
                                    <span class="text-dark small"><?= htmlspecialchars($userdata['user_mobile'] ?? '') ?></span>
                                </div>
                            </div>

                            <input name="name" type="hidden" value="<?= htmlspecialchars($userdata['full_name'] ?? '') ?>">
                            <input name="email" type="hidden" value="<?= htmlspecialchars($userdata['user_email'] ?? '') ?>">
                            <input name="mobile" type="hidden" value="<?= htmlspecialchars($userdata['user_mobile'] ?? '') ?>">

                            <!-- Order Summary Box -->
                            <div class="mb-4 p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Recharge Amount</span>
                                    <span class="fw-semibold text-dark" id="summaryBase">₹1,000.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Processing / Platform Fee</span>
                                    <span class="badge bg-success bg-opacity-15 text-success font-monospace">FREE (₹0.00)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <strong class="text-dark">Total Payable Amount</strong>
                                    <strong class="text-primary fs-5" id="summaryTotal">₹1,000.00</strong>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn pay-btn-modern d-flex align-items-center justify-content-center">
                                    <i class="align-middle me-2" data-feather="lock" style="width: 18px; height: 18px;"></i>
                                    <span id="btnPayText">Proceed to Pay ₹1,000.00</span>
                                </button>
                            </div>

                            <!-- Accepted Payment Methods -->
                            <div class="text-center pt-2">
                                <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                                    <span class="payment-pill"><i class="align-middle me-1 text-primary" data-feather="check"></i> UPI (GPay/PhonePe)</span>
                                    <span class="payment-pill"><i class="align-middle me-1 text-primary" data-feather="check"></i> Credit/Debit Cards</span>
                                    <span class="payment-pill"><i class="align-middle me-1 text-primary" data-feather="check"></i> NetBanking</span>
                                    <span class="payment-pill"><i class="align-middle me-1 text-primary" data-feather="check"></i> Wallets</span>
                                </div>
                                <div class="small text-muted d-flex align-items-center justify-content-center gap-2">
                                    <img src="https://razorpay.com/assets/razorpay-glyph-bold.svg" height="16" class="opacity-75" alt="Razorpay">
                                    <span>256-Bit SSL Bank-Grade Encryption</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Wallet FAQ & Benefits -->
            <div class="col-lg-5 col-xl-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="align-middle me-2 text-primary" data-feather="zap"></i>Wallet Advantages
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="d-flex mb-3">
                                <i class="align-middle text-success me-2 mt-1" data-feather="check-circle" style="width: 16px; height: 16px;"></i>
                                <div>
                                    <strong class="d-block text-dark">Automated Notifications</strong>
                                    Seamlessly send automated client case alerts via SMS, Email, and WhatsApp.
                                </div>
                            </li>
                            <li class="d-flex mb-3">
                                <i class="align-middle text-success me-2 mt-1" data-feather="check-circle" style="width: 16px; height: 16px;"></i>
                                <div>
                                    <strong class="d-block text-dark">Zero Interruption</strong>
                                    Keep your daily cause-list sync and case updates running continuously without pause.
                                </div>
                            </li>
                            <li class="d-flex mb-0">
                                <i class="align-middle text-success me-2 mt-1" data-feather="check-circle" style="width: 16px; height: 16px;"></i>
                                <div>
                                    <strong class="d-block text-dark">Instant GST Invoices</strong>
                                    Download official recharge receipts and tax invoices immediately after every transaction.
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <div class="small text-muted mb-2">Need billing assistance or custom plans?</div>
                        <a href="https://wa.me/+91<?= $dev_contact ?>?text=Hello%2C%20I%20need%20help%20with%20CaseOS%20Wallet%20Recharge" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100">
                            <i class="align-middle me-1" data-feather="message-circle" style="width: 14px; height: 14px;"></i> WhatsApp Support
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    function selectPlan(amt, el) {
        document.getElementById('amountInput').value = amt;
        document.querySelectorAll('.plan-card').forEach(function(card) {
            card.classList.remove('active');
        });
        if (el) {
            el.classList.add('active');
        }
        updateSummary();
    }

    function updateSummary() {
        var rawVal = parseFloat(document.getElementById('amountInput').value) || 0;
        var formatted = '₹' + rawVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryBase').innerText = formatted;
        document.getElementById('summaryTotal').innerText = formatted;
        document.getElementById('btnPayText').innerText = 'Proceed to Pay ' + formatted;

        // Check if value matches any preset
        document.querySelectorAll('.plan-card').forEach(function(card) {
            var cardAmt = parseFloat(card.querySelector('.plan-amount').innerText.replace(/[^0-9]/g, ''));
            if (cardAmt === rawVal) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateSummary();
    });
</script>

<?php require_once("../system/footer.php"); ?>
