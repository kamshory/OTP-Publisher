kill $(lsof -t -i:8080)
nohup /bin/php -q /var/www/html/chat/bin/server.php &

