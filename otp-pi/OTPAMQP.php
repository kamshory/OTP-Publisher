<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class OTPAMQP extends OTPForwarder {
    public $username = '';
    public $passkey = '';
    public $port = 6379;
    public $topic = 'sms';
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['AMQP']['username'];
        $this->passkey = $config['AMQP']['password'];
        $this->host = $config['AMQP']['host'];
        $this->port = $config['AMQP']['port'];
        $this->topic = $config['AMQP']['topic'];
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
        $connection = new AMQPStreamConnection($this->host, $this->port, $this->username, $this->passkey);
        $channel = $connection->channel();

        $channel->queue_declare($topic, false, false, false, false);

        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, '', $topic);
    }
}