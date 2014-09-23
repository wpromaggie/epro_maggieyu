
function sales_manage_leads()
{
	this.cols = [
		{key:'actions'},
		{key:'name'},
		{key:'uploaded'},
		{key:'count'},
		{key:'dups'},
	];
	this.init_data();
	$('#leads_wrapper').table({
		cols:this.cols,
		data:this.data,
		show_totals:false,
		sort_init:'uploaded'
	});
}

sales_manage_leads.prototype.init_data = function()
{
	var i, d;
	
	this.data = [];
	if (globals.lead_uploads)
	{
		for (i = 0; i < globals.lead_uploads.length; ++i)
		{
			d = globals.lead_uploads[i];
			if (d.count > 0)
			{
				d.actions = '<a href="'+e2.url('/sales/manage_leads/download?upload_id='+d.id)+'">Download</a>';
				this.data.push(d);
			}
		} 
	}
};
