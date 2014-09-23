<?php

class as_partner extends service
{
	public static $db, $cols, $primary_key;

	public static $recurring_payment_types = array('Partner PPC', 'Partner SMO', 'Partner SEO');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'        ,'char'  ,16  ,'',rs::READ_ONLY),
			new rs_col('ppc_fee'   ,'double',null,0 ,rs::NOT_NULL),
			new rs_col('ppc_budget','double',null,0 ,rs::NOT_NULL),
			new rs_col('smo_fee'   ,'double',null,0 ,rs::NOT_NULL),
			new rs_col('seo_fee'   ,'double',null,0 ,rs::NOT_NULL)
		);
	}

	// these partner billing fields should be standardized when eppctwo.client_payment
	// is combined with eac.payment
	public static function get_account_payment_type_key($type)
	{
		$type = util::simple_text($type);
		// if not budget, partner field has "_fee" suffixed
		if (stripos($type, 'budget') !== false) {
			$partner_type_key = $type;
		}
		else {
			$partner_type_key = $type.'_fee';
		}
		return preg_replace("/^partner_/", '', $partner_type_key);
	}
}

?>