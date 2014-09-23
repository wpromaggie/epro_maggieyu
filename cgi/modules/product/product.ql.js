
function ql_spend()
{
	// mouse movements
	this.mx = this.my = 0;
	
	// for multiple row stuff
	this.multi_i1 = this.multi_i2 = null;
	
	// highlights columns
	this.is_col_hl_on = false;
	
	// for details boxes
	this.z_count = 0;
	
	// number cells align right
	this.right_align_cell_numbers('td');
	
	$('#spend_headers_row a').bind('click', this, function(e){ e.data.header_click($(this)); return false; });
	
	//$('#spend_tbody td.multi_button').click(function(event) { multi_button_click(event); event.stopPropagation(); });
	
	//$('#spend_tbody tr').bind('click', this, function(e){ e.data.row_click(e, $(this)); });
	$('#data_start').change(function(event) { $('#f').submit(); });
	$('#budget').change(function(event) { $('#f').submit(); });
	$(document).bind('keyup', this, function(e){ e.data.keyup(e); });
	$(document).bind('mousemove', this, function(e){ e.data.mousemove(e); });
	
	$('#url_search').bind('keydown', this, function(e){ return e.data.url_search_keydown(e); });
	
	this.init_hl_cols();
}

ql_spend.prototype.row_click = function(e, row)
{
	e2.new_window('/sbs/ql/url/spend/?url_id='+row.attr('url_id')+'&start_date='+$f('start_date')+'&end_date='+$f('end_date'));
};


ql_spend.prototype.url_search_keydown = function(e)
{
	// capture enter and submit a url search
	if (e.which == 13)
	{
		e2.action_submit('url_search');
		return false;
	}
	return e.which;
}

ql_spend.prototype.init_hl_cols = function()
{
	var cols, i;
	
	cols = cookie.get('ql_spend_cols');
	if (!cols) return;
	
	cols = cols.split(',');
	for (i = 0; i < cols.length; ++i)
	{
		this.highlight_column(cols[i]);
	}
};

// register mouse movements for keyup
ql_spend.prototype.mousemove = function(e)
{
	this.mx = e.pageX;
	this.my = e.pageY;
};

ql_spend.prototype.set_col_hl_mode = function(action)
{
	switch (action)
	{
		case ('toggle'): this.is_col_hl_on = !this.is_col_hl_on; break;
		case ('on'):     this.is_col_hl_on = true; break;
		case ('off'):    this.is_col_hl_on = false; break;
	}
};

ql_spend.prototype.keyup = function(e)
{
	// control key.. because this is keyup cannot use e.ctrlKey
	if (e.which == 17)
	{
		this.set_col_hl_mode('toggle');
		$('body').append('<div class="spend_ctrl_click" style="z-index:1024;position:absolute;top:'+this.my+'px;left:'+this.mx+'px;">HL '+((this.is_col_hl_on) ? 'On' : 'Off')+'</div>');
		$('div.spend_ctrl_click').fadeOut(1000);
	}
};


ql_spend.prototype.header_click = function(a)
{
	var col, cur_sort, cur_sort_dir;
	
	a.blur();
	
	// shift click highlights the column?!
	if (this.is_col_hl_on)
	{
		this.highlight_column(Number(a.closest('th')[0].cellIndex) + 1);
	}
	else
	{
		col = a.attr("col");
		
		cur_sort = $("#sort").val();
		cur_sort_dir = $("#sort_dir").val()
		if (col == cur_sort)
		{
			$("#sort_dir").val((cur_sort_dir == "asc") ? "desc" : "asc");
		}
		else
		{
			$("#sort").val(col);
			$("#sort_dir").val("desc");
		}
		$("#f").submit();
	}
};

ql_spend.prototype.highlight_column = function(index)
{
	var cols_str, cols, i;
	
	if ($('tbody#spend_tbody tr:first td:nth-child('+(index)+')').hasClass('col_highlight'))
	{
		$('tr#spend_headers_row th:nth-child('+(index)+')').attr('col_hl', 0);
		$('tbody#spend_tbody tr td:nth-child('+(index)+')').removeClass('col_highlight');
	}
	else
	{
		$('tr#spend_headers_row th:nth-child('+(index)+')').attr('col_hl', 1);
		$('tbody#spend_tbody tr td:nth-child('+(index)+')').addClass('col_highlight');
	}
	
	cols_str = '';
	cols = $('tr#spend_headers_row th[col_hl=1]');
	for (i = 0; i < cols.length; ++i)
	{
		if (cols_str != '') cols_str += ',';
		cols_str += Number(cols[i].cellIndex) + 1;
	}
	cookie.set('ql_spend_cols', cols_str);
};

ql_spend.prototype.right_align_cell_numbers = function(selector)
{
	var i, cell;
	
	cells = $(selector);
	for (i = 0; i < cells.length; ++i)
	{
		cell = $(cells[i]);
		if (cell.text() != "" && !isNaN(cell.text().replace("$", ""))) cell.addClass("r");
	}
}
