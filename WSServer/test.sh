#!/bin/bash
message=$(curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Host: www.planetbiru.com" -H "Origin: htt" https://www.planetbiru.com/lib.wss/ | grep Ratchet)
if [ -z "$message" ];
then
	nohup /bin/java -jar /apps/disbursement/disbursement.jar &
else
	echo "The HTTP server already running!"
fi