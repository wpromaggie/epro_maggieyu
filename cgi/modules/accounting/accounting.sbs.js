
function accounting_sbs()
{
	$('#pseudo_origin').bind('click', this, function(e){ e.data.pseudo_origin_click($(this)); });
	this.pseudo_origin_click($('#pseudo_origin'));
}

accounting_sbs.prototype.pseudo_origin_click = function(cbox)
{
	if (cbox.is(':checked'))
	{
		$('#origin_start').closest('tr').show();
		$('#origin_end').closest('tr').show();
	}
	else
	{
		$('#origin_start').closest('tr').hide();
		$('#origin_end').closest('tr').hide();
	}
};
