<?php 
require_once("../function.php");
$fromdate =$_POST['fromdate'];
$sql ="SELECT next_date, count(case_id) FROM `case_status` where next_date > '$fromdate' GROUP by (next_date)";
$res = mysqli_query($con,$sql) or die ("Error in Future Case". mysqli_error());
$cnt =mysqli_num_rows($res);
$userdata=get_data('op_user',$user_id)['data'];
?>
<title> <?= $userdata['full_name']?> Date Wise Task After <?php echo date('d-M-Y',strtotime($fromdate)); ?> </title>
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
										<th colspan='3'>Name </th>
										<td colspan='3'><?= $userdata['full_name']?></td>
										<th colspan='3'> Mobile </th>
										<td colspan='3'> +91 <?= $userdata['user_mobile']?></td>
									</tr>
									<tr>
										<th colspan='3'> Email </th>
										<td colspan='3'> <?= $userdata['user_email']?></td>
										<th colspan='3'> Website </th>
										<td colspan='3'> <?= $userdata['website']?> </td>
									</tr>
									<tr> <td colspan='12'> <hr><center> [ After <?php echo date('d-F-Y',strtotime($fromdate)); ?> : <?php echo $cnt; ?> Working Day ]</center></td></tr>
										<tr bgcolor='#f5f5f5'>
                                            <th># </th>
                                            <th colspan='5'>Date </th>
                                            <th colspan='4'>Count </th>
                                             
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
									
										echo"<tr class='odd gradeX'>";
										                                   
										echo "<td>[".$i."]</td>";
										echo "<td colspan='5'>".date('d-M-Y (l)',strtotime($row['next_date']))."</td>";
										echo "<td colspan='4'>".$row['count(case_id)']."</td>";
										
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