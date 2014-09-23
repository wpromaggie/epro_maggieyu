
function account_product_ql_spend()
{
}

// run after registering so we can get ajaxy
account_product_ql_spend.prototype.post_register = function()
{
	this.init_keywords();
	this.init_metrics_positioning();
	this.init_bids();
	
	$('a:contains("force update")').bind('click', this, function(e){ e.data.cache_force_update($(this)); return false; });
	$('.ag_bid_submit').bind('click', this, function(e){ e.data.ag_bid_submit_click($(this)); return false; });
};

account_product_ql_spend.prototype.init_keywords = function()
{
	var self = this;
	
	$('.w_ag').each(function(){
		self.get_keywords($(this));
	});
};

account_product_ql_spend.prototype.get_keywords = function(w_ag)
{
	var request = {
		market:w_ag.closest('.w_market').attr('market'),
		ag_id:w_ag.attr('ag_id'),
		start_date:$('#start_date').val(),
		end_date:$('#end_date').val()
	};
	if (arguments.length > 1)
	{
		$.extend(request, arguments[1]);
	}
	this.post('get_keyword_details', request);
};

account_product_ql_spend.prototype.init_bids = function()
{
	$('.multi_bid_input').bind('keyup', this, function(e){ e.data.multi_bid_keyup($(this)); });
	$('.detail_submit').bind('click', this, function(e){ e.data.submit_changes_click($(this)); return false; });
};

account_product_ql_spend.prototype.multi_bid_keyup = function(input)
{
	var i, bid_input, new_bid,
		bid_inputs = input.closest('.w_ag').find('.w_keywords .kw_bid_input'),
		change_amount = input.val(),
		change_type = input.attr('t');
	
	for (i = 0; i < bid_inputs.length; ++i)
	{
		bid_input = $(bid_inputs[i]);
		new_bid = this.multi_bid_calc_new(Number(bid_input.attr('original')), change_type, Number(change_amount));
		bid_input.val(new_bid);
	}
};

account_product_ql_spend.prototype.multi_bid_calc_new = function(before, change_type, change_amount)
{
	var after;
	
	switch (change_type)
	{
		case ('percent'):  after = before + (before * change_amount * .01); break;
		case ('delta'):    after = before + (.01 * change_amount); break;
		case ('absolute'): after = (change_amount == 0) ? 'NaN' : (change_amount * .01); break;
	}
	if (isNaN(after))
	{
		after = before;
	}
	return Format.n2(after, 2);
}

account_product_ql_spend.prototype.get_keyword_details_callback = function(request, response)
{
	var ml_options, ml_keywords, ml_totals, ml_deleted, ml_all_totals, i, keyword, kw_class,
		market = request.market,
		wrapper = $('#'+market+'_'+request.ag_id);
	
	// dbg(request, response); return;
	
	keywords = response.keywords;
	if (empty(keywords))
	{
		ml_keywords = '<tr><th colspan=16>No Keyword Data</th></tr>';
		ml_totals = ml_deleted = ml_all_totals = '';
	}
	else
	{
		var clicks = 0,
			cost = 0,
			dclicks = 0,
			dcost = 0;
		ml_keywords = '';
		if (market == 'm')
		{
			keywords = this.m_group_keywords(keywords);
		}
		keywords.sort(account_product_ql_spend.kw_cost_cmp);
		for (i = 0; i < keywords.length; ++i)
		{
			keyword = keywords[i];
			
			if (keyword.status.toLowerCase() == 'deleted') {
				dclicks += Number(keyword.clicks);
				dcost += Number(keyword.cost);
				continue;
			}
			else {
				clicks += Number(keyword.clicks);
				cost += Number(keyword.cost);
			}
			
			if (empty(keyword.bid) || keyword.bid == 0 || keyword.bid == '0.00' || keyword.bid == '0')
			{
				keyword.bid = response.ag_max_cpc;
				kw_class = ' kw_bid_default';
			}
			else
			{
				kw_class = '';
			}
			
			ml_keywords += '\
				<tr kw_id="'+keyword.id+'">\
					<td>'+(i + 1)+'</td>\
					<td>'+keyword.text+'</td>\
					<td original="'+keyword.status+'" status="'+keyword.status+'">\
						<img src="/img/'+keyword.status+'.jpg" />\
					</td>\
					'+this.format_market_kw_info(market, keyword)+'\
					<td>'+keyword.clicks+'</td>\
					<td>'+Format.dollars(keyword.cost)+'</td>\
					<td>'+Format.dollars(keyword.cpc)+'</td>\
					<td>'+Format.n1(keyword.pos, 1)+'</td>\
					<td><input type="text" class="bid_input kw_bid_input'+kw_class+'" original="'+Format.n2(keyword.bid, 2)+'" value="'+Format.n2(keyword.bid, 2)+'" /></td>\
				</tr>';
		}
		// totals rows
		ml_totals = '<td></td><td>Active</td><td></td>'+this.format_market_kw_info(market, null)+'<td>'+clicks+'</td><td>'+Format.dollars(cost)+'</td><td></td>';
		ml_deleted = '<td></td><td>Deleted</td><td></td>'+this.format_market_kw_info(market, null)+'<td>'+dclicks+'</td><td>'+Format.dollars(dcost)+'</td><td></td>';
		ml_all_totals = '<td></td><td>All</td><td></td>'+this.format_market_kw_info(market, null)+'<td>'+(clicks + dclicks)+'</td><td>'+Format.dollars(cost + dcost)+'</td><td></td>';
	}
	
	wrapper.find('.kw_form').html(ml_keywords);
	wrapper.find('.kw_totals').html(ml_totals);
	wrapper.find('.deleted_totals').html(ml_deleted);
	wrapper.find('.all_totals').html(ml_all_totals);
	wrapper.find('td[status]').bind('click', this, function(e){ e.data.kw_status_click($(this)); });
	wrapper.find('input.bid_input').bind('focus', function(){ $(this).select(); })
};

account_product_ql_spend.kw_cost_cmp = function(a, b)
{
	var x = Number(a.cost),
		y = Number(b.cost);
	
	if (x == y)
	{
		return ((a.text > b.text) ? 1 : -1);
	}
	else
	{
		return (y - x);
	}
};

account_product_ql_spend.prototype.kw_status_click = function(cell)
{
	var cur_status = cell.attr("status");
	var new_status = (cur_status == "On") ? "Off" : "On";
	
	cell.attr("status", new_status);
	
	var img = $(cell.find("img"));
	img.attr("src", img.attr("src").replace(cur_status, new_status));
};

account_product_ql_spend.prototype.init_metrics_positioning = function()
{
	this.is_metrics_relative = true;
	this.w_metrics = $('#w_metrics');
	this.w_metrics.attr('top_start', this.w_metrics.offset().top);
	$(window).bind('scroll', this, function(e){ e.data.window_scroll(); });
};

// pixels from top to "pin" metrics data
account_product_ql_spend.prototype.METRICS_OFFSET_TOP = 48;

account_product_ql_spend.prototype.window_scroll = function()
{
	if (this.is_metrics_relative)
	{
		if ($(window).scrollTop() + this.METRICS_OFFSET_TOP > this.w_metrics.attr('top_start'))
		{
			this.is_metrics_relative = false;
			this.w_metrics.css({ position:'fixed', top:this.METRICS_OFFSET_TOP+'px', left:this.w_metrics.offset().left+'px' });
		}
	}
	else
	{
		if ($(window).scrollTop() + this.METRICS_OFFSET_TOP < this.w_metrics.attr('top_start'))
		{
			this.is_metrics_relative = true;
			this.w_metrics.css({ position:'relative', left:'0' });
		}
	}
};

account_product_ql_spend.prototype.format_market_kw_info = function(market, keyword)
{
	var ml_class;
	
	switch (market)
	{
		case ('g'):
			ml_class = (keyword != null && Number(keyword.bid) < Number(keyword.market_info.first_page_cpc)) ? ' class="detail_bid_below_min"' : '';
			return '\
				<td>'+((keyword == null) ? '' : keyword.market_info.quality_score)+'</td>\
				<td'+ml_class+'>'+((keyword == null) ? '' : Format.dollars(keyword.market_info.first_page_cpc))+'</td>\
			';
			
		case ('y'):
			ml_class = (keyword != null && Number(keyword.bid) < Number(keyword.market_info.min_bid)) ? ' class="detail_bid_below_min"' : '';
			return '\
				<td'+ml_class+'>'+((keyword == null) ? '' : Format.dollars(keyword.market_info.min_bid))+'</td>\
			';
	}
	return '';
};

account_product_ql_spend.prototype.m_group_keywords = function(keywords)
{
	var i, broad_id, kw, tmp, grouped;
	
	tmp = {};
	for (i = 0; i < keywords.length; ++i)
	{
		kw = keywords[i];
		broad_id = 'B'+kw.id.substr(1);
		if (tmp[broad_id])
		{
			tmp[broad_id].imps += Number(kw.imps);
			tmp[broad_id].clicks += Number(kw.clicks);
			tmp[broad_id].cost += Number(kw.cost);
			tmp[broad_id].pos += kw.imps * kw.pos;
		}
		else
		{
			tmp[broad_id] = kw;
			tmp[broad_id].id = broad_id;
			tmp[broad_id].imps = Number(kw.imps);
			tmp[broad_id].clicks = Number(kw.clicks);
			tmp[broad_id].cost = Number(kw.cost);
			tmp[broad_id].pos = Number(kw.imps) * kw.pos;
			if ((kw.text.charAt(0) == '"' && kw.text.charAt(kw.text.length - 1) == '"') || (kw.text.charAt(0) == '[' && kw.text.charAt(kw.text.length - 1) == ']'))
			{
				tmp[broad_id].text = kw.text.substr(1, kw.text.length - 2);
			}
		}
	}
	grouped = [];
	for (broad_id in tmp)
	{
		kw = tmp[broad_id];
		kw.pos = (kw.imps) ? kw.pos / kw.imps : 0;
		kw.cpc = (kw.clicks) ? kw.cost / kw.clicks : 0;
		grouped.push(kw);
	}
	grouped.sort(this.kw_sort);
	return grouped;
};

account_product_ql_spend.prototype.submit_changes_click = function(submit)
{
	var container, rows, row, i, new_count, bid_input, status, request;

	container = $(submit.closest('.w_ag'));
	
	request = {
		'market':container.closest('.w_market').attr('market'),
		'ag_id':container.attr('ag_id')
	};
	
	this.get_msg_div('loading', request.market, request.ag_id).show();
	
	rows = $(container.find('.kw_form tr'));
	for (i = 0, new_count = 0; i < rows.length; ++i)
	{
		row = $(rows[i]);
		
		status = $(row.find('td[status]'));
		bid_input = $(row.find('.kw_bid_input'));
		
		// if bid is the same and the status is the same, nothing new to do
		if (bid_input.val() == bid_input.attr('original') && status.attr('status') == status.attr('original')) continue;
		
		request['bid_'+new_count+'_kw_id'] = row.attr('kw_id');
		
		if (bid_input.val() != bid_input.attr('original'))    request['bid_'+new_count+'_amount'] = bid_input.val();
		if (status.attr('status') != status.attr('original')) request['bid_'+new_count+'_status'] = status.attr('status');
		
		++new_count;
	}
	this.post('submit_bid_changes', request);
};

account_product_ql_spend.prototype.get_msg_div = function(msg_type, market, ag_id)
{
	return $('#'+market+'_'+ag_id+' .detail_updates_msg_'+msg_type);
};

account_product_ql_spend.prototype.submit_bid_changes_callback = function(request, response)
{
	var div, table, matches, re, i, index, kw_id, new_bid, new_status, row;
	
	// dbg(request); dbg(response); return;
	
	if (this.handle_ajax_response(request, response))
	{
		// update original input attr for status and bid
		table = $('#'+request.market+'_'+request.ag_id+' .kw_form');
		re = new RegExp('^bid_(\\d+)_kw_id$');
		for (i in request)
		{
			matches = i.match(re);
			if (matches)
			{
				index = matches[1];
				kw_id = request[i];
				row = $(table.find('[kw_id='+kw_id+']'));
				
				new_bid = request['bid_'+index+'_amount'];
				new_status = request['bid_'+index+'_status'];
				
				// get the input elem and set the 'original' amount to the new value
				$(row.find('.kw_bid_input')).attr('original', Format.decimal(new_bid, 2));
				$(row.find('[status]')).attr('original', new_status);
			}
		}
	}
};

account_product_ql_spend.prototype.handle_ajax_response = function(request, response)
{
	var div;
	
	// hide loading div
	div = this.get_msg_div('loading', request.market, request.ag_id);
	div.hide();
	
	// check for errors
	if (ajax_is_error(response))
	{
		div = this.get_msg_div('error', request.market, request.ag_id);
		div.html('Error: '+response.msg.error);
		div.show();
		$fade_out(div, 1500, 1000);
		
		return false;
	}
	// no errors, success!
	else
	{
		div = this.get_msg_div('success', request.market, request.ag_id);
		div.show();
		$fade_out(div, 1500, 1000);
		return true;
	}
};

account_product_ql_spend.prototype.cache_force_update = function(a)
{
	var w_ag = a.closest('.w_ag');
	
	w_ag.find('.kw_form').html('<tr><td colspan="100">LOADING <img src="'+loading_gif()+'" /></td></tr>');
	w_ag.find('.kw_totals').html('');
	this.get_keywords(w_ag, { force_update:true });
};

account_product_ql_spend.prototype.ag_bid_submit_click = function(submit)
{
	var container = $(submit.closest('.w_ag')),
		market = container.closest('.w_market').attr('market'),
		ag_id = container.attr('ag_id'),
		request = {
			'market':market,
			'ag_id':ag_id,
			'new_bid':$('#'+market+'_'+ag_id+'_ag_bid').val()
		};
	
	this.get_msg_div('loading', request.market, request.ag_id).show();
	this.post('submit_ad_group_changes', request);
};

account_product_ql_spend.prototype.submit_ad_group_changes_callback = function(request, response)
{
	this.handle_ajax_response(request, response);
};
