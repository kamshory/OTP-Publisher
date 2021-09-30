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
        $username = $this->username;
        $password = $this->passkey;
        $connectionSettings = (new \PhpMqtt\Client\ConnectionSettings)

            // The username used for authentication when connecting to the broker.
            ->setUsername($username)
            
            // The password used for authentication when connecting to the broker.
            ->setPassword($password)
            
            // The connect timeout defines the maximum amount of seconds the client will try to establish
            // a socket connection with the broker. The value cannot be less than 1 second.
            ->setConnectTimeout(60)
            
            // The socket timeout is the maximum amount of idle time in seconds for the socket connection.
            // If no data is read or sent for the given amount of seconds, the socket will be closed.
            // The value cannot be less than 1 second.
            ->setSocketTimeout(5)
            
            // The resend timeout is the number of seconds the client will wait before sending a duplicate
            // of pending messages without acknowledgement. The value cannot be less than 1 second.
            ->setResendTimeout(10)
            
            // The keep alive interval is the number of seconds the client will wait without sending a message
            // until it sends a keep alive signal (ping) to the broker. The value cannot be less than 1 second
            // and may not be higher than 65535 seconds. A reasonable value is 10 seconds (the default).
            ->setKeepAliveInterval(10)
            
            ;

        $mqtt = new \PhpMqtt\Client\MqttClient($this->host, $this->port, $this->clientID);
        $mqtt->connect($connectionSettings, true);
        $mqtt->publish($topic, $message, 0);
        $mqtt->disconnect();
        return true;
    }
}