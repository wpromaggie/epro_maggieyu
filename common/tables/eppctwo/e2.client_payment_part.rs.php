<?php


class client_payment_part extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $belongs_to;
	
	public static $part_types = array(
		'PPC Management' => 'ppc',
		'PPC Budget' => 'ppc',
		'PPC Consulting' => 'ppc',
		'PPC Conversion Optimization' => 'ppc',
		'PPC Facebook' => 'ppc',
		'PPC Mobile' => 'ppc',
		'PPC Media' => 'ppc',
		'PPC Banner' => 'ppc',
		'PPC Other' => 'ppc',
		'SEO Management' => 'seo',
		'SEO Budget' => 'seo',
		'SEO Consulting' => 'seo',
		'SEO Offsite' => 'seo',
		'SEO Audit' => 'seo',
		'SEO Infographic' => 'seo',
		'SEO Other' => 'seo',
		'Media Management' => 'ppc',
		'Media Budget' => 'ppc',
		'Media Consulting' => 'ppc',
		'Media Other' => 'ppc',
		'SMO Management' => 'smo',
		'SMO Budget' => 'smo',
		'SMO Consulting' => 'smo',
		'SMO Other' => 'smo',
		'Email Management' => 'email',
		'Email ESP Budget' => 'email',
		'Email Volume Budget' => 'email',
		'WebDev Management' => 'webdev',
		'WebDev Budget' => 'webdev',
		'WebDev Consulting' => 'webdev',
		'WebDev Optimization' => 'webdev',
		'WebDev Banner' => 'webdev',
		'WebDev Other' => 'webdev',
		'Hosting' => 'webdev',
		'Partner PPC' => 'partner',
		'Partner PPC Budget' => 'partner',
		'Partner SMO' => 'partner',
		'Partner SEO' => 'partner',
		'Partner Start-Up Fee' => 'partner',
		'Partner WebDev Budget' => 'partner',
		'QuickList Pro' => 'sbs',
		'GoSEO Pro' => 'sbs',
		'Government Services' => 'wgs',
		'FetchBack' => 'ppc',
		'Copy' => '',
		'Call Tracking' => ''
	);
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_id')
		);
		self::$belongs_to = array('client_payment');
		self::$cols = self::init_cols(
			new rs_col('id'               ,'int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_payment_id','int'    ,null,0   ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('client_id'        ,'bigint' ,null,0   ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('type'             ,'enum'   ,32  ,''  ,rs::NOT_NULL),
			new rs_col('amount'           ,'double' ,null,0   ,rs::NOT_NULL),
			new rs_col('rep_pay_num'      ,'tinyint',null,0   ,rs::NOT_NULL),
			new rs_col('rep_comm'         ,'double' ,null,-1  ,rs::NOT_NULL)
		);
	}
	
	public static function get_type_options()
	{
		return array_keys(self::$part_types);
	}
	
	public static function get_departments()
	{
		$depts = array_unique(array_filter(array_values(self::$part_types)));
		sort($depts);
		return $depts;
	}
	
	public static function get_types_from_department($target)
	{
		$types = array();
		foreach (self::$part_types as $type => $department)
		{
			if ($department == $target)
			{
				$types[] = $type;
			}
		}
		return $types;
	}
	
	public static function get_management_part_types()
	{
		return array(
			'PPC Management',
			'PPC Facebook',
			'SEO Management',
			'Media Management',
			'SMO Management',
			'Email Management',
			'SEO Offsite',
			'PPC Consulting',
			'Media Consulting',
			'SEO Consulting',
			'SEO Audit'
		);
	}
	
	public function get_deparment()
	{
		return (array_key_exists($this->type, self::$part_types) ? self::$part_types[$this->type] : null);
	}
}

?>