<?php


namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PhpAmqpLib\Connection;
use React\EventLoop\StreamSelectLoop;

use PhpAmqpLib\Connection\AMQPStreamConnection;

include dirname(__FILE__)."/autoload.php";


class MessageBroker implements MessageComponentInterface 
{
  protected $clients;
  protected $connectionMap = array();
  protected $clientTopic = array();
  public $loop = NULL;
  public function __construct()
  {
    $this->clients = new \SplObjectStorage();
  }
  public function onOpen(ConnectionInterface $conn) 
  {
    $headers = $conn->WebSocket->request->getHeaders();
    $query = $conn->WebSocket->request->getQuery()->toArray();
    if(isset($headers['authorization']))
    {
      $authorization = $headers['authorization'];
      if(stripos($authorization, 'Basic ') !== false && $this->validUser($authorization))
      {
        $this->clients->attach($conn);
        $this->setTopic($conn->resourceId, @$query['topic']);
      }
    }
    
    
  }
  public function validUser($authorization)
  {
    $decoded = base64_encode(trim($authorization));
    $arr2 = explode(':', $decoded);
    $username = isset($arr2[0])?$arr2[0]:'';
    $password = isset($arr2[1])?$arr2[1]:'';
    return $this->userMatch($username, $password);
  }
  public function userMatch($username, $password)
  {
    return true;
  }
  public function setTopic($resourceId, $topic)
  {
    $this->clientTopic[$resourceId] = $topic;
  }
  public function getTopic($resourceId)
  {
    return isset($this->clientTopic[$resourceId])?$this->clientTopic[$resourceId]:'';
  }
  public function onMessage(ConnectionInterface $from, $message) 
  {
    foreach($this->clients as $client)
    {
      if($this->getTopic($client->resourceId) == $this->getTopic($from->resourceId))
      {
        
        $client->send($message);
      }
    }
  }

  public function onClose(ConnectionInterface $conn) 
  {
    unset($this->clientTopic[$conn->resourceId]);
  }

  public function onError(ConnectionInterface $conn, \Exception $e) 
  {
    unset($this->clientTopic[$conn->resourceId]);
    $conn->close();        
  }

    
}