
function payments_table()
{
	var self = this;
	
	this.today = Date.js_to_str(new Date());
	$('#payments_table').table({
		cols:[
			{key:'url'},
			{key:'dept'},
			{key:'status'},
			{key:'plan'},
			{key:'partner'},
			{key:'pay_option'},
			{key:'cancel_date'},
			{key:'de_activation_date'},
			{key:'last_date',format:function(val, data){ return self.format_last_date(val, data); }},
			{key:'last_amount',format:'dollars'}
		],
		data:globals.accounts,
		show_totals:false
	});
}

payments_table.prototype.format_last_date = function(val, data)
{
	return ((val == this.today) ? '<span style="background-color:#ffc0c0;">'+val+'</span>' : val);
};
