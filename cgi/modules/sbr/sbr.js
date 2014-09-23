
function source_td()
{
	$('#partner').bind('change', this, function(e){ return e.data.partner_change($(this)); });
	
	if (globals.source)
	{
		this.partner_change($('#partner'));
		$('#source').val(globals.source);
	}
}

source_td.prototype.partner_change = function(partner_select)
{
	var partner = partner_select.val(),
		sources = globals.sources[partner];
	
	if (!sources || sources.length == 0)
	{
		alert('There do not appear to be any sources for '+partner+'. Please add at least one source before uploading');
		return;
	}
	
	sources.unshift('');
	$('#source_td').html(html.select('source', sources));
};
