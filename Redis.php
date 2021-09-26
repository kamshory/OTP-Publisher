<?php
require "vendor/autoload.php";

$host = "localhost";
$port = 6379;
$password = "";
$topic = "sms";

$message = json_encode(array(
    "command"=>"send-sms",
    "data"=>array(
        "date_time"=>1629685778,
        "expiration"=>1629685838,
        "id"=>123456,
        "recipient"=>"6281111111111",
        "receiver"=>"OTP Anda adalah 123456"
    )
));

if(!empty($password))
{
    $redis = new Predis\Client(['host' => $host, 'port' => $port, 'password' => $password]);
}
else
{
    $redis = new Predis\Client(['host' => $host, 'port' => $port]);
}

$redis->publish($topic, $message);
