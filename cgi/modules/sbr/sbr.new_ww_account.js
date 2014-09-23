
function sbr_new_ww_account()
{
	$('.purchase_option').bind('change', this, function(e){ e.data.set_billing_totals(); });
}

sbr_new_ww_account.prototype.set_billing_totals = function()
{
	this.post('get_billing_totals', {
		plan:$('#plan').val(),
		contract_length:$('#contract_length').val(),
		landing_page:$('#landing_page').val(),
		extra_pages:$('#extra_pages').val()
	});
};

sbr_new_ww_account.prototype.get_billing_totals_callback = function(request, response)
{
	$('#today_total').text(Format.dollars(response.first_month));
	$('#monthly_total').text(Format.dollars(response.monthly));
};
