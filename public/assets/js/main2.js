var update_interval = 500, busy = false;
var current_user, last_received, last_contact_upd, current_chat = 0, last_read = 0;

var tabs_buttons, tabs_contents, alert_msg, loading;
var chats, active_chat, candidates, friends, requests, messages, members_txt, members_img;
var empty_chat, empty_friend, empty_request, empty_message, empty_member_txt, empty_member_img;

$(document).ready(function() {

	// Empty elements
	empty_chat = $('#chats .empty-chat');
	empty_friend = $('#friends .empty-friend');
	empty_request = $('#requests .empty-request');
	empty_message = $('#messages .empty-message');

	// Lists
	chats = $('#chats .chats-list');
	friends = $('#friends .friends-list');
	requests = $('#requests .requests-list');
	messages = $('#messages .messages-list');
	members_img = $('#messages .members-list');
	empty_member_img = members_img.find('.empty-member');

	// Forms
	formsListener($('#content form[action="ajax.php"]'));

	// Chats
	chatsListener(chats.find('.chat-link'));

	// Tabs
	tabs_buttons = $('#content a.tab');
	tabs_contents = $('#content .tab-content');
	tabs_buttons.click(function(event) {
		event.preventDefault();
		tabs_buttons.removeClass('active');
		tabs_contents.hide();
		tabs_contents.filter('.'+$(this).data('target')).show();
		$(this).removeClass('new').addClass('active');
	});

	// Loading
	loading = $('#loading');

	// Message
	alert_msg = $('#alert-msg');
	alert_msg.find('a.close').click(function(event){
		event.preventDefault();
		alert_msg.fadeOut(200);
	});
	
	// If main page
	if ($('#content.main').length > 0){
		// Update interval
		setInterval(function() { update(); }, update_interval);
		// Edit profile
		$('.edit-profile-btn').click(function(event) {
			event.preventDefault();
			$('.edit-profile').slideToggle(200);
		});
	}

	// Upload
	$('.btn-upload').click(function(event) {
		event.preventDefault();
		$(this).siblings('input[type="file"]').click();
	});

});

// ------------------
// SHOW ALERT
// ------------------
function showAlert(type, message) {
	alert_msg.removeClass();
	alert_msg.addClass(type);
	alert_msg.find('p').text(message);
	alert_msg.show();
}

// ------------------
// PROCESS RESPONSE
// ------------------
function processResponse(response) {
	console.log(response); // A BORRAR
	if (response.redirect)
		location.href = response.redirect;
	if (response.usuario_id && response.usuario_id != current_user)
		current_user = response.usuario_id;
	if (response.last_contact_upd && response.last_contact_upd != last_contact_upd)
		last_contact_upd = response.last_contact_upd;
	if (response.focus)
		tabs_buttons.filter('.'+response.focus).click();
	if (response.message)
		showAlert(response.type, response.message);
	if (response.userdata)
		updateUserdata(response.userdata);
	if (response.chats)
		updateChats(response.chats);
	if (response.messages)
		updateMessages(response.messages);
	if (response.candidates)
		updateCandidates(response.candidates);
	if (response.friends)
		updateFriends(response.friends);
	if (response.requests)
		updateRequests(response.requests);
	if (response.members)
		updateMembers(response.members);
}

// ------------------
// UPDATE
// ------------------
function update() {
	if (busy) return;
	busy = true;
	$.post("ajax.php", {
			action: "update",
			chat_id: current_chat,
			last_received: last_received,
			last_read: last_read,
			last_contact_upd: last_contact_upd,
		}, function(response) {
			processResponse(response);
			busy = false;
		}
	);
}

function updateUserdata(userdata) {
	$('#menu .saludo span').text(userdata.nombre);
	$('#menu .edit-profile input[name="name"]').val(userdata.nombre);
	$('#menu .edit-profile input[name="email"]').val(userdata.email);
	$('#menu .edit-profile input[type="password"]').val('');
	$('#menu .avatar img').attr('src','avatar.php?id='+userdata.id+'&'+(new Date().getTime()));
}

function updateChats(chats_list) {
	if (putChat(chats_list))
		tabs_buttons.filter('.chats:not(.active)').addClass('new');
}

function updateMessages(messages_list) {
	last_read = putMessage(messages_list);
	messages.scrollTop(messages[0].scrollHeight);
}

function updateFriends(friends_list) {
	friends.find('.a-friend').remove();
	if (putFriend(friends_list))
		tabs_buttons.filter('.friends:not(.active)').addClass('new');
}

function updateRequests(requests_list) {
	requests.find('.a-request').remove();
	if (putRequest(requests_list))
		tabs_buttons.filter('.requests:not(.active)').addClass('new');
}

function updateMembers(members_list) {
	if (active_chat) {
		$('.a-member').remove();
		putMember(members_list);
	}
}

function updateCandidates(candidates_list) {
	if (active_chat) {
		candidates = active_chat.find('.candidates-list');
		candidates.find('.a-candidate').remove();
		putCandidate(candidates_list);
	}
}

// ------------------
// PUTS
// ------------------
function putChat(chats_list) {
	if (chats_list.length == 0) return false;
	let chat = chats_list.pop();
	let chat_dom = $('#chats .chat-'+chat.id);
	let updated = false;
	if (parseInt(chat.last_msg) > last_received) last_received = parseInt(chat.last_msg);
	if (!chat_dom.hasClass('lastmsg-'+chat.last_msg)) {
		let clases = 'a-chat deletable chat-'+chat.id+' lastmsg-'+chat.last_msg;
		if (chat_dom.length == 0) {
			chat_dom = empty_chat.clone();
			chat_dom.find('input[name="chat_id"]').val(chat.id);
			chat_dom.find('.chat-link').data('id', chat.id);
			chatsListener(chat_dom.find('.chat-link'));
			formsListener(chat_dom.find('form[action="ajax.php"]'));
			chat_dom.show();
		} else if (chat_dom.hasClass('active')) {
			clases += ' active';
		}
		chat_dom.removeClass().addClass(clases);
		chat_dom.find('.chat-link').text(chat.nombre);
		if (current_chat != chat.id) chat_dom.addClass('unread');
		chats.prepend(chat_dom);
		updated = true;
	}
	return putChat(chats_list) || updated;
}

function putMessage(messages_list) {
	if (messages_list.length == 0) return false;
	let message = messages_list.pop();
	let message_dom = empty_message.clone();
	message_dom.attr('id', 'message-'+message.id);
	message_dom.find('.contenido').text(message.contenido);
	message_dom.find('.fecha').text(message.fecha);
	message_dom.find('.autor').text(message.usuario_nombre);
	message_dom.removeClass('empty-message').addClass('a-message');
	if (!message.usuario_id) message_dom.addClass('aviso');
	else if (message.usuario_id == current_user) message_dom.addClass('propio');
	else if (parseInt(message.id) > last_read) message_dom.addClass('nuevo');
	message_dom.show();
	messages.append(message_dom);
	return putMessage(messages_list) || parseInt(message.id);
}

function putFriend(friends_list) {
	if (friends_list.length == 0) return false;
	let friend = friends_list.pop();
	let friend_dom = empty_friend.clone();
	friend_dom.find('.avatar img').attr('src','avatar.php?id='+friend.id);
	friend_dom.find('.datos .nombre').text(friend.nombre);
	friend_dom.find('.datos .email').text(friend.email);
	friend_dom.find('input[name="members[]"], input[name="contact_id"]').val(friend.id);
	friend_dom.removeClass('empty-friend').addClass('a-friend');
	friend_dom.show();
	formsListener(friend_dom.find('form[action="ajax.php"]'));
	friends.prepend(friend_dom);
	return putFriend(friends_list) || true;
}

function putRequest(requests_list) {
	if (requests_list.length == 0) return false;
	let request = requests_list.pop();
	let request_dom = empty_request.clone();
	request_dom.find('.datos .nombre').text(request.nombre);
	request_dom.find('.datos .email').text(request.email);
	request_dom.find('input[name="contact_id"]').val(request.id);
	request_dom.removeClass('empty-request').addClass('a-request');
	request_dom.show();
	formsListener(request_dom.find('form[action="ajax.php"]'));
	requests.prepend(request_dom);
	return putRequest(requests_list) || true;
}

function putMember(members_list) {
	if (members_list.length == 0) return false;
	let member = members_list.shift();
	let member_txt_dom = empty_member_txt.clone();
	let member_img_dom = empty_member_img.clone();
	member_txt_dom.find('.name').text(member.nombre);
	member_txt_dom.find('.email').text(member.email);
	member_img_dom.find('.avatar').attr('src','avatar.php?id='+member.id).attr('title',member.nombre);
	member_txt_dom.removeClass('empty-member').addClass('a-member');
	member_img_dom.removeClass('empty-member').addClass('a-member');
	member_txt_dom.show();
	member_img_dom.show();
	members_txt.prepend(member_txt_dom);
	members_img.prepend(member_img_dom);
	return putMember(members_list) || true;
}

function putCandidate(candidates_list) {
	if (candidates_list.length == 0) return false;
	let candidate = candidates_list.pop();
	let candidate_dom = $('<option value="'+candidate.id+'">'+candidate.nombre+'</option>');
	candidates.append(candidate_dom.addClass('a-candidate'));
	return putCandidate(candidates_list) || true;
}

// ------------------
// LOAD CHAT
// ------------------
function loadChat(chat_id) {
	if (busy) {
		setTimeout(function() {loadChat(chat_id);}, 50);
		return;
	}
	if (active_chat && active_chat.length > 0) unloadChat();
	busy = true;
	$.post("ajax.php", { action: "loadChat", chat_id: chat_id }, function(response) {
		active_chat = chats.find('.a-chat.chat-'+chat_id);
		members_txt = active_chat.find('.members-list');
		empty_member_txt = members_txt.find('.empty-member');
		current_chat = parseInt(response.id);
		last_read = parseInt(response.last_read);
		active_chat.addClass('active');
		$('#send-message-form input[name="chat_id"]').val(current_chat);
		$('#send-message-form input[name="mensaje"]').prop('disabled', false);
		$('#send-message-form input[type="submit"]').prop('disabled', false);
		processResponse(response);
		busy = false;
	});
}

function unloadChat() {
	$('.a-message, .a-member').remove();
	active_chat.removeClass('active');
	current_chat = 0;
	active_chat = null;
	$('#send-message-form input[name="chat_id"]').val(0);
	$('#send-message-form input[name="mensaje"]').prop('disabled', true);
	$('#send-message-form input[type="submit"]').prop('disabled', true);
}


// ------------------
// ADD LISTENERS
// ------------------
function chatsListener(chats) {
	chats.click(function(event) {
		event.preventDefault();
		loadChat($(this).data('id'));
		$(this).parent().removeClass('unread');
	});
}

function formsListener(forms) {
	forms.submit(function(event) {
		var form = $(this);
		event.preventDefault();
		if (!form.hasClass('confirmable') || confirm('Are you sure?'))
			formSubmit(form);
	});
}

function formSubmit(form) {
	if (busy) {
		setTimeout(function() {formSubmit(form);}, 50);
		return;
	}
	let show_loading = setTimeout(function(){ loading.show(); }, 250);
	let url = form.attr('action');
	let type = form.attr('method');
	let formData = new FormData(form[0]);
	busy = true;
	if (formData.get('last_received') == '0') formData.set('last_received', last_received);
	if (formData.get('last_read') == '0') formData.set('last_read', last_read);
	if (formData.get('last_contact_upd') == '0') formData.set('last_contact_upd', last_contact_upd);
	$.ajax({
		url: url,
		type: type,
		data: formData,
        contentType: false,
        processData: false,
		success: function(response) {
			clearTimeout(show_loading);
			loading.hide();
			processResponse(response);
			if (response.type != 'error'){
				if (form.hasClass('unload-chat'))
					unloadChat();
				if (form.hasClass('delete-parent'))
					form.closest('.deletable').remove();
				if (form.hasClass('empty-on-submit'))
					form[0].reset();
			}
			busy = false;
		}
	});
}