<?php require_once("../system/all_header.php"); 

        $table_name = "online_payment";
        $userdata = get_data('op_user', $user_id)['data'];
        if (isset($_GET["link"]) and $_GET["link"] != "") {
            $branch = decode($_GET["link"]);
            $id = $branch["id"];
            $isedit ="yes";
        } else {
            $demand_id = date('ymdhis').rand(1000,9999);
            $branch = insert_row($table_name);
            $id = $branch["id"];
            update_data($table_name,['demand_id'=>$demand_id],$id);
            $isedit ="no";
        }
        
        if ($id != "") {
            $res = get_data($table_name, $id);
            if ($res["count"] > 0 and $res["status"] == "success") {
                extract($res["data"]);
            }
        }
        ?>
<style>
    .recharge-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        background: #fff;
    }
    .input-group-text {
        background: transparent;
        border-right: none;
        color: #6c757d;
    }
    .form-control {
        border-left: none;
        padding: 12px;
    }
    .btn-amount {
        border: 2px solid #e9ecef;
        background: #fff;
        padding: 10px 15px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        margin-right: 10px;
        font-weight: 600;
        color: #495057;
    }
    .btn-amount:hover {
        border-color: #384350;
        background: #f8f9fa;
    }
    .btn-amount.active {
        background: #384350;
        color: #fff;
        border-color: #384350;
    }
    .pay-btn {
        background: #384350;
        border: none;
        padding: 15px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.1rem;
        transition: transform 0.2s;
    }
    .pay-btn:hover {
        transform: translateY(-2px);
        background: #2c343f;
    }
</style>

<main class="content">
    <div class="container-fluid p-0">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-2">Wallet Recharge</h1>
                    <p class="text-muted">Top up your wallet safely via Razorpay</p>
                    <a href="recharge_receipts_list" class="btn btn-link btn-sm text-decoration-none">
                        <i class="feather-sm me-1" data-feather="clock"></i> View Recharge History
                    </a>
                </div>
                
                <div class="card recharge-card">
                    <div class="card-body p-4">
                        <form method="POST" action="../ppay/razorpay_pay.php" id="rechargeForm">
                            <input name="demand_id" type="hidden" value='<?= $demand_id ?>'>
                            
                            <!-- User Details (Read-only) -->
                            <div class="mb-4">
                                <label class="form-label small text-uppercase font-weight-bold">User Information</label>
                                <div class="p-3 bg-light rounded-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa fa-user text-muted me-3"></i>
                                        <span class="fw-bold"><?= $userdata['full_name'] ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-envelope text-muted me-3"></i>
                                        <span class="text-sm"><?= $userdata['user_email'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount Selection -->
                            <div class="mb-4">
                                <label class="form-label small text-uppercase font-weight-bold">Recharge Amount (₹)</label>
                                <div class="d-flex mb-3">
                                    <div class="btn-amount" onclick="setAmount(100)">100</div>
                                    <div class="btn-amount" onclick="setAmount(500)">500</div>
                                    <div class="btn-amount" onclick="setAmount(1000)">1000</div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa fa-indian-rupee-sign"></i></span>
                                    <input name="amount" id="amountInput" class="form-control form-control-lg" placeholder="Enter custom amount" type="number" min="1" value="100" required>
                                </div>
                            </div>

                            <input name="name" type="hidden" value='<?= $userdata['full_name'] ?>'>
                            <input name="email" type="hidden" value='<?= $userdata['user_email'] ?>'>
                            <input name="mobile" type="hidden" value='<?= $userdata['user_mobile'] ?>'>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary pay-btn">
                                    <i class="fa fa-lock me-2"></i> Proceed to Pay
                                </button>
                            </div>
                            
                            <div class="text-center mt-4">
                                <img src="https://razorpay.com/assets/razorpay-glyph-bold.svg" height="20" class="opacity-50">
                                <span class="ms-2 small text-muted">Secured by Razorpay</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function setAmount(amt) {
        document.getElementById('amountInput').value = amt;
        // Visual feedback for selected button
        const buttons = document.querySelectorAll('.btn-amount');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
    }
</script>
        
        <?php 
        require_once("../system/footer.php"); ?>