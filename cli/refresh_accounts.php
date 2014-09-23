<?php
require_once('cli.php');

// default is to refresh accounts for all markets
$markets = util::get_ppc_markets();

// market from cl, just do that market
if (array_key_exists('m', cli::$args)) $markets = array(cli::$args['m']);
else $markets = util::get_ppc_markets();

foreach ($markets as $market)
{
	switch ($market)
	{
		case ('m'):
			$accounts = db::select("
				select distinct user, pass
				from eppctwo.m_accounts
				where user <> ''
			");
			for ($i = 0; list($user, $pass) = $accounts[$i]; ++$i)
			{
				util::refresh_accounts('m', $user, $pass);
			}
			break;
		
		case ('g'):
			util::refresh_accounts('g');
	}
}

?>