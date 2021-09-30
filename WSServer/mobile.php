<?php
session_start();
?><!DOCTYPE html>
<html>
<head>
<script type="text/javascript" src="jquery/jquery.min.js"></script>
<style type="text/css">
body{
	font-family:arial,sans-serif;;
}
.planet-chat-box{
	width:300px;
	border:1px solid #DDDDDD;
	box-sizing: border-box;
}
.message-container{
	height:360px;
	overflow:auto;
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
.message-item:nth-child(odd){
	background:#FAFAFA;
}
.message-item:nth-child(event){
	background:#FDFDFD;
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
		
		this.init = function()
		{
			this.conn = new WebSocket(url);
			this.conn.opopen = function(e){
				_this.onOpen(e);
			}
			this.conn.onmessage = function(e){
				_this.onMessage(e);
			}
		}
		this.connect = function(url)
		{
			this.conn = new WebSocket(url);
		}
		this.onOpen = function(e)
		{
		};
		this.onMessage = function(e)
		{
		};
		this.send = function(message)
		{
			this.conn.send(message);
		};
		this.newChatBox = function(partnerID, partnerName, partnerTheme)
		{
			var box = this.container.find('.planet-chat-box[data-partner-id="'+partnerID+'"]') || [];
			if(box.length)
			{
				this.showMessage(partnerID);
			}
			else
			{
				this.createChatBox(partnerID, partnerName, partnerTheme);
				this.showMessage(partnerID);
			}
		};
		this.createChatBox = function(partnerID, partnerName, partnerTheme)
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
	pChat.onMessage = function(e){		
		console.log(e);	
		pChat.pMessage.addMessage(e.data, 
			{
				'send-message':function(partner_id, data){
					console.log('Receive message');			
					console.log(partner_id);
					console.log(data);
					$(pChat.planet-chat-container).find('.planet-chat-box[data-partner-id="'+partner_id+'"] .message-container').append(
					'        	<div class="message-item" data-timestamp="">\r\n'+
					'            	<div class="message-time">2020-10-10 10:10:10</div>\r\n'+
					'            	<div class="message-text">'+data.message.text+'</div>\r\n'+
					'            	<div class="message-controller">\r\n'+
					'                	<span>Delete</span>\r\n'+
					'                </div>\r\n'+
					'            </div>\r\n'
					);
					
				},
				'delete-message':function(partner_id, data){
					console.log('Delete message');			
					console.log(partner_id);
					console.log(data);
				},
				'log-in':function(my_id, data){
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
			
			console.log(messageData);
			var messageDataJson = JSON.stringify(messageData);			
			pChat.send(messageDataJson);
			$('.text-message').val('');
		});
		pChat.createChatBox(2, 'Kamshory Roy', '');
		
		
	});
	
	</script>

</div>

<div class="online-user">
	<ul>
    	<li>
        	<a href="2">
            	<div class="user-item">
                    <div class="user-image"><img src="https://avatar.planetbiru.com/2/uimage-50.jpg?hash=6368"></div>
                    <div class="user-item-inner">
                        <div class="user-last-update">1 min</div>
                        <div class="user-name">Kamshory</div>
                    </div>
                </div>
            </a>
        </li>
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