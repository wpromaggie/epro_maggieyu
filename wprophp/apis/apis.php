<?php

if (!class_exists('env'))
{
	class env
	{
		const WPRO_PATH = WPRO_PATH;
	}
}

require_once(\epro\WPROPHP_PATH.'apis/base_soap.php');
require_once(\epro\WPROPHP_PATH.'apis/base_market.php');
require_once(\epro\WPROPHP_PATH.'xml.php');
require_once(\epro\WPROPHP_PATH.'strings.php');
require_once(\epro\WPROPHP_PATH.'curl.php');
require_once(\epro\WPROPHP_PATH.'apis/rosetta_stone.php');
require_once(\epro\WPROPHP_PATH.'apis/objects/api_factory.php');
require_once(\epro\WPROPHP_PATH.'apis/markets/g.php');
require_once(\epro\WPROPHP_PATH.'apis/markets/y.php');
require_once(\epro\WPROPHP_PATH.'apis/markets/m.php');
require_once(\epro\WPROPHP_PATH.'apis/markets/f.php');
require_once(\epro\WPROPHP_PATH.'apis/affiliates/base-affiliate.php');
require_once(\epro\WPROPHP_PATH.'apis/affiliates/cj.php');

?>