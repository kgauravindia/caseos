<?php
require_once("../system/all_header.php");
$table_name = "payment_transactions";
$user_id = $_SESSION['user_id'];

// Fetch all success transactions for this user
$res = get_all($table_name, '*', ['op_user_id' => $user_id, 'status' => 'success']);
?>
<main class="content">
    <div class="container-fluid p-0">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-primary fw-bold">Online Recharge Receipts</h1>
                <p class="text-muted small mb-0">Manage and download your payment history</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>">Home</a></li>
                    <li class="breadcrumb-item active">Recharge Receipts</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <div class="d-flex align-items-center">
                            <i class="feather-sm text-primary me-2" data-feather="credit-card"></i>
                            <h5 class="card-title mb-0 fw-bold">Transaction History</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase fw-bold">
                                    <tr>
                                        <th class="ps-4">Date & Time</th>
                                        <th>Transaction ID</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($res['count'] > 0): ?>
                                        <?php foreach($res['data'] as $txn): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-medium text-dark"><?= date('d M Y', strtotime($txn['transaction_date'])) ?></span>
                                                        <span class="text-muted extra-small"><?= date('h:i A', strtotime($txn['transaction_date'])) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-primary border border-primary border-opacity-10 px-2 py-1">
                                                        <?= $txn['payment_id'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">₹<?= number_format($txn['amount'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="dot bg-success me-2"></span>
                                                        <span class="text-dark small"><?= $txn['payment_method'] ?: 'Online' ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="<?= $base_url ?>system/recharge_receipt?payment_id=<?= $txn['payment_id'] ?>" 
                                                       class="btn btn-sm btn-premium-primary rounded-pill px-3 shadow-none" target="_blank">
                                                       <i class="feather-sm me-1" data-feather="file-text"></i> View Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center opacity-50">
                                                    <i class="feather-lg mb-3" data-feather="alert-circle"></i>
                                                    <p class="mb-0">No successful recharge transactions found.</p>
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

<style>
.extra-small { font-size: 0.75rem; }
.dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.btn-premium-primary {
    background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}
.btn-premium-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    color: white;
}
</style>

<?php require_once("../system/footer.php"); ?>
