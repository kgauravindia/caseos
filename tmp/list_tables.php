<?php
require_once('function.php');
$res = mysqli_query($con, "SHOW TABLES");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . PHP_EOL;
}
