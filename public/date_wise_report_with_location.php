<?php require_once("../system/all_header.php"); ?>

            <main class="content">
                <div class="container-fluid p-0">
        
                    <h1 class="h3 mb-3" > Date Wise Report With Location</h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                
                                <div class="card-body">
                                    <div class="card-header">
                                        <h5> Task Report </h5>
                                     <div class="row">
                    					<div class="col-lg-2">
                                		<form action ='task_print_with_location.php' method='post' target='_blank'>    
                    				    	<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                    					</div>	
                    					<div class="col-lg-2">
                    					    <input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='todate' required>
                    					</div>
                    					<div class="col-lg-2">
                    					<select name='court_location' required class='form-control'>
                    						<?php echo dropdown_list('court_location','id','name' ); ?>
                    					</select>
                    					</div> 
                    					<div class="col-lg-2">
                                          <input type="color" id="head" name="color_back" value="#000066" />
                                        </div>
                                       	<div class="col-lg-2">
                    						<input type='submit' class='btn btn-info btn-sm' value=' View and Print '  class='form-control' name='submit'>
                    					</form>
                    					</div>
                                    </div>
                                    </div>
                                 <div class="card-header">
                                        <h5> Task Report (Refernce Person) </h5>
                    				<div class="row">
                    					<div class="col-lg-2">
                                		<form action ='print_by_ref_with_location.php' method='post' target='_blank'>    
                    						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                    					</div>	
                    					<div class="col-lg-2">
                    						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='todate' required>
                    					</div>
                    					<div class="col-lg-2">
                    					<select name='court_location' required class='form-control'>
                    						<?php echo dropdown_list('court_location','id','name' ); ?>
                    					</select>
                    					</div> 
                    					<div class="col-lg-1">
                                          <input type="color" id="head" name="color_back" value="#000066" />
                                        </div>
                    					<div class="col-lg-2">
                    					<select name='ref_id' required class='form-control'>
                    						<?php echo selectref(1); ?>
                    					</select>
                    					</div> 
                    					<div class="col-lg-1">
                    						<input type='submit' class='btn btn-info btn-sm' value=' View and Print'  class='form-control' name='submit'>
                    					</form>
                    					</div>
                    				</div>
                                    </div>
				                <div class="card-header">
                                        <h5> Old Task Report </h5>
				                <div class="row">
                    					<div class="col-lg-2">
                                		<form action ='old_print_with_location.php' method='post' target='_blank'>    
                    						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                    					</div>	
                    					<div class="col-lg-2">
                    					    <input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='todate' required>
                    					</div>
                    					<div class="col-lg-2">
                    					<select name='court_location' required class='form-control'>
                    					<?php echo dropdown_list('court_location','id','name' ); ?>
                    					</select>
                    					</div>
                    					<div class="col-lg-2">
                    						<input type='submit' class='btn btn-info btn-sm' value='View and Print '  class='form-control' name='submit'>
                    					</form>
                    					</div>
                    			</div>
                    			</div>
                                <div class="card-header">
                                        <h5> Future Task Count (Date Wise)</h5>
                    					<div class="row">
                            					<div class="col-lg-3">
                                        		<form action ='future_task_with_location.php' method='post' target='_blank'>    
                            					
                            						<input type='date' value='<?php echo date('Y-m-d'); ?>'  class='form-control' name='fromdate' required >
                            					</div>	
                            					<div class="col-lg-3">
                                					<select name='court_location' required class='form-control'>
                                						<?php echo dropdown_list('court_location','id','name' ); ?>
                                					</select>
                    					        </div>
                            					<div class="col-lg-3">
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