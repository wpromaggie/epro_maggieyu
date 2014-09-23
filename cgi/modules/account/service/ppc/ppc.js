
function ppc()
{
	$('#select_cols_link').bind('click', this, function(e){ e.data.select_cols_click($(this)); return false; });
}

function ajax_track_sync_status_callback(request, response)
{
	var row, map, i;
	
	map = ['processing_start', 'processing_end', 'status', 'details'];
	row = $('#sync_details tr[job_id="'+request.job_id+'"]');
	for (i = 0; i < map.length; ++i)
	{
		// indexed from 1, 1st col we don't need to update (the row number)
		row.find('td:nth-child('+(i + 2)+')').text(response[map[i]]);
	}
	// not done yet
	if (response.status == 'Completed' || response.status == 'Error') return;
	window.setTimeout(track_sync_status_refresh, 5000);
}

function track_sync_status_refresh()
{
	var request, rows;
	
	// there should be at most 1
	rows = $('#sync_details tr[is_running="1"]');
	if (rows.length > 0)
	{
		ajax_post('ajax_job_status', ajax_track_sync_status_callback, {'job_id':$(rows[0]).attr('job_id')});
	}
}

function track_trackify_level_click()
{
	var level, i, radio, radio_val, before_level;
	
	level = $f('level');
	
	if (empty(level)) return;
	
	td.set_market_and_level($f('market'), level);
	radio = $('input[type=radio][name=level]');
	before_level = true;
	for (i = 0; i < radio.length; ++i)
	{
		radio_val = $(radio[i]).val();
		
		// don't need to show/hide anything for account
		if (radio_val != 'account')
		{
			if (before_level)
			{
				$('div#items fieldset#'+radio_val).show();
			}
			else
			{
				$('div#items fieldset#'+radio_val).hide();
			}
		}
		
		if (radio_val == level)
		{
			before_level = false;
		}
	}
	
	if (level != 'account') td.get_campaigns();
	$('input[type="submit"][name="trackify_submit"]').attr('disabled', false);
}

function Trackify_Data(market, level)
{
	// construct
	this.market = null;
	this.level = null;
	this.num_cas_visible = 0;
	this.campaigns = {};
	this.ad_groups = {};
}

Trackify_Data.prototype.get_campaigns = function()
{
	var request;
	
	if (!this.campaigns[this.market])
	{
		request = {
			'cl_id':$f('client'),
			'market':this.market,
			'deleted':$f('include_deleted')
		};
		ajax_post('ajax_track_trackify_get_campaigns', 'ajax_track_trackify_get_campaigns_callback', request, this);
	}
}

Trackify_Data.prototype.get_ad_groups = function(ca_id)
{
	var request;
	
	if (!this.ad_groups[this.market])
	{
		this.ad_groups[this.market] = {};
	}
	if (!this.ad_groups[this.market][ca_id])
	{
		request = {
			'cl_id':$f('client'),
			'market':this.market,
			'ca_id':ca_id,
			'deleted':$f('include_deleted')
		};
		ajax_post('ajax_track_trackify_get_ad_groups', 'ajax_track_trackify_get_ad_groups_callback', request, this);
	}
	else
	{
		$('#ad_group #ad_groups table[ca_id="'+ca_id+'"]').show();
	}
	
	// first one to show, get rid of select one or more campaigns message
	if (++this.num_cas_visible == 1)
	{
		$('#ad_group #select_a_ca').hide();
	}
}

Trackify_Data.prototype.hide_ad_groups = function(ca_id)
{
	$('#ad_group #ad_groups table[ca_id="'+ca_id+'"]').hide();
	if (--this.num_cas_visible == 0)
	{
		$('#ad_group #select_a_ca').show();
	}
}

Trackify_Data.prototype.set_campaign_ml = function()
{
	var id, campaign;
	
	ml = '';
	for (id in this.campaigns[this.market])
	{
		campaign = this.campaigns[this.market][id];
		ml += '\
			<tr>\
				<td><input type="checkbox" value="'+id+'" /></td>\
				<td>'+campaign.text+'</td>\
				<td>'+campaign.status+'</td>\
			</tr>\
		';
	}
	$('fieldset#campaign tbody').append(ml);
	
	// tie handler to ca cbox'es even if level isn't ad group because it might be later and this function is only called the first time
	// simple enough to check the level in the cbox click handler
	$('fieldset#campaign tbody input[type=checkbox]').bind('click', this, function(e){e.data.campaign_cbox_click(e);});
}

Trackify_Data.prototype.campaign_cbox_click = function(e)
{
	var cbox;
	
	// don't need to show ad groups
	if (this.level != 'ad_group') return;
	
	cbox = $(e.target);
	if (cbox.is(':checked'))
	{
		this.get_ad_groups(cbox.val());
	}
	else
	{
		this.hide_ad_groups(cbox.val());
	}
}

Trackify_Data.prototype.set_ad_group_ml = function(ca_id)
{
	var ag, ag_id, ml;
	
	ml = '';
	for (ag_id in this.ad_groups[this.market][ca_id])
	{
		ag = this.ad_groups[this.market][ca_id][ag_id];
		ml += '\
			<tr>\
				<td><input type="checkbox" id="ag_'+ag_id+'" value="'+ag_id+'" /></td>\
				<td><label for="ag_'+ag_id+'">'+ag.text+'</label></td>\
				<td>'+ag.status+'</td>\
			</tr>\
		';
	}
	
	$('#ad_group #ad_groups').append('\
		<table ca_id="'+ca_id+'">\
			<thead>\
				<tr>\
					<td colspan=3>&bull;<i>'+this.campaigns[this.market][ca_id].text+'</i>&bull;</td>\
				</tr>\
				<tr>\
					<td><input type="checkbox" value="all" /></td>\
					<td colspan=2>Toggle All</td>\
				</tr>\
			</thead>\
			<tbody>\
				'+ml+'\
			</tbody>\
		</table>\
	');
	$('#ad_group #ad_groups table[ca_id="'+ca_id+'"] input[value="all"]').bind('click', this, function(e){e.data.ag_toggle_all_in_campaign_click(e);});
}

Trackify_Data.prototype.ajax_track_trackify_get_ad_groups_callback = function(request, response)
{
	var i, ag;
	
	this.ad_groups[this.market][request.ca_id] = {};
	for (i = 0; i < response.length; ++i)
	{
		ag = response[i];
		this.ad_groups[this.market][request.ca_id][ag.id] = {
			'text':ag.text,
			'status':ag.status
		};
	}
	this.set_ad_group_ml(request.ca_id);
}

Trackify_Data.prototype.ag_toggle_all_in_campaign_click = function(e)
{
	var input;
	
	input = $(e.target);
	input.closest('table').find('tbody input[type="checkbox"]').attr('checked', input.is(':checked'));
}

Trackify_Data.prototype.ajax_track_trackify_get_campaigns_callback = function(request, response)
{
	var i, campaign;
	
	this.campaigns[this.market] = {};
	for (i = 0; i < response.length; ++i)
	{
		campaign = response[i];
		this.campaigns[this.market][campaign.id] = {
			'text':campaign.text,
			'status':campaign.status
		};
	}
	this.set_campaign_ml();
}

Trackify_Data.prototype.set_market_and_level = function(market, level)
{
	this.market = market;
	this.level = level;
}

Trackify_Data.prototype.submit = function()
{
	var vals;
	
	if (this.level == 'account')
	{
		return true;
	}
	vals = this.set_checkbox_values(this.level);
	if (!vals) return false;
	$f('entity_ids', vals.join("\t"));
	return true;
}

Trackify_Data.prototype.set_checkbox_values = function(type)
{
	checked = $('fieldset#'+type+' input[value!="all"][type="checkbox"]:checked');
	if (checked.length == 0)
	{
		alert('Please select at least 1 '+type.display_text()+'.');
		return false;
	}
	vals = [];
	for (i = 0; i < checked.length; ++i)
	{
		vals.push($(checked[i]).val());
	}
	return vals;
}

function ajax_track_trackify_status_refresh_callback(request, response)
{
	var map, elem, i;
	
	map = ['processing_start', 'processing_end', 'status', 'details'];
	for (i = 0; i < map.length; ++i)
	{
		elem = map[i];
		// indexed from 1, 1st col we don't need to update (the row number)
		$('td#'+elem).text(response[elem]);
	}
	dbg(response.status);
	// not done yet
	if (response.status == 'Completed' || response.status == 'Error') return;
	window.setTimeout(track_trackify_status_refresh, 15000);
}

function track_trackify_status_refresh()
{
	ajax_post('ajax_job_status', ajax_track_trackify_status_refresh_callback, {'job_id':$f('job_id')});
}

function track_trackify_init()
{
	var s;
	
	// form view
	if (!$.empty('div#items') || 1)
	{
		td = new Trackify_Data();
		$('input[name=level]').click(function(){track_trackify_level_click();});
		$('input[type="submit"][name="trackify_submit"]').attr('disabled', true);
		$('input[type="submit"][name="trackify_submit"]').bind('click', td, function(e){return e.data.submit();});
	}
	// status view
	else
	{
		track_trackify_status_refresh();
	}
}

function track_action(e)
{
	var input;
	
	input = $(e.currentTarget);
	$f('market', input.closest('fieldset').attr('m'));
}

function track_changes()
{
	var self = this;
	
	$('#account_submit_cbox').bind('click', this, function(e){e.data.account_submit_checkbox_click($(this));});
	$('#account_submit').bind('click', this, function(e){e.data.account_submit_click();});
	
	$('#details_table').table({
		'data':g_campaign_data,
		'cols':['campaign', 'cas', 'ags', 'ads', 'kws'],
		'clear_float':false,
		'click_tr':function(e, tr, data){self.campaign_click(tr, data);},
		'checkboxes':function(e, td, table, data){self.entity_checkbox_click(e, td, table, data, 'campaign');}
	});
}

track_changes.prototype.account_submit_checkbox_click = function(cbox)
{
	$('#account_submit')[((cbox.is(':checked')) ? 'show' : 'hide')]();
};

track_changes.prototype.account_submit_click = function()
{
	$f('level', 'Account');
	$f('entity_ids', $f('ac_id'));
};

track_changes.prototype.entity_submit_click = function(submit, type)
{
	var ids;
	
	ids = submit.closest('.checked_entities').find('.list div:visible').map(function(){return $(this).attr('entity_id');}).get();
	
	$f('level', type.display_text());
	$f('entity_ids', ids.join("\t"));
	
	return true;
};

track_changes.prototype.campaign_click = function(tr, data)
{
	var row_selected_id, child_id, container;
	
	container = tr.closest('.details_container');
	container.find(' > .details_table_container').hide();
	container.find(' > .details_table_actions').hide();
	
	child_id = container.attr('id')+'_'+data.ca_id;
	row_selected_id = child_id+'_selected';
	
	// user has already clicked this row
	if (!$.empty('#'+child_id))
	{
		this.show_previously_clicked(row_selected_id, child_id);
	}
	// first time click, ajax get data
	else
	{
		dbg(container.length, row_selected_id);
		// show the row that was selected so user can see what they clicked
		container.append('\
			<div class="selected_row">\
				<div class="selected_row_info" id="'+row_selected_id+'">Campaign: '+data.campaign+'</div>\
				<div class="selected_row_actions">\
				</div>\
				<div class="clr"></div>\
			</div>\
		');
		
		data.client = $f('client');
		data.market = $f('market');
		data.child_id = child_id;
		data.parent_id = container.attr('id');
		//ajax_post('ajax_track_changes_get_ad_groups', 'ajax_get_ad_groups_callback', data, this);
		this.post('track_changes_get_ad_groups', data)
		dbg('post sent');
	}
};

track_changes.prototype.track_changes_get_ad_groups = function(request, response)
{
	this.ajax_get_ad_groups(request, response);
}

track_changes.prototype.show_previously_clicked = function(row_selected_id, child_id)
{
	var child_container;
	
	if (row_selected_id)
	{
		$('#'+row_selected_id).closest('.selected_row').show();
	}
	
	child_container = $('#'+child_id);
	child_container.show();
	child_container.find('.selected_row').hide();
	child_container.find(' > .details_table_container').show();
	child_container.find(' > .details_table_actions').show();
};

track_changes.prototype.ajax_get_ad_groups = function(request, response)
{
	var id, self, level;
	
	//dbg(response);
	if (Types.is_string(response)) return;
	id = request.child_id;
	$('#changes').append('\
		<div class="details_container" style="padding-left:20px;" id="'+id+'" pid="'+request.parent_id+'">\
			<div class="details_table_container" id="'+id+'_table"></div>\
			<div class="details_table_actions">\
				<a href="back">&uarr; Back to Campaigns</a>\
			</div>\
			<div class="clr"></div>\
		</div>\
	');
	self = this;
	$('#'+id+'_table').table({
		'data':response,
		'cols':['ad_group', 'ags', 'ads', 'kws'],
		'show_nav':true,
		'page_size':50,
		'clear_float':false,
		'click_tr':function(e, tr, data){self.ad_group_click(tr, data);},
		'checkboxes':function(e, td, table, data){self.entity_checkbox_click(e, td, table, data, 'ad_group');}
	});
	$('#'+id+' a[href="back"]').bind('click', this, function(e){e.data.details_table_close_click($(this));return false;});
};

track_changes.prototype.entity_checkbox_click = function(e, td, table, data, type)
{
	var type_short, is_checked, actions_container, list, visible;
	
	type_short = (type == 'campaign') ? 'ca' : 'ag';
	if (e)
	{
		e.stopPropagation();
	}
	is_checked = td.find('input').is(':checked');
	actions_container = table.closest('.details_container').find('.details_table_actions');
	
	if ($.empty(actions_container.find('.checked_entities')))
	{
		actions_container.append('\
			<div class="checked_entities">\
				<div class="list"></div>\
				<div><input type="submit" name="trackify_submit" value="Submit" /></div>\
			</div>\
		');
		actions_container.find('.checked_entities input[type="submit"]').bind('click', this, function(e){return e.data.entity_submit_click($(this), type);});
	}
		
	list = actions_container.find('.list');
	if (is_checked)
	{
		list.wp_show('div[entity_id="'+data[type_short+'_id']+'"]', 'append', '<div entity_id="'+data[type_short+'_id']+'">'+data[type]+'</div>');
	}
	else
	{
		list.find('div[entity_id="'+data[type_short+'_id']+'"]').hide();
	}
	visible = list.find('div[entity_id]:visible');
	actions_container.find('.checked_entities input[type="submit"]').toggle((visible.length > 0));
};

track_changes.prototype.ad_group_click = function(tr, data)
{
	var row_selected_id, child_id, container;
	
	container = tr.closest('.details_container');
	container.find(' > .details_table_container').hide();
	container.find(' > .details_table_actions').hide();
	
	child_id = container.attr('id')+'_'+data.ag_id;
	row_selected_id = child_id+'_selected';
	
	// user has already clicked this row
	if (!$.empty('#'+child_id))
	{
		this.show_previously_clicked(row_selected_id, child_id);
	}
	// first time click, ajax get data
	else
	{
		dbg(container.length, row_selected_id, data);
		// show the row that was selected so user can see what they clicked
		container.append('\
			<div class="selected_row">\
				<div class="selected_row_info" id="'+row_selected_id+'">Ad Group: '+data.ad_group+'</div>\
				<div class="selected_row_actions">\
					<a href="ads">Ads ('+data.ads+')</a>\
					<a href="kws">Keywords ('+data.kws+')</a>\
					<a href="sample">Sample wpropath URLs</a>\
					<a href="ags">&larr; Back to Ad Groups</a>\
					<a href="cas">&uarr; Back to Campaigns</a>\
				</div>\
				<div class="clr"></div>\
			</div>\
		');
		
		container.find('a').bind('click', this, function(e){e.data.selected_ag_click(container, $(this));return false;});
	}
};

track_changes.prototype.selected_ag_click = function(container, a)
{
	a.blur();
	this['selected_ag_goto_'+a.attr('href')](container, a.closest('.selected_row'));
};

track_changes.prototype.selected_ag_goto_sample = function(details_container, selected_container)
{
	var data, id_parts, child_id;
	
	id_parts = selected_container.find('.selected_row_info').attr('id').split('_');
	child_id = id_parts.slice(0, 3).join('_');
	
	dbg(child_id);
	return;
};

track_changes.prototype.selected_ag_goto_ads = function(details_container, selected_container)
{
	this.selected_ag_goto_details(selected_container, 'Ad');
};

track_changes.prototype.selected_ag_goto_kws = function(details_container, selected_container)
{
	this.selected_ag_goto_details(selected_container, 'Keyword');
};

track_changes.prototype.selected_ag_goto_details = function(container, type)
{
	var data, id_parts, child_id;
	
	id_parts = container.find('.selected_row_info').attr('id').split('_');
	child_id = id_parts.slice(0, 3).join('_')+'_'+type;
	
	// if user clicked Ad, hide Keyword; vice versa
	$('#'+child_id.replace(/(Ad|Keyword)$/, (type == 'Ad') ? 'Keyword' : 'Ad')).hide();
	
	// user had previously viewed details, just show
	if (!$.empty('#'+child_id))
	{
		$('#'+child_id).show();
	}
	else
	{
		data = {
			'client':$f('client'),
			'market':$f('market'),
			'ag_id':id_parts[2],
			'type':type,
			'parent_id':container.closest('.details_container').attr('id'),
			'child_id':child_id
		};
		//ajax_post('ajax_track_changes_get_ad_group_details', 'ajax_get_ad_group_details_callback', data, this);
		this.post('track_changes_get_ad_group_details', data);
	}
};

track_changes.prototype.track_changes_get_ad_group_details_callback = function(request, response)
{
	var id;
	
	//dbg(response);
	if (Types.is_string(response)) return;
	id = request.child_id;
	$('#changes').append('\
		<div class="details_container" style="padding-left:40px;" id="'+id+'" pid="'+request.parent_id+'">\
			<div class="details_table_container" id="'+id+'_table"></div>\
			<div class="details_table_actions">\
				<a href="back">&uarr; Back to Ad Groups</a>\
			</div>\
			<div class="clr"></div>\
		</div>\
	');
	$('#'+id+'_table').table({
		'data':response,
		'show_nav':true,
		'page_size':50,
		'clear_float':false
	});
	$('#'+id+' a[href="back"]').bind('click', this, function(e){e.data.details_table_close_click($(this));return false;});
};

track_changes.prototype.selected_ag_goto_ags = function(details_container, selected_container)
{
	this.hide_ad_group_details(details_container);
	this.show_previously_clicked(null, details_container.attr('id'));
};

track_changes.prototype.selected_ag_goto_cas = function(details_container, selected_container)
{
	this.hide_ad_group_details(details_container);
	this.fold_up(details_container);
};

track_changes.prototype.hide_ad_group_details = function(container)
{
	var child_id;
	
	child_id = container.find('.selected_row_info:visible').attr('id').replace(/_selected$/, '');
	$('#'+child_id+'_Ad').hide();
	$('#'+child_id+'_Keyword').hide();
};

track_changes.prototype.details_table_close_click = function(a)
{
	this.fold_up(a.closest('.details_container'));
};

track_changes.prototype.fold_up = function(container)
{
	var parent;
	
	parent = $('#'+container.attr('pid'));
	
	// hide table and actions
	container.hide();
	
	// hide parent selected row stuff
	parent.find('.selected_row').hide();
	
	// show parent table
	parent.find(' > .details_table_container').show();
	parent.find(' > .details_table_actions').show();
};

function track_init()
{
	if (!$.empty('#google_sync'))
	{
		track_sync_status_refresh();
	}
	else if (!$.empty('#trackify'))
	{
		track_trackify_init();
	}
	else if (!$.empty('#conv_code'))
	{
		track_conv_code_init();
	}
	else if (!$.empty('#track_changes'))
	{
		new track_changes();
	}
	else if (!$.empty('#schedule_job'))
	{
		//new track_schedule_job();
	}
	// track home
	else
	{
		$('div.actions input[type="submit"]').click(function(e){track_action(e);});
	}
}

function track_schedule_job()
{
	// construct
	this.market = null;
	this.level = null;
	this.num_cas_selected = 0;
	this.campaigns = {};
	this.ad_groups = {};

	$('#campaigns').closest('fieldset').hide();
	$('#ad_groups').closest('fieldset').hide();
	
	$('#levels input').bind('change', this, function(e){e.data.level_change($(this));});
	$('#markets input').bind('change', this, function(e){e.data.market_change($(this));});
	$('input[name="refresh_campaigns"]').bind('click', this, function(e){e.data.refresh_campaigns_submit();return false;});
	$('input[name="schedule_job_submit"]').bind('click', this, function(e){return e.data.submit($(this));});
	
	this.get_sync_status();
}

track_schedule_job.prototype.refresh_campaigns_submit = function()
{
	var request = {
		'cl_id':$f('client'),
		'market':this.market,
		'deleted':$f('include_deleted')
	};
	this.post('track_sync_refresh_campaigns', request);
}

track_schedule_job.prototype.track_sync_refresh_campaigns_callback = function(request, response)
{
	this.ajax_get_campaigns(response)
}

track_schedule_job.prototype.get_sync_status = function()
{
	var job_ids, request;
	
	job_ids = $('#markets div.sync_status[do_get_status="1"]').map(function(){return $(this).attr('job_id');}).get().join("\t");
	if (job_ids == "")
	{
		return;
	}
	
	request = {
		'cl_id':$f('client'),
		'job_ids':job_ids,
		'deleted':$f('include_deleted')
	};
	//ajax_post('ajax_track_sync_get_sync_status', 'ajax_track_sync_get_sync_status_callback', request, this);
	this.post('track_sync_get_sync_status', request);
}


track_schedule_job.prototype.track_sync_get_sync_status_callback = function(request, response)
{
	var job_id, self, div;
	
	//dbg(request, response);
	for (job_id in response)
	{
		div = $('#markets div.sync_status[job_id="'+job_id+'"]');
		div.find('span.status_details').html(response[job_id].status);
		if (response[job_id].is_done == '1')
		{
			div.attr('do_get_status', '0');
			div.find('span.loading').empty();
		}
	}
	self = this;
	window.setTimeout(function(){self.get_sync_status();}, 5000);
}

track_schedule_job.prototype.market_change = function(input)
{
	this.level_market_change();
}

track_schedule_job.prototype.level_change = function(input)
{
	this.level_market_change();
}

track_schedule_job.prototype.level_market_change = function()
{
	this.level = $f('level');
	this.market = $f('market');
	
	if (this.level && this.market)
	{
		if (this.level == 'account')
		{
			$('#campaigns').closest('fieldset').hide();
			$('#ad_groups').closest('fieldset').hide();
		}
		else
		{
			if (this.level == 'campaign')
			{
				$('#campaigns').closest('fieldset').show();
				$('#ad_groups').closest('fieldset').hide();
			}
			else
			{
				$('#campaigns').closest('fieldset').show();
				$('#ad_groups').closest('fieldset').show();
			}
			this.get_campaigns();
		}
	}
	else
	{
		$('#campaigns').closest('fieldset').hide();
		$('#ad_groups').closest('fieldset').hide();
	}
}

track_schedule_job.prototype.get_campaigns = function()
{
	var request;
	
	if (!this.campaigns[this.market])
	{
		request = {
			'cl_id':$f('client'),
			'market':this.market,
			'deleted':$f('include_deleted')
		};
		this.post('track_sync_get_campaigns', request);
	}
	else
	{
		this.set_campaign_ml();
	}
}

track_schedule_job.prototype.track_sync_get_campaigns_callback = function(request, response){
	this.ajax_get_campaigns(response)
}

track_schedule_job.prototype.ajax_get_campaigns = function(response)
{
	var i, campaign;
	
	this.campaigns[this.market] = {};
	for (i = 0; i < response.length; ++i)
	{
		campaign = response[i];
		this.campaigns[this.market][campaign.id] = {
			'text':campaign.text,
			'status':campaign.status
		};
	}
	this.set_campaign_ml();
}


track_schedule_job.prototype.set_campaign_ml = function()
{
	var id, campaign, cbox_id;
	
	if (empty(this.campaigns[this.market]))
	{
		ml = '<p>No campaigns in market.</p>';
	}
	else
	{
		ml = '';
		for (id in this.campaigns[this.market])
		{
			campaign = this.campaigns[this.market][id];
			cbox_id = 'campaign_cbox_'+this.market+'_'+id;
			ml += '\
				<tr>\
					<td><input id="'+cbox_id+'" type="checkbox" value="'+id+'" /></td>\
					<td><label for="'+cbox_id+'">'+campaign.text+'</label></td>\
					<td>'+campaign.status+'</td>\
				</tr>\
			';
		}
		ml = '<table><tbody>'+ml+'</tbody></table>'
	}
	$('#campaigns').html(ml);
	
	// tie handler to ca cbox'es even if level isn't ad group because it might be later and this function is only called the first time
	// simple enough to check the level in the cbox click handler
	$('#campaigns tbody input[type=checkbox]').bind('click', this, function(e){e.data.campaign_cbox_click($(this));});
}

track_schedule_job.prototype.campaign_cbox_click = function(cbox)
{
	// don't need to show ad groups
	if (this.level != 'ad_group') return;
	
	if (cbox.is(':checked'))
	{
		this.num_cas_selected++;
		this.get_ad_groups(cbox.val());
	}
	else
	{
		//this.num_cas_selected--;
		//this.hide_ad_groups(cbox.val());
	}
}

track_schedule_job.prototype.get_ad_groups = function(ca_id)
{
	var request;
	
	if (!this.ad_groups[this.market])
	{
		this.ad_groups[this.market] = {};
	}
	if (!this.ad_groups[this.market][ca_id])
	{
		request = {
			'cl_id':$f('client'),
			'market':this.market,
			'ca_id':ca_id,
			'deleted':$f('include_deleted')
		};
		//ajax_post('ajax_track_sync_get_ad_groups', 'ajax_get_ad_groups_callback', request, this);
		this.post('track_sync_get_ad_groups', request);
	}
	else
	{
		$('#ad_groups table[ca_id="'+ca_id+'"]').show();
	}
	
	// first one to show, get rid of select one or more campaigns message
	if (this.num_cas_selected == 1)
	{
		$('#ad_group_meta').hide();
	}
}

track_schedule_job.prototype.track_sync_get_ad_groups_callback = function(request, response)
{
	this.ajax_get_ad_groups(request, response)
}

track_schedule_job.prototype.ajax_get_ad_groups_callback = function(request, response)
{
	var i, ag;

	this.ad_groups[this.market][request.ca_id] = {};
	for (i = 0; i < response.length; ++i)
	{
		ag = response[i];
		this.ad_groups[this.market][request.ca_id][ag.id] = {
			'text':ag.text,
			'status':ag.status
		};
	}
	this.set_ad_group_ml(request.ca_id);
}

track_schedule_job.prototype.set_ad_group_ml = function(ca_id)
{
	var ag, ag_id, cbox_id, ml;
	
	ml = '';
	for (ag_id in this.ad_groups[this.market][ca_id])
	{
		ag = this.ad_groups[this.market][ca_id][ag_id];
		cbox_id = 'ad_group_cbox_'+this.market+'_'+ag_id;
		ml += '\
			<tr>\
				<td><input type="checkbox" id="'+cbox_id+'" value="'+ag_id+'" /></td>\
				<td><label for="'+cbox_id+'">'+ag.text+'</label></td>\
				<td>'+ag.status+'</td>\
			</tr>\
		';
	}
	
	$('#ad_groups').append('\
		<table ca_id="'+ca_id+'">\
			<thead>\
				<tr>\
					<td colspan=3>&bull;<i>'+this.campaigns[this.market][ca_id].text+'</i>&bull;</td>\
				</tr>\
				<tr>\
					<td><input type="checkbox" value="all" /></td>\
					<td colspan=2>Toggle All</td>\
				</tr>\
			</thead>\
			<tbody>\
				'+ml+'\
			</tbody>\
		</table>\
	');
	$('#ad_groups table[ca_id="'+ca_id+'"] input[value="all"]').bind('click', this, function(e){e.data.ag_toggle_all_in_campaign_click($(this));});
}

track_schedule_job.prototype.ag_toggle_all_in_campaign_click = function(cbox)
{
	cbox.closest('table').find('tbody input[type="checkbox"]').attr('checked', cbox.is(':checked'));
}

track_schedule_job.prototype.submit = function(submit)
{
	var vals;
	
	if (this.level == 'account')
	{
		return true;
	}
	vals = this.set_checkbox_values(this.level);
	if (!vals) return false;
	$f('entity_ids', vals.join("\t"));
	return true;
}

track_schedule_job.prototype.set_checkbox_values = function(type)
{
	checked = $('#'+type+'s input[value!="all"][type="checkbox"]:checked');
	if (checked.length == 0)
	{
		alert('Please select at least 1 '+type.display_text()+'.');
		return false;
	}
	vals = [];
	for (i = 0; i < checked.length; ++i)
	{
		vals.push($(checked[i]).val());
	}
	return vals;
}

function track_conv_code_init()
{
	$('textarea').focus(function(e){$(this).select();});
}

function ppc_init()
{
	if (!$.empty('div#track')) track_init();
}

$(document).ready(function() {ppc_init();});

function ppc_info()
{
	this.notes_list = $('#notes_ul');
	this.notes_input = $('#notes_input');
	this.show_notes();
	
	$('#edit_notes_a').bind('click', this, function(e){e.data.edit_notes_click($(this));return false;});
	$('#notes_update_button').bind('click', this, function(e){e.data.edit_notes_update();return false;});
	$('#notes_cancel_button').bind('click', this, function(e){e.data.edit_notes_cancel();return false;});

	this.init_conv_types();
}

ppc_info.prototype.init_conv_types = function()
{
	var $radio_on = $('#conversion_types_1');
	if ($radio_on.is(':checked')) {
		$radio_on.closest('td').append('\
			<span id="w_manage_conv_types">\
				<a href="'+e2.url('/account/service/ppc/manage_conv_types'+window.location.search)+'">Manage Conv Types</a>\
			</span>\
		');
	}
};

ppc_info.prototype.show_notes = function()
{
	var i, ml, s_notes, a_notes;
	
	s_notes = this.notes_input.find('textarea').val();
	s_notes = s_notes.replace("\r", '');
	s_notes = s_notes.replace(/((\s*\n\s+)|(\s+\n\s*))/g, "\n"); // replace consecutive whitespace containing at least one new line with just a new line
	if (empty(s_notes))
	{
		this.notes_list.empty();
	}
	else
	{
		a_notes = s_notes.split("\n");
		
		ml = '';
		for (i = 0; i < a_notes.length; ++i)
		{
			if (!empty(a_notes[i]))
			{
				ml += '<li>'+a_notes[i]+'</li>';
			}
		}
		this.notes_list.html(ml);
	}
	
	this.notes_input.hide();
	this.notes_list.show();
};

ppc_info.prototype.edit_notes_click = function(a)
{
	a.blur();
	this.edit_notes();
};

ppc_info.prototype.edit_notes = function()
{
	var a_notes;
	
	this.notes_list.hide();
	a_notes = this.notes_list.find('li').map(function(){return $(this).text();}).get();
	this.notes_input.find('textarea').val(a_notes.join("\n\n"));
	
	this.notes_input.show();
	this.notes_list.hide();
};

ppc_info.prototype.edit_notes_update = function(a)
{
	this.post('info_update_notes', {
		notes:this.notes_input.find('textarea').val()
	});
};

ppc_info.prototype.info_update_notes_callback = function(request, response)
{
	this.show_notes();
	Feedback.add_success_msg('Notes Updated');
};

ppc_info.prototype.edit_notes_cancel = function(a)
{
	this.show_notes();
};

ppc_manage_conv_types = function()
{
	this.$tbody = $('#ct_body');
	var r = this.init_data();
	if (r === false) {
		this.$tbody.append('<tr><td id="no_conv_data_msg">\
			<p>No conversion type data.</p>\
			<p>To manage conversions, please first <a href="'+e2.url('/account/service/ppc/data_sources/'+window.location.search)+'">refresh data</a>\
			in all markets (eg google, bing) for which you expect conversion type data.\
			Once this data is in our local database, you will be able to set up conversion types here and then see them in the CDL, reporting, etc.</p>\
		</td></tr>');
		$('#submit_buttons').hide();
	}
	else {
		this.init_actions();
		this.init_form();
	}
}

ppc_manage_conv_types.prototype = {

	init_data:function() {
		this.conv_types = globals.conv_types;
		this.markets = globals.markets;
		this.map = globals.map;
		this.market_opts = {};

		if (empty(this.conv_types)) {
			return false;
		}
		var i, j, market, mapping, mapping_market;
		for (market in this.conv_types) {
			this.market_opts[market] = Array.clone(this.conv_types[market]);
			this.market_opts[market].push(['', 'NA']);
		}
		this.map = [];
		for (i = 0; i < globals.map.length; ++i) {
			mapping = globals.map[i];
			this.map.push({
				'id':mapping.id,
				'canonical':mapping.canonical,
				'markets':{}
			})
			for (j = 0; j < mapping.conv_type_market.length; ++j) {
				mapping_market = mapping.conv_type_market[j];
				this.map[i].markets[mapping_market.market] = mapping_market.market_name;
			}
		}
		return true;
	}

	,init_actions:function() {
		$('#add_ct').bind('click', this, function(e){ e.data.add_ct_click(); return false; });
	}

	,init_form:function() {
		var i, market,
			$headers = $('#ct_headers')
		;
		// headers
		$headers.append('<th>Wpro</th>');
		for (market in this.conv_types) {
			$headers.append('<th>'+this.markets[market]+'</th>');
		}
		// rows
		for (i = 0; i < this.map.length; ++i) {
			this.add_ct_row(this.map[i]);
		}
	}

	,add_ct_click:function() {
		this.add_ct_row();
	}

	,add_ct_row:function() {
		var
			market,
			mapping = (arguments.length > 0) ? arguments[0] : null,
			ml = '',
			num_rows = this.$tbody.find('tr').length
		;
		for (market in this.conv_types) {
			ml += '<td>'+html.select(market+'_'+num_rows, this.market_opts[market], (mapping && mapping.markets[market]) ? mapping.markets[market] : null)+'</td>';
		}
		this.$tbody.append('\
			<tr class="ct_row">\
				<td>\
					<input type="text" class="canon" name="canon_'+num_rows+'" id="canon_'+num_rows+'" value="'+((mapping) ? mapping.canonical : '')+'" />\
					<input type="hidden" name="cid_'+num_rows+'" id="cid_'+num_rows+'" value="'+((mapping) ? mapping.id : '')+'" />\
				</td>\
				'+ml+'\
				<td><input type="submit" title="Remove Row" alt="Remove Row" class="remove_ct_row" value=" - " /></td>\
			</tr>\
		');
		// focus if a mapping wasn't passed in
		if (mapping === null) {
			this.$tbody.find('tr.ct_row:last-child .canon').focus();
		}
		// action to remove row
		this.$tbody.find('tr.ct_row:last-child .remove_ct_row').bind('click', this, function(e){ e.data.remove_ct_row_click($(this)); return false; });
	}

	,remove_ct_row_click:function($button) {
		var
			$row = $button.closest('tr'),
			canon = $row.find('.canon').val()
		;
		if (confirm('Remove row for '+((canon == '') ? '(empty)' : canon)+'?')) {
			$row.remove();
		}
	}
};

ppc.prototype = {
	select_cols_click:function($a) {
		$('#w_col_options').toggle();
		$a.text(($('#w_col_options').is(':visible')) ? 'Nevermind, do not change columns' : 'Change Columns');
	}
};
