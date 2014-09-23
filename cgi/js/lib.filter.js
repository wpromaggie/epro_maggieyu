
function filter_init(table_key)
{
	return '<div id="filter_row_'+table_key+'_0_0" class="filter_row">'+filter_init_ml(table_key+'_0_0')+'</div>';
}

function filter_init_ml(filter_key)
{
	var key_parts, col, col_info, ml_filter, ml_filter_col, ml_filter_cmp, ml_filter_val, ml_filter_links, filter_compare, option, i, filter_or_test, keyword_filter_cols;
	
	ml_filter_col = '<select class="filter_col" name="filter_col_'+filter_key+'" id="filter_col_'+filter_key+'">';
	
	keyword_filter_cols = [
		['pos', 'Ave Pos'],
		['status', 'Status'],
		['bid', 'Bid'],
		['clicks', 'Clicks'],
		['imps', 'Imps'],
		['cpc', 'CPC'],
		['cost', 'Cost'],
		['convs', 'Convs'],
		['cost_conv', 'Cost/Conv']
	];
	
	if (globals.data_cols) {
		for (i = 0; i < globals.data_cols.length; ++i) {
			if (globals.data_cols[i].key == 'revenue') {
				keyword_filter_cols.push(['revenue', 'Revenue']);
				keyword_filter_cols.push(['roas', 'ROAS']);
				break;
			}
		}
	}

	// separate input for campaign
	else if (globals.pages && globals.pages[1] == 'reporting')
	{
		//keyword_filter_cols.push(['campaign', 'Campaign']);
		keyword_filter_cols.push(['headline', 'Headline']);
		keyword_filter_cols.push(['description', 'Description']);
		keyword_filter_cols.push(['disp_url', 'Display URL']);
		keyword_filter_cols.push(['dest_url', 'Destination URL']);
	}
	
	for (i = 0; i < keyword_filter_cols.length; ++i)
	{
		col_info = keyword_filter_cols[i];
		ml_filter_col += '<option value="'+col_info[0]+'">'+col_info[1]+'</option>';
	}
	ml_filter_col += '</select>';
	
	filter_compare = [
		["gt", "Greater Than"],
		["lt", "Less Than"],
		["eq", "Equal To"],
		["ne", "Not Equal To"],
		["contains", "Contains"],
		["not_contains", "Not Contains"]
	];
	
	ml_filter_cmp = '<select class="filter_cmp" id="filter_cmp_'+filter_key+'" name="filter_cmp_'+filter_key+'">';
	for (i = 0; i < filter_compare.length; ++i)
	{
		option = filter_compare[i];
		ml_filter_cmp += '<option value="'+option[0]+'">'+option[1]+'</option>';
	}
	ml_filter_cmp += '</select>';
	
	ml_filter_val = '<div class="lft"><input class="filter_val" type="text" id="filter_val_'+filter_key+'" name="filter_val_'+filter_key+'" autocomplete="off" /></div>';
	
	ml_filter_links  = '<div class="filter_links">';
	ml_filter_links += '<a href="" onclick="filter_and(\''+filter_key+'\'); return false;">AND</a> ';
	
	// we don't need the "OR" link if this isn't the last row
	// test if there is another OR block by incrementing OR of current key parts
	key_parts = filter_get_key_parts(filter_key);
	filter_or_test = 'filter_val_'+key_parts.table_key+'_'+(Number(key_parts.or_index) + 1)+'_0';
	
	if ($.empty('#'+filter_or_test))
	{
		ml_filter_links += '<a href="" class="filter_or_link" onclick="filter_or(\''+filter_key+'\'); return false;">OR</a>';
	}
	
	ml_filter_links += '</div>';
	
	return ml_filter_col + ml_filter_cmp + ml_filter_val + ml_filter_links;
}

function filter_toggle_option(is_on, table_key, option_val)
{
	if (is_on)
	{
		filter_add_option(table_key, option_val);
	}
	else
	{
		filter_remove_option(table_key, option_val);
	}
}

function filter_add_option(table_key, new_option_val)
{
	var i, select, option, container = $('#filter_container_'+table_key),
		col_selects = container.find('select.filter_col');
	
	for (i = 0; i < col_selects.length; ++i)
	{
		select = $(col_selects[i]);
		option = select.find('option[value="'+new_option_val+'"]');
		if (option.length == 0)
		{
			select.append('<option value="'+new_option_val+'">'+new_option_val.display_text()+'</option>');
		}
	}
}

function filter_remove_option(table_key, bad_option_val)
{
	var container = $('#filter_container_'+table_key),
		col_selects = container.find('select.filter_col');
	
	col_selects.find('option[value="'+bad_option_val+'"]').remove();
}

// filter is ||'s of &&'s
function filter_init_obj(table_key, ors)
{
	var i, j, ands, filter, col_input;
	
	if (!ors) return;
	for (i = 0; i < ors.length; ++i)
	{
		ands = ors[i];
		// first filter is added when table is created, need to add subsequent filter rows
		// since i is greater than 0, we can use j from the previous iteration to get the final filter row
		if (i > 0)
		{
			filter_or(table_key+'_'+(i-1)+'_'+(j-1));
		}
		for (j = 0; j < ands.length; ++j)
		{
			filter = ands[j];
			filter_key = table_key+'_'+i+'_'+j;
			
			// not part of filter anymore!
			if (filter.col == 'campaign') continue;
			
			// add row if more than 1 'and'
			if (j > 0) filter_and(table_key+'_'+i+'_'+(j-1));
			
			col_input = $('select[name="filter_col_'+filter_key+'"]');
			$f('filter_col_'+filter_key, filter.col);
			
			$f('filter_cmp_'+filter_key, filter.cmp);
			$f('filter_val_'+filter_key, filter.val);
			$f('filter_val_hidden_'+filter_key, filter.val_hidden);
		}
	}
}

function filter_reset(table_key)
{
       var $container = $('#filter_container_'+table_key);
       $container.empty();
       filter_add_row($container, '0_0_0_0');
}

function filter_get_key_parts(filter_key)
{
	var matches;
	
	matches = filter_key.match(/^(\d+_\d+)_(\d+)_(\d+)$/i);
	
	return {
		'table_key':matches[1],
		'or_index':matches[2],
		'and_index':matches[3]
	};
}

// optional elem can be passed in to indicate where to insert new row
function filter_add_row($container, filter_key, $target)
{
	var div = "<div id='filter_row_"+filter_key+"' class='filter_row'>"+filter_init_ml(filter_key)+"</div>";
	
	if ($target)
	{
		$target.after(div);
	}
	// add it on the end
	else
	{
		$container.append(div);
	}
}

function filter_is_row(elem)
{
	return (elem && elem.className == 'filter_row');
}

function filter_is_links_container(elem)
{
	return (elem && elem.className == 'filter_links');
}

function filter_is_or_link(elem)
{
	return (elem && elem.className == 'filter_or_link');
}

function filter_and(filter_key)
{
	var key_parts, $container, $filter_row, $filter_row_after;
	
	key_parts = filter_get_key_parts(filter_key);
	$container = $("#filter_container_"+key_parts.table_key);
	$filter_row = $("#filter_row_"+filter_key);
	
	filter_add_row($container, key_parts.table_key+"_"+key_parts.or_index+"_"+(Number(key_parts.and_index) + 1), $filter_row);
	
	// remove "and" link and "or" link
	$filter_row.find('.filter_links').remove();
}

function filter_or(filter_key)
{
	var key_parts, $container, $filter_row;
	key_parts = filter_get_key_parts(filter_key);
	
	$container = $("#filter_container_"+key_parts.table_key);
	$filter_row = $("#filter_row_"+filter_key);
	
	// add a div to say "OR"
	$container.append("<div class='clr' style='margin-left: 20px;'>OR</div>");
	
	// add filter
	filter_add_row($container, key_parts.table_key+"_"+(Number(key_parts.or_index) + 1)+"_0");
	
	// remove the or link
	$filter_row.find('.filter_or_link').remove();
}

// hacks. lame but what are you gonna do
function filter_get_campaign_options(elem)
{
	var market, table_key, options;
	
	if (typeof(rep_get_table_key) == "function")
	{
		table_key = rep_get_table_key(elem);
		market = f["market_"+table_key].value;
	}
	else
	{
		market = f.market.value;
		if (market == "*")
		{
			alert("Please select a market by click on one in the data table if you would like to filter by campaign.");
			return;
		}
	}
	
	return window.campaigns[market];
}

function filter_to_obj(table_key)
{
	var i, j, field, filter, ors, filter_key, filter_val;
	
	filter = [];
	for (i = 0; true; ++i)
	{
		ors = [];
		for (j = 0; true; ++j)
		{
			filter_key = table_key+"_"+i+"_"+j;
			
			// if filter val is empty, no filter set
			field = f["filter_val_"+filter_key];
			if (!field) break;
			filter_val = field.value;
			if (filter_val == "") break;
			
			// if there is a hidden value, use that
			field = f["filter_val_hidden_"+filter_key];
			filter_val_hidden = (field) ? field.value : "";
			
			ors.push({
				"col":f["filter_col_"+filter_key].value,
				"cmp":f["filter_cmp_"+filter_key].value,
				"val":filter_val,
				"val_hidden":filter_val_hidden
			});
		}
		if (j == 0) break;
		filter.push(ors);
	}
	
	return filter;
}
