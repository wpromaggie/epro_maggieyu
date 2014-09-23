<?php
/* ----
 * Description:
 * 	Test harness for wproPHP
 * Programmers:
 *  cp C-P
 * 	kk Koding Kevin
 * 	mc Merlin Corey
 *	vy Vyrus001
 * History:
 *	0.0.1 2008February05 First recorded version; added new 'modules' tests and Levenshtein
 * ---- */

ini_set('display_errors', 'on');
error_reporting(E_ALL | E_STRICT);

//define('WPRO_DB_NAME', 'tests/sql.db');
//define('WPRO_DB_TYPE', 'sqlite');
//define('WPRO_MAGIC', 'database;modules;skin;simpleskin;email');
define('WPRO_MAGIC', 'modules;skin;simpleskin;email');

require_once('wpro.php');
$wpro['paths']->Add('tests', 'tests/');
$wpro['modules']->require_enable($wpro['paths']->get('tests').'main.php', 'mod_main'); 

//wpro::db_model_view();
wpro::model_view();

?>
