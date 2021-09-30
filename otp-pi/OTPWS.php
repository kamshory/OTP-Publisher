<?php
require_once dirname(__FILE__)."/WSClient.php";
class OTPWS extends OTPForwarder {
    public $username = '';
    public $passkey = '';
    public $port = 6379;
    public $topic = 'sms';
    public $path = '/';
    public $clientID = 'php';
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['WS']['username'];
        $this->passkey = $config['WS']['password'];
        $this->host = $config['WS']['host'];
        $this->port = $config['WS']['port'];
        $this->topic = $config['WS']['topic'];
        $this->path = $config['WS']['path'];
    }
    public function request($requestJSON)
    {
        if($this->manageOTP && $requestJSON['command'] ==  'create-otp')
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
                return $this->rejectRequest($requestJSON, "1201", $e);
            }
        }
        else if($this->manageOTP && $requestJSON['command'] ==  'validate-otp')
        {
            $otpValidation = $this->validateOTP($requestJSON);
            return $otpValidation;
        }
        else
        {
            return $this->requestHTTP(json_encode($requestJSON), array());
        }
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

        if($sp = websocket_open($this->host, $this->port, $headers, $errstr, 10, false, false, $path)) 
        {
            websocket_write($sp, $message);
            //$response = websocket_read($sp, $errorcode, $errstr); 
            return true;
        }
        else 
        {
            return false;
        }
    }
}