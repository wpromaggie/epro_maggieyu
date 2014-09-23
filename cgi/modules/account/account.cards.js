
function show_full_cards()
{
	this.find('.loading').hide();
	this.find('a').bind('click', this, function(e){e.data.show_full_cards_click($(this));return false;});
}

show_full_cards.prototype.show_full_cards_click = function(a)
{
	a.blur();
	
	this.find('.loading').show();
	this.post('get_full_cards', {});
};

show_full_cards.prototype.get_full_cards_callback = function(request, response)
{
	var cc_id, container;
	
	for (cc_id in response)
	{
		container = $('[cc_id="'+cc_id+'"] .cc_number');
		container.text(String(response[cc_id]));
		container.hide();
		container.fadeIn(1000, 'swing');
	}
	this.find('.loading').hide();
};

function copy_card()
{
	$('#copy_card_link').bind('click', this, function(e){ e.data.link_click($(this)); return false; });
	$('#copy_get_cards_button').bind('click', this, function(e){ e.data.get_cards_button_click($(this)); return false; });
}

copy_card.prototype.link_click = function(link)
{
	link.blur();
	$('#copy_card_form').show();
};

copy_card.prototype.get_cards_button_click = function(button)
{
	button.blur();
	this.post('copy_get_cards', {
		dept:$('#copy_dept').val(),
		ac_id:$('#copy_ac_id').val()
	});
};

copy_card.prototype.copy_get_cards_callback = function(request, response)
{
	$('#copy_cc_select_td').html(html.select('copy_cc_id', response));
	$('#copy_card_form .card_select_row').show();
};

function card()
{
	this.find('[a0="action_cards_update"]').bind('click', this, function(e){e.data.set_cc_id($(this));});
	this.find('[a0="action_cards_activate"]').bind('click', this, function(e){e.data.set_cc_id($(this));});
	this.find('[a0="action_cards_delete"]').bind('click', this, function(e){return e.data.card_delete($(this));});
}

card.prototype.set_cc_id = function(button)
{
	$f('cc_id', button.closest('[cc_id]').attr('cc_id'));
};

card.prototype.card_delete = function(button)
{
	if (!confirm('Delete card?'))
	{
		$f('a0', '');
		return false;
	}
	else
	{
		this.set_cc_id(button);
		return true;
	}
};
