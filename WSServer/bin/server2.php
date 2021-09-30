<?php
// Your shell script
use Ratchet\Session\SessionProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;
use Ratchet\App;

$memcache = new Memcache;
$memcache->connect('localhost', 8080);

$session = new SessionProvider(
	new MyApp
  , new Handler\MemcacheSessionHandler($memcache)
);

$server = new App('localhost');
$server->route('/sessDemo', $session);
$server->run();
?>