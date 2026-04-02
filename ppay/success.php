<?php
ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include_once('../temp/function.php'); 
?>
<html>
  <head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
  </head>
    <style>
      body {
        text-align: center;
        padding: 40px 0;
        background: #EBF0F5;
      }
        h1 {
          color: #88B04B;
          font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
          font-weight: 900;
          font-size: 40px;
          margin-bottom: 10px;
        }
        p {
          color: #404F5E;
          font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
          font-size:20px;
          margin: 0;
        }
      i {
        color: #9ABC66;
        font-size: 100px;
        line-height: 200px;
        margin-left:-15px;
      }
      .card {
        background: white;
        padding: 60px;
        border-radius: 4px;
        box-shadow: 0 2px 3px #C8D0D8;
        display: inline-block;
        margin: 0 auto;
      }
    </style>
    <body>
      <div class="card">
      <div style="border-radius:200px; height:200px; width:200px; background: #F8FAF5; margin:0 auto;">
        <i class="checkmark">✓</i>
      </div>

        <h1>Success</h1>
        <p>Transaction ID : <?php echo $_GET['tid']; ?></p>
        <p>Amount : <?php echo $_GET['amount']/100; ?></p>
        <?php 

            //Updating Wallet With Amount 
            
                $txn = get_data('online_payment',$_GET['txn_id'],null,'txn_id')['data'];
                $center = get_data('center_details',$txn['center_code'],null,'center_code')['data'];
                update_data('online_payment',['status'=>'SUCCESS','payment_id'=>$_GET['tid']],$_GET['txn_id'],'txn_id');
                
                $txn_id  = $_GET['txn_id'];
                $credit_amt = $_GET['amount']/100;
        	    
				$cbal =$center['center_wallet'];
				$nbal =$cbal+ $credit_amt;
				$check_txn = get_all('wallet','*',['txn_remarks'=>$txn_id]);
				
				if($check_txn['count']==0)
				{
				$res = insert_data('wallet', ['center_id'=>$center['id'], 'txn_date'=>$today,'txn_remarks'=>$txn_id ,'credit_amt'=>$credit_amt,'balance'=>$nbal,'status'=>'SUCCESS']);
				update_data('center_details', array('center_wallet'=>$nbal), $center['id'],'id');
				//$wa_sms =  "Dear *{$center['center_name']}*, Your Wallet recharge with ₹ *$credit_amt* and current balance is ₹ *$nbal*.";
				//wa_send($center['center_mobile'],$wa_sms);
				check_offer($credit_amt, $center['id']);
				}
				
        ?>
        <p>We received your payment request;<br/> we'll be in updated shortly!</p>
        <a href='<?=$base_url ?>client_index' ?> Back to Dashboard </a>
      </div>
    </body>
</html>