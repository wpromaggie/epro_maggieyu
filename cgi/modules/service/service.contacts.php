<?php

class mod_service_contacts extends mod_service
{
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'view_all';
	}
	
	public function display_view_all()
	{
		$contacts = db::select("SELECT * FROM eppctwo.partner_contacts ORDER BY created DESC", "ASSOC");
		
		$cols = array(
			'Id' => 'id',
			'Name' => 'name',
                        'Practice' => 'practice',
                        'Website' => 'url',
			'Email' => 'email',
			'Phone' => 'phone',
			'Rep' => 'rep',
                        'Interests' => 'interests',
                        'Notes' => 'notes',
			'Date' => 'date',
			'Created' => 'created'
		);
                
                $table = "<table id='partner-contacts'><thead><tr>";
		foreach($cols as $col => $key){
			$table .= "<th>$col</th>";
		}
		$table .= "</tr></thead><tbody>";
		
		foreach($contacts as $contact){
			$table .= "<tr>";
			foreach($cols as $col){
				
				$value = $contact[$col];
				
				$table .= "<td>$value</td>";
			}
			$table .= "</tr>";
		}
		$table .= "</tbody></table>";
                
        ?>

                <h2>Partner Contacts</h2>
                
                <?php echo $table;
        
	}
        
	public function sts_submit_new()
	{
		//e($_POST);
		//db::dbg();
		unset($_POST['_sts_func_']);

		$_POST['created'] = date(util::DATE_TIME);

		if (isset($_POST['phone'])){
			$_POST['phone'] = preg_replace("/[^0-9]/", "", $_POST['phone']);
		}

		if (isset($_POST['date'])){
			$_POST['date'] = date(util::DATE, strtotime($_POST['date']));
		}
		
		if (isset($_POST['company'])){
			$_POST['practice'] = $_POST['company'];
			unset($_POST['company']);
		}

		if (isset($_POST['interested'])){
			$_POST['interests'] = $_POST['interested'];
			unset($_POST['interested']);
		}

		if (isset($_POST['goals'])){
			$_POST['notes'] = 'Goals: '.$_POST['goals'];
			unset($_POST['goals']);
		}

		if (!isset($_POST['rep'])){
			$_POST['rep'] = 'wpromote.com';
		}

		$id = db::insert('partner_contacts', $_POST);

		//return id
		echo json_encode($id);
	}
	
	public function sts_submit_new_referral()
	{
		unset($_POST['_sts_func_']);

		$_POST['created'] = date(util::DATE_TIME);

		if (isset($_POST['phone']))
		{
			$_POST['phone'] = preg_replace("/[^0-9]/", "", $_POST['phone']);
		}
		
		$notes = "";
		if(isset($_POST['comments'])){
			$notes .= $_POST['comments'];
			unset($_POST['comments']);
		}
		if(isset($_POST['referrer'])){
			$notes .= "| Refered By: ".$_POST['referrer'];
			unset($_POST['referrer']);
		}
		$_POST['notes'] = $notes;
		
		//db::dbg();
		$id = db::insert('partner_contacts', $_POST);

		//return id
		echo json_encode($id);
	}
        
}
?>