$(document).ready(function(){

	var forms = $('form[action="ajax.php"]');
	var tabs_buttons = $('.tabs a.tab');
	var tabs_contents = $('.tab-content');

	forms.submit(function(event){
		var url = $(this).attr('action');
		var type = $(this).attr('method');
		var data = $(this).serialize();
		$.ajax({
			url: url,
			type: type,
			data: data
		}).done(function(data){
			console.log(data); // A BORRAR
			var json = JSON.parse(data);
			if (json.refresh) location.reload();
		});
		event.preventDefault();
	});

	tabs_buttons.click(function(event){
		tabs_buttons.removeClass('active');
		tabs_contents.hide();
		$(this).addClass('active');
		$('.tab-content.' + $(this).data('target')).show();
		event.preventDefault();
	});

});