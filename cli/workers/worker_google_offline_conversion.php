<?php
/*
 * google_offline_conversion.php
 * Modified by Yafu Li
*/

require_once('cli.php');
util::load_lib('eac','account','agency','ppc','delly');
cli::run();

class worker_google_offline_conversion extends worker{
	const BATCH_SIZE = 25;

	public function run(){
		$aid =& $this->job->account_id;
		$market = 'g';
		$table = "{$market}_objects.offline_conversion_{$aid}";

		//Select eligible account ids
		$r = db::select("SELECT 
					id, g_acct_id, gclid, conversion_name ,conversion_value ,conversion_time
				FROM
					{$table}
				WHERE
					success = 0",
				'ASSOC');


		$ids = $gaccts = array();
		foreach($r as $row){
			$ids[] = "'{$row['id']}'";
			$gaccts[$row['g_acct_id']][] = array(
				'gclid'=>$row['gclid'],
				'name'=>$row['conversion_name'],
				'value'=>$row['conversion_value'],
				'time'=>$row['conversion_time']
			);
		}
		
		$account_ids = array_keys($gaccts);
		foreach($account_ids as $gid){
			$api = base_market::get_api('g', $gid);
			e($api);
			$date = date('Ymd His e',time());
			db::dbg();

			$batch_count = 0;
			//push to google offline conv & if conflicts happen, update success, error_message fields of {$market}_objects.offline_conversion_{$aid}
			foreach($gaccts[$gid] as $conv){

				if ($batch_count % self::BATCH_SIZE == 0) {
					$error_rows = $batch  = array();
				}

				$batch[] = $conv;
				$batch_count++;

				if ( $batch_count % self::BATCH_SIZE == 0 || $batch_count == count($gaccts[$gid]) ) {
					$this->job->update_details("SUBMITTING BATCH WITH COUNT OF: ".count($batch));
					$response = $api->set_offline_conversion($batch);


					if (method_exists($response, 'getMessage')) {   //if conflict, update the success status to -1 and error_msg in offline_conversion_{aid} table
						if(preg_match("/OfflineConversionError/",$response->getMessage())){
							//$errors = $response->detail->ApiExceptionFault->errors;
							$this->job->update_details("BATCH CONTAINED ".count($response->detail->ApiExceptionFault->errors)." ERRORS");
							foreach ($response->detail->ApiExceptionFault->errors as $error) {
								e($error);
								$error_reason = $error->enc_value->reason;
								$error_fieldPath = $error->enc_value->fieldPath;

								//find the error index
								$error_rows[] = $error_index = preg_replace("/[^0-9]/","",$error_fieldPath);
								$error_row = $batch[$error_index];
								e($error_row);

								//update the 'success' and 'error_reason' fields in offline_conversion_{aid} table
								$set_data = array('success'=>-1, 'error_reason'=>$error_reason,'sent'=>date('Y-m-d H:i:s'));
								$where_query = "`gclid` = :gclid && `conversion_name` = :conversion_name";
								$where_data = array('gclid'=>$error_row['gclid'],'conversion_name'=>$error_row['name']);

								db::update($table, $set_data, $where_query, $where_data);
							}
						}
					}

				}
			}

			//update the rest records' success status to 1 in offline_conversion_{aid} table
			$set_data = array('success'=>1, 'sent'=>date('Y-m-d H:i:s'));
			$where_query = "`success` = 0";

			db::update($table, $set_data, $where_query);

		}
	}

	

}

?>