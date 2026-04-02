<?php

define("BASE_URL", "https://ediary.olaw.in/ppay/");
define("API_STATUS", "LIVE"); //LIVE OR UAT
define("MERCHANTIDLIVE", "M22SNEU85BA56");
define("MERCHANTIDUAT", "PGTESTPAYUAT");  //Sandbox testing
define("SALTKEYLIVE", "847dca05-ae03-4208-8c4c-d74ecbe1951d");
define("SALTKEYUAT", "099eb0cd-02cf-4e2a-8aca-3e6c6aff0399");
define("SALTINDEX", "1");
define("REDIRECTURL", "paymentstatus.php");
define("SUCCESSURL", "success.php");
define("FAILUREURL", "failure.php");
define("UATURLPAY", "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay");
define("LIVEURLPAY", "https://api.phonepe.com/apis/hermes/pg/v1/pay");
define("STATUSCHECKURL", "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/");
define("LIVESTATUSCHECKURL", "https://api.phonepe.com/apis/hermes/pg/v1/status/");


function getTransactionID(){

    return rand(1111111111,99999999999);
}


?>