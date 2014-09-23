
(function(window){ var _html = {

	form_set: function(elem_name, val)
	{
		// only set if val is non-empty
		if (empty(val)) return;
		
		var $input = $(f).find('[name="'+elem_name+'"]');
		if ($input.length) $input.val(val);
	},

	hidden: function(field_name, field_value, $target)
	{
		var input, value;
		if (empty(field_name)) return false;
		value = (empty(field_value)) ? "true" : field_value;
		if($target==null) $target = $(f);

		input = "<input type='hidden' name='"+field_name+"' value='"+value+"' />";
		$target.append(input);
		return true;
	},
	
	get_option:function(option)
	{
		if (Types.is_array(option)) {
			return {value:option[0],text:option[1]};
		}
		else if (Types.is_object(option)) {
			return option;
		}
		else {
			return {value:option,text:option};
		}
	},

	options:function(the_options)
	{
		var ml, ml_selected, i, option;

		ml = '';
		for (i = 0; i < the_options.length; ++i)
		{
			option = html.get_option(the_options[i]);
			ml_selected = (arguments.length > 1 && option.value == arguments[1]) ? ' selected' : '';
			ml += '<option value="'+option.value+'"'+ml_selected+'>'+option.text+'</option>';
		}
		return ml;
	},
	
	/*
	 * name: name and id of select
	 * options: array of options. see above for element types
	 * selected: optional value of selected option
	 * ml: optional arbitrary ml to be added to select dom elem
	 */
	select:function(name, options)
	{
		var ml_options = window.html.options.apply({}, Array.prototype.slice.call(arguments, 1));
		return '<select name="'+name+'" id="'+name+'"'+((arguments.length > 3) ? ' '+arguments[3] : '')+'>'+ml_options+'</select>';
	},

	select_add_option:function(select, value, display)
	{
		select[select.options.length] = new Option(display, value);
	},

	select_remove_option:function(select, value)
	{
		var i;

		for (i = 0; i < select.options.length; ++i)
		{
			if (select.options[i].value == value)
			{
				select.remove(i);
				return;
			}
		}
	},

	select_has_option:function(select, value)
	{
		var i;

		for (i = 0; i < select.options.length; ++i)
		{
			if (select.options[i].value == value) return true;
		}
		return false;
	},

	checkboxes:function(name, options)
	{
		var ml, ml_checked, span_class, i, id, option, selected, opts;

		selected = (arguments.length > 2) ? arguments[2] : [];
		opts = (arguments.length > 3) ? arguments[3] : {};
		if (!Types.is_array(selected)) {
			if (Types.is_string(selected)) {
				if (selected != '*') {
					selected = (selected.indexOf("\t") != -1) ? selected.split("\t") : selected.split(",");
				}
			}
			else {
				// ??
				selected = [];
			}
		}
		ml = '';
		for (i = 0; i < options.length; ++i) {
			option = html.get_option(options[i]);
			if (selected == '*' || Array.in_array(selected, option.value)) {
				ml_checked = ' checked';
				span_class = ' class="on"';
			}
			else {
				ml_checked = '';
				span_class = '';
			}
			id = name+'-'+option.value;
			ml += '\
				<span'+span_class+'>\
					<label for="'+id+'">'+option.text+'</label>\
					<input type="checkbox" id="'+id+'" name="'+id+'" value="'+option.value+'"'+ml_checked+((option.attrs) ? ' '+option.attrs : '')+' />\
					<br />\
				</span>\
			';
		}
		if (opts.toggle_all) {
			id = name+'-__toggle_all__';
			ml = '\
				<span>\
					<label for="'+id+'">Toggle</label>\
					<input type="checkbox" id="'+id+'" value="1" class="toggle_all" />\
					<br />\
				</span>\
			'+ml;
		}

		return '\
			<div id="'+name+'" class="cboxes_wrapper'+(opts.classes ? ' '+opts.classes : '')+'"'+(opts.attrs ? ' '+opts.attrs : '')+'>\
				'+ml+'\
			</div>\
		';
	},

	radios:function(name, options)
	{
		var ml, ml_selected, i, id, option,
			opts = (arguments.length > 3) ? arguments[3] : {},
			separator = opts.separator || '<br />',
			other_attrs = opts.attrs || ''
		;

		ml = '';
		for (i = 0; i < options.length; ++i) {
			option = html.get_option(options[i]);
			id = '__'+name+'_'+i+'__';
			ml_selected = (arguments.length > 2 && option.value == arguments[2]) ? ' checked' : '';
			ml += '\
				<input type="radio" id="'+id+'" name="'+name+'"'+((other_attrs) ? ' '+other_attrs : '')+' value="'+option.value+'"'+ml_selected+' />\
				<label for="'+id+'">'+option.text+'</label>\
				'+separator+'\
			';
		}

		return ml;
	},

	encode:function(s)
	{
		return $('<div/>').text(s).html();
	},

	decode:function(text)
	{
		return $('<div/>').html(s).text();
	},

	textarea_display:function(s)
	{
		return s.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br />').replace(/\r/g, '');
	}

}; window.html = _html; })(window);