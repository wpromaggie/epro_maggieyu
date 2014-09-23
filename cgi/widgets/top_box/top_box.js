
function topbox_close_click(e)
{
	var close_div;
	
	close_div = $(e.currentTarget);
	$(close_div.closest('div.topbox')).hide();
}


// count incremented whenever we show or drag a container so the current one is always on top
var g_topbox_drag_count = 0;
function topbox_drag_start(e)
{
	var container;

	container = $(e.currentTarget);
	container.css("zIndex", ++g_topbox_drag_count);
}


function top_box(argv)
{
	var head, head_id, body, content, x, y;
	
	head_id = (argv.head_id) ? argv.head_id : (argv.id+'_head');
	head = '\
		<div id="'+head_id+'" class="topbox_head">\
			<span class="topbox_title">'+argv.title+'</span>\
			<div class="topbox_close">x</div>\
		</div>';
	
	// if content is passed in, use that, otherwise show "loading" image and assume an ajax call is being made to get the content
	content = (argv.content) ? argv.content : '\
		<div style="padding:100px 0 0 100px;width:200px;height:100px;">\
			<span style="float:left;">LOADING<span><img style="float:right;margin-left:10px;" src="'+loading_gif()+'" />\
		</div>\
	';
	body = '\
		<div id="'+((argv.body_id) ? argv.body_id : (argv.id+'_body'))+'" class="topbox_body">\
			'+content+'\
		</div>';
	
	// add it to page
	$('body').append('\
		<div id="'+argv.id+'" class="topbox" style="z-index:'+(++g_topbox_drag_count)+'">\
			'+head+'\
			'+body+'\
		</div>\
	');
	
	// position it
	if (argv.x)
	{
		x = argv.x;
		y = argv.y;
	}
	else if (argv.event)
	{
		x = argv.event.pageX;
		y = argv.event.pageY;
	}
	else
	{
		x = $(window).scrollLeft() + Math.floor($(window).width() / 2) - Math.floor($('div#'+argv.id).width() / 2);
		y = $(window).scrollTop() + Math.floor($(window).height() / 2) - Math.floor($('div#'+argv.id).height() / 2);
	}
	$('div#'+argv.id).css('left', x+'px').css('top', y+'px');
	
	// make draggable
	$('div#'+argv.id).draggable({
		handle:$('div#'+argv.head_id),
		delay:50,
		start:function(event, ui) { topbox_drag_start(event); }
	});
	
	// set handlers
	$('div#'+head_id+' div.topbox_close').click(function(event) { topbox_close_click(event); });
	if (argv.unload) $('div#'+argv.head_id+' div.topbox_close').click(argv.unload);
	if (argv.load) argv.load();
}
