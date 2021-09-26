# OTP-Publisher

OTP-Publisher is an application to create, send and validate OTP or One Time Password using SMS (Short Message Service) and email. OTP-Publisher uses the PHP language which is widely used to build web-based applications. 

Message will be consumed by OTP-Pi. See OTP-Pi project on https://github.com/kamshory/OTP-Pi

| Method | Send SMS | Send Email | Block MSISDN | Unblock MSISDN | Create OTP | Validate OTP |
| ---- | ---- | ---- | ---- | ---- | ---- | ---- | 
| REST API | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| RabbitMQ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Redis | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Mosquitto | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| WebSocket | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |

# Topology

Both WebSocket and Message Broker use a topic that can be configured from both sides (sender and receiver).

To use WebSocket, please use the WSMessageBrocker library with the link https://github.com/kamshory/Messenger or you can create your own.

To use RabbitMQ, please open the link https://www.rabbitmq.com/

To use Redis, please open the link https://redis.io/

To use Mosquitto, please open the link https://mosquitto.org/


![OTP-Pi Topology](https://raw.githubusercontent.com/kamshory/OTP-Pi/main/resource/www/lib.assets/images/topology.svg)

### Scenario 1 - App Server Can Access OTP-Pi 

In this scenario, the App Server can directly send the OTP to the OTP-Pi via HTTP.

![OTP-Pi Topology Scenario 1](https://raw.githubusercontent.com/kamshory/OTP-Pi/main/resource/www/lib.assets/images/topology-1.svg)

Users can use a cheap domain and use the Dynamic Domain Name System for free. With the use of port forwarding on the router, OTP-Pi can be accessed from anywhere using a domain or subdomain. In this scenario, the user needs:

1. OTP-Pi
2. Fixed internet connection with public IP (static or dynamic)
3. Router that can do port forwarding
4. Domains whose name servers can be set
5. Dynamic DNS service (free or paid)

In this scenario, the application server can generate and validate the OTP sent for each transaction. OTP creation and validation requires the following parameters:

**reference**

`reference` is unique transaction reference number. This number must be different from one transaction to another. This number is the key to validate the OTP.

**receiver**

`receiver` is the phone number or email address of the recipient.

**param1, param2, param3, param4**

These four parameters are additional information for validating the OTP. These four parameters must be the same between OTP creation and validation. Of course this parameter can be filled with empty strings. Information that can be used as this parameter is for example the sender's account number, the recipient's account number, the transaction amount (in string format), and so on.

OTP-Pi does not store the clear OTP but only stores the hash. In addition, the OTP-Pi immediately deletes the SMS sent immediately after. Thus, the OTP is very safe because it is only known by the recipient.

**1. REST API**

**Create OTP Request**

```http
POST /api/otp HTTP/1.1
Host: sub.domain.tld
Connection: close
User-agent: KSPS
Content-type: application/json
Content-length: 313
Authorization: Basic dXNlcjpwYXNzd29yZA==

{
	"command": "create-otp",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"receiver": "08126666666",
		"message": "Your OTP is %s",
		"reference": "12345678901234567890",
		"param1": "100000",
		"param2": "1234567890",
		"param3": "987654",
		"param4": "674527846556468254"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN of the receiver |
| `data`.message | String | Content format of the SMS. Note that the format must be contains one %s |
| `data`.reference | String | Reference ID of the transaction. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param1 | String | Parameter 1. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param2 | String | Parameter 2. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param3 | String | Parameter 3. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param4 | String | Parameter 4. This value must match between `Create OTP` and `Validate OTP` | 


**Create OTP Response**

```http
HTTP/1.1 200 OK
Host: sub.domain.tld
Connection: close
Content-type: application/json
Content-length: 199

{
	"command": "create-otp",
	"response_code": "000",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
		"reference": "12345678901234567890"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| response_code | String | Response Code | 
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN of the receiver |
| `data`.reference | String | Reference ID of the transaction. This value must match between `Create OTP` and `Validate OTP` | 

**Example**

```php
<?php

require_once "OTPPi.php";

$url = "http://localhost:8899/api/otp";
$username = 'kamshory';
$password = 'kamshory';

/**
 * Parameters to be sent on request OTP
 */

$receiver = '0812661111';

// Reference number from the transaction (generated by your application)
$reference = md5(time());

// OTP ID
$id = time();

// OTP life time (in second)
$lifetime = 30;

// OTP message format (must be contains one %s)
$messageFormat = 'Kode OTP Anda adalah %s';

// Subject (used if receiver is email address)
$subject = 'Kode OTP Anda';

// Additional data from the transaction (must be identic with validate OTP)
$params = array(
    '123456',
    '7890',
    '98765',
    '64875384'
);

$otp = new OTPPi($url, $username, $password);
$resp1 = $otp->createOTP($receiver, $id, $reference, $lifetime, $messageFormat, $params, $subject);

$createResponse = json_decode($resp1, true);
echo "Create OTP : ".$createResponse['response_code']."<br>\r\n";

/*
Response Code List
==============================
SUCCESS              = "0000";
SERIAL_PORT_NULL     = "1000";
UNAUTHORIZED         = "1100";
NO_DEVICE_CONNECTED  = "1101";
FAILED               = "1102";
*/

?>
```

**Validate OTP Request**

```http
POST /api/otp HTTP/1.1
Host: sub.domain.tld
Connection: close
User-agent: KSPS
Content-type: application/json
Content-length: 274
Authorization: Basic dXNlcjpwYXNzd29yZA==

{
	"command": "validate-otp",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
		"otp": "123456",
		"reference": "12345678901234567890",
		"param1": "100000",
		"param2": "1234567890",
		"param3": "987654",
		"param4": "674527846556468254"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN of the receiver |
| `data`.otp | String | Cleat OTP to be valieadted |
| `data`.reference | String | Reference ID of the transaction. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param1 | String | Parameter 1. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param2 | String | Parameter 2. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param3 | String | Parameter 3. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.param4 | String | Parameter 4. This value must match between `Create OTP` and `Validate OTP` | 


**Validate OTP Response**

```http
HTTP/1.1 200 OK
Host: sub.domain.tld
Connection: close
Content-type: application/json
Content-length: 201

{
	"command": "validate-otp",
	"response_code": "000",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
		"reference": "12345678901234567890"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| response_code | String | Response Code | 
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN of the receiver |
| `data`.reference | String | Reference ID of the transaction. This value must match between `Create OTP` and `Validate OTP` | 


```php
<?php

require_once "OTPPi.php";

$url = "http://localhost:8899/api/otp";
$username = 'kamshory';
$password = 'kamshory';

/**
 * Parameters to be sent on request OTP
 */

$receiver = '0812661111';

// Reference number from the transaction (generated by your application)
$reference = md5(time());

// OTP life time (in second)
$lifetime = 30;

// OTP message format (must be contains one %s)
$messageFormat = 'Kode OTP Anda adalah %s';

// Subject (used if receiver is email address)
$subject = 'Kode OTP Anda';

// Additional data from the transaction (must be identic with validate OTP)
$params = array(
    '123456',
    '7890',
    '98765',
    '64875384'
);

$otp = new OTPPi($url, $username, $password);
$resp1 = $otp->createOTP($receiver, $reference, $lifetime, $messageFormat, $params, $subject);

$createResponse = json_decode($resp1, true);
echo "Create OTP : ".$createResponse['response_code']."<br>\r\n";

/*
Response Code List
==============================
SUCCESS              = "0000";
SERIAL_PORT_NULL     = "1000";
UNAUTHORIZED         = "1100";
NO_DEVICE_CONNECTED  = "1101";
FAILED               = "1102";
*/

?>
```


**Send SMS Request**

```http
POST /api/sms HTTP/1.1
Host: sub.domain.tld
Connection: close
User-agent: KSPS
Content-type: application/json
Content-length: 182
Authorization: Basic dXNlcjpwYXNzd29yZA==

{
	"command": "send-sms",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "08126666666",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.reference | String | Reference ID of the transaction. This value must match between `Create OTP` and `Validate OTP` | 
| `data`.receiver | String | MSISDN of the receiver |
| `data`.message | String | Content of the SMS |

**Send Email Request**

```http
POST /api/sms HTTP/1.1
Host: sub.domain.tld
Connection: close
User-agent: KSPS
Content-type: application/json
Content-length: 222
Authorization: Basic dXNlcjpwYXNzd29yZA==

{
	"command": "send-email",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "someone@domain.tld",
		"subject": "Your OTP Code",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | Recipient's email address |
| `data`.message | String | Content of the SMS |


**Block Number Request**

```http
POST /api/block HTTP/1.1
Host: sub.domain.tld
Connection: close
User-agent: KSPS
Content-type: application/json
Content-length: 107
Authorization: Basic dXNlcjpwYXNzd29yZA==

{
	"command": "block-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.msisdn | String | MSISDN number to block |

**Unblock Number Request**

```http
POST /api/unblock HTTP/1.1
Host: sub.domain.tld
Connection: close
User-agent: KSPS
Content-type: application/json
Content-length: 109
Authorization: Basic dXNlcjpwYXNzd29yZA==

{
	"command": "unblock-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to be unblocked |

### Scenario 2 - App Server Can't Access OTP-Pi

In this scenario, the App Server may send the OTP to RabbitMQ Server, Redis Server, Mosquitto Server or WSMessageBroker. WSMessageBroker uses the WebSoket protocol and Basic Authentication. Both App Server and OTP-Pi act as clients of WSMessageBroker.

App Server acts as publisher and OTP-Pi becomes consumer of RabbitMQ Server, Redis Server, Mosquitto Server and WSMessageBroker. Both must use the same topic so that all OTPs sent by the App Server can be received by the OTP-Pi.

![OTP-Pi Topology Scenario 2](https://raw.githubusercontent.com/kamshory/OTP-Pi/main/resource/www/lib.assets/images/topology-2.svg)

From the two scenarios above, the OTP-Pi will send SMS using a GSM modem that is physically attached to the OTP-Pi device. Users can use either RabbitMQ Server, Mosquitto Server or WSMessageBroker and can also use both at the same time. However, if the App Server sends the same OTP to RabbitMQ Server, Mosquitto Server and WSMessageBroker, the OTP-Pi will send the SMS twice to the recipient number.

In this scenario, the user does not need a public IP. Users only need:

1. OTP-Pi
2. Internet connection (no need for public IP and port forwarding)
3. RabbitMQ, Mosquitto or WSMessageBroker servers

**1. RabbitMQ**

**Send SMS Request**

```json
{
	"command": "send-sms",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "08126666666",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | MSISDN of the receiver |
| `data`.message | String | Content of the SMS |

**Send Email Request**

```json
{
	"command": "send-email",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "someone@domain.tld",
		"subject": "Your OTP Code",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | Recipient's email address |
| `data`.message | String | Content of the SMS |

**Block Number Request**

```json
{
	"command": "block-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to block |

**Unblock Number Request**

```json
{
	"command": "unblock-msisdn",
	"data":{
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to be unblocked |

**2. Redis**

**Send SMS Request**

```json
{
	"command": "send-sms",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "08126666666",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | MSISDN of the receiver |
| `data`.message | String | Content of the SMS |

**Send Email Request**

```json
{
	"command": "send-email",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "someone@domain.tld",
		"subject": "Your OTP Code",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | Recipient's email address |
| `data`.message | String | Content of the SMS |

**Block Number Request**

```json
{
	"command": "block-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to block |

**Unblock Number Request**

```json
{
	"command": "unblock-msisdn",
	"data":{
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to be unblocked |

**3. Mosquitto**

**Send SMS Request**

```json
{
	"command":"send-sms",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "08126666666",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | MSISDN of the receiver |
| `data`.message | String | Content of the SMS |

**Send Email Request**

```json
{
	"command": "send-email",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "someone@domain.tld",
		"subject": "Your OTP Code",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | Recipient's email address |
| `data`.message | String | Content of the SMS |

**Block Number Request**

```json
{
	"command": "block-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to block |

**Unblock Number Request**

```json
{
	"command": "unblock-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to be unblocked |

**4. WSMessageBroker**

**Send SMS Request**

```json
{
	"command": "send-sms",
	"data": {	
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "08126666666",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | MSISDN of the receiver |
| `data`.message | String | Content of the SMS |

**Send Email Request**

```json
{
	"command": "send-email",
	"data": {
		"date_time": 1629685778,
		"expiration": 1629685838,
		"id": 123456,
		"receiver": "someone@domain.tld",
		"subject": "Your OTP Code",
		"message": "Your OTP is 1234"
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.id | String | SMS ID |
| `data`.receiver | String | Recipient's email address |
| `data`.message | String | Content of the SMS |

**Block Number Request**

```json
{
	"command": "block-msisdn",
	"data": {
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to block |

**Unblock Number Request**

```json
{

	"command": "unblock-msisdn",
	"data":{
		"date_time": 1629685778,
		"receiver": "08126666666",
	}
}
```

| Parameter | Type | Description |
| --------- | ---- | ----------|
| command | String | Command for OTP-Pi |
| data | Object | Data for OTP-Pi | 
| `data`.date_time | Number | Unix Time Stamp when the message is transmitted by the applications | 
| `data`.receiver | String | MSISDN number to be unblocked |

The WSMessageBroker-based server uses the WebSocket protocol. Please download WSMessageBroker at https://github.com/kamshory/Messenger

**Handhakes**

The handshake between OTP-Pi and WSMessageBroker is as follows:
1. OTP-Pi as client and WSMessageBroker as server
2. OTP-Pi sends request to WSMessageBroker

**WebSocket Subscriber Configuration Example**

| Parameter | Value |
|--|--|
| Host | domain.example |
| Port | 8000 |
| Path | /ws |
| Username | username |
| Password | password |
| Topic | sms |

**Example of a WebSocket Handhake**

```http
GET /ws?topic=sms HTTP/1.1
Host: domain.example:8000
Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==
Sec-WebSocket-Version: 13
```

The server will verify whether the username and password are correct. If true, the server will add the connection to the list of recipients of the message.

When a client sends a message, the message will be sent to all clients by topic except the sender. Thus, the handshake between the sender and the recipient of the message is the same.

The OTP-Pi never sends messages to the WSMessageBroker server. OTP-Pi only accepts messages according to the desired topic.

## Subscribe to Our YouTube Channel

https://www.youtube.com/channel/UCY-qziSbBmJ7iZj-cXqmcMg

## Donate to Our Developer

[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=DMHFJ6LR7FGQS)

