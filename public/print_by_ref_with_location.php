<?php 
require_once("../function.php");
$fromdate =$_POST['fromdate'];
$todate =$_POST['todate'];
$ref_id =$_POST['ref_id'];
$court_location =$_POST['court_location'];
$sql ="SELECT * FROM `case_reg` WHERE next_date >='$fromdate' and next_date <= '$todate' and case_status= 'ACTIVE' and court_location='$court_location' and ref_id= $ref_id order by court_id";
$res = mysqli_query($con,$sql) or die ("Error in Today Case". mysqli_error());
$cnt =mysqli_num_rows($res);
$color_back =$_POST['color_back'];
$userdata=get_data('op_user',$user_id)['data'];
?>

<title> <?php echo refinfo($ref_id, 'name' )  ?> <?php echo refinfo($ref_id, 'ref_type' ) ?> <?php echo date('d-M-Y',strtotime($fromdate)); ?> To <?php echo date('d-M-Y',strtotime($todate)); ?> </title>
        <!--  page-wrapper -->
        <div id="page-wrapper">
            
			
			<div class="row">
                <div class="col-lg-12">
                    <!-- Advanced Tables -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table style='' align='center'>
                                 <style> 
								 th { border-bottom:solid 1px gray;padding:4px; font-family:arial;font-size:12px;color:blue;} 
								 td { border-bottom:dotted 1px gray;padding:4px; font-family:arial;font-size:12px;} 
								 .info{line-height:13px;}
								 .info td{ border-bottom:dotted 0px gray;font-size:15px; font-family:'Lucida Console', monospace;color:midnightblue;text-align:left;} 
								 .info th{ border-bottom:dotted 0px gray;font-size:15px;font-family:calibri,Times New Roman,arial;color:blue;text-align:left;} 
								 
								 </style>
                                    <thead class='info'>
									<tr>
										<th colspan='3'> <img src="<?= $base_url ?>system/img/logo_white.png" height="30px"> </th>
										<th colspan='5'> <center><big>CaseOS : Case Operating System </big><br>
											<span style='font-size:10px;text-align:right;color:gray;'> Powered by OfferPlant Technologies Private Limited </span></center> 
										</th>
										<th colspan='2'  style='text-align:right;' ><img src="<?= $base_url ?>system/img/logo.png" height="30px">
										<button  onclick="this.style.display = 'none';window.print();" > Print </button>
										</th>
									</tr>
									<tr> <th colspan='12' > <hr>  </th></tr>
									<tr>
										<th colspan='3'> Name </th>
										<td colspan='4'> <?php echo refinfo($ref_id, 'name' )  ?> <?php echo refinfo($ref_id, 'ref_type' ) ?></td>
										<th colspan='1'> From Date </th>
										<td colspan='2'> <?php echo date('d-M-Y',strtotime($fromdate)); ?></td>
									</tr>
									<tr>
										<th colspan='3'> Mobile </th>
										<td colspan='4'> +91 <?php echo refinfo($ref_id, 'mobile' ) ?></td>
										<th colspan='1'> To Date </th>
										<td colspan='2'> <?php echo date('d-M-Y',strtotime($todate)); ?></td>
									</tr>
									<tr>
										<th colspan='3'> Email </th>
										<td colspan='4'> <?php echo refinfo($ref_id, 'email' ) ?></td>
										<th colspan='1'> Website </th>
											<td colspan='2'> <?= $userdata['website']?></td>
									</tr>
									<tr> <td colspan='12'> <hr><center> [ Referance Wise Task : <?php echo $cnt; ?> | Location : <?php echo courtlocationinfo ($court_location, 'name') ; ?> ] </center></td></tr>
										<tr bgcolor='#f5f5f5'>
                                            <th># </th>
                                            
                                            <th>Court </th>
                                            <th>Case Type </th>
                                            <th>Case No </th>
											<th>Reg No</th>
											<th>1st Party</th>
											<th>2nd Party</th>
											<th>DS / N Date</th>
											<th>Our Party/Note</th>
                                             
                                             
                                             
                                        </tr>
                                    </thead>
                                    <tbody>
										<?php
										$i =1;
										if($cnt<1)
										{
											echo "<tr> <td colspan='10' style='color:red;font-size:18px;text-align:center;'> Sorry ! No Task To Print .</td></tr>";
										}
										else{
										while($row =mysqli_fetch_array($res))
										{
									
										echo"<tr class='odd gradeX' style='color:$color_back';>";
									//	$case_id =$row['case_id'];
										                                   
										echo "<td>[".$i."]</td>";
										
										echo "<td>".courtinfo($row['court_id'],'court_designation')."</td>";
										echo "<td>".casetypeinfo($row['case_type_id'],'name')."</td>";
										
										echo"<td align='right'>".$row['case_number']."/".$row['case_year']."</td>";
										echo"<td align='right'>".$row['case_reg_number']."/".$row['case_reg_year']."</td>";
										
										echo"<td>".$row['cfp_name']."</td>";
										echo"<td>".$row['csp_name']."</td>"; 
										
										echo"<td>".$row['date_source']."&nbsp;&nbsp;".date('d/m/Y',strtotime($row['next_date']))."</td>";                                     
										echo"<td>(".$row['our_party'].")&nbsp;&nbsp;</td>";                                     
                                        
										?>
										
                                         <?php   
                                        echo "</tr>";
										$i=$i+1;
										}
										}
										
                                       ?>
                                     
                                    </tbody>
                                </table>
                            </div>
                            
                      
            </div>

        </div>
        <!-- end page-wrapper -->

    </div>
    <!-- end wrapper -->

    <!-- Core Scripts - Include with every page -->
    <script src="assets/plugins/jquery-1.10.2.js"></script>
    <script src="assets/plugins/bootstrap/bootstrap.min.js"></script>
    <script src="assets/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="assets/plugins/pace/pace.js"></script>
    <script src="assets/scripts/siminta.js"></script>
   
</body>

</html>