
/**
 * same as jquery fadeOut but takes additional delay parameter in milliseconds
 */
function $fade_out(selector, fade_ms, delay_ms, callback, callback_obj)
{
	setTimeout(function(){ $fade_out_go(selector, fade_ms, callback, callback_obj);}, delay_ms);
}

function $fade_out_go(mixed, fade_ms, callback, callback_obj)
{
	if (!$.is_jq(mixed))
	{
		mixed = $(mixed);
	}
	if (callback_obj && callback_obj[callback])
	{
		mixed.fadeOut(fade_ms, function(){ callback_obj[callback](); });
	}
	else if (window[callback])
	{
		mixed.fadeOut(fade_ms, window[callback]);
	}
	else
	{
		mixed.fadeOut(fade_ms);
	}
}