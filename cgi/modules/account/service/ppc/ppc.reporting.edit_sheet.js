
function sheet(report)
{
	this.tables = [];
	this.report = report;
	this.jElement = $('<div id="rep_sheet_'+report.sheet_count+'" class="sheet_body" sid="'+report.sheet_count+'" table_count="0"></div>');
}


sheet.prototype.add_table = function(report)      
{
	var
		opts = (arguments.length > 1) ? arguments[1] : {},
		t = new table(report, this, opts)
	;
	
	if (opts.prepend) {
		report.move_object(t.jElement, 'FIRST');
		this.tables.unshift(t);
	}
	// default is to push/add to end of sheet
	else {
		this.tables.push(t);
	}
	this.sync_tables();
	return t;
};

sheet.prototype.sync_tables = function(){ 
	this.jElement.find('.sheet_table').each(function(i){
		$(this).find('.table_title').text('Table '+(i + 1));
	})
}

sheet.prototype.rename = function() 
{
	this.report.get_object_name_from_user('Sheet', this.report.cur_header, /^[\w -\(\)]+$/, 31);
};

sheet.prototype.remove_sheet_options = function()
{ 
	// if options are already there, remove them
	if ($("#sheet_header_options").length > 0 )
	{    
		$("#sheet_header_options").remove();
	}
};

sheet.prototype.get_table_by_key = function(table_key)
{
	var i, table;
	
	for (i = 0; i < this.tables.length; ++i)
	{
		table = this.tables[i];
		if (table.key == table_key)
		{
			return table;
		}
	}
	return false;
};

sheet.prototype.get_sheet_index_in_report = function(){ 
	
	for (var i=0; i < this.report.sheets.length; i++) { 
		if (this.report.sheets[i].jElement.get(0) == this.jElement.get(0))
			return i;
	}
	
	return -1;
};

