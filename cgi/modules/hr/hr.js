
function hr_events()
{
	$('#new_link').on('click', this, function(e){ e.data.new_click(); return false; });
	$('input[name="wpro_event_all_day"]').on('change', this, function(e){ e.data.all_day_change(); });

	this.all_day_change();

	this.init_event_data();
	$('#w_table').table({
		'data':this.data,
		'cols':[
			{key:'name'},
			{key:'type'},
			{key:'date'},
			{key:'start_time'},
			{key:'end_time'}
		]
	});

	// if there was an error, show new form
	if (Feedback.is_error_msg()) {
		dbg('hello');
		this.new_click();
	}
}

hr_events.prototype.init_event_data = function()
{
	var i, d;

	this.data = [];
	for (i = 0; i < globals.events.length; ++i) {
		d = globals.events[i];
		d._display_name = '<a target="_blank" href="'+e2.url('/hr/edit_event?eid='+d.id)+'">'+d.name+'</a>';
		if (d.start_time == '00:00:00') {
			d.start_time = '';
		}
		if (d.end_time == '00:00:00') {
			d.end_time = '';
		}
		this.data.push(d);
	}
};

hr_events.prototype.new_click = function()
{
	var $div = $('#w_new');

	if ($div.is(':visible')) {
		$div.hide();
	}
	else {
		$div.show();
	}
};

hr_events.prototype.all_day_change = function()
{
	var
		val = $('input[name="wpro_event_all_day"]:checked').val(),
		$time_input_rows = $('.time_input').closest('tr')
	;

	// it is all day event, don't need time
	if (val == 1) {
		$time_input_rows.hide();
	}
	else {
		$time_input_rows.show();
	}
};

function hr_edit_event()
{
	dbg('hello?');
	$('input[a0="action_delete_wpro_event_submit"]').on('click', this, function(e){ return e.data.delete_click(); });
}

hr_edit_event.prototype.delete_click = function()
{
	return confirm('Delete this event?');
};
