var forms;
var tabs_buttons;
var tabs_contents;
var alert_msg;
var mensajes;
var empty_msg;
var current_chat;
var last_msg = -1;
var pause_check = false;

$(document).ready(function() {

	forms = $('#main form[action="ajax.php"]');
	tabs_buttons = $('#main .tabs a.tab');
	tabs_contents = $('#main .tab-content');
	alert_msg = $('#alert-msg');
	mensajes = $('#mensajes .lista-mensajes');
	empty_msg = $('#mensajes .empty-message');

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
						case 'chat': updateChat(true); break;
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
	
	setInterval(function() { if (current_chat) updateChat(); }, 500);

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
			mensajes.find('.un-mensaje').remove();
			$('#send-message-form input[name="chat_id"]').val(current_chat);
			$('#send-message-form input[name="mensaje"]').prop('disabled', false);
			if (data.mensajes && data.mensajes.length > 0) {
				last_msg = data.mensajes[0].id;
				for (let msg of data.mensajes)
					mensajes.prepend(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, msg.usuario_id == data.usuario_id));
			} else {
				showAlert('info', 'No hay mensajes');
			}
		}
	});
}

function updateChat(empty_input = false) {
	if( !pause_check ){
		pause_check = true;
		$.post("ajax.php", { action: "updateChat", chat_id: current_chat, last_msg: last_msg }, function(data) {
			data = JSON.parse(data);
			if (data.message) {
				showAlert(data.type, data.message);
			} else {
				if (empty_input) $('#send-message-form input[name="mensaje"]').val('');
				if (data.mensajes && data.mensajes.length > 0) {
					data.mensajes.reverse();
					for (let msg of data.mensajes){
						last_msg = msg.id;
						mensajes.append(cloneMessage(msg.contenido, msg.fecha, msg.usuario_nombre, msg.usuario_id == data.usuario_id));
					}
				}
			}
			pause_check = false;
		});
	}
}

function cloneMessage(contenido, fecha, usuario, propio) {
	mensaje_dom = empty_msg.clone();
	mensaje_dom.find('.contenido').text(contenido);
	mensaje_dom.find('.fecha').text(fecha);
	mensaje_dom.find('.autor').text(usuario);
	mensaje_dom.removeClass('empty-message').addClass('un-mensaje');
	if (propio) mensaje_dom.addClass('propio');
	mensaje_dom.show();
	return mensaje_dom;
}