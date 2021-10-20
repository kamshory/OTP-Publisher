<?php
use \PhpMqtt\Client\ConnectionSettings;
use \PhpMqtt\Client\MqttClient;

class OTPMQTT extends OTPForwarder {
    public $username = '';
    public $passkey = '';
    public $port = 6379;
    public $topic = 'sms';
    public $clientID = 'php';
    public $callbackDelay = 50;
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['MQTT']['username'];
        $this->passkey = $config['MQTT']['password'];
        $this->host = $config['MQTT']['host'];
        $this->port = $config['MQTT']['port'];
        $this->topic = $config['MQTT']['topic'];
        $this->clientID = $config['MQTT']['client_id'];
        $this->callbackDelay = $config['AMQP']['callback_delay'];
    }
    public function request($requestJSON)
    {
        if($this->manageOTP && $requestJSON['command'] ==  'request-otp')
        {
            try
            {
                $otpRequest = $this->createOTP($requestJSON);
                $this->publish($this->topic, json_encode($otpRequest));
                $result = array(
                    'command'=>$requestJSON['command'],
                    'response_code'=>'1111',
                    'data'=>array(
                        'date_time'=>$requestJSON['data']['date_time']
                    )
                );
                return $result;
            }
            catch(DuplicatedException $e)
            {
                return $this->rejectRequest($requestJSON, ResponseCode::DUPLICATED, $e);
            }
        }
        else if($this->manageOTP && $requestJSON['command'] ==  'verify-otp')
        {
            $otpValidation = $this->verifyOTP($requestJSON);
            return $otpValidation;
        }
        else if($this->manageOTP && isset($requestJSON['callback_topic']))
        {
            $requestJSON['callback_delay'] = $this->callbackDelay;
            $callbackTopic = $requestJSON['callback_topic'];
            $sub = $this->subscribe($callbackTopic);
            $pub = $this->publish($this->topic, json_encode($requestJSON));
            $result = array(
                'command'=>$requestJSON['command'],
                'response_code'=>$pub?'0000':'1102',
                'data'=>array(
                    'date_time'=>$requestJSON['data']['date_time']
                )
            );
            try
            {
                if($sub != null)
                {
                    $sub->loop();
                }
            }
            catch(Exception $e)
            {
                
            }
            
            $response = "";
            $i = 0;
            $response = $GLOBALS['rec_msg']; 
                        
            $result = json_decode($response);
            return $result;
        }
        else
        {
            return $this->publish($this->topic, json_encode($requestJSON));
        }
    }
    public function subscribe($topic)
    {
        $host = $this->host;
        $port = $this->port;
        $clientID = $this->clientID;
        $username = $this->username;
        $password = $this->passkey;

        $GLOBALS['rec_msg'] = '';

        $host = $this->host;
        $port = $this->port;
        $clientID = $this->clientID;
        $username = $this->username;
        $password = $this->passkey;
        $clean_session = false;
            
        $connectionSettings = new ConnectionSettings();
        $connectionSettings
        ->setUsername($username)
        ->setPassword($password)
        ->setKeepAliveInterval(60)
        ->setLastWillTopic($topic)
        ->setLastWillMessage($topic)
        ->setLastWillQualityOfService(1);
        
        try {
            $mqtt = new MQTTClient($host, $port, $clientID);
            $mqtt->connect($connectionSettings, $clean_session);  
            $mqtt->subscribe($topic, function ($topic, $message) use ($mqtt) {
                $GLOBALS['rec_msg'] = $message;
                $mqtt->interrupt();
            }, MQTTClient::QOS_AT_MOST_ONCE);          
               
        } 
        catch (\Throwable $e) {
            return null;
        }
        return $mqtt;    
    }
    
    public function publish($topic, $message)
    {
        $host = $this->host;
        $port = $this->port;
        $clientID = $this->clientID;
        $username = $this->username;
        $password = $this->passkey;
        $clean_session = false;
        $clean_session = false;

        $connectionSettings = new ConnectionSettings();
        $connectionSettings
        ->setUsername($username)
        ->setPassword($password)
        ->setKeepAliveInterval(60)
        ->setLastWillTopic($topic)
        ->setLastWillMessage($topic)
        ->setLastWillQualityOfService(1);
        
        try {
            $mqtt = new MQTTClient($host, 1883, uniqid());
            $mqtt->connect();         
            $mqtt->publish($topic, $message, MQTTClient::QOS_AT_MOST_ONCE);         
        
        } 
        catch (\Throwable $e) {
            
        }

    }
}