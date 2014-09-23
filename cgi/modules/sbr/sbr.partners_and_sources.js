
function sbr_partners_and_sources()
{
	// if we have a spid, we edited a source, show those sources
	var spid = $('#source_partner_id').val();
	if (spid)
	{
		$('.partner_id a:contains("'+spid+'")').closest('.container').find('.sources_wrapper').show();
	}
	$('.partner_id a').bind('click', this, function(e){ e.data.partner_click($(this)); return false; });
	$('.new_source_submit').bind('click', this, function(e){ e.data.new_source_submit($(this)); });
	$('.status_link').bind('click', this, function(e){ e.data.status_click($(this)); return false; });
	$('.edit_link').bind('click', this, function(e){ e.data.edit_click($(this)); return false; });
	$('input[type="text"]').bind('keydown', this, function(e){ return e.data.input_keydown(e, $(this)); });
}

sbr_partners_and_sources.prototype.input_keydown = function(e, elem)
{
	// catch enter, "click" appropriate button
	if (e.which == 13)
	{
		elem.siblings('[a0]').click();
		e.stopPropagation();
		return false;
	}
	else
	{
		return true;
	}
	dbg(e.which);
};

sbr_partners_and_sources.prototype.get_id = function(elem, type)
{
	return elem.closest('.container[type="'+type+'"]').find('.'+type+'_id').text()
};

sbr_partners_and_sources.prototype.partner_click = function(a)
{
	a.blur();
	a.closest('.container').find('.sources_wrapper').toggle();
};

sbr_partners_and_sources.prototype.new_source_submit = function(input)
{
	$('#source_partner_id').val(this.get_id(input, 'partner'));
};

sbr_partners_and_sources.prototype.status_click = function(elem)
{
	var type = elem.closest('.container').attr('type'),
		id = this.get_id(elem, type);
	
	if (confirm(elem.text()+' '+type+' '+id+'?'))
	{
		$('#action_id').val(id);
		if (type == 'source')
		{
			$('#source_partner_id').val(this.get_id(elem, 'partner'));
		}
		e2.action_submit('action_'+type+'_'+elem.text().toLowerCase());
	}
};

sbr_partners_and_sources.prototype.edit_click = function(elem)
{
	var type = elem.closest('.container').attr('type'),
		id = this.get_id(elem, type),
		new_id = prompt('New name:', id);;
	
	if (Types.is_string(new_id))
	{
		$('#action_id').val(id);
		$('#action_val').val(new_id);
		if (type == 'source')
		{
			$('#source_partner_id').val(this.get_id(elem, 'partner'));
		}
		e2.action_submit('action_'+type+'_edit');
	}
};
