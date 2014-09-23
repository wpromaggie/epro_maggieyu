
function q_table()
{
	this.find('input[type="submit"]').bind('click', this, function(e){ e.data.done_click($(this)); return false; });
}

function product_queues()
{
	var self = this;
	// hacky blah blah. updates and bfs have their own classes
	if (globals.pages.length > 2 && (globals.pages[2] == 'updates' || globals.pages[2] == 'billing_failures')) {
		return;
	}
	this.init_accounts();
	this.init_cols();
	this.table = $('#w_queue').table({
		data:this.accounts,
		cols:this.cols,
		id_key:'id',
		sort_init:'signup',
		sort_dir_init:'desc',
		show_totals:false,
		post_render:function(tbody, data, sort_col){ self.post_render(tbody, data, sort_col); }
	}).data('table');
	$('#w_queue').find('input.done').bind('click', this, function(e){ return e.data.done_click($(this)); });
}

product_queues.prototype.post_render = function(tbody, data, sort_col)
{
	var i, d, dprev;

	// remove any spacers we may have previously added
	tbody.find('tr.signup_spacer').remove();

	// only add spacers if sort col is signup
	if (sort_col == 'signup') {
		for (i = 1; i < data.length; ++i) {
			d = data[i];
			dprev = data[i - 1];
			if (d.signup.substr(0, 10) != dprev.signup.substr(0, 10)) {
				tbody.find('tr[i="'+d.id+'"]').before('<tr class="signup_spacer"><td colspan=20><hr /></td></tr>');
			}
		}
	}
};

product_queues.prototype.init_accounts = function()
{
	var i, ac, manager_map = {};
	
	$('#manager option').each(function(){
		var $opt = $(this);
		manager_map[$opt.val()] = $opt.text();
	});
	this.accounts = globals.accounts;
	for (i = 0; i < this.accounts.length; ++i) {
		ac = this.flatten(this.accounts[i]);
		ac.manager = (manager_map[ac.manager]) ? manager_map[ac.manager] : '';
		ac._display_url = '<a target="_blank" href="'+e2.url('/account/product/'+ac.dept+'?aid='+ac.id)+'">'+((ac.url) ? ac.url : '(none)')+'</a>';
		this.accounts[i] = ac;
	}
};

product_queues.prototype.done_click = function($input)
{
	var queue_name = globals.pages[globals.pages.length - 1],
		data = this.table.get_row_data($input);
	
	$f('done_id', data.id);
	e2.action_submit('action_'+queue_name+'_done_submit');
	return false;
};

product_queues.prototype.show_done = function(val, data)
{
	return '<input class="done" type="submit" value="Done" />';
};

product_queues.prototype.init_cols = function()
{
	var self = this;
	this.cols = [];
	if (globals.show_done) {
		this.cols.push({key:'done',display:'',format:function(val, data){ return self.show_done(val, data); }});
	}
	this.cols = this.cols.concat([
		{key:'dept'},
		{key:'url'},
		{key:'name'},
		{key:'email'},
		{key:'signup'},
		{key:'oid'},
		{key:'status'},
		{key:'plan'},
		{key:'manager'},
		{key:'rep'},
		{key:'partner'},
		{key:'source'},
		{key:'subid'}
	]);
};

product_queues.prototype.flatten = function(obj)
{
	var i, k, v, new_obj = {};
	
	new_obj = {};
	for (k in obj)
	{
		v = obj[k];
		if ($.type(v) == 'object')
		{
			$.extend(new_obj, this.flatten(v));
		}
		else
		{
			new_obj[k] = v;
		}
	}
	return new_obj;
};

function product_queues_updates()
{
	var self = this;
	
	// no updates!
	if (!globals.updates || globals.updates.length == 0) {
		$('#updates_table').html('<tbody><tr><td><strong>Updates Queue Empty</strong></td></tr></tbody>');
	}
	else {
		// todo: get rid of row click stuff
		this.table = $('#updates_table').table({
			data:globals.updates,
			id_key:'_id',
			cols:[
				{key:'done',display:'',format:function(val, data){ return self.show_done(val, data); }},
				{key:'url'},
				{key:'name',classes:'nowrap'},
				{key:'email'},
				{key:'dt',classes:'nowrap',display:'Date'},
				{key:'type',format:function(val, data){ return val.split('-')[1]; }},
				{key:'data',format:function(val, data){ return self.show_data(val, data); }}
			],
			sort_init:'dt',
			sort_dir_init:'asc',
			show_totals:false
		}).data('table');
		$('#updates_table .update_url').bind('click', this, function(e){ e.data.url_click($(this)); return false; });
		$('#updates_table input[type="submit"][value="Done"]').bind('click', this, function(e){ return e.data.done_click($(this)); });
	}
}

product_queues_updates.prototype.url_click = function($a)
{
	var target_page,
		data = this.table.get_row_data($a);
	
	switch (data.type)
	{
		case ('sbs-cc'):
			target_page = 'cards'; break;
			
		case ('ql-ad'):
		case ('ql-keywords'):
			target_page = 'markets';
			break;
			
		case ('sbs-contact'):
		default:
			target_page = 'dashboard';
			break;
	}
	e2.new_window('/account/product/'+data.dept+'/'+target_page+'?aid='+data.aid);
};

product_queues_updates.prototype.done_click = function($button)
{
	var data = this.table.get_row_data($button);
	//note = prompt('Optional Note');
	//if (note == null)
	//{
		//return false;
	//}
	
	$f('note', '');
	$f('done_id', data._id);
	e2.action_submit('action_updates_done_submit');
	return false;
};

product_queues_updates.prototype.show_done = function(val, data)
{
	return '<input type="submit" value="Done" />';
};

product_queues_updates.prototype.show_data = function(val, data)
{
	var func;
	
	func = 'show_data_'+data.type.split('-')[1];
	if (this[func])
	{
		return this[func](val);
	}
	else
	{
		return this.show_data_generic(val);
	}
};

product_queues_updates.prototype.show_data_ad = function(val)
{
	val = eval('('+val+')');
	
	//socialboost ad format is dif
	if(val.body_text){
		return '\
			<b>Ad ID:</b> '+val.ad_id+',\
			<b>Title:</b> '+val.title+',\
			<b>Text:</b> '+val.text+',\
			<b>Keywords:</b> '+val.keywords+'\
		';
	}
	
	return '\
		<b>Title:</b> '+val.text+',\
		<b>Desc 1:</b> '+val.desc_1+',\
		<b>Desc 2:</b> '+val.desc_2+'\
	';
};

product_queues_updates.prototype.show_data_generic = function(val)
{
	var key, ml;
	val = eval('('+val+')');
	ml = '';
	for (key in val)
	{
		if (ml != '') ml += ', ';
		ml += '<b>'+key.display_text()+':</b> '+val[key];
	}
	return ml;
};

product_queues_updates.prototype.show_data_keywords = function(val)
{
	var keywords, i, keyword, ml;
	
	val = eval('('+val+')');
	keywords = val.keywords.split("\t");
	ml = '';
	for (i = 0; i < keywords.length; ++i)
	{
		if (i) ml += ', ';
		ml += '<b>KW'+(i + 1)+':</b> '+keywords[i];
	}
	return ml;
};
