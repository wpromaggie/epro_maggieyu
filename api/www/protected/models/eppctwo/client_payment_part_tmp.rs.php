<?php

class mod_eppctwo_client_payment_part_tmp extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client_id'));

		self::$uniques = array(array(;''));

		self::$cols = self::init_cols(
			new rs_col('id'                         ,'int'                   ,10    ,''		),
			new rs_col('client_payment_id'          ,'int'                   ,11    ,''		),
			new rs_col('client_id'                  ,'bigint'                ,20    ,''		),
			new rs_col('type'                       ,'enum'                  ,'PPC Management','PPC Budget','PPC Consulting','PPC Conversion Optimization','PPC Facebook','PPC Email','PPC Mobile','PPC Media','PPC Banner','PPC Other','SEO Management','SEO Budget','SEO Consulting','SEO Offsite','SEO Audit','SEO Infographic','SEO Other','Media Management','Media Budget','Media Consulting','Media Other','SMO Management','SMO Budget','SMO Consulting','SMO Other','WebDev Management','WebDev Budget','WebDev Consulting','WebDev Optimization','WebDev Banner','WebDev Other','Hosting','Televox QuickList','Televox SocialBoost','Televox GoSEO','QuickList Pro','Government Services','FetchBack','Copy','Call Tracking',''		),
			new rs_col('amount'                     ,'double'                ,''    ,''		),
			new rs_col('rep_pay_num'                ,'tinyint'               ,4     ,''		)
			);
	}
}
?>
