var forms, tabs_buttons, tabs_contents, alert_msg;
var messages, empty_msg, check_messages = true, last_readed = -1;
var chats, empty_chat, current_chat, check_chats = true;
var friends, empty_friend, check_friends = true, last_friend = "";
var requests, empty_request, check_requests = true, last_request = "";
var current_chat_dom, empty_member;
var update_interval = 2000;

$(document).ready(function() {

	forms = $('#content form[action="ajax.php"]');
	tabs_buttons = $('#content .tabs a.tab');
	tabs_contents = $('#content .tab-content');
	alert_msg = $('#alert-msg');
	messages = $('#messages .messages-list');
	empty_msg = $('#messages .empty-message');
	chats = $('#chats .chats-list');
	empty_chat = $('#chats .empty-chat');
	friends = $('#friends .friends-list');
	empty_friend = $('#friends .empty-friend');
	requests = $('#requests .requests-list');
	empty_request = $('#requests .empty-request');
	empty_member = empty_chat.find('.empty-member');

	formsClicables(forms);

	chatsClicables(chats.find('.a-chat a'));

	tabs_buttons.click(function(event) {
		event.preventDefault();
		tabs_buttons.removeClass('active');
		tabs_contents.hide();
		$(this).removeClass('new').addClass('active');
		$('.tab-content.' + $(this).data('target')).show();
	});

	alert_msg.find('a.close').click(function(event){
		event.preventDefault();
		alert_msg.fadeOut(200);
	});
	
	if ($('#content.main').length > 0){
		setInterval(function() {
			updateChats();
			setTimeout(updateFriends, update_interval*0.25);
			setTimeout(updateRequests, update_interval*0.5);
			if (current_chat) setTimeout(updateMessages, update_interval*0.75);
		}, update_interval);
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
	current_chat_dom = chats.find('.a-chat.chat-'+chat_id);
	$.post("ajax.php", { action: "loadChat", chat_id: chat_id }, function(data) {
		data = processData(data);
		if (data.id) {
			current_chat = data.id;
			messages.find('.a-message').remove();
			$('#send-message-form input[name="chat_id"]').val(current_chat);
			$('#send-message-form input[name="mensaje"]').prop('disabled', false);
			$('#send-message-form input[type="submit"]').prop('disabled', false);
			if (data.messages && data.messages.length > 0) {
				for (let msg of data.messages) {
					let propio = msg.usuario_id == data.usuario_id;
					let nuevo = parseInt(msg.id) > data.last_readed;
					messages.prepend(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, propio, nuevo));
				}
				last_readed = parseInt(data.last_msg);
				messages.scrollTop(messages[0].scrollHeight);
			}
			if (data.users && data.users.length > 0) {
				let members = current_chat_dom.find('.members-list');
				members.empty();
				for (let member of data.users)
					members.append(cloneMember(member.nombre, member.email));
			}
			current_chat_dom.addClass('active');
		}
	});
}

function updateMessages() {
	if (check_messages) {
		check_messages = false;
		$.post("ajax.php", { action: "updateMessages", chat_id: current_chat, last_readed: last_readed }, function(data) {
			data = processData(data);
			if (data.messages && data.messages.length > 0) {
				data.messages.reverse();
				for (let msg of data.messages){
					last_readed = msg.id;
					messages.append(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, msg.usuario_id == data.usuario_id));
				}
				messages.scrollTop(messages[0].scrollHeight);
			}
			check_messages = true;
		});
	}
}

function updateChats() {
	if (check_chats) {
		check_chats = false;
		$.post("ajax.php", { action: "updateChats" }, function(data) {
			data = processData(data);
			if (data.chats && data.chats.length > 0) {
				let updated = false;
				data.chats.reverse();
				for (let chat of data.chats)
					if (chat.last_msg || chats.find('.chat-'+chat.id).length == 0) {
						chats.prepend(cloneChat(chat.id, chat.nombre, current_chat != chat.id));
						updated = true;
					}
				if (updated) tabs_buttons.filter('.chats:not(.active)').addClass('new');
			}
			check_chats = true;
		});
	}
}

function updateFriends() {
	if (check_friends) {
		check_friends = false;
		$.post("ajax.php", { action: "updateFriends", last: last_friend }, function(data) {
			data = processData(data);
			if (data.friends && data.friends.length > 0) {
				let updated = false;
				data.friends.reverse();
				for (let friend of data.friends) {
					if (friend.fecha_upd > last_friend) last_friend = friend.fecha_upd;
					friends.prepend(cloneFriend(friend.id, friend.nombre, friend.email));
					updated = true;
				}
				if (updated) tabs_buttons.filter('.friends:not(.active)').addClass('new');
			}
			check_friends = true;
		});
	}
}

function updateRequests() {
	if (check_requests) {
		check_requests = false;
		$.post("ajax.php", { action: "updateRequests", last: last_request }, function(data) {
			data = processData(data);
			if (data.requests && data.requests.length > 0) {
				let updated = false;
				data.requests.reverse();
				for (let request of data.requests){
					if (request.fecha_upd > last_request) last_request = request.fecha_upd;
					requests.prepend(cloneRequest(request.id, request.nombre, request.email));
					updated = true;
				}
				if (updated) tabs_buttons.filter('.requests:not(.active)').addClass('new');
			}
			check_requests = true;
		});
	}
}

function updateUserdata() {
	$.post("ajax.php", { action: "updateUserdata" }, function(data) {
		data = processData(data);
		$('#menu .saludo span').text(data.nombre);
		$('#menu .edit-profile input[name="name"]').val(data.nombre);
		$('#menu .edit-profile input[name="email"]').val(data.email);
		$('#menu .edit-profile input[type="password"]').val('');
		$('#menu .avatar img').attr('src','/avatar.php?id='+data.id+'&'+(new Date().getTime()));
	});
}

function cloneMessage(contenido, fecha, usuario, propio, nuevo = false) {
	let mensaje_dom = empty_msg.clone();
	mensaje_dom.find('.contenido').text(contenido);
	mensaje_dom.find('.fecha').text(fecha);
	mensaje_dom.find('.autor').text(usuario);
	if (propio) mensaje_dom.addClass('propio');
	else if (nuevo) mensaje_dom.addClass('nuevo');
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
	chat_dom.find('input[name="chat_id"]').val(id);
	if (unread) chat_dom.addClass('unread');
	chat_dom.removeClass('empty-chat').addClass(clases);
	chat_dom.show();
	return chat_dom;
}

function cloneFriend(id, nombre, email) {
	let friend_dom = empty_friend.clone();
	friend_dom.find('.avatar img').attr('src','/avatar.php?id='+id);
	friend_dom.find('.datos .nombre').text(nombre);
	friend_dom.find('.datos .email').text(email);
	friend_dom.find('input[name="members[]"], input[name="friend_id"]').val(id);
	friend_dom.removeClass('empty-chat').addClass('a-friend');
	friend_dom.show();
	formsClicables(friend_dom.find('form[action="ajax.php"]'));
	return friend_dom;
}

function cloneRequest(id, nombre, email) {
	let request_dom = empty_request.clone();
	request_dom.find('.datos .nombre').text(nombre);
	request_dom.find('.datos .email').text(email);
	request_dom.find('input[name="request_id"]').val(id);
	request_dom.removeClass('empty-chat').addClass('a-request');
	request_dom.show();
	formsClicables(request_dom.find('form[action="ajax.php"]'));
	return request_dom;
}

function cloneMember(nombre, email) {
	let member_dom = empty_member.clone();
	member_dom.find('.name').text(nombre);
	member_dom.find('.email').text(email);
	member_dom.removeClass('empty-member').addClass('a-member');
	member_dom.show();
	return member_dom;
}

function chatsClicables(chats) {
	chats.click(function(event) {
		event.preventDefault();
		loadChat($(this).data('id'));
		$(this).parent().removeClass('unread');
	});
}

function formsClicables(forms) {
	forms.submit(function(event) {
		var form = $(this);
		event.preventDefault();
		if (!$(this).hasClass('confirmable') || confirm('Are you sure?')) {
			let url = $(this).attr('action');
			let type = $(this).attr('method');
			let formData = new FormData(this);
			$.ajax({
				url: url,
				type: type,
				data: formData,
		        contentType: false,
		        processData: false,
				success: function(data) {
					data = processData(data);
					if (data.type != 'error'){
						if (form.hasClass('delete-parent')) form.closest('.deletable').remove();
						if (form.hasClass('empty-on-submit')) form[0].reset();
					}
				}
			});
		}
	});
}

function processData(data) {
	//console.log(data); // A BORRAR
	data = JSON.parse(data);
	if (data.update)
		switch (data.update) {
			case 'chats': updateChats(); break;
			case 'messages': updateMessages(); break;
			case 'userdata': updateUserdata(); break;
			case 'friends': updateFriends(); break;
			case 'requests': updateRequests(); break;
			case 'page': location.reload(); break;
			default: showAlert('error', 'Server side error');
		}
	if (data.focus)
		switch (data.focus) {
			case 'chats': tabs_buttons.filter('.chats').click(); break;
			case 'friends': tabs_buttons.filter('.friends').click(); break;
			case 'requests': tabs_buttons.filter('.requests').click(); break;
			default: showAlert('error', 'Server side error');
		}
	if (data.message)
		showAlert(data.type, data.message);
	return data;
}