<?php
require_once("../system/all_header.php");
$table_name = "payment_transactions";
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch all success transactions for this user
$res = get_all($table_name, '*', ['op_user_id' => $user_id, 'status' => 'success'], 'id DESC');

$total_recharged = 0;
$total_count = $res['count'] ?? 0;
if ($total_count > 0 && !empty($res['data'])) {
    foreach ($res['data'] as $t) {
        $total_recharged += (float)($t['amount'] ?? 0);
    }
}
$current_wallet = get_data('op_user', $user_id, 'wallet')['data'] ?? 0;
?>

<style>
    .metric-card {
        background: #fff;
        border-radius: 1rem;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        padding: 1.25rem;
        transition: transform 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
    }
    .metric-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .table-card {
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        background: #fff;
        overflow: hidden;
    }
    .btn-receipt {
        background: #f1f5f9;
        color: #1e293b;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        padding: 0.35rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .btn-receipt:hover {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }
</style>

<main class="content">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1 text-dark fw-bold">
                    <i class="align-middle me-2 text-primary" data-feather="receipt"></i>Recharge Receipts & History
                </h1>
                <p class="text-muted small mb-0">Track all online wallet transactions and download official payment receipts.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="recharge" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="align-middle me-1" data-feather="plus-circle" style="width: 14px; height: 14px;"></i> New Recharge
                </a>
            </div>
        </div>

        <!-- Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="metric-card">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i data-feather="dollar-sign" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Current Wallet Balance</span>
                            <h3 class="fw-bold mb-0 text-dark">₹<?= number_format((float)$current_wallet, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="metric-card">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-success bg-opacity-10 text-success me-3">
                            <i data-feather="check-circle" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Lifetime Recharged</span>
                            <h3 class="fw-bold mb-0 text-success">₹<?= number_format((float)$total_recharged, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="metric-card">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon-box bg-info bg-opacity-10 text-info me-3">
                            <i data-feather="layers" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Successful Transactions</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= (int)$total_count ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table Card -->
        <div class="row">
            <div class="col-12">
                <div class="table-card">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="align-middle me-2 text-primary" data-feather="list"></i>Statement of Account
                        </h5>
                        <span class="badge bg-light text-muted border"><?= (int)$total_count ?> records found</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase fw-bold">
                                    <tr>
                                        <th class="ps-4">Date & Time</th>
                                        <th>Transaction / Payment ID</th>
                                        <th>Order ID</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Receipt / Bill</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($total_count > 0 && !empty($res['data'])): ?>
                                        <?php foreach ($res['data'] as $txn): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark small"><?= !empty($txn['transaction_date']) ? date('d M Y', strtotime($txn['transaction_date'])) : date('d M Y') ?></span>
                                                        <span class="text-muted" style="font-size: 0.7rem;"><?= !empty($txn['transaction_date']) ? date('h:i A', strtotime($txn['transaction_date'])) : '' ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-primary border font-monospace px-2 py-1">
                                                        <?= htmlspecialchars($txn['payment_id'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="small font-monospace text-muted">
                                                        <?= htmlspecialchars($txn['order_id'] ?? '—') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-success fs-6">₹<?= number_format((float)($txn['amount'] ?? 0), 2) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= htmlspecialchars($txn['payment_method'] ?: 'Razorpay') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1">
                                                        <i class="align-middle me-1" data-feather="check" style="width: 10px; height: 10px;"></i> Success
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="<?= $base_url ?>system/recharge_receipt?payment_id=<?= urlencode($txn['payment_id'] ?? '') ?>" 
                                                       class="btn btn-receipt" target="_blank" title="View & Download Payment Receipt">
                                                        <i class="align-middle me-1" data-feather="file-text" style="width: 12px; height: 12px;"></i> View Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center opacity-75">
                                                    <div class="bg-light p-3 rounded-circle mb-3">
                                                        <i data-feather="inbox" style="width: 32px; height: 32px;" class="text-muted"></i>
                                                    </div>
                                                    <h6 class="fw-bold text-dark mb-1">No Recharge History Yet</h6>
                                                    <p class="text-muted small mb-3">Your wallet recharge receipts will appear here once you top up.</p>
                                                    <a href="recharge" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                                        Recharge Now
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once("../system/footer.php"); ?>
