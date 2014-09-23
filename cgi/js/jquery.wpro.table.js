
(function($){
	
	var defaults = {
		'cols':null,
		'data':null,

		'class':'wpro_table',
		'wrapper_class':'wpro_table_wrapper',
		'nav_class':'wpro_table_nav',
		'clear_float':true,

		'sort_col':-1,
		'sort_dir':'',
		'sort_init':null,
		'sort_dir_init':null,

		'id_key':null,
		'id_key_tr_attr':'i',

		'id_link':null,
		'id_link_href':null,

		'page_start':0,
		'page_size':25,

		'show_row_number':true,
		'show_totals':true,
		'show_totals_top':false,
		'totals_func':false,
		'show_nav':false,
		'show_download':false,

		// user supplied callback which receives table body, data, sort_col
		'post_render':false
	};
	
	function Table(settings, _src)
	{
		var self = this;
		this.wrapper = null;
		this.table = null;
		this.tbody = null;
		this.thead = null;
		this.tfoot = null;
		
		this.col_data_types = [];
		this.col_map = {};
		this.totals = [];
		if (settings)
		{
			$.extend(this, defaults, settings);
		}
		
		this.init_cols_and_sort();
		this.init_col_types_and_data();
		
		// we're a table, use this, wrap
		if (_src.is('table'))
		{
			this.table = _src;
			this.table.wrap('<div class="'+this.wrapper_class+'" />');
		}
		// otherwise assume we were given a wrapper, put the table in there
		else
		{
			_src.addClass(this.wrapper_class);
			_src.html('<table></table>');
			this.table = _src.find('table');
		}
		this.wrapper = this.table.closest('div');
		if (this.clear_float)
		{
			this.wrapper.after('<div class="clr" />');
		}
		if (empty(this.data))
		{
			this.table.html('<tbody><tr><td><strong>No Data</strong></td></tr></tbody>');
			return;
		}

		if (this.table.attr('id'))
		{
			window.globals[this.table.attr('id')+'_data'] = this.data;
		}

		// we have columns, draw table head
		if (this.cols)
		{
			this.draw_head();
			this.table.find('thead a').bind('click', self, function(e){ e.data.click_thead_a($(this)); return false; });
		}
		if (this.show_totals)
		{
			this.draw_foot();
		}
		this.sort();
		this.draw_body();
		if (this.show_nav)
		{
			this.draw_nav();
			this.max_width = this.table.width();
			this.table.width(this.max_width);
		}
		if (this.show_download)
		{
			this.draw_download();
		}

		this.table.addClass(this.class);
	}
	
	Table.prototype.click_thead_a = function(a)
	{
		a.blur();

		this.tbody.find('tr').find('td:nth('+(this.sort_td_index)+')').removeClass('on');
		this.sort_td_index = $.index(a.closest('th'));
		this.set_sort({is_header_click:1});
		this.sort();

		if (this.show_nav)
		{
			this.nav.find('select').find('option:first-child').attr('selected', 1);
			this.nav_page_change();
		}
		else
		{
			this.re_draw_body();
		}
	};
	
	Table.prototype.nav_page_change = function()
	{
		this.tbody.empty();
		this.page_start = Number(this.nav.find('select').val());
		this.table.css('width', null);
		this.draw_body();
		this.nav_show_arrows();
		if (this.table.width() > this.max_width)
		{
			this.max_width = this.table.width();
		}
		this.table.width(this.max_width);
	};
	
	Table.prototype.re_draw_body = function()
	{
		var i, d, row, on_class, off_class;

		for (i = 0; i < this.data.length; ++i)
		{
			d = this.data[i];
			row = this.tbody.find('tr['+this.id_key_tr_attr+'="'+d[this.id_key]+'"]');

			row.detach();
			if (this.show_row_number)
			{
				row.find('td:first-child').text(String(i + 1));
			}
			if (i & 1)
			{
				on_class = 'odd';
				off_class = 'even';
			}
			else
			{
				on_class = 'even';
				off_class = 'odd';
			}
			if (!row.hasClass(on_class))
			{
				row.removeClass(off_class);
				row.addClass(on_class);
			}
			this.tbody.append(row);
		}
		this.tbody.find('tr').find('td:nth('+(this.sort_td_index)+')').addClass('on');
		this.post_render_callback();
	};
	
	Table.prototype.post_render_callback = function()
	{
		if (this.post_render) {
			this.post_render(this.tbody, this.data, this.sort_col.key);
		}
	};

	Table.prototype.sort = function()
	{
		var sort_dir, sort_col;

		sort_col = this.sort_col;
		sort_dir = this.sort_dir;
		if (sort_col.type == 'numeric')
		{
			cmp = function(a, b)
			{
				var a_val, b_val, dif;

				a_val = Types.to_number(a[sort_col.key]);
				b_val = Types.to_number(b[sort_col.key]);
				if (isNaN(a_val))
				{
					dif = -1;
				}
				else if (isNaN(b_val))
				{
					dif = 1;
				}
				else
				{
					dif = a_val - b_val;
				}
				return ((sort_dir == 'asc') ? dif : (dif * -1));
			};
		}
		else
		{
			if (sort_dir == 'asc')
			{
				cmp = function(a, b)
				{
					var a_val, b_val;

					a_val = (a[sort_col.key]) ? a[sort_col.key] : '~~~';
					b_val = (b[sort_col.key]) ? b[sort_col.key] : '~~~';
					return ((a_val > b_val) ? 1 : -1); 
				};
			}
			else
			{
				cmp = function(a, b) { return ((b[sort_col.key] > a[sort_col.key]) ? 1 : -1); };
			}
		}

		this.data.sort(cmp);
	};
	
	Table.prototype.get_totals_row_ml = function()
	{
		var ml_tr;

		if (this.totals_func) {
			this.totals_func(this.totals);
		}

		ml_tr = (this.show_row_number) ? '<td></td>' : '';
		ml_tr += this.draw_row(this.totals, {is_totals:1});
		return '<tr class="totals">'+ml_tr+'</tr>';
	};
	
	Table.prototype.draw_body = function()
	{
		var i, d, ml, ml_tr, classes, col;

		this.tbody = this.table.find('tbody');
		if (this.tbody.length == 0)
		{
			this.table.append('<tbody></tbody>');
			this.tbody = this.table.find('tbody');
		}

		if (this.show_nav)
		{
			this.page_end = Math.min(this.page_start + this.page_size, this.data.length);
		}
		else
		{
			this.page_start = 0;
			this.page_end = this.data.length;
		}
		this.all_pages_visible = (this.page_start == 0 && this.page_end == this.data.length);

		ml = '';
		for (i = this.page_start; i < this.page_end; ++i)
		{
			d = this.data[i];
			ml_tr = (this.show_row_number) ? '<td>'+(i + 1)+'</td>' : '';
			ml_tr += this.draw_row(d);

			classes = (d._classes_) ? d._classes_ : [];
			classes.push('wpro_table_tr');
			classes.push((i & 1) ? 'odd' : 'even');
			ml += '<tr '+this.id_key_tr_attr+'="'+d[this.id_key]+'" class="'+classes.join(' ')+'">'+ml_tr+'</tr>';
		}
		this.tbody.html(ml);
		this.tbody.find('tr').find('td:nth('+(this.sort_td_index)+')').addClass('on');

		if (this.show_row_number)
		{
			this.tbody.find('tr').find('td:first').addClass('r');
		}

		if (this.click_tr)
		{
			this.tbody.find('tr').bind('click', this, function(e){ e.data.click_tr_wrapper(e); });
		}

		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			if (col.click)
			{
				this.tbody.find('tr td:nth-child('+this.col_index_to_nth(i)+')').bind('click', this, function(e){ e.data.click_td_wrapper(e); });
			}
		}
		this.post_render_callback();
	};
	
	Table.prototype.click_tr_wrapper = function(e)
	{
		var tr;

		tr = $(e.currentTarget);
		this.click_tr(e, tr, this.data[this.page_start + $.index(tr)]);
	};
	
	Table.prototype.click_td_wrapper = function(e)
	{
		var td, i, col, func, self;

		td = $(e.currentTarget);
		col = this.cols[this.cell_index_to_col($.index(td))];
		if (Types.is_function(col.click))
		{
			func = col.click;
		}
		else if (window[col.click])
		{
			func = window[col.click];
		}

		if (func)
		{
			func(e, td, this, this.data[this.page_start + $.index(td.closest('tr'))]);
		}
	};
	
	Table.prototype.col_index_to_nth = function(i)
	{
		return (i + ((this.show_row_number) ? 2 : 1));
	};
	
	Table.prototype.cell_index_to_col = function(i)
	{
		return (i - ((this.show_row_number) ? 1 : 0));
	};

	Table.prototype.draw_nav = function()
	{
		var ml, page_options, i;

		if (this.all_pages_visible)
		{
			this.show_nav = false;
			return;
		}

		page_options = [];
		for (i = 0; i < this.data.length; i += this.page_size)
		{
			page_options.push([i, (i + 1)+' - '+Math.min(i + this.page_size, this.data.length)]);
		}

		ml = '\
			<div class="'+this.nav_class+'_back">\
				<a href=""> &lt;&lt; </a> &nbsp;\
				<a href=""> &lt; </a>\
			</div>\
			<div class="'+this.nav_class+'_page_select">\
				'+html.select(this.nav_class+'_page', page_options, this.page_start)+'\
				<span>of '+this.data.length+'</span>\
			</div>\
			<div class="'+this.nav_class+'_forward">\
				<a href=""> &gt; </a> &nbsp;\
				<a href=""> &gt;&gt; </a>\
			</div>\
		';

		this.wrapper.prepend('\
			<div class="'+this.nav_class+'">'+ml+'</div>\
			<div class="clr"></div>\
		');
		this.nav = this.wrapper.find('.'+this.nav_class);
		this.nav_show_arrows();
		this.nav.find('select').bind('change', this, function(e){ e.data.nav_page_change(); });
		this.nav.find('a').bind('click', this, function(e){ e.data.nav_arrow_click(e); return false; });
	};

	Table.nav_arrow_click = function(e)
	{
		var a, select, is_forward, is_one_page, new_index;

		a = $(e.currentTarget);
		a.blur();
		select = this.nav.find('select');

		is_forward = a.closest('div').attr('class').match(/forward$/);
		is_one_page = ($.index(a) == ((is_forward) ? 0 : 1));
		if (is_one_page)
		{
			new_index = $.index(select.find('option:selected')) + ((is_forward) ? 1 : -1);
		}
		else
		{
			new_index = (is_forward) ? (select.find('option').length - 1) : 0;
		}
		select.find('option:nth-child('+(new_index + 1)+')').attr('selected', 1);
		this.nav_page_change();
	};
	
	Table.nav_show_arrows = function()
	{
		if (this.page_start == 0)
		{
			this.nav.find('.'+this.nav_class+'_back').hide();
		}
		else
		{
			this.nav.find('.'+this.nav_class+'_back').show();
		}
		if ((this.page_start + this.page_size) >= this.data.length)
		{
			this.nav.find('.'+this.nav_class+'_forward').hide();
		}
		else
		{
			this.nav.find('.'+this.nav_class+'_forward').show();
		}
	};

	Table.prototype.draw_download = function()
	{
		var i, j, d, col, val, csv, row, data_link;

		csv = '';

		// headers
		row = [];
		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			row.push(col.display);
		}
		csv += row.join(',')+"\n";

		// body
		for (i = 0; i < this.data.length; ++i)
		{
			d = this.data[i];
			row = [];
			for (j = 0; j < this.cols.length; ++j)
			{
				col = this.cols[j];
				val = d[col.key];

				if (col.format)
				{
					if (Types.is_function(col.format))
					{
						val = col.format(val, d);
					}
					else if (Format[col.format])
					{
						val = Format[col.format](val, d);
					}
					else if (window[col.format])
					{
						val = window[col.format](val, d);
					}
				}

				// if still undefined, set to empty string
				if (typeof(val) == 'undefined')
				{
					val = '';
				}
				row.push('"'+String(val).replace(/,/g, '\,')+'"');
			}
			csv += row.join(',')+"\n";
		}

		// add link to page
		this.wrapper.prepend('\
			<div>\
				<a class="table_download_link" download="'+(Types.is_string(this.show_download) ? this.show_download : 'download')+'.csv" href="data:application/octet-stream;charset=utf-8,'+escape(csv)+'">Download</a>\
			</div>\
		');
	};
	
	Table.prototype.draw_foot = function()
	{
		this.tfoot = this.table.find('tfoot');
		if (this.tfoot.length == 0)
		{
			this.table.append('<tfoot></tfoot>');
			this.tfoot = this.table.find('tfoot');
		}

		this.tfoot.html(this.get_totals_row_ml());
	};
	
	Table.prototype.draw_head = function()
	{
		var i, ml, ml_tr, ml_th, col;

		this.thead = this.table.find('thead');
		if (this.thead.length == 0)
		{
			this.table.append('<thead></thead>');
			this.thead = this.table.find('thead');
		}

		ml_tr = (this.show_row_number) ? '<th></th>' : '';
		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			if (col.key == '__checkbox')
			{
				ml_th = '<th>'+col.display+'</th>';
			}
			else
			{
				ml_th = '<th><a href="'+col.key+'">'+col.display+'</a></th>';
			}

			ml_tr += ml_th;
		}
		ml = '<tr>'+ml_tr+'</tr>';
		if (this.show_totals_top)
		{
			ml += this.get_totals_row_ml();
		}
		this.thead.html(ml);
		if ($.empty(this.thead.find('tr th.on')))
		{
			this.thead.find('tr th:nth('+(this.sort_td_index)+')').addClass('on');
		}
		if (this.checkboxes)
		{
			this.thead.find('input[type="checkbox"]').bind('click', this, function(e){ e.data.click_checkbox_head($(this)); });
		}
	};
	
	Table.prototype.click_checkbox_head = function(master_cbox)
	{
		var cboxes, i, cbox, col, td;

		// get the cbox col
		col = null;
		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			if (col.key == '__checkbox')
			{
				break;
			}
		}
		if (!col)
		{
			return;
		}

		cboxes = this.tbody.find('input[type="checkbox"]');
		for (i = 0; i < cboxes.length; ++i)
		{
			cbox = $(cboxes[i]);
			if ((cbox.is(':checked') && !master_cbox.is(':checked')) || (!cbox.is(':checked') && master_cbox.is(':checked')))
			{
				cbox.attr('checked', master_cbox.is(':checked'));
				td = cbox.closest('td');
				col.click(null, td, this, this.data[this.page_start + $.index(td.closest('tr'))]);
			}
		}
	};
	
	Table.prototype.draw_row = function(d)
	{
		var ml, i, col, val, classes;

		ml = '';
		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			val = d[col.key];

			if (d['_display_'+col.key])
			{
				val = d['_display_'+col.key];
			}
			else if (col.format && !(col.type == 'alpha' && arguments[1] && arguments[1].is_totals))
			{
				if (Types.is_function(col.format))
				{
					val = col.format(val, d);
				}
				else if (Format[col.format])
				{
					val = Format[col.format](val, d);
				}
				else if (window[col.format])
				{
					val = window[col.format](val, d);
				}
			}

			// if still undefined or null, set to empty string
			if (typeof(val) == 'undefined' || val == null)
			{
				val = '';
			}

			classes = [];
			if (col.type == 'numeric')
			{
				classes.push('r');
			}
			if (col.classes)
			{
				classes.push(col.classes);
			}
			ml += '<td'+((classes) ? (' class="'+classes.join(' ')+'"') : '')+'>'+val+'</td>';
		}
		return ml;
	};
	
	Table.prototype.set_data_ids = function()
	{
		var i, d;

		// user provided id key, don't need to do anything
		if (this.id_key != null)
		{
			return;
		}
		// generate random, unused key for row IDs
		this.id_key = '_'+this.rand_char();
		d = this.data[0];
		while(1)
		{
			if (Types.is_undefined(d[this.id_key]))
			{
				break;
			}
			this.id_key += this.rand_char();
		}
		for (i = 0; i < this.data.length; ++i)
		{
			this.data[i][this.id_key] = i;
		}
	};
	
	Table.prototype.total_hidden_cols = function()
	{
		var i, j, d, hidden_cols, col_key, val;

		hidden_cols = [];
		d = this.data[0];
		for (i in d)
		{
			if (Types.is_undefined(this.col_map[i]))
			{
				hidden_cols.push(i);
				this.totals[i] = 0;
			}
		}

		// there can be columns involved in calculations that we aren't displaying, total them as well
		for (i = 0; i < this.data.length; ++i)
		{
			d = this.data[i];
			for (j = 0; j < hidden_cols.length; ++j)
			{
				col_key = hidden_cols[j];
				val = d[col_key];

				if (Types.is_numeric(val))
				{
					this.totals[col_key] += Types.to_number(val);
				}
			}
		}
	};
	
	Table.prototype.rand_char = function()
	{
		return String.fromCharCode(this.rand_int(97, 122));
	};

	Table.prototype.rand_int = function(min, max)
	{
		return (min + Math.floor(Math.random() * (max - min + 1)));
	};
	
	Table.prototype.init_col_types_and_data = function()
	{
		var i, j, col, d, val, is_numeric, found_alpha;

		if (empty(this.data))
		{
			return;
		}

		// determine type of cols
		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			is_numeric = true;
			for (j = 0; j < this.data.length; ++j)
			{
				d = this.data[j];
				//if (col.type == 'numeric' && isNaN(d[col.key]))
				//{
					//d[col.key] = 0;
				//}
				val = d[col.key];

				if (col.calc)
				{
					val = (Types.is_function(col.calc)) ? col.calc(d, col.key) : window[col.calc](d, col.key);
					d[col.key] = val;
				}

				if (!Types.is_numeric(val) && !Types.is_undefined(val))
				{
					is_numeric = false;
				}
				else if (!col.totals_val)
				{
					this.totals[col.key] += (isNaN(Types.to_number(val))) ? 0 : Types.to_number(val);
				}

				if (this.id_link_href && this.id_link == col.key) {
					d['_display_'+col.key] = '<a target="_blank" href="'+this.id_link_href+'?id='+val+'">'+val+'</a>';
				}
			}
			if (!col.type)
			{
				col.type = (is_numeric) ? 'numeric' : 'alpha';
			}
		}
		this.set_data_ids();
		this.total_hidden_cols();

		// 1. run any col calculations on totals
		// 2. set "Total" text for first alpha col, '-' for other alpha cols
		found_alpha = false;
		for (i = 0; i < this.cols.length; ++i)
		{
			col = this.cols[i];
			if (col.totals_val)
			{
				this.totals[col.key] = col.totals_val;
			}
			else if (col.calc)
			{
				this.totals[col.key] = (Types.is_function(col.calc)) ? col.calc(this.totals, col.key) : window[col.calc](this.totals, col.key);
			}
			if (col.type == 'alpha')
			{
				if (found_alpha)
				{
					this.totals[col.key] = '-';
				}
				else
				{
					this.totals[col.key] = 'Totals';
					found_alpha = true;
				}
			}
		}
	};
	
	Table.prototype.init_cols_and_sort = function()
	{
		var i, col, func;

		if (empty(this.data))
		{
			return;
		}
		if (!this.cols)
		{
			this.create_dummy_cols();
		}
		this.sort_td_index = undefined;
		for (i = 0; i < this.cols.length; ++i)
		{
			col = new Column(this.cols[i]);

			if (this.sort_init == col.key)
			{
				this.sort_td_index = i + ((this.show_row_number) ? 1 : 0);
			}

			this.cols[i] = col;
			this.col_map[col.key] = i;
			this.totals[col.key] = 0;
		}
		if (this.checkboxes)
		{
			this.cols.push(new Column({
				'key':'__checkbox',
				'display':'<input type="checkbox" name="__cbox_head" />',
				'format':function(data){ return '<input type="checkbox" />'; },
				'click':this.checkboxes
			}));
		}
		if (Types.is_undefined(this.sort_td_index))
		{
			this.sort_td_index = (this.show_row_number) ? 1 : 0;
		}
		this.set_sort({'sort_dir':this.sort_dir_init});
	};
	
	Table.prototype.set_sort = function()
	{
		this.sort_col = this.cols[this.sort_td_index - ((this.show_row_number) ? 1 : 0)];
		if (this.thead && this.thead.find('tr th:nth('+(this.sort_td_index)+')').is('.on')) {
			this.sort_dir = (this.sort_dir == 'asc') ? 'desc' : 'asc';
		}
		else {
			if (this.thead) {
				this.thead.find('tr th.on').removeClass('on');
				this.thead.find('tr th:nth('+(this.sort_td_index)+')').addClass('on');
			}
			if (arguments[0] && arguments[0].sort_dir) {
				this.sort_dir = arguments[0].sort_dir;
			}
			else {
				this.sort_dir = (this.sort_col.type == 'numeric') ? 'desc' : 'asc';
			}
		}
	};
	
	/*
	 * Create table columns based off the data object keys
	 */
	Table.prototype.create_dummy_cols = function()
	{
		var i, d, col;

		d = this.data[0];
		this.cols = [];
		for (i in d)
		{
			i = String(i);
			if (i.charAt(0) == '_')
			{
				continue;
			}
			col = {'key':i,'display':i.display_text()};
			if (this.col_meta && this.col_meta[i])
			{
				$.extend(col, this.col_meta[i]);
			}
			this.cols.push(col);
		}
	};
	
	$.fn.table = function(settings)
	{
		var self = this;
		this.each(function(){
			self.data('table', new Table(settings, $(this)));
		});
		return this;
	};
	
	/*
	 * public methods to get/set data for a row.
	 * can pass in any child jquery element of the row
	 */
	Table.prototype.get_row_data = function(elem)
	{
		var row = (elem.is('tr')) ? elem : elem.closest('tr'),
			row_index = row.prevAll('.wpro_table_tr').length;

		return this.data[row_index];
	};
	
	Table.prototype.set_row_data = function(elem, key, val)
	{
		var row = (elem.is('tr')) ? elem : elem.closest('tr'),
			row_index = row.prevAll().length;

		this.data[row_index][key] = val;
	};

	Table.prototype.get_column = function(elem)
	{
		var th = (elem.is('th')) ? elem : elem.closest('th'),
			col_index = th.prevAll().length - (this.show_row_number ? 1 : 0);

		return this.cols[col_index];
	};
	
	Table.prototype.get_sort_dir = function()
	{
		return this.sort_dir;
	};
	
	/* Column Object */
	function Column(settings)
	{
		if (!Types.is_object(settings))
		{
			settings = {'key':settings,'display':settings.display_text()};
		}
		this.key = settings.key;
		this.display = (Types.is_defined(settings.display)) ? settings.display : this.key.display_text();
		this.type = settings.type || null;
		this.classes = settings.classes || null;
		this.format = settings.format || null;
		this.calc = settings.calc || null;
		this.totals_val = settings.totals_val || null;
		this.click = settings.click || null;
	}

})(jQuery);
