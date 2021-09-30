<?php
class OTPForwarder {
    public $config = array();
    public $cacheDir = '';
    public $manageOTP = false;
    public $cacheMaxAge = 600;
    public $otpSalt = 'iufuiwehfiwhefiuw8y882y3r';
    public function __construct($config)
    {
        $this->config = $config;
        $this->cacheDir = $this->config['GENERAL']['cache_dir'];
        $this->manageOTP = $config['GENERAL']['manage_otp'];
        $this->cacheMaxAge = $config['GENERAL']['cache_max_age'];
    }
    private function createHash($otpID, $plainOTP, $receiver, $param1, $param2, $param3, $param4)
    {
        return hash('sha512', $otpID.":".$plainOTP.":".$receiver.":".$param1.":".$param2.":".$param3.":".$param4.":".$this->otpSalt);
    }
    public function fixPath($path)
    {
        $os = PHP_OS;
        if(stripos($os, 'WIN') !== false)
        {
            $path = str_replace("/", "\\", $path);
        }
        else
        {
            $path = str_replace("\\", "/", $path);
        }
        return $path;
    }

    public function createOTP($requestJSON)
    {
        $plainOTP = sprintf("%06d", mt_rand(0, 999999));
        $id = $requestJSON['data']['id'];
        $datetime = $requestJSON['data']['date_time'];
        $expiration = $requestJSON['data']['expiration'];
        $receiver = $requestJSON['data']['receiver'];
        $param1 = $requestJSON['data']['param1'];
        $param2 = $requestJSON['data']['param2'];
        $param3 = $requestJSON['data']['param3'];
        $param4 = $requestJSON['data']['param4'];
        
        $subject = isset($requestJSON['data']['subject'])?$requestJSON['data']['subject']:'';
        $reference = $requestJSON['data']['reference'];
        $message = sprintf($requestJSON['data']['message'], $plainOTP);
        $filename = $this->fixPath(rtrim($this->cacheDir, '/').'/'.md5($reference).".bin");
        $hash = $this->createHash($reference, $plainOTP, $receiver, $param1, $param2, $param3, $param4);

        if(file_exists($filename))
        {
            throw new DuplicatedException("Duplicated");
        }
        else
        {
            $fileContent = json_encode(array("hash"=>$hash, "expiration"=>$expiration));
            file_put_contents($filename, $fileContent);
            $result = array(
                'command'=>'send-message',
                'data'=>array(
                    'date_time'=>$datetime,
                    'expiration'=>$expiration,
                    'id'=>$id,
                    'receiver'=>$receiver,
                    'message'=>$message,
                    'subject'=>$subject
                )
            );
        }
        return $result;
    }
    public function validateOTP($requestJSON)
    {
        $result = array();
        $datetime = $requestJSON['data']['date_time'];
        $receiver = $requestJSON['data']['receiver'];
        $plainOTP = $requestJSON['data']['otp'];
        $param1 = $requestJSON['data']['param1'];
        $param2 = $requestJSON['data']['param2'];
        $param3 = $requestJSON['data']['param3'];
        $param4 = $requestJSON['data']['param4'];
        
        $reference = $requestJSON['data']['reference'];
        $filename = $this->fixPath(rtrim($this->cacheDir, '/').'/'.md5($reference).".bin");
        
        $match = false;
        $expire = false;

        if(file_exists($filename))
        {
            $fileContent = file_get_contents($filename);
            $json = json_decode($fileContent, true);
            if($json['expiration'] > time())
            {
                $hash = $this->createHash($reference, $plainOTP, $receiver, $param1, $param2, $param3, $param4);
                $match = ($json['hash'] == $hash);          
            }      
            else
            {
                $expire = true;
            }
            if(($expire || $match))
            {
                @unlink($filename);
            }
        }
 
        $result = array(
            'command'=>'validate-otp',
            'response_code'=>($match)?'0000':'1102',
            'data'=>array(
                'date_time'=>$datetime,
                'receiver'=>$receiver,
                'reference'=>$reference
            )
        );
       return $result;
    }
    public function gc()
    {
        $dir = $this->fixPath($this->cacheDir);
        $files = scandir($dir);
        foreach($files as $key => $value)
        {
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path) && (time() - filemtime($path)) > $this->cacheMaxAge) 
            {
                @unlink($path);
            } 
            else if($value != "." && $value != "..") 
            {
                // Do nothing
            }  
        } 
    }
    function rejectRequest($requestJSON, $responseCode, $e)
    {
        return array(
            'command'=>'request-otp',
            'response-code'=>$responseCode,
            'error_message'=>$e->getMessage(),
            'data'=>new StdClass()
        );
    }
}