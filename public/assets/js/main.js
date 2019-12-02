var update_interval = 500, replied = false, busy = false;
var current_user, last_received, last_contact_upd, current_chat = 0, last_read = 0;

var tabs_buttons, tabs_contents, alert_msg, loading, upload_attachment;
var chats, active_chat, candidates, friends, requests, messages, members_txt, members_img;
var empty_chat, empty_friend, empty_request, empty_message, empty_member_txt, empty_member_img;

$(document).ready(function() {

	// Lists
	chats = $('#chats .chats-list');
	friends = $('#friends .friends-list');
	requests = $('#requests .requests-list');
	messages = $('#messages .messages-list');
	members_img = $('#messages .members-list');

	// Empty elements
	empty_chat = $('#sidebar .empty-chat');
	empty_friend = $('#sidebar .empty-friend');
	empty_request = $('#sidebar .empty-request');
	empty_message = $('#messages .empty-message');
	empty_member_img = members_img.find('.empty-member');

	// Forms
	formsListener($('#content form[action="ajax.php"]'));

	// Chats
	chatsListener(chats.find('.chat-link'));

	// Tabs
	tabs_buttons = $('#content .tab.btn');
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
	alert_msg.find('a.btn-close').click(function(event){
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

	// Upload attachment
	upload_attachment = $('#messages input[type="file"]');
	upload_attachment.change(function() {
		if (upload_attachment.val())
			upload_attachment.siblings('.btn-upload').removeClass('empty');
		else
			upload_attachment.siblings('.btn-upload').addClass('empty');
	});

	// Slide sidebar
	$('.btn-sidebar').click(function(event) {
		event.preventDefault();
		$('#sidebar').toggleClass('visible');
	});

	// Filter chats
	var search_timeout;
	$('.search-chat input').keyup(function() {
		var search = $(this).val().toLowerCase();
		clearTimeout(search_timeout);
		search_timeout = setTimeout(function() {
			$('#chats .chats-list .a-chat').each(function() {
				var this_chat = $(this);
				var text = this_chat.find('.chat-link').text().toLowerCase();
				if (text.search(search) > -1) this_chat.show();
				else this_chat.hide();
			});
		}, 150);
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
	if (response.redirect)
		location.href = response.redirect;
	if (response.user_id && response.user_id != current_user)
		current_user = response.user_id;
	if (response.last_contact_upd && response.last_contact_upd != last_contact_upd)
		last_contact_upd = response.last_contact_upd;
	if (response.focus)
		tabs_buttons.filter('.'+response.focus).click();
	if (response.members)
		updateMembers(response.members);
	if (response.message)
		showAlert(response.type, response.message);
	if (response.userdata)
		updateUserdata(response.userdata);
	if (response.chats)
		updateChats(response.chats);
	if (response.messages)
		updateMessages(response.messages);
	if (response.friends)
		updateFriends(response.friends);
	if (response.requests)
		updateRequests(response.requests);
	if (response.candidates)
		updateCandidates(response.candidates);
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
	$('#menu .saludo .name').text(userdata.name);
	$('#menu .edit-profile input[name="name"]').val(userdata.name);
	$('#menu .edit-profile input[name="email"]').val(userdata.email);
	$('#menu .edit-profile input[type="password"]').val('');
	$('#menu .avatar').attr('src','avatar.php?id='+userdata.id+'&'+(new Date().getTime()));
}

function updateChats(chats_list) {
	if (putChat(chats_list))
		tabs_buttons.filter('.chats:not(.active)').addClass('new');
}

function updateMessages(messages_list) {
	last_read = putMessage(messages_list);
	messages.parent().scrollTop(messages[0].scrollHeight);
}

function updateFriends(friends_list) {
	if (putFriend(friends_list))
		tabs_buttons.filter('.friends:not(.active)').addClass('new');
}

function updateRequests(requests_list) {
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
		let classes = 'a-chat deletable chat-'+chat.id+' lastmsg-'+chat.last_msg;
		if (chat_dom.length == 0) {
			chat_dom = empty_chat.clone();
			chat_dom.find('input[name="chat_id"]').val(chat.id);
			chat_dom.find('.chat-link').data('id', chat.id);
			chatsListener(chat_dom.find('.chat-link'));
			formsListener(chat_dom.find('form[action="ajax.php"]'));
			chat_dom.show();
		} else if (chat_dom.hasClass('active')) {
			classes += ' active';
		}
		chat_dom.removeClass('empty-chat').addClass(classes);
		chat_dom.find('.chat-link').text(chat.name);
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
	message_dom.find('.content').text(message.content);
	message_dom.find('.date').text(message.date);
	message_dom.find('.autor').text(message.user_name);
	message_dom.removeClass('empty-message').addClass('a-message');
	if (message.attachment_id) {
		let btn_file = message_dom.find('.attachment');
		btn_file.show().attr('href','attachment.php?id='+message.attachment_id);
		if (message.mime_type.indexOf('image/') === 0) {
			let ratio = message.height/message.width;
			let width = ratio*message.width > 300 ? 300/ratio : message.width;
			btn_file.addClass('type-image');
			btn_file.css('width', width+'px');
			btn_file.find('.preview').attr('src','attachment.php?id='+message.attachment_id);
			btn_file.find('.reserve').css('padding-bottom', ratio*100+'%');
		}
	}
	if (!message.user_id) message_dom.addClass('aviso');
	else if (message.user_id == current_user) message_dom.addClass('propio');
	else if (!replied && parseInt(message.id) > last_read) message_dom.addClass('nuevo');
	message_dom.show();
	messages.append(message_dom);
	return putMessage(messages_list) || parseInt(message.id);
}

function putFriend(friends_list) {
	if (friends_list.length == 0) return false;
	let friend = friends_list.pop();
	let friend_dom = empty_friend.clone();
	friend_dom.find('.avatar').attr('src','avatar.php?id='+friend.id);
	friend_dom.find('.datos .name').text(friend.name);
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
	request_dom.find('.datos .name').text(request.name);
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
	let member = members_list.pop();
	let member_txt_dom = empty_member_txt.clone();
	let member_img_dom = empty_member_img.clone();
	member_txt_dom.find('.name').text(member.name);
	member_txt_dom.find('.email').text(member.email);
	member_img_dom.find('.avatar').attr('src','avatar.php?id='+member.id).attr('title',member.name);
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
	let name = candidate.name+' ('+candidate.email+')';
	let candidate_dom = $('<option value="'+candidate.id+'">'+name+'</option>');
	candidates.append(candidate_dom.addClass('a-candidate'));
	return putCandidate(candidates_list) || true;
}

// ------------------
// LOAD CHAT
// ------------------
function loadChat(chat_id) {
	if (busy) {
		setTimeout(function() { loadChat(chat_id); }, 50);
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
		$('#messages .btn-upload').show();
		$('#send-message-form input[name="chat_id"]').val(current_chat);
		$('#send-message-form input[name="message"]').prop('disabled', false);
		$('#send-message-form button[type="submit"]').prop('disabled', false);
		$('#send-message-form button.btn-upload').prop('disabled', false);
		processResponse(response);
		replied = busy = false;
	});
}

function unloadChat() {
	$('.a-message, .a-member').remove();
	if (active_chat) active_chat.removeClass('active');
	current_chat = 0;
	active_chat = null;
	$('#messages .btn-upload').hide();
	upload_attachment.val('').change();
	$('#send-message-form input[name="chat_id"]').val(0);
	$('#send-message-form input[name="message"]').prop('disabled', true);
	$('#send-message-form button[type="submit"]').prop('disabled', true);
	$('#send-message-form button.btn-upload').prop('disabled', true);
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
		if (!form.hasClass('confirmable') || confirm(sure_msg))
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
				if (formData.get('action') == 'sendMessage') {
					replied = true;
					upload_attachment.change();
					$('.a-message.nuevo').removeClass('nuevo');
				}
			}
			busy = false;
		}
	});
}