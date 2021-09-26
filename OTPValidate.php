<?php

require_once "OTPPi.php";

$url = "http://localhost:8899/api/otp";
$username = 'kamshory';
$password = 'kamshory';

/**
 * Parameters to be sent on validate OTP
 */

$receiver = '0812661111';

// Reference number from the transaction
$reference = 'dc8db28ce90a3cacbc180c72769fa0cd';

// Clear OTP received by reveicer
$clearOTP = '712032';

// Additional data from the transaction (must be identic with request OTP)
$params = array(
    '123456',
    '7890',
    '98765',
    '64875384'
);

$otp = new OTPPi($url, $username, $password);
$resp2 = $otp->validateOTP($receiver, $clearOTP, $reference, $params);

$validateResponse = json_decode($resp2, true);
echo "Validate OTP : ".$validateResponse['response_code']."<br>\r\n";

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