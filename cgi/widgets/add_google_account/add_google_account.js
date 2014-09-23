
function add_google_account(e)
{
	this.id = 'add_google_account_box';
	$.box({
		id:this.id,
		title:'Add Google Account',
		close:true,
		event:e,
		content:'\
			<table>\
				<tbody>\
					<tr>\
						<td>ID</td>\
						<td><input type="text" name="id" focus_me="1" /></td>\
					</tr>\
					<tr>\
						<td>Name</td>\
						<td><input type="text" name="name" /></td>\
					</tr>\
					<tr>\
						<th colspan=2>OR</th>\
					</tr>\
					<tr>\
						<td>One Line</td>\
						<td><input type="text" name="one_line" /> Format: Client Name ( Client ID: XXX-XXX-XXXX )</td>\
					</tr>\
					<tr>\
						<td></td>\
						<td><input type="submit" a0="action_add_google_account" value="Submit" /></td>\
					</tr>\
				</tbody>\
			</table>\
		'
	});
	
	$('#'+this.id+' input[a0]').bind('click', this, function(e){ e.data.submit_click($(this)); });
}

add_google_account.prototype.submit_click = function(input)
{
	$f('a0', input.attr('a0'));
};

add_google_account.prototype.show = function()
{
	$('#'+this.id).show();
};
