<?php

require_once "OTPPi.php";

$url = "http://localhost:8899/api/otp";
$username = 'kamshory';
$password = 'kamshory';

/**
 * Parameters to be sent on request OTP
 */

$receiver = '0812661111';

// OTP ID
$id = time();

// OTP life time (in second)
$lifetime = 30;

// OTP message 
$messageFormat = 'Kode OTP Anda adalah 123456 ';

// Subject (used if receiver is email address)
$subject = 'Kode OTP Anda';

// Additional data from the transaction (must be identic with validate OTP)
$params = array(
    '123456',
    '7890',
    '98765',
    '64875384'
);

$otp = new OTPPi($url, $username, $password);
$resp1 = $otp->sendSMS($receiver, $id, $lifetime, $message);

$createResponse = json_decode($resp1, true);
echo "Send SMS : ".$createResponse['response_code']."<br>\r\n";

/*
Response Code List
==============================
SUCCESS              = "0000";
SERIAL_PORT_NULL     = "1000";
UNAUTHORIZED         = "1100";
NO_DEVICE_CONNECTED  = "1101";
FAILED               = "1102";
*/

?>