<?php
/**
 * worker_email_exports
 * processes emails that are schedule to export to a receiptient at a specific time on one time or recuring basis
 * 
 */

util::load_lib('delly', 'ppc','cgi');

class worker_email_exports extends worker{
	public function run(){
		$email_info = $this->get_email_info($this->job->fid);


		$class_file = dirname(__FILE__)."/email_exports/email_{$fid}.php";
		$class = "email_{$fid}";

		if(file_exists($class_file)){
			if(!class_exists($class))
				include_once($class_file);
			
			$mailer = $this->get_mailer();
			$mailer->
			$body = $class::run($this->job);
			$this->set_email_recipients($this->job->fid);





		}else{
			throw new Exception("class {$class} not found. File {$class}.php is missing.");
		}
	}

	private function set_email_recipients($id){
		$receipients = $this->get_email_recipients($id);
		//Set Recipients
		foreach($recipients as $recipient){

		}
	}

	protected function get_email_recipients($id){
		$q = "SELECT 
					email,
					first_name,
					last_name,
					role
				FROM `custom_email`.`email_has_recipients` ehr ON ehr.email_id = e.id
				LEFT JOIN `custom_email`.`recipients` r ON r.id = ehr.recipients_id
				WHERE ehr.email_id = '{$id}'";

		return db::select($q,'ASSOC');
	}

	protected function get_email_info($id){
		$q = "SELECT * FROM `custom_email`.`email` e
				WHERE e.id = '{$id}'";
		
		return db::select($q,'ASSOC');
	}

	private function get_mailer(){
			
			$email = new PHPMailer();
			$email->Host = '';
			$email->SMTPAuth = true;
			$email->Username = '';
			$email->Password = '';
			$email->SMTPSecure = '';		
			$email->From = 'techsupport@wpromote.com';
			$email->FromName = 'Wpromote Hermes';
			$email->addReplyTo('techsupport@wpromote.com');
			return $email;
	}
}

?>