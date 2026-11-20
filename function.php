<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);

require_once(__DIR__ . '/system/op_lib.php');

function btn_next_date($table, $id, $status)
{
    $case  = get_data($table,$id)['data'];
    
	$str = "<button class='btn_cal btn btn-warning btn-sm'  data-table='$table' data-id='$id' data-date_source ='{$case['date_source']}' data-next_date ='{$case['next_date']}' data-case_type_id ='{$case['case_type_id']}' data-party_name ='{$case['party_name']}' data-case_number ='{$case['case_number']}' data-case_year ='{$case['case_year']}' data-user_id ='{$case['user_id']}' data-mobile ='{$case['mobile']}' data_email ='{$case['email']}' data-pkey='id' data-status='$status' title='Next Date'> <i class='fa fa-calendar'></i> </button> ";
	return $str;
}

function btn_next_date_sms($table, $id, $status)
{
    $case  = get_data($table,$id)['data'];
    
	$str = "<button class='btn_cal btn btn-warning btn-sm'  data-table='$table' data-id='$id' data-date_source ='{$case['date_source']}' data-next_date ='{$case['next_date']}' data-case_type_id ='{$case['case_type_id']}' data-party_name ='{$case['party_name']}' data-case_number ='{$case['case_number']}' data-case_year ='{$case['case_year']}' data-user_id ='{$case['user_id']}' data-mobile ='{$case['mobile']}' data_email ='{$case['email']}' data-pkey='id' data-status='$status' title='Next Date'> <i class='fa fa-envelope'></i> </button> ";
	return $str;
}

function btn_update_mobile($table, $id, $status)
{
    $case  = get_data($table,$id)['data'];
    
	$str = "<button class='btn_cal btn btn-success btn-sm'  data-table='$table' data-id='$id' data-date_source ='{$case['date_source']}' data-mobile ='{$case['mobile']}' data-pkey='id' data-status='$status' title='Update Mobile'> <i class='fa fa-phone'></i> </button> ";
	return $str;
}

function generateDateList($startDate, $interval, $numberOfDates) {
    $dateList = array();

    // Convert start date to DateTime object
    $currentDate = new DateTime($startDate);

    // Generate date list
    for ($i = 0; $i < $numberOfDates; $i++) {
        
        
        // Add the interval to the current date
        $currentDate->add(new DateInterval('P' . $interval . 'D'));
        
        // Add current date to the list
        $dateList[$i+1] = $currentDate->format('Y-m-d');

    }

    return $dateList;
}

function this_month($table_name, $date_column_name)
	{
		$current_month = date('n');
		$current_year = date('Y');
		$sql ="SELECT * FROM $table_name having month($date_column_name) ='$current_month' and year($date_column_name) ='$current_year'";
		$res = direct_sql($sql);
		return $res;
	}

function last_id($table, $col_name, $start =0, $end =0)
	{
		if($end ==0)
		{
			$end = "length($col_name)";
		}
		$sql ="SELECT max(cast(substring($col_name,$start,$end) as int)) as sl_no FROM $table where status not in('DELETED','AUTO','REMOVED')";
		$res = direct_sql($sql);
		return $res['data'][0]['sl_no'];
	}

function loan_late_fine($amount, $due_date)
    {
        global $working_date;
        $data['one_day_fine']=0;
        $data['total_days']=0;
        $data['total_fine']=0;
        if($amount>49)
        {
        $data['one_day_fine'] = $fine  = $amount*0.001;
        
        //$now = time(); // or your date as well
        $now = strtotime($working_date);
        $your_date = strtotime($due_date);
        $datediff = $now - $your_date;
        // $days  = round($datediff / (60 * 60 * 24))-1;
        // $days = ($days<0)?$days:0;
        $data['total_days']=  $days = round($datediff / (60 * 60 * 24))-1;
        $tfine = round($days*$fine);
        $data['total_fine'] =($tfine<=0)?0:$tfine;
        }
        return $data;
    }

// from old CIS
function countdate( $todate)
		{
		global $con;
		$query ="select * from case_reg where next_date <= '$todate' and case_status='ACTIVE'";
		$result = mysqli_query($con,$query) or die(" Function Count Error A : ".mysqli_error($con));
		$count = mysqli_num_rows($result);
		return $count;
		
		}
function counttodaydate( $todate)
		{
		global $con;
		$query ="select * from case_reg where next_date = '$todate' and case_status='ACTIVE'";
		$result = mysqli_query($con,$query) or die(" Function Count Error A : ".mysqli_error($con));
		$count = mysqli_num_rows($result);
		return $count;
		
		}

function userid($username )
		{
		global $con;
		$query ="select * from op_user where user_name = '$username'";
		$res = mysqli_query($con,$query) or die(" User Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row['user_id'];
		}
		
function userinfo($user_id, $value )
		{
		global $con;
		$query ="select * from op_user where id = '$user_id'";
		$res = mysqli_query($con,$query) or die(" User Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function advinfo($adv_id, $value )
		{
		global $con;
		$query ="select * from adv_reg where adv_id = $adv_id or adv_mobile ='$adv_id'";
		$res = mysqli_query($con,$query) or die(" Advocate Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function advdatainfo($adv_id, $value )
		{
		global $con;
		$query ="select * from adv_data where adv_id ='$adv_id'";
		$res = mysqli_query($con,$query) or die(" Advocate Data Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}

function courtinfo($court_id, $value )
		{
		global $con;
		$query ="select * from court_reg where id = '$court_id'";
		$res = mysqli_query($con,$query) or die(" Court Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function courtlocationinfo($id, $value )
		{
		global $con;
		$query ="select * from court_location where id = '$id'";
		$res = mysqli_query($con,$query) or die(" Court Location Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function caseinfo($case_id, $value )
		{
		global $con;
		$query ="select * from case_reg where id = '$case_id'";
		$res = mysqli_query($con,$query) or die(" Case Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function refinfo($ref_id, $value )
		{
		global $con;
		$query ="select * from ref_reg where id = $ref_id";
		$res = mysqli_query($con,$query) or die(" Ref Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function refmobile($ref_id, $value )
		{
		global $con;
		$query ="select * from ref_reg where ref_mobile = $ref_id";
		$res = mysqli_query($con,$query) or die(" Ref Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function casestatusinfo($case_status_id, $value )
		{
		global $con;
		$query ="select * from case_status where id = $case_status_id";
		$res = mysqli_query($con,$query) or die(" Case Status Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
		
function casetypeinfo($case_type, $value )
		{
		global $con;
		$query ="select * from case_type where id = '$case_type'";
		$res = mysqli_query($con,$query) or die(" Case Type Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function stateinfo($state_id, $value )
		{
		global $con;
		$query ="select * from state where state_id = '$state_id'";
		$res = mysqli_query($con,$query) or die(" State Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function statebcinfo($state_bc_id, $value )
		{
		global $con;
		$query ="select * from state_bc where state_bc_id = '$state_bc_id'";
		$res = mysqli_query($con,$query) or die(" State Bar Council Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function districtinfo($district_id, $value )
		{
		global $con;
		$query ="select * from district where district_id = $district_id";
		$res = mysqli_query($con,$query) or die(" District Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
function prevdate($case_id )
		{
		global $con;
		$query ="select * from case_status where case_id = $case_id and id =(select max(id) from case_status where case_id =$case_id)";
		$res = direct_sql($query);
		$str ='';
		if($res['count']>0)
		{
		  $str=    $res['data'][0]['present_date'];
		}
		$res = mysqli_query($con,$query) or die(" Present Date Information Error : ".mysqli_error($con));
		
		return $str;
		}
		
		
function nextdate($case_id )
		{
		global $con;
		$query ="select * from case_status where case_id = $case_id and id =(select max(id) from case_status where case_id =$case_id)";
		$res = mysqli_query($con,$query) or die(" Present Date Information Error : ".mysqli_error($con));
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row['next_date'];
		}
		
function psinfo($ps_id, $value )
		{
		global $con;
		$query ="select * from police_station where id = $ps_id";
		$res = mysqli_query($con,$query) or die(" Police Station Information Error : ".mysqli_error($con));
		if(mysqli_num_rows($res)==0)
		{
			return "Not Applicable";
		
		}
		else{
		$row =mysqli_fetch_assoc($res);
		extract($row);
		//print_r($row);
		return $row[$value];
		}
		}

function selectstatebc($select_id)
		{
		global $con;
		$query ="select * from state_bc";
		$res = mysqli_query( $con,$query) or die(" State BC List Error : ".mysqli_error($con));
		
		while($row = mysqli_fetch_array($res))
			{
			$state_bc_id= ucwords($row['state_bc_id']);
			$state_bc_name= ucwords($row['state_bc_name']);
			?>
			<option <?php if($state_bc_id ==$select_id) echo "selected";?> value ='<?php echo $state_bc_id; ?>'> <?php echo $state_bc_name; ?> </option>
			<?php
			}
		
		}
function selectps($select_id,$district_id)
		{
		global $con;
		$query ="select * from police_station where district_id ='$district_id' order by ps_name";
		$res = mysqli_query( $con,$query) or die(" Police Station Error : ".mysqli_error($con));
			echo "<option value='0' selected> Not Applicable </option>";
		while($row = mysqli_fetch_array($res))
			{
			$ps_id= ucwords($row['ps_id']);
			$ps_name= ucwords($row['ps_name']);
			?>
			<option <?php if($ps_id ==$select_id) echo "selected";?> value ='<?php echo $ps_id; ?>'> <?php echo $ps_name; ?> </option>
			<?php
			}
		
		}


function selectcasetype($select_id)
		{
		global $con;
		$query ="select * from case_type order by case_type ";
		$res = mysqli_query( $con,$query) or die(" Csae Type List Error : ".mysqli_error($con));
		
		while($row = mysqli_fetch_array($res))
			{
			$case_type_id= ucwords($row['case_type_id']);
			$case_type = ucwords($row['case_type']);
			$court_type = ucwords($row['court_type']);
			?>
			<option <?php if($case_type_id ==$select_id) echo "selected";?> value ='<?php echo $case_type_id; ?>'> <?php echo $case_type. " [ " .$court_type ." ]"; ?> </option>
			<?php
			}
		
		}
		
function selectref($select_id)
		{
		global $con;
		$user_id = $_SESSION['user_id'];
		$query ="select * from ref_reg where status='ACTIVE'and user_id = '$user_id' order by name ";
		$res = mysqli_query( $con,$query) or die(" Ref List Error : ".mysqli_error($con));
		
		while($row = mysqli_fetch_array($res))
			{
			$id= ucwords($row['id']);
			$ref_type = ucwords($row['ref_type']);
			$name = ucwords($row['name']);
			?>
			<option <?php if($id ==$select_id) echo "selected";?> value ='<?php echo $id; ?>'> <?php echo $name. " [ " .$ref_type ." ]"; ?> </option>
			<?php
			}
		
		}
function selectcourtlocation($select_id)
		{
		global $con;
		$query ="select * from court_location order by name";
		$res = mysqli_query( $con,$query) or die(" Location Error : ".mysqli_error($con));
		
		while($row = mysqli_fetch_array($res))
			{
			$id= ucwords($row['id']);
			$district_name = ucwords($row['district_name']);
			$name = ucwords($row['name']);
			?>
			<option <?php if($id ==$select_id) echo "selected";?> value ='<?php echo $id; ?>'> <?php echo $name. " [ " .$district_name ." ]"; ?> </option>
			<?php
			}
		
		}
function selectdistrict($select_id)
		{
		global $con;
		$query ="select * from district order by state_id";
		$res = mysqli_query( $con,$query) or die(" District List Error : ".mysqli_error($con));
		
		while($row = mysqli_fetch_array($res))
			{
			$district_id= ucwords($row['district_id']);
			$state_id= ucwords($row['state_id']);
			
			$district_name = ucwords($row['district_name']);
			?>
			<option <?php if($district_id ==$select_id) echo "selected";?> value ='<?php echo $district_id; ?>'> <?php echo $district_name; ?> </option>
			<?php
			}
		
		}

function selectcourt($select_id,$district_id)
		{
		global $con;
		$query ="select * from court_reg where district_id='$district_id' order by court_type desc  ";
		$res = mysqli_query( $con,$query) or die(" Court List Error : ".mysqli_error($con));
		
		while($row = mysqli_fetch_array($res))
			{
			$court_id= ucwords($row['court_id']);
			$court_designation = ucwords($row['court_designation']);
			$court_name = ucwords($row['court_name']);
			?>
			<option <?php if($court_id ==$select_id) echo "selected";?> value ='<?php echo $court_id; ?>'> <?php echo $court_designation. " [ " .$court_name ." ]"; ?> </option>
			<?php
			}
		
		}

function usedsms() {
		global $con;
		$user_id = userid($_SESSION['admin']);
		$res = mysqli_query($con,"select sum(sms_credit) from sms where id =$user_id");
		if(!$res){
		return 0;
		}
		$row =mysqli_fetch_assoc($res);
		extract($row);
		return $used =$row['sum(sms_credit)'];
		
		}

function sendsms1($number,$sms)
		{		
		global $con;
		$user_id = $_SESSION['user_id'];
		$sms_limit =userinfo($user_id,'sms_limit'); 
		
		if(strlen($sms) <150) {$credit =1;}
			else{
			$credit =(strlen($sms)/150)+1;
			}
		
		if ( $credit >= $sms_limit) 
		{ 
		echo "<script> window.confirm('Sorry ! You do not Have SMS Balance to send SMS. Are you want to continue ?') </script>";
		
		}					
		else{
		$no =urldecode($number);
		if($no<>"")
		{
		$msg = urlencode($sms);
		$sender =userinfo($user_id,'sender_id');
		// b0e99bea1fa7d15e27e1c5fd8e3c868 myadvindia
		mysqli_query($con,"insert into sms(sender_id,mobile,sms_details,sms_credit,user_id) values ('$sender','$number','$sms','$credit',$user_id)");
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, "http://msg.morg.in/rest/services/sendSMS/sendGroupSms?AUTH_KEY=b0e99bea1fa7d15e27e1c5fd8e3c868&message=$msg&senderId=$sender&routeId=1&mobileNos=$no&smsContentType=english");
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Set atimeout here
		$res= curl_exec($ch);
		$new_credit =$sms_limit - $credit;
		mysqli_query($con,"update op_user set sms_limit =$new_credit where id =$user_id");
		@curl_close($ch);
		return $res;
		}
		
		}
}

function userlist($value,$list)
		{
		global $con;
		$i =0;
		$query ="select * from user";
		$res = mysqli_query($con,$query) or die(" User List Creating Error : ".mysql_error($con));
			echo "<option value=''>=== Select Value === </option>";
		while($row =mysqli_fetch_array($res))
		{
			echo "<option value='".$row[$value]."'>". $row[$list]."</option>";
		}		
		}	
function datecount($dt)
{
		global $con;
		$sql="SELECT next_date, count(case_id) FROM `case_status` where next_date = '$dt' limit 1 ";
		$res = mysqli_query($con,$sql) or die ("Error in Future Case". mysqli_error());
		$row =mysqli_fetch_assoc($res);
		extract($row);
		return $row['count(case_id)'];
}

function btn_case_view($table, $id, $user_id='')
{
   
    
	$link =encode("table_name=$table&id=$id");
	$str = "<a href='case_detail_view.php?link=$link' class=' btn btn-warning btn-sm'  data-table='$table' data-id='$id' data-pkey='id' title='View Case Details' target='_blank'> <i class='fa fa-tv'></i> </a> ";
	return $str;
}


function custom_widget($table_name, $status = 'ACTIVE')
{
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    $ct = 0;
    
    $table_data = get_data('op_table', $table_name, null, 'table_id');
    $table_id = isset($table_data['data']['id']) ? $table_data['data']['id'] : 0;
    
    if($user_type=='DEV' || $user_type=='SUPERADMIN') {
	    $ct = get_all($table_name, '*', array('status' => $status))['count'];
    } else {
	    $ct = get_all($table_name, '*', array('status' => $status, 'user_id' => $user_id))['count'];
    }

	$iconres = get_multi_data('op_menu', array('type' => 'MAIN', 'table_id' => $table_id));
	$icon = ($iconres['count'] > 0) ? $iconres['data'][0]['icon'] : 'database';
    
    // Choose a color based on the icon/table name or just use primary
    $colors = ['primary', 'success', 'info', 'warning', 'danger'];
    $color = $colors[abs(crc32($table_name)) % count($colors)];

	$str = '<div class="col-sm-6 col-xl-3 mb-4">
		<div class="card shadow-premium h-100 border-0">
			<div class="card-body p-4">
				<div class="d-flex align-items-center mb-3">
					<div class="stat me-3" style="background: var(--' . $color . '-soft) !important; color: var(--' . $color . ') !important; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
						<i data-feather="' . $icon . '" class="feather-lg"></i>
					</div>
					<div>
						<h6 class="card-title mb-0" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 700;">' . strtoupper(add_space($table_name)) . '</h6>
						<span class="text-muted small">' . $status . '</span>
					</div>
				</div>
				<div class="d-flex align-items-baseline justify-content-between">
					<h2 class="mb-0 fw-bold" style="font-family: \'Outfit\', sans-serif;">' . $ct . '</h2>
					<a href="../public/' . $table_name . '_manage.php" class="btn btn-sm btn-light rounded-pill px-3" style="font-size: 0.7rem;">View All <i data-feather="chevron-right" style="width:12px; height:12px;"></i></a>
				</div>
			</div>
		</div>
	</div>';

	return $str;
}


function recent_widget_1($table_name)
	{
		
		$cols = create_list('op_master_table','column_name',array('table_name'=> $table_name, 'allow_global_search'=>'YES'));
		
		if(is_array($cols))
		{
		$cols_list = implode(',',$cols);
		
		$sql ="select id, $cols_list from $table_name where status not in ('AUTO','DELETED') order by id desc limit 10";
		$res = direct_sql($sql);

		
		$str ='<div class="col-12 col-lg-6 d-flex">
		<div class="card flex-fill w-100">
			<div class="card-header">
				
				<h5 class="card-title mb-0">'. add_space($table_name).'</h5>
			</div>
			<div class="card-body pt-2 pb-3">'.
				create_data_table($table_name,$res, [], '')
			.'</div>
		</div>
		</div>';

		return $str;
		}
	}
	
function wallet_amount($user_id, $txn_amount, $txn_type='ADD',  $txn_remarks ='', $txn_date ='')
    {
            global $today;
            $res =['status'=>'error','msg'=>'No Update Found'];
		    $owallet = get_data('op_user',$user_id,'wallet')['data'];
		    if($txn_type =='ADD')
		    {
		        $data['balance'] = $nwallet  = $owallet + $txn_amount;
		        $data['credit_amt'] = $txn_amount;
		    }else{
		       $data['balance'] = $nwallet = $owallet - $txn_amount;  
		       $data['debit_amt'] = $txn_amount;
		    }
		    $data['txn_date'] =($txn_date=='')? $today :$_POST['txn_date'];
		    $data['txn_remarks'] =($txn_remarks=='')? '' :$txn_remarks;
		    $data['user_id'] =$user_id;
		    $ires = insert_data('wallet', $data);
		    if($ires['status'] =='success')
		    {
		    $res = update_data('op_user',['wallet'=>$nwallet], $user_id);
		    $res['msg'] = ($res['status'] =='success') ? $txn_amount ." Added Successfully ! Current Ballance : ".$nwallet :$res['msg'];
		    $res['wallet'] = $nwallet;
		    }
		   return $res;
    }
