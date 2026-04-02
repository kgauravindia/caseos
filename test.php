<pre>
<?php require_once("function.php");
echo $_SESSION['user_id'];
//echo $_SESSION['full_name'];
//echo get_data('op_user', $_SESSION['user_id'], 'user_email');
$userdata=get_data('op_user',$user_id)['data'];
echo $userdata['full_name'];
echo $wa_api_key = $userdata('whatsapp_api');
?>
