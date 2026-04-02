<?php
require_once("all_header.php");
$table_name = "payment_transactions";
$user_id = $_SESSION['user_id'];

// Fetch all success transactions for this user
$res = get_all($table_name, '*', ['op_user_id' => $user_id, 'status' => 'success']);
?>
<main class="content">
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3">Online Recharge Receipts</h1>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Your Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover my-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment ID</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($res['count'] > 0): ?>
                                        <?php foreach($res['data'] as $txn): ?>
                                            <tr>
                                                <td><?= date('d M Y, h:i A', strtotime($txn['transaction_date'])) ?></td>
                                                <td><span class="badge bg-light text-dark"><?= $txn['payment_id'] ?></span></td>
                                                <td><strong>₹<?= number_format($txn['amount'], 2) ?></strong></td>
                                                <td><?= $txn['payment_method'] ?: 'Online' ?></td>
                                                <td>
                                                                                                        <a href="<?= $base_url ?>system/recharge_receipt?payment_id=<?= $txn['payment_id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                       View Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No successful recharge found.</td>
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
<?php require_once("footer.php"); ?>
