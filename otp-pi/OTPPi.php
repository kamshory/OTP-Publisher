<?php
require_once dirname(dirname(__FILE__))."/vendor/autoload.php";

class OTPPi{
    public $config = array();
    public $username = "";
    public $password = "";
    public function __construct()
    {
 
        $configLoader = new ConfigLoader();
        $this->config = $configLoader->load();
        
 
        $this->method = $this->config['GENERAL']['method'];
        $this->username = $this->config['GENERAL']['username'];
        $this->password = $this->config['GENERAL']['password'];
    }

    private function request($payload)
    {
        $otpForwarder = new OTPForwarder($this->config);
        if($this->method == OTPMethod::REST)
        {
            $otpForwarder = new OTPREST($this->config);
        }
        else if($this->method == OTPMethod::WS)
        {
            $otpForwarder = new OTPWS($this->config);
        }
        if($this->method == OTPMethod::REDIS)
        {
            $otpForwarder = new OTPRedis($this->config);
        }
        else if($this->method == OTPMethod::AMQP)
        {
            $otpForwarder = new OTPAMQP($this->config);
        }
        else if($this->method == OTPMethod::MQTT)
        {
            $otpForwarder = new OTPMQTT($this->config);
        }
        $response = $otpForwarder->request($payload);
        $otpForwarder->gc();
        return $response;
    }
    public function createOTP($receiver, $id, $reference, $lifetime, $messageFormat, $params = array(), $subject = NULL)
    {
        $datetime = time();
        $expiration = $datetime + $lifetime;
    
        $param1 = isset($params[0])?$params[0]:'';
        $param2 = isset($params[1])?$params[1]:'';
        $param3 = isset($params[2])?$params[2]:'';
        $param4 = isset($params[3])?$params[3]:'';
    
        if(stripos($receiver, '@') !== false && $subject == NULL)
        {
            $subject = 'Your OTP Code';
        }
    
        $message = array(
            "command"=>"create-otp",
            "data"=>array(
                "date_time"=>$datetime,
                "expiration"=>$expiration,
                "receiver"=>$receiver,
                "message"=>$messageFormat,
                "id"=>$id,
                "reference"=>$reference,
                "param1"=>$param1,
                "param2"=>$param2,
                "param3"=>$param3,
                "param4"=>$param4
            )
        );

        $return = $this->request($message);
        return $return;
    }

    public function validateOTP($receiver, $clearOTP, $reference, $params = array())
    {
        $datetime = time();
    
        $param1 = isset($params[0])?$params[0]:'';
        $param2 = isset($params[1])?$params[1]:'';
        $param3 = isset($params[2])?$params[2]:'';
        $param4 = isset($params[3])?$params[3]:'';
    
        $message = array(
            "command"=>"validate-otp",
            "data"=>array(
                "date_time"=>$datetime,
                "receiver"=>$receiver,
                "otp"=>$clearOTP,
                "reference"=>$reference,
                "param1"=>$param1,
                "param2"=>$param2,
                "param3"=>$param3,
                "param4"=>$param4
            )
        );

        $return = $this->request($message);
        return $return;
    }

    public function sendSMS($receiver, $id, $lifetime, $message)
    {
        $datetime = time();
        $expiration = $datetime + $lifetime;

        $message = array(
            "command"=>"send-sms",
            "data"=>array(
                "date_time"=>$datetime,
                "expiration"=>$expiration,
                "id"=>$id,
                "receiver"=>$receiver,
                "message"=>$message
            )
        );

        $return = $this->request($message);
        return $return;
    }

    public function sendEmail($receiver, $id, $lifetime, $message, $subject)
    {
        $datetime = time();
        $expiration = $datetime + $lifetime;

        $message = array(
            "command"=>"send-email",
            "data"=>array(
                "date_time"=>$datetime,
                "expiration"=>$expiration,
                "id"=>$id,
                "receiver"=>$receiver,
                "message"=>$message,
                "subject"=>$subject
            )
        );

        $return = $this->request($message);
        return $return;
    }

    public function blockMSISDN($receiver)
    {
        $datetime = time();
 
        $message = array(
            "command"=>"block-msisdn",
            "data"=>array(
                "date_time"=>$datetime,
                "receiver"=>$receiver
            )
        );

        $return = $this->request($message);
        return $return;
    }

    public function unblockMSISDN($receiver)
    {
        $datetime = time();
 
        $message = array(
            "command"=>"unblock-msisdn",
            "data"=>array(
                "date_time"=>$datetime,
                "receiver"=>$receiver
            )
        );

        $return = $this->request($message);
        return $return;
    }


}
