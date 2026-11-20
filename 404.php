<?php
require_once('op_config.php');
require_once('system/op_error_handler.php');

$requested = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'page');
render_caseos_error(
    "Page Not Found",
    "The page or resource you requested (<code>$requested</code>) does not exist or has been moved.",
    null,
    404
);
