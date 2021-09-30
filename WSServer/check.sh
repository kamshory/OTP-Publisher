#!/bin/bash
message=$(curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Host: www.planetbiru.com" -H "Origin: htt" https://www.planetbiru.com/lib.wss/ | grep "Ratchet")
if [ -z "$message" ];
then
  nohup /bin/php -q /var/www/html/lib.websocket/bin/server.php &  
else
  echo "The WebSocket server already running!"
fi
