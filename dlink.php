<?php  
$original_string = "Welcome to JavaTpoint With Dynamic Functions \n";  
// Print the original input string  
echo "Original String: " .$original_string;  
// Store the cipher method   
$ciphering_value = "BF-CBC";   
$iv_length = openssl_cipher_iv_length($ciphering_value);  
$options = 0;  
$encryption_iv_value = random_bytes($iv_length);  
// Store the encryption key  
$encryption_key =  openssl_digest(php_uname(), 'MD5', TRUE);  
// Use openssl_encrypt() function   
$encryption_value = openssl_encrypt($original_string, $ciphering_value, $encryption_key, $options,$encryption_iv_value);   
// Display the encrypted input string data  
echo "<br><br> Encrypted Input String: " . $encryption_value  . "\n";  
$decryption_key =  openssl_digest(php_uname(), 'MD5', TRUE);  
// Use openssl_decrypt() function to decrypt the data  
$decryption_value = openssl_decrypt($encryption_value, $ciphering_value, $decryption_key, $options,$encryption_iv_value);   
// Display the decrypted string as an original data  
echo "<br><br> Decrypted Input String: " .$decryption_value. "\n";  
?>  