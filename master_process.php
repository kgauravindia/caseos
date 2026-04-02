<?php
require_once('function.php');
$_POST = post_clean($_POST);
$_GET = post_clean($_GET);
if (isset($_GET['task'])) {
	$task = xss_clean($_GET['task']);
	$user_id = (isset($_SESSION['user_id']))?$_SESSION['user_id']:'';
	switch ($task) {
        
      
        case "master_update_data":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$_POST['status']=(isset($_POST['status']))?$_POST['status']:'ACTIVE';
			$_POST['user_id'] =$_SESSION['user_id'];
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;
			

         case "working_date": // Change Date form Complete System
			extract($_POST);
			$res = update_data('op_config', array('option_value'=>$working_date),'working_date','option_name');
			echo json_encode($res);
			break;
			
        case "change_password": // Change Password of Logged in User
			$current_pass = md5($_POST['current_password']);
			$new_password = md5($_POST['new_password']);
			$where = array('id' => $user_id, 'user_pass' => $current_pass);
			$res = update_multi_data('user', array('user_pass' => $new_password), $where);
// 			$notice  = "$user_name changed their password";
			echo json_encode($res);
			break;
		
		case "update_password": // From Reset Link
			extract($_POST);
			$new_password = md5($_POST['new_password']);
			$where = array('id' => $id, 'user_name' => $user_name);
			$res = update_multi_data('op_user', array('user_pass' => $new_password), $where);
			if($res['status']=='success')
			{
				$res['msg'] ='Password Chnaged Successfully';
				$res['url'] ='login';
			}
			echo json_encode($res);
			break;
        
		case "add_table":
			extract($_REQUEST);
			$res = create_table(remove_space($table_name));
			echo json_encode($res);
			break;	

		case "get_column":
			extract($_REQUEST);
			$res = column_list($table_name)['data'];
			foreach((array) $res as $row)
			{
				echo "<option value='".$row['COLUMN_NAME']."'>". $row['COLUMN_NAME'] ."</option>";
			}
			break;	


		case  "get_dist":
			extract($_REQUEST);
			$state_code = get_data('state',$id,'code')['data'];
			$res = get_all('district', '*', array('state_code' => $state_code), 'name');
			foreach ($res['data'] as $data) {
				$id = $data['id'];
				echo "<option value='" . $data['code'] . "'>" . $data['name'] . "</option>";
			}
			break;
		
		case "sort_column" :
			extract($_POST);
			$i =$ct =1;
			foreach((array) $columns as $column_id)
			{
				$res =update_data('op_master_table',array('display_id'=>$i), $column_id);
				if($res['status']=='success')
				{
					$ct++;
				}
				$i++;
			}
			$res['status'] = 'success';
			$res['msg'] = $ct .' Chanages found';
			echo json_encode($res);
			break;	

	    case "update_table":
			extract($_POST);
			unset($_POST['isedit']);
			if ($input_type == 'List-Dynamic' or $input_type == 'CheckList-Dynamic'  or $input_type == 'List-Where' ) {
				$_POST['input_value'] = implode(',', $_POST['dynamic_input']);
			}
			else{
				$_POST['input_value'] = $_POST['static_input'];	
			}
			unset($_POST['dynamic_input']);
			unset($_POST['static_input']);
			if($isedit=='no')
			{
				add_column($table_name, remove_space($column_name));
				$_POST['column_name'] = remove_space($column_name);
				$_POST['display_name'] = add_space($column_name);
			}
			$res = update_data('op_master_table', $_POST, $id);
			
			$res['url']="op_table_manager?table_name=$table_name";
			echo json_encode($res);
			break;			
		
		case "upload" :
			$result =upload_img('uploadimg', 'rand','../upload');
			$result['src'] ='upload';
			echo json_encode($result);
			break;
		
		
		
		case "add_data":
			extract($_POST);
			$res = update_data($table_name, array($col=>$value), $id);
			echo json_encode($res);
			break;
	
	case "update_master_permission":
		extract($_POST);
		
		if($column=='is_unique')
		{
		  $col_name =  get_data('op_master_table',$id,'column_name')['data'];
		  $tab_name =  get_data('op_master_table',$id,'table_name')['data'];
		  if($status=='YES')
		  {
		  $sql ="ALTER TABLE $tab_name ADD UNIQUE($col_name)";
		  direct_sql($sql,"set");
		  }
		  else{
		       $sql ="ALTER TABLE $tab_name drop INDEX $col_name";
		      direct_sql($sql,"set");
		  }
		    $data = array($column=>$status);
	        $res = update_data($table, $data, $id); 
		}
		else{
		   $data = array($column=>$status);
	       $res = update_data($table, $data, $id); 
		}
		echo json_encode($res);
		break;	

	case 'sync_table':
		if(isset($_GET['table_name']) and $_GET['table_name']!='')
		{
			$table = $_GET['table_name'];
			$table_id  = get_data('op_table',$table,'id','table_id')['data'];
			$res = sync_table($table_id);
			
		}else{
			$tables  = table_list();	
            foreach($tables['data'] as $table)
		    {
			$table_id  = get_data('op_table',$table,'id','table_id')['data'];
			$res = sync_table($table_id);
	    	}
		}
		break;
        
    case "verify_login": // Delete Any Data From Table 
		extract($_POST);
		$user_name = strtolower(preg_replace("/[^a-zA-Z0-9.@]+/", "_", $user_name));
		$res = get_all('op_user', '*', array('user_name' => $user_name, 'user_pass' => md5($user_pass),'user_status'=>'ACTIVE'));
		
		if ($res['count'] == 1) {
			$mobile = $res['data'][0]['user_mobile'];
			$email = $res['data'][0]['user_email'];
			session_regenerate_id();
			if( trim(get_data('op_user',remove_space($user_name), 'allow_otp','user_name')['data']) =='YES')
			{
				$res['otp'] = $otp=rand(1000,9999);
				$salt=rnd_str(6);
				$_SESSION['initiated'] = "NO";
				$sms ="Thanks for interest in $inst_name Your App Login OTP is $otp Regards $inst_name ";
				//send_msg($mobile,$sms, "1207166936475956518");
				//wa_send($mobile,$sms);
				send_mail($email,"Your Login Otp is $otp", $sms);
				$ref = encode("otp=$otp&user_name=$user_name&salt=$salt");
				//$res['url'] = $base_url."system/op_otp?ref=$ref";  
				$res['url'] = $base_url."otp?ref=$ref";  
			}
			else
			{
				$_SESSION['initiated'] = "YES";
				$_SESSION['bine_token'] = $token  = md5(uniqid(rand(), TRUE));
				$_SESSION['token_time'] = time();
				$_SESSION['user_agent'] = 'bine_' . $_SERVER['HTTP_USER_AGENT'];
				$_SESSION['user_id'] = $user_id = $res['data'][0]['id'];
				$_SESSION['user_name'] = $res['data'][0]['user_name'];
				$mobile = $res['data'][0]['user_mobile'];
				$_SESSION['user_type'] = $user_type = $res['data'][0]['user_type'];
				update_data('op_user', array('token' => $token, 'status' => 'ACTIVE'), $user_id);
				//$res['url'] = $base_url.'system/op_dashboard';
				$res['url'] = $base_url.'index';
		    }
			//setcookie('bine_token', $token, time() + 600, '/', $inst_url);
			//void session_set_cookie_params ( int $lifetime [, string $path [, string $domain [, bool $secure = false [, bool $httponly = false ]]]] )
			//session_set_cookie_params( 60, '/' , $inst_name , true , true );
			session_regenerate_id();
		} else {
			$res['url'] = 'op_login';
		}
		echo json_encode($res);
		break;

		case "login_as":
			extract($_POST);
			$user_name = strtolower(preg_replace("/[^a-zA-Z0-9.@]+/", "_", $user_name));
			$res = direct_sql("select * from op_user where user_name ='$user_name' and user_pass ='$user_pass' and status not in('BLOCK','DELETED','AUTO')");
			if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV') {
				$outh = 'yes';
			} else {
				$outh = 'no';
			}
			if ($res['status'] == 'success' and $res['count'] == 1) {
				$uid = $res['data'][0]['id'];
				$cuser =get_data('op_user',$user_id)['data'];
				$udata = array('status' => 'ACTIVE', 'token' => $cuser['token']);
				$result = update_data('op_user', $udata, $uid, 'id');
				// 	$_SESSION['login_type'] ='ADMIN';
				// 	$_SESSION['old_user_id'] =   $_SESSION['user_id'];
				$_SESSION['admin_data'] = array('user_id' => $_SESSION['user_id'], 'user_type' => 'ADMIN', 'user_outh' => $outh);
				$_SESSION['user_id']    = $res['data'][0]['id'];
				$_SESSION['user_type']  = $res['data'][0]['user_type'];
				$_SESSION['user_name']  = $res['data'][0]['user_name'];
				//setcookie("username", $_SESSION['user_name'], time()+3600, "/", "",  0);
			} else {
				$result['id'] = 0;
				$result['status'] = 'fail';
				$result['msg'] = 'system is already Login';
			}
			echo json_encode($result);
			break;
		
	case "verify_otp": // Delete Any Data From Table 
		extract($_POST);
		
		if ($_POST['sotp'] == $_POST['uotp']) {
			$res = get_all('op_user', '*', array('user_name' => remove_space($user_name),'user_status'=>'ACTIVE'));
		
			session_regenerate_id();
			$_SESSION['initiated'] = "YES";
			$_SESSION['bine_token'] = $token  = md5(uniqid(rand(), TRUE));
			$_SESSION['token_time'] = time();
			$_SESSION['user_agent'] = 'bine_' . $_SERVER['HTTP_USER_AGENT'];
			$_SESSION['user_id'] = $user_id = $res['data'][0]['id'];
			$_SESSION['user_name'] = $res['data'][0]['user_name'];
			$mobile = $res['data'][0]['user_mobile'];
			$_SESSION['user_type'] = $user_type = $res['data'][0]['user_type'];
			update_data('op_user', array('token' => $token, 'status' => 'ACTIVE'), $user_id);					
			$res['url'] = $base_url.'index';
			$res['msg'] =  "OTP Verified Successfully";
			session_regenerate_id();
		} else {
			$res['status'] =  "error";
			$res['msg'] =  "OTP Not Matched";
			$res['url'] =  $_SERVER['HTTP_REFERER'];
		}
		echo json_encode($res);
		break;


		case "master_delete": // Delete Any Data From Table 
			extract($_POST);
			if ($_SESSION['user_type'] == 'ADMIN') {
				$searchdata  = get_data($table, $id);
				
				if ($searchdata['count'] > 0) {
					if($table=='op_master_table')
					{
						$col_name = $searchdata['data']['column_name'];
						$table_name = $searchdata['data']['table_name'];
						remove_column($table_name, $col_name);
					}
					$res = delete_data($table, $id, $pkey);
				}
			} else {
				$res = array('msg' => "Don't  have permission", 'status' => 'error');
			}
			//$notice  = "Record id $id is deleted from $table by {$_SESSION['user_name']}";
			// add_notice($notice);
			echo json_encode($res);
			break;
		
		case "master_remove": // Delete Any Data From Table 
			extract($_POST);
			$res = remove_data($table, $id);
			echo json_encode($res);
			break;
			
			
		case "master_delete_multiple": // Delete Selected Record Any Data From Table 
			extract($_POST);
			if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV' ) {
				
				foreach($sel_id as $id)
				{
				$searchdata  = get_data($table_name, $id);
					if ($searchdata['count'] > 0) {
						$info[] = delete_data($table_name, $id);
					}
				}
				$res['status'] ='success';
				$res['msg'] = count($sel_id) ." record(s) deleted sucessfully";
				$res['data'] =$info;
			} else {
				$res = array('msg' => "Don't  have permission", 'status' => 'error');
			}
			echo json_encode($res);
			break;
	
		case "master_remove_multiple": // Delete Selected Record Any Data From Table 
			extract($_POST);
			if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV' ) {
				
				foreach($sel_id as $id)
				{
				$searchdata  = get_data($table_name, $id);
					if ($searchdata['count'] > 0) {
						$info[] = update_data($table_name, array('status'=>'DELETED'), $id);
					}
				}
				$res['status'] ='success';
				$res['msg'] = count($sel_id) ." record(s) removed sucessfully";
				$res['data'] =$info;
			} else {
				$res = array('msg' => "Don't  have permission", 'status' => 'error');
			}
			echo json_encode($res);
			break;	

		case "add_template": // SMS SMS any TIME
			extract($_POST);
			$_POST['status'] = 'ACTIVE';
			$res = insert_data('sms_template', $_POST);
			echo json_encode($res);
			$notice  = "New SMS Template is added by {$_SESSION['user_name']}";
			// add_notice($notice);
			break;

		case "send_sms" : // SMS any TIME
		    //$_POST['mobile'] = preg_replace('#\s+#',',',trim($mobile));
		   	//print_r($_POST);
		   	extract($_POST);
		   	$templateid = get_data('sms_template',$template_id, 'template_id')['data'];
		   	$ctype = get_data('sms_template',$template_id, 'content_type')['data'];
			$res = send_msg($mobile,$sms,$templateid,$ctype);
			//wa_text($mobile,$sms);
			$notice  = "SMS send by {$_SESSION['user_name']} on $mobile";
			// add_notice($notice);
			echo json_encode($res);
			break;

		case "group_sms" : // SMS SMS any TIME
				//print_r($_POST);
				$template_id = $_POST['template_id'];
				$templateid = get_data('sms_template',$template_id, 'template_id')['data'];
				$ctype = get_data('sms_template',$template_id, 'content_type')['data'];
				$msg_no = $_POST['msg_no'];
				$section = $_POST['section'];
				$sms = $_POST['msg_text'];
			
				if($section !='')
				{
					$mobile =get_all('student',array('student_mobile'),array('status'=>'ACTIVE','student_class'=>$msg_no,'student_section'=>$section))['data'];
				}
				else{
					if($msg_no =='ALL_STUDENT')
					{
						$mobile =get_all('student',array('student_mobile'),array('status'=>'ACTIVE'))['data'];
					}
					
					else if($msg_no =='ALL_STAFF')
					{
						$mobile =get_all('employee',array('e_mobile'),array('status'=>'ACTIVE'))['data'];
					}
					else{
						$mobile =get_all('student',array('student_mobile'), array('student_class'=>$msg_no))['data'];
					}
				}
				$mobile = implode(',',array_column($mobile,'student_mobile'));
				$notice  = "Group SMS Send by {$_SESSION['user_name']}";
			    // add_notice($notice);
				$res = send_msg($mobile,$sms, $templateid,$ctype);
				echo json_encode($res);
				break;

		case "master_block": // BLOCK Any Data From Table 
			extract($_POST);
			//print_r($_POST);
			$bdata = array('status' => 'BLOCK');
			$res = update_data($table, $bdata, $id, $pkey);
			$notice  = "Record Id $id of $table is blocked by {$_SESSION['user_name']}";
			// add_notice($notice);
			echo json_encode($res);
			break;
		
		case "active_block": // Active Block Any Data From Table 
			extract($_POST);
			$ndata = array('status' => $status);
			$res = update_data($table, $ndata, $id);
			echo json_encode($res);
			break;
		
		case "logout":
			$rtype = 'direct';
			extract($_POST);
			if (isset($_SESSION['bine_token']) and $_SESSION['bine_token'] != '') {
				$user_id = $_SESSION['user_id'];
				$user_type = $_SESSION['user_type'];
				$result = update_data('op_user', array('token' => '', 'status' => 'LOGOUT'), $user_id);
				echo json_encode($result);
				if ($result['status'] == 'success') {
					unset($_SESSION['user_name']);
					unset($_SESSION['user_type']);
					unset($_SESSION['user_id']);
					unset($_SESSION['bine_token']);
					session_destroy();
					$url = $base_url.'login';
				} else {
					$url = $base_url.'dashboard';
				}
			} else {
				$url = 'login';
			}
			if ($rtype == 'AJAX') {
				$result['url'] = $url;
				json_encode($result);
			} else {
				$result['url'] = 'login';
				echo "<script> window.location ='".$base_url."login.php' </script>";
				json_encode($result);
			}
			//$notice  = "{$_SESSION['user_name']} logout";
			// add_notice($notice);
			break;

        case "forget_password":
			$user_name  = $_POST['user_name'];
			$sql = "select * from op_user where user_name ='$user_name' and status not in ('AUTO','DELETED')";
			$res = direct_sql($sql);
			if ($res['count'] > 0) {
				$id = $res['data'][0]['id'];
				$user_type = $res['data'][0]['user_type'];
				$email = $res['data'][0]['user_email'];
				$mobile = $res['data'][0]['user_mobile'];
				$name = $res['data'][0]['full_name'];

				// $np = rnd_str(6);
				// $up = array('password' => md5($np));
				// $res = update_data($user_type, $up, $id, 'id');
				$link  = encode("user_name=$user_name&user_id=$id");
				$rlink = $base_url."op_reset_password?link=$link";
				$sms = "Dear  $name  \n Click on below link $rlink  to change password. \n  Kindly change after login  $inst_name";
				send_mail($email, "Password Recover of $inst_name ", $sms);
				//$sms_i= "Dear $name, \nYour Online Law OTP / EVC / Password is: $rlink \nRegards \nOLAWIN \nOfferPlant";
				//bulk_sms($mobile,$sms);
				$data['id'] = $id;
				$data['status'] = 'success';
				$data['msg'] = "Reset Link Successfully Send to $email";
			} else {
				$data['id'] = 0;
				$data['status'] = 'error';
				$data['msg'] = 'No any user exist with this ID. Try Again';
			}
			echo json_encode($data);
			break;
        
        case "payment_mode" :
            extract($_POST);
            //print_r($_POST);
            $rid =$_POST['receipt_id'];
            $payment_mode =$_POST['payment_mode'];
               $payment_mode = ($payment_mode =='Cash')?'Bank':'Cash'; 
            $udata  = array('payment_mode'=>$payment_mode);
            //print_r($udata);
            $res2 = update_data('receipt',$udata,$rid);
            if($res2['status'] =='success'){
            $res2['msg'] = "Payment mode changed successfully";
            }
            echo json_encode($res2);
            break;
        
	

		case "bulk_import":
			extract($_POST);
			echo "<pre>";
			$res = csv_import($table, $pkey);
			print_r($res);
			//echo "<script> window.location='bulk_import.php?res=".json_encode($res)."' </script>";
			break;


		case "bulk_export":
			if ($_SESSION['user_type'] == 'Admin') {
				$status = $_GET['status'];
				csv_export($_REQUEST['table']);
			}
			break;

		case "update_user":
		    unset($_POST['isedit']);
			unset($_POST['table_name']);
			$_POST['user_name'] = remove_space($_POST['user_name']);
			$_POST['user_pass'] = md5($_POST['user_pass']);
			$res = update_data('user', $_POST, $_POST['id']);
			//$notice  = "User Info of {$_POST['user_name']} updated  by {$_SESSION['user_name']}";
			// add_notice($notice);
			echo json_encode($res);
			break;

        case "update_profile":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			if($_POST['user_pass']!='')
			{
			$_POST['user_pass']= md5($_POST['user_pass']);
			}
			else{
			    unset($_POST['user_pass']);
			}
			$res = update_data($table_name, $_POST, $id);
			if($send_mail=='YES')
			{
				$msg = "Thanks for Joining $inst_name , Your Login Details is as follows User Name : $user_name & Password : $user_pass";
				mail($user_email, "New User Registration",  $msg );
			}
			echo json_encode($res);
			break;

	
    	case "save_photo" :
				$baseFromJavascript = $_POST['student_photo']; //your data in base64 'data:image/png....';
                $base_to_php = explode(',', $baseFromJavascript);
                $data = base64_decode($base_to_php[1]);
                $file_name = date('ymdhis')."_".rnd_str(5).".png";	
                $filepath = "upload/image.png "; //.$file_name; // or image.jpg
                file_put_contents($filepath,$data);
                rename($filepath, 'upload/'.$file_name);
                $res['msg'] = "The file ". $file_name. " has been uploaded.";
                $res['id'] = $file_name;
				$res['status'] ='success';
				echo json_encode($res);
				break;

			/*---------------LIBRARY MANAGMENT ----------------*/

		/*---------------Account MANAGMENT ----------------*/


		
		case "add_config":
			unset($_POST['table_name']);
			unset($_POST['isedit']);
			$res = update_data('op_config', $_POST,$_POST['id']);
			echo json_encode($res);
			break;


		case "update_config":
			extract($_POST);
			$res = update_data('op_config', array('option_value'=>$option_value),$option_name, 'option_name');
			echo json_encode($res);
			break;

		case "update_settings":
			$alldata = $_POST;
			$ct = 0;
			foreach((array) $alldata as $key=>$value)
			{
				$res = update_data('op_config', array('option_value'=>$value), $key,'option_name');
				if($res['status'] =="success")
				{
					$ct++;
				}
			}
			$res['msg'] = $ct . " Update found";
			$res['status'] = "success";
			echo json_encode($res);
			break;

		case "reset_config":
			//$res = update_multi_data('op_config', array(option_value=>default_value), array('status'=>'ACTIVE'));
			$sql ="update op_config set option_value = default_value";
			$res = direct_sql($sql,'set');
			echo json_encode($res);
			break;
		
		
		case "add_menu":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			//$_POST['table_id'] = get_data('op_table',$table_id,'table_id')['data']; // Get Table Name
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = 'op_menu_manage';	
			}
			else{
				$res['url'] = 'op_menu_add';
			}
			echo json_encode($res);
			break;
		
		case "send_chat":
			extract($_POST);
			$res = insert_data('op_msg', array('message'=>$message,'to_user'=>$to_user));
			echo json_encode($res);
			break;
			
		case "bulk_menu_update":
			extract($_POST);
			foreach($sel_id as $id)
			{
			$res = update_data('op_menu', array('parent'=>$parent), $id);
			}
			echo json_encode($res);
			break;
			
		case "quick_update_data": // Double Click to Edit and Save
			extract($_POST);
			unset($_POST['table_name']);
			unset($_POST['value']);
			unset($_POST['column']);
			$_POST[$column] =$value;
			$_POST = array_to_string($_POST);
			$res = update_data($table_name, $_POST, $id);
			echo json_encode($res);
			break;
      
		// MINIBANK ACTIONS 

	
			
		case "op_wa":
			extract($_POST);
		    $res = op_wa($mobile, $message);
			echo json_encode($res);
			break;	
			
			 // New Process in Gold Statrt	
		case "next_date_entry":
		    extract($_POST);
		    $case = get_data('case_reg', $case_id)['data'];
		   // $party_name = get_data('party_name', $case_id)['data'];
		    $party_name= $case['party_name'];
		    $case_type_id = $case['case_type_id'];
		    $contact = $case['mobile'];
		    $email = $case['email'];
		    $case_number = $case['case_number'];
		    $case_year = $case['case_year'];
		    $court_location = $case['court_location'];
		    $_POST['case_type_id'] = $case['case_type_id'];
		    $_POST['court_id'] = $case['court_id'];
		    $_POST['present_date'] = $case['next_date'];
		    $_POST['case_number'] = $case['case_number'];
		    $_POST['case_year'] = $case['case_year'];
		    $_POST['court_location'] = $case['court_location'];
		    $_POST['user_id'] = $case['user_id'];
		    $userdata=get_data('op_user',$user_id)['data'];
		    //$user_name = $case['user_name'];
		    $res =insert_data('case_status', $_POST);
		    $res =update_data('case_reg', ['next_date'=>$next_date, 'date_source'=>$date_source ], $case_id);
		    
 		    //$sms ="Dear ". $party_name .", your next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ."\nBy \n".$userdata['full_name'] ."\n".$userdata['user_mobile'] ."\nRegards \nOnline Law";	
 		    
 		    //$sms= "Dear \nyour next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ." \nBy ".$userdata['full_name'] ."\n".$userdata['user_mobile'] ." \nRegards \nOLAWIN \nOFFERPLANT"; 	//From 07 March 2025 
 		    $sms= "Dear \nYour next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ." \nBy ".$userdata['full_name'] ."\n".$userdata['user_mobile'] ." \nRegards \nCaseOS \nOFFERPLANT"; 	//From 29 March 2026 
 		    
 		    //$wa_text ="Your next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ;
 		   // add_to_whatsapp($contact, $party_name, $email, $case_number."/".$case_year);
 		   // send_wa_template($contact, 'olaw_general', $party_name. $wa_text);
 		    
            sendsms1($contact,$sms);
            wallet_amount($_SESSION['user_id'], $service_fee, 'DEDUCT', 'Next Date Update Fee');
             if($email!='')
 		    {
            $msg_email = '<div class="container" style="max-width: 600px; margin: 50px auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
                <h4 style="color: #333;">Next Date Updates </h4>
                <p style="color: #555;">Dear  '.$party_name.' , Your next date is '.date('d-M-Y',strtotime($_POST['next_date'])).' in '.casetypeinfo($case_type_id,'name').' : '.$case_number. '/' .$case_year.' </p>
                <p style="color: #555;">You may also got notification via SMS as CaseOS.</p>
                <a class="reset-link" href="{$base_url}" style="display: inline-block; padding: 10px 20px; background-color: #007BFF; color: #fff; text-decoration: none; border-radius: 5px;">Next Date : '.date('d-M-Y',strtotime($_POST['next_date'])).' </a>
                <p style="color: #555;">If you have any issues, please contact <b> '.$userdata['full_name']. ' / Call '.$userdata['user_mobile'].'</b> </p>
                <p style="color: #555;">This mail is auto generated mail from your Advocates / Legal Professionals.</p>
                <br>
                Regards<br>
                <b>'. $inst_name.'
                     </div>';
			send_mail($email, "Next Date Update Notification from $inst_name ", $msg_email);
 		    }
             
            send_whatsapp($contact,$sms);
            $res['msg'] ="Next Date Updated Successfully";
            $res['modal'] ='myModal';
            echo json_encode($res);
            break;

        case "next_date_sms":
		    extract($_POST);
		    $case = get_data('case_reg', $case_id)['data'];
		   // $party_name = get_data('party_name', $case_id)['data'];
		    $party_name= $case['party_name'];
		    $case_type_id = $case['case_type_id'];
		    $contact = $case['mobile'];
		    $email = $case['email'];
		    $case_number = $case['case_number'];
		    $case_year = $case['case_year'];
		    $_POST['case_type_id'] = $case['case_type_id'];
		    $_POST['court_id'] = $case['court_id'];
		    $_POST['present_date'] = $case['next_date'];
		    $_POST['case_number'] = $case['case_number'];
		    $_POST['case_year'] = $case['case_year'];
		    $_POST['user_id'] = $case['user_id'];
		    $userdata=get_data('op_user',$user_id)['data'];
		   
 		    //$sms ="Dear ". $party_name .", your next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ."\nBy \n".$userdata['full_name'] ."\n".$userdata['user_mobile'] ."\nRegards \nOnline Law";
 		   
 		    //$sms= "Dear \nyour next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ." \nBy ".$userdata['full_name'] ."\n".$userdata['user_mobile'] ." \nRegards \nOLAWIN \nOFFERPLANT"; 	//From 07 March 2025 
 		    $sms= "Dear \nYour next date is ". date('d-M-Y',strtotime($_POST['next_date'])) ." in ". casetypeinfo($case_type_id,'name'). " : ". $case_number. "/" .$case_year ." \nBy ".$userdata['full_name'] ."\n".$userdata['user_mobile'] ." \nRegards \nCaseOS \nOFFERPLANT"; 	//From 29 March 2026 
            sendsms1($contact,$sms);
            wallet_amount($_SESSION['user_id'], $service_fee, 'DEDUCT', 'Resend Next Date SMS');
             if($email!='')
 		    {
           $msg_email = '<div class="container" style="max-width: 600px; margin: 50px auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
                <h4 style="color: #333;">Next Date Updates </h4>
                <p style="color: #555;">Dear  '.$party_name.' , Your next date is '.date('d-M-Y',strtotime($_POST['next_date'])).' in '.casetypeinfo($case_type_id,'name').' : '.$case_number. '/' .$case_year.' </p>
                <p style="color: #555;">You may also got notification via SMS as CaseOS. </p>
                <a class="reset-link" href="{$base_url}" style="display: inline-block; padding: 10px 20px; background-color: #007BFF; color: #fff; text-decoration: none; border-radius: 5px;">Next Date : '.date('d-M-Y',strtotime($_POST['next_date'])).' </a>
                <p style="color: #555;">If you have any issues, please contact <b> '.$userdata['full_name']. ' / Call '.$userdata['user_mobile'].'</b> </p>
                <p style="color: #555;">This mail is auto generated mail from your Advocates / Legal Professionals.</p>
                <br>
                Regards<br>
                <b>'. $inst_name.'
                     </div>';
			send_mail($email, "Next Date Update Notification from $inst_name ", $msg_email);
 		    }
            send_whatsapp($contact,$sms); // for sendind whatapp http://148.251.129.118/login
            $res['msg'] ="Next Date SMS Sent Successfully";
            $res['status'] ="success";
            $res['modal'] ='myModal';
            echo json_encode($res);
            break;

        case "update_mobile_email": // Change Date form Complete System
			extract($_POST);
			$case = get_data('case_reg', $case_id)['data'];
		    unset($_POST['mobile']);
		    unset($_POST['email']);
		    $res =update_data('case_reg', ['mobile'=>$mobile, 'email'=>$email ], $case_id);
		   	$res['msg'] ="Mobile and Email Added Successfully";
            $res['url'] ='mobile_add';
			echo json_encode($res);
			break;
        case "update_cnr": // Change Date form Complete System
			extract($_POST);
			$res =update_data('case_reg', ['cnr'=>$cnr ], $id);
		   	$res['msg'] ="CNR Added Successfully";
            $res['modal'] ='myModal';
			echo json_encode($res);
			break;
			
        case "ref_reg":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$_POST['user_id'] =$_SESSION['user_id'];
			
			$res = update_data($table_name, $_POST, $id);
			$find  = get_data('op_user',$mobile,null,'user_name');
		    if($find['count']==0)
			{
			 $user_pass=rand(100000,999999);
			 $password = md5($user_pass);
			insert_data('op_user',['full_name'=>$name, 'user_name'=>$mobile,'user_mobile'=>$mobile,'user_email'=>$email,'status'=>'ACTIVE', 'user_status'=>'ACTIVE','user_doc'=>$today,'user_type'=>$ref_type,'user_pass'=>$password]);
			 if($email!='')
 		    {
			$msg_email = '<div class="container" style="max-width: 600px; margin: 50px auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
                <h4 style="color: #333;">Next Date Updates </h4>
                <p style="color: #555;">Dear  '.$name.' , Your are registered a reference person. Details sent on '.$mobile.' and email '.$email.'   </p>
                <p style="color: #555555;"><strong>Username:</strong> <span style="color: #007bff;">'.$mobile.'</span></p>
                <p style="color: #555555;"><strong>Password:</strong> <span style="color: #007bff;">'.$user_pass.'</span></p>
                <a class="reset-link" href="https://ediary.olaw.in" style="display: inline-block; padding: 10px 20px; background-color: #007BFF; color: #fff; text-decoration: none; border-radius: 5px;"> Login Here </a>
                
                <p style="color: #555;">This mail is auto generated mail from Advocates / Legal Professionals.</p>
                <br>
                Regards<br>
                <b>'. $inst_name.'
                     </div>';
			send_mail($email, "Reference Person Registration from $inst_name ", $msg_email);
 		    }
			}
			else{
			    $fid  = $find['data']['id'];
			 	update_data('op_user',['full_name'=>$name, 'user_name'=>$mobile,'user_mobile'=>$mobile,'user_email'=>$email,'status'=>'ACTIVE','user_doc'=>$today,'user_type'=>$ref_type], $fid);   
			}
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;
        // New Proces in Gold End
        
        // Get Courrt List from Courrt Location 
           case "get_court_list": // 
			extract($_GET);
			$res =get_all('court_reg', '*', ['court_location'=>$court_location ]);
			$str ='';
			foreach((array)$res['data'] as $row)
			{
			    $str .="<option value ='{$row['id']}' > {$row['court_designation']} </option>";
			}
		   	echo $str;
			break;
		
		case "add_to_wallet":
		    extract($_POST);
		    $res = wallet_amount($user_id, $txn_amount, 'ADD', $txn_remarks,$txn_date);
		    echo json_encode($res);
		    break;
			
		default:
			echo "<script> alert('Invalid Action'); window.location ='" . $_SERVER['HTTP_REFERER'] . "' </script>";
	}
}
