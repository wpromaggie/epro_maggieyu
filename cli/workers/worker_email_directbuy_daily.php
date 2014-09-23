<?php
/**
 */

class worker_email_directbuy_daily extends worker{
	private $filename;

	public function run(){
		$this->filename = sprintf("/tmp/directbuy_daily_%s.csv",date('Ymd'));
		util::create_file_from_db($this->filename, $this->get_data(),',');
		$this->send_email();
	}

	private function send_email(){
		$email = util::get_phpmailer();
		$email->addAddress('moniqua@wpromote.com','Moniqua Banks');
		$email->addAddress('angelo@wpromote.com','Angelo Lilo');
		$email->Subject = "Daily DirectBuy Report";
		$email->Body = sprintf("Attached is the daily DirectBuy Report<br>
								Start Date: %s<br>
								End Date: %s<br><br>",
								date('Y-m-d',strtotime("- 30 days")),
								date('Y-m-d'));
		$email->isHTML(true);
		$email->addAttachment($this->filename);

		if(!$email->send()){
			e($email->ErrorInfo);
		}else{
			e('Message Sent!');
		}
	}

	protected function get_data(){
		$start_date = date('Y-m-d',strtotime("- 30 days"));
		$q = "SELECT 
					db.directbuy_id, 
					db.bionic_id, 
					campaign_code, 
					utm_term, 
					utm_source, 
					utm_campaign,  
					db.lead_status, 
					dls.name as latest_leads_status, 
					gclid, creation_date, 
					db.last_modified, 
					db.appointment_date_time,
					db.original_appointment_date_time
				FROM `client_db_directbuy`.`directbuy` db
				LEFT JOIN `client_db_directbuy`.`directbuy_lead_status` dls ON dls.`id` = db.lead_status
				WHERE creation_date BETWEEN '{$start_date}' AND CURDATE() AND DATE(appointment_date_time) > '{$start_date}'
				GROUP BY db.directbuy_id
				ORDER BY appointment_date_time";
		e($q);
		return db::select($q,'ASSOC');
	}


}
?>