function help_forms(){
	
	$('input[data-toggle]').click(function(){
		$target = $('#'+$(this).data('toggle'));
		if($(this).prop('checked')){
			$target.slideDown();
		}
		else {
			$target.slideUp();
		}
	});

	$('input.enable-text').click(function(){
		$text = $(this).closest('.input-group').find('input[type=text]');
		if($(this).prop('checked')){
			$text.prop('disabled', false);
		}
		else {
			$text.val('').prop('disabled', true);
		}
	});

}