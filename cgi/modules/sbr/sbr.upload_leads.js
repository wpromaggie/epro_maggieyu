
function sbr_upload_leads()
{
	$('#leads_submit').bind('click', this, function(e){ return e.data.leads_submit_click(); });
}

sbr_upload_leads.prototype.leads_submit_click = function()
{
	if (!$('#partner').val())
	{
		alert('Please select a partner');
		return false;
	}
	if (!$('#source').val())
	{
		alert('Please select a source');
		return false;
	}
	return true;
};
