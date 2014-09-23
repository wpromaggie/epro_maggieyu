
(function($){
	
	$.is_jq = function(x)
	{
		return (x instanceof jQuery);
	};
	
	$.to_jq = function(x)
	{
		return (($.is_jq(x)) ? x : $(x));
	};
	
	$.empty = function(x)
	{
		return ($.to_jq(x).length == 0);
	};
	
	$.index = function(x)
	{
		return ($.to_jq(x).prevAll().length);
	};
	
	$.elem_swap = function(x, y)
	{
		var ix = x.prevAll().length;
		var iy = y.prevAll().length;
		return ((ix < iy) ? $.elem_swap.ordered_elem_swap(x, y, ix, iy) : $.elem_swap.ordered_elem_swap(y, x, iy, ix));
	};
	
	$.elem_swap.ordered_elem_swap = function(younger, older, iyounger, iolder)
	{
		// special case, they are next to each other
		if ((iolder - iyounger) == 1)
		{
			older.insertBefore(younger);
		}
		else
		{
			var next_older_than_younger = younger.next();
			younger.insertBefore(older);
			older.insertBefore(next_older_than_younger);
		}
		return true;
	};
	
	var js_loaded = {};
	$.load_js = function()
	{
		var i, arg, file_path;
		
		$.ajaxSetup({async: false});
		for (i = 0; i < arguments.length; ++i)
		{
			arg = arguments[i];
			if (arg.charAt(0) == '/')
			{
				file_path = arg;
			}
			else
			{
				file_path = '/js/'+arg;
			}
			if (!js_loaded[file_path])
			{
				js_loaded[file_path] = 1;
				$.getScript(file_path);
			}
		}
		$.ajaxSetup({async: true});
	};
	
	
	var box_count = 0;
	$.box = function(opts)
	{
		var x, y, box, css_attr;
		
		if (!$.empty('#'+opts.id))
		{
			return $('#'+opts.id).show();
		}
		
		$('form').append('\
			<div id="'+opts.id+'" class="box">\
				<div class="handle">\
					<div class="title">'+opts.title+'</div>\
					'+((opts.close) ? '<div class="close">x</div>' : '')+'\
					'+((opts.minimize) ? '<div class="minimize">_</div>' : '')+'\
					<div class="clear"></div>\
				</div>\
				<div class="content">\
					'+opts.content+'\
				</div>\
			</div>\
		');
		
		if (opts.x)
		{
			x = opts.x;
			y = opts.y;
		}
		else if (opts.event)
		{
			x = opts.event.pageX;
			y = opts.event.pageY;
		}
		else
		{
			x = $(window).scrollLeft() + Math.floor($(window).width() / 2) - 64;
			y = $(window).scrollTop() + Math.floor($(window).height() / 2) - Math.floor($('div#'+opts.id).height() / 2);
		}
		box = $('div#'+opts.id);
		
		if (opts.css)
		{
			for (css_attr in opts.css)
			{
				box.css(css_attr, opts.css[css_attr]);
			}
		}
		
		box.css('left', x+'px');
		box.css('top', y+'px');
		box.css('position', ((opts.position) ? opts.position : 'absolute'));
		box.css('z-index', ++box_count);
		
		// make draggable
		box.draggable({
			handle:$('div#'+opts.id+' div.handle'),
			delay:50
		});
		
		box.find('.handle .minimize').click(function(e){ $.box_minimize_click($(this)); });
		box.find('.handle .close').click(function(e){ $.box_close_click($(this)); });
		
		
		if (opts.unload) box.find('.handle .close').click(function(e){ opts.unload(); });
		if (opts.load) opts.load();
		return box;
	};
	
	$.box_minimize_click = function(minimize_button)
	{
		$.dbg('mini!');
		minimize_button.css('background-color', '#ff0000');
	};
	
	$.box_close_click = function(close_button)
	{
		close_button.closest('.box').hide();
	};
	
})(jQuery);
