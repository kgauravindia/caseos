<?php
require_once('function.php');
$res = get_data('op_settings', 1);
echo "Settings for ID 1:\n";
print_r($res['data']);
?>
