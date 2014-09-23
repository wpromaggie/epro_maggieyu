
secondary_managers = function()
{
	$('#secondary_managers_table .remove_user_button').bind('click', this, function(e){ e.data.remove_secondary_manager_click($(this)); return false; });
}

secondary_managers.prototype.remove_secondary_manager_click = function(button)
{
	$('#remove_secondary_manager_id').val(button.attr('uid'));
	e2.action_submit('action_remove_secondary_manager');
};
