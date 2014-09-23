
function account_product_ql_markets()
{
	// we set market for user, reflect in url
	if (globals.do_set_market)
	{
		window.location.replace_path(window.location.search+'&m='+globals.do_set_market);
	}
	$('.pull_new_ad_groups_link').bind('click', this, function(e){ e.data.pull_ags_click(); return false; });
}

account_product_ql_markets.prototype.copy_multi_line = function(jqo)
{
	var box, first = jqo.filter(':first'),
		offset = first.offset();
	
	$('#multi_line_copy').remove();
	box = $.box({
		title:'Multi-Line Copy',
		close:true,
		x:offset.left,
		y:offset.top,
		id:'multi_line_copy',
		content:'<textarea class="multi_line_copy">'+jqo.map(function(){ return $(this).val(); }).get().join("\n")+'</textarea>'
	});
	box.find('.multi_line_copy').select();
};

account_product_ql_markets.prototype.pull_ags_click = function()
{
	e2.action_submit('action_pull_new_ags');
};

function data_source()
{	
	this.market = this.attr('market');
	this.have_data_source_info_selects_been_loaded = false;
	
	// google add account link
	// we do this here instead of phps because php does not know market at
	// time of ml creation
	if (this.market == 'g')
	{
		this.find('td[entity_type="ac"] a.refresh_link').after(' &nbsp; <a href="" class="add_google_account_link">Add Google Account</a>');
	}
	
	// ui stuff
	this.find('.data_source_info span.choose span.loading').hide();
	this.find('.data_source_info span.choose').hide();
	this.find('.set_data_source_submit_buttons').hide();
	
	// events
	this.find('.set_data_source_link').bind('click', this, function(e){ e.data.set_data_source_click($(this)); return false; });
	this.find('.un_tie_link').bind('click', this, function(e){ e.data.un_tie_click(e); return false; });
	this.find('.re_tie_ads_link').bind('click', this, function(e){ e.data.re_tie_ads_click($(this)); return false; });
	this.find('a.refresh_link').bind('click', this, function(e){ e.data.refresh_click($(this)); return false; });
	this.find('a.add_google_account_link').bind('click', this, function(e){ e.data.add_google_account_click(e); return false; });
	this.find('[name="set_data_source_submit"]').bind('click', this, function(e){ return e.data.set_data_source_submit(); });
	this.find('[name="set_data_source_cancel"]').bind('click', this, function(e){ e.data.set_data_source_cancel(); return false; });
	
	if (globals.account_refresh_market == this.market)
	{
		this.set_data_source_click(this.find('.set_data_source_link'));
	}
}

data_source.prototype.re_tie_ads_click = function(a)
{
	e2.action_submit('action_re_tie_ads');
};

data_source.prototype.un_tie_click = function(e)
{
	var untie_clear_su_key = 'untie_clear_su_'+this.market,
		b = $.box({
		id:'untie_box_'+this.market,
		title:'Untie Confirmation',
		event:e,
		close:true,
		content:'\
			<p>\
				<input type="checkbox" name="'+untie_clear_su_key+'" id="'+untie_clear_su_key+'"'+((this.market == 'g') ? ' checked' : '')+' />\
				<label for="'+untie_clear_su_key+'">Also Clear SU</label>\
			</p>\
			<input type="submit" a0="action_un_tie_market" value="Do it" />\
		'
	});
	$f('un_tie_market', this.market);
	e2.auto_action_submit(b);
};

data_source.prototype.add_google_account_click = function(e)
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

data_source.prototype.set_data_source_click = function(a)
{
	a.blur();
	
	this.find('.data_source_info span.display').hide();
	this.find('.data_source_info span.choose').show();
	this.find('.set_data_source_submit_buttons').show();
	
	if (!this.have_data_source_info_selects_been_loaded)
	{
		this.load_data_source_info_selects();
	}
};

data_source.prototype.refresh_click = function(a)
{
	var request, tr, td, type, parent_id;
	
	a.blur();
	
	td = a.closest('td');
	tr = td.closest('tr');
	type = td.attr('entity_type');
	
	if (type == 'ac')
	{
		$f('market', this.market);
		e2.action_submit('action_refresh_accounts');
		return;
	}
	
	parent_id = ((type == 'ac') ? '' : tr.prev().find('select').val());
	
	// refresh clicked but no parent selected -> we don't know what to refresh
	if (type != 'ac' && !parent_id)
	{
		return;
	}
	request = {
		'market':this.market,
		'type':type,
		'ac_id':$('#'+this.market+'_new_account').val(),
		'parent_id':parent_id
	};
	td.find('span.loading').show();
	this.post('refresh_entity', request);
};

data_source.prototype.set_data_source_cancel = function()
{
	this.find('span.display').show();
	this.find('span.choose').hide();
	this.find('.set_data_source_submit_buttons').hide();
};

data_source.prototype.set_data_source_submit = function()
{
	if (!$f(this.market+'_new_account'))
	{
		alert('Please select an account');
		return false;
	}
	$f('market', this.market);
	return true;
};

data_source.prototype.load_data_source_info_selects = function()
{
	this.have_data_source_info_selects_been_loaded = true;
	this.get_accounts();
};

data_source.prototype.get_accounts = function()
{
	var td;
	
	td = this.find('.data_source_info td[entity_type="ac"]');
	td.find('span.loading').show();
	
	this.post('get_accounts', {market:this.market});
};

data_source.prototype.show_entity_select = function(td, type, data)
{
	if (!data) {
		data = [];
	}
	data.unshift(['', ' - Select - ']);
	
	td.find('span.loading').hide();
	td.find('span._select').html(html.select(this.market+'_new_'+type, data, td.attr('entity_id')));
	td.find('span._select select').bind('change', this, function(e){ e.data.set_data_source_new_entity_change($(this)); });
};

data_source.prototype.set_data_source_new_entity_change = function(select)
{
	var val, td;
	
	val = select.val();
	if (val)
	{
		td = select.closest('td');
		switch (td.attr('entity_type'))
		{
			case ('ac'):
				this.get_child_entities('ac', 'account', 'ca', 'campaign');
				break;
			
			case ('ca'):
				this.get_child_entities('ca', 'campaign', 'ag', 'ad_group');
				break;
			
			case ('ag'):
				// nothing to do
				break;
		}
	}
};

data_source.prototype.get_child_entities = function(parent_type, parent_type_full, child_type, child_type_full)
{
	var request;
	
	request = {
		'market':this.market,
		'ac_id':$('#'+this.market+'_new_account').val()
	};
	request[parent_type+'_id'] = $f(this.market+'_new_'+parent_type_full);
	this.find('td[entity_type="'+child_type+'"] span.loading').show();
	this.post('get_'+child_type_full+'s', request);
};

data_source.prototype.get_accounts_callback = function(request, response)
{
	var td;
	
	td = this.find('td[entity_type="ac"]');
	this.show_entity_select(td, 'account', response);
	
	// if we have an account, get campaigns
	if (td.attr('entity_id'))
	{
		this.get_child_entities('ac', 'account', 'ca', 'campaign');
	}
}

data_source.prototype.get_campaigns_callback = function(request, response)
{
	var td = this.find('td[entity_type="ca"]');
	this.show_entity_select(td, 'campaign', response);
	
	// if we have a campaign, get ad groups
	if (td.attr('entity_id'))
	{
		this.get_child_entities('ca', 'campaign', 'ag', 'ad_group');
	}
};

data_source.prototype.get_ad_groups_callback = function(request, response)
{
	var td;
	
	td = this.find('td[entity_type="ag"]');
	this.show_entity_select(td, 'ad_group', response);
};

data_source.prototype.refresh_entity_callback = function(request, response)
{
	var td, type_full;

	type_full = (request.type == 'ac') ? 'account' : (request.type == 'ca') ? 'campaign' : 'ad_group';
	td = this.find('td[entity_type="'+request.type+'"]');
	this.show_entity_select(td, type_full, response);
};

function ad_group()
{
	this.find('.refresh_ads_link').bind('click', this, function(e){ e.data.refresh_ads_click($(this)); return false; });
	this.find('.refresh_keywords_link').bind('click', this, function(e){ e.data.refresh_keywords_click($(this)); return false; });
}

ad_group.prototype.refresh_ads_click = function(a)
{
	$('#ag_id').val(this.attr('ag_id'));
	e2.action_submit('action_refresh_ads');
};

ad_group.prototype.refresh_keywords_click = function(a)
{
	$('#ag_id').val(this.attr('ag_id'));
	e2.action_submit('action_refresh_keywords');
};

function ad()
{
	// id of the wrapper.. prefix w_
	this.id = this.attr('id');
	this.ql_ad_id = this.attr('ql_ad_id');
	this.ag_id = this.attr('ag_id');
	this.ad_id = this.attr('ad_id');
	this.uid = this.ag_id+'_'+this.ad_id;
	
	this.find('.ad_submit').bind('click', this, function(e){ e.data.submit_changes(); });
	this.find('.copy_from_link').bind('click', this, function(e){ e.data.copy_from_click(e, $(this)); return false; });
	this.find('.multi_line_popup_link').bind('click', this, function(e){ e.data.multi_line_popup_click($(this)); return false; });
}

ad.prototype.submit_changes = function()
{
	$('#ql_ad_id').val(this.ql_ad_id);
	$('#ag_id').val(this.ag_id);
	$('#ad_id').val(this.ad_id);
	e2.action_submit('action_update_ad_submit');
};

ad.prototype.multi_line_popup_click = function(a)
{
	a.blur();
	modules.account_product_ql_markets.copy_multi_line(this.find('input[type="text"]'));
};

ad.prototype.copy_from_click = function(e, a)
{
	var i, ad, box,
		ml = '';
	
	for (i = 0; i < globals.ads.length; ++i)
	{
		ad = globals.ads[i];
		if (!(this.ag_id == ad.ad_group && this.ad_id == ad.ad && window.location.get['m'] == ad.market))
		{
			ml += '<p><a class="copy_from_ad_link" href="" market="'+ad.market+'" ag_id="'+ad.ad_group_id+'" ad_id="'+ad.id+'">'+globals.markets[ad.market]+' - '+ad.text+'</a></p>';
		}
	}
	box = $.box({
		id:'ad_copy_from_'+this.ag_id+'_'+this.ad_id,
		title:'Copy From',
		close:true,
		event:e,
		content:ml
	});
	box.find('.copy_from_ad_link').bind('click', this, function(e){ e.data.copy_from_box_click($(this)); return false; });
};

ad.prototype.copy_from_box_click = function(a)
{
	var src_ad, data = {
		market:a.attr('market'),
		ag_id:a.attr('ag_id'),
		ad_id:a.attr('ad_id')
	};
	$('#ad_copy_from_'+this.ag_id+'_'+this.ad_id).remove();
	
	if (data.market == window.location.get['m'])
	{
		src_ad = e2.get_ejo('ad', function(test_ejo){ return (test_ejo.id == 'w_ad_'+data.ag_id+'_'+data.ad_id); });
		this.set_title(src_ad.find('.title').val());
		this.set_description(src_ad.get_description());
		this.find('.disp_url').val(src_ad.find('.disp_url').val());
		this.find('.dest_url').val(src_ad.find('.dest_url').val());
	}
	else
	{
		this.post('copy_from_get_ad', data);
	}
};

ad.prototype.copy_from_get_ad_callback = function(request, response)
{
	this.set_title(response.text);
	this.set_description([response.desc_1, response.desc_2]);
	this.find('.disp_url').val(response.disp_url);
	this.find('.dest_url').val(response.dest_url);
};

ad.prototype.set_title = function(new_title_text)
{
	var matches,
		title = this.find('.title');
	
	if (arguments.length > 1 && arguments[1].check_for_keyword_sub)
	{
		matches = title.val().match(/{(keyword):\s*(.*)}/i);
		if (matches)
		{
			new_title_text = '{'+matches[1]+':'+new_title_text+'}';
		}
	}
	title.val(new_title_text);
	title.keyup();
};

ad.prototype.set_description = function(description)
{
	switch (window.location.get['m'])
	{
		case ('g'):
			$('#'+this.uid+'_desc_1').val(description[0]).keyup();
			$('#'+this.uid+'_desc_2').val(description[1]).keyup();
			break;
			
		case ('m'):
			$('#'+this.uid+'_desc_1').val(description[0]+' '+description[1]).keyup();
			break;
	}
};

ad.prototype.get_description = function()
{
	switch (window.location.get['m'])
	{
		case ('g'): return [$('#'+this.uid+'_desc_1').val(), $('#'+this.uid+'_desc_2').val()];
		case ('m'): return this.split_description($('#'+this.uid+'_desc_1').val());
	}
};

ad.prototype.split_description = function(description)
{
	var len, mid, index, delta, dir, dirs, char;
	
	dirs = [-1, 1];
	len = description.length;
	for (mid = Math.floor(len / 2), delta = 0; (delta * 2) < len; ++delta)
	{
		for (dir in dirs)
		{
			index = mid + (delta * dirs[dir]);
			char = description.charAt(index);
			if (char == ' ')
			{
				return [description.substr(0, index), description.substr(index + 1)];
			}
		}
	} 
	return [description, ''];
};

function keywords()
{
	this.ag_id = this.attr('ag_id');
	
	// events
	this.find('.copy_from_link').bind('click', this, function(e){ e.data.copy_from_click(e, $(this)); return false; });
	this.find('.multi_line_popup_link').bind('click', this, function(e){ e.data.multi_line_popup_click($(this)); return false; });
	this.find('.toggle_modified_broad_link').bind('click', this, function(e){ e.data.toggle_modified_broad_click($(this)); return false; });
	this.find('input[type="submit"][value="Preview Changes"]').bind('click', this, function(e){ e.data.preview_changes_click($(this)); return false; });
	this.find('input[a0="action_update_keywords_submit"]').bind('click', this, function(e){ e.data.submit_changes_click($(this)); });
	this.find('.keyword_preview_changes input[type="submit"][value="Cancel"]').bind('click', this, function(e){ e.data.cancel_changes_click($(this)); return false; });
	
	// add start value attribute for all keywords
	this.find('.keyword').each(function(){
		var elem = $(this);
		elem.attr('start_value', elem.val());
	});
}

keywords.prototype.preview_changes_click = function(button)
{
	var kw, kw_new, kw_keep, kw_delete, cur, start;
	
	button.blur();
	
	// get and empty lists
	kw_new = this.find('.list.new ul');
	kw_keep = this.find('.list.keep ul');
	kw_delete = this.find('.list.delete ul');
	
	// empty markup lists
	kw_new.empty();
	kw_keep.empty();
	kw_delete.empty();
	
	start = {};
	cur = {};
	
	this.find('.keyword').each(function(){
		var elem = $(this);
		
		if (elem.val()) cur[elem.val()] = 1;
		if (elem.attr('start_value')) start[elem.attr('start_value')] = 1;
	});
	
	for (kw in start)
	{
		if (cur[kw])
		{
			this.add_to_list(kw_keep, kw);
			delete(cur[kw]);
		}
		else
		{
			this.add_to_list(kw_delete, kw);
		}
	}
	// everything left in cur is a new kw
	for (kw in cur)
	{
		this.add_to_list(kw_new, kw);
	}
	
	this.find('.keyword_preview_changes').show();
};

keywords.prototype.add_to_list = function(list, kw)
{
	list.append('<li>'+kw+'</li>');
};

keywords.prototype.submit_changes_click = function(button)
{
	$f('ag_id', this.ag_id);
	$f('kw_new', this.find('.list.new ul li').map(function(){ return $(this).text(); }).get().join("\t"));
	$f('kw_delete', this.find('.list.delete ul li').map(function(){ return $(this).text(); }).get().join("\t"));
};

keywords.prototype.cancel_changes_click = function(button)
{
	button.blur();
	this.find('.keyword_preview_changes').hide();
};

keywords.prototype.refresh_keywords_click = function(a)
{
	$f('market', this.market);
	e2.action_submit('action_refresh_keywords');
};

keywords.prototype.multi_line_popup_click = function(a)
{
	a.blur();
	modules.account_product_ql_markets.copy_multi_line(this.find('input[type="text"]'));
};

keywords.prototype.copy_from_click = function(e, a)
{
	var i, ag, box,
		ml = '';
	
	for (i = 0; i < globals.ad_groups.length; ++i)
	{
		ag = globals.ad_groups[i];
		if (this.ag_id != ag.id)
		{
			ml += '<p><a class="copy_from_ag_link" href="" market="'+ag.market+'" ag_id="'+ag.id+'">'+globals.markets[ag.market]+' - '+ag.text+'</a></p>';
		}
	}
	box = $.box({
		id:'kw_copy_from_'+this.ag_id,
		title:'Copy From',
		close:true,
		event:e,
		content:ml
	});
	box.find('.copy_from_ag_link').bind('click', this, function(e){ e.data.copy_from_ag_click($(this)); return false; });
};

keywords.prototype.copy_from_ag_click = function(a)
{
	var kws, data = {
		market:a.attr('market'),
		ag_id:a.attr('ag_id')
	};
	$('#kw_copy_from_'+this.ag_id).remove();
	
	if (data.market == window.location.get['m'])
	{
		kws = $('.w_keywords[ag_id="'+data.ag_id+'"] input[type="text"]').map(function(){ return $(this).val(); }).get();
		this.set_keywords(kws);
	}
	else
	{
		this.post('copy_from_get_kws', data);
	}
};

keywords.prototype.copy_from_get_kws_callback = function(request, response)
{
	this.set_keywords(response);
};

keywords.prototype.set_keywords = function(src_keywords)
{
	var num_dst_keywords, i, ml_more_kws;
	
	// if more source keywords than destination keywords, add aditional keyword inputs
	num_dst_keywords = this.find('input[type="text"]').length;
	ml_more_kws = '';
	for (i = this.find('input[type="text"]').length; i < src_keywords.length; ++i)
	{
		ml_more_kws += '\
			<tr>\
				<td class="r">'+(i + 1)+'</td>\
				<td><input type="text" class="keyword" name="'+this.ag_id+'_keyword_'+i+'" id="'+this.ag_id+'_keyword_'+i+'" value="" /></td>\
			</tr>\
		';
	}
	// last row is submit button, insert new kws before that
	if (ml_more_kws)
	{
		this.find('input[type="submit"]').closest('tr').before(ml_more_kws);
		num_dst_keywords = src_keywords.length;
	}
	
	for (i = 0; i < num_dst_keywords; ++i)
	{
		$('#'+this.ag_id+'_keyword_'+i).val((i < src_keywords.length) ? src_keywords[i] : '');
	}
};

keywords.prototype.toggle_modified_broad_click = function(a)
{
	var num_keywords, num_mb, i, kw, kw_text, convert_to_mb;
	
	num_mb = num_non_empty = 0;
	for (i = 0, num_keywords = this.find('input[type="text"]').length; i < num_keywords; ++i)
	{
		kw_text = $('#'+this.ag_id+'_keyword_'+i).val().trim();
		if (kw_text != '')
		{
			num_non_empty++;
			if (kw_text.indexOf('+') != -1)
			{
				num_mb++;
			}
		}
	}
	convert_to_mb = (i > 0 && num_non_empty > 0 && (num_mb / num_non_empty) < .5);
	
	for (i = 0; i < num_keywords; ++i)
	{
		kw = $('#'+this.ag_id+'_keyword_'+i);
		kw_text = kw.val()
		if (convert_to_mb)
		{
			kw_text = kw_text.replace(/(^| )(\w)/g, '$1+$2');
		}
		else
		{
			kw_text = kw_text.replace(/\+/g, '');
		}
		if (kw.val() != kw_text)
		{
			kw.val(kw_text);
		}
	}
};

account_product_ql_markets.prototype.show_user_update = function(data)
{
	this.show_user_update_ad(data);
	this.show_user_update_keywords(data);
};

account_product_ql_markets.prototype.show_user_update_ad = function(data)
{
	$('#title_update a').text(data.title);
	if ($f('market') == 'g')
	{
		$('#desc_1_update a').text(data.desc_1);
		$('#desc_2_update a').text(data.desc_2);
	}
	else
	{
		$('#desc_1_update a').text(data.desc_1+' '+data.desc_2);
	}
	$('#disp_url_update a').text(data.url);
	$('#dest_url_update a').text(data.url);
};

account_product_ql_markets.prototype.ad_copy_ninja_click = function(a)
{
	a.blur();
	
	a.closest('tr').find('input[type="text"]').val(a.text());
};

account_product_ql_markets.prototype.ad_copy_all_click = function()
{
	$f('title', $('#title_update').text());
	$f('desc_1', $('#desc_1_update').text());
	if (!$.empty('#desc_2_update')) $f('desc_2', $('#desc_2_update').text());
	$f('disp_url', $('#disp_url_update').text());
	$f('dest_url', $('#dest_url_update').text());
};

account_product_ql_markets.prototype.show_user_update_keywords = function(data)
{
	alert('here');
	var rows, row, cur_keywords, user_keywords, dup_keywords, new_keywords, i, kw, is_init;
	
	if ($('#keyword_table thead th:contains("New Keywords")').length > 0)
	{
		is_init = false;
	}
	else
	{
		is_init = true;
		$('#keyword_table thead tr').append('<th>New Keywords</th>');
		$('#keyword_table thead tr').append('<th>Duplicate Keywords</th>');
	}
	
	rows = $('#keyword_table tbody tr');
	user_keywords = data.keywords.split("\t");
	cur_keywords = rows.map(function(){
		var kw = $(this).find('td:nth-child(2) input').val();
		var len = kw.length;
		switch (kw.length)
		{
			case (0):
				return;
			
			case (1):
				return kw;
			
			default:
				if ((kw.charAt(0) == '"' && kw.charAt(kw.length - 1) == '"') || (kw.charAt(0) == '[' && kw.charAt(kw.length - 1) == ']'))
				{
					return kw.substr(1, kw.length - 2);
				}
				else
				{
					return kw;
				}
		}
	}).get();
	
	dup_keywords = [];
	new_keywords = [];
	for (i = 0; i < user_keywords.length; ++i)
	{
		kw = user_keywords[i];
		if (Array.in_array(cur_keywords, kw))
		{
			dup_keywords.push(kw);
		}
		else
		{
			new_keywords.push(kw);
		}
	}
	for (i = 0; i < rows.length - 1; ++i)
	{
		if (i >= new_keywords.length && i >= dup_keywords.length)
		{
			break;
		}
		row = $(rows[i]);
		if (is_init)
		{
			row.append('<td class="new_keyword"></td><td class="dup_keyword"></td>');
		}
		if (i < new_keywords.length)
		{
			row.find('td.new_keyword').text(new_keywords[i]);
		}
		if (i < dup_keywords.length)
		{
			row.find('td.dup_keyword').text(dup_keywords[i]);
		}
	}
};

/*
 * class: client_updates
 */
function client_updates()
{
}

client_updates.prototype.init = function(_type)
{
	this.type = _type;
	this.updates = [];
	this.display_container = null;
	this.cur_update = null;
	
	this.init_updates();
};

client_updates.prototype.init_display_container = function()
{
	this.display_container = $('#w_update_details');
	this.display_container.html('\
		<h2>Client Update</h2>\
		<table>\
			<tbody class="update_tbody">\
			</tbody>\
		</table>\
		<div class="update_buttons">\
			'+this['ml_copy_to_buttons_'+this.type]()+'\
			<div>\
				<input type="submit" class="done_button" value="Update Done" />\
				<input type="submit" class="close_button" value="Close" />\
			</div>\
		</div>\
	');
	this.display_container.find('.done_button').bind('click', this, function(e){ e.data.update_done_click($(this)); return false; });
	this.display_container.find('.copy_button').bind('click', this, function(e){ e.data.update_copy_click($(this)); return false; });
	this.display_container.find('.close_button').bind('click', this, function(e){ e.data.update_close_click(); return false; });
};

client_updates.prototype.ml_copy_to_buttons_ad = function()
{
	var i, j, ag, ads, ad, classes,
		ml = '',
		update_ad_id = (this.cur_update) ? this.cur_update.data.ql_ad_id : false,
		ags = $('#w_ad_groups .w_ag');
	
	for (i = 0; i < ags.length; ++i)
	{
		ag = $(ags[i]);
		ads = ag.find('.w_ad');
		for (j = 0; j < ads.length; ++j)
		{
			ad = $(ads[j]);
			if (ad.attr('ad_id') != 'new')
			{
				classes = ['copy_button'];
				if (ad.attr('ql_ad_id') == update_ad_id)
				{
					classes.push('highlighted');
				}
				ml += '<div><input type="submit" uid="'+ad.attr('id')+'" class="'+classes.join(' ')+'" value="Copy to '+ag.find(' > legend').text()+', Ad #'+(j + 1)+'" /></div>';
			}
		}
	}
	return ml;
};

client_updates.prototype.ml_copy_to_buttons_keywords = function()
{
	var i, j, ag,
		ml = '',
		ags = $('#w_ad_groups .w_ag');
	
	for (i = 0; i < ags.length; ++i)
	{
		ag = $(ags[i]);
		ml += '<div><input type="submit" ag_id="'+ag.attr('ag_id')+'" class="copy_button" value="Copy to '+ag.find(' > legend').text()+'" /></div>';
	}
	return ml;
};

client_updates.prototype.update_done_click = function(button)
{
	this.display_container.append('<div class="update_msg">'+e2.loading()+'</div>');
	this.post('update_done', {
		update_id:this.cur_update.id
	});
};

client_updates.prototype.update_done_callback = function(request, response)
{
	var msg, self;

	self = this;
	msg = this.display_container.find('.update_msg');
	msg.html(response);
	msg.fadeOut(1500, function(){
		$(this).remove();
		self.cur_update = null;
		self.display_container.hide();
	});
};

client_updates.prototype.update_close_click = function()
{
	this.cur_update = null;
	this.display_container.hide();
};

client_updates.prototype.init_updates = function()
{
	var i, update;
	
	for (i = 0; i < globals.updates.length; ++i)
	{
		update = globals.updates[i];
		if (update.type == 'ql-'+this.type)
		{
			update.data = this.init_update_data(update.data);
			this.updates.push(update);
		}
	}
	this.init_updates_select();
};

client_updates.prototype.init_updates_select = function()
{
	var i, update, ml, options, is_any_new, option_new_text;
	
	if (this.updates.length == 0)
	{
		ml = 'No Updates';
	}
	else
	{
		options = [];
		is_any_new = false;
		for (i = 0; i < this.updates.length; ++i)
		{
			update = this.updates[i];
			if (update.users_id == 0)
			{
				is_any_new = true;
				option_new_text = ' (New)';
			}
			else
			{
				option_new_text = '';
			}
			options.push([i, update.dt+option_new_text]);
		}
		options.unshift(['', ' - Select - '+((is_any_new) ? ' ***' : '')]);
		ml = html.select('client_update_'+this.type, options);
		ml += '&nbsp; <input type="submit" class="load" value="Load" />';
	}
	this.find('._updates').html(ml);
	this.find('._updates select').bind('change', this, function(e){ e.data.client_update_change($(this)); });
	this.find('._updates input.load').bind('click', this, function(e){ e.data.load_update_click($(this)); return false; });
};

client_updates.prototype.client_update_change = function(select)
{
	if (select.val())
	{
		this.display_update(this.updates[select.val()]);
	}
};

client_updates.prototype.load_update_click = function(input)
{
	input.blur();
	this.display_update(this.updates[this.find('._updates select').val()]);
};

client_updates.prototype.display_update = function(update)
{
	this.cur_update = update;
	if (!this.display_container)
	{
		this.init_display_container();
	}
	this.display_container.find('.update_tbody').html(this.ml_update_details(update));
	
	// show/hide done button if update has been processed
	this.display_container.find('.done_button')[(update.users_id == 0) ? 'show' : 'hide']();
	this.display_container.show();
};

/*
 * class: client_updates_ad
 */
function client_updates_ad()
{
	$.extend(this, new client_updates());
	this.init('ad');
}

client_updates_ad.prototype.init_update_data = function(str)
{
	return eval('('+str+')');
};

client_updates_ad.prototype.ml_update_details = function(update)
{
	return '\
		<tr>\
			<td>Title</td>\
			<td>'+update.data.text+'</td>\
		</tr>\
		<tr>\
			<td>Desc 1</td>\
			<td>'+update.data.desc_1+'</td>\
		</tr>\
		<tr>\
			<td>Desc 2</td>\
			<td>'+update.data.desc_2+'</td>\
		</tr>\
	';
};

client_updates_ad.prototype.update_copy_click = function(button)
{
	var uid, ejo;
	
	uid = button.attr('uid');
	ejo = e2.get_ejo('ad', function(test_ejo){ return (test_ejo.id == uid); });
	ejo.set_title(this.cur_update.data.text, {check_for_keyword_sub:true});
	ejo.set_description([this.cur_update.data.desc_1, this.cur_update.data.desc_2]);
};

/*
 * class: client_updates_keywords
 */
function client_updates_keywords()
{
	$.extend(this, new client_updates());
	this.init('keywords');
}

client_updates_keywords.prototype.init_update_data = function(str)
{
	var tmp = eval('('+str+')');
	tmp.keywords = tmp.keywords.split("\t");
	return tmp;
};

client_updates_keywords.prototype.ml_update_details = function(update)
{
	var ml, i;
	
	ml = '';
	for (i = 0; i < update.data.keywords.length; ++i)
	{
		ml += '\
			<tr>\
				<td>Keyword '+(i + 1)+'</td>\
				<td>'+update.data.keywords[i]+'</td>\
			</tr>\
		';
	}
	return ml;
};

client_updates_keywords.prototype.update_copy_click = function(button)
{
	var ag_id, ejo;
	
	ag_id = button.attr('ag_id');
	ejo = e2.get_ejo('keywords', function(test_ejo){ return (test_ejo.ag_id == ag_id); });
	ejo.set_keywords(this.cur_update.data.keywords);
};
