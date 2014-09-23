<?php
/**
 */

class worker_email_directbuy_weekly extends worker{
	private $rpt_all,$rpt_pros_attnd,$rpt_cb_src;
	private $email_attachment;
	private static $rpts = array('rpt_all','rpt_pros_attnd','rpt_cb_src');

	public function run(){
		$this->email_attachment = sprintf("/tmp/directbuy_weekly_%s.zip",date('Ymd'));
		$this->set_rpt_filenames();
		$this->create_reports();
		$this->create_compressed_file_for_export();
		$this->send_email();
	}

	private function send_email(){
		$email = util::get_phpmailer();
		$email->addAddress('db@wpromote.com','Chimdi Azubuike');
		$email->Subject = "Weekly DirectBuy Report";

		$str_files_included = '';
		foreach(self::$rpts as $rpt){
			$str_files_included .= $this->get_rpt_name($rpt).'<br>';
		}
		$email->Body = sprintf("Attached are the weekly DirectBuy Reports<br>
								Files Included:<br>
								%s<br><br>",
								$str_files_included);
		$email->isHTML(true);
		$email->addAttachment($this->email_attachment);

		if(!$email->send()){
			e($email->ErrorInfo);
		}else{
			e('Message Sent!');
		}
	}

	private function set_rpt_filenames(){
		$this->rpt_all = sprintf("dall_data_%s.csv",date('Ymd'));
		$this->rpt_pros_attnd = sprintf("prospects_attended_presentation_thru_%s.csv",date('Y-m-d'));
		$this->rpt_cb_src = sprintf("conversion_by_source_thru_%s.csv",date('Y-m-d'));
	}

	private function create_compressed_file_for_export(){
		$files = '';
		foreach(self::$rpts as $rpt){
			$files .= ' '.$this->get_rpt_file($rpt); 
		}
		$cmd = sprintf("zip %s %s",
						$this->email_attachment,
						$files);
		e($cmd);
		e(shell_exec($cmd));
	}

	private function get_rpt_file($name){
		return '/tmp/'.$this->{$name};
	}

	private function get_rpt_name($name){
		return $this->{$name};
	}

	private function create_reports(){
		foreach(self::$rpts as $rpt){
			util::create_file_from_db($this->get_rpt_file($rpt), $this->get_data($rpt),',');
		}
	}

	private function get_data($report){
		return $this->{'get_report_'.substr($report,4)}();
	}

	/**
	 * get_report_all: gets all data from inception as a raw dump of predefined fields
	 */
	protected function get_report_all(){
		$q = "SELECT
				directbuy_id,
				db.bionic_id,
				submit_date,
				db.first_name,
				db.last_name,
				db.street1,
				db.city,
				db.state,
				db.postal_code,
				db.primary_phone,
				email,
				appointment_date_time,
				original_appointment_date_time,
				last_modified,
				db.lead_source,
				campaign_code,
				utm_term,
				utm_content,
				dls.name AS lead_status,
				if(open_house_confirmation = 1,'YES','NO') AS open_house_confirmation
			FROM `client_db_directbuy`.`directbuy` db
			LEFT JOIN `client_db_directbuy`.`directbuy_lead_status` dls ON dls.id = db.lead_status
			WHERE submit_date BETWEEN '2014-02-16' AND CURDATE()";
		return db::select($q,'ASSOC');
	}

	/**
	 * get_report_cb_src: conversions by source
	 */
	protected function get_report_cb_src(){
		$q = "SELECT
					IF(ISNULL(lead_source),'TOTAL',lead_source) AS lead_source,
					count(lead_source) AS _count
				FROM (
					SELECT
						directbuy_id,
						bionic_id,
						lead_source,
						lead_status,
						dls.name
					FROM `client_db_directbuy`.`directbuy` db
					LEFT JOIN `client_db_directbuy`.`directbuy_lead_status` dls ON dls.id = db.lead_status
					WHERE lead_status IN (5,13,14,15,16,17,38,39) 
						AND DATE(creation_date) BETWEEN '2014-02-15'
						AND CURDATE() 
						AND (lead_source LIKE '%WP%' OR lead_source LIKE '%WPROMOTE%' OR lead_source LIKE '%QUANT%')
						GROUP BY directbuy_id
					) AS tbla
				GROUP BY lead_source WITH ROLLUP";
		return db::select($q,'ASSOC');
	}

	protected function get_report_pros_attnd(){
		$q = "SELECT 
				db.last_modified, 
				db.gclid, 
				db.directbuy_id, 
				db.lead_status, 
				dls.name, 
				db.appointment_date_time,
				db.original_appointment_date_time,
				db.utm_term
			FROM `client_db_directbuy`.`directbuy` db
			LEFT JOIN `client_db_directbuy`.`directbuy_lead_status` dls on dls.`id` = db.lead_status
			WHERE db.lead_status IN (6,19,5,13,14,15,16,17) AND NOT ISNULL(db.gclid)
			GROUP BY db.directbuy_id";
		return db::select($q,'ASSOC');
	}


}
?>