
function account_notes()
{
	var self = this;
	if (!globals.notes || globals.notes.length == 0) return;
	$('#notes_table').table({
		data:globals.notes,
		show_totals:false,
		cols:['date','user','note'],
		click_tr:function(e, tr, data){self.notes_row_click(e, tr, data);}
	});
}

account_notes.prototype.notes_row_click = function(e, tr, data)
{
	var id = 'note_'+data.id;
	
	$.box({
		id:id,
		title:'Edit Note',
		close:true,
		event:e,
		content:'\
			<div>\
				<textarea>'+data.note+'</textarea>\
			</div>\
			<div>\
				<input type="submit" value="Update" />\
				<input type="submit" value="Cancel" />\
				<input type="submit" value="Delete" />\
			</div>\
		'
	});
	$('#'+id+' textarea').focus();
	$('#'+id+' input[type="submit"]').bind('click', this, function(e){ e.data.note_edit_submit($(this)); return false; });
};

account_notes.prototype.note_edit_submit = function(button)
{
	var box = button.closest('.box');
	var note_id = box.attr('id').replace(/^note_/, '');
	var note = box.find('textarea').val();
	
	switch (button.attr('value'))
	{
		case ('Update'): return this.note_edit_update(note_id, note);
		case ('Cancel'): return this.note_edit_cancel(box);
		case ('Delete'): return this.note_edit_delete(note_id, note);
	}
};

account_notes.prototype.note_edit_update = function(note_id, note)
{
	this.post('note_edit_update', {
		note_id:note_id,
		note:note
	});
};

account_notes.prototype.note_edit_cancel = function(box)
{
	box.hide();
};

account_notes.prototype.note_edit_delete = function(note_id, note)
{
	if (!confirm('Delete note: '+note+'?'))
	{
		return;
	}
	else
	{
		this.post('note_edit_delete', {
			note_id:note_id
		});
	}
};

account_notes.prototype.note_edit_update_callback = function(request, response)
{
	var i, d;
	
	for (i = 0; i < globals.notes_table_data.length; ++i)
	{
		d = globals.notes_table_data[i];
		if (d.id == request.note_id)
		{
			$('#notes_table tbody tr:eq('+i+') td:eq(3)').text(request.note);
			d.note = request.note;
			break;
		}
	}
	$('#note_'+request.note_id).hide();
};

account_notes.prototype.note_edit_delete_callback = function(request, response)
{
	var i, d;
	
	for (i = 0; i < globals.notes_table_data.length; ++i)
	{
		d = globals.notes_table_data[i];
		if (d.id == request.note_id)
		{
			$('#notes_table tbody tr:eq('+i+')').remove();
			globals.notes_table_data.splice(i, 1);
			
			// hehe
			$('#notes_table th.on a').click();
			$('#notes_table th.on a').click();
			break;
		}
	}
	$('#note_'+request.note_id).hide();
};
