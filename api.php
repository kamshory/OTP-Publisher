<?php
require_once dirname(__FILE__)."/otp-pi/autoload.php";

$otppi = new OTPPi("user", "pass");

/**
 * Parameters to be sent on request OTP
 */

$receiver = '0812661111';

// Reference number from the transaction (generated by your application)
$reference = md5(time());
//$reference = "12345678";

// OTP ID
$id = time();

// OTP life time (in second)
$lifetime = 60;

// OTP message format (must be contains one %s)
$messageFormat = 'Kode OTP Anda adalah %s';

// Subject (used if receiver is email address)
$subject = 'Kode OTP Anda';

// Additional data from the transaction (must be identic with validate OTP)
$params = array(
    '123456',
    '7890',
    '98765',
    '64875384'
);

$clearOTP = "329180";

//$response = $otppi->createOTP($receiver, $id, $reference, $lifetime, $messageFormat, $params, $subject);
//$response = $otppi->verifyOTP($receiver, $clearOTP, $reference, $params);
//$response = $otppi->requestUSSD("*888#", "20570ff7f9a4664df11e8c3dfdf4c6c4");
if(isset($_POST))
{
    $response = $otppi->request(
        array(
            "command"=>"set-gpio-value",
            "callback_topic"=>"gpio-".mt_rand(100000, 999999),
            "callback_delay"=>50,
            "data"=>array(
                "gpio"=>$_POST['gpio']*1,
                "value"=>$_POST['value']*1,
                "date_time"=>time()
            )
        )
        
    );
    echo json_encode($response);
}


?>