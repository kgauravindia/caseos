<?php 
require_once("../function.php");
$data  = decode($_GET['link']);

if(!isset($_SESSION['user_id']))
{
    exit();
}
$case_id =$data['id'];
$userdata=get_data('op_user',$user_id)['data'];

?>
       <title> <?= $userdata['full_name']?>  </title>
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
										<th colspan='3'> <img src="<?= $base_url ?>system/img/logo.png" height="30px"> </th>
										<th colspan='5'> <center><big>eDiary : Case Information System </big><br>
											<span style='font-size:10px;text-align:right;color:gray;'> Powered by OfferPlant Technologies Private Limited </span></center> 
										</th>
										<th colspan='2'  style='text-align:right;' ><img src="<?= $base_url ?>system/img/logo.png" height="30px">
										<button  onclick="this.style.display = 'none';window.print();" > Print </button>
										</th>
									</tr>
									<tr> <th colspan='12' > <hr>  </th></tr>
									<tr>
										<th colspan='3'> Name </th>
										<td colspan='4'> <tt><?= $userdata['full_name']?> </td>
										<th colspan='1'> Mobile </th>
										<td colspan='2'> +91 <?= $userdata['user_mobile']?></td>
									</tr>
									
									<tr>
										<th colspan='3'> Email </th>
										<td colspan='4'> <?= $userdata['user_email']?></td>
										<th colspan='1'> Website </th>
										<td colspan='2'> <?= $userdata['website']?></td>
									</tr>
									<tr> <th colspan='12' > <hr>  </th></tr>

                                    <tr>
										<th colspan='3'> Court </th>
										<td colspan='4'> <?php echo courtinfo(caseinfo($case_id,'court_id'),'court_designation'); ?></td>
										<th colspan='1'> Case Type </th>
										<td colspan='2'> <?php echo casetypeinfo(caseinfo($case_id,'case_type_id'),'name'); ?> </td>
									</tr>
									<tr>
										<th colspan='3'> Case No </th>
										<td colspan='4'> <?php echo caseinfo($case_id,'case_number');?>/<?php echo caseinfo($case_id,'case_year'); ?> </td>
										<th colspan='1'> Case Reg. No </th>
										<td colspan='2'> <?php echo caseinfo($case_id,'case_reg_number'); ?>/<?php echo caseinfo($case_id,'case_reg_year'); ?> </td>
									</tr>
									<tr>
										<th colspan='3'> CNR </th>
										<td colspan='4'> <?php echo caseinfo($case_id,'cnr'); ?> </td>
										<th colspan='1'> Case Type </th>
										<td colspan='2'> <?php echo casetypeinfo(caseinfo($case_id,'case_type_id'),'name'); ?> </td>
									</tr>
									<tr>
										<th colspan='3'> Petitioner / Plaintiff  </th>
										<td colspan='3'> <?php echo caseinfo($case_id,'cfp_name'); ?>  </td>
										<th colspan='2'> OP / Defendant  </th>
										<td colspan='2'> <?php echo caseinfo($case_id,'csp_name'); ?>  </td>
									</tr>
									<tr>
										<th colspan='3'> Our Party </th>
										<td colspan='4'> <?php echo caseinfo($case_id,'our_party'); ?>  </td>
										<th colspan='1'> Contact Person </th>
										<td colspan='2'> <?php echo caseinfo($case_id,'party_name'); ?>  </td>
									</tr>
									<tr>
										<th colspan='3'> Party Mobile </th>
										<td colspan='4'><?php echo caseinfo($case_id,'mobile'); ?>  </td>
											<th colspan='1'> Party Email </th>
										<td colspan='2'> <?php echo caseinfo($case_id,'email'); ?>  </td>
									</tr>
									<tr>
										<th colspan='3'> Reg. Date </th>
										<td colspan='4'><?php echo date('d-M-Y',strtotime(caseinfo($case_id,'date_of_reg'))); ?>   </td>
										<th colspan='1'> Under Section </th>
										<td colspan='2'> <?php echo caseinfo($case_id,'under_section'); ?>  </td>
									</tr>
									<tr>
										<th colspan='3'> Police Station </th>
										<td colspan='4'><?php echo psinfo(caseinfo($case_id,'ps_id'),'name'); ?>  </td>
										<th colspan='1'> PS No/Year </th>
										<td colspan='2'><?php echo caseinfo($case_id,'ps_number'); ?> / <?php echo caseinfo($case_id,'ps_year'); ?> </td>
									</tr>
									<tr>
										<th colspan='3'> Case Status </th>
										<td colspan='4'><?php echo caseinfo($case_id,'case_status'); ?> </td>
										<th colspan='1'> Refered By </th>
										<td colspan='2'><?php echo refinfo (caseinfo($case_id,'ref_id'),'name'); ?> (<?php echo refinfo (caseinfo($case_id,'ref_id'),'ref_type'); ?>) </td>
									</tr>
					               <tr> <th colspan='12' > <hr>  </th></tr>
					                <tr>
                                            <th colspan='1'># </th>
                                            <th colspan='1'>Court </th>
                                            <th colspan='2'>Present Date </th>
                                            <th colspan='2'>Case Type </th>
                                            <th colspan='2'>Case No </th>
											<th colspan='3'>Work Done</th>
                                            <th colspan='1'>Next Date</th>
										</tr>
									<tr> <th colspan='12' > <hr>  </th></tr>
                                    </thead>
                                    <tbody>
										<?php 
										$i =1;
										$sql ="select * from case_status where case_id='$case_id' order by next_date";
										$res = mysqli_query($con,$sql) or die ("Error in selecting Case Status". mysql_error());
										while($row =mysqli_fetch_array($res))
										{
										echo"<tr class='odd gradeX'>";
										$case_status_id =$row['id'];
										echo "<td colspan='1'>[".$i++."]</td>";
										echo"<td colspan='1'>".courtinfo($row['court_id'],'court_designation')."</td>";
										echo"<td colspan='2'>".date('d/m/Y',strtotime($row['present_date']))."</td>";
										echo"<td colspan='2'>".casetypeinfo($row['case_type_id'],'name')."</td>";
										echo"<td colspan='2'>".$row['case_number']."/".$row['case_year']."</td>";
										echo"<td colspan='3'>".$row['case_work']."</td>";
										echo"<td colspan='1'>".date('d/m/Y',strtotime($row['next_date']))."</td>"; 
										echo "</tr>";
										}
                                       ?>
                                    </tbody>
								</table>
							
</div>
</div>
</div>
</div>