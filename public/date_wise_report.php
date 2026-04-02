<?php require_once("../system/all_header.php"); 
$user_id = $_SESSION['user_id'];
?>

            <main class="content">
                <div class="container-fluid p-0">
        
                    <h1 class="h3 mb-3" > Date Wise Report </h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                
                                <div class="card-body">
                                    <div class="card-header">
                                        <h5> Task Report </h5>
                                     <div class="row">
                    					<div class="col-lg-4">
                                		<form action ='task_print.php' method='post' target='_blank'>    
                    				    	<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                    					</div>	
                    					<div class="col-lg-4">
                    					    <input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='todate' required>
                    					</div>
                    					<div class="col-lg-4">
                    						<input type='submit' class='btn btn-info btn-sm' value=' View and Print '  class='form-control' name='submit'>
                    					</form>
                    					</div>
                                    </div>
                                    </div>
                                 <div class="card-header">
                                        <h5> Task Report (Refernce Person) </h5>
                    				<div class="row">
                    					<div class="col-lg-3">
                                		<form action ='print_by_ref.php' method='post' target='_blank'>    
                    						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                    					</div>	
                    					<div class="col-lg-3">
                    						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='todate' required>
                    					</div>
                    					<div class="col-lg-2">
                    					<select name='ref_id' required class='form-control'>
                    						<?php echo selectref($user_id); ?>
                    					</select>
                    					</div> 
                    					<div class="col-lg-2">
                    						<input type='submit' class='btn btn-info btn-sm' value=' View and Print'  class='form-control' name='submit'>
                    					</form>
                    					</div>
                    				</div>
                                    </div>
				                <div class="card-header">
                                        <h5> Old Task Report </h5>
				                <div class="row">
                    					<div class="col-lg-4">
                                		<form action ='old_print.php' method='post' target='_blank'>    
                    						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                    					</div>	
                    					<div class="col-lg-4">
                    					    <input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='todate' required>
                    					</div>
                    					<div class="col-lg-4">
                    						<input type='submit' class='btn btn-info btn-sm' value='View and Print '  class='form-control' name='submit'>
                    					</form>
                    					</div>
                    			</div>
                    			</div>
                                <div class="card-header">
                                        <h5> Future Task Count (Date Wise)</h5>
                    					<div class="row">
                            					<div class="col-lg-8">
                                        		<form action ='future_task.php' method='post' target='_blank'>    
                            					
                            						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                            					</div>	
                            					
                            					<div class="col-lg-4">
                            						<input type='submit' class='btn btn-info btn-sm' value='View and Print'  class='form-control' name='submit'>
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