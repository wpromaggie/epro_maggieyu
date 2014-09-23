<?php
require_once('cli.php');
/*

CREATE TABLE `data_speeds` (
`id` int(11) unsigned NOT NULL default 0,
`speed` double NOT NULL default 0,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

*/

// check command line for dates
$end_date = (array_key_exists('e', cli::$args)) ? cli::$args['e'] : date(DATE, time() - 86400);
$start_date = (array_key_exists('s', cli::$args)) ? cli::$args['s'] : date(DATE, strtotime("$end_date -4 months"));

db::exec("delete from data_speeds");

echo "$start_date, $end_date\n";
for ($i = util::DATA_TABLE_COUNT - 1; $i > -1 ; --$i)
{
	echo "$i\n";
	data_speed_update($i);
}
?>