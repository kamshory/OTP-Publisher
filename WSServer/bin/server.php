<?php
require dirname(__DIR__) . '/lib/bootstrap.php';
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\ExampleChat;

$server = IoServer::factory(
    new WsServer(
        new ExampleChat()
    )
  , 9000
);
$server->run();