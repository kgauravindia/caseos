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
     .input-group-text{
         padding:10px;
     }
 </style>      
            <main class="content">
                <div class="container-fluid p-0">
        
                    <h1 class="h3 mb-3" >Add to Wallet </h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> Online Payment
                                  
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class='row'>
                                    <div class='col-md-4'>
                                        
                                   
                                   	<form method="POST" action="../ppay/pay.php" target="_blank">
                                   	    <input name="demand_id"  type="hidden" value='<?= $demand_id ?>' readonly >
                        				<div class="form-group input-group">
                        					<div class="input-group-prepend">
                        						<span class="input-group-text"> <i class="fa fa-user"></i> </span>
                        					</div>
                        					
                        					<input name="name" class="form-control" placeholder="Name" type="text" value='<?= $userdata['full_name'] ?>' readonly >
                        				</div> <!-- form-group// -->
                        				<div class="form-group input-group">
                        					<div class="input-group-prepend">
                        						<span class="input-group-text"> <i class="fa fa-envelope"></i> </span>
                        					</div>
                        					<input name="email" class="form-control" placeholder="Email address" type="email" value='<?= $userdata['user_email'] ?>' readonly>
                        				</div> <!-- form-group// -->
                        				<div class="form-group input-group">
                        					<div class="input-group-prepend">
                        						<span class="input-group-text"> <i class="fa fa-phone"></i> </span>
                        					</div>
                        
                        					<input name="mobile" class="form-control" placeholder="Phone number" type="text" value='<?= $userdata['user_mobile'] ?>' readonly>
                        				</div> <!-- form-group// -->
                        
                        				<div class="form-group input-group">
                        					<div class="input-group-prepend">
                        						<span class="input-group-text"> <i class="fa fa-lock"></i> </span>
                        					</div>
                        					<input name="amount" class="form-control" placeholder="Create password" type="number" value="10">
                        				</div> <!-- form-group// -->
                        
                        				<div class="form-group">
                        					<button type="submit" class="btn btn-primary btn-block"> Pay Now </button>
                        				</div> <!-- form-group// -->
                        
                        			</form>
                        			 </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        
                </div>
            </main>
        
        <?php 
        require_once("../system/footer.php"); ?>