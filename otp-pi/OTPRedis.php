<?php
class OTPRedis extends OTPForwarder {
    public $username = '';
    public $port = 6379;
    public $topic = 'sms';
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['REDIS']['username'];
        $this->passkey = $config['REDIS']['password'];
        $this->host = $config['REDIS']['host'];
        $this->port = $config['REDIS']['port'];
        $this->topic = $config['REDIS']['topic'];
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
                return $this->rejectRequest($requestJSON, ResponseCode::DUPLICATED, $e);
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
        if(!empty($this->password))
        {
            $redis = new Predis\Client(['host' => $this->host, 'port' => $this->port, 'password' => $this->password]);
        }
        else
        {
            $redis = new Predis\Client(['host' => $this->host, 'port' => $this->port]);
        }
        $redis->publish($topic, $message);
        return true;
    }
}