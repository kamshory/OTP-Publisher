<?php
require dirname(__DIR__) . '/lib/bootstrap.php';
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\MessageBroker;

$userFile = dirname(__FILE__)."/.htpasswd";

$server = IoServer::factory(
    new WsServer(
        new MessageBroker($userFile)
    )
  , 9000
);
$server->run();