<?php
session_start();
?><!DOCTYPE html>
<html>
<head>
</head>
<body>

<div class="container">
	
	<div class="chat"></div>
	
	<form id="message-form">
	
    	<div><input type="text" name="partner_id" id="partner_id" /></div>
		<div><input type="text" name="message" id="message" placeholder="Chat here..." /></div>
		<button name="send" id="send" class="btn">Send</button>
	
	</form>
	
	<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
	
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
						var partner_id = data.sender_id;
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
	
	function planetChat(container, pMessage, conn)
	{
		this.container = container;
		this.pMessage = pMessage ;
		this.conn = conn ;
		this.container = container;
		this.init = function(){
		};
		this.newChatBox = function(partnerID)
		{
			var box = this.container.find('.planet-chat-box[data-partner-id="'+partnerID+'"]') || [];
			if(box.length)
			{
				this.showMessage(partnerID);
			}
			else
			{
				this.createChatBox(partnerID);
				this.showMessage(partnerID);
			}
		};
		this.createChatBox = function(partnerID)
		{
			
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
		}
		
	}
	
	var conn = new WebSocket('ws://www.planetbiru.com/lib.wss/');
	conn.onopen = function(e){
	    console.log("Connection established!");	    
	};
	conn.onmessage = function(e){			
		console.log(e);
		pMessage.addMessage(e.data, 
			{
				'send-message':function(partner_id, data){			
					console.log(partner_id);
					console.log(data);
				},
				'delete-message':function(partner_id, data){			
					console.log(partner_id);
					console.log(data);
				},
				'log-in':function(my_id, data){			
					console.log(my_id);
					console.log(data);
				},
				'update-online-user':function(data){			
					console.log(data);
				}
			}
		);	    
	};
	var pMessage = new PlanetMessage();
	var pChat = new planetChat();
	
	$(function(){
		
		$('#message-form').submit(function(e){
		
			e.preventDefault();
			var message = $('#message').val();
			var partner_id = $('#partner_id').val();
			var messageData = {
				'command':'send-message',
				'data':[{
					'receiver_id': partner_id,
					'message': {
						'text':message
					}
				}]
			}
			
			var messageDataJson = JSON.stringify(messageData);			
			conn.send(messageDataJson);
			addMessage(message);			
			el.val('');
			
		});
		
		
	});
	
	function addMessage(message)
	{
		
		$('.chat').append('<div class="chat-message">' + message + '</div>');
		
	}
	
	</script>

</div>

</body>
</html>