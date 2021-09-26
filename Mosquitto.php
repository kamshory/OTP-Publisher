<?php
require "vendor/autoload.php";

$server   = '127.0.0.1';
$port     = 1883;
$clientId = 'php';
$topic = 'sms';
$username = 'user';
$password = 'pass';

$message = json_encode(array(
    "command"=>"send-sms",
    "data"=>array(
        "date_time"=>1629685778,
        "expiration"=>1629685838,
        "id"=>123456,
        "receiver"=>"6281111111111",
        "message"=>"OTP Anda adalah 123456"
    )
));


$connectionSettings = (new \PhpMqtt\Client\ConnectionSettings)

    // The username used for authentication when connecting to the broker.
    ->setUsername($username)
    
    // The password used for authentication when connecting to the broker.
    ->setPassword($password)
    
    // The connect timeout defines the maximum amount of seconds the client will try to establish
    // a socket connection with the broker. The value cannot be less than 1 second.
    ->setConnectTimeout(60)
    
    // The socket timeout is the maximum amount of idle time in seconds for the socket connection.
    // If no data is read or sent for the given amount of seconds, the socket will be closed.
    // The value cannot be less than 1 second.
    ->setSocketTimeout(5)
    
    // The resend timeout is the number of seconds the client will wait before sending a duplicate
    // of pending messages without acknowledgement. The value cannot be less than 1 second.
    ->setResendTimeout(10)
    
    // The keep alive interval is the number of seconds the client will wait without sending a message
    // until it sends a keep alive signal (ping) to the broker. The value cannot be less than 1 second
    // and may not be higher than 65535 seconds. A reasonable value is 10 seconds (the default).
    ->setKeepAliveInterval(10)
    
    ;

$mqtt = new \PhpMqtt\Client\MqttClient($server, $port, $clientId);
$mqtt->connect($connectionSettings, true);
$mqtt->publish($topic, $message, 0);
$mqtt->disconnect();

?>