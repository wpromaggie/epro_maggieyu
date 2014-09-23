<?php


class mod_eppctwo_sb_exp_contacts extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
	
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		
		
		self::$cols = self::init_cols(
			new rs_col('account_id'  ,'char'    ,16 ,''     ,rs::NOT_NULL),
			new rs_col('name'         ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('url'          ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('street'       ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('city'       ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('state'       ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('zip'       ,'varchar' ,16  ,''     ,rs::NOT_NULL),
			new rs_col('phone'        ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('email'        ,'varchar' ,64 ,''     ,rs::NOT_NULL),
			new rs_col('hour_start'     ,'varchar' ,16 ,''     ,rs::NOT_NULL),
			new rs_col('hour_end'     ,'varchar' ,16 ,''     ,rs::NOT_NULL),
			new rs_col('fb_exists'     ,'tinyint' ,1 ,0     ,rs::NOT_NULL),
			new rs_col('fb_email'     ,'varchar' ,256 ,''     ,rs::NOT_NULL),
			new rs_col('fb_email'     ,'varchar' ,64 ,''     ,rs::NOT_NULL),
			new rs_col('fb_pass'     ,'varchar' ,32 ,''     ,rs::NOT_NULL),
			new rs_col('url_key'     ,'varchar' ,32 ,''     ,rs::NOT_NULL)
		);
		
	}
	
	public static function create($data, $opts = array())
	{
		$data['url_key'] = self::generate_url_key();
		return parent::create($data, $opts);
	}
	
	public static function get_express_link($account)
	{
		$sb_exp_contact = new sb_exp_contacts(array('account_id' => $account->id));
		
		$protocol = (util::is_dev()) ? 'http' : 'https';
		return ($protocol.'://'.\epro\WPRO_DOMAIN.'/account/socialboost-express-follow-up?k='.$sb_exp_contact->url_key);
	}
	
	public static function generate_url_key()
	{
		while (1)
		{
			$key = md5(mt_rand());
			if (self::count(array("where" => "authentication = '$key'")) == 0)
			{
				return $key;
			}
		}
	}
}
?>
