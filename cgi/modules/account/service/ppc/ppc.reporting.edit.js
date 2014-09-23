
function ppc_reporting_edit()
{
	this.cur_header = null;
	this.cur_sheet = null;
	
	this.sheet_count = 0;
	
	this.headers_div = $('#sheet_headers');
	this.sheets_div = $('#sheet_container');
	
	this.sheets = new Array();
	
	$('#add_sheet_button').bind('click', this, function(e){ e.data.add_sheet_click(); });
	$('#add_table_button').bind('click', this, function(e){ e.data.add_table_click(); });
	$('#set_dates_button').bind('click', this, function(e){ e.data.set_dates_show(); });
	$('#load_sheet_button').bind('click', this, function(e){ e.data.load_sheet_click(e); });
	$('#refresh_campaigns_button').bind('click', this, function(e){ e.data.refresh_campaigns_click(); });
	$('#run_report_button').bind('click', this, function(e){ e.data.run_report_click(); });
	$('#set_dates_submit_button').bind('click', this, function(e){ e.data.set_dates_go(); return false; });
	$('#set_dates_cancel_button').bind('click', this, function(e){ e.data.set_dates_cancel(); return false; });
	$('#save_changes_button').bind('click', this, function(e){ e.data.save_changes_click(); });
	$('#save_template_button').bind('click', this, function(e){ e.data.save_template(); });
	$('#load_template_submit').bind('click', this, function(e){ e.data.load_template_submit(); return false; });
	
	this.set_live_events();
	this.init_detail_cols();
	
	$('#set_date_button').bind('click', this, function(e){ e.data.set_dates_go(); return false; });
	$('#cancel_set_date_button').bind('click', this, function(e){ e.data.set_dates_cancel(); return false; });
	
	var self = this;
	document.onclick = function(event){
		self.doc_click(event);
	}
	
	if (!empty($f('rep_id')) || globals.is_user_loaded_template)
	{
		this.init_from_saved();
	}
	else
	{
		var sheet = this.add_sheet(this);
		sheet.add_table(this);
	}
}

ppc_reporting_edit.prototype.add_sheet_click = function()
{
	var sheet = this.add_sheet(this);
	sheet.add_table(this);
};

ppc_reporting_edit.prototype.add_table_click = function()
{
	this.cur_sheet.add_table(this, {prepend:true});
};

ppc_reporting_edit.prototype.set_dates_cancel = function()
{
	$('#set_dates_container').hide();
};

ppc_reporting_edit.prototype.set_dates_show = function()  
{
	var container, all_dates, div, ml, i, j, sheet, sheet_id, table, table_id, start_date, end_date;
	
	// get possible dates to replace with new dates
	all_dates = {};
	for (i = 0; i < this.headers_div.children().length; ++i)
	{
		node = $(this.headers_div.children()[i]);
		sheet_id = node.attr('sid');
		sheet = $('#rep_sheet_'+sheet_id);
		
		for (j = 0; j < sheet.children().length; ++j) 
		{
			table = $(sheet.children()[j]);
			table_id = table.attr('count');
			
			start_date = f['start_date_'+sheet_id+'_'+table_id].value;
			end_date = f['end_date_'+sheet_id+'_'+table_id].value;
			
			if (!all_dates[start_date]) all_dates[start_date] = {};
			all_dates[start_date][end_date] = 1;
		}
	}
	
	container = $('#set_dates_replace_container');
	$(container).children().remove();
	
	i = 0;
	for (start_date in all_dates)
	{ 
		for (end_date in all_dates[start_date])
		{ 
			ml = '<input type="radio" name="set_dates_radio" value="'+start_date+','+end_date+'"';
			if (i++ == 0) ml += ' checked';
			ml += ' />';
			ml += ' '+start_date+' to '+end_date;
			
			div = $("<div/>").addClass('set_dates_replace')
							 .html(ml);
			container.append(div);
		}
	}
	
	// show it
	$('#set_dates_container').show();
};

ppc_reporting_edit.prototype.load_sheet_click = function(e)
{
	$.box({
		id:'load_sheet_box',
		title:'Load Sheet',
		close:true,
		event:e,
		content:'\
			<table>\
				<tbody>\
					<tr>\
						<td>Report</td>\
						<td id="load_sheet_reports_td"></td>\
					</tr>\
					<tr>\
						<td>Sheet</td>\
						<td id="load_sheet_sheets_td"></td>\
					</tr>\
					<tr>\
						<td></td>\
						<td>\
							<input type="submit" id="load_sheet_cancel" value="Cancel" />\
						</td>\
					</tr>\
				</tbody>\
			</table>\
		'
	});
	$('#load_sheet_cancel').click(function(){ $('#load_sheet_box').hide(); return false; });
	if (!this.external_reps_loaded)
	{
		this.load_external_reps();
	}
};

ppc_reporting_edit.prototype.refresh_campaigns_click = function()
{
	if (confirm('Page must be refreshed. Save changes first?')) {
		this.on_save_success = this.refresh_campaigns_action_submit.bind(this);
		this.save_report();
	}
	else {
		this.refresh_campaigns_action_submit();
	}
};

ppc_reporting_edit.prototype.refresh_campaigns_action_submit = function()
{
	e2.action_submit('action_refresh_campaigns');
};

ppc_reporting_edit.prototype.load_external_reps = function()
{
	this.post('load_external_reps', {});
};

ppc_reporting_edit.prototype.load_external_reps_callback = function(request, response)
{
	response.unshift(['', ' - Select - ']);
	$('#load_sheet_reports_td').html(html.select('load_sheet_rep_select', response));
	$('#load_sheet_rep_select').bind('change', this, function(e){ e.data.load_sheet_rep_change($(this)); });
	this.external_reps_loaded = true;
};

ppc_reporting_edit.prototype.load_sheet_rep_change = function(select)
{
	if (select.val())
	{
		this.post('load_external_sheets', {rid:select.val()});
	}
};

ppc_reporting_edit.prototype.load_external_sheets_callback = function(request, response)
{
	var sheet_name, sheet_names = [];
	this.external_sheets = response;
	for (sheet_name in this.external_sheets)
	{
		sheet_names.push(sheet_name);
	}
	sheet_names.sort();
	sheet_names.unshift(['', ' - Select - ']);
	$('#load_sheet_sheets_td').html(html.select('load_sheet_sheet_select', sheet_names));
	$('#load_sheet_sheet_select').bind('change', this, function(e){ e.data.load_sheet_sheet_change($(this)); });
};

ppc_reporting_edit.prototype.load_sheet_sheet_change = function(select)
{
	var tables, t, s, i,
		selected = select.val();
	
	if (selected && confirm('Load sheet '+selected+'?'))
	{
		s = this.add_sheet();
		this.cur_header.text("Loaded copy of "+selected);
		
		tables = this.external_sheets[selected];
		for (i = 0; i < tables.length; ++i)
		{
			t = s.add_table(this);
			t.init_table(tables[i]);
		}
		
		$('#load_sheet_box').hide();
	}
};

ppc_reporting_edit.prototype.set_dates_go = function() 
{
	var replace_dates, i, j, sheet, sheet_id, table, table_id, start_date, end_date;
	
	replace_dates = $f('set_dates_radio');
	if (empty(replace_dates))
	{ 
		alert('Please select which dates to replace.');
		return;
	}
	replace_dates = replace_dates.split(',');
	
	// find the dates and replace them
	for (i = 0; i < this.headers_div.children().length; ++i) 
	{
		node = $(this.headers_div.children()[i]);
		sheet_id = node.attr('sid');
		sheet = $('#rep_sheet_'+sheet_id);
		
		for (j = 0; j < sheet.children().length; ++j) 
		{
			table = $(sheet.children()[j]);
			table_id = table.attr('count');
			
			start_date = f['start_date_'+sheet_id+'_'+table_id].value;
			end_date = f['end_date_'+sheet_id+'_'+table_id].value;
			
			if (start_date == replace_dates[0] && end_date == replace_dates[1])
			{  
				f['start_date_'+sheet_id+'_'+table_id].value = f.set_dates_start.value;
				f['end_date_'+sheet_id+'_'+table_id].value = f.set_dates_end.value;
			}
		}
	}
	$('#set_dates_container').hide();
}

ppc_reporting_edit.prototype.doc_click = function(event)
{
	var target = $(event.target);
	if (target.is('.table_title') || target.is('.sheet_header')) 
		return false; 
	
	// if user clicks somewhere on doc and header options are being shown, get rid of them
	if ((div = $("#sheet_header_options")).length > 0) {
		div.remove();
	}
	if ((div = $(".sheet_table_options")).length > 0) { 
		div.remove();
	}
};

ppc_reporting_edit.prototype.init_from_saved = function()
{
	var i, j, sheet_data, sheet, table_data, t;
	
	for (i = 0; i < globals.rep_init_data.length; ++i) {
		sheet_data = globals.rep_init_data[i];
		sheet = this.add_sheet();
		
		$('#rep_header_'+i).text(sheet_data.name);
		
		for (j = 0; j < sheet_data.ppc_report_table.length; ++j) {
			table_data = sheet_data.ppc_report_table[j];
			t = sheet.add_table(this, {is_load:true});
			t.init_table(JSON.parse(table_data.definition));
		}
	}
	if (i > 1) {
		// highlight first sheet
		this.highlight_sheet($("#rep_header_0"));
	}
};

// delegate for repetitive stuff like tables and their actions
ppc_reporting_edit.prototype.set_live_events = function() {
	
	// Table data manipulation
	$(document).on('change','.market_select'       ,this,function(e){ e.data.delegate('market_change', $(this)); });
	$(document).on('change','.detail_select'       ,this,function(e){ e.data.delegate('check_detail_cols', $(this)); });
	$(document).on('change','.time_period_select'  ,this,function(e){ e.data.delegate('time_period_change', $(this)); });
	$(document).on('change','.display_type_select' ,this,function(e){ e.data.delegate('display_type_change', $(this)); });
	$(document).on('click' ,'.col_group_header'    ,this,function(e){ e.data.delegate('col_group_header_click', $(this)); });
	$(document).on('click' ,'.meta_desc_toggle'    ,this,function(e){ e.data.delegate('meta_desc_toggle_click', $(this)); });
	$(document).on('click' ,'.campaign_status'     ,this,function(e){ e.data.delegate('campaign_status_click', $(this)); });
	$(document).on('keyup' ,'.campaign_name_filter',this,function(e){ e.data.delegate('campaign_filter_keyup', $(this), e); });
	
	$(document).on('click' ,'.sort_add_button'     ,this,function(e){ e.data.delegate('sort_add', $(this)); });
	$(document).on('click' ,'.sort_reset_button'   ,this,function(e){ e.data.delegate('sort_reset', $(this)); });
	$(document).on('change','.display_type'        ,this,function(e){ e.data.delegate('table_display_type_change', $(this)); });
	$(document).on('click' ,'.checkbox_sort_sync'  ,this,function(e){ e.data.delegate('col_change_sync', $(this)); });
	
	// table options
	$(document).on('click' ,'.table_title'         ,this,function(e){ e.data.delegate('table_title_click', $(this), e); });
	$(document).on('click' ,'.sheet_table_options' ,this,function(e){ e.data.delegate('table_options_click', $(this), e); });
}

ppc_reporting_edit.prototype.delegate = function(func, src_elem, event)  
{
	var t = this.get_table(src_elem);
	if (t) {
		t[func](src_elem, event);
	}
};

ppc_reporting_edit.prototype.get_sheet = function(elem) 
{
	return this.get_selected_sheet(this.cur_header.attr('sid'));
};

ppc_reporting_edit.prototype.get_table = function(elem) 
{
	var table_key = table.get_key_from_elem(elem);
	return this.cur_sheet.get_table_by_key(table_key);
};

ppc_reporting_edit.prototype.load_template_submit = function()
{
	e2.http_get('/ppc/reporting/edit?cl_id='+location.get['cl_id']+'&tpl_id='+$('#tpl_select').val());
};

ppc_reporting_edit.prototype.save_changes_click = function()
{
	// set callback
	this.on_save_success = this.save_rep_callback.bind(this);
	this.save_report();
};

ppc_reporting_edit.prototype.save_rep_callback = function()
{
	$('#f').attr('action', $('#rep_home_link').attr('href'));
	e2.action_submit('action_rep_save_changes');
};

ppc_reporting_edit.prototype.save_template = function()
{
	this.go_set_sheet_info();
	f.action = e2.url('/ppc/reporting?cl_id='+location.get['cl_id']);
	e2.action_submit('action_rep_save_template');
};

ppc_reporting_edit.prototype.get_move_options = function(parent, child, hor_or_vert) 
{
	var num_before, num_after, move_options;
	
	num_prev = child.prevAll().length;
	num_next = child.nextAll().length;
	
	move_options = [];
	if (num_prev > 1) move_options.push("Move First");
	if (num_prev > 0) move_options.push("Move "+((hor_or_vert == "HOR") ? "Left" : "Up"));
	if (num_next > 0) move_options.push("Move "+((hor_or_vert == "HOR") ? "Right" : "Down"));
	if (num_next > 1) move_options.push("Move Last");
	
	return move_options;
};

ppc_reporting_edit.prototype.sheet_header_click = function(target)   
{
	if (target.is('.header_option')) {
		this.sheet_header_option_click(target);
	}
	else if (target.is('.sheet_header')) {
		this.highlight_sheet(target);
	}
};

ppc_reporting_edit.prototype.run_report_click = function()
{
	this.on_save_success = this.run_report_callback.bind(this);
	this.save_report();
}

ppc_reporting_edit.prototype.run_report_callback = function()
{
	$('#f').attr('action', $('#rep_home_link').attr('href'));
	e2.action_submit('action_rep_run_report');
};

// large reports could contain too much post info for browser/server
// so we save info one sheet/one table at a time
ppc_reporting_edit.prototype.save_report = function()
{
	$('#saving').show();
	$('#buttons').fadeOut(250);
	this.save_sheets();
};

ppc_reporting_edit.prototype.save_sheets = function()
{
	this.save_sheet(0);
};

ppc_reporting_edit.prototype.save_sheet = function(i)
{
	var header, $sheet,
		headers = this.headers_div.children()
	;
	// done!
	if (i === headers.length) {
		if ($.type(this.on_save_success) === 'function') {
			this.on_save_success();
		}
	}
	// save sheet
	else {
		header = $(headers[i]);
		$sheet = $('#rep_sheet_'+header.attr('sid'));

		this.post('save_sheet', {
			position:i,
			report_name:$('#report_name').val(),
			report_id:$('#rep_id').val(),
			is_template:$('#is_template').is(':checked') ? 1 : 0,
			name:header.text().trim(),
		}, this.save_sheet_callback.bind(this, $sheet));
	}
};

ppc_reporting_edit.prototype.save_sheet_callback = function($sheet, request, response)
{
	// first sheet we have saved,
	// if new report, we need to set rep id
	if (request.position == 0) {
		$('#rep_id').val(response.report_id);
	}
	$sheet.rep_data = {
		id:response.id,
		position:request.position
	};
	this.save_tables($sheet);
};

ppc_reporting_edit.prototype.save_tables = function($sheet)
{
	this.save_table($sheet, 0);
};

ppc_reporting_edit.prototype.save_table = function($sheet, i)
{
	var $table = $sheet.find('.sheet_table:eq('+i+')');

	if ($table.length == 0) {
		// remove tables from dom
		$sheet.find('.sheet_table').remove();
		this.save_sheet($sheet.rep_data.position + 1);
	}
	else {
		this.post('save_table', {
			report_id:$('#rep_id').val(),
			sheet_id:$sheet.rep_data.id,
			table_key:table.get_key_from_elem($table),
			position:i,
			definition:$table.find('input, textarea, select').serialize()
		}, this.save_table_callback.bind(this, $sheet));
	}
};

ppc_reporting_edit.prototype.save_table_callback = function($sheet, request, response)
{
	// dbg(request, response); return;
	// move on to next table
	this.save_table($sheet, request.position + 1);
};

ppc_reporting_edit.prototype.create_sheet_header = function()    
{
	var header = $('<span id="rep_header_'+this.sheet_count+'" class="sheet_header" sid="'+this.sheet_count+'">Sheet '+(this.sheet_count + 1)+'</span>');
	header.bind('click', this, function(e){ e.data.sheet_header_click($(e.target)); });
	this.headers_div.append(header);
	
	return header;
};

ppc_reporting_edit.prototype.get_selected_sheet = function(sid){ 
	var i = 0;
	for (i=0; i<this.sheets.length; i++){ 
		if (this.sheets[i].jElement.attr('id') == 'rep_sheet_' + sid)
			break;
	}
	
	return this.sheets[i];
}

ppc_reporting_edit.prototype.highlight_sheet = function(header_div)    
{
	var i, div;
	
	if (header_div.is('.on'))
	{
		this.show_header_options(header_div);
	}
	else
	{
		if (this.cur_header)
		{
			this.cur_header.removeClass('on');
		}
	
		if (this.cur_sheet)
		{
			this.cur_sheet.jElement.addClass('hide');
		}
		
		// find div header so we can get sheet body
		this.cur_header = header_div;
		this.cur_sheet = this.get_selected_sheet(this.cur_header.attr('sid'));
		
		// select header and sheet
		this.cur_header.addClass('on');
		this.cur_sheet.jElement.removeClass('hide');
	}
};


ppc_reporting_edit.prototype.get_object_name_from_user = function(type, $container, re) 
{
	var tmp, error_msg,
		max_len = arguments.length > 2 ? arguments[2] : false
	;
	
	error_msg = "";
	while (1) {
		tmp = prompt("New "+type+" Name"+error_msg+":", (tmp) ? tmp : $container.html());
		
		// user hit cancel or entered empty string
		if (!tmp) {
			return false;
		}
		else if (max_len !== false && tmp.length > max_len) {
			error_msg = " (Max "+max_len+" characters (currently "+tmp.length+"))";
		}
		else if (!re.test(tmp)) {
			error_msg = " (Only letters, numbers, spaces and dashes)";
		}
		else {
			$container.html(tmp);
			return true;
		}
	}
};

ppc_reporting_edit.prototype.show_header_options = function(header_div) 
{
	var div, ml, ml_delete, ml_move, i, move_options;
	
	// check if options are already there
	div = $("#sheet_header_options");
	if (div.length > 0)
	{ 
		header_div.remove(div);
		return;
	}
	
	// is delete an option?
	ml_delete = (this.headers_div.children().length > 1) ? '<p class="header_option">Delete</p>' : '';
	
	// see where this sheet can be moved
	move_options = this.get_move_options(this.headers_div, header_div, "HOR");
	if (move_options.length == 0)
	{
		ml_move = '';
	}
	else
	{
		ml_move = '<div class="header_option_spacer"></div>';		
		for (i = 0; i < move_options.length; ++i)
		{
			ml_move += '<p class="header_option">'+move_options[i]+'</p>';
		}
	}
	ml = '\
		<div id="sheet_header_options">\
			<p class="header_option">Rename</p>\
			<p class="header_option">Copy</p>\
			'+ml_delete+'\
			'+ml_move+'\
		</div>\
	';
	header_div.append(ml);
};

ppc_reporting_edit.prototype.sheet_header_option_click = function(option)  
{
	var func = 'sheet_action_'+option.text().simple_text();
	
	// don't need to show menu anymore
	$("#sheet_header_options").remove();
	
	this[func]();
};


ppc_reporting_edit.prototype.copy_table_to_new_sheet = function(src_table, dst_sheet)     
{
	var dst_table = new table(this, dst_sheet);
	dst_sheet.tables.push(dst_table);
	dst_table.init_table(src_table.table_to_obj(), dst_table.key);
};

ppc_reporting_edit.prototype.sheet_action_rename = function()  
{
	this.cur_sheet.rename();
};

ppc_reporting_edit.prototype.sheet_action_copy = function()  
{
	var src_sheet_name, src_sheet, src_tables, src_table_key, src_table;
	
	src_sheet = this.cur_sheet;
	src_sheet_name = this.cur_header.text();
	
	// add_sheet sets this.cur_sheet to new sheet
	this.add_sheet();
	this.cur_header.text("Copy of "+src_sheet_name);
	
	for (src_tables = src_sheet.jElement.children(), i = 0; i < src_tables.length; ++i)
	{
		src_table_key = window.table.get_key_from_elem($(src_tables[i]));
		src_table = src_sheet.get_table_by_key(src_table_key);
		this.copy_table_to_new_sheet(src_table, this.cur_sheet);
	}
};

ppc_reporting_edit.prototype.sheet_action_delete = function()  
{
	var sheet_index
	// make sure there are at least two sheets
	if (this.get_sheet_count() == 1)
	{
		alert("Can't delete the last sheet!");
		return;
	}
	// delete the current sheet and 
	if (confirm("Delete "+ this.cur_header.text()+"?"))
	{ 
		this.cur_header.remove();
		this.cur_sheet.jElement.remove();
		
		// remove the table from the sheet object
		sheet_index = this.cur_sheet.get_sheet_index_in_report();
		this.sheets.splice(sheet_index, 1);
			
		// set them to null
		this.cur_header = null;
		this.cur_sheet = null;
		
		// get first sheet header and highlight it
		this.highlight_sheet($(this.headers_div.children()[Math.max(sheet_index - 1, 0)]));
	}
};

ppc_reporting_edit.prototype.sheet_action_move_first = function()  
{
	this.move_object(this.cur_header, 'FIRST');
};

ppc_reporting_edit.prototype.sheet_action_move_left = function()  
{
	this.move_object(this.cur_header, 'LEFT');
};

ppc_reporting_edit.prototype.sheet_action_move_right = function()  
{
	this.move_object(this.cur_header, 'RIGHT');
};

ppc_reporting_edit.prototype.sheet_action_move_last = function()  
{
	this.move_object(this.cur_header, 'LAST');
};

ppc_reporting_edit.prototype.move_object = function(src_elem, dir)  
{
	switch (dir.toUpperCase()) 
	{
		case ('FIRST'):
			src_elem.insertBefore(src_elem.prevAll(':first-child'));
			break;
		
		case ('LEFT'):
		case ('UP'):
			$.elem_swap(src_elem, src_elem.prev());
			break;
		
		case ('RIGHT'):
		case ('DOWN'):
			$.elem_swap(src_elem, src_elem.next());
			break;
		
		case ('LAST'):
			src_elem.insertAfter(src_elem.nextAll(':last-child'));
			break;
	}
};


ppc_reporting_edit.prototype.init_detail_cols = function()  
{
	this.detail_cols = [];
	for (col in window.g_all_cols) {  
		col_info = window.g_all_cols[col];
		if (col_info.group == 'detail') {
			this.detail_cols.push($.extend({
				key:col,
				// all is used for market for reasons lost to history
				detail:(col == 'market') ? 'all' : col
			}, col_info));
		}
	}
};

ppc_reporting_edit.prototype.get_sheet_count = function() 
{
	return this.headers_div.children().length;
};

ppc_reporting_edit.prototype.add_sheet = function()   
{
	var s, header;
	
	if (this.sheet_count == 20)
	{
		alert("20 sheet max!!");
		return;
	}

	s = new sheet(this);
	this.sheets.push(s);
	this.sheets_div.append(s.jElement);
	
	header = this.create_sheet_header();
	this.highlight_sheet(header);
	
	// increment sheet count
	this.sheet_count++;	
	
	return s;
};
