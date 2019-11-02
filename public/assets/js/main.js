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

	forms = $('#content form[action="ajax.php"]');
	tabs_buttons = $('#content .tabs a.tab');
	tabs_contents = $('#content .tab-content');
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
				console.log(data); // A BORRAR
				data = JSON.parse(data);
				if (data.update)
					switch (data.update) {
						case 'chats': updateChats(true); break;
						case 'messages': updateMessages(true); break;
						case 'userdata': updateUserdata(); break;
						case 'page': location.reload(); break;
						default: showAlert('error', 'Error al recibir información del servidor');
					}					
				if (data.message)
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
	
	if ($('#content.main').length > 0){
		setInterval(function() {
			updateChats();
			if (current_chat) updateMessages();
		}, 1000);
	}

	$('.edit-profile-btn').click(function(event) {
		event.preventDefault();
		$('.edit-profile').slideToggle(200);
	});

	$('.btn-upload').click(function(event) {
		event.preventDefault();
		$(this).siblings('input[type="file"]').click();
	});

});

function showAlert(type, message) {
	alert_msg.removeClass();
	alert_msg.addClass(type);
	alert_msg.find('p').text(message);
	alert_msg.show();
}

function loadChat(chat_id) {
	chats.find('.a-chat.active').removeClass('active');
	chats.find('.a-chat.chat-'+chat_id).addClass('active');
	$.post("ajax.php", { action: "loadChat", chat_id: chat_id }, function(data) {
		data = JSON.parse(data);
		if (data.message) {
			showAlert(data.type, data.message);
		} else {
			current_chat = data.id;
			mensajes.find('.a-message').remove();
			$('#send-message-form input[name="chat_id"]').val(current_chat);
			$('#send-message-form input[name="mensaje"]').prop('disabled', false);
			$('#send-message-form input[type="submit"]').prop('disabled', false);
			if (data.mensajes && data.mensajes.length > 0) {
				last_msg = data.mensajes[0].id;
				for (let msg of data.mensajes)
					mensajes.prepend(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, msg.usuario_id == data.usuario_id));
				mensajes.scrollTop(mensajes[0].scrollHeight);
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
					mensajes.scrollTop(mensajes[0].scrollHeight);
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

function updateUserdata() {
	$.post("ajax.php", { action: "updateUserdata" }, function(data) {
		data = JSON.parse(data);
		if (data.message) {
			showAlert(data.type, data.message);
		} else {
			$('#menu .saludo span').text(data.nombre);
			$('#menu .edit-profile input[name="name"]').val(data.nombre);
			$('#menu .edit-profile input[name="email"]').val(data.email);
			$('#menu .edit-profile input[type="password"]').val('');
			$('#menu .avatar img').attr('src','/avatar.php?id='+data.id+'&'+(new Date().getTime()));
		}
	});
}

function cloneMessage(contenido, fecha, usuario, propio) {
	let mensaje_dom = empty_msg.clone();
	mensaje_dom.find('.contenido').text(contenido);
	mensaje_dom.find('.fecha').text(fecha);
	mensaje_dom.find('.autor').text(usuario);
	if (propio) mensaje_dom.addClass('propio');
	mensaje_dom.removeClass('empty-message').addClass('a-message');
	mensaje_dom.show();
	return mensaje_dom;
}

function cloneChat(id, nombre, unread) {
	let chat_dom = empty_chat.clone();
	let chat_dom_a = chat_dom.find('a');
	let old_chat = $('#chats .chat-'+id);
	let clases = 'a-chat chat-'+id+(old_chat.hasClass('active')?' active':'');
	old_chat.remove();
	chat_dom_a.text(nombre);
	chat_dom_a.data('id', id);
	chatsClicables(chat_dom_a);
	if (unread) chat_dom.addClass('unread');
	chat_dom.removeClass('empty-chat').addClass(clases);
	chat_dom.show();
	return chat_dom;
}

function chatsClicables(chats) {
	chats.click(function(event) {
		event.preventDefault();
		loadChat($(this).data('id'));
		$(this).parent().removeClass('unread');
	});
}