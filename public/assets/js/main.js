var forms;
var tabs_buttons;
var tabs_contents;
var alert_msg;
var mensajes;
var empty_msg;
var current_chat;
var last_msg;

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
				if (data.refresh)
					switch (data.refresh) {
						case 'chat': updateChat(); break;
						default: location.reload(); break;
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
		tabs_buttons.removeClass('active');
		tabs_contents.hide();
		$(this).addClass('active');
		$('.tab-content.' + $(this).data('target')).show();
		event.preventDefault();
	});

	alert_msg.find('a.close').click(function(e){
		alert_msg.fadeOut(200);
	});

});

function showAlert(type, message) {
	alert_msg.removeClass();
	alert_msg.addClass(type);
	alert_msg.find('p').text(message);
	alert_msg.show();
}

function loadChat(chat_id) {
	$.post("ajax.php", { action: "loadChat", chat_id: chat_id }, function(data) {
		console.log(data); // A BORRAR
		data = JSON.parse(data);
		if (data.message) {
			showAlert(json.type, json.message);
		} else {
			current_chat = data.id;
			mensajes.find('.un-mensaje').remove();
			$('#send-message-form input[name="chat_id"]').val(current_chat);
			$('#send-message-form input[name="mensaje"]').prop('disabled', false).val('');
			if (data.mensajes && data.mensajes.length > 0) {
				let mensaje_dom;
				for (let mensaje of data.mensajes) {
					mensaje_dom = empty_msg.clone();
					mensaje_dom.find('.contenido').text(mensaje.contenido);
					mensaje_dom.find('.fecha').text(mensaje.fecha);
					mensaje_dom.find('.autor').text(mensaje.usuario_nombre);
					mensaje_dom.removeClass('empty-message').addClass('un-mensaje');
					if (mensaje_dom.usuario_id == data.usuario_id)
						mensaje_dom.addClass('propio');
					mensaje_dom.show();
					mensajes.prepend(mensaje_dom);
				}
			} else {
				showAlert('info', 'No hay mensajes');
			}
		}
	});
}

function updateChat() {
	$.post("ajax.php", { action: "updateChat", chat_id: current_chat, last_msg: last_msg }, function(data) {
		// TODO
	});
}