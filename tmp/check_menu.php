<?php
require_once('function.php');
$res = mysqli_query($con, "SELECT id, title, link FROM op_menu LIMIT 50");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['id'] . ' | ' . $row['title'] . ' | ' . $row['link'] . "\n";
}
