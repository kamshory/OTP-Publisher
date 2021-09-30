<?php
class OTPREST extends OTPForwarder{
    public $method = "POST";
    public $username = "";
    public $passkey = "";
    public function __construct($config)
    {
        parent::__construct($config);
        $this->username = $config['REST']['username'];
        $this->passkey = $config['REST']['password'];
        $this->url = $config['REST']['url'];
    }
    public function request($requestJSON)
    {
        if($this->manageOTP && $requestJSON['command'] ==  'create-otp')
        {
            try
            {
                $otpRequest = $this->createOTP($requestJSON);
                return $this->requestHTTP(json_encode($otpRequest), array());
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
    
    private function createHeader($header = NULL, $username = NULL, $passkey = NULL, $body = NULL)
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
        else
        {
            if($header == NULL)
            {
                $header = array();
            }
        }
       
        if($body != NULL)
        {
            $header[] = 'Content-length: '.strlen($body);
        }
        return $header;
    }
    
    private function requestHTTP($body = NULL, $header = NULL)
    {
        $method = $this->method;
        $username = $this->username;
        $passkey = $this->passkey;

        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
     
        $header = $this->createHeader($header, $username, $passkey, $body);
    
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
        return json_decode($server_output, true);
    }
    
}