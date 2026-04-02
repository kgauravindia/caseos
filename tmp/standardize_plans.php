<?php
require_once('function.php');
$queries = [
    // Update payment_orders
    "ALTER TABLE payment_orders MODIFY COLUMN plan_type ENUM('verified','premium','CASEOS') DEFAULT 'CASEOS'",
    
    // Ensure payment_transactions is updated (re-running for safety)
    "ALTER TABLE payment_transactions MODIFY COLUMN plan_type ENUM('verified','premium','CASEOS') DEFAULT 'CASEOS'",
    
    // Ensure payment_logs is updated
    "ALTER TABLE payment_logs MODIFY COLUMN plan_type VARCHAR(20) DEFAULT 'CASEOS'"
];

foreach($queries as $q) {
    if(mysqli_query($con, $q)) {
        echo "SUCCESS: $q\n";
    } else {
        echo "ERROR: " . mysqli_error($con) . " in $q\n";
    }
}
