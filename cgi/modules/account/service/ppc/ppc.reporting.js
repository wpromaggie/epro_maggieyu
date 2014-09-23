
function scheduled_reports(){
	if ($("#scheduled_reports").length) {
		this.rep_init_home();
	}
}

scheduled_reports.prototype.rep_set_completed_report_link = function(elems)
{
	elems.each(function(){
		var
			$elem = $(this),
			href = e2.url('/account/service/ppc/reporting/download/?aid='+window.location.get.aid+'&job_id='+$elem.closest('tr').attr('job_id'));
		;
		$elem.html('<a target="_blank" class="rep_completed_link" href="'+href+'">Completed</a>');
	});
};

scheduled_reports.prototype.running_job_completed_callback = function(job_id)
{
	var $td = $('#scheduled_reports tr[job_id="'+job_id+'"] td.rep_job_status');
	// see if there was an error
	if ($td.find('.error, .cancelled').length) {
		// don't need to do anything, widget will show error
		return;
	}
	else {
		this.rep_set_completed_report_link($td);
	}
};

scheduled_reports.prototype.rep_init_home = function()
{
	var self = this;
	// check for completed reports
	this.rep_set_completed_report_link($('tr.rep_completed td.rep_job_status'));
	$('#scheduled_reports tr.rep_running td.rep_job_status').each(function(){
		var $elem = $(this);
		e2.job_status_widget($elem, $elem.closest('tr').attr('job_id'), self.running_job_completed_callback.bind(self));
	});
};
