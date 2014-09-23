<?php
/* ----
 * Description:
 * 	Commision junction api object
 * Programmers:
 *	cp C-P
 *	km K-Money
 *	mc Merlin Corey
 *	vy1 Vyrus001
 * History:
 *	0.0.1 2008February18 Initial version
 * ---- */

define('WPRO_CJ_PUBLISHER_URL', 'https://publookup.api.cj.com');
define('WPRO_CJ_PRODUCT_URL', 'https://product.api.cj.com');
define('WPRO_CJ_ADVERTISER_URL', 'https://linksearch.api.cj.com');
define('WPRO_CJ_LINK_URL', 'https://linksearch.api.cj.com');
define('WPRO_CJ_SUPPORT_URL', 'https://linksearch.api.cj.com');
define('WPRO_CJ_RTCOMMISSION_URL', 'https://rtpubcommission.api.cj.com');
define('WPRO_CJ_PUBCOMMISSION_URL', 'https://pubcommission.api.cj.com');
define('WPRO_CJ_ITEMDETAILS_URL', 'https://pubcommission.api.cj.com');

if (class_exists('cj_api'))
{
	trigger_error('Error: "cj_api" already exists!', E_USER_WARNING);
}
else
{
	class cj_api extends base_affiliate
	{
		protected $m_name = 'cj';
		
		public function get_report($query)
		{
			
		}
		
		protected function get_header()
		{
			$headers = "
			";
			return ($headers);
		}

		protected function get_endpoint()
		{
			return ('');
		}
		
		protected function get_soap_action()
		{
			return '';
		}
		
		protected function is_error(&$doc)
		{
			return false;
		}
	}; // base_affiliate	
}

?>