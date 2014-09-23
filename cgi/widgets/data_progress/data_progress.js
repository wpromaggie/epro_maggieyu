
function data_progress()
{
	this.refresh();
}

data_progress.prototype.refresh = function()
{
	if (this.post)
	{
		this.post('get_status', {'_widget_':'data_progress'});
	}
}

data_progress.prototype.get_status_callback = function(request, response){
	var row, market, data, all_done, self;
	
	all_done = true;
	this.find('tbody tr').each(function(){
		row = $(this);
		market = row.attr('market');
		data = response[market];
		if (empty(data))
		{
			row.children('.data_progress_time').html('-');
			row.children('.data_progress_status').html('Pending');
			all_done = false;
		}
		else
		{
			row.children('.data_progress_time').html(data.t);
			row.children('.data_progress_status').html(data.status);
			if (data.status != 'Completed') all_done = false;
		}
	});
	if (!all_done)
	{
		self = this;
		window.setTimeout(function(){self.refresh();}, 5000);
	}
}

$(document).ready(function(){ new data_progress(); });
