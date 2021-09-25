<?php
require "vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = "localhost";
$port = 5672;
$username = 'guest';
$password = 'guest';
$topic = 'sms';

$message = json_encode(array(
    "command"=>"send-sms",
    "data"=>array(
        "date_time"=>1629685778,
        "expiration"=>1629685838,
        "id"=>123456,
        "recipient"=>"6281111111111",
        "message"=>"OTP Anda adalah 123456"
    )
));

$connection = new AMQPStreamConnection($host, $port, $username, $password);
$channel = $connection->channel();

$channel->queue_declare($topic, false, false, false, false);

$msg = new AMQPMessage($message);
$channel->basic_publish($msg, '', $topic);


?>