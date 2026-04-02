<?php
require_once('function.php');
$queries = [
    // Update payment_transactions ENUM and default
    "ALTER TABLE payment_transactions MODIFY COLUMN plan_type ENUM('verified','premium','CASEOS') DEFAULT 'CASEOS'",
    
    // Update payment_logs default
    "ALTER TABLE payment_logs MODIFY COLUMN plan_type VARCHAR(20) DEFAULT 'CASEOS'"
];

foreach($queries as $q) {
    if(mysqli_query($con, $q)) {
        echo "SUCCESS: $q\n";
    } else {
        echo "ERROR: " . mysqli_error($con) . " in $q\n";
    }
}
