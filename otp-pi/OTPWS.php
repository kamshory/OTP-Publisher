<?php
require_once dirname(__FILE__)."/WSClient.php";
class OTPWS extends OTPForwarder {
    public $username = '';
    public $passkey = '';
    public $port = 6379;
    public $topic = 'sms';
    public $path = '/';
    public $clientID = 'php';
    public $callbackDelay = 50;
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['WS']['username'];
        $this->passkey = $config['WS']['password'];
        $this->host = $config['WS']['host'];
        $this->port = $config['WS']['port'];
        $this->topic = $config['WS']['topic'];
        $this->path = $config['WS']['path'];
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
            $pub = $this->publish($this->topic, json_encode($requestJSON));
            $callbackTopic = $requestJSON['callback_topic'];
            $result = array(
                'command'=>$requestJSON['command'],
                'response_code'=>$pub?'0000':'1102',
                'data'=>array(
                    'date_time'=>$requestJSON['data']['date_time']
                )
            );
            $response = $this->subecribe($callbackTopic);
            $result = json_decode($response);
            return $result;
        }
        else
        {
            $pub = $this->publish($this->topic, json_encode($requestJSON));
            $result = array(
                'command'=>$requestJSON['command'],
                'response_code'=>$pub?'0000':'1102',
                'data'=>array(
                    'date_time'=>$requestJSON['data']['date_time']
                )
            );
            return $result;
        }
    }
    public function subecribe($topic)
    {
        $message = "";
        $headers = array(
            'Authorization: Basic '.base64_encode($this->username.':'.$this->username),
            'Content-type: application/json'
        );
        
        $path = $this->path;

        $pargs = array();
        if(stripos($path, "?") !== false)
        {
            $arr = explode("?", $path, 2);
            $path = $arr[0];
            parse_str($arr[1], $pargs);
        }

        $pargs['topic'] = $topic;
        $arr2 = array();
        foreach($pargs as $k=>$v)
        {
            $arr2[] = $k."=".rawurlencode($v);
        }
        $str = implode("&", $arr2);
        $path .= "?$str";

        if($sp = websocket_open($this->host, $this->port, $headers, $errstr, 10, false, false, $path)) 
        {
            $message = websocket_read($sp, $error_code, $error_string);
            if($error_code == "000")
            {

            }
        }
        return $message;
    }
    public function publish($topic, $message)
    {
        $headers = array(
            'Authorization: Basic '.base64_encode($this->username.':'.$this->username),
            'Content-type: application/json'
        );
        
        $path = $this->path;

        $pargs = array();
        if(stripos($path, "?") !== false)
        {
            $arr = explode("?", $path, 2);
            $path = $arr[0];
            parse_str($arr[1], $pargs);
        }

        $pargs['topic'] = $topic;
        $arr2 = array();
        foreach($pargs as $k=>$v)
        {
            $arr2[] = $k."=".rawurlencode($v);
        }
        $str = implode("&", $arr2);
        $path .= "?$str";

        if($sp = websocket_open($this->host, $this->port, $headers, $errstr, 5, false, false, $path)) 
        {
            websocket_write($sp, $message);
            return true;
        }
        else 
        {
            return false;
        }
    }
}