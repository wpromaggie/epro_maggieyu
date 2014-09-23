(function(window){ var feedback = {

init:function(msgs)
{
	//alert(msgs);
	if (Types.is_undefined(msgs) || empty(msgs))
	{
		return;
	}
	Feedback.init_container();
	Feedback.add_msgs(msgs);
},

init_container:function()
{
	// already have a message container
	if (!$.empty('div#msg-dst'))
	{
		return;
	}
	$('.content-wrap').first().prepend('<div id="msg-dst"></div>');
},

add_error_msg:function(text)
{
	Feedback.add_msg(text, 'danger');
},

add_success_msg:function(text)
{
	Feedback.add_msg(text, 'success');
},

add_warning_msg:function(text)
{
	Feedback.add_msg(text, 'warning');
},

add_info_msg:function(text)
{
	Feedback.add_msg(text, 'info');
},

add_msg:function(text, type)
{
	if (Options.on(arguments, 'clear'))
	{
		Feedback.clear();
	}
	$('#msg-dst').append('<div class="alert alert-'+type+' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>'+text+'</div>')
	$('#msg-dst').show();
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
	$('#msg-dst').empty();
},

}; window.Feedback = feedback; })(window);
