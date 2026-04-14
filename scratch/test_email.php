<?php
require_once(__DIR__ . '/../function.php');

$to = "ask@offerplant.com"; // Default test email from config
$subject = "Test Email from CaseOS";
$msg = "This is a test email to verify the mail configuration in CaseOS.";

echo "Attempting to send email to: $to...\n";

if (send_mail($to, $subject, $msg)) {
    echo "Success: Email sent successfully!\n";
} else {
    echo "Error: Failed to send email. Check SMTP settings and error logs.\n";
}
?>
