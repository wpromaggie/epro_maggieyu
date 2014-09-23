
function cal_table()
{
	this.find('a.nav_link').bind('click', this, function(e){ e.data.nav_click($(this)); return false; });
}


cal_table.prototype.nav_click = function(a)
{
	//$f('ym', a.attr('ym'));
	//f_submit();
	window.location.qset({
		keep:true,
		put:{ym:a.attr('ym')}
	});
}
