
function move_account()
{
	$('#move_account_submit').on('click', this.move_account_submit_click.bind(this));
}

move_account.prototype.move_account_submit_click = function()
{
	// check destination client is set
	if (!$('#dst_client').val()) {
		alert('Please enter the client you would like to move the account(s) to.');
		$('#dst_input').focus();
		return false;
	}
	// if multiple accounts, make sure at least 1 is selected
	if (!$('input[name="move_accounts"]').val()) {
		alert('Please select at least 1 account to move.');
		return false;
	}
	return true;
};

