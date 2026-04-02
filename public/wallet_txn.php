<?php 
require_once("../system/all_header.php"); 
        $table_name = "wallet";
        $user_id  =  (isset($_GET['user_id']))?$_GET['user_id'] :$_SESSION['user_id'];
        $userdata = get_data('op_user', $user_id)['data'];
        $res = get_all($table_name,'*',['user_id'=>$user_id]);
        ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3" >Wallet Statment</h1>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> 
                                     <?= $userdata['full_name'] ?>
                                     <span class='badge bg-success float-end'> ₹ <?= $userdata['wallet'] ?> </span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                  <?= create_data_table($table_name, $res); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
             <!-- The Modal -->
    
<?php require_once("../system/footer.php"); ?>
   