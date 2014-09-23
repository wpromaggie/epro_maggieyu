
function product_account_reps()
{
	this.changes = {};
	$('.rep').bind('click', this, function(e){ e.data.rep_click($(this)); });
	$('#reps_submit').bind('click', this, function(e){ return e.data.reps_submit(); });
	
	this.init_edit_rep($('#edit_rep_select'));
}

product_account_reps.prototype.init_edit_rep = function(select)
{
	select.bind('change', this, function(e){ e.data.edit_rep_change($(this)); });
	if (select.val())
	{
		this.edit_rep_change(select);
		$('#edit_rep_table input[name="product_account_rep_name"]').blur();
	}
};

product_account_reps.prototype.rep_click = function(input)
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

product_account_reps.prototype.reps_submit = function()
{
	$('#changes').val(JSON.stringify(this.changes));
	return true;
};

product_account_reps.prototype.edit_rep_change = function(select)
{
	var rep,
		selected = select.val();
	
	if (selected)
	{
		rep = window.globals.rep_data[selected];
		$f('sbs_account_rep_name', rep.name);
		$f('sbs_account_rep_email', rep.email);
		$f('sbs_account_rep_phone', rep.phone);
		$('#edit_rep_table').show();
		$('#edit_rep_table input[name="sbs_account_rep_name"]').select();
	}
	else
	{
		$('#edit_rep_table').hide();
	}
};

product_account_reps.prototype.get_rep_info_callback = function(request, response)
{
};
