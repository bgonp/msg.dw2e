var forms;
var tabs_buttons, tabs_contents;
var alert_msg;

var mensajes, empty_msg;
var chats, empty_chat, current_chat;
var friends, empty_friend;
var requests, empty_request;

var last_msg = -1;
var check_messages = true;
var check_chats = true;

$(document).ready(function() {

	forms = $('#main form[action="ajax.php"]');
	tabs_buttons = $('#main .tabs a.tab');
	tabs_contents = $('#main .tab-content');
	alert_msg = $('#alert-msg');
	mensajes = $('#messages .messages-list');
	empty_msg = $('#messages .empty-message');
	chats = $('#chats .chats-list');
	empty_chat = $('#chats .empty-chat');
	friends = $('#friends .friends-list');
	empty_friend = $('#friends .empty-friend');
	requests = $('#requests .requests-list');
	empty_request = $('#requests .empty-request');

	forms.submit(function(event) {
		event.preventDefault();
		var url = $(this).attr('action');
		var type = $(this).attr('method');
		var formData = new FormData(this);
		$.ajax({
			url: url,
			type: type,
			data: formData,
			success: function(data) {
				data = JSON.parse(data);
				if (data.update)
					switch (data.update) {
						case 'chats': updateChats(true); break;
						case 'messages': updateMessages(true); break;
						case 'page': location.reload(); break;
						default: showAlert('error', 'Error al recibir información del servidor');
					}					
				else if (data.message)
					showAlert(data.type, data.message);
			},
	        cache: false,
	        contentType: false,
	        processData: false
		});
	});

	chatsClicables(chats.find('.a-chat a'));

	tabs_buttons.click(function(event) {
		event.preventDefault();
		tabs_buttons.removeClass('active');
		tabs_contents.hide();
		$(this).addClass('active');
		$('.tab-content.' + $(this).data('target')).show();
	});

	alert_msg.find('a.close').click(function(event){
		event.preventDefault();
		alert_msg.fadeOut(200);
	});
	
	setInterval(function() {
		updateChats();
		if (current_chat) updateMessages();
	}, 1000);

});

function showAlert(type, message) {
	alert_msg.removeClass();
	alert_msg.addClass(type);
	alert_msg.find('p').text(message);
	alert_msg.show();
}

function loadChat(chat_id) {
	$.post("ajax.php", { action: "loadChat", chat_id: chat_id }, function(data) {
		data = JSON.parse(data);
		if (data.message) {
			showAlert(data.type, data.message);
		} else {
			current_chat = data.id;
			mensajes.find('.a-mensaje').remove();
			$('#send-message-form input[name="chat_id"]').val(current_chat);
			$('#send-message-form input[name="mensaje"]').prop('disabled', false);
			if (data.mensajes && data.mensajes.length > 0) {
				last_msg = data.mensajes[0].id;
				for (let msg of data.mensajes)
					mensajes.prepend(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, msg.usuario_id == data.usuario_id));
			}
		}
	});
}

function updateMessages(clear_input = false) {
	if (check_messages) {
		check_messages = false;
		$.post("ajax.php", { action: "updateMessages", chat_id: current_chat, last_msg: last_msg }, function(data) {
			data = JSON.parse(data);
			if (data.message) {
				showAlert(data.type, data.message);
			} else {
				if (clear_input) $('#send-message-form input[name="mensaje"]').val('');
				if (data.mensajes && data.mensajes.length > 0) {
					data.mensajes.reverse();
					for (let msg of data.mensajes){
						last_msg = msg.id;
						mensajes.append(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, msg.usuario_id == data.usuario_id));
					}
				}
			}
			check_messages = true;
		});
	}
}

function updateChats() {
	if (check_chats) {
		check_chats = false;
		$.post("ajax.php", { action: "updateChats" }, function(data) {
			data = JSON.parse(data);
			if (data.message) {
				showAlert(data.type, data.message);
			} else {
				if (data.chats && data.chats.length > 0) {
					data.chats.reverse();
					for (let chat of data.chats){
						if (chat.last_msg || chats.find('.chat-'+chat.id).length == 0)
							chats.prepend(cloneChat(chat.id, chat.nombre, current_chat != chat.id));
					}
				}
			}
			check_chats = true;
		});
	}
}

function cloneMessage(contenido, fecha, usuario, propio) {
	let mensaje_dom = empty_msg.clone();
	mensaje_dom.find('.contenido').text(contenido);
	mensaje_dom.find('.fecha').text(fecha);
	mensaje_dom.find('.autor').text(usuario);
	if (propio) mensaje_dom.addClass('propio');
	mensaje_dom.removeClass('empty-message').addClass('a-mensaje');
	mensaje_dom.show();
	return mensaje_dom;
}

function cloneChat(id, nombre, nuevo) {
	let chat_dom = empty_chat.clone();
	let chat_dom_a = chat_dom.find('a');
	$('#chats .chat-'+id).remove();
	chat_dom_a.text(nombre);
	chat_dom_a.data('id', id);
	chatsClicables(chat_dom_a);
	if (nuevo) chat_dom.addClass('nuevo');
	chat_dom.removeClass('empty-chat').addClass('a-chat chat-'+id);
	chat_dom.show();
	return chat_dom;
}

function chatsClicables(chats) {
	chats.click(function(event) {
		event.preventDefault();
		loadChat($(this).data('id'));
		$(this).parent().removeClass('nuevo');
	});
}