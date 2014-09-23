
function billing_hist()
{
	var self = this;
	
	this.init_data();
	$('#w_history').table({
		data:this.data,
		cols:this.cols,
		sort_init:'date_received',
		sort_dir_init:'desc',
		show_totals:true
	});
}

billing_hist.prototype.init_data = function()
{
	var i, j, total, row, payment, type, part_types, part_amounts;
	
	this.cols = [
		{key:'date_received'},
		{key:'date_attributed'},
		{key:'total',format:'dollars'}
	];
	this.data = [];
	this.part_types = {};

	for (i = 0; i < globals.payments.length; ++i) {
		payment = globals.payments[i];
		row = {
			_id:payment.id,
			date_received:payment.date_received,
			date_attributed:payment.date_attributed,
			notes:payment.notes
		};
		part_types = payment.part_types.split("\t");
		part_amounts = payment.part_amounts.split("\t");
		total = false;
		for (j = 0; j < part_types.length; ++j) {
			type = part_types[j];
			if (globals.dept == 'partner' && (!(type in globals.part_types) || globals.part_types[type] != 'partner')) {
				continue;
			}
			if (total === false) {
				total = Number(part_amounts[j]);
			}
			else {
				total += Number(part_amounts[j]);
			}
			this.part_types[type] = 1;
			row[type] = part_amounts[j];
		}
		if (total !== false) {
			row.total = total;
			row._display_date_received = '<a target="_blank" href="'+globals.billing_url+'edit_payment'+window.location.search+'&pid='+row._id+'">'+row.date_received+'</a>';
			this.data.push(row);
		}
	}
	
	for (type in this.part_types) {
		this.cols.push({key:type,format:'dollars',calc:this.calc_part_amount});
	}
	this.cols.push({key:'notes'});
};

billing_hist.prototype.calc_part_amount = function(data, key)
{
	return (data[key]) ? data[key] : 0;
};

function payment_table()
{
	// delete button
	this.find('input[a0="action_delete_payment_submit"]').bind('click', this, function(e){return e.data.delete_click();});
	this.find('input[a0="action_payment_move_submit"]').bind('click', this, function(e){return e.data.move_click();});
	this.part_amounts = this.find('input.part_amount');
	this.part_amounts.bind('keyup', this, function(e){e.data.update_total();});
	this.update_total();
}

payment_table.prototype.delete_click = function()
{
	return (confirm('Delete this payment?'));
};

payment_table.prototype.move_click = function()
{
	var r = prompt('Client number to move payment to:');
	if (r === null) {
		return false;
	}
	else {
		$f('move_id', r);
		return true;
	}
};

payment_table.prototype.update_total = function()
{
	var sum = 0;
	this.part_amounts.each(function(){
		var amount = Types.to_number($(this).val());
		sum += (isNaN(amount) ? 0 : amount)
	});
	this.find('#total').text(Format.dollars(sum));
};


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

function contact_select()
{
	this.bind('change', this, function(e){
		e.data.set_contact(e.data.val());
	});
	this.set_contact(this.val());
}

contact_select.prototype.set_contact = function(contact_id)
{
	for (var i=0;i<globals.client_contacts.length;i++) {
		if (globals.client_contacts[i].id == contact_id) {
			var selected = globals.client_contacts[i];

			$('input[name="pdf_contact"]').val(selected.name);
			$('input[name="pdf_address_1"]').val(selected.name);
			$('input[name="pdf_address_2"]').val(selected.street);
			$('input[name="pdf_address_3"]').val(selected.city +", "+ selected.state +" "+ selected.zip);

			break;
		}
	}
}

function pdf_charges()
{
	this.$pdf_charges_table = $('#pdf_charges table');
	this.$pdf_charges_table.bind('click', this, function(e){
		var $move_charge, $sibling_charge;
		var $target = $(e.target);

		if ($target.hasClass('up_button')) {
			$move_charge = $target.closest('tr');
			$sibling_charge = $move_charge.prev();
			if ($sibling_charge.length != 0) {
				$sibling_charge.before($move_charge.detach());
			}
		} else if ($target.hasClass('down_button')) {
			$move_charge = $target.closest('tr');
			$sibling_charge = $move_charge.next();
			if ($sibling_charge.length != 0) {
				$sibling_charge.after($move_charge.detach());
			}
		} else if ($target.hasClass('del_button')) {
			$target.closest('tr').remove();
		}
	});
	var add_chg_id = 0;
	$('#pdf_add_charge').bind('click', this, function(e){
		e.data.add_charge('added_'+add_chg_id++, 0, '');
	});

	//Payment table stuff for Invoice page
	$('#payment_table input[type="submit"]').closest('tr').remove();
	$('#payment_table input.date_input').closest('tr').remove();
	$('#payment_table input.notes').closest('tr').remove();
	$('#payment_table input.part_amount').bind('keyup', this, function(e){
		var $part_amt = $(this);
		var charge_id = $part_amt.attr('name');
		var $pdf_charge = $('#pdf_charge_'+charge_id);

		if ($part_amt.val() == '') {
			$pdf_charge.remove();
		} else if ($pdf_charge.length == 0) {
			e.data.add_charge(charge_id, $part_amt.val(), $part_amt.closest('tr').find('td b').html())
		} else {
			$pdf_charge.find('input').first().val($part_amt.val());
		}
	});

	//Add existing charges on Receipt page
	if (globals.receipt_charges)
	{
		var charge;
		for (var i=0;i<globals.receipt_charges.length;i++) {
			charge = globals.receipt_charges[i];
			this.add_charge(String(charge.type).toLowerCase().replace(/\s/g,'_'), charge.amount, charge.type);
		}
	}
}

pdf_charges.prototype.add_charge = function(charge_id, charge_amount, charge_desc)
{
	this.$pdf_charges_table.append('\
		<tr id="pdf_charge_'+charge_id+'">\
			<td><input type="text" name="pdf_chg_amt[]" value="'+charge_amount+'"></td>\
			<td><input type="text" name="pdf_chg_desc[]" value="'+charge_desc+'"></td>\
			<td><input type="button" class="up_button" value="&uarr;"></td>\
			<td><input type="button" class="down_button" value="&darr;"></td>\
			<td><input type="button" class="del_button" value="del"></td>\
		</tr>\
	');
}