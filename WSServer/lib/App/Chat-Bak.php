<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface 
{
    protected $clients;
	protected $connectionMap = array();
	protected $onlineUser = array();
    public function __construct() 
    {    
		$this->logServerUp();
        $this->clients = new \SplObjectStorage;
        //echo "Chat server started!\n";
    }
	public function logServerUp()
	{
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$query = "INSERT INTO chat_service(time_up) values (now());";	
		$db->query($query);
		$db->close();
	}
	public function UTF8ToEntities($string){
	if (!@ereg("[\200-\237]",$string) && !@ereg("[\241-\377]",$string))
		return $string;
	$string = preg_replace("/[\302-\375]([\001-\177])/","&#65533;\\1",$string);
	$string = preg_replace("/[\340-\375].([\001-\177])/","&#65533;\\1",$string);
	$string = preg_replace("/[\360-\375]..([\001-\177])/","&#65533;\\1",$string);
	$string = preg_replace("/[\370-\375]...([\001-\177])/","&#65533;\\1",$string);
	$string = preg_replace("/[\374-\375]....([\001-\177])/","&#65533;\\1",$string);
	$string = preg_replace("/[\300-\301]./", "&#65533;", $string);
	$string = preg_replace("/\364[\220-\277]../","&#65533;",$string);
	$string = preg_replace("/[\365-\367].../","&#65533;",$string);
	$string = preg_replace("/[\370-\373]..../","&#65533;",$string);
	$string = preg_replace("/[\374-\375]...../","&#65533;",$string);
	$string = preg_replace("/[\376-\377]/","&#65533;",$string);
	$string = preg_replace("/[\302-\364]{2,}/","&#65533;",$string);
	$string = preg_replace(
		"/([\360-\364])([\200-\277])([\200-\277])([\200-\277])/e",
		"'&#'.((ord('\\1')&7)<<18 | (ord('\\2')&63)<<12 |".
		" (ord('\\3')&63)<<6 | (ord('\\4')&63)).';'",
	$string);
	$string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
	"'&#'.((ord('\\1')&15)<<12 | (ord('\\2')&63)<<6 | (ord('\\3')&63)).';'",
	$string);
	$string = preg_replace("/([\300-\337])([\200-\277])/e",
	"'&#'.((ord('\\1')&31)<<6 | (ord('\\2')&63)).';'",
	$string);
	$string = preg_replace("/[\200-\277]/","&#65533;",$string);
	return $string;
}

    public function onOpen(ConnectionInterface $conn) 
    {
		$headers = $conn->WebSocket->request->getHeaders();
		$cookie_data = $this->parseCookie($headers['cookie']);
		$session_id = strrev(md5($this->readCookie($cookie_data, "planetbiru")));
		$user_data = $this->getUserBySessionID($session_id);	
		if(isset($user_data['member_id']))
		{	
			$this->onlineUser[$user_data['member_id']] = $user_data;		
			$this->connectionMap[$conn->resourceId] = $user_data;
			$this->clients->attach($conn);
			$logInData = array(
				'command'=>'log-in',
				'data'=>array(
					array('my_id'=>$user_data['member_id'])
				)
			);
			$conn->send(json_encode($logInData));
		}
    }
	public function sendMessage(ConnectionInterface $from, $message, $sender_data) 
	{
		$sender_id = $sender_data['member_id'];
		$sender_name = $sender_data['name'];
		$message_json = json_decode($message, true);
	
		$message_json['data'][0]['timestamp'] = round(microtime(true)*1000);
		$message_json['data'][0]['sender_id'] = $sender_data['member_id'];
		$message_json['data'][0]['date_time'] = date('j F Y H:i:s');
		$receiver_id = $message_json['data'][0]['receiver_id'];
		$uid = uniqid($sender_id.'_'.$receiver_id.'_'.time(0));
		$message_json['data'][0]['unique_id'] = $uid;
		$message_json['data'][0]['sender_name'] = $sender_name;
		$message_json['data'][0]['read'] = false;
		if(isset($message_json['data']))
		{
			
			$sent_to_receiver = false;
			$receiver_data = isset($this->onlineUser[$receiver_id])?$this->onlineUser[$receiver_id]:array(); 
			if(isset($receiver_data['member_id']))
			{
				$message_json['data'][0]['receiver_name'] = $receiver_data['name'];

				$message_json['data'][0]['partner_name'] = $sender_data['name'];
				$message_json['data'][0]['partner_id'] = $sender_data['member_id'];
				$message_json['data'][0]['partner_uri'] = $sender_data['username'];

				$message = json_encode($message_json);
				
				
				foreach($this->clients as $client) 
				{
					$current_user_data = $this->getUserByResourceID($client->resourceId);
					$member_id = $current_user_data['member_id'];
					$member_name = $current_user_data['name'];
					if($member_id == $receiver_id) 
					{
						$client->send($message);
						$sent_to_receiver = true;
					}
				}
			}
			
			$user_data_from_db = array();
			$save = false;
			
			if(isset($receiver_data['member_id']))
			{
				$save = true;
			}
			else
			{
				$receiver_data = $this->getUserByID($receiver_id);
				if(isset($receiver_data['member_id']))
				{
					$save = true;
				}
			}
			$message_json['data'][0]['partner_id'] = $receiver_data['member_id'];
			$message_json['data'][0]['partner_name'] = $receiver_data['name'];
			$message_json['data'][0]['partner_uri'] = $receiver_data['username'];
			$message = json_encode($message_json);
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id) 
				{
					$client->send($message);
					
				}
			}
			
			
			if($save)
			{
				if($this->saveMessage($from, $sender_data, $message, $sent_to_receiver))
				{
				}
				else
				{
				}
			}
		}
	}
	
	public function loadMessage(ConnectionInterface $from, $message, $my_data)
	{
		$max = 500;
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$partner_id = @$messageData['data'][0]['partner_id'];
		$partner_data = $this->getUserByID($partner_id);
		if($partner_id)
		{
			$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			$query = "select message.* 
			from message 
			where 
				(message.sender_id = '$my_id' and message.receiver_id = '$partner_id' and message.delete_by_sender = 0) 
					or 
				(message.sender_id = '$partner_id' and message.receiver_id = '$my_id' and message.delete_by_receiver = 0)
			order by message.message_id desc 
			limit 0, $max
			";
			$messages = array();
			
			$result = $db->query($query);

			while($row = $result->fetch_assoc())
			{
				
				$sender_name = ($my_id == $row['sender_id'])?$my_data['name']:$partner_data['name'];
				$receiver_name = ($my_id == $row['receiver_id'])?$my_data['name']:$partner_data['name'];
				$messages[] = array(
					'timestamp'=>strtotime($row['date_time_send']) * 1000,
					'date_time'=>date('j F Y H:i:s', strtotime($row['date_time_send'])),
					'partner_id'=>$partner_id,
					'unique_id'=>$row['unique_id'],
					'partner_name'=>$partner_data['name'],
					'partner_uri'=>$partner_data['username'],
					'sender_id'=>$row['sender_id'],
					'receiver_id'=>$row['receiver_id'],
					'sender_name'=>$sender_name,
					'receiver_name'=>$receiver_name,
					'read'=>($row['sender_id'] && $my_id && $row['read_by_receiver']),
					'message'=>array(
						'text'=>$row['content']
					)
				);
			}
			$result->close();
			$message_reversed = array_reverse($messages);
			$message_data = array(
				'command'=>'send-message',
				'data'=>$message_reversed
			
			);
			$from->send(json_encode($message_data));
			
			
			$db->close();
		}
	}
	public function markMessage(ConnectionInterface $from, $message, $my_data)
	{
		$max = 100;
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
		// echo "call markMessage\r\n";
			if(isset($messageData['data']))
			{
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$data_all = $messageData['data'];
				foreach($data_all as $data)
				{
					// print_r($data);
					if(isset($data['message_list']))
					{
						$partner_id = $data['partner_id'];
						if(isset($this->onlineUser[$partner_id]))
						{
							$partner_data = $this->onlineUser[$partner_id];
							if(isset($data['message_list']))
							{
								$message_list = $data['message_list'];
								if(is_array($message_list))
								{
									if(count($message_list) > 0)
									{
										$reedback_message = array(
											'command'=>'mark-message',
											'data'=>array(
												array(
													'partner_id'=>$my_id,
													'flag'=>'read',
													'message_list'=>$message_list
												)
											)
										);
	
										foreach($this->clients as $client) 
										{
											$current_user_data = $this->getUserByResourceID($client->resourceId);
											$member_id = $current_user_data['member_id'];
											// echo "Current member ID = $member_id, partner ID = $partner_id \r\n";
											if($partner_id == $member_id) 
											{
												// echo "SEND TO THIS GUY\r\n";
												$client->send(json_encode($reedback_message));
											}
										}
									}
								}
							}
						}
						else
						{
							$partner_data = $this->getUserByID($partner_id);
						}
						if(isset($data['message_list']))
						{
							$message_list = $data['message_list'];
							if(is_array($message_list))
							{
								if(count($message_list) > 0)
								{
									$lst = "'".implode("', '", $message_list)."'";
									$query = "update message set read_by_receiver = 1, date_time_read = now() 
									where unique_id in($lst) and read_by_receiver = 0
									and receiver_id = '$my_id' and sender_id = '$partner_id';
									";
									// echo $query;
									$db->query($query);
								}
							}
						}
					}
				}
				$db->close();
			}
		}
	}
	
	public function deleteMessageForAll(ConnectionInterface $from, $message, $my_data)
	{
		$max = 100;
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
		// echo "call deleteMessageForAll\r\n";
			if(isset($messageData['data']))
			{
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$data_all = $messageData['data'];
				foreach($data_all as $data)
				{
					// print_r($data);
					if(isset($data['message_list']))
					{
						$partner_id = $data['partner_id'];
						if(isset($this->onlineUser[$partner_id]))
						{
							$partner_data = $this->onlineUser[$partner_id];
							if(isset($data['message_list']))
							{
								$message_list = $data['message_list'];
								if(is_array($message_list))
								{
									if(count($message_list) > 0)
									{
										$reedback_message = array(
											'command'=>'delete-message-for-all',
											'data'=>array(
												array(
													'partner_id'=>$my_id,
													'flag'=>'read',
													'message_list'=>$message_list
												)
											)
										);
	
										foreach($this->clients as $client) 
										{
											$current_user_data = $this->getUserByResourceID($client->resourceId);
											$member_id = $current_user_data['member_id'];
											if($partner_id == $member_id)
											{
												// echo "Current member ID = $member_id, partner ID = $partner_id \r\n";
												// echo "SEND TO THIS GUY\r\n";
												$reedback_message['data'][0]['partner_id'] = $my_id;
												$client->send(json_encode($reedback_message));
											}
											else if($member_id == $my_id) 
											{
												// echo "Current member ID = $member_id, partner ID = $partner_id \r\n";
												// echo "SEND TO THIS GUY\r\n";
												$reedback_message['data'][0]['partner_id'] = $partner_id;
												$client->send(json_encode($reedback_message));
											}
										}
									}
								}
							}
						}
						else
						{
							$partner_data = $this->getUserByID($partner_id);
						}
						if(isset($data['message_list']))
						{
							$message_list = $data['message_list'];
							if(is_array($message_list))
							{
								if(count($message_list) > 0)
								{
									// Delete for all
									$lst = "'".implode("', '", $message_list)."'";
									$query = "update message set delete_by_receiver = 1, delete_by_sender = 1 
									where unique_id in($lst) and sender_id = '$my_id' and receiver_id = '$partner_id';
									";
									// echo $query;
									$db->query($query);
								}
							}
						}
					}
				}
				$db->close();
			}
		}
	}
	public function deleteMessage(ConnectionInterface $from, $message, $my_data)
	{
		$max = 100;
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
		// echo "call deleteMessage\r\n";
			if(isset($messageData['data']))
			{
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$data_all = $messageData['data'];
				foreach($data_all as $data)
				{
					if(isset($data['message_list']))
					{
						$partner_id = $data['partner_id'];
						if(isset($data['message_list']))
						{
							$message_list = $data['message_list'];
							if(is_array($message_list))
							{
								if(count($message_list) > 0)
								{
									$lst = "'".implode("', '", $message_list)."'";
									// Delete for me
									// If sender is me
									$query = "update message set delete_by_sender = 1
									where unique_id in($lst) and sender_id = '$my_id'
									";
									// echo $query;
									$db->query($query);
									// If sender is partner
									$query = "update message set delete_by_receiver = 1
									where unique_id in($lst) and receiver_id = '$my_id'
									";
									// echo $query;
									$db->query($query);
								}
							}
						}
						
						if(isset($this->onlineUser[$partner_id]))
						{
							$partner_data = $this->onlineUser[$partner_id];
							if(isset($data['message_list']))
							{
								$message_list = $data['message_list'];
								if(is_array($message_list))
								{
									if(count($message_list) > 0)
									{
										$reedback_message = array(
											'command'=>'delete-message-for-all',
											'data'=>array(
												array(
													'partner_id'=>$partner_id,
													'flag'=>'read',
													'message_list'=>$message_list
												)
											)
										);
	
										foreach($this->clients as $client) 
										{
											$current_user_data = $this->getUserByResourceID($client->resourceId);
											$member_id = $current_user_data['member_id'];
											// echo "Current member ID = $member_id, partner ID = $partner_id \r\n";
											if($member_id == $my_id) 
											{
												// echo "SEND TO THIS GUY\r\n";
												$client->send(json_encode($reedback_message));
											}
										}
									}
								}
							}
						}
					}
				}
				$db->close();
			}
		}
	}
	
    public function onMessage(ConnectionInterface $from, $message) 
    {
		$message_json = json_decode($message, true);
		$command = $message_json['command'];
		$sender_data = $this->getUserByResourceID($from->resourceId);
		$sender_id = @$sender_data['member_id'];
		// echo "Command = $command \r\n";
		if($sender_id)
		{
			if($command == 'send-message')
			{
				$this->sendMessage($from, $message, $sender_data); 
			}
			else if($command == 'load-message')
			{
				$this->loadMessage($from, $message, $sender_data); 
			}
			else if($command == 'mark-message')
			{
				$this->markMessage($from, $message, $sender_data); 
			}
			else if($command == 'delete-message-for-all')
			{
				$this->deleteMessageForAll($from, $message, $sender_data); 
			}
			else if($command == 'delete-message')
			{
				$this->deleteMessage($from, $message, $sender_data); 
			}
			
		}
        
    }

    public function onClose(ConnectionInterface $conn) 
    {
		unset($this->connectionMap[$conn->resourceId]);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) 
    {
        // echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();        
    }
    public function getUserByResourceID($resourceId)
	{
		return isset($this->connectionMap[$resourceId])?$this->connectionMap[$resourceId]:array();
	}
	
    public function saveMessage($from, $sender_data, $message, $sent_to_receiver)
    {
	    
		$message_json = json_decode($message, true);
		$data = $message_json['data'];
		$unique_id = addslashes($data[0]['unique_id']);
		$sender_id = addslashes($data[0]['sender_id']);
		$sender_id = addslashes($data[0]['sender_id']);
		$receiver_id = addslashes($data[0]['receiver_id']);
		$date_time_send = date('Y-m-d H:i:s');
		$ip_send = $from->remoteAddress;
		$latitude_send = $sender_data['latitude'] * 1;
		$longitude_send = $sender_data['longitude'] * 1;
		$text = addslashes($this->UTF8ToEntities($data[0]['message']['text']));
		
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

		$query2 = ("INSERT INTO message(unique_id,sender_id,receiver_id,date_time_send,ip_send,latitude_send,longitude_send,content) VALUES
		('$unique_id','$sender_id','$receiver_id','$date_time_send','$ip_send',$latitude_send,$longitude_send,'$text');");		
		$db->query($query2);
		
		$db->close();
		return true;
    }
    public function markMessageRead($from, $sender_data, $message, $readFlag)
    {
	    
		$message_json = json_decode($message, true);
		$json_data = $message_json['data'];

		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$ip_send = $from->remoteAddress;
		foreach($json_data as $data)
		{
			$unique_id = $data['unique_id'];
					

		}
		$db->query($query2);
		
		$db->close();
		return true;
    }

	public function getUserBySessionID($session_id)
	{
		$user_data = array();
		$session_data = array();
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$query = "SELECT * from sessions where id = '$session_id' ";
		$result = $db->query($query);
		if($row = $result->fetch_assoc())
		{
			$xdata = str_rot13(stripslashes($row['xdata']));
			$session_data = unserialize($xdata);
			
			$username = $session_data['username'];
			$password = $session_data['password'];
			
			$query2 = "select member_id, username, name, gender, birth_place, birth_day, email, phone, phone_code, url, show_compass,
			autoplay_360, autorotate_360, img_360_compress, picture_hash, background, language, country_id, state_id, city_id, circle_avatar,
			latitude, longitude
			from member 
			where username = '$username' and password = md5('$password') 
			and active = '1'
			and blocked = '0'
			";
			
			$result = $db->query($query2);
			if($user_data = $result->fetch_assoc())
			{
			}
		}
		$result->close();
		$db->close();
		return $user_data;
	}
	public function getUserByID($user_id)
	{
		$user_data = array();
		$session_data = array();
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$user_data = array();
		$query2 = "select member_id, username, name, gender, birth_place, birth_day, email, phone, phone_code, url, show_compass,
		autoplay_360, autorotate_360, img_360_compress, picture_hash, background, language, country_id, state_id, city_id, circle_avatar,
		latitude, longitude
		from member 
		where member_id = '$user_id'
		and active = '1'
		and blocked = '0'
		";
		$result = $db->query($query2);
		if($user_data = $result->fetch_assoc())
		{
		}
		$result->close();
		$db->close();
		return $user_data;
	}
	public function readCookie($cookie_data, $name){
		$v0 = (isset($cookie_data[$name."0"]))?($cookie_data[$name."0"]):"";
		$v1 = (isset($cookie_data[$name."1"]))?($cookie_data[$name."1"]):"";
		$v2 = (isset($cookie_data[$name."2"]))?($cookie_data[$name."2"]):"";
		$v3 = (isset($cookie_data[$name."3"]))?($cookie_data[$name."3"]):"";
		$v  = strrev(str_rot13($v1.$v3.$v2.$v0));
		if($v=="")
		return md5(microtime().mt_rand(1,9999999));
		else 
		return $v;
	}
	public function parseCookie($cookie_string)
	{
		$cookie_data = array();
		$arr = explode("; ", $cookie_string);
		foreach($arr as $key=>$val)
		{
			$arr2 = explode("=", $val, 2);
			$cookie_data[$arr2[0]] = $arr2[1];
		}
		return $cookie_data;
	}  
    
}
