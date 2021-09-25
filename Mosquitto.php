<?php
require "vendor/autoload.php";

$server   = '127.0.0.1';
$port     = 1883;
$clientId = 'php';
$topic = 'sms';

$message = json_encode(array(
    "command"=>"send-sms",
    "data"=>array(
        "recipient"=> "6281111111111",
        "message"=>"OTP Anda adalah 123456"
    )
));

$mqtt = new \PhpMqtt\Client\MqttClient($server, $port, $clientId);
$mqtt->connect();
$mqtt->publish($topic, $message, 0);
$mqtt->disconnect();

?>