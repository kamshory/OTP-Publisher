<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PhpAmqpLib\Connection;
use React\EventLoop\StreamSelectLoop;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\HTPasswd;

include dirname(__FILE__)."/autoload.php";

class MessageBroker implements MessageComponentInterface 
{
  protected $clients;
  protected $connectionMap = array();
  protected $clientTopic = array();
  public $loop = NULL;
  protected $userPass = array();
  protected $userMatch = array();
  public function __construct($userFile)
  {
    if(file_exists($userFile))
    {
      $content = file($userFile);
      foreach($content as $line)
      {
        if(stripos($line, ":"))
        {
          $arr = explode(":", $line, 2);
          $this->userPass[$arr[0]] = array($arr[0], $arr[1]);
        }
      }
    }
    $this->clients = new \SplObjectStorage();
  }

  public function onOpen(ConnectionInterface $conn) 
  {
    $headers = $conn->WebSocket->request->getHeaders();
    $query = $conn->WebSocket->request->getQuery()->toArray();
    if(isset($headers['authorization']))
    {
      $authorization = $headers['authorization'];
      if(stripos($authorization, 'Basic ') !== false && $this->isValidUser($authorization))
      {
        /**
         * Set topic for connected client
         */
        $this->setTopic($conn->resourceId, @$query['topic']);
        $this->clients->attach($conn);
      }
    }
  }

  public function isValidUser($authorization)
  {
    $authorization = trim($authorization);
    if(stripos($authorization, 'Basic ') === 0)
    {
      $authorization = substr($authorization, strlen('Basic '));
    }
    if(isset($this->userMatch) && !empty($this->userMatch) && isset($this->userMatch[$authorization]))
    {
      return true;
    }
    $decoded = base64_decode(trim($authorization));
    $arr2 = explode(':', $decoded);
    $username = isset($arr2[0])?$arr2[0]:'';
    $password = isset($arr2[1])?$arr2[1]:'';
    return $this->isUserMatch($username, $password);
  }

  public function isUserMatch($username, $password)
  {
    if(!isset($this->userPass[$username]))
    {
      return false;
    }
    $passStored = $this->userPass[$username][1];
    if(stripos($passStored, '{SHA}') === 0)
    {
      if(HTPasswd::crypt_sha1($password) === $passStored)
      {
        return true;
      }
      else
      {
        return false;
      }
    }
    else if(stripos($passStored, '$apr1$') === 0)
    {
      if(HTPasswd::check($password, $passStored))
      {
        return true;
      }
      else
      {
        return false;
      }
    }
    else
    {
      return false;
    }
  }

  public function setTopic($resourceId, $topic)
  {
    $this->clientTopic[$resourceId] = $topic;
  }

  public function getTopic($resourceId)
  {
    return isset($this->clientTopic[$resourceId])?$this->clientTopic[$resourceId]:'';
  }
  public function unsetTopic($resourceId)
  {
    unset($this->clientTopic[$resourceId]);
  }
  public function onMessage(ConnectionInterface $from, $message) 
  {
    foreach($this->clients as $client)
    {
      /**
       * Only send to same topic
       */
      if($this->getTopic($client->resourceId) == $this->getTopic($from->resourceId) && $client->resourceId != $from->resourceId)
      {     
        $client->send($message);
      }
    }
  }

  public function onClose(ConnectionInterface $conn) 
  {
    $this->unsetTopic($conn->resourceId);
  }

  public function onError(ConnectionInterface $conn, \Exception $e) 
  {
    $this->unsetTopic($conn->resourceId);
    $conn->close();        
  }

    
}