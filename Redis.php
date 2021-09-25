<?php
require "vendor/autoload.php";

$host = "localhost";
$port = 6379;
$password = "";
$topic = "sms";

$message = json_encode(array(
    "command"=>"send-sms",
    "data"=>array(
        "recipient"=> "6281111111111",
        "message"=>"OTP Anda adalah 123456"
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
