<?php
class OTPMQTT extends OTPForwarder {
    public $username = '';
    public $passkey = '';
    public $port = 6379;
    public $topic = 'sms';
    public $clientID = 'php';
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['MQTT']['username'];
        $this->passkey = $config['MQTT']['password'];
        $this->host = $config['MQTT']['host'];
        $this->port = $config['MQTT']['port'];
        $this->topic = $config['MQTT']['topic'];
        $this->clientID = $config['MQTT']['client_id'];
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
        else if($this->manageOTP && $requestJSON['command'] ==  'request-ussd' || $this->manageOTP && $requestJSON['command'] ==  'get-modem-list')
        {
            $pub = $this->publish($this->topic, json_encode($requestJSON));
            $callbackTopic = $requestJSON['callback_topic'];
            $result = array(
                'command'=>$requestJSON['command'],
                'response_code'=>$pub?'0000':'1102',
                'data'=>array(
                    'date_time'=>$requestJSON['data']['date_time']
                )
            );
            $response = "";
            $i = 0;
            do
            {
                $response = $this->subscribe($callbackTopic);
                if($i > 0 && empty($response))
                {
                    usleep(10000);
                }
                $i++;
            }
            while(empty($response) && $i<1);           
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

        function callback($topic, $message)
        {
            if(!empty($message))
            {
                $GLOBALS['rec_msg'] = $message;
            }
        }
        $timeout = 10; 
        $mqtt = new Bluerhinos\phpMQTT($host, $port, $clientID);
        if ($mqtt->connect(true, NULL, $username, $password)) 
        {
            $topics[$topic] = array(
                "qos" => 0,
                "function" => "callback"
            );
            $mqtt->subscribe($topics,0);
            $time_start = microtime(true);
            while($mqtt->proc()) 
            {
                $time_end = microtime(true);
                if($time_end - $time_start > $timeout || !empty($GLOBALS['rec_msg']))
                {
                    break;
                }
            }
            $mqtt->close();
        } 
        return $GLOBALS['rec_msg'];    
    }
    
    public function publish($topic, $message)
    {
        $host = $this->host;
        $port = $this->port;
        $clientID = $this->clientID;
        $username = $this->username;
        $password = $this->passkey;
            
        $mqtt = new Bluerhinos\phpMQTT($host, $port, $clientID);
        if ($mqtt->connect(true, NULL, $username, $password)) 
        {
            $mqtt->publish($topic, $message, 0);
            $mqtt->close();
        } 
        return true;
    }
}