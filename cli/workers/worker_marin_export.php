<?php
util::load_lib('ppc');


define('MARIN_FTP_USER','ftp.ruboid@directbuy.marinsoftware.com');
define('MARIN_FTP_HOST','integration.marinsoftware.com');
define('MARIN_FTP_PASS','d1r3ctBuy14');
define('MARIN_REMOTE_PATH','/pmld1dzsu0/revuploadbyorderid');
define('MARIN_LOCAL_PATH',epro\REPORTS_PATH.'marin');

/*
		$export = "export SSHPASS=d1r3ctBuy14";
		$user = "ftp.ruboid@directbuy.marinsoftware.com";
		$host = "integration.marinsoftware.com";
		$remote_path = "/pmld1dzsu0/revuploadbyorderid";
*/

/**
 * worker_marin_update
 * for the specific conversions listed, we are using the marin standard format such that NULL
 * is set for values that are not included in the records being pulled
 */
class worker_marin_export extends worker{
	private static $date_start, $export_file;

	public function run(){
		self::set_date_range();
		// only used for local testing so that the export process does not fail
		if(!file_exists(MARIN_LOCAL_PATH))
			mkdir(MARIN_LOCAL_PATH);

		//Create export file
		self::$export_file = MARIN_LOCAL_PATH.'/bulkrevenueorderidadd_wpro_'.date("Ymd").'.txt';
		$data = self::merge_data();
		self::create_file($data);
		e(self::send_file());
	}

	private static function set_date_range(){
		self::$date_start = date('Y-m-d',strtotime("- 1 day"));
	}

	/**
	 * get_membership
	 * simple query against the ion table for all respondents with the appointment set and directbuy_id rep
	 * as order_id & api_response_lead_id
	 * 
	 * @return void
	 */
	protected static function get_appointments(){
		$q = sprintf("SELECT
					DATE(ion_converted_date) AS `Date`,
					api_response_lead_id AS `Order ID`,
					1 AS 'appointment Conv',
					0 AS 'appointment Rev',
					NULL AS 'presentation Conv',
					NULL AS 'presentation Rev',
					NULL AS 'new_member Conv',
					NULL AS 'new_member Rev'
				FROM `client_db_directbuy`.`ion` 
				WHERE 
					NOT ISNULL(`api_response_lead_id`) 
					AND LENGTH(`api_response_lead_id`) > 0 
					AND LENGTH(`schedule_date`) > 0
					AND DATE(ion_converted_date) = '%s'",self::$date_start);

		return db::select($q,"ASSOC");
	}


	/**
	 * get_presentations
	 * presentation conversion dates are based on the last_modified date
	 */
	protected static function get_presentations(){
		$q = sprintf("SELECT 
					DATE(last_modified) AS `Date`,
					directbuy_id AS `Order ID`,
					NULL AS 'appointment Conv',
					NULL AS 'appointment Rev',
					1 AS 'presentation Conv',
					0 AS 'presentation Rev',
					NULL AS 'new_member Conv',
					NULL AS 'new_member Rev'
				FROM (
				select directbuy_id, lead_status, MAX(`last_modified`) AS last_modified, dls.name, dls.id from `client_db_directbuy`.`db_update` du
				left join `client_db_directbuy`.`directbuy_lead_status` dls on dls.`id` = du.lead_status
				where lead_status IN (6,19,5,13,14,15,16,17) 
				group by directbuy_id
				HAVING DATE(`last_modified`) = '%s'
				order by last_modified
				) AS prsnt",self::$date_start);

		return db::select($q,"ASSOC");
	}

	protected static function get_membership(){
		$q = sprintf("SELECT
					DATE(acquisition_date) AS `Date`,
					directbuy_id AS `Order ID`,
					NULL AS 'appointment Conv',
					NULL AS 'appointment Rev',
					NULL AS 'presentation Conv',
					NULL AS 'presentation Rev',
					1 AS 'new_member Conv',
					negotiated_price AS 'new_member Rev'
				from `client_db_directbuy`.`member_value` mv
				left join `client_db_directbuy`.`directbuy` db on db.bionic_id = mv.bionic_id
				where DATE(`member_activated`) = '%s'",self::$date_start);

		return db::select($q,"ASSOC");
	}



	// get data from client_db_directbuy
	public function merge_data(){
		$ca = self::get_appointments();
		$cp = self::get_presentations();
		$cm = self::get_membership();
		return array_merge($ca,$cp,$cm);
	}

	public function create_file($data){
		$head = true;
		$fp = fopen(self::$export_file,"w");

		foreach($data as $content){
			if($head){
				$head = false;
				fputcsv($fp,array_keys($content),"\t");
			}
			fputcsv($fp,$content,"\t");
		}
		fclose($fp);
	}// end of create_file method

	/**
	 * send_file
	 * sftp to marin server
	 */
	public static function send_file(){
		$cmd = sprintf('expect -c "
						set timeout -1
						spawn sftp %s@%s:%s
						expect "password"
						send \"%s\n\"
						send \"put %s\n\"
						send \"quit\n\"
						interact "',MARIN_FTP_USER,MARIN_FTP_HOST,MARIN_REMOTE_PATH,MARIN_FTP_PASS,self::$export_file);
		e($cmd);
		return shell_exec($cmd);
	}


}

?>