/**
 * JavaScript for timecard.
 */

/**
 * This function gets called when the timecard module page loads.
 * THIS IS BASICALLY THE CONSTRUCTOR!
 */
function timecard()
{
	// Adjust page button actions (ajax)
	$("#add_button").bind("click", this, function(e){
		e.data.adjust_add();
		return false;
	});
	$(document).on("click", "#adjust_table button.delete_button", this, function(e){
		e.data.adjust_delete($(this));
		return false;
	});
	$(document).on("click", "#adjust_table button.update_button", this, function(e){
		e.data.adjust_update($(this));
		return false;
	});
}

/**
 * Add a time record on the Adjust page, using ajax.
 */
timecard.prototype.adjust_add = function(){
	var request;
	Feedback.clear();

	var $add_row = $('#add_row');

	request = {
		user_id: $('#current_user').html(),
		date: $('#current_date').html(),
		clock_in: $add_row.find('td.c_in input').val(),
		clock_out: $add_row.find('td.c_out input').val()
	};

	this.post('adjust_add', request);
};

timecard.prototype.adjust_add_callback = function(request, response){
	if (response == 'FALSE') {
		Feedback.add_error_msg('Error adding time record');
	} else {
		var $add_row = $('#add_row');
		$add_row.find('td.c_in input').val('');
		$add_row.find('td.c_out input').val('');
		$add_row.before(String(response));

		Feedback.add_success_msg('Time record added');
	}
};

/**
 * Delete a time record on the Adjust page, using ajax.
 */
timecard.prototype.adjust_delete = function($button){
	var request;
	Feedback.clear();

	request = {
		time_id: $button.closest('tr').attr('time_id')
	};

	this.post('adjust_delete', request);
};

timecard.prototype.adjust_delete_callback = function(request, response){
	if (response != 'TRUE') {
		Feedback.add_error_msg('Error deleting time record');
	} else {
		$('#adjust_table tr[time_id=' + request.time_id + ']').remove();
		Feedback.add_success_msg('Time record deleted');
	}
};

/**
 * Update a time record on the Adjust page, using ajax.
 */
timecard.prototype.adjust_update = function($button){
	var request;
	Feedback.clear();

	var $parent_row = $button.closest('tr');

	request = {
		time_id: $parent_row.attr('time_id'),
		date: $('#current_date').html(),
		clock_in: $parent_row.find('td.c_in input').val(),
		clock_out: $parent_row.find('td.c_out input').val()
	};

	this.post('adjust_update', request);
};

timecard.prototype.adjust_update_callback = function(request, response){
	if (response == 'FALSE') {
		//Notify user that update was unsuccessful
		Feedback.add_error_msg('Error updating time record');

		//Need to set old values if new values were crap
		//XXX
		window.location.reload(true);
		//XXX
		
	} else {
		//Let user know update was successful
		Feedback.add_success_msg('Time record updated');
	}
};

function timecard_report()
{
	$('input[name="who"]').bind('click', this, function(e){ e.data.who_click($(this)); });
	
	$('input[name="who"]:checked').click();
}

timecard_report.prototype.who_click = function(radio)
{
	var user_row = $('#user'),
		who = radio.val();
	
	if (who == 'indiv')
	{
		if (!user_row.is(':visible'))
		{
			user_row.show();
		}
	}
	else
	{
		if (user_row.is(':visible'))
		{
			user_row.hide();
		}
	}
};
