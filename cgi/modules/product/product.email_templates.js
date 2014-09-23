
function product_email_templates()
{
	var self = this,
		cboxes = $('#template_options input[type="checkbox"]');
	
	// set array of option group names
	this.option_groups = $('#template_options .cboxes_wrapper').map(function(){return $(this).attr('id');}).get();
	
	cboxes.bind('click', this, function(e){ e.data.option_click($(this)); });
	
	$('#tpl_list .tpl_link').bind('click', this, function(e){ e.data.tpl_link_click($(this)); return false; });
	$('#new_tpl_link').bind('click', this, function(e){ e.data.new_tpl_link_click($(this)); return false; });
	$('input[a0="action_get_templates"]').bind('click', this, function(e){ e.data.get_templates_click(); });
	$('#change_options_link').bind('click', this, function(e){ e.data.change_options_click(); return false; });
	$('#clear_all_options').bind('click', this, function(e){ e.data.clear_all_options_click(); return false; });
	$('#change_options_buttons input[value="Submit Changes"]').bind('click', this, function(e){ return e.data.change_options_submit(); });
	$('#change_options_buttons input[value="Cancel"]').bind('click', this, function(e){ e.data.change_options_cancel(); return false; });
	
	// push new tkey to url
	if ($('#new_tkey').val())
	{
		window.location.replace_path('?tkey='+$('#new_tkey').val());
	}
	
	// hide template form if we don't have a template
	if (!window.location.get['tkey'])
	{
		$('#tpl').hide();
		$('#current_edit').hide();
	}
	this.init_plan_cboxes();
	this.init_autocomplete();
}

function trigger_click_on_checkable(jqe)
{
	var cur_state = jqe.is(':checked');
	
	// set checked so any click handlers see correct state
	jqe.attr('checked', !cur_state);
	
	// click!
	jqe.click();
	
	// at some point click() actually does change the state,
	// which we had changed manually
	// so now we change it again and we should be all set
	jqe.attr('checked', !cur_state);
}

product_email_templates.prototype.init_plan_cboxes = function()
{
	var prev_dept, dept, i, cobx
		cboxes = $('#plans input[type="checkbox"][class!="toggle_all"]');
	
	prev_dept = 'ql';
	for (i = 0; i < cboxes.length; ++i)
	{
		cbox = $(cboxes[i]);
		dept = cbox.val().substr(0, 2);
		if (dept != prev_dept)
		{
			prev_dept = dept;
			cbox.closest('span').before('<br />');
		}
	}
};

product_email_templates.prototype.clear_all_options_click = function()
{
	var i, wrapper,
		cboxes = $('#template_options input[type="checkbox"][class!="toggle_all"]:checked');
	
	trigger_click_on_checkable(cboxes);
	
	// rarrr why??
	for (i = 0; i < this.option_groups.length; ++i)
	{
		e2.cboxes_set_val($('#'+this.option_groups[i]));
	}
};

product_email_templates.prototype.init_autocomplete = function(a)
{
	var ac_field_name, field,
		self = this;
	
	if (window.globals.autocomplete_data)
	{
		for (ac_field in window.globals.autocomplete_data)
		{
			field = $('#tpl input[name="email_template_'+ac_field+'"]');
			field.attr('autocomplete', 'off');
			field.autocomplete({
				minLength:0,
				source:this.get_autocomplete_source_callback(ac_field),
				select:function(e, ui){ self.autocomplete_select($(this), e, ui); },
				focus:function(e, ui){ e.preventDefault(); }
			});
		}
	}
};

product_email_templates.prototype.change_options_click = function(a)
{
	var i, j, option, elem, type, selected, cboxes, pre_change_on;
	
	this.pre_change_options = $();
	
	// grab our option groups (dept, action, plan)
	for (i = 0; i < this.option_groups.length; ++i)
	{
		type = this.option_groups[i];
		elem = $('#current_edit span.option_display[type="'+type+'"]');
		selected = elem.text().split(',');
		cboxes = $('#'+type+' input[type="checkbox"]');
		pre_change_on = cboxes.filter(':checked');
		
		if (pre_change_on.length > 0)
		{
			// add to our collection of options that were on before change click
			this.pre_change_options = this.pre_change_options.add(pre_change_on);
			
			// uncheck all the checked cboxes
			trigger_click_on_checkable(pre_change_on);
		}
		
		// click the cboxes for this option that are part of the mapping for the template currently being edited
		for (j = 0; j < selected.length; ++j)
		{
			option = selected[j].trim();
			trigger_click_on_checkable(cboxes.filter('#'+type+'-'+option));
		}
	}
	this.show_change_option_buttons();
};

product_email_templates.prototype.show_change_option_buttons = function()
{
	$('#change_options_buttons').show();
	$('#get_options_buttons').hide();
};

product_email_templates.prototype.change_options_submit = function()
{
	var i, type, val
		labels = $('#template_options .option_label');
	
	for (i = 0; i < labels.length; ++i)
	{
		type = $(labels[i]).text().toLowerCase();
		val = $f(type);
		
		if (empty(val))
		{
			alert('Please select at least one option from each section');
			return false;
		}
	}
	return true;
};

product_email_templates.prototype.change_options_cancel = function()
{
	$('#change_options_buttons').hide();
	$('#get_options_buttons').show();
	
	// uncheck everything and then turn back on what was previously on
	trigger_click_on_checkable($('#template_options input[type="checkbox"]:checked'));
	trigger_click_on_checkable(this.pre_change_options);
};

product_email_templates.prototype.get_autocomplete_source_callback = function(ac_field)
{
	var self = this;
	return function(request, response){ self.autocomplete_source(window.globals.autocomplete_data[ac_field], request.term, response); };
};

product_email_templates.prototype.tpl_link_click = function(a)
{
	var form = $('#f');
	
	form.attr('action', '?tkey='+a.attr('tkey'));
	form.submit();
};

product_email_templates.prototype.get_templates_click = function()
{
	var form = $('#f');
	
	form.attr('action', e2.url('/product/email_templates'));
};

product_email_templates.prototype.autocomplete_source = function(options, term, response)
{
	var i, option,
		filtered = [],
		separator_index = term.lastIndexOf(','),
		after_separator = (separator_index == -1) ? term : term.substr(separator_index + ((term.charAt(separator_index + 1) == ' ') ? 2 : 1));
	
	for (i = 0; i < options.length; ++i)
	{
		option = options[i];
		if (option.indexOf(after_separator) != -1)
		{
			filtered.push(option);
		}
	}
	
	response(filtered);
};

product_email_templates.prototype.autocomplete_select = function(elem, e, ui)
{
	var prev_val = elem.val(),
		separator_index = prev_val.lastIndexOf(','),
		before_separator = (separator_index == -1) ? '' : prev_val.substr(0, separator_index + ((prev_val.charAt(separator_index + 1) == ' ') ? 2 : 1));
	
	e.preventDefault();
	e.stopPropagation();
	
	elem.val(before_separator+ui.item.value);
};

product_email_templates.prototype.new_tpl_link_click = function(link)
{
	$('#tpl').show();
	$('#tpl input:eq(0)').focus();
};
