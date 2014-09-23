
function accounting_types_dept_types()
{
	$('#w_type input[type="radio"]').bind('click', this, function(e){ e.data.type_click($(this)); });
	$('#w_depts input[type="checkbox"]').bind('click', this, function(e){ e.data.dept_click($(this)); });
	$('input[a0="action_dept_type_edit_submit"]').bind('click', this, function(e){ return e.data.edit_submit_click(); });
	
	dbg('posted', $('#posted_type').val());
	if ($('#posted_type').val())
	{
		dbg('gonna click', $('#type-'+$('#posted_type').val()).length);
		$('#type-'+$('#posted_type').val()).click();
	}
}

accounting_types_dept_types.prototype.edit_submit_click = function()
{
	var new_depts = $('#w_new_depts span').map(function(){ return $(this).text(); }).get(),
		removed_depts = $('#w_removed_depts span').map(function(){ return $(this).text(); }).get();
	
	if (!new_depts.length && !removed_depts.length)
	{
		alert('No changes!');
		return false;
	}
	else
	{
		$('#new_depts').val(new_depts.join("\t"));
		$('#removed_depts').val(removed_depts.join("\t"));
		return true;
	}
};

accounting_types_dept_types.prototype.type_click = function($radio)
{
	var type = $radio.val(),
		$cur_maps = $('#w_cur_mappings .cur_mapping p:contains("'+type+'")');
	
	// check that user isn't clicking a radio that is already selected
	if (type == this.type)
	{
		return;
	}
	
	// we only need to do this once
	$('#edit_mapping_table tr.hide').show().removeClass('hide');
	
	this.type = type;
	// remember what is currently set so we can track what user add/removes
	this.cur_depts = $cur_maps.closest('.cur_mapping').find('.dept').map(function(){ return $(this).text(); }).get();
	
	// turn off any depts that may be on
	$('#w_depts input[type="checkbox"]:checked').attr('checked', false);
	for (var i = 0; i < this.cur_depts.length; ++i)
	{
		// set selected depts for this type based on cur mappings
		$('#depts-'+this.cur_depts[i]).attr('checked', true);
	}
	
	// clear out changes ui updates
	$('#dept_changes .dept_change_details').empty();
};

accounting_types_dept_types.prototype.dept_click = function($cbox)
{
	var dept = $cbox.val();
	
	dbg(dept, $cbox.is(':checked'));
	if ($cbox.is(':checked'))
	{
		if (!Array.in_array(this.cur_depts, dept))
		{
			$('#w_new_depts').append('<span class="dept_change" id="new_dept_'+dept+'">'+dept+'</span>');
		}
		else
		{
			$('#removed_dept_'+dept).remove();
		}
	}
	else
	{
		if (Array.in_array(this.cur_depts, dept))
		{
			$('#w_removed_depts').append('<span class="dept_change" id="removed_dept_'+dept+'">'+dept+'</span>');
		}
		else
		{
			$('#new_dept_'+dept).remove();
		}
	}
};

function items()
{
	this.key = $('#enum_col').val();
	
	this.init_data();
	this.init_cols();
	this.$table = $('#items').table({
		data:this.data,
		cols:this.cols,
		sort_init:this.key,
		sort_dir_init:'asc',
		show_totals:false
	});
	this.$table.find('.edit').bind('click', this, function(e){ e.data.edit_click($(this)); return false; });
	this.$table.find('.delete').bind('click', this, function(e){ e.data.delete_click($(this)); return false; });
}

items.prototype.init_data = function()
{
	var i;
	
	this.data = globals.items;
	for (i = 0; i < this.data.length; ++i)
	{
		this.data[i].actions = '\
			<a href="" class="edit">Edit</a>\
			<a href="" class="delete">Delete</a>\
		';
	}
};

items.prototype.init_cols = function()
{
	this.cols = [
		{key:'actions'},
		{key:this.key}
	];
};

items.prototype.edit_click = function($a)
{
	var data = this.$table.data('table').get_row_data($a),
		new_item = prompt('This will change all current and future payments of '+this.key+' \''+data[this.key]+'\' to the new value.', data[this.key]);
	
	if (typeof(new_item) == 'string')
	{
		$('#item_id').val(data.id);
		$('#edit_item').val(new_item);
		e2.action_submit('action_edit_item');
	}
};

items.prototype.delete_click = function($a)
{
	var data = this.$table.data('table').get_row_data($a);
	
	if (confirm('Delete '+this.key+' \''+data[this.key]+'\'? You can only delete if no payments match. You will receive an error message if there are matching payments.'))
	{
		$('#item_id').val(data.id);
		e2.action_submit('action_delete_item');
	}
};
