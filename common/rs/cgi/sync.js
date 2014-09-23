
function rs_sync()
{
	// all pages
	$('input[type="submit"][act]').bind('click', this, function(e){ e.data.submit_act_click($(this)); });
	$('input[type="submit"][view]').bind('click', this, function(e){ e.data.submit_view_click($(this)); });
	
	var cur_page_class = 'rs_sync_'+$('#cur_view').val();
	if (window[cur_page_class] && window[cur_page_class].prototype)
	{
		var page = new window[cur_page_class]();
	}
}

rs_sync.prototype.submit_act_click = function(submit)
{
	$('#form').append('<input type="hidden" name="act" value="'+submit.attr('act')+'" />');
};

rs_sync.prototype.submit_view_click = function(submit)
{
	$('#form').attr('action', '?view='+submit.attr('view'));
};

function rs_sync_index()
{
	$('.file_cbox').bind('click', this, function(e){ e.data.file_checkbox_click($(this)); });
	$('input[view="preview"]').bind('click', this, function(e){ return e.data.submit_preview_click($(this)); });
}

rs_sync_index.prototype.submit_preview_click = function(submit)
{
	var form = $('#form');
	
	$('.table_cbox:checked').each(function(i){
		form.append('<input type="hidden" name="table_'+i+'" value="'+$(this).val()+'" />');
	});
	return true;
};

rs_sync_index.prototype.get_file_name_from_cell = function(cell)
{
	var text, matches, file_name;
	
	matches = cell.text().match(/(.*) \(\d\d\d\d/);
	return matches[1];
};

rs_sync_index.prototype.file_checkbox_click = function(cbox)
{
	$('#rs_table tr[file_block="'+cbox.closest('tr').attr('file_block')+'"] .table_cbox').attr('checked', (cbox.is(':checked')));
};

function rs_sync_preview()
{
	$('.preview_col_toggle_all').bind('click', this, function(e){ e.data.preview_col_toggle_all_click($(this)); });
	$('.delete_or_rename').bind('change', this, function(e){ e.data.delete_or_rename_change($(this)); });
	$('.rename_to_col_select').bind('change', this, function(e){ e.data.rename_col_change($(this)); });
	this.init_rename_to();
}

rs_sync_preview.prototype.preview_col_toggle_all_click = function(cbox)
{
	cbox.closest('table').find('.col_change_cbox').attr('checked', cbox.is(':checked'));
};

rs_sync_preview.prototype.init_rename_to = function()
{
	var i, table, deleted, ml_new_col_options,
		tables = $('.col_changes_table');
	
	for (i = 0; i < tables.length; ++i)
	{
		table = $(tables[i]);
		deleted = table.find('.delete_or_rename');
		if (deleted.length)
		{
			// get labels for new columns
			ml_new_col_options = '';
			table.find('label[for^="new\t"]').each(function(){
				var new_col_text = $(this).text();
				ml_new_col_options += '<option value="'+new_col_text+'">'+new_col_text+'</option>';
			});
			
			// set rename to options
			table.find('.rename_to_col_select').each(function(){
				$(this).html(ml_new_col_options);
			});
		}
	}
};

rs_sync_preview.prototype.submit_process_click = function(submit)
{
	var form = $('#form');
	
	$('.col_change_cbox:checked').each(function(i){
		form.append('<input type="hidden" name="col_change_'+i+'" value="'+$(this).attr('id')+'" />');
	});
	return true;
};

rs_sync_preview.prototype.delete_or_rename_change = function(select)
{
	select.closest('tr').find('.rename_to_col_select').toggle();
	this.disable_new_on_rename(select);
};

rs_sync_preview.prototype.disable_new_on_rename = function(select)
{
	var rename_selects, i, select, id_parts, new_col_input,
		table = select.closest('table');
	
	// enable all
	table.find('.col_change_cbox[id^="new\t"]').attr('disabled', false);
	
	rename_selects = table.find('.rename_to_col_select:visible');
	for (i = 0; i < rename_selects.length; ++i)
	{
		select = $(rename_selects[i]);
		new_col_input = table.find('.col_change_cbox[id^="new\t"][id$="\t'+select.val()+'"]');
		new_col_input.attr('checked', false);
		new_col_input.attr('disabled', true);
	}
};

rs_sync_preview.prototype.rename_col_change = function(select)
{
	this.disable_new_on_rename(select);
};

$(window.document).ready(function(){ new rs_sync(); });
