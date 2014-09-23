
function table(report, sheet)  
{
	var table_element,
		opts = (arguments.length > 2) ? arguments[2] : {},
		do_fade = (sheet.tables.length > 0 && !opts.is_load)
	;
	this.report = report;
	this.sheet = sheet;
	
	table_element = this.create_table();
	
	if (do_fade) {
		table_element.hide();
	}
	this.sheet.jElement.append(table_element);
	this.jElement = table_element;
	if (do_fade) {
		table_element.fadeIn(800);
	}

	this.init_dates();
	e2.cboxes_init($('#campaigns_container_'+this.key));

	if (!opts.is_load) {
		this.check_detail_cols($('select[name="detail_'+this.key+'"]').get(0));
		this.display_type_change($('select[name="display_type_'+this.key+'"]'));
	}
}

/*
 * gets the table key from the name/id of a child dom elem
 */
table.get_key_from_elem = function(elem)
{
	var matches, str;
	
	if (elem.name) {
		str = elem.name;
	}
	else if (elem.id) {
		str = elem.id;
	}
	else if (elem.attr) {
		str = elem.attr('name') || elem.attr('id') || elem.closest('.sheet_table').attr('id');
	}
	if (str) {
		// reg exp: elemType_sheetNumber_tableNumber
		matches = str.match(/^[a-z_]+_(\d+)_(\d+)/i);
		if (matches && matches.length && matches.length > 2) {
			return (matches[1]+"_"+matches[2]);
		}
	}
	return null;
};

table.prototype.init_dates = function()
{
	var 
		dates = this.create_table_get_dates(Number(this.sheet.jElement.attr("table_count"))),
		suffix = (arguments.length > 0) ? '_'+arguments[0] : '',
		date_range_opts = $.extend({}, window.globals.date_range_rep, {
			key_suffix:this.key+suffix,
			do_set_keys:true
		})
	;
	if (dates) {
		if (!empty(dates.defined)) {
			date_range_opts.default_defined = dates.defined;
		}
		else if (!empty(dates.start_date)) {
			date_range_opts.default_start = dates.start_date;
			date_range_opts.default_end = dates.end_date;
		}
	}
	this.jElement.find('.date_range_placeholder').date_range(date_range_opts);
};

table.prototype.table_title_click = function(src_elem, event)      
{ 
	var div, cur_table, ml, move_options, prev_options_table;

	this.remove_table_options();
	ml = '';
	ml += '<p class="header_option" id="copy_' + this.key + '">Copy</p>';

	// don't show delete if this is the only table
	if (this.sheet.tables.length > 1) {
		ml += '<p class="header_option" id="delete_' + this.key + '">Delete</p>';
	}
	
	ml += '<p class="header_option" id="create_series_' + this.key + '">Create Series</p>';
	
	// see where the table can be moved
	move_options = this.report.get_move_options(this.sheet.jElement, this.jElement, 'VERT');		
	if (move_options.length > 0) {     
		ml += '<div class="header_option_spacer" />';
		
		for (i = 0; i < move_options.length; ++i) {
			ml += '<p class="header_option" func="move" id="move_' + this.key + '">' + move_options[i] + '</p>';  
		}
	}

	ml = '\
		<div id="table_options_'+this.key+'" class="sheet_table_options" style="position:absolute;top:'+(event.pageY + 8)+'px;left:'+(event.pageX + 2)+'px;">\
			'+ml+'\
		</div>\
	';
	$('body').append(ml);
}


table.prototype.table_options_click = function(src_elem, event)      
{
	var p = $(event.target);

	this.remove_table_options();
	if (p.is('.header_option')) {
		var func = (p.is('[func]')) ? p.attr('func') : p.text().simple_text();
		if (this[func]) {
			this[func](p, event);
		}
	}
};

table.prototype.remove_table_options = function(){ 
	// if options are already there, remove them
	var div;
	if ((div = $(".sheet_table_options")).length > 0 ) {    
		div.remove();
	}
};

table.prototype.create_series = function(p, e)
{
	var start_date = this.get_start_date('date'),
		rollover_day = (start_date) ? start_date.getDate() : false;

	if (rollover_day === false) {
		alert('Please enter a valid start date before creating a series');
		return false;
	}
	var $box = $.box({
		title:'Create Series',
		id:'create_series_box',
		event:e,
		close:true,
		content:'\
			<table>\
				<tbody>\
					<tr>\
						<td>Type</td>\
						<td>'+html.radios('table_series_type', ['Weekly', 'Monthly', 'Quarterly'])+'</td>\
					</tr>\
					<tr class="rollover hide">\
						<td>Rollover</td>\
						<td>'+html.select('table_series_rollover', Array.range(1, 31), rollover_day)+'</td>\
					</tr>\
					<tr>\
						<td>How Many</td>\
						<td>'+html.select('table_series_how_many', Array.range(2, 12))+'</td>\
					</tr>\
					<tr>\
						<td></td>\
						<td><input type="submit" name="series_submit" value="Go" /></td>\
					</tr>\
				</tbody>\
			</table>\
		'
	});

	// $box.find('[name="table_series_type"]').bind('click', this, function(e){ e.data.series_type_click($box, $(this)); });
	$box.find('[type="submit"][name="series_submit"]').bind('click', this, function(e){ e.data.create_series_submit($box); return false; });
};

table.prototype.get_start_date = function()
{
	var type = (arguments.length > 0) ? arguments[0] : 'str',
		start_date_str = $('input[name="start_date_'+this.key+'"]').val();
	return (type == 'str') ? start_date_str : Date.str_to_js(start_date_str);
};

table.prototype.series_type_click = function($box, $elem)
{
	var $row = $box.find('tr.rollover');
	if ($elem.val() == 'Monthly') {
		$row.show();
	}
	else {
		$row.hide();
	}
};

table.prototype.create_series_submit = function($box)
{
	var i, new_table, $start_date, $end_date, tmp_date,
		type = $box.find('[name="table_series_type"]:checked').val(),
		// rollover = $box.find('[name="table_series_rollover"]').val(),
		number = $box.find('[name="table_series_how_many"]').val(),
		prev_table = this
	;

	if (!type) {
		alert('Please select a type');
		return false;
	}

	for (i = 0; i < number; ++i) {
		new_table = prev_table.copy();
		$start_date = new_table.jElement.find('#start_date_'+new_table.key);
		$end_date = new_table.jElement.find('#end_date_'+new_table.key);
		// weekly
		if (type == 'Weekly') {
			tmp_date = prev_table.get_start_date('date');
			$end_date.val(Date.js_to_str(new Date(tmp_date.getTime() - 86400000)));
			$start_date.val(Date.js_to_str(new Date(tmp_date.getTime() - 604800000)));
		}
		// monthly
		else if (type == 'Monthly') {
			tmp_date = prev_table.get_start_date('date');
			$end_date.val(Date.js_to_str(new Date(tmp_date.getTime() - 86400000)));
			$start_date.val(Date.delta_month(Date.js_to_str(tmp_date), -1));
		}
		// quarterly
		else {
			tmp_date = prev_table.get_start_date('date');
			$end_date.val(Date.js_to_str(new Date(tmp_date.getTime() - 86400000)));
			$start_date.val(Date.delta_month(Date.js_to_str(tmp_date), -3));
		}
		// clear defined ranges for series tables
		$start_date.data('Date_Picker').clear_defined();
		prev_table = new_table;
	}
	$box.remove();
};

table.prototype.copy = function()
{
	var new_table = this.sheet.add_table(this.report);
	new_table.init_table(this.table_to_obj());
	return new_table;
};

table.prototype.move = function(src_elem)
{
	this.remove_table_options();
	this.report.move_object(this.jElement, src_elem.text().replace(/move./i, ''));
	this.sheet.sync_tables();
};

table.prototype.table_to_obj = function()
{
	var form_key, key, val, matches,
		tmp = String.parse_query(this.jElement.find('input, textarea, select').serialize().replace(/\+/g, ' ')),
		obj = {
			cols:{},
			filter:filter_to_obj(this.key)
		};
	;

	for (form_key in tmp) {
		matches = form_key.match(/^([a-z_]+[a-z]+)_([\d_]+)$/);
		if (matches !== null) {
			key = matches[1];
			val = tmp[form_key];
			if (key == 'col') {
				obj.cols[val] = 1;
			}
			else {
				switch (key) {
					case ('filter_cmp'):
					case ('filter_col'):
					case ('filter_val'):
						// noop
						break;
					default:
						obj[key] = val;
				}
			}
		}
	}
	return obj;
};

table.prototype.col_change_sync = function(cbox)   
{
	var selects, select, i;
	
	// other column actions
	if (cbox.val() == 'keyword') this.keyword_cbox_change(cbox);
	if (cbox.val() == 'ad')      this.ad_cbox_change(cbox);
	
	selects = this.jElement.find('select.col_change_listener');
	for (i = 0; i < selects.length; ++i)  
	{
		// not jqo :(
		select = selects[i];
		if (this.is_checked(cbox))
		{  
			if (!html.select_has_option(select, cbox.val())) html.select_add_option(select, cbox.val(), cbox.attr('display'));
		}
		else
		{ 
			html.select_remove_option(select, cbox.val());
		}
	}
	
	// coming soon?
	if (cbox.is('.filter_col'))
	{
		//filter_toggle_option(cbox.is(':checked'), this.key, cbox.val());
	}
};

table.prototype.is_checked = function(cbox)
{
	return (!cbox.is(':disabled') && cbox.is(':checked'));
};

table.prototype.col_group_header_click = function(cell)
{
	var row,
		self = this,
		cboxes = $();
	
	$row = cell.closest('tr');
	row_class = $row.attr('class');

	cboxes = cell.closest('table').find('tr.'+row_class+' input');

	if (cell.closest('table').find('tr.'+row_class+' input:checked').length){
		cboxes.removeAttr('checked');
	} else {
		cboxes.prop('checked', true);
	}

	cboxes.each(function(){ self.col_change_sync($(this)); });
};

/* 
 * here's the idea
 * if the user wants keyword detail,
 * then they can also show campaign, ad group, etc
 * if they want campaign detail
 * it makes no sense to also show ad group, keyword, etc
 * so we loop over detail types and allow the ones that make sense
 * and disable the ones that don't
 */

table.prototype.check_detail_cols = function(detail_select)  
{
	var i, detail, cbox, before_selected, selected;
	
	detail_select = $(detail_select);
	selected = detail_select.val();
	
	before_selected = true;
	for (i = 0; i < this.report.detail_cols.length; ++i) {
		detail = this.report.detail_cols[i];
		cbox = this.find_col_by_value(detail.key);
		
		if (detail.key == 'market') {
			this.market_change(f['market_'+this.key]);
		}
		else if (before_selected) {
			if (
				// can't select more than 1 leaf detail
				(detail.is_leaf && detail.detail != selected) ||
				// no ad group data for extensions
				(detail.key == 'ad_group' && (selected && selected.match(/^ext/)))
			) {
				this.disable_col(cbox);
			}
			else {
				this.enable_col(cbox, (selected == detail.detail));
			}
		}
		else {
			this.disable_col(cbox);
		}
		
		// see if we've hit the selected
		if (selected == detail.detail) {
			before_selected = false;
		}
	}
	this.sync_aggregate_markets();
	this.sync_extension_col();
}

table.prototype.sync_extension_col = function()   
{
	var
		$detail_select = this.jElement.find('.detail_select'),
		$parent_container = $detail_select.parent(),
		select_key = 'ext_type_'+this.key,
		$ext_type_select = $('#'+select_key),
		$select_wrapper = $('#w_'+select_key)
	;

	if ($detail_select.val() == 'extension') {
		// already created select, show it
		if ($ext_type_select.length > 0) {
			$ext_type_select.attr('disabled', false);
			$select_wrapper.show();
		}
		// create select
		else {
			$parent_container.append('\
				<div id="w_'+select_key+'" class="p_t4">\
					<span>Extension Type: </span>\
					'+html.select(select_key, globals.extensions)+'\
				</div>\
			');
		}
	}
	else {
		// hide/disable select
		$ext_type_select.attr('disabled', true);
		$select_wrapper.hide();
	}
};

table.prototype.check_sheet_for_dates = function(sheet)   
{
	var i, tables, t, key, start_date;
	
	tables = sheet.find('.sheet_table');
	for (i = 0; i < tables.length; ++i) {
		t = $(tables[i]);
		key = table.get_key_from_elem(t);
		
		start_date = $('#start_date_'+key).val();
		if (start_date) {
			return {
				start_date:start_date,
				end_date:t.find('#end_date_'+key).val(),
				defined:t.find('#defined_date_'+key).val()
			};
		}
	}
	return false;
};

table.prototype.create_table_get_dates = function()  
{
	var dates, i, table, table_key, node, sid, sheet, cur_sheet_index, headers;
	
	// check current sheet for dates
	dates = this.check_sheet_for_dates(this.sheet.jElement);
	if (dates) return dates;
	
	headers = this.report.headers_div.children('.sheet_header');
	cur_sheet_index = $('#rep_header_'+this.sheet.jElement.attr('sid')).prevAll('.sheet_header').length;
	
	// got our index, start going backwards
	for (i = cur_sheet_index -1; i > -1; --i)    
	{
		node = $(headers[i]);
		sid = node.attr('sid');
		sheet = $("#rep_sheet_"+sid);
		
		dates = this.check_sheet_for_dates(sheet);
		if (dates) return dates;
	}
	
	// still didn't find anything, go foward
	for (i = cur_sheet_index +1; i < headers.length; ++i)    
	{
		node = $(headers[i]);
		sid = node.attr('sid');
		sheet = $("#rep_sheet_"+sid);
		
		dates = this.check_sheet_for_dates(sheet);
		if (dates) return dates;
	}
	
	// fail, just use empty dates
	return {"start_date":"","end_date":""};
};

table.prototype.get_cols_and_sort = function(table_key)
{  
	var ml_cols, ml_cols_tmp, ml_sort, cbox_classes, col, col_info, cbox_key, group_count, col_count, group_col_count, COLS_PER_ROW, prev_group, is_conv_type_group;
	
	// "constant"
	COLS_PER_ROW = 4;
	
	ml_cols = '<table id="cols_table_'+table_key+'">';
	ml_sort = '<select class="col_change_listener" name="sort_'+table_key+'_0" id="sort_'+table_key+'_0">';
	
	col_count = 0;
	group_count = 0;
	prev_group = "";
	is_conv_type_group = false;
	
	for (col in window.g_all_cols)
	{  
		col_info = window.g_all_cols[col];
		
		if (col_info.group != prev_group)
		{  
			is_conv_type_group = (0 || col_info.is_conv_type);

			// add ml to cols table if past the first iteration
			if (col_count > 0) ml_cols += this.get_col_group_ml(ml_cols_tmp, group_count, group_col_count, COLS_PER_ROW, prev_group);
			
			ml_cols_tmp = '';
			group_col_count = 0;
			prev_group = col_info.group;
			group_count++;
		}
		 
		if ((group_col_count > 0) && ((group_col_count % COLS_PER_ROW) == 0)) ml_cols_tmp += '</tr><tr class="group_'+col_info.group+'">';
		
		cbox_classes = ['checkbox_sort_sync'];
		if (col_info.group == 'ad')
		{
			cbox_classes.push('filter_col')
		}
		
		cbox_key = 'col_'+table_key+'_'+col_count;
		ml_cols_tmp += this.get_col_cbox_ml(col, cbox_key, col_info, cbox_classes, !is_conv_type_group);
		ml_sort += '<option value="'+col+'">'+col_info.display+'</option>';
		
		++col_count;
		++group_col_count;
	}
	// add ml from last group
	ml_cols += this.get_col_group_ml(ml_cols_tmp, group_count, group_col_count, COLS_PER_ROW, prev_group);
	
	ml_cols += '</table>';
	ml_sort += '</select>';
	
	return {"ml_cols":ml_cols,"ml_sort":ml_sort};
};

table.prototype.get_col_cbox_ml = function(col, cbox_key, col_info, classes, is_checked)
{
	var ml_checked = (is_checked) ? ' checked' : '';

	return '\
		<td class="p_r16">\
			<input class="'+classes.join(' ')+'" type="checkbox" display="'+col_info.display+'" id="'+cbox_key+'" name="'+cbox_key+'" value="'+col+'"'+ml_checked+' />\
			<label for="'+cbox_key+'">'+col_info.display+'</label>\
		</td>\
	';
};
		

table.prototype.get_col_group_ml = function(ml_cols_tmp, group_count, group_col_count, COLS_PER_ROW, group_name) 
{
	var ml_tmp = "";
	
	// add spacer between groups
	if (group_count > 1) ml_tmp += '<tr class="cols_spacer group_'+group_name+'"><td colspan=32 height=16></td></tr>';
	
	ml_tmp += '<tr valign="top" class="group_'+group_name+'">';
	ml_tmp += '<td class="col_group_header" rowspan='+(Math.ceil(group_col_count / COLS_PER_ROW))+'>'+group_name.display_text()+'</td>';
	ml_tmp += ml_cols_tmp;
	ml_tmp += '</tr>';
	
	return ml_tmp;
};

table.prototype.get_market_select = function(table_key)  
{
	var market, options = [['all','Summary']];
	for (market in g_markets) {
		options.push([market, g_markets[market]]);
	}
	return html.select('market_'+table_key, options, null, 'class="market_select"');
};

table.prototype.get_time_period_select = function(table_key)      
{
	var ml, i, ml_options, time_periods;
	
	var self = this;
	
	time_periods = {"all":"Summary","quarterly":"Quarterly","monthly":"Monthly","weekly":"Weekly","daily":"Daily","custom":"Custom"};
	ml_options = '';
	for (i in time_periods) {
		ml_options += '<option value="'+i+'">'+time_periods[i]+'</option>';
	}
	
	ml = "<select name='time_period_" + table_key + "' class='time_period_select'>";
	ml += ml_options + "</select>"; 

	
	return ml;
};

table.prototype.get_detail_select = function(table_key)   
{
	var ml, i, detail, ml_options, details;
	
	var self = this;
	
	ml_options = '';
	for (i = 0; i < this.report.detail_cols.length; ++i) {
		detail = this.report.detail_cols[i];
		ml_options += '<option value="'+detail.detail+'">'+detail.display+'</option>';
	}
	
	ml = "<select name='detail_" + table_key + "' class='detail_select'>";
	ml += ml_options + "</select>"; 	
		
	return ml;
};

table.prototype.create_table = function()     
{
	var div, ml, sid, table_num, cols_and_sort, table_key;
	
	sid = this.sheet.jElement.attr('sid');
	table_num = Number(this.sheet.jElement.attr("table_count"));
	table_key = sid+"_"+table_num;
	
	this.key = table_key;

	// set both of these at the same time
	cols_and_sort = this.get_cols_and_sort(table_key);
	
	ml = '';
	// title
	ml += '<div class="table_title lft" id="table_title_'+table_key+'"><h2></h2></div>';
	ml += '<div class="clr"></div>';

	// description
	ml += '<div class="table_attr">Description '+this.get_meta_desc_input(table_key)+'</div>';
	ml += '<div class="clr"></div>';

	// dates
	ml += '<div class="table_attr">';
	ml += '<table><tbody><tr class="date_range_placeholder"></tr></tbody></table>';
	ml += '</div>';
	ml += '<div class="clr"></div>';

	// meta
	ml += '<div class="table_attr">Market '+this.get_market_select(table_key)+'</div>';
	ml += '<div class="table_attr">Time Period '+this.get_time_period_select(table_key)+'</div>';
	ml += '<div class="table_attr">Detail '+this.get_detail_select(table_key)+'</div>';	

	// columns to show in report
	ml += '<div class="clr"></div>';
	ml += '<div class="table_attr">'+cols_and_sort.ml_cols+'</div>';
	
	// keyword type, starts as hidden
	ml += '<div id="keyword_style_separator_'+table_key+'" class="clr hide"></div>';
	ml += '<div id="keyword_style_container_'+table_key+'" class="table_attr hide">Keyword Style '+this.get_keyword_display_mode_select(table_key)+'</div>';
	
	// aggregate markets
	ml += '<div id="aggregate_markets_separator_'+table_key+'" class="clr hide"></div>';
	ml += '<div id="aggregate_markets_container_'+table_key+'" class="table_attr hide">';
	ml += '<label class="lft" for="aggregate_markets_'+table_key+'">Aggregate Markets</label>';
	ml += ' &nbsp;<input type="checkbox" name="aggregate_markets_'+table_key+'" id="aggregate_markets_'+table_key+'" />';
	ml += '</div>';
	
	// campaign
	ml += '<div class="clr"></div>';
	ml += '<div class="table_attr">';
	ml += '<label class="lft campaign_label">Campaign</label>';
	ml += '<div id="campaigns_container_'+table_key+'" class="lft">';
	ml += '<span name="campaigns_cboxes_'+table_key+'" class="campaigns_select">';
	ml += '<span class="campaign_filter">'+this.get_campaign_cboxes_header()+'</span>';
	ml += '<span class="inner_campaign_cboxes">'+this.get_campaign_cboxes(table_key)+'</span>';
	ml += '</span>'
	ml += '</div>';
	ml += '</div>';


	// filter
	ml += '<div class="clr"></div>';
	ml += '<div class="table_attr">';
	ml += '<label class="lft">Filter</label>';
	ml += '<div id="filter_container_'+table_key+'" class="lft">'+filter_init(table_key)+'</div>';
	ml += '<div class="clr"><input type="submit" value="Reset" onclick="filter_reset(\''+table_key+'\'); return false;" /></div>';
	ml += '</div>';
	
	// display
	ml += '<div class="clr"></div>';
	ml += '<div class="table_attr">';
	ml += '<div>';
	ml += '<label class="lft" style="padding-right:4px;">Display Type</label>';
	ml += this.get_display_type_select(table_key);
	ml += '</div>';
	ml += '</div>';
	
	// sort
	ml += '<div class="clr"></div>';
	ml += '<div class="table_attr"><label class="lft" style="padding-right:4px;">Sort By</label><div class="lft" id="table_sort_container_'+table_key+'"><div>'+cols_and_sort.ml_sort+'</div></div></div>';
	ml += '<div class="table_attr">';
	ml += '<input type="button" class="sort_add_button" value="Add" name="sort_add_' + table_key + '" table_key="' + table_key + '" />';
	ml += '<input type="button" class="sort_reset_button" value="Reset" name="sort_reset_' + table_key + '" table_key="' + table_key + '" />';
	ml += '</div>';
	ml += '<div class="clr"></div>';
					
	// limit
	ml += '<div class="table_attr">Limit '+this.get_limit_input(table_key)+'</div>';
	ml += '<div class="clr"></div>';

	this.report.cur_sheet.jElement.attr("table_count", table_num + 1);
	
	div = $("<div/>").attr('id','table_'+table_key)
					.addClass('sheet_table')
					.attr("count", table_num)
					.html(ml);

	
	return div;
};

table.prototype.market_change = function(select) 
{
	var table_key, cbox;
	
	if (!select) {
		return;
	}
	if (!select.val) {
		select = $(select);
	}
	cbox = this.find_col_by_value("market");
	
	if (select.val() == "all") {
		this.enable_col(cbox, true);
	}
	else { 
		if (f["detail_"+this.key].value != "all") {
			this.disable_col(cbox);
		}
	}
	this.sync_aggregate_markets();
	this.sync_extension_detail();
	this.sync_campaign_cboxes();
};

table.prototype.sync_campaign_cboxes = function() 
{
	var i, $cbox, $label,
		$container = $('#campaigns_container_'+this.key),
		$cboxes = $container.find('input.ca_cbox[type="checkbox"]'),

		market_val = $('#market_'+this.key).val(),
		filter_show_hide = $('input[name="campaign_filter_show_hide_'+this.key+'"]:checked').val(),
		filter_val = $container.find('.campaign_name_filter').val();
		filter_re = new RegExp(RegExp.escape(filter_val), 'i'),
		paused_val = $('#show_paused_campaigns_'+this.key).is(':checked'),
		deleted_val = $('#show_deleted_campaigns_'+this.key).is(':checked')
	;

	for (i = 0; i < $cboxes.length; ++i) {
		$cbox = $($cboxes[i]);
		$label = $cbox.siblings('label');

		if (
			this.campaign_market_test($cbox, market_val) &&
			this.campaign_filter_test($label, filter_show_hide, filter_val, filter_re) &&
			this.campaign_status_test($label, paused_val, deleted_val)
		) {
			this.show_campaign_checkbox($cbox);
		}
		else {
			this.hide_campaign_checkbox($cbox);
		}
	}
};

table.prototype.campaign_market_test = function($cbox, market_val) 
{
	return (market_val == 'all' || $cbox.attr('m') == market_val);
};

table.prototype.campaign_filter_test = function($label, filter_show_hide, filter_val, filter_re) 
{
	// always show on empty string
	if (filter_val == '') {
		return true;
	}
	var is_match = filter_re.test($label.text());
	return (
		(filter_show_hide == 'Show' && is_match) ||
		(filter_show_hide == 'Hide' && !is_match)
	);
};

table.prototype.campaign_status_test = function($label, do_show_paused, do_show_deleted) 
{
	if (this.is_campaign_off($label)) {
		return (do_show_paused);
	}
	else if (this.is_campaign_deleted($label)) {
		return (do_show_deleted);
	}
	// active, always passes status test {
	else {
		return true;
	}
};

table.prototype.sync_extension_detail = function() 
{
	var i, $opt,
		market = this.jElement.find('.market_select').val(),
		exts = (market in globals.extensions && globals.extensions[market].length) ? globals.extensions[market] : [],
		$detail_select = this.jElement.find('.detail_select'),
		$options = $detail_select.find('option');
	;
	// remove extensions currently on select
	for (i = 0; i < $options.length; ++i) {
		$opt = $($options[i]);
		// if the market has not changed, do not need
		// to do anything, return from function
		if ($opt.attr('market') == market) {
			return;
		}
		if (this.is_extension($opt.val())) {
			$opt.remove();
		}
	}
	// add extensions for this market
	for (i = 0; i < exts.length; ++i) {
		$detail_select.append('<option market="'+market+'" value="ext_'+exts[i]+'">Extension: '+exts[i]+'</option>');
	}
};

table.prototype.is_extension = function(x)
{
	return (x && x.match(/^ext_/))
};

table.prototype.get_campaign_cboxes_header = function()
{
	var
		show_paused_key = 'show_paused_campaigns_'+this.key,
		show_deleted_key = 'show_deleted_campaigns_'+this.key
	;
	return '\
		<table>\
			<tbody>\
				<tr>\
					<td>Name Filter</td>\
					<td>\
						<input type="text" class="campaign_name_filter" />\
						'+html.radios('campaign_filter_show_hide_'+this.key, ['Show', 'Hide'], 'Show', {separator:' &nbsp; '})+'\
					</td>\
				</tr>\
				<tr>\
					<td colspan="10">\
						<label for="'+show_paused_key+'">Show Paused</label>\
						<input type="checkbox" class="paused_campaigns campaign_status" id="'+show_paused_key+'" />\
						&nbsp; &nbsp;\
						<label for="'+show_deleted_key+'">Show Deleted</label>\
						<input type="checkbox" class="deleted_campaigns campaign_status" id="'+show_deleted_key+'" />\
					</td>\
				</tr>\
			</tbody>\
		</table>\
		<hr style="margin:2px 24px;" />\
	';
};

table.prototype.campaign_status_click = function($elem)
{
	this.sync_campaign_cboxes();
};

table.prototype._campaigns_click = function($elem)
{
	this.sync_campaign_cboxes();
};

table.prototype.campaign_filter_keyup = function($elem, event)
{
	this.sync_campaign_cboxes();
};

table.prototype.show_campaign_checkbox = function($cbox)
{
	if (!$cbox.is(':visible')) {
		$cbox.attr('disabled', false);
		$cbox.closest('span').show();
	}
};

table.prototype.hide_campaign_checkbox = function($cbox)
{
	if ($cbox.is(':visible')) {
		if ($cbox.is(':checked')) {
			$cbox[0].click();
		}
		$cbox.attr('disabled', true);
		$cbox.closest('span').hide();
	}
};

table.prototype.get_campaign_cboxes = function(table_key)
{
	var
		campaigns_key = 'campaigns_'+table_key
	;
	if (!globals.campaigns) {
		return '';
	}
	else {
		return html.checkboxes(campaigns_key, globals.campaigns, [], {
			toggle_all:1,
			attrs:'no_star="1" separator=","',
			classes:'w_campaigns'
		});
	}
};

// if the keyword column is checked
// show the select for keyword mode
table.prototype.keyword_cbox_change = function(cbox)
{
	var is_on = this.is_checked(cbox);
	if (is_on)
	{
		$("#keyword_style_separator_"+this.key).removeClass("hide");
		$("#keyword_style_container_"+this.key).removeClass("hide");
	}
	else if (!is_on && !$("#keyword_style_separator_"+this.key).hasClass("hide"))
	{
		$("#keyword_style_separator_"+this.key).addClass("hide");
		$("#keyword_style_container_"+this.key).addClass("hide");
	}
}

// toggle showing ad details cboxes
table.prototype.ad_cbox_change = function(cbox)
{
	var i, is_on = this.is_checked(cbox),
		cols_table = $('#cols_table_'+this.key),
		ad_rows = cols_table.find('.group_ad'),
		cboxes = ad_rows.find('input[type="checkbox"]');
	
	if (is_on)
	{
		cboxes.attr('disabled', false);
		ad_rows.show();
	}
	else
	{
		cboxes.attr('disabled', true);
		ad_rows.hide();
	}
	for (i = 0; i < cboxes.length; ++i)
	{
		this.col_change_sync($(cboxes[i]));
	}
}

table.prototype.enable_col = function(cbox, do_check)
{
	cbox.attr('disabled', false);
	if (do_check) cbox.attr('checked', true);
	cbox.closest('td').css('color', '');
	
	this.col_change_sync(cbox);
}

table.prototype.disable_col = function(cbox)
{
	cbox.attr('disabled', true);
	cbox.closest('td').css('color', '#9a9a9a');
	
	this.col_change_sync(cbox);
};

table.prototype.find_col_by_value = function(value)
{
	return $('#cols_table_'+this.key+' input[value="'+value+'"]');
};

table.prototype.sort_reset = function()
{
	var container;
	
	container = $("#table_sort_container_"+this.key);
	container.children().remove();
	
	this.sort_add();
}

table.prototype.get_display_type_select = function(table_key) 
{
	return html.select('display_type_'+table_key, globals.display_types, null, 'class="display_type_select"');
};

table.prototype.get_meta_desc_input = function(table_key)
{
	var
		ml_input = '<input type="text" class="meta_desc" name="meta_desc_'+table_key+'" disabled="1" />'
		ml_radios = html.radios('meta_desc_toggle_'+table_key, ['Default', 'Custom'], 'Default', {attrs:'class="meta_desc_toggle"',separator:' &nbsp; '})
	;

	return ml_radios + ml_input;
};

table.prototype.get_limit_input = function(table_key)
{
	return '<input class="w32" type="text" name="limit_'+table_key+'" />';
};

table.prototype.get_keyword_display_mode_select = function(table_key)
{
	var ml;
	
	ml = '<select name="keyword_style_'+table_key+'">';
	ml += '<option value="grouped">Grouped</option>';
	ml += '<option value="match_type">Match Type</option>';
	ml += '</select>';
	
	return ml;
};

table.prototype.sort_add = function()
{
	var container, div, ml, option, col, col_info, cbox;
	
	container = $("#table_sort_container_"+this.key);
	ml = '<select class="col_change_listener" name="sort_'+this.key+'_'+container.children().length+'" id="sort_'+this.key+'_'+container.children().length+'">';
	for (col in window.g_all_cols)
	{
		col_info = window.g_all_cols[col];
		cbox = this.find_col_by_value(col);
		if (this.is_checked(cbox))
		{
			ml += '<option value="'+col+'">'+col_info.display+'</option>';
		}
	}
	ml += '</select>';
	
	container.append('<div>'+ml+'</div>');
};

table.prototype.meta_desc_toggle_click = function($elem, event)
{
	var 
		$input = $elem.closest('div.table_attr').find('input.meta_desc'),
		do_select = (arguments.length > 2) ? arguments[2] : true;
	;
	if ($elem.length > 1) {
		$elem = $elem.filter(':checked');
	}

	if ($elem.val() == 'Custom') {
		$input.attr('disabled', false);
		$input.show();
		if (do_select) {
			$input.select();
		}
	}
	else {
		$input.attr('disabled', true);
		$input.hide();
	}
};

table.prototype.get_table_index_in_sheet = function(tableObj){ 
	
	for (var i = 0; i < this.sheet.tables.length; i++)
	{
		if (this.sheet.tables[i].key == this.key)
		{
			return i;
		}
	}
	
	return -1;
}

table.prototype.delete = function()   
{
	var table_index;
	
	this.remove_table_options();
			
	if (this.sheet.tables.length == 1) {   
		alert("Can't delete the last table!");
		return;
	}
	else { 
		this.jElement.remove();
		// remove the table from the sheet object
		if ((table_index = this.get_table_index_in_sheet()) > -1) {
			this.sheet.tables.splice(table_index, 1);
		}
		this.sheet.sync_tables();
	}
};

// show details if time period is weekly or monthly
table.prototype.time_period_change = function(select)   
{
	var
		$select = $(select),
		selected = $select.val(),
		ml = false,
		sub_div_id = "time_period_more_"+this.key+"_div"
	;
	
	// remove any previously added sub div
	$('#'+sub_div_id).remove();
	
	// weekly and monthly are only two options at this point
	if (selected == "weekly") {
		ml = this.get_weekly_select(this.key);
	}
	else if (selected == "monthly") {
		ml = this.get_monthly_select(this.key);
	}

	if (ml !== false) {
		$select.parent().append('<div id="'+sub_div_id+'" class="p_t4">'+ml+'</div>');
	}
	else {
		// custom is its own beast
		if (selected == "custom") {
			this.enable_custom_dates();
		}
		else {
			// remove custom elems
			this.disable_custom_dates();
		}
	}
};

table.prototype.enable_custom_dates = function()     
{
	var
		$tbody = $('#start_date_'+this.key).closest('tbody'),
		add_date_range_button_id = 'add_date_range_button_'+this.key
	;
	$tbody.append('\
		<tr>\
			<td colspan="10">\
				<input type="submit" class="small_button" id="'+add_date_range_button_id+'" value="Add Date Range" />\
			</td>\
		</tr>\
	');
	$('#'+add_date_range_button_id).on('click', this, function(e){ e.data.add_custom_date_range_click(); return false; });
};

table.prototype.disable_custom_dates = function()     
{
	$tbody = $('#start_date_'+this.key).closest('tr').nextAll().remove();
};

table.prototype.add_custom_date_range_click = function()
{
	var new_id, $new_row, $new_row_first_col,
		$tbody = $('#start_date_'+this.key).closest('tbody'),
		$add_date_range_button_row = $tbody.find('#add_date_range_button_'+this.key).closest('tr')
	;

	new_id = $tbody.find('tr.custom_date_range').length;

	// add placeholder row, which init dates converts to a date range selector
	$add_date_range_button_row.before('<tr class="date_range_placeholder"></tr>');
	this.init_dates(new_id);
	// mark as custom row
	$new_row = $('#start_date_'+this.key+'_'+new_id).closest('tr');
	$new_row.addClass('custom_date_range');
	$new_row.attr('num', new_id);
	// add remove button for row
	$new_row_first_col = $new_row.find('td:first-child');
	$new_row_first_col.prepend('<input type="submit" class="small_button delete_row" value=" - " />&nbsp;');
	$new_row_first_col.find('.delete_row').on('click', this, function(e){ e.data.custom_date_range_delete_row($(this)); return false; });
};

table.prototype.custom_date_range_delete_row = function($button)
{
	var $tr = $button.closest('tr'),
		$next_inputs = $tr.nextAll('.custom_date_range').find('input[type="text"],input[type="hidden"]')
	;
	$tr.remove();
	// decrement following input row names and ids
	$next_inputs.each(function(){
		var key, 
			keys = {'id':1,'name':1},
			$input = $(this),
			prev_num = Number($input.closest('tr').attr('num'))
		;
		for (key in keys) {
			$input.attr(key, $input.attr(key).replace(/\d+$/, prev_num - 1))
		}
	});
};

table.prototype.display_type_change = function(select)
{
	var $table_extras_container = $('#total_type_container_' + this.key),
		$chart_extras_container = $('#y_axis_label_container_' + this.key),
		sel_value = select.val();
	
	// Add new one if it is line or bar chart  
	if (sel_value == 'line_chart' || sel_value == 'bar_chart')  {
		$table_extras_container.hide();
		if ($chart_extras_container.length > 0) {
			$chart_extras_container.show();
		}
		else {
			select.closest('.table_attr').append(this.get_y_axis_label_input());
		}
	}
	else if (sel_value == 'table') {
		$chart_extras_container.hide();
		if ($table_extras_container.length > 0) {
			$table_extras_container.show();
		}
		else {
			select.closest('.table_attr').append(this.get_total_type_input());
		}
	}
};

table.prototype.get_y_axis_label_input = function()
{
	return '\
		<div id="y_axis_label_container_'+this.key+'">\
			<label class="p_t4">Y-Axis Label</label>\
			<input id="y_axis_label_'+this.key+'" name="y_axis_label_'+this.key+'">\
		</div>\
	';
};

table.prototype.get_total_type_input = function()
{
	return '\
		<div id="total_type_container_'+this.key+'">\
			<label class="p_t4">Total Row</label>\
			'+html.select('total_type_'+this.key, ['Total', 'Compare'])+'\
		</div>\
	';
};

table.prototype.get_secondary_axis_select = function()
{
	return '\
		<div id="secondary_axis_container_'+this.key+'">\
			<label class="p_t4">Secondary Axis</label>\
			<select class="col_change_listener" name="secondary_axis_'+this.key+'">\
				<option value="None">None</option>\
				'+$('#sort_'+this.key+'_0').html()+'\
			</select>\
		</div>\
	';
};

table.prototype.get_weekly_select = function(table_key)
{
	var ml, i, ml_options, weekdays;
	
	ml_options = '';
	for (i = 0; i < window.g_weekdays.length; ++i)
		ml_options += '<option value="'+i+'">'+window.g_weekdays[i]+'</option>';
	
	ml = 'Start ';
	ml += '<select name="time_period_weekly_'+table_key+'">';
	ml += ml_options;
	ml += '</select>';
	
	return ml;
};

table.prototype.get_monthly_select = function(table_key)
{
	var ml, i, ml_options, days_in_month;
	
	days_in_month = 31;
	
	ml_options = '';
	for (i = 1; i <= days_in_month; ++i)
		ml_options += '<option value="'+i+'">'+i+'</option>';
	
	ml = 'Start ';
	ml += '<select name="time_period_monthly_'+table_key+'">';
	ml += ml_options;
	ml += '</select>';
	
	return ml;
};

table.prototype.load_campaigns = function($wrapper, market, campaigns) 
{
	var i, $cbox, ca_id,
		init_off_campaigns = false,
		init_deleted_campaigns = false
	;
	
	if (typeof(campaigns) != 'undefined' && campaigns && campaigns != 'null') {
		campaigns = campaigns.split(',');
		for (i = 0; i < campaigns.length; ++i) {
			ca_id = campaigns[i];
			// backwards compatible
			// used to have to select market, so we did not need market prepended to ca id
			if (!isNaN(ca_id.charAt(0))) {
				ca_id = market+'_'+ca_id;
			}
			$cbox = $wrapper.find('#campaigns_'+this.key+'-'+ca_id);
			if ($cbox.length > 0) {
				// only need to do this once, but must do this before we click anything camps that are off
				if (this.is_campaign_off($cbox) && !init_off_campaigns) {
					init_off_campaigns = true;
					$('#show_paused_campaigns_'+this.key)[0].click();
				}
				else if (this.is_campaign_deleted($cbox) && !init_deleted_campaigns) {
					init_deleted_campaigns = true;
					$('#show_deleted_campaigns_'+this.key)[0].click();
				}
				$cbox[0].click();
			}
		}
	}
}

table.prototype.is_campaign_off = function($elem)
{
	return this.is_campaign_status($elem, 'off|paused');
};

table.prototype.is_campaign_deleted = function($elem)
{
	return this.is_campaign_status($elem, 'deleted');
};

table.prototype.is_campaign_status = function($elem, status_re_str)
{
	var $label,
		re = new RegExp('('+status_re_str+')\\)$', 'i')
	;
	if ($elem.is('label')) {
		$label = $elem;
	}
	else {
		$label = ($elem.is('span') ? $elem : $elem.closest('span')).find('label');
	}
	return ($label.text().trim().search(re) != -1);
};

// some cols get turned on by default when table is created
// get all the checkboxes and see if they are actually in our selected cols obj
table.prototype.init_table_cols = function(cols_selected)
{
	var cols_table, cols, col, i;
	
	cols_table = $('#cols_table_'+this.key)
	cols = cols_table.find('input');
	for (i = 0; i < cols.length; ++i) {
		col = $(cols[i]);
		if (!cols_selected[col.val()]) {
			col.attr('checked', false);
			this.col_change_sync(col);
		}
		else if (!col.is(':checked') && cols_selected[col.val()]) {
			col.attr('checked', true);
			this.col_change_sync(col);
		}
	}
};

// first we have to add the sort selects to the table
// then sync options with checked columns is done in rep_init_table_cols
// then set the sort value (rep_init_table_sort_set)
table.prototype.init_table_sort = function(sort) 
{
	var i;
	
	// legacy code, used to just be one sort, convert to array if string
	if (typeof(sort) == 'string')
	{
		sort = [sort];
	}
	else if (!sort)
	{
		sort = [''];
	}
	
	// first sort is added when table is created, if past 0, add another
	for (i = 1; i < sort.length; ++i)
	{
		this.sort_add();
	}
};

table.prototype.init_table_sort_set = function(sort)
{
	var i;
	
	// legacy code, used to just be one sort, convert to array if string
	if (typeof(sort) == 'string') sort = [sort];
	for (i = 0; i < sort.length; ++i)
	{
		$f('sort_'+this.key+'_'+i, sort[i], 0);
	}
};

table.prototype.init_custom_dates = function(custom_dates)   
{
	if (empty(custom_dates)) {
		return;
	}
	var i, date_suffix, dates;
	// start at 1: 0th dates are already set via normal start and end date
	for (i = 1; i < custom_dates.length; ++i) {
		dates = custom_dates[i];
		date_suffix = this.key+'_'+(i - 1);
		this.add_custom_date_range_click();
		$('#start_date_'+date_suffix).val(dates.start);
		$('#end_date_'+date_suffix).val(dates.end);
	}
};

table.prototype.init_table = function(data)   
{
	var i, field, $elem;

	for (field in data) {
		$elem = this.jElement.find('[name="'+field+'_'+this.key+'"]');
		if ($elem.is('input[type="checkbox"]')) {
			if (!empty(data[field])) {
				$elem.attr('checked', true);
			}
		}
		else if ($elem.is('input[type="radio"]')) {
			$elem.filter('[value="'+data[field]+'"]').attr('checked', true);
		}
		else {
			$f(field+'_'+this.key, decodeURIComponent(data[field]), 0);
		}
	}
	
	// todo: save defined in db so we can properly load it
	// clear defined for now
	$('#start_date_'+this.key).data('Date_Picker').clear_defined();
	
	this.init_table_cols(data.cols);
	filter_init_obj(this.key, data.filter);
	this.init_table_sort(data.sort);

	this.display_type_change($('select[name="display_type_'+this.key+'"]'));
	this.market_change(f['market_'+this.key]);
	this.check_detail_cols(f['detail_'+this.key]);
	this.time_period_change(f['time_period_'+this.key]);
	// check for custom dates
	this.init_custom_dates(data.custom_dates);
	// run again :(
	this.init_table_cols(data.cols);
	this.keyword_cbox_change(this.find_col_by_value('keyword'));
	
	this.load_campaigns($('span[name="campaigns_cboxes_'+this.key+'"]'), data.market, data.campaigns);
	this.meta_desc_toggle_click(this.jElement.find('input.meta_desc_toggle'), null, false);
	
	// set fields that are not always present (this must be after we run the functions above)
	for (i = 0; i < window.g_optional_fields.length; ++i) {
		field = window.g_optional_fields[i];
		if (f[field+"_"+this.key] && field in data) {
			$f(field+"_"+this.key, decodeURIComponent(data[field]), 0);
		}
	}
	this.init_table_sort_set(data.sort);
};

table.prototype.sync_aggregate_markets = function()
{
	if ($(f['market_'+this.key]).val() == 'all' && $(f['detail_'+this.key]).val() == 'all')
	{
		$('#aggregate_markets_separator_'+this.key).show();
		$('#aggregate_markets_container_'+this.key).show();
		$('#aggregate_markets_'+this.key).attr('disabled', false);
	}
	else
	{
		$('#aggregate_markets_separator_'+this.key).hide();
		$('#aggregate_markets_container_'+this.key).hide();
		$('#aggregate_markets_'+this.key).attr('disabled', true);
	}
};
