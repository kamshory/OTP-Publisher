<?php
namespace App;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface 
{
    protected $clients;
	protected $connectionMap = array();
	protected $onlineUser = array();
	protected $callMarker = array();
	public $sessionName = "ByfTtdt53FKh";
	
	private $notification_base_url = "https://www.planetbiru.com/";
	private $apiKey = "PLANETBIRU";
	private $apiPassword = "0987654321";
	private $apiVersion = "1.0.0";
	private $apiURL = "http://localhost:94";
	
	private $appName = "Planetbiru";
	private $appVersion = "2.0.0";
	private $notificationGroup = "member";
	
	private $langPack = array();
	
    public function __construct() 
    {    
		$this->logServerUp();
        $this->clients = new \SplObjectStorage;
    }
	function loadLanguage($lang)
	{
		$this->langPack[$lang] = json_decode(file_get_contents(dirname(__FILE__)."/langs/".strtolower($lang).".json"));
	}
	public function sendNotification($type, $sender_data, $receiver_id)
	{

		$sender_id = $sender_data['member_id'];
		$sender_name = $sender_data['sender_name'];
		
		$chat_room = MD5($sender_id.'_'.$receiver_id);
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

		$query = "SELECT GROUP_CONCAT(mobile_device.device_id separator ',') AS device_id,
		(select member.language from member where member.member_id = '$to') as language
		FROM mobile_device
		where mobile_device.member_id = '$to'
		GROUP BY mobile_device.member_id
		";			
			
		$result = $db->query($query);
		$idx = 0;
		$title = '';
		$notifString = '';
		while($dbdata = $result->fetch_assoc())
		{
			$device_id = $dbdata['device_id'];
			$lang = $dbdata['language'];
			if($device_id != '')
			{
				$registrationIds = explode(',', $device_id);
				if($type == 'video-call')
				{
					$notification_url = rtrim($this->notification_base_url, "/")."/video-call.php?partner_id=$sender_id&chat_room=$chat_room";
					$title = $this->langPack[$lang]['notif_receive_video_call_title'];
					$notifString = sprintf($this->langPack[$lang]['notif_receive_video_call_text'], $sender_name);
				}
				else if($type == 'voice-call')
				{
					$notification_url = rtrim($this->notification_base_url, "/")."/voice-call.php?partner_id=$sender_id&chat_room=$chat_room";
					$title = $this->langPack[$lang]['notif_receive_voice_call_title'];
					$notifString = sprintf($this->langPack[$lang]['notif_receive_voice_call_text'], $sender_name);
				}
				else if($type == 'send-message')
				{
					$notification_url = rtrim($this->notification_base_url, "/")."/";
					$title = $this->langPack[$lang]['notif_receive_message_title'];
					$notifString = sprintf($this->langPack[$lang]['notif_receive_message_text'], $sender_name);
				}

				
				include_once dirname(dirname(dirname(dirname(__FILE__))))."/lib.inc/notif.php";
				$notif = new Notification($this->apiKey, $this->apiPassword, $this->apiVersion, $this->apiURL);

				$notif->appName = $this->appName;
				$notif->appVersion = $this->appVersion;
				$notif->group = $this->notificationGroup;

				$miscData = new StdClass();
				$msg = array
				(
					'message' 	   => $notifString,
					'title'		   => $title,
					/*
					'subtitle'	   => $notifString,
					'tickerText'   => $notifString,
					*/
					'uri'	       => $notification_url,
					'clickAction'  => 'open-url',
					'type'         => $type,
					'miscData'     => $miscData,
					'clickAction'  => 'open-url',
					'color'        => '#FF5599',
					'vibrate'      => array(200, 0, 200, 400, 0),
					'sound'		   => 'sound1.wav',
					'badge'        => 'tameng1.png',
					'largeIcon'    => 'large_icon.png',
					'smallIcon'    => 'small_icon.png'
				);

				$response = $notif->push($registrationIds, $msg); 
			}
			
		}
		$result->close();
		$db->close();		
	}
	/**
	This method is called when a new connection made
	@param $conn Connection interface from ConnectionInterface
	*/
    public function onOpen(ConnectionInterface $conn) 
    {
		$login = false;
		$headers = $conn->WebSocket->request->getHeaders();
		$query = $conn->WebSocket->request->getQuery()->toArray();
		if(isset($headers['cookie']))
		{
			if(isset($headers['cookie']))
			{
				$cookie_data = $this->parseCookie($headers['cookie']);
				if(isset($cookie_data[$this->sessionName]))
				{
					$session_id = $cookie_data[$this->sessionName];
					$user_data = $this->getUserBySessionID($session_id);	
					if(isset($query['webrtc']))
					{
						if($query['webrtc'] == "true")
						{
							$user_data['webrtc'] = 1;
						}
						else
						{
							$user_data['webrtc'] = 0;
						}
					}
					else
					{
						$user_data['webrtc'] = 0;
					}
					if(isset($user_data['member_id']))
					{	
						$this->clients->attach($conn);
						$this->onlineUser[$user_data['member_id']] = $user_data;		
						$this->connectionMap[$conn->resourceId] = $user_data;
						$logInData = array(
							'command'=>'log-in',
							'data'=>array(
								array('my_id'=>$user_data['member_id'])
							)
						);
						$login = true;
						$conn->send(json_encode($logInData));
						$this->setUserOnline($user_data['member_id']);
						
						$sender_id = $user_data['member_id'];


						// search call marker
						foreach($this->callMarker as $marker)
						{
							if($marker['sender_id'] == $sender_id)
							{
								// ooww
								// ada yang nggantung
								$chat_room = $marker['chat_room'];
								$receiver_id = $marker['receiver_id'];
								$message = json_encode(array('command'=>'on-call', 'data'=>array(array('sender_id'=>$sender_id, 'receiver_id'=>$receiver_id, 'chat_room'=>$chat_room))));
								foreach($this->clients as $client) 
								{
									$current_user_data = $this->getUserByResourceID($client->resourceId);
									$member_id = $current_user_data['member_id'];
									if($member_id == $sender_id) 
									{
										$client->send($message);
									}
								}
							}
						}	
					}
				}
			}
		}
		if(!$login)
		{
		}
    }
	/**
	This method is called when a new message received
	@param $conn Connection interface from ConnectionInterface
	@param $message String message in JSON object format
	*/
    public function onMessage(ConnectionInterface $from, $message) 
    {
		$message_json = json_decode($message, true);
		$command = $message_json['command'];
		$sender_data = $this->getUserByResourceID($from->resourceId);
		$sender_id = @$sender_data['member_id'];
		
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
			else if($command == 'clear-message')
			{
				$this->clearMessage($from, $message, $sender_data); 
			}
			else if($command == 'video-call')
			{
				$this->videoCall($from, $message, $sender_data); 
			}
			else if($command == 'voice-call')
			{
				$this->voiceCall($from, $message, $sender_data); 
			}
			else if($command == 'on-call')
			{
				$this->onCall($from, $message, $sender_data); 
			}
			else if($command == 'missed-call')
			{
				$this->missedCall($from, $message, $sender_data); 
			}
			else if($command == 'reject-call')
			{
				$this->rejectCall($from, $message, $sender_data); 
			}
			else if($command == 'client-call' || $command == 'client-answer' || $command == 'client-offer' || $command == 'client-candidate' || $command == 'touch-point' || $command == 'touch-out')
			{
				$this->sendWebRTCInfo($from, $message, $sender_data); 
			}
			else if($command == 'receive-webrtc-info')
			{
				$this->receiveWebRTCInfo($from, $message, $sender_data); 
			}
		}
    }
 	/**
	This method is called when a connection closed
	@param $conn Connection interface from ConnectionInterface
	*/
   public function onClose(ConnectionInterface $conn) 
    {
		$resID = $conn->resourceId;
		$user_data = $this->getUserByResourceID($conn->resourceId);
		$sender_id = $user_data['member_id'];
		if(isset($this->callMarker[$resID]))
		{
			$marker = $this->callMarker[$resID];
			$chat_room = $marker['chat_room'];
			$receiver_id = $marker['receiver_id'];
			$message = json_encode(array('command'=>'call-iddle', 'data'=>array(array('sender_id'=>$sender_id, 'receiver_id'=>$receiver_id, 'chat_room'=>$chat_room))));
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id || $member_id == $receiver_id) 
				{
					$client->send($message);
				}
			}
			unset($this->callMarker[$resID]);		
		}
		// search call marker
		foreach($this->callMarker as $marker)
		{
			if($marker['sender_id'] == $sender_id)
			{
				// ooww
				// ada yang nggantung
				$chat_room = $marker['chat_room'];
				$receiver_id = $marker['receiver_id'];
				$message = json_encode(array('command'=>'on-call', 'data'=>array(array('sender_id'=>$sender_id, 'receiver_id'=>$receiver_id, 'chat_room'=>$chat_room))));
				foreach($this->clients as $client) 
				{
					$current_user_data = $this->getUserByResourceID($client->resourceId);
					$member_id = $current_user_data['member_id'];
					if($member_id == $sender_id || $member_id == $receiver_id) 
					{
						$client->send($message);
					}
				}
			}
		}	
		
		unset($this->connectionMap[$conn->resourceId]);
        $this->clients->detach($conn);
		$this->setUserOffline($user_data['member_id']);
    }

 	/**
	This method is called when an error occurred
	@param $conn Connection interface from ConnectionInterface
	*/
    public function onError(ConnectionInterface $conn, \Exception $e) 
    {
        // echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();        
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

	public function saveWebRTCInfo($sender_id, $receiver_id, $command, $data)
	{
		$gctime = date('Y-m-d H:i:s', '-1 day');
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$data_str = addslashes(json_encode($data));
		$query = "INSERT INTO webrtc_data
		(sender_id, receiver_id, command, data, time_create) values 
		('$sender_id', '$receiver_id', '$command', '$data_str', now());
		";	
		$db->query($query);
		
		$query = "DELETE FROM webrtc_data
		where time_create < '$gctime'
		";	
		$db->query($query);
		$db->close();
	}
	public function loadWebRTCInfo($sender_id, $receiver_id)
	{
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$query = "select webrtc_data.* 
		from webrtc_data 
		where sender_id = '$sender_id' and receiver_id = '$receiver_id'
		";
		$messages = array();
		$result = $db->query($query);
		$idx = 0;
		while($row = $result->fetch_assoc())
		{
			$messages[] = array(
				'webrtc_data_id'=>$row['webrtc_data_id'],
				'command'=>$row['command'],
				'data'=>json_decode($row['data'], true)
				);
		}
		$db->close();
		return $messages;
	}
	public function deleteWebRTCInfo($webrtc_data_ids)
	{
		if(is_array($webrtc_data_ids))
		{
			if(count($webrtc_data_ids) > 0)
			{
				$ids = implode(", ", $webrtc_data_ids);
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$query = "delete
				from webrtc_data 
				where webrtc_data_id in ($ids)
				";
				$messages = array();
				$db->query($query);
				$db->close();
			}
		}
	}

	public function receiveWebRTCInfo($from, $message, $sender_data)
	{
		$sender_id = $sender_data['member_id'];
		$message_json = json_decode($message, true);
		$receiver_id = $message_json['data'][0]['receiver_id'];
		$messages = $this->loadWebRTCInfo($sender_id, $receiver_id);
		$id_sents = array();
		if(count($messages) > 0)
		{
			foreach($messages as $message)
			{
				$from->send(json_encode(
					array(
						'command'=>$message['command'],
						'data'=>$message['data']
						)
					)
				);
				$id_sents[] = $message['webrtc_data_id'];
			}
		}
		$this->deleteWebRTCInfo($id_sents);
	}
	public function sendWebRTCInfo($from, $message, $sender_data)
	{
		
		$sender_id = $sender_data['member_id'];
		$sender_name = $sender_data['name'];
		$message_json = json_decode($message, true);
		$message_json['data'][0]['sender_id'] = $sender_data['member_id'];
		$receiver_id = $message_json['data'][0]['receiver_id'];
		$uid = uniqid($sender_id.'_'.$receiver_id.'_'.time(0));
		$message_json['data'][0]['sender_name'] = $sender_name;
		$message_json['data'][0]['name_name'] = $sender_name;

		$uid = uniqid($sender_id.'_'.$receiver_id.'_'.time(0));
				
		if(isset($message_json['data']))
		{
			$sent_to_receiver = false;
			$receiver_data = isset($this->onlineUser[$receiver_id])?$this->onlineUser[$receiver_id]:array(); 
			$receiver_count = 0;
			if(isset($receiver_data['member_id']))
			{
				$message_json['data'][0]['partner_id'] = $sender_data['member_id'];
				$message_json['data'][0]['partner_uri'] = $sender_data['username'];
				$message_json['data'][0]['partner_image_uri_50'] = $sender_data['image_url_50'];
				$message_json['data'][0]['partner_image_uri_100'] = $sender_data['image_url_100'];
				$message = json_encode($message_json);

				foreach($this->clients as $client) 
				{
					$current_user_data = $this->getUserByResourceID($client->resourceId);
					$webrtc = isset($current_user_data['webrtc'])?$current_user_data['webrtc']:false;
					$member_id = $current_user_data['member_id'];
					if($member_id == $receiver_id && $webrtc) 
					{
						$client->send($message);
						$receiver_count++;
					}
				}
				if($receiver_count == 0 && $message_json['command'] != 'client-call')
				{
					// No client receiver SDP
					$this->saveWebRTCInfo($sender_id, $receiver_id, $message_json['command'], $message_json['data']);
				}
			}
		}
	}

	public function videoCall($from, $message, $sender_data)
	{
		$this->connectionMap[$from->resourceId]['webrtc'] = 1;
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
		$uid = uniqid($sender_id.'_'.$receiver_id.'_'.time(0));
		
		$system_message_data = array(
			'command'=>'send-message',
			'data'=>array(
				array(
					'unique_id'=>$uid,
					'timestamp'=>$message_json['data'][0]['timestamp'],
					'sender_id'=>$message_json['data'][0]['sender_id'],
					'receiver_id'=>$message_json['data'][0]['receiver_id'],
					'partner_id'=>$sender_data['member_id'],
					'partner_name'=>$sender_data['name'],
					'partner_uri'=>$sender_data['username'],
					'partner_image_uri_50'=>$sender_data['image_url_50'],
					'partner_image_uri_100'=>$sender_data['image_url_100'],
					'message'=>array(
						'text'=>'video-call'
					),
					'by_system'=>true
				)
			)
		);
		$system_message = json_encode($system_message_data);
		
		$nreceiver = 0;
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
				$message_json['data'][0]['partner_image_uri_50'] = $sender_data['image_url_50'];
				$message_json['data'][0]['partner_image_uri_100'] = $sender_data['image_url_100'];
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
						$client->send($system_message);
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
			$message_json['data'][0]['partner_image_uri_50'] = $receiver_data['image_url_50'];
			$message = json_encode($message_json);


			$system_message_data = array(
				'command'=>'send-message',
				'data'=>array(
					array(
						'unique_id'=>$uid,
						'timestamp'=>$message_json['data'][0]['timestamp'],
						'sender_id'=>$message_json['data'][0]['sender_id'],
						'receiver_id'=>$message_json['data'][0]['receiver_id'],
						'partner_id'=>$receiver_data['member_id'],
						'partner_name'=>$receiver_data['name'],
						'partner_uri'=>$receiver_data['username'],
						'partner_image_uri_50'=>$receiver_data['image_url_50'],
						'partner_image_uri_100'=>$receiver_data['image_url_100'],
						'message'=>array(
							'text'=>'video-call'
						),
						'by_system'=>true
					)
				)
			);
			$system_message = json_encode($system_message_data);
			
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id) 
				{
					$client->send($message);
					$client->send($system_message);
					$nreceiver++;
				}
			}
			if($save)
			{
				if($this->saveMessage($from, $sender_data, $system_message, $sent_to_receiver, true))
				{
				}
				else
				{
				}
			}
		}
		if($nreceiver == 0)
		{
			// TODO Send notification
			$this->sendNotification('video-call', $sender_data, $receiver_id);
		}
	}
	public function voiceCall($from, $message, $sender_data)
	{
		$this->connectionMap[$from->resourceId]['webrtc'] = 1;
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

		$uid = uniqid($sender_id.'_'.$receiver_id.'_'.time(0));
		
		$system_message_data = array(
			'command'=>'send-message',
			'data'=>array(
				array(
					'unique_id'=>$uid,
					'timestamp'=>$message_json['data'][0]['timestamp'],
					'sender_id'=>$message_json['data'][0]['sender_id'],
					'receiver_id'=>$message_json['data'][0]['receiver_id'],
					'partner_id'=>$sender_data['member_id'],
					'partner_name'=>$sender_data['name'],
					'partner_uri'=>$sender_data['username'],
					'partner_image_uri_50'=>$sender_data['image_url_50'],
					'partner_image_uri_100'=>$sender_data['image_url_100'],
					'message'=>array(
						'text'=>'voice-call'
					),
					'by_system'=>true
				)
			)
		);
		$system_message = json_encode($system_message_data);
		
		$nreceiver = 0;
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
				$message_json['data'][0]['partner_image_uri_50'] = $sender_data['image_url_50'];
				$message_json['data'][0]['partner_image_uri_100'] = $sender_data['image_url_100'];
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
						$client->send($system_message);
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
			$message_json['data'][0]['partner_image_uri_50'] = $receiver_data['image_url_50'];
			$message = json_encode($message_json);


			$system_message_data = array(
				'command'=>'send-message',
				'data'=>array(
					array(
						'unique_id'=>$uid,
						'timestamp'=>$message_json['data'][0]['timestamp'],
						'sender_id'=>$message_json['data'][0]['sender_id'],
						'receiver_id'=>$message_json['data'][0]['receiver_id'],
						'partner_id'=>$receiver_data['member_id'],
						'partner_name'=>$receiver_data['name'],
						'partner_uri'=>$receiver_data['username'],
						'partner_image_uri_50'=>$receiver_data['image_url_50'],
						'partner_image_uri_100'=>$receiver_data['image_url_100'],
						'message'=>array(
							'text'=>'voice-call'
						),
						'by_system'=>true
					)
				)
			);
			$system_message = json_encode($system_message_data);

			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id) 
				{
					$client->send($message);
					$client->send($system_message);
					$nreceiver++;
				}
			}
			if($save)
			{
				if($this->saveMessage($from, $sender_data, $system_message, $sent_to_receiver, true))
				{
				}
				else
				{
				}
			}
		}
		if($nreceiver == 0)
		{
			// TODO Send notification
			$this->sendNotification('voice-call', $sender_data, $receiver_id);
		}
	}
	public function onCall($from, $message, $sender_data)
	{
		$sender_id = $sender_data['member_id'];
		$sender_name = $sender_data['name'];
		$message_json = json_decode($message, true);
		$chat_room = $message_json['data'][0]['chat_room'];
		$receiver_id = $message_json['data'][0]['receiver_id'];
		if(isset($message_json['data']))
		{
			$sent_to_receiver = false;
			$message = json_encode($message_json);
			foreach($this->clients as $client) 
			{
				if($client->resourceId == $from->resourceId)
				{
					$this->callMarker[$from->resourceId] = array('marker'=>true, 'sender_id'=>$sender_id, 'receiver_id'=>$receiver_id, 'chat_room'=>$chat_room);
				}
			}
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id || $member_id == $receiver_id) 
				{
					$client->send($message);
				}
			}
		}
	}
	public function missedCall($from, $message, $sender_data)
	{
		$message_json = json_decode($message, true);
		$chat_room = $message_json['data'][0]['chat_room'];
		$sender_id = $message_json['data'][0]['sender_id'];
		$receiver_id = $message_json['data'][0]['receiver_id'];
		if(isset($message_json['data']))
		{
			$sent_to_receiver = false;
			$message = json_encode($message_json);
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id || $member_id == $receiver_id) 
				{
					$client->send($message);
				}
			}
		}
	}
	public function rejectCall($from, $message, $sender_data)
	{
		$message_json = json_decode($message, true);
		$chat_room = $message_json['data'][0]['chat_room'];
		$sender_id = $message_json['data'][0]['sender_id'];
		$receiver_id = $message_json['data'][0]['receiver_id'];
		if(isset($message_json['data']))
		{
			$sent_to_receiver = false;
			$message = json_encode($message_json);
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id || $member_id == $receiver_id) 
				{
					$client->send($message);
				}
			}
		}
	}
	public function forwardMessage($from, $message, $sender_data)
	{
		$message_json = json_decode($message, true);
		$chat_room = $message_json['data'][0]['chat_room'];
		$receiver_id = $message_json['data'][0]['receiver_id'];
		if(isset($message_json['data']))
		{
			$sent_to_receiver = false;
			$message = json_encode($message_json);
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $receiver_id) 
				{
					$client->send($message);
				}
			}
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
		$nreceiver = 0;
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
				$message_json['data'][0]['partner_image_uri_50'] = $sender_data['image_url_50'];
				$message_json['data'][0]['by_system'] = false;
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
			$message_json['data'][0]['partner_image_uri_50'] = $receiver_data['image_url_50'];
			$message_json['data'][0]['by_system'] = false;
			$message = json_encode($message_json);
			foreach($this->clients as $client) 
			{
				$current_user_data = $this->getUserByResourceID($client->resourceId);
				$member_id = $current_user_data['member_id'];
				$member_name = $current_user_data['name'];
				if($member_id == $sender_id) 
				{
					$client->send($message);
					$nreceiver++;
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
		if($nreceiver == 0)
		{
			// TODO Send notification
			$this->sendNotification('send-message', $sender_data, $receiver_id);
		}
	}
	
	public function setUserOnline($member_id)
	{
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$query = "update member set online = 1
		where member_id = '$member_id';
		";
		$db->query($query);
		$db->close();
	}
	public function setUserOffline($member_id)
	{
		$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$query = "update member set online = 0
		where member_id = '$member_id';
		";
		$db->query($query);
		$db->close();
	}
 	/**
	Load message from database
	@param $from Connection interface from ConnectionInterface
	@param $message String message in JSON object format
	@param $my_data User data who request the message to be loaded from database
	*/
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
			$idx = 0;
			while($row = $result->fetch_assoc())
			{
				
				$sender_name = ($my_id == $row['sender_id'])?$my_data['name']:$partner_data['name'];
				$receiver_name = ($my_id == $row['receiver_id'])?$my_data['name']:$partner_data['name'];
				$messages[$idx] = array(
					'timestamp'=>strtotime($row['date_time_send']) * 1000,
					'date_time'=>date('j F Y H:i:s', strtotime($row['date_time_send'])),
					'partner_id'=>$partner_id,
					'unique_id'=>$row['unique_id'],
					'partner_name'=>$partner_data['name'],
					'partner_uri'=>$partner_data['username'],
					'partner_image_uri_50'=>$partner_data['image_url_50'],
					'sender_id'=>$row['sender_id'],
					'receiver_id'=>$row['receiver_id'],
					'sender_name'=>$sender_name,
					'by_system'=>($row['by_system']==1),
					'receiver_name'=>$receiver_name,
					'read'=>($row['sender_id'] && $my_id && $row['read_by_receiver']),
					'message'=>array(
						'text'=>$row['content']
					)
				);
				
				$attachments = $row['attachments'];
				if(strlen($attachments) > 3)
				{
					try
					{
						$json = json_decode($attachments, true);
						$messages[$idx]['message']['attachment'] = $json;
					}
					catch(Exception $e)
					{
					}
				}
				
				$idx++;
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
 	/**
	Mark message as read
	@param $from Connection interface from ConnectionInterface
	@param $message String message in JSON object format
	@param $my_data User data who read the message
	*/
	public function markMessage(ConnectionInterface $from, $message, $my_data)
	{
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
			if(isset($messageData['data']))
			{
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$data_all = $messageData['data'];
				foreach($data_all as $data)
				{
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
											if($partner_id == $member_id) 
											{
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
	public function clearMessage(ConnectionInterface $from, $message, $my_data)
	{
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
			if(isset($messageData['data']))
			{
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$data_all = $messageData['data'];
				foreach($data_all as $data)
				{
					$partner_id = $data['partner_id'];
					$query = "update message set delete_by_sender = 1 
					where sender_id = '$my_id' and receiver_id = '$partner_id';
					";
					$db->query($query);
					
					$query = "update message set delete_by_receiver = 1 
					where sender_id = '$partner_id' and receiver_id = '$my_id';
					";
					$db->query($query);
					
					$query = "update last_message set delete_by_sender = 1 
					where sender_id = '$my_id' and receiver_id = '$partner_id';
					";
					$db->query($query);
					
					$query = "update last_message set delete_by_receiver = 1 
					where sender_id = '$partner_id' and receiver_id = '$my_id';
					";
					$db->query($query);
				}
				$db->close();
			}
		}
	}
	
	public function deleteMessageForAll(ConnectionInterface $from, $message, $my_data)
	{
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
			if(isset($messageData['data']))
			{
				$db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				$data_all = $messageData['data'];
				foreach($data_all as $data)
				{
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
												$reedback_message['data'][0]['partner_id'] = $my_id;
												$client->send(json_encode($reedback_message));
											}
											else if($member_id == $my_id) 
											{
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
									$lst = "'".implode("', '", $message_list)."'";
									$query = "update message set delete_by_receiver = 1, delete_by_sender = 1 
									where unique_id in($lst) and sender_id = '$my_id' and receiver_id = '$partner_id';
									";
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
		$messageData = json_decode($message, true);
		$my_id = @$my_data['member_id'];
		$message_id_read = array();
		if($my_id)
		{
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
									$query = "update message set delete_by_sender = 1
									where unique_id in($lst) and sender_id = '$my_id'
									";
									$db->query($query);
									$query = "update message set delete_by_receiver = 1
									where unique_id in($lst) and receiver_id = '$my_id'
									";
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
											if($member_id == $my_id) 
											{
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
	
    public function getUserByResourceID($resourceId)
	{
		return isset($this->connectionMap[$resourceId])?$this->connectionMap[$resourceId]:array();
	}
	
    public function saveMessage($from, $sender_data, $message, $sent_to_receiver, $by_system = false)
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
		if($by_system)
		{
			$by_system = 1;
		}
		else
		{
			$by_system = 0;
		}

		$hasAttachment = false;
		if(isset($data[0]['message']['attachment']))
		{
			if(is_array($data[0]['message']['attachment']))
			{
				if(count($data[0]['message']['attachment']) > 0)
				{
					$attachments = $data[0]['message']['attachment'];
					$attachments_str = addslashes(json_encode($attachments));
					
					$query2 = ("INSERT INTO message(unique_id,sender_id,receiver_id,date_time_send,ip_send,latitude_send,longitude_send,content,attachments,by_system) VALUES
					('$unique_id','$sender_id','$receiver_id','$date_time_send','$ip_send',$latitude_send,$longitude_send,'$text','$attachments_str','$by_system');
					");		
					$db->query($query2);
					
					$hasAttachment = true;
					
					$ids = array();
					foreach($attachments as $idx=>$val)
					{
						$ids[] = "'".addslashes($val['id'])."'";
					}
					if(count($attachments) > 0)
					{
						$query3 = "select last_insert_id() as last_id ";
						$result = $db->query($query3);
						if($message_data = $result->fetch_assoc())
						{
							$message_id = $message_data['last_id'];
							$set_attachment = implode(", ", $ids);
							$sql = "update `attachment` set `message_id` = '$message_id' where `attachment_id` in($set_attachment) ";
							$db->query($query2);
						}
					}
				}
			}
		}
		if(!$hasAttachment)
		{
			$query2 = ("INSERT INTO message(unique_id,sender_id,receiver_id,date_time_send,ip_send,latitude_send,longitude_send,content,by_system) VALUES
			('$unique_id','$sender_id','$receiver_id','$date_time_send','$ip_send',$latitude_send,$longitude_send,'$text','$by_system');
			");		
			$db->query($query2);
		}

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
		$session_data = $this->getSessions($session_id);
		
		$username = addslashes(@$session_data['username']);
		$password = addslashes(@$session_data['password']);
		
		$query2 = "select member_id, username, name, gender, birth_place, birth_day, email, phone, phone_code, url, show_compass,
		autoplay_360, autorotate_360, img_360_compress, picture_hash, background, language, country_id, state_id, city_id, circle_avatar,
		latitude, longitude
		from member 
		where username = '$username' and password = md5('$password') 
		and active = '1'
		and blocked = '0';
		";
		$result = $db->query($query2);
		if($user_data = $result->fetch_assoc())
		{
			$user_data['image_url_50'] = $user_data['member_id']."/uimage-50.jpg?hash=".$user_data['picture_hash'];
			$user_data['image_url_100'] = $user_data['member_id']."/uimage-100.jpg?hash=".$user_data['picture_hash'];
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
			$user_data['image_url_50'] = $user_data['member_id']."/uimage-50.jpg?hash=".$user_data['picture_hash'];
			$user_data['image_url_100'] = $user_data['member_id']."/uimage-100.jpg?hash=".$user_data['picture_hash'];
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
	
	/**
	* Get session data
	* @param $sessionID Session ID
	* @param $sessionSavePath Session save path
	* @param $prefix Prefix of the session file name
	* @return Asociated array contain session
	*/
	public function getSessions($sessionID, $sessionSavePath = NULL, $prefix = "sess_")
	{
		$sessions = array();
		if($sessionSavePath == NULL)
		{
			$sessionSavePath = session_save_path();
		}
		$path = $sessionSavePath."/".$prefix.$sessionID;
		if(file_exists($path))
		{
			$session_text = file_get_contents($path);
			if($session_text != '')
			{
				$sessions = $this->sessionDecode($session_text);
				return $sessions;
			}
		}
		else
		{
		}
	}
	/**
	* Decode session data
	* @param sessionData Raw session data
	* @return Asociated array contain session
	*/
	public function sessionDecode($sessionData) 
	{
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($sessionData)) 
		{
			if (!strstr(substr($sessionData, $offset), "|")) 
			{
				throw new Exception("invalid data, remaining: " . substr($sessionData, $offset));
			}
			$pos = strpos($sessionData, "|", $offset);
			$num = $pos - $offset;
			$varname = substr($sessionData, $offset, $num);
			$offset += $num + 1;
			$data = unserialize(substr($sessionData, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
	/**
	* Decode binary session data
	* @param sessionData Raw session data
	* @return Asociated array contain session
	*/
	public function sessionDecodeBinary($sessionData) 
	{
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($sessionData)) 
		{
			$num = ord($sessionData[$offset]);
			$offset += 1;
			$varname = substr($sessionData, $offset, $num);
			$offset += $num;
			$data = unserialize(substr($sessionData, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
    
}
