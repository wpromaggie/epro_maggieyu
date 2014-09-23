
function wid_client_select()
{
	this.is_client_selected = false;
	this.selected_client_text = false;
	this.$client_field = this.find('.client_select_field');
	this.$input = this.find('.client_select_input');
	this.$msg = this.find('.client_select_msg');

	this.init_input();
	this.init_selected();
}

wid_client_select.prototype.init_input = function()
{
	this.$input.attr('autocomplete', 'off');
	this.$input.autocomplete({
		minLength:1,
		source:this.dst_input_autocomplete_source_callback.bind(this),
		select:this.dst_input_autocomplete_select_callback.bind(this),
		focus:function(e, ui){ e.preventDefault(); }
	});
};

wid_client_select.prototype.dst_input_autocomplete_source_callback = function(request, response)
{
	var name, option,
		term = request.term.toLowerCase(),
		filtered = [],
		separator_index = term.lastIndexOf(','),
		after_separator = (separator_index == -1) ? term : term.substr(separator_index + ((term.charAt(separator_index + 1) == ' ') ? 2 : 1));
	
	// if user had previously selected a client and we are here, that means they are typing again
	// make sure they haven't changed anything
	if (this.is_client_selected && request.term != this.selected_client_text) {
		this.$msg
			.text('Client de-selected, please re-enter client name')
			.addClass('i')
		;
		this.$client_field.val('');
		this.is_client_selected = false;
	}
	for (name in globals.client_select_options) {
		if (name.toLowerCase().indexOf(term) != -1) {
			filtered.push(name);
			if (filtered.length > 10) break;
		}
	}
	
	response(filtered);
};

wid_client_select.prototype.dst_input_autocomplete_select_callback = function(e, ui)
{
	var elem = $('#dst_input'),
		prev_val = this.$input.val(),
		selected = ui.item.value,
		separator_index = prev_val.lastIndexOf(','),
		before_separator = (separator_index == -1) ? '' : prev_val.substr(0, separator_index + ((prev_val.charAt(separator_index + 1) == ' ') ? 2 : 1));
	
	e.preventDefault();
	e.stopPropagation();
	
	this.set_selected(selected);
	
	// if (selected in globals.client_select_options) {
	// 	this.is_client_selected = true;
	// 	this.selected_client_text = selected;
	// 	this.$msg.text('');
	// 	this.$client_field.val(globals.client_select_options[selected].id);
	// }
	// this.$input.val(before_separator+ui.item.value);
};

wid_client_select.prototype.set_selected = function(selected)
{
	// should always be
	if (selected in globals.client_select_options) {
		this.is_client_selected = true;
		this.selected_client_text = selected;
		this.$msg.text('');
		this.$client_field.val(globals.client_select_options[selected].id);
		this.$input.val(selected);
	}
};

wid_client_select.prototype.init_selected = function()
{
	var text;
	
	if (globals.client_select_selected) {
		for (text in globals.client_select_options) {
			if (globals.client_select_selected == globals.client_select_options[text].id) {
				this.set_selected(text);
				break;
			}
		}
	}
};
