
function ppc_bf()
{
	this.detail = (window.location.get.detail) ? window.location.get.detail : 'market';

	this.data = globals.data;
	this.cols = globals.data_cols;

	this.init_sort();
	this.init_filter_history();
	this.init_filter();
	this.init_bid_buttons();

	this.init_cols();
	this.init_data();
	this.init_table();
	
	// events!
	$('#data_crumbs a').bind('click', this, function(e){ e.data.crumb_click($(this)); return false; });
	$('#date_submit').bind('click', this, function(e){ e.data.update_dates(); return false; });
	$('#run_submit').bind('click', this, function(e){ e.data.run_filter_click(); });
	$('#clear_submit').bind('click', this, function(e){ e.data.clear_click(); return false; });
	$('#cancel_submit').bind('click', this, function(e){ e.data.cancel_click(); return false; });
	$('#change_status_cell img').bind('click', this, function(e){ e.data.meta_status_click($(this)); });
	$('.bid_button').bind('click', this, function(e){ e.data.bid_button_click($(this)); return false; });
	$('.bid_change_input').bind('keyup', this, function(e){ e.data.preview_bid_change($(this)); });
	$('#data_table th a').bind('click', this, function(e){ e.data.data_header_click($(this)); });
	$('#filter_history_toggle').bind('click', this, function(e){ e.data.filter_history_click($(this)); return false; });
	$('#filter_new_toggle').bind('click', this, function(e, do_not_clear){ e.data.filter_new_click(do_not_clear); return false; });

	if (this.detail == 'keyword' || this.is_filter_on()) {
		this.init_keywords();
	}

	// editing filter
	if (window.location.get.faction == 'edit') {
		$('#filter_new_toggle').trigger('click', [true]);
	}
}

ppc_bf.prototype.init_cols = function()
{
	var i, c, format_func;

	for (i = 0; i < this.cols.length; ++i) {
		c = this.cols[i];
		if (globals.formats[c.key]) {
			format_func = globals.formats[c.key];
			// check self
			if (Types.is_function(this['format_'+format_func])) {
				c.format = this['format_'+format_func].bind(this);
			}
			// check global formatter
			else if (Types.is_function(Format[format_func])) {
				c.format = format_func;
			}
		}
	}
};

ppc_bf.prototype.format_status = function(val, other_data)
{
	return ('<img src="/img/'+val+'.jpg" />');
};

ppc_bf.prototype.init_data = function()
{
	var i, d;

	if (!this.data || !this.data.length) {
		this.data = null;
		return;
	}
	for (i = 0; i < this.data.length; ++i) {
		d = this.data[i];
		this.compute_metrics(d);
	}
};

ppc_bf.prototype.compute_metrics = function(d)
{
	// filter calculates position, just add as number at ave_pos key
	if (this.is_filter_on()) {
		d.ave_pos = Number(d.pos);
	}
	else {
		d.ave_pos = (d.imps != 0) ? (d.pos / d.imps) : 0;
	}
	d.cpc = (d.clicks != 0) ? (d.cost / d.clicks) : 0;
	d.roas = (d.cost != 0) ? (d.revenue / d.cost) : 0;
	d.cost_conv = (d.convs > 0)  ? (d.cost / d.convs) : 0;
};

ppc_bf.prototype.init_sort = function()
{
	var i, col,
		default_sort_key = 'clicks',
		default_sort_dir = 'desc';

	this.sort_key = (window.location.get.sk) ? window.location.get.sk : default_sort_key;
	this.sort_dir = (window.location.get.sd) ? window.location.get.sd : default_sort_dir;

	for (i = 0; i < this.cols.length; ++i) {
		col = this.cols[i];
		if (col.key == this.sort_key) {
			break;
		}
	}
	if (i == this.cols.length) {
		this.sort_key = default_sort_key;
		this.sort_dir = default_sort_dir;
	}
};

ppc_bf.prototype.init_filter_history = function()
{
	var i, filter,
		filter_data = [],
		found_running = false,
		found_completed = false;

	for (i = 0; i < globals.filter_history.length; ++i) {
		filter = globals.filter_history[i];
		filter.data_opts = JSON.parse(filter.data_opts);

		// first completed we've come across
		if (!found_completed && Array.in_array(globals.done_stati, filter.status)) {
			found_completed = true;
			this.show_completed_filter(filter);
		}
		// we have a filter that is currently running, show status
		if (!found_running && !Array.in_array(globals.done_stati, filter.status)) {
			found_running = true;
			e2.job_status_widget($('#running_filter'), filter.id, this.running_filter_completed_callback.bind(this));
		}
		// don't add currently running filters to history
		else {
			filter_data.push({
				scheduled:filter.created,
				filter:this.get_filter_text(filter),
				status:filter.status,
				links:this.get_filter_links(filter)
			});
		}
	}
	if (!found_running) {
		$('#running_filter').html('None');
	}
	if (!found_completed) {
		$('#completed_filter').html('None');
	}
	if (filter_data.length > 0) {
		$('#w_filter_history').table({
			cols:[
				{key:'scheduled'},
				{key:'filter'},
				{key:'status'},
				{key:'links',display:''}
			],
			data:filter_data,
			sort_init:'scheduled',
			sort_dir_init:'desc',
			show_totals:false
		});
	}
};

ppc_bf.prototype.running_filter_completed_callback = function(job_id)
{
	this.post('get_completed_job', {jid:job_id});
};

ppc_bf.prototype.get_completed_job_callback = function(request, response)
{
	$('#running_filter .job_status_widget').append('<span> &nbsp;'+this.get_filter_links(response, ['results'])+'</span>');
};

ppc_bf.prototype.show_completed_filter = function(filter)
{
	$('#completed_filter').html(filter.finished+', <i>'+this.get_filter_text(filter)+'</i> ('+this.get_filter_links(filter)+')');
};

ppc_bf.prototype.get_filter_text = function(filter)
{
	var i, j, ands, and, ml_ands,
		ors = []
	;

	if (!filter.data_opts.filter || !Types.is_array(filter.data_opts.filter)) {
		return;
	}

	for (i = 0; i < filter.data_opts.filter.length; ++i) {
		ands = filter.data_opts.filter[i];
		ml_ands = [];
		for (j = 0; j < ands.length; ++j) {
			and = ands[j];
			ml_ands.push(and.col+' '+and.cmp+' '+and.val);
		}
		ors.push(ml_ands.join(' AND '));
	}

	return ors.join(' OR ');
};

ppc_bf.prototype.get_filter_links = function(filter)
{
	var results_params, edit_params, common_params,
		links = [],
		types = (arguments.length > 1) ? arguments[1] : true
	;

	common_params = {
		aid:filter.data_opts.aid,
		fid:filter.id,
		start_date:filter.data_opts.start_date,
		end_date:filter.data_opts.end_date
	};

	if (types === true || Array.in_array(types, 'results')) {
		if (filter.status == 'Completed') {
			results_params = $.extend({
				detail:filter.data_opts.detail,
				faction:'results'
			}, common_params);
			links.push('<a href="'+e2.url('/account/service/ppc/bf?'+String.to_query(results_params))+'">Results</a>');
		}
	}
	if (types === true || Array.in_array(types, 'edit')) {
		edit_params = $.extend({
			detail:'market',
			faction:'edit'
		}, common_params);

		// check for ids
		if (filter.data_opts.market != 'all' && filter.data_opts.market != '*') {
			edit_params.detail = 'campaign';
			edit_params.market = filter.data_opts.market;
		}
		this.set_filter_ids(edit_params, filter);
		links.push('<a href="'+e2.url('/account/service/ppc/bf?'+String.to_query(edit_params))+'">Edit</a>');
	}

	return links.join(' &bull; ');
};

ppc_bf.prototype.set_filter_ids = function(params, filter)
{
	var i, j, detail, ands, and,
		detail_levels = {
			'campaign_id':{count:0,id:''},
			'ad_group_id':{count:0,id:''}
		}
	;

	if (!filter.data_opts.filter || !Types.is_array(filter.data_opts.filter)) {
		return;
	}

	for (i = 0; i < filter.data_opts.filter.length; ++i) {
		ands = filter.data_opts.filter[i];
		for (j = 0; j < ands.length; ++j) {
			and = ands[j];
			for (detail in detail_levels) {
				if (and.col == detail && and.cmp == 'eq') {
					detail_levels[detail].count++;
					detail_levels[detail].id = and.val;
				}
			}
		}
	}

	// ad group set in all the ORs
	if (detail_levels.ad_group_id.count == i) {
		params.detail = 'keyword';
		params.ad_group_id = detail_levels.ad_group_id.id;
	}
	else if (detail_levels.campaign_id.count == i) {
		params.detail = 'ad_group';
		params.campaign_id = detail_levels.campaign_id.id;
	}
};

ppc_bf.prototype.init_filter = function()
{
	var container = $('#filter_container_0_0');
	
	container.html(filter_init('0_0'));

	this.init_filter_markets();
	this.init_filter_campaigns();

	// click default selected
	$('input[name="filter_market"]'+((location.get.market) ? '[value="'+location.get.market+'"]' : ':eq(0)'))[0].click();

	if (!empty(globals.init_filter_data)) {
		filter_init_obj('0_0', globals.init_filter_data);
	}
};

ppc_bf.prototype.init_filter_markets = function()
{
	var market, options = [];

	this.$w_markets = $('#w_markets');
	for (market in globals.markets) {
		options.push([market, globals.markets[market]]);
	}
	if (options.length == 0) {
		return;
	}
	else if (options.length > 1) {
		options.unshift(['all', 'All']);
	}
	this.$w_markets.html(html.radios('filter_market', options, false, {separator:' &nbsp; '}));
	$('input[name="filter_market"]').on('click', this, function(e){ e.data.filter_market_click($(this)); });
};

ppc_bf.prototype.init_filter_campaigns = function()
{
	var market,
		options = [],
		ml = ''
	;

	this.$w_campaigns = $('#w_campaigns');
	for (market in globals.markets) {
		if (!empty(globals.campaigns[market])) {
			ml += '\
				<div class="w_market_campaigns" id="w_'+market+'_campaigns" market="'+market+'">\
					'+html.checkboxes(market+'_filter_campaigns', globals.campaigns[market], [], { toggle_all:1, attrs:'no_star="1"'})+'\
				</div>\
			';
		}
	}
	this.$w_campaigns.html(ml);
	e2.cboxes_init(this.$w_campaigns);

	// check for init data
	if (!empty(globals.init_filter_campaigns)) {
		this.$w_campaigns.find('input[value="'+globals.init_filter_campaigns.join('"],input[value="')+'"]').each(function(){this.click();});
	}
};

ppc_bf.prototype.filter_market_click = function($elem)
{
	var selected = $elem.val();

	// loop over campaign market groups
	this.$w_campaigns.find('.w_market_campaigns').each(function(){
		var $wrapper = $(this);
		if (selected == $wrapper.attr('market')) {
			$wrapper.find('input').attr('disabled', false);
			$wrapper.show();
		}
		// if not market or all was selected, disable and hide
		// todo: allow selecting campaigns from each market
		else {
			$wrapper.find('input').attr('disabled', true);
			$wrapper.hide();
		}
	});
};

ppc_bf.prototype.init_bid_buttons = function()
{
	if (this.detail == 'ad_group') {
		$('#toggle_bids_button').show();
		$('#toggle_cbids_button').hide();
	}
	else if (this.detail == 'keyword') {
		$('#toggle_cbids_button').hide();
	}
	else {
		$('#bid_table').hide();
	}
};


ppc_bf.prototype.init_keywords = function()
{
	this.set_kw_bid_from_ag_if_empty();
	this.shorten_ca_and_ag_text();
	this.refresh_keyword_bids();
};

ppc_bf.prototype.set_kw_bid_from_ag_if_empty = function()
{
	var bid_index, i, d, data_id_parts, market, ag_id;
	
	if (!this.table || !this.table.data) {
		return;
	}
	bid_index = this.get_col_index('bid');
	for (i = 0; i < this.table.data.length; ++i) {
		d = this.table.data[i];
		
		if (d.bid && (d.bid == '0' || d.bid == 0)) {
			data_id_parts = d.uid.split('_');
			market = data_id_parts[0];
			ag_id = data_id_parts[1];
			
			if (window.g_ag_info && g_ag_info[market] && g_ag_info[market][ag_id] && g_ag_info[market][ag_id].max_cpc) {
				d.bid = Format.dollars(g_ag_info[market][ag_id].max_cpc);
				$('#data_table tbody tr:nth-child('+(i + 1)+') td:nth-child('+(bid_index + 1)+')').text(d.bid);
			}
		}
	}
};

ppc_bf.prototype.shorten_ca_and_ag_text = function()
{
	var rows, i, row, data_id, col_indexes, j, col_index, cell;
	
	col_indexes = [this.get_col_index('campaign'), this.get_col_index('ad_group')];
	
	rows = $('#data_table tbody tr');
	for (i = 0; i < rows.length; ++i) {
		row = $(rows[i]);
		
		for (j = 0; j < col_indexes.length; ++j) {
			col_index = col_indexes[j];
			if (isNaN(col_index)) continue;
			
			cell = row.find('td:nth-child('+(col_index + 1)+')');
			if (cell.text().length > 32) cell.html(cell.text().substr(0, 32)+'&hellip;');
		}
	}
};

ppc_bf.prototype.refresh_keyword_bids = function()
{
	var updates, ap_updates, ap_update, rows, i, d, data_id_parts, market, ag_id, ap, ap_settings;
	
	if ($f('detail') != 'keyword' && $f('ag_or_kw') != 'keyword') {
		return;
	}
	return;
	
	updates = {};
	rows = $('#data_table tbody tr');
	for (i = 0; i < rows.length; ++i) {
		data_id_parts = $(rows[i]).attr('rid').split('_');
		market = data_id_parts[0];
		
		if (market != 'g') {
			ag_id = data_id_parts[1];
			if (window.g_ag_info && g_ag_info[market] && g_ag_info[market][ag_id] && !g_ag_info[market][ag_id].do_refresh_cache) {
				continue;
			}
			if (!updates[market+'_'+ag_id]) {
				updates[market+'_'+ag_id] = [];
			}
			updates[market+'_'+ag_id].push(data_id_parts[2]);
		}
	}
	if (empty(updates)) {
		return;
	}
	ap_updates = [];
	for (ag_id in updates) {
		ap_update = {'ag_id':ag_id};
		for (i = 0; i < updates[ag_id].length; ++i) {
			ap_update['kw_id_'+i] = updates[ag_id][i];
		}
		ap_updates.push(ap_update);
	}
	ap_settings = {
		'text':'Getting Bids, Ad Group',
		'on_finish_callback':'data_refresh_keywords_done',
		'common_data':{
			'cl_id':window.location.get.cl_id
		}
	};
	
	//dbg(JSON.stringify(changes));
	//dbg(JSON.stringify(ajaxed));
	
	ap = new Ajax_Parallel(ap_updates, 'ajax_refresh_keywords', ap_settings);
	ap.go();
};

ppc_bf.prototype.get_col_index = function(col)
{
	return $('#data_table thead a[href="'+col+'"]').closest('th').prevAll().length;
};

ppc_bf.prototype.init_table = function()
{
	this.table = $('#data_table').table({
		'data':this.data,
		'cols':this.cols,
		'click_tr':this.row_click.bind(this),
		'id_key':'uid',
		'id_key_tr_attr':'rid',
		'sort_init':this.sort_key,
		'sort_dir_init':this.sort_dir,
		'totals_func':this.compute_metrics.bind(this)
	}).data('table');
};

ppc_bf.prototype.is_filter_on = function()
{
	return ($('#w_filter_results_overview').is(':visible'));
};

ppc_bf.prototype.is_bid_change_on = function()
{
	return ($('#change_container').is(':visible'));
};

ppc_bf.prototype.update_dates = function()
{
	this.update_params(['start_date', 'end_date']);
};

ppc_bf.prototype.data_header_click = function($a)
{
	var col = this.table.get_column($a),
		sort_dir = this.table.sort_dir,
		params = $.extend({}, window.location.get);

	this.sort_key = col.key;
	this.sort_dir = sort_dir;

	params.sk = this.sort_key;
	params.sd = this.sort_dir;

	window.location.replace_path('?'+String.to_query(params));
};

// can either pass in array of field keys, in which case we get value from the form
// or an object, with key/value pairs
ppc_bf.prototype.update_params = function(fields_changed)
{
	var i, field,
		cur_fields = window.location.get,
		updates = {};

	if (Types.is_array(fields_changed)) {
		for (i = 0; i < fields_changed.length; ++i) {
			field = fields_changed[i];
			updates[field] = $f(field);
		}
	}
	else {
		updates = fields_changed;
	}

	// get rid of filter params
	updates.fid = null;
	updates.faction = null;

	// if we are here and filter is on,
	// user submitted form (updated dates?)
	// don't want detail and market to be based on filter
	if (this.is_filter_on()) {
		updates.detail = null;
		updates.market = null;
	}

	for (field in updates) {
		if (updates[field] == null) {
			if (field in cur_fields) {
				delete(cur_fields[field]);
			}
		}
		else {
			cur_fields[field] = updates[field];
		}
	}
	window.location.assign_search(cur_fields);
};

ppc_bf.prototype.clear_click = function()
{
	// uncheck campaigns (before setting market so they are not disabled)
	$('#w_campaigns input[type="checkbox"]:not(.toggle_all):checked').each(function(){ dbg($(this).val()); this.click(); });
	// set market to default
	$('#__filter_market_0__')[0].click();

	filter_reset('0_0');
};

ppc_bf.prototype.row_click = function(e, tr, data)
{
	var i, detail, updates;

	if (this.detail == 'keyword' || this.is_bid_change_on()) {
		return false;
	}
	if (this.detail == 'campaign' && $f('market') == 'f') {
		alert('Campaign lowest level of detail for now for facebook');
		return false;
	}
	updates = {};
	for (i = 0; i < globals.detail_cols.length; ++i) {
		detail = globals.detail_cols[i];
		if (detail == this.detail) {
			updates[this.detail] = this.format_id(data.uid, this.detail);
			updates['detail'] = (globals.detail_cols[i+1] == 'ad') ? 'keyword' : globals.detail_cols[i+1];
			break;
		}
	}
	this.update_params(updates);
};

ppc_bf.prototype.format_id = function(data_id, detail)
{
	switch (detail) {
		// market also includes client id, get rid of that
		case ('market'):
			return data_id.charAt(0);
		case ('campaign'):
		case ('ad_group'):
			// this was put in to remove market.. turns out we need it for some stuff, just return the whole id
			// return data_id.substr(2);
			return data_id;
	}
}

// unset value of all data id's more specific than the crumb that was clicked
ppc_bf.prototype.crumb_click = function($a)
{
	var i, $rows, row_index, detail_key,
		updates = {}
	;
	
	row_index = $a.closest('tr').prevAll().length;
	$rows = $('#data_crumbs tr');
	for (i = row_index + 1; i < $rows.length; ++i) {
		detail_key = $($rows[i]).find('td:first-child').text().simple_text();
		updates[detail_key] = null;
	}
	updates.detail = $('#data_crumbs tr:nth-child('+(row_index + 2)+') td:first-child').text().simple_text();
	this.update_params(updates);
};

ppc_bf.prototype.run_filter_click = function()
{
	$f('filter', JSON.stringify(filter_to_obj('0_0')).replace(/"/g, '&quot;'));
};

ppc_bf.prototype.filter_history_click = function($a)
{
	var $container = $('#w_filter_history');
	if ($container.is(':visible')) {
		$container.hide();
	}
	else {
		$container.show();
	}
};

ppc_bf.prototype.cancel_click = function()
{
	$('#w_filter_new').hide();
};

ppc_bf.prototype.filter_new_click = function(do_not_clear)
{
	$('#w_filter_new').show();
	// default: clear form
	// we also call this to load filter when initing an edit, however
	// so optional param to not clear
	if (!do_not_clear) {
		this.clear_click();
	}
};

ppc_bf.prototype.bid_button_click = function($button)
{
	if ($button.val().indexOf('Cancel') == -1) {
		this.show_bids($button);
	}
	else {
		this.hide_bids($button);
	}
};

// we also store original bids here
ppc_bf.prototype.show_bids = function($button)
{
	var rows, row, cell, bid_index, status_index, cur_bid, ml_bid_input
		bid_type = $button.attr('bid_type');
	
	this.bid_change_counter = false;
	$('#bid_type').val(bid_type);
	
	// update button to toggle to hide bids, show group bid change input
	$button.val('Cancel');
	$button.blur();
	$('#change_container').show();
	
	// get index of bid column
	bid_index = this.get_col_index(bid_type);
	status_index = this.get_col_index('status');

	rows = $('#data_table tbody tr');
	for (i = 0; i < rows.length; ++i) {
		row = $(rows[i]);
		
		// transform bid
		cell = row.find('td:nth-child('+(bid_index + 1)+')');
		cur_bid = $.trim(cell.text()).substr(1);
		ml_bid_input = '<input class="data_bid_input" type="text" value="'+cur_bid+'" style="background-color:#ffffff;" />';
		cell.html('<span class="cur_bid">'+cell.text()+'</span> &rarr; '+ml_bid_input);
		
		// transform status
		cell = row.find('td:nth-child('+(status_index + 1)+')');
		cell.html('<span class="cur_status">'+cell.html()+'</span> &rarr; <span class="new_status">'+cell.html()+'</span>');
	}
	
	$('.change_button_row').show();
	this.show_submit_changes_button();
	$('#data_table tbody span.new_status img').bind('click', this, function(e){ e.stopPropagation(); e.data.status_click($(this)); });
};

ppc_bf.prototype.hide_bids = function($button)
{
	var tbody, row, cell, bid_index, status_index,
		bid_type = $button.attr('bid_type');
	
	// update text of button that was clicked, show the other button
	$button.val('Set'+((bid_type == 'cbid') ? ' Content' : '')+' Bids');
	this.init_bid_buttons();
	
	$button.blur();
	$('#change_container').hide();
	
	// get index of bid column
	bid_index = this.get_col_index(bid_type);
	status_index = this.get_col_index('status');
	
	rows = $('#data_table tbody tr');
	for (i = 0; i < rows.length; ++i) {
		row = $(rows[i]);
		
		// revert bid
		cell = row.find('td:nth-child('+(bid_index + 1)+')');
		cell.html($(cell).find('span.cur_bid').text());
		
		// revert status
		cell = row.find('td:nth-child('+(status_index + 1)+')');
		cell.html($(cell).find('span.cur_status').html());
	}
	$('.change_button_row').hide();
	this.hide_submit_changes_button();
};

ppc_bf.prototype.show_submit_changes_button = function()
{
	var bid_index, i, ml;
	
	if (!$.empty('#data_table tr.bid_changes_button_row')) {
		$('#data_table tr.bid_changes_button_row').show();
	}
	else {
		bid_index = this.get_col_index($f('bid_type'));
		ml = '';
		for (i = 0; i < bid_index; ++i) {
			ml += '<td></td>';
		}
		ml = '<tr class="bid_changes_button_row">'+ml+'<td colspan=20><input type="submit" class="change_button" value="Submit Changes" /></td></tr>';
		$('#data_table thead').prepend(ml);
		$('#data_table tfoot').append(ml);
		$('#data_table input.change_button').bind('click', this, function(e){ e.data.submit_changes_click(); return false; });
	}
};

ppc_bf.prototype.hide_submit_changes_button = function()
{
	$('#data_table tr.bid_changes_button_row').hide();
};

ppc_bf.prototype.status_click = function($img)
{
	var img_src = $img.attr('src');
	if (img_src.indexOf('On') != -1) {
		$img.attr('src', img_src.replace(/On/, 'Off'));
	}
	else {
		$img.attr('src', img_src.replace(/Off/, 'On'));
	}
};

ppc_bf.prototype.meta_status_click = function($img)
{
	$('#data_table tbody span.new_status img').attr('src', $img.attr('src'));
}

ppc_bf.prototype.preview_bid_change = function($input)
{
	var rows, i, j, row, cell, cur_bid, temp_type, input, bid_change, do_use_cur_bid,
		change_type = $input.attr('change_type'),
		bid_change_types = ['percent', 'delta', 'absolute'];

	// set bg color of meta input
	for (i = 0; i < bid_change_types.length; ++i) {
		temp_type = bid_change_types[i];
		$('#'+temp_type+'_bid_change').css('background-color', (change_type == temp_type) ? '#e0ffe0' : '#ffffff');
	}
	bid_change = $('#'+change_type+'_bid_change').val();
	if (isNaN(bid_change)) return;
	do_use_cur_bid = (String(bid_change) == '' && change_type == 'absolute');
	bid_change = Number(bid_change);
	
	$('#bid_change_type').val(change_type);
	$('#bid_change_amount').val(bid_change);
	
	rows = $('#data_table tbody tr');
	for (i = 0; i < rows.length; ++i) {
		row = $(rows[i]);
		cell = row.find('span.cur_bid').closest('td');
		cur_bid = Types.to_number(cell.find('span.cur_bid').text());
		
		// hack to set local to current bid if type is absolute and value is empty
		if (do_use_cur_bid) bid_change = cur_bid;
		
		input = cell.find('input');
		input.css('background-color', '#e0ffe0');
		
		// set the bid based on the type of change
		if (change_type == 'percent') input.val(Format.decimal(cur_bid + (cur_bid * bid_change * .01), 2));
		else if (change_type == 'delta') input.val(Format.decimal(cur_bid + bid_change, 2));
		else input.val(Format.decimal(bid_change, 2));
	}
	if (!this.bid_change_counter) {
		this.bid_change_counter = 0;
	}
	this.bid_change_counter++;
	setTimeout(this.highlight_bid_change.bind(this, 221, this.bid_change_counter), 40);
};

ppc_bf.prototype.highlight_bid_change = function(dec_color, count)
{
	if (dec_color >= 256 || count != this.bid_change_counter) {
		return;
	}
	
	$('#data_table .data_bid_input').each(function(){
		$(this).css('background-color', 'rgb('+dec_color+', 255, '+dec_color+')');
	});
	
	setTimeout(this.highlight_bid_change.bind(this, dec_color + 2, this.bid_change_counter), 80);
};

ppc_bf.prototype.submit_changes_click = function()
{
	var i, rows, row, data_id, bid_input, old_bid, new_bid, status_img, old_status, new_status, tmp, market, ag_id, kw_id,
		changes = {},
		rows = $('#data_table tbody tr')
	;
	
	for (i = 0; i < rows.length; ++i) {
		row = $(rows[i]);
		data_id = row.attr('rid');
		tmp = data_id.split('_');
		market = tmp[0];
		ag_id = tmp[1];
		kw_id = (tmp.length > 2) ? tmp[2] : 'kw_place_holder';
		
		bid_input = row.find('input.data_bid_input');
		status_img = row.find('span.new_status img');
		
		new_bid = bid_input.val();
		old_bid = bid_input.closest('td').find('span').text().substr(1);
		if (new_bid != old_bid) {
			accumulate(changes, [market, ag_id, kw_id], {bid:new_bid,row_id:row.attr('rid')});
		}
		
		new_status = (status_img.attr('src').indexOf('On') != -1) ? 'On' : 'Off';
		old_status = (status_img.closest('td').find('span.cur_status img').attr('src').indexOf('On') != -1) ? 'On' : 'Off';
		if (new_status != old_status) {
			// msn doesn't do pausing/unpausing, just setting bid to zero/non-zero
			// so to turn back on, we need the bid (or set bid to null to use ag bid)
			if (new_status == 'On' && data_id[0] == 'm' && new_bid != 0) {
				accumulate(changes, [market, ag_id, kw_id], {bid:new_bid,row_id:row.attr('rid')});
			}
			accumulate(changes, [market, ag_id, kw_id], {status:new_status,row_id:row.attr('rid')});
		}
	}
	if (empty(changes)) {
		alert('There don\'t appear to be any changes');
		return false;
	}
	else {
		this.post('process_changes', {
			changes:JSON.stringify(changes),
			bid_type:$f('bid_type'),
			ag_or_kw:this.is_filter_on() ? $f('ag_or_kw') : this.detail
		});
		this.changes_progress_box = $.box({
			id:'processing_progress_box',
			title:'Processing Changes',
			content:e2.loading()
		});
	}
};

ppc_bf.prototype.process_changes_callback = function(request, response)
{
	var market, market_changes, ag_id, ag_changes, kw_id, kw_changes, result, $row,
		changes = $.parseJSON(request.changes);

	Feedback.clear();
	this.changes_progress_box.hide();

	if (empty(response) || !Types.is_object(response)) {
		Feedback.add_error_msg('Error: cannot determine success/failure');
		return;
	}
	for (market in changes) {
		market_changes = changes[market];
		for (ag_id in market_changes) {
			if (!response[market] || !response[market][ag_id]) {
				Feedback.add_error_msg('Error: cannot determine success/failure for ad group '+ag_id);
				continue;
			}
			result = response[market][ag_id];
			if (ajax_is_error(result)) {
				Feedback.add_error_msg(result.text);
				continue;
			}
			// success!
			Feedback.add_success_msg(result.text);

			// update kw data
			ag_changes = market_changes[ag_id];
			for (kw_id in ag_changes) {
				kw_changes = ag_changes[kw_id];
				$row = $('#data_table tbody tr[rid='+kw_changes.row_id+']');
				if (kw_changes.bid) {
					this.table.set_row_data($row, 'bid', kw_changes.bid);
					$row.find('span.cur_bid').text(Format.dollars(kw_changes.bid));
				}
				if (kw_changes.status) {
					this.table.set_row_data($row, 'status', kw_changes.status);
					$row.find('span.cur_status').html($row.find('span.new_status').html());
				}
			}
		}
	}
	this.hide_bids($('.bid_button[bid_type="'+$('#bid_type').val()+'"]'));
};

function data_process_filter_response(requests, responses)
{
	var i, response, data, cols;
	
	data = [];
	for (i = 0; i < responses.length; ++i)
	{
		// dbg(requests[i], responses[i]);
		// continue;
		if (!responses[i] || ajax_is_error(responses[i]))
		{
			continue;
		}
		response = responses[i];
		if (i == 0)
		{
			cols = response.cols;
		}
		if (response.data && response.data.length > 0)
		{
			data = data.concat(response.data);
		}
	}
	if (data.length == 0)
	{
		Feedback.add_msg('Filter did not match any results.', 'alert');
	}
	else
	{
		// because we ran a filter, we had to compute ave position - use alternate compute function
		for (i = 0; i < cols.length; ++i)
		{
			if (cols[i].key == 'ave_pos')
			{
				cols[i].calc = 'data_calc_ave_pos_psyche';
				break;
			}
		}
		$('#clear_filter').show();
		$('#bid_table').show();
		$('#toggle_cbids_button')[(($f('ag_or_kw') == 'ad_group') ? 'show' : 'hide')]();
		data_load_table(data, cols);
		data_refresh_keyword_bids();
	}
}

function data_refresh_keywords_done(requests, responses)
{
	var i, request, response, market_and_ag_id, kw_id, row_id, row, bid_index;
	
	bid_index = this.get_col_index('bid');
	Feedback.clear();
	for (i = 0; i < responses.length; ++i)
	{
		request = requests[i];
		response = responses[i];
		
		//dbg(JSON.stringify(request)); dbg(response); continue;
		
		if (!response)
		{
			is_error = true;
			Feedback.add_msg('Unknow Error', 'error');
		}
		else if (ajax_is_error(response))
		{
			is_error = true;
			Feedback.add_msg(response.msg.text, response.msg.type);
		}
		
		market_and_ag_id = request.ag_id.split('_');
		market = market_and_ag_id[0];
		ag_id = market_and_ag_id[1];
		for (j = 0; true; ++j)
		{
			if (!request['kw_id_'+j])
			{
				break;
			}
			kw_id = request['kw_id_'+j];
			if (!response[kw_id])
			{
				continue;
			}
			row_id = market+'_'+ag_id+'_'+kw_id;
			row = $('#data_table tbody tr[rid="'+row_id+'"]');
			this.table.set_row_data(row, 'bid', response[kw_id]);
			row.find('td:nth-child('+(bid_index + 1)+')').text(Format.dollars(response[kw_id]));
		}
	}
}

function data_changes_set_ajax_request_info(request, updates)
{
	var i, kw_id, kw_updates, key;
	
	i = 0;
	for (kw_id in updates)
	{
		request['kw_id_'+i] = kw_id;
		kw_updates = updates[kw_id];
		for (key in kw_updates)
		{
			request['kw_'+key+'_'+i] = kw_updates[key];
		}
		++i;
	}
}

function data_keyword_refresh_info_setter(request, updates)
{
	var i;
	
	for (i = 0; i < updates.length; ++i)
	{
		request['kw_id_'+i] = updates[i];
	}
}

function data_keyword_info_callback(request, response)
{
	var market, ag_id, kw_id, row, bid_index;
	
	if (!typeof(response) == 'object') return;
	bid_index = this.get_col_index('bid');
	for (kw_id in response)
	{
		row = $('#data_body tr[data_id="'+request.market+'_'+request.ag_id+'_'+kw_id+'"]');
		row.find('td:nth-child('+(bid_index + 1)+')').html(Format.dollars(response[kw_id]));
	}
}
