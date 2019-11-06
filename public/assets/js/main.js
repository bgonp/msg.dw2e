var forms, tabs_buttons, tabs_contents, alert_msg, check = true;
var messages, empty_msg, check_messages = true, last_readed = -1;
var chats, empty_chat, current_chat = 0;
var friends, empty_friend, last_friend = "";
var requests, empty_request, last_request = "";
var current_chat_dom, empty_member;
var update_interval = 500;
var usuario_id;

$(document).ready(function() {

	forms = $('#content form[action="ajax.php"]');
	tabs_buttons = $('#content a.tab');
	tabs_contents = $('#content .tab-content');
	alert_msg = $('#alert-msg');
	loading = $('#loading');
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
		setInterval(function() { update(); }, update_interval);
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
	if (chats.find('.a-chat.active.chat-'+chat_id).length > 0) return;
	chats.find('.a-chat.active').removeClass('active');
	current_chat_dom = chats.find('.a-chat.chat-'+chat_id);
	$.post("ajax.php", { action: "loadChat", chat_id: chat_id }, function(data) {
		data = processData(data);
		if (data.id) {
			loadMessages(data.id, data.last_readed, data.last_msg, data.messages);
			if (data.users && data.users.length > 0) {
				let members = current_chat_dom.find('.members-list');
				members.find('.a-member').remove();
				data.users.reverse();
				for (let member of data.users)
					members.prepend(cloneMember(member.nombre, member.email));
			}
			current_chat_dom.addClass('active');
		}
	});
}

function loadMessages(chat_id = 0, last_readed = 0, last_msg = 0, messages_list = false) {
	current_chat = chat_id;
	messages.find('.a-message').remove();
	$('#send-message-form input[name="chat_id"]').val(current_chat);
	$('#send-message-form input[name="mensaje"]').prop('disabled', current_chat ? false : true);
	$('#send-message-form input[type="submit"]').prop('disabled', current_chat ? false : true);
	if (messages_list && messages_list.length > 0) {
		for (let msg of messages_list) {
			let propio = msg.usuario_id == usuario_id;
			let nuevo = parseInt(msg.id) > last_readed;
			messages.prepend(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, propio, nuevo));
		}
		last_readed = parseInt(last_msg);
		messages.scrollTop(messages[0].scrollHeight);
	}
}

function update() {
	if (!check) return;
	check = false;
	$.post("ajax.php", {
			action: "update",
			chat_id: current_chat,
			last_friend: last_friend,
			last_readed: last_readed,
			last_request: last_request
		}, function(data) {
			data = processData(data);
			if (data.friends && data.friends.length > 0)
				updateFriends(data.friends);
			if (data.requests && data.requests.length > 0)
				updateRequests(data.requests);
			if (data.chats && data.chats.length > 0)
				updateChats(data.chats);
			if (data.messages && data.messages.length > 0)
				updateMessages(data.messages, data.usuario_id);
			check = true;
		}
	);
}

function updateMessages(messages_list, usuario_id) {
	var propio = false;
	messages_list.reverse();
	for (let msg of messages_list){
		propio = msg.usuario_id == usuario_id;
		if (propio) messages.find('.a-message.nuevo').removeClass('nuevo');
		last_readed = msg.id;
		messages.append(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, propio, !propio ));
	}
	messages.scrollTop(messages[0].scrollHeight);
}

function updateChats($chats_list) {
	var updated = false;
	$chats_list.reverse();
	for (let chat of $chats_list)
		if (chat.last_msg || chats.find('.chat-'+chat.id).length == 0) {
			chats.prepend(cloneChat(chat.id, chat.nombre, current_chat != chat.id));
			updated = true;
		}
	if (updated) tabs_buttons.filter('.chats:not(.active)').addClass('new');
}

function updateFriends(friends_list) {
	var updated = false;
	friends_list.reverse();
	for (let friend of friends_list) {
		if (friend.fecha_upd > last_friend) last_friend = friend.fecha_upd;
		friends.prepend(cloneFriend(friend.id, friend.nombre, friend.email));
		updated = true;
	}
	if (updated) tabs_buttons.filter('.friends:not(.active)').addClass('new');
}

function updateRequests(requests_list) {
	var updated = false;
	requests_list.reverse();
	for (let request of requests_list){
		if (request.fecha_upd > last_request) last_request = request.fecha_upd;
		requests.prepend(cloneRequest(request.id, request.nombre, request.email));
		updated = true;
	}
	if (updated) tabs_buttons.filter('.requests:not(.active)').addClass('new');
}

function updateUserdata(userdata) {
	$('#menu .saludo span').text(userdata.nombre);
	$('#menu .edit-profile input[name="name"]').val(userdata.nombre);
	$('#menu .edit-profile input[name="email"]').val(userdata.email);
	$('#menu .edit-profile input[type="password"]').val('');
	$('#menu .avatar img').attr('src','/avatar.php?id='+userdata.id+'&'+(new Date().getTime()));
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
	let chat_dom = $('#chats .chat-'+id);
	if (chat_dom.length == 0) {
		chat_dom = empty_chat.clone();
		chatsClicables(chat_dom.find('.chat-link'));
		formsClicables(chat_dom.find('form[action="ajax.php"]'));
		chat_dom.find('input[name="chat_id"]').val(id);
		chat_dom.removeClass('empty-chat').addClass('a-chat chat-'+id);
		chat_dom.find('.chat-link').data('id', id);
		chat_dom.show();
		chat_dom.find('.chat-link').text(nombre);
		if (unread) chat_dom.addClass('unread');
		return null;
	}
	chat_dom.find('.chat-link').text(nombre);
	if (unread) chat_dom.addClass('unread');
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
		var show_loading = setTimeout(function(){ loading.show(); }, 250);
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
					clearTimeout(show_loading);
					loading.hide();
					data = processData(data);
					if (data.type != 'error'){
						if (form.hasClass('unload-chat'))
							loadMessages();
						if (form.hasClass('delete-parent'))
							form.closest('.deletable').remove();
						if (form.hasClass('empty-on-submit'))
							form[0].reset();
					}
				}
			});
		}
	});
}

function processData(data) {
	console.log(data); // A BORRAR
	data = JSON.parse(data);
	if (data.redirect)
		location.href = data.redirect;
	if (data.update)
		update();
	if (data.focus)
		switch (data.focus) {
			case 'chats': tabs_buttons.filter('.chats').click(); break;
			case 'friends': tabs_buttons.filter('.friends').click(); break;
			case 'requests': tabs_buttons.filter('.requests').click(); break;
			default: showAlert('error', 'Server side error');
		}
	if (data.userdata)
		updateUserdata(data.userdata);
	if (data.message)
		showAlert(data.type, data.message);
	return data;
}