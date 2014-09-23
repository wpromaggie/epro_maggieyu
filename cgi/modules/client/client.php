<?php

class mod_client extends module_base
{
	public function pre_output()
	{
		$cid = $_REQUEST['cid'];
		if ($cid) {
			// hack! C at beginning means sbp for now
			if (strpos($cid, 'C') === 0) {
				$this->client = new client(array('id' => $cid));
			}
			else {
				// if cid is invalid, we'll just get back an empty client rs object
				$this->cl_id = $cid;
				$this->client = new clients(array('id' => $this->cl_id));

				$contacts = contacts::get_all(array(
					'where' => "client_id = '".$this->cl_id."'"
				));
				$this->client->contacts = $contacts->a;
			}
		}
	}

	public function get_menu()
	{
		if (user::is_admin()) {
			return new Menu(array(
				new MenuItem('Search'        ,'search'        )
			));
		}
		else {
			return false;
		}
	}
	
	public function sts_log_login()
	{
		if (!$this->is_valid_client(true)) {
			return false;
		}
		db::update("eppctwo.contacts", array('last_login' => date(util::DATE_TIME)), "client_id = :cid", array('cid' => $this->client->id));
	}
	
	public function sts_delete_cc()
	{
		util::load_lib('billing', 'sbs');
		
		list($cid, $cc_id, $new_cc_id) = util::list_assoc($_POST, 'cid', 'cc_id', 'new_cc_id');
		
		$cc = billing::cc_get_display($cc_id);
		$account = $this->switch_cc_and_get_client_account($cid, $cc_id, $new_cc_id);
		
		// put in update queue
		sbs_lib::client_update($account->dept, $account->id, 'sbs-cc', array(
			'action' => 'delete',
			'card' => $cc['cc_number']
		));
	}
	
	private function is_valid_client($is_sts = false)
	{
		if (!isset($this->client->name)) {
			if ($is_sts) {
				echo serialize(array('error' => 'Could not find client for cid='.$_POST['cid']));
			}
			return false;
		}
		else {
			return true;
		}
	}
	
	public function sts_new_cc()
	{
		if (!$this->is_valid_client(true)) {
			return false;
		}
		util::load_lib('billing', 'sbs');
		
		$cid = $_POST['cid'];
		$new_cc_id = db::insert("eppctwo.ccs", array(
			'name' => $_POST['cc_name'],
			'country' => $_POST['cc_country'],
			'zip' => $_POST['cc_zip'],
			'cc_number' => util::encrypt($_POST['cc_number']),
			'cc_exp_month' => $_POST['cc_exp_month'],
			'cc_exp_year' => $_POST['cc_exp_year']
		));
		cc_x_client::create(array(
			'cc_id' => $new_cc_id,
			'client_id' => $cid
		));
		
		// todo: we should not have to tie these updates to an account,
		//  add client id to product client updates table?
		$account = $this->get_client_account($cid);
		sbs_lib::client_update($account->dept, $account->id, 'sbs-cc', array(
			'action' => 'new',
			'card' => billing::cc_obscure_number($_POST['cc_number'])
		));
		
		echo serialize(array('new_cc_id' => $new_cc_id));
	}
	
	public function sts_update_cc()
	{
		if (!$this->is_valid_client(true)) {
			return false;
		}
		util::load_lib('billing', 'sbs');
		
		$cc_id = $cc_number = $cc_name = $cc_exp_month = $cc_exp_year = $cc_country = $cc_zip = false;
		extract($_POST, EXTR_OVERWRITE | EXTR_IF_EXISTS);
		
		$old_cc = billing::cc_get_actual($cc_id);
		$cc_x = new cc_x_client(array(
			'cc_id' => $cc_id
		), array('do_get' => true));
		
		if (!$cc_x->client_id || $cc_x->client_id != $this->client->id) {
			// client not tied to card?
			$do_check_if_update = false;
			$cid = $this->client->id;
		}
		else {
			$do_check_if_update = true;
			$cid = $cc_x->client_id;
		}
		
		$response = array();
		// if number not sent over, update non-number card fields
		if ($do_check_if_update && !$cc_number) {
			$response['update_type'] = 'update';
			db::update("eppctwo.ccs", array(
				'name' => $cc_name,
				'cc_exp_month' => $cc_exp_month,
				'cc_exp_year' => $cc_exp_year,
				'country' => $cc_country,
				'zip' => $cc_zip
			), "id = :id", array('id' => $cc_id));
			
			$account = $this->get_client_account($cid);
		}
		// if number is new, create new card, send id back to wpro
		else {
			$response['update_type'] = 'new';
			$cc_db_data = array(
				'name' => $cc_name,
				'country' => $cc_country,
				'zip' => $cc_zip,
				'cc_number' => util::encrypt($cc_number),
				'cc_exp_month' => $cc_exp_month,
				'cc_exp_year' => $cc_exp_year
			);
			$new_cc_id = db::insert("eppctwo.ccs", $cc_db_data);
			cc_x_client::create(array(
				'cc_id' => $new_cc_id,
				'client_id' => $cid
			));
			$response['new_cc_id'] = $new_cc_id;
			$response['cid'] = $cid;
			
			$account = $this->switch_cc_and_get_client_account($cid, $cc_id, $new_cc_id);
		}
		
		sbs_lib::client_update($account->dept, $account->id, 'sbs-cc', array(
			'action' => 'update',
			'is_new_number' => ($response['update_type'] == 'new') ? 'yes' : 'no',
			'card' => billing::cc_obscure_number(($cc_number) ? $cc_number : $old_cc['cc_number'])
		));
		
		echo serialize($response);
	}
	
	// generally for when user updates contact info
	// can also be called to simply update contact info, in which case there is no need for url id and to register as client update
	public function sts_update_contact()
	{
		util::load_lib('sbs');
		
		$aid = $_POST['aid'];
		$cid = $_POST['id'];
		unset($_POST['id'], $_POST['aid'], $_POST['_sts_func_'], $_POST['password']);
		
		if ($aid) {
			$ac = new account(array('id' => $aid), array('select' => 'dept'));
			sbs_lib::client_update($ac->dept, $aid, 'sbs-contact', $_POST);
		}
		
		db::update("eppctwo.contacts", $_POST, "client_id = :cid", array('cid' => $cid));
	}
	
	public function sts_activate_contact()
	{
		if (!$this->is_valid_client(true)) {
			return false;
		}
		db::update("eppctwo.contacts", array(
			'status' => 'active',
			'authentication' => ''
		), "client_id = :cid", array('cid' => $this->client->id));
	}
	
	protected function encrypt_pw_rst_key($contact=array())
	{
		return implode("-", array($contact['id'], $contact['client_id']));
	}
	
	protected function decrytp_pw_rst_key($key="")
	{
		return explode("-", $key);
	}
	
	public function sts_reset_password()
	{
		//Create a new authentication code and empty out the password
		util::load_lib('sbs');
		$pass_set_key = sbs_lib::generate_pass_set_key();
		
		list($contact_id, $client_id) = $this->decrytp_pw_rst_key($_POST['contact_key']);

		$response = db::update("eppctwo.contacts", array(
			'password' => null,
			'authentication' => $pass_set_key
		), "id = :id", array('id' => $contact_id));
		
		echo serialize(array(
		    'set_pass_key' => $pass_set_key,
		    'client_id' => $client_id
		));
	}

	public function sts_send_reset_password_email()
	{
		$response = array();
		$email = $_POST['email'];
		
		$contact = db::select_row("select * from eppctwo.contacts where email = :email", array('email' => $email), "ASSOC");
		
		if(empty($contact)){
			//The client's email address was not found
			$response['error'] = "Sorry, there is no client matching this email address.";
		} else {
			//create a password reset key
			$pw_reset_key = $this->encrypt_pw_rst_key($contact);
			
			$pw_rst_url = "http://".\epro\WPRO_DOMAIN."/account/password_reset/$pw_reset_key";
			$support_url = "http://".\epro\WPRO_DOMAIN."/small-business-products/contact-us";
			$body = array();
			$body['html'] = "
<html>
<body>
<div style='font-family:'Helvetica Neue',Arial,Helvetica,sans-serif;font-size:13px;margin:14px'>
	<h2 style='font-family:'Helvetica Neue',Arial,Helvetica,sans-serif;margin:0 0 16px;font-size:18px;font-weight:normal'>Forgot your password, {$contact['name']}?</h2>

	<p>Wpromote received a request to reset the password for your account.</p>

	<p>To reset your password, click on the link below (or copy and paste the URL into your browser):<br />
	<a href='$pw_rst_url'>$pw_rst_url</a></p>

	<p>Check out our <a href='$support_url'>support page</a> for more information.</p>

	<img src='http://".\epro\WPRO_DOMAIN."/css/img/signature.png' style='width: 149px;height: 33px;' />
</div>
</body>
</html>
";
	
			$body['text'] = "
Forgot your password, {$contact['name']}?

Wpromote received a request to reset the password for your account.

To reset your password, click on the link below (or copy and paste the URL into your browser):
$pw_rst_url

Check out $support_url for more information.

The Wpromote Team
";
			//send email!
			$mail_sent = util::mail('productsupport@wpromote.com', $email, 'Reset your Wpromote password', $body, null, array('silent' => true));
			$response = ($mail_sent) ? '1' : '0';
			
			
		}
		
		if (util::is_dev()) {
			db::update("eppctwo.contacts", array(
				'notes' => $pw_rst_url,
			), "id = :id", array('id' => $contact['id']));
		}
		
		echo serialize($response);
	}
	
	protected function get_client_account($cid)
	{
		$accounts = account::get_all(array(
			'select' => "id, dept",
			'where' => "client_id = :cid",
			'data' => array('cid' => $cid)
		));
		if ($accounts->count() > 0) {
			return $accounts->current();
		}
		else {
			return false;
		}
	}
	
	protected function switch_cc_and_get_client_account($cid, $old_cc_id, $new_cc_id)
	{
		account::update_all(array(
			'where' => "cc_id = '$old_cc_id'",
			'set' => array('cc_id' => $new_cc_id)
		));
		return $this->get_client_account($cid);
	}

	public function display_export_to_dash()
	{
		util::load_rs('as', 'sales');

		$client_info = array();
		$client_info['client'] = $this->client->id;
		$client_info['name'] = $this->client->name;
		
		$contacts = contacts::get_all(array(
			'select' => "*",
			'where' => "client_id = :cid",
			'data' => array("cid" => $this->client->id)
		));
		if ($contacts->count() > 0){

			//just use the 1st contact
			$contact = $contacts->shift();

			$client_info['address'] = $contact->street;
			$client_info['city'] = $contact->city;
			$client_info['state'] = $contact->state;
			$client_info['zip'] = $contact->zip;
			$client_info['phone'] = $contact->phone;
			$client_info['fax'] = $contact->fax;
		}
		else {
			// ??
			// $client_account['name'] = "Unknown ({$this->client->id})";
		}

		$client_accounts = account::get_all(array(
			'select' => array(
				"account" => array("id", "url", "dept"),
				"users" => array("username as manager")
			),
			'left_join' => array("users" => "users.id = account.manager"),
			'where' => "division = 'service' && client_id = :cid",
			'data' => array("cid" => $this->client->id),
			'flatten' => true
		));
		$secondary_managers = users::get_all(array(
			'select' => "users.username",
			'distinct' => true,
			'join' => array("secondary_manager" => "secondary_manager.user_id = users.id"),
			'where' => "secondary_manager.account_id in (:aids)",
			'data' => array("aids" => $client_accounts->id)
		));
		$sales_reps = users::get_all(array(
			'select' => "users.username",
			'distinct' => true,
			'join' => array("sales_client_info" => "sales_client_info.sales_rep = users.id"),
			'where' => "sales_client_info.client_id = :cid",
			'data' => array("cid" => $this->client->id)
		));
		$urls = array_unique(array_filter($client_accounts->url));

		$client_info['url'] = ($urls) ? $urls[0] : '';

		$client_info['services'] = array_unique($client_accounts->dept);
		$client_info['services'] = implode(',', $client_info['services']);

		$client_info['managers'] = array_unique(array_filter(array_merge($client_accounts->manager, $secondary_managers->username, $sales_reps->username)));
		$client_info['managers'] = implode(',', $client_info['managers']);

		//e($client_info);

		$response = util::wpro_post('dashboard', 'add_client_account', $client_info);
		
		echo $response;

	}

	public function pre_output_search()
	{
		$this->search_results = false;
	}

	public function action_search()
	{
		$user_depts = user::get_user_client_depts();
		$this->search_results = client::get_all(array(
			'select' => array(
				"client" => array("id as cid", "name"),
				"account" => array("id as aid", "dept", "status")
			),
			'join_many' => array(
				"account" => "
					account.client_id = client.id &&
					account.division = 'service' &&
					account.dept in (:user_depts)
				"),
			'where' => "client.name like :user_search",
			'data' => array(
				"user_search" => "%{$_POST['cs']}%",
				"user_depts" => $user_depts
			),
			'order_by' => "name asc, dept asc",
			'de_alias' => true
		));
		$this->dept_priority = array('ppc', 'seo', 'smo', 'email');
		if ($this->search_results && $this->search_results->count() == 1) {
			$this->redirect_to_client($this->search_results->a[0]);
		}
	}
	
	public function display_search()
	{
		if ($this->action == 'action_search' && $this->search_results->count() == 0) {
			feedback::add_error_msg("No clients matching search <i>{$_POST['cs']}</i>");
		}
		?>
		<h1>Client Search</h1>
		<label for="cs">Client Name</label>
		<input type="text" focus_me="1" name="cs" id="cs" value="<?= $_POST['cs'] ?>" />
		<input type="submit" a0href="/client/search" a0="action_search" value="Submit" />
		<?= $this->ml_search_results() ?>
		<?php
	}

	private function redirect_to_client($client)
	{
		// we know we have at least 1 account
		$acnt = $client->account->a[0];
		cgi::redirect($this->get_client_account_href($client));
	}

	private function ml_search_results()
	{
		if (!$this->search_results || $this->search_results->count() == 0) {
			return;
		}
		$ml_search_results = '';
		foreach ($this->search_results as $i => $client) {
			$ml_depts = '';
			foreach ($client->account as $j => $acnt) {
				$ml_comma = ($j < ($client->account->count() - 1)) ? ', ' : '';
				$ml_depts .= '<span class="'.(($acnt->status == 'Active') ? 'active' : 'inactive').'">'.$acnt->dept.$ml_comma.'</span>';
			}
			$ml_search_results .= '
				<tr>
					<td>'.($i + 1).'</td>
					<td><a href="'.$this->get_client_account_href($client).'">'.$client->name.'</a></td>
					<td>'.$ml_depts.'</td>
				</tr>
			';
		}
		return '
			<br />
			<hr />
			<table>
				<thead>
					<tr>
						<th></th>
						<th>Client</th>
						<th>Depts</th>
					</tr>
				</thead>
				<tbody>
					'.$ml_search_results.'
				</tbody>
			</table>
		';
	}

	private function get_client_account_href($client)
	{
		$client_depts = $client->account->dept;
		$acnt = false;
		foreach ($this->dept_priority as $dept) {
			$i = array_search($dept, $client_depts);
			if ($i !== false) {
				$acnt = $client->account->a[$i];
				break;
			}
		}
		if ($acnt === false) {
			$acnt = $client->account->a[0];
		}
		return $acnt->get_href('info');
	}

	//
	// Communication with the wpro dashboard
	//
	public function display_tie_to_enet()
	{
		$client_found = util::wpro_post('dashboard', 'get_client_account_by_id', array('id' => $this->client->id));

		//e($client_found);

		echo '<h1>
			<i>'.$this->client->name.'</i>
				:: Wpro Dash
				'.((user::is_developer()) ? ' ('.$this->client->id.')' : '').'
			</h1>
		';

		//e($this->client);

		if ($client_found != 'FALSE'){
			echo '<p>Currently tied to <a href="http://'.\epro\WPRO_DOMAIN.'/dashboard/services_view/'.$client_found['ClientAccount']['id'].'/ppc/" target="_blank">'.$client_found['ClientAccount']['name'].'</a></p>';
			echo '<input type="submit" a0="action_unlink_wpro_account" value="Unlink" />';
		}
		else {

			//select all ppc accounts from wpromote that have not been assigned to an e2 account
			$clients = util::wpro_post('dashboard', 'get_client_accounts', array('service' => 'ppc'));

			//e($clients);

			$client_select[] = array('', 'Select Client');
			foreach($clients as $client){
				$client_select[] = array($client['ClientAccount']['id'], $client['ClientAccount']['name']);
			}

			echo '<h4>Attach PPC Account</h4>';
			echo cgi::html_select('client_account_id', $client_select);
			echo '<input type="submit" a0="action_set_wpro_account" value="Go" />';
		}
	}

	public function action_set_wpro_account()
	{
		util::wpro_post('dashboard', 'update_client_account', array(
			'id' => $_POST['client_account_id'],
			'client' => $this->client->id
		));
	}

	public function action_unlink_wpro_account()
	{
		util::wpro_post('dashboard', 'unlink_client_account', array(
			'client' => $this->client->id
		));
	}
}

?>