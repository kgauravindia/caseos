<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//include_once('../function.php');
require_once "config.php";

//print_r($_POST);

if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['mobile']) && isset($_POST['amount'])) {
    $demand_id = $_POST['demand_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $amount = $_POST['amount'];


 //   session_start();

    $_SESSION['name']  = $name;
    $_SESSION['email']  = $email;
    $_SESSION['mobile']  = $mobile;



if (API_STATUS == "LIVE") {
    $merchantid  = MERCHANTIDLIVE;
    $saltkey = SALTKEYLIVE;
    $saltindex = SALTINDEX;
    $pay_url = LIVEURLPAY;
} else {
      
    $merchantid  = MERCHANTIDUAT;
    $saltkey = SALTKEYUAT;
    $saltindex = SALTINDEX;
    $pay_url = UATURLPAY;
    
}


    $txn_id = "ARPAY-" . getTransactionID();
    $mid = 'M-'.$_SESSION['user_name'].uniqid();
    
    //update_data('online_payment',['payment_amount'=>$amount, 'total_amount'=>$amount,'txn_id'=>$txn_id,'status'=>'PENDING'], $demand_id, 'demand_id');
     
    $payLoad = array(
        'merchantId' => $merchantid,
        'merchantTransactionId' => $txn_id, // test transactionID
        "merchantUserId" => $mid,
        'amount' => $amount * 100, // phone pe works on paise
        'redirectUrl' => BASE_URL . REDIRECTURL,
        'redirectMode' => "POST",
        'callbackUrl' => BASE_URL . REDIRECTURL,
        "mobileNumber" => $mobile,
        "email" => $email,
        // "param1"=>$email,
        "paymentInstrument" => array(
            "type" => "PAY_PAGE",
        )
    );


    $jsonencode = json_encode($payLoad);

    $payloadbase64 = base64_encode($jsonencode);

    $payloaddata = $payloadbase64 . "/pg/v1/pay" . $saltkey;


    $sha256 = hash("sha256", $payloaddata);

    $checksum = $sha256 . '###' . $saltindex;

    echo $checksum;


    $request = json_encode(array('request' => $payloadbase64));

    $url = '';
    if (API_STATUS == "LIVE") {
        $url = LIVEURLPAY;
    } else {
        $url = UATURLPAY;
    }

    echo "<br/>" . $url;

    $curl = curl_init(); // This extention should be installed

    curl_setopt_array($curl, [
        CURLOPT_URL => $pay_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $request,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-VERIFY:" . $checksum,
            "accept: application/json"
        ],
    ]);

    $response = curl_exec($curl);

    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      $res = json_decode($response);

      echo "<br/>response===";
      print_r($res);

      if (isset($res->success) && $res->success == '1') {
        // $paymentCode=$res->code;
        // $paymentMsg=$res->message;
        $payUrl = $res->data->instrumentResponse->redirectInfo->url;
        header('Location:' . $payUrl);
      }

    }
}
