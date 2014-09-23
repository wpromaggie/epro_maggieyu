
function quick_search()
{
	this.find('#quick_search').bind('keydown', this, function(e){ return e.data.text_keydown(e); });
}

quick_search.prototype.text_keydown = function(e)
{
	if (e.which == 13) {
		$('#quick_search_submit').click();
		return false;
	}
	else {
		return e.which;
	}
};
