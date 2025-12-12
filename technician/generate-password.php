<?php
$plain_password = 'apay123';  // Choose a password
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

echo "Plain Password: " . $plain_password . "\n";
echo "Hashed Password: " . $hashed_password . "\n";
echo "\nCopy the hashed password for the SQL query below.\n";
?>