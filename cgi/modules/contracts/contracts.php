<?php
class mod_contracts extends module_base
{
	protected $prospect, $prospect_id;
	protected $proposal;
	
	public function get_menu()
	{
		$menu = array();
		$menu[] = new MenuItem('New Prospect', array('contracts', 'prospect'));
		$menu[] = new MenuItem('View Prospects', array('contracts', 'prospect', 'all'));
		$menu[] = new MenuItem('Edit Default Contract', array('contracts', 'edit', 'default'), array('role' => 'Leader'));
		$menu[] = new MenuItem('Edit Default Terms', array('contracts', 'edit', 'terms'), array('role' => 'Leader'));
		$menu[] = new MenuItem('Edit Packages', array('contracts', 'package'), array('role' => 'Leader'));
		return $menu;
	}
	
	public function pre_output()
	{
		//e('pre output');
		if(isset($_GET['pid'])){
			$this->prospect_id = $_GET['pid'];
			$this->prospect = new prospects(array('id' => $this->prospect_id));
			$this->proposal = new proposal($this->prospect);
		} else {
			$this->prospect = new prospects();
		}
	}
	
	public function pre_output_view(){
		cgi::redirect(util::get_sap_url($this->prospect->url_key));
	}
	
	public function get_page_menu()
	{
		$page_menu = array(
			array('prospect/edit', 'Dashboard')
		);
		if($this->prospect->status=="Signed"){
			$page_menu[] = array('prospect/payment', 'Edit Payment Info');
		}else if($this->prospect->status=="Pending") {
			$page_menu[] = array('prospect/order_table', 'Order Deatils Table');
			$page_menu[] = array('edit', 'Edit Contract Text');
			$page_menu[] = array('edit/terms', 'Edit Contract Terms');
		}
		
		$page_menu[] = array('view', 'View Contract', array('target' => '_blank'));
		return $page_menu;
	}
	
	private function menu()
	{
		return $this->page_menu($this->get_page_menu(), '');
	}
	
	public function head()
	{
		if(isset($this->prospect_id)){
		?>
			<div id="status" class="right">
				Status: <?php echo $this->prospect->status; ?> <br />
				Total: <?php echo util::format_dollars($this->prospect->get_charge_total()); ?>
			</div>
		<?php
			echo '
				<h1><i>'.$this->prospect->company.'</i> ('.$this->prospect_id.')</h1>
				'.$this->menu().'
			';

			if($this->prospect->client_id)
			{
				util::load_lib('as');
				$cl_links = as_lib::get_client_department_links($this->prospect->client_id);
				if ($cl_links){
					echo "<div id='client_dept_links'>$cl_links</div>";
				}
			}
		}
		echo "<div class='clear'></div>";
	}

	public function display_index()
	{
		echo "<h1>Welcome to SAP 2.0!!</h1>";
	}

	public function sts_get_contract_data()
	{
		util::load_lib('billing');

		$prospect = db::select_row("SELECT * FROM contracts.prospects WHERE url_key = '{$_REQUEST['url_key']}'", "ASSOC");
		$propsect_type = '';
		if(empty($prospect)){
			//check old prospects
			$prospect = db::select_row("SELECT * FROM eppctwo.prospects WHERE url_key = '{$_REQUEST['url_key']}'", "ASSOC");

			if (empty($prospect)){
				echo "FALSE";
				return;
			}
			//is small business pro?
			if ($prospect['ql_pro_package'] || $prospect['gs_pro_package']){

				$propsect_type = 'sbpro';

				$user = db::select_row("SELECT * FROM eppctwo.users WHERE id = {$prospect['user']}", "ASSOC");

				if($prospect['client_id']){
					$cc = db::select_row("
						SELECT * FROM ccs
						WHERE foreign_table='clients' AND foreign_id='{$prospect['client_id']}'
					", 'ASSOC'); 
				} else {
					$cc = db::select_row("
						SELECT * FROM ccs
						WHERE foreign_table='prospects' AND foreign_id='{$prospect['id']}'
					", 'ASSOC'); 
				}

				$cc_info = billing::cc_get_display($cc['id']);

			}

			//is 1st gen agency
			else {
				
				//these should not be created anymore, lets disable
				echo "FALSE";
				return;
			}
		}
		else {

			$propsect_type = 'saptwo';

			$user = db::select_row("SELECT * FROM eppctwo.users WHERE id = {$prospect['user_id']}", "ASSOC");

			$prospect_obj = new prospects(array('id' => $prospect['id']));
			$proposal_obj = new proposal_display($prospect_obj);

			$contract_root = $proposal_obj->build_contract();

			ob_start();
			$contract_root->printNode();
			$contract_body = ob_get_contents();
			ob_end_clean();
			
			ob_start();
			$proposal_obj->printOrderTable();
			$order_table = ob_get_contents();
			ob_end_clean();

			ob_start();
			$proposal_obj->printTerms();
			$terms = ob_get_contents();
			ob_end_clean();

			$prospect['contract_length'] = $prospect_obj->get_contract_length();

		}

		echo serialize(array(
			'prospect' => $prospect,
			'user' => $user,
			'contents' => $contract_body,
			'order_table' => $order_table,
			'terms' => $terms,
			'type' => $propsect_type,
			'cc_info' => $cc_info
		));
		
	}


	public function sts_submit_signed_contract()
	{
		util::load_lib('billing');
		if($_POST['type']=="saptwo"){

			if($_POST['payment_options']=='credit'){

				//store post values needed for the cc table
				$cc_info = array(
					'name' => $_POST['cc_name'],
					'country' => $_POST['cc_country'],
					'zip' => $_POST['cc_zip'],
					'cc_number' => $_POST['cc_number'],
					'cc_type' => $_POST['cc_type'],
					'cc_exp_month' => $_POST['cc_exp_date'],
					'cc_exp_year' => $_POST['cc_exp_year'],
					'cc_code' => $_POST['cc_code']
				);

				// check if previous payment info exists for this prospect
				$cc_id = db::select_one("select id from ccs where foreign_table = 'prospects' && foreign_id='{$_POST['id']}'");
				if (empty($cc_id)){
					if(!billing::cc_new('prospects', $_POST['id'], $cc_info)){
						//e("Error: Could not save credit card info.");
						echo 'FALSE';
						return;
					}
				} else {
					if(billing::cc_update($cc_id, $cc_info) === false){
						e("Error: Could not update credit card info.");
						echo 'FALSE';
						return;
					}
				}
			}

			// update the prospect table to show payemt info has been entered
			db::update(
				"contracts.prospects",
				array(
					'status' => 'Signed',
					'payment_method' => $_POST['payment_options'],
					'signature' => $_POST['signature'],
					'sig_title' => $_POST['title'],
					'sig_month' => $_POST['date_month'],
					'sig_day' => $_POST['date_day'],
					'sig_year' => $_POST['date_year'],
					'close_date' => date('Y-m-d'),
					'ip' => $_POST['ip'] 
				),
				"id = :id",
				array('id' => $_POST['id'])
			);
			$prospect = new prospects(array('id' => $_POST['id']));

			//email the wpro contact
			list($user_id, $company) = db::select_row("
				select user_id, company
				from contracts.prospects
				where id = {$_POST['id']}
			");

			$wpro_email = db::select_one("SELECT username FROM users where id = $user_id");
			
			$packages = $prospect->get_packages();
			$msg = "";
			$contract_total = 0;
			foreach($packages as $package){
				$vars = $prospect->get_package_vars($package['id'], TRUE);
				$msg .= "<u>".$package['name']."</u><br />";
				$total = 0;
				foreach($vars as $var){
					$msg .= $var['description'].": $".$var['value'];
					if($var['charge']){
						$total += $var['value'];
					} else {
						$msg .= ' (client pays)';
					}
					$msg .= "<br />";
				}
				$contract_total += $total;
				$msg .= "<b>Total: $$total</b><br /><br />";
			}

			$to      = $wpro_email;
			$to     .= ', mstone@wpromote.com';
			$to     .= ', mike@wpromote.com';
			$to     .= ', mwilde@wpromote.com';
			$to     .= ', david@wpromote.com';
			$to     .= ', rneldner@gmail.com';

			$subject = 'Signed SAP for '.$company;

			$message = '
				<html>
				<body>
				<p><a href="http://'.\epro\DOMAIN.'/contracts/prospect/edit/?pid='.$prospect->id.'" target="_blank">'.$company.' Details Page</a></p>
				<p>Company URL: <a href="'.$prospect->url.'" target="_blank">'.$prospect->url.'</a></p>
				<p>'.$msg.'<b>Contract Total: $'.$contract_total.'</b></p>
				</body>
				</html>
			';

			// To send HTML mail, the Content-type header must be set
			$headers  = array(
			    'MIME-Version' => '1.0',
			    'Content-type' => 'text/html; charset=iso-8859-1'
			);
			util::mail('sap@wpromote.com', $to, $subject, $message, $headers, array('silent' => true));

		}

		else if($_POST['type']=="sbpro"){

			db::update("
				UPDATE prospects 
				SET
				    signature = '{$_POST['signature']}',
				    sig_title = '{$_POST['title']}',
				    sig_month = '{$_POST['date_month']}',
				    sig_day = '{$_POST['date_day']}',
				    sig_year = '{$_POST['date_year']}',
				    close_date = '".date('Y-m-d')."',
				    ip = '{$_POST['ip']}',
				    status = 'Signed'
				WHERE id = '{$_POST['id']}'
			");

			//email the wpro contact
			list($user_id, $prospect_company) = @db::select_row("
				select user, prospect_company
				from prospects
				where id = {$_POST['id']}
			");
			$wpro_email = db::select_one("select username from users where id = {$user_id}");

			$to  = $wpro_email;
			$to .= ', productsupport@wpromote.com';
			$to .= ', mike@wpromote.com';

			if ($wpro_email != 'vic@wpromote.com'){
				$to .= ', vic@wpromote.com';
			}

			$subject = 'Signed Small Business PRO SAP for '.$prospect_company;

			$message = '<html>
					<body>
					    <a href="http://'.env::E2_FULL_URL.'sales/details/?client='.$_POST['id'].'">'.$prospect_company.' details page</a>
					</bodt>
				    </html>';

			// To send HTML mail, the Content-type header must be set
			$headers  = array(
			    'MIME-Version' => '1.0',
			    'Content-type' => 'text/html; charset=iso-8859-1'
			);

			$from = 'sap@wpromote.com';

			util::mail($from, $to, $subject, $message, $headers, array('silent' => true));

		}

		//save the contract
		//util::save_contract($_POST['url_key']);
	
		echo 'TRUE';

		
	}
	
}
?>