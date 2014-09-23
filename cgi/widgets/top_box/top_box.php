<?php

class wid_top_box extends widget_base
{
	public static function init()
	{
		cgi::add_js_jquery_ui('jquery.ui.draggable.js');
	}
}

?>