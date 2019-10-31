$(document).ready(function(){

	var forms = $('form[action="ajax.php"]');

	forms.submit(function(event){
		var url = $(this).attr('action');
		var type = $(this).attr('method');
		var data = $(this).serialize();
		$.ajax({
			url: url,
			type: type,
			data: data
		}).done(function(data){
			console.log(data);
			var json = JSON.parse(data);
			if (json.refresh) location.reload();
		});
		event.preventDefault();
	});

});