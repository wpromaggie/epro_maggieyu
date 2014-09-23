
function sbr_reps()
{
	this.changes = {};
	$('.rep').bind('click', this, function(e){ e.data.rep_click($(this)); });
	$('#reps_submit').bind('click', this, function(e){ return e.data.reps_submit(); });
}

sbr_reps.prototype.rep_click = function(input)
{
	var rid = input.attr('rid');
	
	if (this.changes[rid])
	{
		delete this.changes[rid];
	}
	else
	{
		this.changes[rid] = (input.is(':checked')) ? 'On' : 'Off';
	}
};

sbr_reps.prototype.reps_submit = function()
{
	$('#changes').val(JSON.stringify(this.changes));
	return true;
};
