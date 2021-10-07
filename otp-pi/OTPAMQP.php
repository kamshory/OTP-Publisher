<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
$instance = new StdClass();
class OTPAMQP extends OTPForwarder {
    public $username = '';
    public $passkey = '';
    public $port = 6379;
    public $topic = 'sms';
    public $waiting = true;
    public static $message = "";
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

    public static function callback($job)
    {
        self::$message = $job->body;
    }
    
    public function subscribe($topic)
    {
        try
        {

             
            $connection = new AMQPStreamConnection($this->host, $this->port, $this->username, $this->passkey);
            $channel = $connection->channel();

            # Create the queue if it doesnt already exist.
            $channel->queue_declare(
                $topic,
                false,
                true,
                false,
                false,
                false,
                null,
                null
            );      
            
            $callback = function($msg){
                global $instance;
                $job = json_decode($msg->body, $assocForm=true);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $instance->response = $job;
            };
            
            $channel->basic_qos(null, 1, null);
            
            $channel->basic_consume(
                $topic,
                '',
                false,
                false,
                false,
                false,
                'OTPAMQP::callback'
            );
            $i = 0;
            $channel->wait(null, true);
        
            $channel->close();
            $connection->close();
        }
        catch(AMQPTimeoutException $e)
        {
            
        }
        return self::$message;
        
    }
    public function publish($topic, $message)
    {
        $connection = new AMQPStreamConnection($this->host, $this->port, $this->username, $this->passkey);
        $channel = $connection->channel();

        $channel->queue_declare($topic, true, false, false, true);

        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, '', $topic);
    }
}