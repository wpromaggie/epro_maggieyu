7 <?php
/**
 * goc_A430104416 importer for directbuy
 *
 */
if(!class_exists('base_oc'))
	include_once(epro\CLI_PATH.'workers/import_offline_data/base_oc.import.php');

class goc_A430104416 extends base_oc{

	public static $table,$job;

	public static function init(){
		self::$market = 'g';
		parent::init();
	}


	public static function run(){
		list($job) = func_get_args();
    	self::$job = $job;
        self::init();
        self::rsync("/home/ubuntu/",self::$storage_path);
		// pull data from directbuy server

		self::load_directbuy();
		self::load_mv();
		self::load_ion();
        self::remote_move_files();

		MergeGoc::merge_to_goc();
	}
	private function remote_move_files(){
		shell_exec('ssh -i '.epro\DIRECTBUY_PEM.' '.epro\DIRECTBUY_HOST.' "sudo mv /home/ubuntu/ion_ready/* /home/ubuntu/ion_done/."');
		shell_exec('ssh -i '.epro\DIRECTBUY_PEM.' '.epro\DIRECTBUY_HOST.' "sudo mv /home/ubuntu/db_ready/* /home/ubuntu/db_done/."');
		shell_exec('ssh -i '.epro\DIRECTBUY_PEM.' '.epro\DIRECTBUY_HOST.' "sudo mv /home/ubuntu/mv_ready/* /home/ubuntu/mv_done/."');

	}
	// sync directbuy server with local server
	private function rsync($src_path, $dst_path){
		// --recursive == -r ; 
		// --time = -t preserve modification time
		// --links = -l copy symlinks as symlinks
		// --compress = -z, compress file data during the transfer
		// -L : follow symbolic link

		// rsync example
		// $ rsync -avz -e "ssh -i /home/thisuser/cron/thishost-rsync-key" remoteuser@remotehost:/remote/dir /this/dir/	
		$rsync_args = 'rsync --recursive --times -L --compress --delete --omit-dir-times -e ';
		$rsync_pem = '"ssh -i '.epro\DIRECTBUY_PEM.'" ';
		$rsync_path = epro\DIRECTBUY_HOST.":{$src_path} {$dst_path}";
		e("$rsync_args $rsync_pem $rsync_path");
		shell_exec("$rsync_args $rsync_pem $rsync_path");
	}

	public function get_files($path){
		// prepare statement
	 	return array_diff(scandir(self::$storage_path.$path),array('.','..'));
	}

	public function load_directbuy(){
		// get all directories names
		// $dirs = self::get_files("/data_directbuy_uploads");


	 	// get all files names
	 	$files = self::get_files("/db_ready/");
	 	// for every file exec load
	 	foreach($files as $file){
	 		if(substr($file,-3) == "txt")
		 		self::exec_load_directbuy($file);
	 	}
	 }

	public function load_mv(){
	 	$files = self::get_files("/mv_ready/");
	 
	 	// for each file, get data
	 	foreach($files as $file){
	 		// make sure file is csv
	 		if(substr($file,-3) == "csv")
	 			self::exec_load_mv($file);
	 	}
	 	self::set_update_mv_to_directbuy();
	 }

	 public function load_ion(){
	 	// get files
	 	$files = self::get_files("/ion_ready/");

	 	// for each file, get data
	 	foreach($files as $file){
	 		// make sure file is csv
	 		if(substr($file,-3) == "csv")
	 			self::exec_load_ion($file);
	 	}
	 	self::set_update_ion_response_string_to_id();
	 	self::set_update_ion_date();
	 	self::set_update_ion_to_directbuy();
	 }

	 // set object
	public function set_object($k,$v){
		$obj = array();
        	foreach($k as $idx => $head){
            	$obj[$head] = trim($v[$idx]);
        }
        return $obj;
	}

	private function set_db_object($k,$v){
		$obj = self::set_object($k,$v);
		//Check INT Datatype
		$obj['open_house_confirmation'] = (is_numeric($obj['open_house_confirmation']))? $obj['open_house_confirmation'] : 0;
		$obj['tech_barn_score'] = (is_numeric($obj['tech_barn_score']))? $obj['tech_barn_score'] : 0;
		
		return $obj;
	}

	// set ion object
	private function set_ion_object($k,$v){
		$obj = self::set_object($k,$v);
		if(strtotime($obj['first_contact_date']))     
        	$obj['first_contact_date'] = date('Y-m-d H:i:s',strtotime($obj['first_contact_date']));

		if(strtotime($obj['last_contact_date']))     
        	$obj['last_contact_date'] = date('Y-m-d H:i:s',strtotime($obj['last_contact_date']));

		if(strtotime($obj['ion_converted_date']))             	
        	$obj['ion_converted_date'] = date('Y-m-d H:i:s',strtotime($obj['ion_converted_date']));

        if(strtotime($obj['schedule_date'])) 
        	$obj['schedule_date'] = date('Y-m-d',strtotime($obj['schedule_date']));
        
		if(strtotime($obj['schedule_time']))  
       		$obj['schedule_time'] = date('H:i:s',strtotime($obj['schedule_time']));

        preg_match("/\d+/",$obj['api_response_lead_id'],$match);
        $obj['api_response_lead_id'] = $match[0];
        return $obj;
    }

    // set mv object
    private function set_mv_object($k,$v){
    	$obj = self::set_object($k,$v);
       	$obj['acquisition_date'] = date('Y-m-d H:i:s',strtotime($obj['acquisition_date']));
        $obj['member_activated'] = date('Y-m-d',strtotime($obj['member_activated']));
        return $obj;
    }   

	private function set_update_rec($o,$fields){
        $set = array();
    	foreach($fields as $field){
        	$set[$field] = $o[$field];
      	}
   		return $set;
    }


    protected static function set_update_ion_response_string_to_id(){
    	$q = "UPDATE `client_db_directbuy`.`ion`
				SET api_response_lead_id = MID(api_post_lead_response,LENGTH('{\"response_status\":\"success\",\"response_message\":\"')+1,7)
				WHERE api_post_lead_response LIKE '%success%' AND LENGTH(api_response_lead_id) = 0";
		db::query($q);
    }

    protected static function set_update_ion_date(){
    	$q = "UPDATE `client_db_directbuy`.`ion`
				SET first_contact_date = STR_TO_DATE(first_contact_date,'%m/%d/%Y %l:%i:%s %p'),
					ion_converted_date = STR_TO_DATE(ion_converted_date,'%m/%d/%Y %l:%i:%s %p'),
					last_contact_date = STR_TO_DATE(last_contact_date,'%m/%d/%Y %l:%i:%s %p')
				Where first_contact_date LIKE '%/%'";
		db::query($q);
    }

    protected static function set_update_ion_to_directbuy(){
    	$q = "UPDATE `client_db_directbuy`.`directbuy` db, `client_db_directbuy`.`ion` i
				SET db.gclid = i.gclid,
					db.utm_term = i.utm_term,
					db.utm_content = i.utm_content,
					db.utm_source = i.utm_source,
					db.utm_medium = i.utm_medium,
					db.utm_campaign = i.utm_campaign,
					db.mkwid = i.mkwid
				WHERE db.directbuy_id = i.api_response_lead_id";
		db::query($q);
    }

    protected static function set_update_mv_to_directbuy(){
    	$q = "UPDATE `client_db_directbuy`.`directbuy` db, `client_db_directbuy`.`member_value` mv
				SET 
				db.mv_region_code = mv.region_code,
				db.mv_source = mv.source,
				db.mv_acquisition_date = mv.acquisition_date,
				db.mv_member_activated = mv.member_activated,
				db.mv_retail_price = mv.retail_price,
				db.mv_negotiated_price = mv.negotiated_price,
				db.track_membership = 1
				WHERE db.bionic_id = mv.bionic_id";
		db::query($q);
    }

	public function check_file($file){
		if((int)(shell_exec("wc -l $file") < 2)){
			return false;
    	}
    	return true;
	}

	public function exec_load_directbuy($filename){
		$filename = self::$storage_path."db_ready/".$filename;
	 	e($filename);
	 	// check if file has more than 2 lines
	 	if(self::check_file($filename)){
	 		if($h = fopen($filename,'r')){
  	     		$head = true;
        		while(!feof($h)){
                	if($head){
                    	$header = explode("\t",trim(fgets($h,9999)));
                    	//db::debug($header);
                    	$header[0] = 'directbuy_id';

                    	for($i=0;$i<count($header);$i++){
                    	    $header[$i] = strtolower(preg_replace("/\W/",'',$header[$i]));
                    	}
                    	//db::debug($header);
                   		$head = false;
                	}
                	
                	//get Row Body
                	$row = explode("\t",trim(fgets($h,9999)));
                	if(count($row) > 1){
                		$o = self::set_db_object($header,$row);
                    	$u = self::set_update_rec($o,array('directbuy_id',
                	                                        'bionic_id',
															'lead_status',
        	                                                'appointment_date_time',
    	                                                    'initial_scheduling',
	                                                        'last_modified')
	                        								);
                    	db::insert_update("`client_db_directbuy`.`directbuy`",array("directbuy_id"),$o);
						db::insert('`client_db_directbuy`.`db_update`',$u);
              		}
        		}
			}
    	} 
	} // end of exec_load_directbuy


	 public function exec_load_ion($filename){
	 	$filename = self::$storage_path . "/ion_ready/". $filename;
	 	// check if file has more than 2 lines
	 	if(self::check_file($filename)){
	 		e($filename);
		 	if($h = fopen($filename,'r')){
            	$head = true;
            	while(!feof($h)){
                	if($head){      
                    	$header = explode("|",trim(fgets($h,9999)));
                    	$header[0] = 'ion_respondent_id';
                    	for($i=0;$i<count($header);$i++){
                        	$header[$i] = strtolower(preg_replace("/\s/",'_',$header[$i]));
                        	$header[$i] = strtolower(preg_replace("/\W/",'',$header[$i]));
                    	}                   
                    	$head = false;      
                	}               

                	//get Row Body  
                	$row = explode("|",trim(fgets($h,9999)));
                	if(count($row) > 1){
                    	$o = self::set_ion_object($header,$row);
                    	db::dbg();
                    	db::insert_update("`client_db_directbuy`.`ion`",array("ion_respondent_id"),$o);
                	}               
            	}           
        	}
    	}       
    } // end exec_load_ion 	

	 public function exec_load_mv($filename){
	 	$filename = self::$storage_path."mv_ready/{$filename}";
	 	e($filename);
	 	if(self::check_file($filename)){
        	if($h = fopen($filename,'r')){
            	$head = true;
            	while(!feof($h)){
                	//Get the Head  
                	if($head){      
                    	$header = explode("\t",trim(fgets($h,9999)));
                    	$head = false;      
                	}               

                	//Get the Body rows
                	$row = explode("\t",trim(fgets($h,9999)));
                	if(count($row) > 1){
                    	$o = self::set_mv_object($header,$row);
                    	db::insert('`client_db_directbuy`.`member_value`',$o);
                	}               
            	}           
        	}
        }       
	} // end of method exec_load_mv


} // end of class goc_A430104416 




/*  MergeGoc: merge all data from client_db_directbuy database to feed g_objects.offline_conversion_{aid} */
/*  Created by Yafu Li */
/*  Date: 06/18/2014   */
class MergeGoc {

		protected static function get_membership_data(){
			db::dbg();
			$q = "SELECT 
					mv.bionic_id AS bionic_id,
					db.directbuy_id AS directbuy_id,
					DATE_FORMAT(member_activated,'%Y-%m-%dT%H:%i:%s') AS 'Conversion Time',
					negotiated_price AS 'Conversion Value',
					'membership' AS 'Conversion Name'
				FROM `client_db_directbuy`.`member_value` mv
				LEFT JOIN `client_db_directbuy`.`directbuy` db on `db`.`bionic_id` = `mv`.`bionic_id`
				WHERE NOT ISNULL(`db`.`bionic_id`)";
			$r = db::select($q,"ASSOC");

			$converted = array();
			foreach($r as $row){
				$converted[$row['directbuy_id']] = $row;
			}


			$ids = implode(',',array_keys($converted));

			//return empty array if there is nothing to query
			if(!$ids)
				return array();

			$q_ion = "SELECT 
							api_response_lead_id, 
							'add' AS 'Action',
							gclid AS 'Google Click Id', 
							utm_content AS 'adwordsContent',
							utm_medium AS 'adwordsMedium',
							utm_source AS 'adwordsSource',
							utm_term AS 'adwordsTerm',
							utm_campaign AS 'adwordsCampaign'
						FROM `client_db_directbuy`.`ion` where `api_response_lead_id` IN ({$ids})";
			$r = db::select($q_ion,"ASSOC");

			foreach($r as $row){
				if(isset($converted[$row['api_response_lead_id']]))
					$converted[$row['api_response_lead_id']] = array_merge($converted[$row['api_response_lead_id']],$row);
			}
			db::dbg_off();

			return $converted;

		}

	public static function merge_to_goc() {

		$market = 'g';

		$db_name = "client_db_directbuy";
		$directbuy_table = $db_name.".directbuy";
		$db_update_table = $db_name.".db_update";
		$mv_table = $db_name.".member_value";
		$ion_dump_table = $db_name.".ion";
		$lead_status_table = $db_name.".directbuy_lead_status";
		$start_date = date('Y-m-d',strtotime('-5 days'));
		$g_acct_id = '3688443951';// Assumed that this will be the only account using offline conversion for now 2014-07-09

		//Select APPOINTMENTS data
		$app_result = db::select("SELECT
							'add' AS 'Action',
							gclid AS 'Google Click Id',
							'appointments' AS 'Conversion Name',
							'1' AS 'Conversion Value',
							DATE_FORMAT(ion.first_contact_date,'%Y-%m-%dT%H:%i:%s') AS 'Conversion Time',
							utm_content AS 'adwordsContent',
							utm_medium AS 'adwordsMedium',
							utm_source AS 'adwordsSource',
							utm_term AS 'adwordsTerm'
							from $directbuy_table  db
							left join $ion_dump_table ion on ion.api_response_lead_id = db.directbuy_id
							where appointment_date_time > '{$start_date}' AND NOT ISNULL(gclid) AND LENGTH(gclid) > 16 order by ion.first_contact_date",
						"ASSOC");


		//Select PRESENTATION data
		$pre_result = db::select("SELECT
							'add' AS 'Action',
							gclid AS 'Google Click Id',
							'presentations' AS 'Conversion Name',
							'1' AS 'Conversion Value',
							DATE_FORMAT(db.appointment_date_time,'%Y-%m-%dT%H:%i:%s') AS 'Conversion Time',
							utm_content AS 'adwordsContent',
							utm_medium AS 'adwordsMedium',
							utm_source AS 'adwordsSource',
							utm_term AS 'adwordsTerm'
							from $directbuy_table db
							left join $lead_status_table dls on dls.`id` = db.lead_status
							left join $db_update_table du on du.directbuy_id = db.directbuy_id
							left join $ion_dump_table ion on ion.api_response_lead_id = db.directbuy_id
							where db.lead_status IN (6,19,5,13,14,15,16,17) 
									AND NOT ISNULL(gclid) 
									AND LENGTH(gclid) > 0 
									AND db.appointment_date_time > '{$start_date}'
							GROUP BY db.directbuy_id order by db.appointment_date_time",
						"ASSOC"); 

		//Select MEMBERSHIP data
		$mem_result = self::get_membership_data();
		

		//combine the conversion results from APPOINTMENTS, PRESENTATION and MEMBERSHIP
		$r = array_merge((array)$app_result, (array)$pre_result, (array)$mem_result);

		date_default_timezone_set('America/Phoenix');

        foreach($r as $row){
        	if(strlen(trim($row['Google Click Id'])) === 0)
        		continue;

            $io = array('id'=>substr(sha1(mt_rand().''.time()),0,30),'action'=>'add','utm'=>'');
            $other = array();
            foreach($row as $field => $value){
                switch($field){
                    case 'Google Click Id':
                        $io['gclid'] = $value;
                        break;
                    case 'Conversion Name':
                        $io['conversion_name'] = $value;
                        break;
                    case 'Conversion Value':
                        $io['conversion_value'] = $value;
                        break;
                    case 'Conversion Time':
                        $io['conversion_time'] = date('Ymd His e',strtotime($value));
                        break;
                    case 'adwordsContent':
                        $other['utm_content'] = $value;
                        break;
                    case 'adwordsMedium':
                        $other['utm_medium'] = $value;
                        break;
                    case 'adwordsSource':
                        $other['utm_source'] = $value;
                        break;
                    case 'adwordsTerm':
                        $other['utm_term'] = $value;
                        break;
                    default:
                        break;
                }
            }
            $io['g_acct_id'] = $g_acct_id;
            $io['utm'] = json_encode($other);
            db::insert("`g_objects`.`offline_conversion_A430104416`",$io);
        }

        date_default_timezone_set('America/Los_Angeles');
	}

	
}
?>