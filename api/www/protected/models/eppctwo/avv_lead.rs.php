<?php

class mod_eppctwo_avv_lead extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('customerid');
		self::$cols = self::init_cols(
			new rs_col('customerlastname'           ,'varchar'               ,128   ,''		),
			new rs_col('customerfirstname'          ,'varchar'               ,128   ,''		),
			new rs_col('address'                    ,'varchar'               ,128   ,''		),
			new rs_col('city'                       ,'varchar'               ,128   ,''		),
			new rs_col('customerfullname'           ,'varchar'               ,256   ,''		),
			new rs_col('customerid'                 ,'bigint'                ,20    ,''		),
			new rs_col('customermiddlename'         ,'varchar'               ,128   ,''		),
			new rs_col('dayphone'                   ,'varchar'               ,64    ,''		),
			new rs_col('dealerid'                   ,'bigint'                ,20    ,''		),
			new rs_col('emailaddress'               ,'varchar'               ,128   ,''		),
			new rs_col('eveningphone'               ,'varchar'               ,128   ,''		),
			new rs_col('groupdir'                   ,'varchar'               ,32    ,''		),
			new rs_col('lastaction'                 ,'varchar'               ,32    ,''		),
			new rs_col('make'                       ,'varchar'               ,64    ,''		),
			new rs_col('model'                      ,'varchar'               ,64    ,''		),
			new rs_col('postalcode'                 ,'varchar'               ,16    ,''		),
			new rs_col('preferredcontact'           ,'varchar'               ,64    ,''		),
			new rs_col('sales_cycle'                ,'varchar'               ,64    ,''		),
			new rs_col('salesid'                    ,'bigint'                ,20    ,''		),
			new rs_col('contactfirstname'           ,'varchar'               ,64    ,''		),
			new rs_col('contactlastname'            ,'varchar'               ,64    ,''		),
			new rs_col('soldidentifier'             ,'varchar'               ,64    ,''		),
			new rs_col('soldtype'                   ,'varchar'               ,64    ,''		),
			new rs_col('soldtimestamp'              ,'datetime'              ,''    ,''		),
			new rs_col('sourcedescription'          ,'varchar'               ,128   ,''		),
			new rs_col('stateorprovince'            ,'varchar'               ,64    ,''		),
			new rs_col('stockno'                    ,'varchar'               ,256   ,''		),
			new rs_col('tstamp'                     ,'datetime'              ,''    ,''		),
			new rs_col('vehicleother'               ,'varchar'               ,64    ,''		),
			new rs_col('year'                       ,'smallint'              ,5     ,''		),
			new rs_col('optoutdate'                 ,'date'                  ,''    ,''		)
			);
	}
}
?>
