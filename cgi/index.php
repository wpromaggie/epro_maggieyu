<?php
require('core/cgi.php');

cgi::init();

if (isset(g::$module->skin) && g::$module->skin=='cameo'){

	require('skins/cameo/cameo.php');
	$skin = new Cameo();
	$skin->draw();

} else {

	require('skins/blue_dragon.php');
	$skin = new Blue_Dragon();
	$skin->draw();

}

?>