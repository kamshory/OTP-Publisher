<?php
class OTPPi
{
    private $method = 'POST';
    private $username = NULL;
    private $secure = NULL;

    public function __construct($url = NULL, $username = NULL, $passkey = NULL)
    {
       
        if($url != NULL)
        {
            $this->url = $url;
        }
        if($username != NULL)
        {
            $this->username = $username;
        }
        if($passkey != NULL)
        {
            $this->secure = $passkey;
        }
        
    }
    
    public function createHeader($header = NULL, $username = NULL, $passkey = NULL)
    {
        if($username != NULL)
        {
            
            $passkey = ($passkey==NULL)?'':$passkey;
            $auth = 'Authorization: Basic '.base64_encode($username.':'.$passkey);
            if($header == NULL)
            {
                $header = array($auth);
            }
            else if(is_array($header))
            {
                $header[] = $auth;
            }
        }
        return $header;
    }
    
    public function requestHTTP($body = NULL, $header = NULL)
    {
        $method = $this->method;
        $username = $this->username;
        $passkey = $this->secure;

        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
    
        $header = $this->createHeader($header, $username, $passkey);
    
        if($header != NULL && is_array($header))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        
    
        if($method = "POST" && $body != NULL)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $server_output = curl_exec($ch);
        
        curl_close ($ch);
        return $server_output;
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
    
        $message = json_encode(array(
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
        ));

        $return = $this->requestHTTP($message);
        return $return;
    }

    public function validateOTP($receiver, $clearOTP, $reference, $params = array())
    {
        $datetime = time();
    
        $param1 = isset($params[0])?$params[0]:'';
        $param2 = isset($params[1])?$params[1]:'';
        $param3 = isset($params[2])?$params[2]:'';
        $param4 = isset($params[3])?$params[3]:'';
    
        $message = json_encode(array(
            "command"=>"create-otp",
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
        ));

        $return = $this->requestHTTP($message);
        return $return;
    }

    public function sendSMS($receiver, $id, $lifetime, $message)
    {
        $datetime = time();
        $expiration = $datetime + $lifetime;

        $message = json_encode(array(
            "command"=>"send-sms",
            "data"=>array(
                "date_time"=>$datetime,
                "expiration"=>$expiration,
                "id"=>$id,
                "receiver"=>$receiver,
                "message"=>$message
            )
        ));

        $return = $this->requestHTTP($message);
        return $return;
    }

    public function sendEmail($receiver, $id, $lifetime, $message, $subject)
    {
        $datetime = time();
        $expiration = $datetime + $lifetime;

        $message = json_encode(array(
            "command"=>"send-email",
            "data"=>array(
                "date_time"=>$datetime,
                "expiration"=>$expiration,
                "id"=>$id,
                "receiver"=>$receiver,
                "message"=>$message,
                "subject"=>$subject
            )
        ));

        $return = $this->requestHTTP($message);
        return $return;
    }

    public function blockMSISDN($receiver)
    {
        $datetime = time();
 
        $message = json_encode(array(
            "command"=>"block-msisdn",
            "data"=>array(
                "date_time"=>$datetime,
                "receiver"=>$receiver
            )
        ));

        $return = $this->requestHTTP($message);
        return $return;
    }

    public function unblockMSISDN($receiver)
    {
        $datetime = time();
 
        $message = json_encode(array(
            "command"=>"unblock-msisdn",
            "data"=>array(
                "date_time"=>$datetime,
                "receiver"=>$receiver
            )
        ));

        $return = $this->requestHTTP($message);
        return $return;
    }
    
}

?>