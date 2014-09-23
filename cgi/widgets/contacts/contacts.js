function contacts_edit(e)
{
	var input, fieldset;
	
	input = $(e.currentTarget);
	fieldset = input.closest('fieldset');
	
	contacts_make_editable(fieldset);
}

function contacts_make_editable(fieldset)
{
	var legend, cid, i, rows, row, field_cell, value_cell, key, field;
	
	legend = fieldset.find('legend');
	cid = fieldset.attr('cid');
	
	legend.html('<input type="text" name="name" value="'+legend.text().entity_text()+'" ov="'+legend.text().entity_text()+'" />');
	
	rows = fieldset.find('tr');
	for (i = 0; i < rows.length; ++i)
	{
		row = $(rows[i]);
		field_cell = row.find('td:nth-child(1)');
		value_cell = row.find('td:nth-child(2)');
		key = field_cell.text().simple_text();
		field = g_fields[key];
		if (field.textarea)
		{
			value_cell.html('<textarea name="'+key+'" ov="'+value_cell.text().entity_text()+'">'+value_cell.text().entity_text()+'</textarea>');
		}
		else
		{
			value_cell.html('<input type="text" name="'+key+'" value="'+value_cell.text().entity_text()+'" ov="'+value_cell.text().entity_text()+'" />');
		}
	}
	
	legend.find('input').focus();
	
	$('#cur_contacts fieldset[cid='+cid+'] div.view_buttons').hide();
	$('#cur_contacts fieldset[cid='+cid+'] div.edit_buttons').show();
}

function contacts_delete(e)
{
	var input, fieldset, legend;
	
	input = $(e.currentTarget);
	fieldset = input.closest('fieldset');
	legend = fieldset.find('legend');
	
	if (!confirm('Delete '+legend.text()+'?')) return false;
	contacts_set_id(e);
	return true;
}

function contacts_add_new(e)
{
	var c, key;
	
	c = {id:'new', name:'New Contact Name'};
	for (key in g_fields)
	{
		if (!c[key]) c[key] = '';
	}
	contacts_add(c);
	contacts_make_editable($('#cur_contacts fieldset[cid='+c.id+']'));
}

function contacts_add(c)
{
	var key, field, val, ml_fields, ml;
	
	ml_fields = '';
	for (key in c)
	{
		field = g_fields[key];
		if (field.read_only || key == 'name') continue;
		
		val = c[key];
		ml_fields += '\
			<tr>\
				<td class="field">'+key.display_text()+'</td>\
				<td class="value">'+val.replace(/\n/g, '<br />')+'</td>\
			</tr>\
		';
	}
	
	ml = '\
		<fieldset cid="'+c.id+'" class="contact end_float">\
			<legend>'+c.name+'</legend>\
			<table>\
				<tbody>\
					'+ml_fields+'\
				</tbody>\
			</table>\
			<div class="view_buttons">\
				<input type="submit" name="edit" value="Edit" />\
				<input type="submit" name="delete" value="Delete" />\
			</div>\
			<div class="edit_buttons">\
				<input type="submit" name="save" value="Save Changes" />\
				<input type="submit" name="cancel" value="Cancel" />\
			</div>\
		</fieldset>\
		<div class="clr"></div>\
	';
	$('#cur_contacts').append(ml);
	$('#cur_contacts fieldset[cid='+c.id+'] input[name=edit]').click(function(e){ contacts_edit(e); return false; });
	$('#cur_contacts fieldset[cid='+c.id+'] input[name=delete]').click(function(e){ return contacts_delete(e); });
	$('#cur_contacts fieldset[cid='+c.id+'] input[name=save]').click(function(e){ contacts_save(e); });
	$('#cur_contacts fieldset[cid='+c.id+'] input[name=cancel]').click(function(e){ contacts_cancel_edit(e); return false; });
}

function contacts_save(e)
{
	contacts_set_id(e);
}

function contacts_set_id(e)
{
	var input, fieldset;
	
	input = $(e.currentTarget);
	fieldset = input.closest('fieldset');
	
	$f('cid', fieldset.attr('cid'));
}

function contacts_cancel_edit(e)
{
	var input, fieldset;
	
	input = $(e.currentTarget);
	fieldset = input.closest('fieldset');
	
	contacts_make_viewable(fieldset);
}

function contacts_make_viewable(fieldset)
{
	var legend, cid, i, rows, row, cell;
	
	legend = fieldset.find('legend');
	cid = fieldset.attr('cid');
	
	// cancel for "Add New Contact", just remove
	if (cid == 'new')
	{
		fieldset.remove();
		return;
	}
	
	legend.html(legend.find('input').attr('ov'));
	
	rows = fieldset.find('tr');
	for (i = 0; i < rows.length; ++i)
	{
		row = $(rows[i]);
		cell = row.find('td:nth-child(2)');
		cell.html(cell.children().first().attr('ov'));
	}
	
	legend.find('input').focus();
	
	$('#cur_contacts fieldset[cid='+cid+'] div.edit_buttons').hide();
	$('#cur_contacts fieldset[cid='+cid+'] div.view_buttons').show();
}

function contacts_init()
{
	var i, c;
	
	for (i = 0; i < g_contacts.length; ++i)
	{
		c = g_contacts[i];
		contacts_add(c);
	}
	$('input[name=add]').click(function(e){ contacts_add_new(e); return false; });
}

$(document).ready(contacts_init);
