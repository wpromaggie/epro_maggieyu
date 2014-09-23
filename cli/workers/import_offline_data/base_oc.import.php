<?php
/**
 * Offline Conversion Base Class
 */
abstract class base_oc{
	public static $table,$market,$storage_path;

	abstract public static function run();

	/**
	 * @TODO add provision for other markets with offline conversion
	 */
	public static function init(){
		$class = get_called_class();
		$aid = explode('_',$class);

		self::$storage_path = epro\OFFLINE_CONVERSION_PATH.$aid[1].'/';

		if(!file_exists(self::$storage_path)){
			mkdir(self::$storage_path);
		}

		if(!isset($aid[1]))
			throw new Expcetion("enet aid value is not set ".__FILE__.' '.__LINE__."\n");
		self::$table = self::$market."_objects.offline_conversion_{$aid[1]}";
	}

	public static function is_aid_import_table_exists(){

	}

	public static function create_oc_table(){

	}
}
?>