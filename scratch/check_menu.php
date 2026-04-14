<?php
require_once('../op_config.php');
require_once('../system/op_lib.php');

$res = direct_sql("SELECT * FROM op_menu WHERE link LIKE '%recharge%'");
echo "Menu Count: " . $res['count'] . "\n";
if ($res['count'] > 0) {
    foreach($res['data'] as $m) {
        echo "ID: " . $m['id'] . " Title: " . $m['title'] . " Link: " . $m['link'] . "\n";
    }
}
?>
