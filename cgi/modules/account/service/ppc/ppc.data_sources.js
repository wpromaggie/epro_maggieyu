
function ppc_data_sources()
{
	// scheduling
	$('#schedule_new_refresh_link').bind('click', this, function(e){ e.data.schedule_new_link_click($(this)); return false; });
	$('#schedule_cancel_link').bind('click', this, function(e){ e.data.schedule_cancel_link_click($(this)); return false; });
	$('#scheduled_refreshes a').bind('click', this, function(e){ e.data.schedule_current_click($(this)); return false; });
	$('.schedule_form select[name*="frequency"]').bind('change', this, function(e){ e.data.schedule_form_frequency_change($(this)); });
	
	// delete
	$('#ds_table input[name="delete_submit"]').bind('click', this, function (e){ return e.data.delete_click($(this)); });

	// refresh
	$('#refresh_table span[is_running]').each(function(){
		var $elem = $(this);
		e2.job_status_widget($elem, $elem.attr('job_id'));
	});
	
	// new ds stuff
	$('#ds_new_market,#ds_new_level').bind('change', this, function(e){ e.data.new_market_level_change(); });
	$('#ds_new_market,#ds_new_level').bind('change', this, function(e){ e.data.new_market_level_change(); });
	$('#ds_new_account').bind('change', this, function(e){ e.data.new_account_change($(this)); });
	$('#ds_new_campaign').bind('change', this, function(e){ e.data.new_campaign_change($(this)); });
	
	// add external
	$('#add_external_button').bind('click', this, function(e){ $('#new_external').show(); return false; });
	$('#cancel_add_external').bind('click', this, function(e){ $('#new_external').hide(); return false; });
	
	// manually adding google account
	$('a.add_google_account_link').bind('click', this, function(e){ e.data.add_google_account_click(e); return false; });
	
	// update google account email
	$('a.update_m_pass_link').bind('click', this, function(e){ e.data.update_m_pass_link_click(e, $(this)); return false; });
}

ppc_data_sources.prototype.update_m_pass_link_click = function(e, a)
{
	var box;
	
	a.blur();
	$('#m_update_pass_ac_id').val(a.attr('ac_id'));
	box = $.box({
		id:'update_m_pass_box',
		title:'Update Password',
		close:true,
		event:e,
		content:'\
			<label for="m_new_ac_pass">New Password</label>\
			<input type="text" id="m_new_ac_pass" name="m_new_ac_pass" value="" />\
			<input type="submit" a0="action_m_update_ac_pass" value="Update" />\
		'
	});
	e2.auto_action_submit(box);
	$('#m_new_ac_pass').select();
};

ppc_data_sources.prototype.add_google_account_click = function(e)
{
	if (this.aga)
	{
		this.aga.show();
	}
	else
	{
		this.aga = new add_google_account(e);
	}
};

ppc_data_sources.prototype.schedule_new_link_click = function(a)
{
	a.blur();
	
	this.schedule_form_init('Schedule New', '', {
		frequency:'Weekly',
		day_of_week:'Monday',
		day_of_month:1,
		time:'00:00',
		num_days:0
	});
};

ppc_data_sources.prototype.schedule_cancel_link_click = function(a)
{
	a.hide();
	$('#_schedule_form').hide();
};

ppc_data_sources.prototype.schedule_current_click = function(a)
{
	a.blur();
	this['schedule_current_click_'+a.text().toLowerCase()](a);
};

ppc_data_sources.prototype.schedule_current_click_edit = function(a)
{
	var row, cells;
	
	row = a.closest('tr');
	cells = row.find('td');
	this.schedule_form_init('Edit', row.attr('rid'), {
		frequency:$(cells[0]).text(),
		day_of_week:$(cells[1]).text(),
		day_of_month:this.extract_number($(cells[1]).text()),
		time:$(cells[2]).attr('time'),
		num_days:this.extract_number($(cells[3]).text())
	});
};

ppc_data_sources.prototype.extract_number = function(s)
{
	var matches;
	
	if (matches = s.match(/(\d+)/)) return Number(matches[1]);
	return null;
}

ppc_data_sources.prototype.schedule_current_click_delete = function(a)
{
	var tr;
	
	a.blur();
	tr = a.closest('tr');
	if (confirm('Delete: '+tr.find('td').map(function(i){ if (i < 4) return $(this).text(); }).get().join(', ')+'?'))
	{
		$f('schedule_refresh_delete_id', tr.attr('rid'));
		e2.action_submit('action_schedule_refresh_delete');
	}
};

ppc_data_sources.prototype.schedule_form_init = function(title, refresh_id, data)
{
	var container, key;
	
	container = $('#_schedule_form');
	container.find('legend').text(title);
	for (key in data)
	{
		$f('ppc_schedule_refresh_'+key, data[key]);
	}
	$f('schedule_refresh_edit_id', refresh_id);
	this.schedule_form_frequency_change(container.find('select[name*="frequency"]'), data.num_days);
	container.show();
	$('#schedule_cancel_link').show();
};

ppc_data_sources.prototype.schedule_form_frequency_change = function(input)
{
	var container, num_days;
	
	container = input.closest('.schedule_form');
	num_days = (arguments.length > 1 && arguments[1]) ? arguments[1] : null;
	if (input.val() == 'Weekly')
	{
		container.find('select[name*="day_of_week"]').closest('tr').show();
		container.find('select[name*="day_of_month"]').closest('tr').hide();
		if (!num_days) num_days = 7;
	}
	else if (input.val() == 'Monthly')
	{
		container.find('select[name*="day_of_week"]').closest('tr').hide();
		container.find('select[name*="day_of_month"]').closest('tr').show();
		if (!num_days) num_days = 31;
	}
	container.find('select[name*="num_days"]').val(num_days);
};

ppc_data_sources.prototype.ds_refresh_click = function(a)
{
	var tr;
	
	tr = a.closest('tr');
	$f('show', 'ds_refresh_show');
	$f('refresh_type', tr.attr('refresh_type'));
	$f('refresh_market', tr.attr('market'));
	
	f_submit();
};

ppc_data_sources.prototype.new_market_level_change = function()
{
	if ($('#ds_new_market').val() && $('#ds_new_level').val())
	{
		f_submit();
	}
};

ppc_data_sources.prototype.new_account_change = function(select)
{
	if (select.val() && $('#ds_new_level').val() != 'Account')
	{
		f_submit();
	}
};

ppc_data_sources.prototype.new_campaign_change = function(select)
{
	if (select.val() && $('#ds_new_level').val() != 'Campaign')
	{
		f_submit();
	}
};

ppc_data_sources.prototype.delete_click = function(input)
{
	var tr, tds, td, details, i;
	
	tr = input.closest('tr');
	tds = tr.children();
	details = [];
	for (i = 2; i < 5; ++i)
	{
		td = $(tds[i]);
		if (!td.text())
		{
			break;
		}
		details.push(td.text());
	}
	if (!confirm("Delete "+details.join(' - ')))
	{
		input.blur();
		return false;
	}
	$f('delete_info', tr.attr('ds_info'));
	return true;
};
