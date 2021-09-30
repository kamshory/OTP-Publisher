<?php
session_start();
?><!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<script type="text/javascript" src="jquery/jquery.min.js"></script>
<style type="text/css">
body{
	font-family:arial,sans-serif;
	margin:0px;
}
@media screen and (min-width:900px)
{
.planet-chat-box{
	width:300px;
	border:1px solid #DDDDDD;
	box-sizing: border-box;
}
.message-container{
	height:360px;
	overflow:auto;
}
}
@media screen and (max-width:899px)
{
.message-container{
	height:calc(100vh - 86px);
	overflow:auto;
}
}
.chat-box-header{
	background-color:#3d91bf;
	position:relative;
}
.chat-box-control{
	z-index:10;
	position:absolute;
	right:0;
	top:0;
	width:48px;
	padding-top:8px;
	padding-right:10px;
	text-align:right;
}
.chat-box-control > span{
	display:inline-block;
	text-align:center;
}
.chat-box-control > span a{
	display:inline-block;
	width:20px;
	height:20px;
	text-align:center;
	color:#FFFFFF;
	text-decoration:none;
}
.chat-box-header h3{
	margin:0;
	padding:0;
	font-size:15px;
	line-height:1.25;
	font-weight:normal;
}
.chat-box-header h3 a{
	padding:8px 40px 8px 10px;
	display:block;
	color:#FFFFFF;
	text-decoration:none;
}


.message-item{
	padding:4px 10px;
}

.planet-chat-box .from-partner .message-sender::before {
	content:"";
	display:inline-block;
	width:9px;
	height:9px;
	border-radius:5px;
    background: #86BB71;
	margin-right:4px;
}
.planet-chat-box .from-partner .message-sender {
	float:left;
	padding-right:10px;
}
.planet-chat-box .from-me .message-sender {
	float:right;
	padding-left:10px;
	
}
.planet-chat-box .from-me .message-time {
	text-align:right;
}
.planet-chat-box .from-partner .message-time {
}
.planet-chat-box .from-me .message-sender::after {
	content:"";
	display:inline-block;
	width:9px;
	height:9px;
	border-radius:5px;
    background: #94C2ED;
	margin-left:4px;
}

.planet-chat-box .message-text {
    color: white;
    padding: 16px 18px;
    font-size: 16px;
    border-radius: 7px;
    margin-bottom: 8px;
    max-width: 92%;
    position: relative;
	margin-top:10px;
}

.planet-chat-box .from-partner .message-text {
    background: #86BB71;
	float:left;
}

.planet-chat-box .from-me .message-text {
    background: #94C2ED;
	float:right;
}


.planet-chat-box .message-text::after {
    bottom: 100%;
    border: solid transparent;
	border-top-width: medium;
	border-right-width: medium;
	border-bottom-color: transparent;
	border-bottom-width: medium;
	border-left-width: medium;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    border-bottom-color: #86BB71;
    border-width: 10px;
    margin-left: -10px;
}
*, ::before, ::after {
    box-sizing: border-box;
}

.planet-chat-box .from-partner .message-text::after {
    border-bottom-color: #86BB71;
    left: 22px;
}
.planet-chat-box .from-me .message-text::after {
    border-bottom-color: #94C2ED;
    right: 14px;
}
*, ::before, ::after {
    box-sizing: border-box;
}
.float-right {
    float: right;
}

.message-form{
	padding:10px 10px;
	position:relative;
}
.message-form input[type="text"]{
	padding:6px 10px;
	display:block;
	width:100%;
	box-sizing:border-box;
	outline:none;
	border:1px solid #EEEEEE;
}
.message-form input[type="text"]:hover{
	outline:none;
}

.clearfix::after {
    display: block;
    content: "";
    clear: both;
}
.clearfix-top::before {
    display: block;
    content: "";
    clear: both;
}
</style>

</head>
<body>
	
<div class="planet-chat-container">
</div>

	

	<script>
	
	
	function PlanetMessage()
	{
		this.myID = '';
		this.messages = {};
		this.partners = {};
		this.addMessage = function(message, callbackFunctions)
		{
			var i, data;
			var obj = JSON.parse(message);
			
			var callbackFunction = callbackFunctions[obj.command];
			
			if(obj.command == 'send-message')
			{
				if(obj.data.length)
				{
					for(i in obj.data)
					{
						data = obj.data[i];
						var partner_id = data.partner_id;
						if(typeof this.messages[partner_id] == 'undefined')
						{
							this.messages[partner_id] = [];
						}
						this.messages[partner_id].push(data);
						this.partners[partner_id] = {partner_id:data.partner_id, partner_uri:data.partner_uri, partner_name:data.partner_name};
						if(typeof callbackFunction == 'function')
						{
							callbackFunction(partner_id, data);
						}
					}
				}
			}
			if(obj.command == 'log-in')
			{
				this.myID = obj.data[0].my_id;
				if(typeof callbackFunction == 'function')
				{
					callbackFunction(this.myID, obj.data[0]);
				}
			}
		}
		this.renderMessage = function(message)
		{
			var obj = JSON.parse(message);
		}
	}
	
	function planetChat(container, pMessage, url)
	{
		this.container = container;
		this.pMessage = pMessage ;
		this.conn = null;
		this.container = container;
		this.myID = 0;
		this.url = url;
		this.connected = false;
		this.firstConnect = true;
		
		
		this.init = function()
		{
			this.connect();
		}
		this.connect = function(url)
		{
			console.log('Connecting...');
			if(!url)
			{
				url = this.url;
			}
			try
			{
				this.conn = new WebSocket(url);
				this.conn.opopen = function(e){
					_this.connected = true;
					_this.firstConnect = false;
					setTimeout(_this.reconnectTimeout);
					_this.onOpen(e);
				}
				this.conn.operror = function(e){
					_this.connected = false;
					_this.firstConnect = false;
					_this.onError(e);
					_this.reconnect();
				}
				this.conn.onclose = function(e){
					_this.connected = false;
					_this.firstConnect = false;
					_this.onClose(e);
					_this.reconnect();
				}
				this.conn.onmessage = function(e){
					_this.onMessage(e);
				}
			}
			catch(e)
			{
				console.log(e);
			}
		}
		this.reconnectTimeout = null;
		this.reconnect = function()
		{
			if(_this.connected)
			{
				setTimeout(_this.reconnectTimeout);
			}
			this.reconnectTimeout = setTimeout(function(){
				_this.connect();
			}, 5000);
		}
		this.onOpen = function(e)
		{
		};
		this.onError = function(e)
		{
			console.log(e);
		};
		this.onClose = function(e)
		{
		};
		this.onMessage = function(e)
		{
		};
		this.send = function(message)
		{
			this.conn.send(message);
		};
		this.newChatBox = function(partnerID, partnerURI, partnerName, partnerTheme)
		{
			var box = this.container.find('.planet-chat-box[data-partner-id="'+partnerID+'"]') || [];
			if(box.length)
			{
				this.showMessage(partnerID);
			}
			else
			{
				this.createChatBox(partnerID, partnerURI, partnerName, partnerTheme);
				this.showMessage(partnerID);
			}
		};
		this.createChatBox = function(partnerID, partnerURI, partnerName, partnerTheme)
		{
			var html = '';
			html += '<div class="planet-chat-box" data-partner-id="'+partnerID+'" data-show="true">\r\n'+
				'    	<div class="chat-box-header">\r\n'+
				'        	<div class="chat-box-control">\r\n'+
				'            	<span><a href="#" title="Close chat">X</a></span>\r\n'+
				'            	<span><a href="#" title="Setting">I</a></span>\r\n'+
				'            </div>\r\n'+
				'        	<h3><a href="kamshory">'+partnerName+'</a></h3>\r\n'+
				'        </div>\r\n'+
				'    	<div class="message-container">\r\n'+
				'        </div>\r\n'+
				'        <div class="message-form">\r\n'+
				'            <form class="chat-form" action="" method="post">\r\n'+
				'            	<input type="text" class="text-message" placeholder="Type your message here...">\r\n'+
				'            </form>\r\n'+
				'        </div>\r\n'+
				'    </div>\r\n';
			$(this.container).append(html);	
		};
		this.showMessage = function(partnerID)
		{
		};
		this.deleteMessage = function(partnerID, messageID)
		{
			var messageData = {
					'command':'delete-message',
					'data':[{
						'partner_id': partnerID,
						'message_id': messageID
					}]
				}
			var messageDataJson = JSON.stringify(messageData);
			this.conn.send(messageDataJson);
		};
		this.onBeforeSendMessage = function()
		{
		};
		this.onSendMessage = function()
		{
		}
		this.onAfterSendMessage = function()
		{
		};
		var _this = this;
		this.init();
		
	}
	
	var url = 'wss://www.planetbiru.com/lib.wss/';
	var pMessage = new PlanetMessage();
	var pChat = new planetChat('.planet-chat-container', pMessage, url);
	
	pChat.onOpen = function(e){
	    console.log("Connection established!");	    
	};
	pChat.onError = function(e) {
	  console.error("WebSocket error observed:", e);
	};
	pChat.onClose = function(e) {
	  console.error("WebSocket closed:", e);
	};
	pChat.onMessage = function(e){		
		pChat.pMessage.addMessage(e.data, 
			{
				'send-message':function(partner_id, data){
					var message = '';
					if(data.partner_id == data.sender_id)
					{
						message =
						'        	<div class="message-item clearfix from-partner" data-timestamp="">\r\n'+
						'            	<div class="message-sender">'+data.sender_name+'</div>\r\n'+
						'            	<div class="message-time">2020-10-10 10:10:10</div>\r\n'+
						'            	<div class="message-text">'+data.message.text+'</div>\r\n'+
						'            	<div class="message-controller clearfix-top">\r\n'+
						'                	<span>Delete</span>\r\n'+
						'                </div>\r\n'+
						'            </div>\r\n'
					}
					else
					{
						message =
						'        	<div class="message-item clearfix from-me" data-timestamp="">\r\n'+
						'            	<div class="message-sender">'+data.sender_name+'</div>\r\n'+
						'            	<div class="message-time">2020-10-10 10:10:10</div>\r\n'+
						'            	<div class="message-text">'+data.message.text+'</div>\r\n'+
						'            	<div class="message-controller clearfix-top">\r\n'+
						'                	<span>Delete</span>\r\n'+
						'                </div>\r\n'+
						'            </div>\r\n'
					}
					var messageBox = $(pChat.container).find('.planet-chat-box[data-partner-id="'+data.partner_id+'"] .message-container');
					if(messageBox.length != 0)
					{
						messageBox.append(message);
					}
					else
					{
						var user_id = data.partner_id;
						var user_name = data.partner_name;
						var partner_uri = data.partner_uri;
						pChat.createChatBox(user_id, partner_uri, user_name, '');
						messageBox = $(pChat.container).find('.planet-chat-box[data-partner-id="'+data.partner_id+'"] .message-container');
						messageBox.append(message);
					}
					$(messageBox).scrollTop(messageBox.prop('scrollHeight'));
					console.log(pChat.pMessage.partners);
					
				},
				'delete-message':function(partner_id, partner_uri, data){
					console.log('Delete message');			
					console.log(partner_id);
					console.log(data);
				},
				'log-in':function(my_id, data){
					pChat.myID = my_id;
					console.log('Log in');			
					console.log(my_id);
					console.log(data);
				},
				'update-online-user':function(data){	
					console.log('Update user online');		
					console.log(data);
				}
			}
		);	   
	};
	
	$(function(){
		
		$(document).on('submit', '.planet-chat-box form', function(e){
			e.preventDefault();
			var message = $(this).find('.text-message').val();
			var partner_id = $(this).closest('.planet-chat-box').attr('data-partner-id');
			var messageData = {
				'command':'send-message',
				'data':[{
					'partner_id': partner_id,
					'receiver_id': partner_id,
					'message': {
						'text':message
					}
				}]
			}
			var messageDataJson = JSON.stringify(messageData);			
			pChat.send(messageDataJson);
			$('.text-message').val('');
		});
		$(document).on('click', '.user-item a', function(e){
			var user_id = $(this).attr('data-user-id');
			var user_name = $(this).attr('data-user-name');
			var partner_uri = $(this).closest('.planet-chat-box').attr('data-partner-uri');
			pChat.createChatBox(user_id, partner_uri, user_name, '');
			e.preventDefault();
		});
		
		
	});
	
	</script>

</div>
<?php
mysql_connect("cebong.planetbiru.com", "root", "CebongBangsat2017");
mysql_select_db("planetbiru");
$sql = "select * from member order by last_activity_time desc limit 0, 50";
$res = mysql_query($sql);

?>
<div class="unread-message">
</div>
<div class="online-user">
	<ul>
    <?php
	while(($data = mysql_fetch_assoc($res)))
	{
	?>
    	<li class="user-item">
        	<a href="#" data-user-id="<?php echo $data['member_id'];?>" data-user-uri="<?php echo $data['username'];?>" data-user-name="<?php echo $data['name'];?>">
            	<div class="user-item">
                    <div class="user-image"><img src="https://avatar.planetbiru.com/<?php echo $data['member_id'];?>/uimage-50.jpg?hash=6368"></div>
                    <div class="user-item-inner">
                        <div class="user-last-update">1 min</div>
                        <div class="user-name"><?php echo $data['name'];?> <span class="user-gender"><?php if($data['gender'] == 'M') echo '&male;'; if($data['gender'] == 'W') echo '&female;';?></span></div>
                    </div>
                </div>
            </a>
        </li>
      <?php
	}
	?>
    </ul>
</div>

<style type="text/css">
@media screen and (min-width:600px) {
	.online-user ul li{
		width:300px;
		display:inline-block;
		box-sizing:border-box;
	}
}
.user-item{
	position:relative;
}
.user-image{
	position:absolute;
}
.user-item-inner{
	padding-left:60px;
	box-sizing:border-box;
}
.online-user > ul{
	margin:0;
	padding:0;
}
.online-user > ul > li{
	margin:0;
	padding:0;
}
.online-user > ul > li > a{
	border:1px solid #DDDDDD;
	display:block;
	padding:5px;
	box-sizing:border-box;
	height:60px;
}
</style>

</body>
</html>