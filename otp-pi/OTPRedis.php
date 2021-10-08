<?php
class OTPRedis extends OTPForwarder {
    public $username = '';
    public $port = 6379;
    public $topic = 'sms';
    public $callbackDelay = 50;
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['REDIS']['username'];
        $this->passkey = $config['REDIS']['password'];
        $this->host = $config['REDIS']['host'];
        $this->port = $config['REDIS']['port'];
        $this->topic = $config['REDIS']['topic'];
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
        else if($this->manageOTP && ($requestJSON['command'] ==  'get-modem-list' || $requestJSON['command'] ==  'request-ussd'))
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
            while(empty($response) && $i<20);           
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
        if(!empty($this->password))
        {
            $redis = new Predis\Client(['host' => $this->host, 'port' => $this->port, 'password' => $this->password]);
        }
        else
        {
            $redis = new Predis\Client(['host' => $this->host, 'port' => $this->port]);
        }
        $pubsub = $redis->pubSubLoop();
        $pubsub->subscribe($topic);

        $i = 0;
        foreach ($pubsub as $msg) 
        {
            if($msg->kind == 'message' && $msg->channel == $topic)
            {
                return $msg->payload;
            }
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