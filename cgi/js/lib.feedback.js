(function(window){ var feedback = {

init:function(msgs)
{
	if (Types.is_undefined(msgs) || empty(msgs))
	{
		return;
	}
	Feedback.init_container();
	Feedback.add_msgs(msgs);
},

init_container:function()
{
	var ml;

	// already have a message container
	if (!$.empty('div#msg_dst'))
	{
		return;
	}

	//cameo layout version
	if ($('.content-wrap').length){
		ml = '<div id="msg_dst" class="alert alert-success"><span class="msg_text"></span><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>';
		$('.content-wrap').prepend(ml);
		return;
	}
	
	// look for best place to add our container
	ml = '<div id="msg_dst" class="msg"><span class="msg_text"></span><span class="msg_close">x</span></div><div class="clr"></div>';
	if (!$.empty($('#page_body').find('h1:first')))
	{
		$('#page_body').find('h1:first').after(ml);
	}
	else if (!$.empty($('#page_body').find('h2:first')))
	{
		$('#page_body').find('h2:first').after(ml);
	}
	else if (!$.empty($('#page_body').children('div:first')))
	{
		$('#page_body').children('div:first').prepend(ml);
	}
	else
	{
		$('#page_body').append(ml);
	}
	
	// close handler
	$('#msg_dst .msg_close').click(function(e){ $('#msg_dst').hide(); });
},

add_error_msg:function(text)
{
	Feedback.add_msg(text, 'error');
},

add_success_msg:function(text)
{
	Feedback.add_msg(text, 'success');
},

add_msg:function(text, type)
{
	Feedback.init_container();
	if (Options.on(arguments, 'clear'))
	{
		Feedback.clear();
	}
	$('#msg_dst').find('.msg_text').append('<p class="'+type+'_msg">'+text+'</p>');
	$('#msg_dst').show();
},

add_msgs:function(msgs)
{
	var i;
	
	if (Options.on(arguments, 'clear'))
	{
		Feedback.clear();
	}
	for (i = 0; i < msgs.length; ++i)
	{
		Feedback.add_msg(msgs[i].text, msgs[i].type);
	}
},

clear:function()
{
	$('#msg_dst').find('.msg_text').empty();
},

is_error_msg:function()
{
	return ($('#msg_dst p.error,#msg_dst p.error_msg').length > 0);
}

}; window.Feedback = feedback; })(window);
