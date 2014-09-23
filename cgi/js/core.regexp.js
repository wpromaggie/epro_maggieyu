
/*
 * http://zetafleet.com/ via http://80.68.89.23/2006/Jan/20/escape/
 */
RegExp.escape = function(text)
{
	return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
}
