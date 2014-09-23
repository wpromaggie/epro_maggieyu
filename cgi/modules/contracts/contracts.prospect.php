<?php

//db::dbg();

class mod_contracts_prospect extends mod_contracts
{
	public function pre_output()
	{
		parent::pre_output();
		util::load_lib('billing', 'sales', 'as','account','eac');
	}

	public function display_index()
	{
		$show_fields = array(
		    'layout',
		    'name',
		    'company',
		    'url_key',
		    'title',
		    'address',
		    'city',
		    'state',
		    'country',
		    'zip',
		    'url',
		    'email',
		    'phone',
		    'fax',
		    'partner',
		    'expire_date'
		);

		$default_expire_date = date(util::DATE, strtotime('+1 month'));
		
		if(isset($_POST['parent_id']) && $_POST['parent_id']){

			$this->prospect = new prospects(array('id' => $_POST['parent_id']));
			$this->prospect->expire_date = $default_expire_date;

			$this->prospect->parent_id = $_POST['parent_id'];
			$this->prospect->url_key = "";

			$show_fields[] = 'parent_id';
			$prospect_form = $this->prospect->html_form(array(
					'show' => $show_fields,
					'hidden' => array('parent_id')
			));
		} 

		else {
			
			if(isset($_POST['prospects_parent_id']) && $_POST['prospects_parent_id']){
				$show_fields[] = 'parent_id';
			}

			$values = array();
			foreach($show_fields as $field){
				if(isset($_POST['prospects_'.$field])){
					$values[$field] = $_POST['prospects_'.$field];
				}
			}

			$this->prospect->expire_date = $default_expire_date;
			$prospect_form = $this->prospect->html_form(array(
					'show' => $show_fields,
					'values' => $values,
					'hidden' => array('parent_id')
			));

		}
		
		$parent_prospects = db::select("
			SELECT id, company 
			FROM contracts.prospects 
			WHERE parent_id = 0 
			ORDER BY company
		", "ASSOC");
		
		$prospect_options = "<option value=''>Select Existing Prospect</option>";
		foreach($parent_prospects as $p){
			$prospect_options .= "<option value='{$p['id']}'>{$p['company']}</option>";
		}
		
		?>
		<div>
			<select id="parent_prospect_id" name='parent_id'><?php echo $prospect_options ?></select>
		</div>

		<div id="prospect_options">

			<h1>New Prospect</h1>

			<div id="new_prospect">
				<?php echo $prospect_form ?>
			</div>
		</div>
		<div class="clear"></div>
		<?php
	}
	
	public function display_all()
	{
		$can_delete = false;
		
		$select_cluase  = "SELECT id, name, company, layout, url_key, status, create_date, close_date, revenue";

		$my_prospects = db::select("
			$select_cluase
			FROM contracts.prospects
			WHERE
				user_id = '".user::$id."'
			ORDER BY create_date DESC, name DESC
		", "ASSOC");
		
		$all_prospects = array(
		    "My Prospects" => $my_prospects
		);
		
		
		
		if ($this->is_user_director()) {
			$can_delete = true;
			
			$sales_list = db::select("
				SELECT distinct realname, id
				FROM users u, user_guilds ug
				WHERE
					company = '".g::$company."' &&
					u.id = ug.user_id &&
					(ug.guild_id in ('contracts', 'sales') || username = 'mike@wpromote.com') &&
					u.id <> '".user::$id."' &&
					u.username <> 'ryan@wpromote.com'
				order by realname asc
			", "ASSOC");
		}
		// check if user can see any other users prospects
		else {
			$sales_list = db::select("
				select sh.cid as id, u.realname
				from eppctwo.sales_hierarch sh
				join eppctwo.users u on
					u.id = sh.cid
				where
					sh.pid = :uid
			", array(
				'uid' => user::$id
			), 'ASSOC');
		}

		foreach ($sales_list as $user) {
			$user_prospects = db::select("
				$select_cluase
				FROM contracts.prospects
				WHERE
					user_id = {$user['id']}
				ORDER BY create_date DESC, name DESC
			", 'ASSOC');
			$all_prospects[$user['realname']] = $user_prospects;
		}
		
		foreach($all_prospects as $realname => $prospects){
			//e($prospects);
			if(!empty($prospects)){
			?>
				<h4><?php echo $realname ?></h4>

				<div class='choose_prospect'>
					<table>
						<tr>
							<th>Name</th>
							<th>Company</th>
							<th>Type</th>
							<th>Status</th>
							<th>Date Created</th>
							<th>Date Closed</th>
							<th>Contract</th>
							<th>Revenue</th>
							<th></th>
						</tr>
						<?php foreach ($prospects as $p) { ?>
						<tr>
							<td><?php echo '<a href="'.cgi::href('contracts/prospect/edit/?pid='.$p['id']).'">'.$p['name'].'</a>';?></td>
							<td><?php echo $p['company'];?></td>
							<td><?php echo $p['layout'];?></td>
							<td><?php echo $p['status'];?></td>
							<td><?php echo $p['create_date'];?></td>
							<td><?php if($p['close_date']!='0000-00-00') echo $p['close_date'];?></td>
							<td><?php echo '<a href="'.cgi::href('contracts/view/?pid='.$p['id']).'" target="_blank">'.$p['url_key'].'</a>';?></td>
							<td><?php echo util::format_dollars($p['revenue']) ?></td>
							<td>
							<?php if($p['revenue']<=0 && $can_delete){ ?>
								<a href="<?php echo cgi::href('contracts/prospect/delete_prospect/?pid='.$p['id']) ?>" onClick="return confirm('Are you sure you want to delete this prospect?');">delete</a>
							<?php } ?>
							</td>
						</tr>
						<?php } ?>
					</table>
				</div>
			<?php
			}
		}
	}
	
	public function display_payment()
	{
		if($this->prospect->payment_method!="credit"){
			echo "Payment Method: ".$this->prospect->payment_method;
			return;
		}
		
		$cc_id = billing::get_prospect_cc_id($this->prospect->id, $this->prospect->client_id);
		$cc = array();
		if(!$cc_id){
			echo '<p>No card</p>';
			$cc_id = 0;
		} else {
			$cc = billing::cc_get_display($cc_id);
		}
		
		list($cols) = ccs::attrs('cols');
		//e($cc);
	?>
		<input name="id" value="<?php echo $cc_id ?>" type="hidden" />
		<table>
			<tbody>
				<tr>
					<td>Billing Name</td>
					<td><input name="name" value="<?php echo $cc['name'] ?>" /></td>
				</tr>
				<tr>
					<td>Type</td>
					<td><?php echo ccs::cc_type_form_input('ccs', $cols['cc_type'], $cc['cc_type']); ?></td>
				</tr>
				<tr>
					<td>Number</td>
					<td><input name="cc_number" value="<?php echo $cc['cc_number'] ?>" /></td>
				</tr>
				<tr>
					<td>Exp Month</td>
					<td><?php echo ccs::cc_exp_month_form_input('ccs', $cols['cc_exp_month'], $cc['cc_exp_month']) ?></td>
				</tr>
				<tr>
					<td>Exp Year</td>
					<td><?php echo ccs::cc_exp_year_form_input('ccs', $cols['cc_exp_year'], $cc['cc_exp_year']) ?></td>
				</tr>
				<tr>
					<td>CVC</td>
					<td><input name="cc_code" value="<?php echo $cc['cc_code'] ?>" /></td>
				</tr>
				<tr>
					<td>Country</td>
					<td><?php echo ccs::country_form_input('ccs', $cols['country'], $cc['country']) ?></td>
				</tr>
				<tr>
					<td>Zip</td>
					<td><input name="zip" value="<?php echo $cc['zip'] ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" a0="edit_payment_submit" /></td>
				</tr>
			</tbody>
		</table>
	<?php	
	}
	
	public function pre_output_edit()
	{
		$this->client_select = $this->register_widget('client_select', array(
			'form_key' => 'tie_to_client',
			'selected' => $this->prospect->client_id
		));
	}

	public function action_client_option_submit()
	{
		list($client_option, $tie_to_client) = util::list_assoc($_POST, 'client_option', 'tie_to_client');

		if ($client_option == 'create_new') {
			$this->prospect->update_from_array(array(
				'client_id' => ''
			));
			feedback::add_success_msg('Prospect not tied to existing client, new client will be created');
		}
		else {
			$this->prospect->update_from_array(array(
				'client_id' => $tie_to_client
			));
			feedback::add_success_msg('Prospect tied to '.db::select_one("
				select name
				from eac.client
				where id = :cid
			", array(
				"cid" => $this->prospect->client_id
			)));
		}
		$this->client_select->set_selected($this->prospect->client_id);
	}

	public function display_edit(){
		
		$services = package::get_services($this->prospect->layout);
		
		$this->print_big_buttons() ?>

		<div id="prospect_info" class="left">
			
			<?php
			if($this->prospect->status=="Pending" || $this->prospect->status=="Signed"){
				
				?>
				<div id="w_client_option">
					<div class="head">
						<div class="title">Client</div>
					</div>
					<div class="body">
						<div>
							<p>
								<input type="radio" class="client_type_radio" name="client_option" id="client_option-create_new" value="create_new"<?= (($this->prospect->client_id) ? '' : ' checked="1"') ?> />
								<label for="client_option-create_new">Create New Client</label>
							</p>
							<p>
								<input type="radio" class="client_type_radio" name="client_option" id="client_option-tie_to_existing" value="tie_to_existing"<?= (($this->prospect->client_id) ? ' checked="1"' : '') ?> />
								<label for="client_option-tie_to_existing">Tie to Existing:</label>
								<div id="w_client_existing_input">
									<?= $this->client_select->output() ?>
								</div>
							</p>
							<p>
								<input type="submit" id="client_option_submit" a0="action_client_option_submit" value="Update" />
							</p>
						</div>
					</div>
				</div>
				<div class="clr"></div>

				<?php
			}
			if(user::is_admin() || user::has_role('Leader')){
				$contract_users = db::select("
					SELECT DISTINCT realname, id
					FROM users u, user_guilds ug
					WHERE
						company = '".g::$company."' &&
						u.id = ug.user_id &&
						(ug.guild_id = 'contracts' || u.primary_dept in ('admin', 'dev'))
					order by realname asc
				", "ASSOC");
				//e($contract_users);
				if(!empty($contract_users)){
					foreach($contract_users as $user){
						$selected = ($user['id']==$this->prospect->user_id) ? ' selected' : '';
						$ml_options .= "<option value='{$user['id']}'$selected>{$user['realname']}</option>";
					}
					echo "<div class='select-wrapper'>";
					echo "<label>Reassign Prospect</label>";
					echo "<select id='prospect-select' name='prospects_user_id'>$ml_options</select>";
					echo "</div>";
				}
			}
			?>
			
			<div id="prospect_fields">
				<?php echo $this->prospect->html_form(array(
					'show' => array(
					    'name',
					    'company',
					    'url_key',
					    'title',
					    'address',
					    'city',
					    'state',
					    'country',
					    'zip',
					    'url',
					    'email',
					    'phone',
					    'fax',
					    'revenue',
					    'partner',
					    'expire_date',
					    'payment_method'
					)
				)); ?>
			</div>
			
		</div>

		<div id="service_options">
		
		<h3 style="margin-bottom: 10px;"><?php echo $this->prospect->layout; ?> Services:</h3>
		
		<?php if($this->prospect->status=="Pending")
		{
			foreach($services as $label => $service){
				
				$packages = package::get_all_by_service($service);
				$current_package = $this->prospect->get_service_package($service);
				
				$package_options = "<option value=''>--- Select Package ---</option>";
				foreach($packages as $package){
					if($current_package && $current_package['id']==$package['id']) continue;
					$package_options .= "<option value='{$package['id']}'>{$package['name']}</option>";
				}
				
				?>
				<div class="edit_service">
					<?php
					if($current_package){
						$package_vars = $this->prospect->get_package_vars($current_package['id']);
						?>
						<input type="hidden" class="package_id" value="<?php echo $current_package['id']; ?>" />
						
						<div class="service_head">
							<div class="service_title" style="float: left;"><?php echo package::get_service_display($service, $this->prospect->layout) ?>:</div>
							<div class='package_title'><?php echo $current_package['name']; ?></div>
							<div class="edit_actions">
								<input type="submit" class="change_package" value="Change Package" />
								<input type="submit" class="delete_package" value="Remove" />
							</div>
							<div class="edit_package_select">
								<select class="edit_package_id"><?php echo $package_options; ?></select>
								<span class="update_package_buttons">
									<input type="submit" class="save_change_package" value="Save" />
									<input type="submit" class="cancel_change_package" value="Cancel" />
								</span>
							</div>
							<div class="clear"></div>
						</div>
						
						<div class="edit_package_vars">
							<?php
							foreach($package_vars as $var){
								package::build_prospect_var_form($var);
							}
							?>
							<input type="submit" class="save_package_vars" value="Save Changes" />
							<input type="submit" class="add_package_var" value="Add Var" />
							<div class="clear"></div>
						</div>
						
						<div id="add_package_var_container" style="display: none;">
							<div class="add_package_var">
								<div>
									<label class="tlabel">Name</label>
									<input type="text" class="add_name"/>
								</div>
								<div>
									<label class="tlabel">Description</label>
									<textarea class="add_description"></textarea>
								</div>
								<div>
									<label class="tlabel">Charge Method</label>
									<select class="add_charge_method">
										<?php echo package::build_var_type_options() ?>
									</select>
								</div>
								<div>
									<label class="tlabel">Payment Type</label>
									<select class="add_payment_type">
										<?php echo package::build_payment_type_options(); ?>
									</select>
								</div>
								<div>
									<label class="tlabel">Value</label>
									<input type="text" class="add_value"/>
								</div>
								<div>
									<label class="tlabel">charge</label>
									<input type="checkbox" class="add_charge" checked="checked" />
								</div>
								<button class="save_new_package_var">Add Var</button>
								<button class="cancel_new_package_var">Cancel</button>
							</div>
						</div>
						<?php
						
					} else {
						?>
						<div class="service_head">
							<div class="service_title" style="float: left;"><?php echo package::get_service_display($service, $this->prospect->layout) ?>:</div>
							<div class='package_title'><?php echo $current_package['name']; ?></div>
							<input type="submit" class="add_package" value="Add" />
							<div class="add_package_select">
								<select class="add_package_id"><?php echo $package_options; ?></select>
								<span class="update_package_buttons">
									<input type="submit" class="save_add_package" value="Save" />
									<input type="submit" class="cancel_add_package" value="Cancel" />
								</span>
							</div>
							<div class="clear"></div>
						</div>
						<div class="edit_package_vars"></div>
						<?php
					}
					?>
					<div class="clear"></div>
				</div>
				<?php
			}
			
		}
		else if ($this->prospect->status=="Signed")
		{
			$packages = $this->prospect->get_packages();
			foreach($packages as $package){
				$package_vars = $this->prospect->get_package_vars($package['id']);
				?>
				<div class="edit_service">
					
					<input type="hidden" class="package_id" value="<?php echo $package['id']; ?>" />
					
					<div class="service_head">
						<div class="service_title" style="float: left;"><?php echo package::get_service_display($package['service'], $this->prospect->layout) ?>:</div>
						<div class='package_title'><?php echo $package['name']; ?></div>
						<div class="clear"></div>
					</div>
					
					<div class="edit_package_vars">
						<?php
						foreach($package_vars as $var){
							package::build_prospect_var_form($var);
						}
						?>
						<input type="submit" class="save_package_vars" value="Save Changes" />
						<div class="clear"></div>
					</div>
				</div>
				<?php
			}
		}
		else
		{
			$packages = $this->prospect->get_packages();
			foreach($packages as $package){
				$package_vars = $this->prospect->get_package_vars($package['id']);
				?>
				<div class="edit_service">
					
					<div class="service_head">
						<div class="service_title" style="float: left;"><?php echo package::get_service_display($package['service'], $this->prospect->layout) ?>:</div>
						<div class='package_title'><?php echo $package['name']; ?></div>
						<div class="clear"></div>
					</div>
					
					<div class="edit_package_vars">
						<?php
						foreach($package_vars as $var){
						?>
						<div class="package_var">
							<label><?php echo $var['name'] ?>: </label>
							<span><?php echo ($var['charge'])?util::format_dollars($var['value']):$var['value'] ?></span>
						</div>
						<?php
						}
						?>
						
					</div>
				</div>
				<?php
			}
		}
		?>
		</div>
		
		<div class="clear"></div>
		
		<?php
	}
	
	public function display_order_table(){
		if($this->prospect->layout=="Agency Services"){
			$this->agency_services_table();
		} else {
			$this->small_business_table();
		}
	}
	
	private function small_business_table(){
		$total = 0;
		$services = $this->prospect->get_services();
	?>
		<div id="order_details" class="televox">
			<div id="table_header" class="table_row">
				<div class="td desc">Description</div>
				<div class="td">Setup</div>
				<div class="td">Monthly</div>
				<div class="td">First Month Cost</div>
			</div>
			<div id="order_vars">
			<?php
			foreach($services as $service){
				//e($service);
				$setup_fee = $this->prospect->get_package_var_by_type($service['package_id'], 'setup_fee');
				$monthly_cost = $this->prospect->get_package_var_by_type($service['package_id'], 'monthly_cost');
				$discount = $this->prospect->get_package_var_by_type($service['package_id'], 'discount');
				
				$setup_fee_value = $setup_fee['value'];
				$monthly_cost_value = $monthly_cost['value'];
				
				$discount = $setup_fee['discount'] + $monthly_cost['discount'];
			?>
				<div class="table_row">
					<div class="td desc"><?php echo package::get_service_display($service['service'], $this->prospect->layout)?></div>
					<div class="td"><?php echo util::format_dollars($setup_fee_value) ?></div>
					<div class="td"><?php echo util::format_dollars($monthly_cost_value) ?></div>
					<div class="td"><?php echo util::format_dollars($setup_fee_value+$monthly_cost_value) ?></div>
					<?php if(!empty($discount)) { echo "<div class='discount-strike'></div>"; } ?>
				</div>

				<?php
				if($discount){
					$setup_fee_value -= $setup_fee['discount'];
					$monthly_cost_value -= $monthly_cost['discount'];
				?>
				<div class="table_row">
					<div class="td desc"><?php echo package::get_service_display($service['service'], $this->prospect->layout)?> (Discounted Rate)</div>
					<div class="td"><?php echo util::format_dollars($setup_fee_value) ?></div>
					<div class="td"><?php echo util::format_dollars($monthly_cost_value) ?></div>
					<div class="td"><?php echo util::format_dollars($setup_fee_value+$monthly_cost_value) ?></div>
				</div>
				<?php
				}
				$total += $setup_fee_value+$monthly_cost_value;
			}
			?>
			</div>
			<div id="table_footer" class="table_row">
				<div class="td desc">1st Month Total Due</div>
				<div class="td"></div>
				<div class="td"></div>
				<div class="td"><?php echo util::format_dollars($total) ?></div>
			</div>
		</div>
	<?php	
	}
	
	private function agency_services_table(){
		//Get all the table vars
		$table_vars = db::select("
			SELECT *
			FROM contracts.package_vars
			WHERE prospect_id=$this->prospect_id AND type <> 'contract_length'
			ORDER BY row_order ASC
		", 'ASSOC');

		
		//e($this->prospect);
	?>
		<div id="order_details" class="agency_services">
			<div id="table_header" class="table_row">
				<div class="td desc">Description</div>
				<div class="td">Monthly</div>
				<div id="total_header" class="td<?php if($this->prospect->hide_total) echo " faded"; ?>">Total <button class="toggle_show_btn hidden"><?php echo ($this->prospect->hide_total)?'Show':'Hide' ?></button></div>
				<div class="td">First Month Fee</div>
			</div>
			<div id="order_vars">
			<?php 
			$total = 0;
			foreach($table_vars as $var){
				$contract_length = $this->prospect->get_contract_length($var['package_id']);
				$total += $this->print_order_table_row($var, $contract_length);
			}
			?>
			</div>
			<div id="table_footer" class="table_row">
				<div class="td desc">1st Month Total Due</div>
				<div class="td"></div>
				<div class="td"></div>
				<div class="td"><?php echo util::format_dollars($total) ?></div>
			</div>
		</div>
	<?php	
	}

	protected function action_unsign()
	{
		$this->prospect->update_from_array(array('status' => 'Pending'));
		$this->delete_contract($this->prospect->url_key);
		$this->prospect->clear_billing();
	}
	
	protected function charge_prospect()
	{
		$packages = $this->prospect->get_packages();
		$total = 0;
		$payment_parts = client_payment_part::new_array(array('type' => 'ASSOC'));
		
		foreach($packages as $package){
			$vars = $this->prospect->get_package_vars($package['id'], TRUE);
			foreach($vars as $var){

				//todo: check that something has to be charged before the account/client can be created

				if($var['charge'] && $var['value']>0){
					
					$payment_type = $var['payment_part_type'];
					$amount = $var['value'];

					// added this in for TV Clients, make sure that Agency is ok with it
					if (!empty($var['discount'])){
						$amount -= $var['discount'];
					}
					
					if ($payment_parts->key_exists($payment_type))
					{
						$payment_part = &$payment_parts->i($payment_type);
						$payment_part->amount += $amount;
					}
					else
					{
						$payment_part = new client_payment_part(array(
							'user_id' => user::$id,
							'type' => $payment_type,
							'amount' => $amount
						));
						$payment_parts->set($payment_type, $payment_part);
					}
					$total += $amount;
				}
			}
		}
		
		if($this->prospect->payment_method == "credit"){

			//e('here'); die;

			$cc_id = billing::get_prospect_cc_id($this->prospect->id, $this->prospect->client_id);
			if (!billing::charge($cc_id, $total))
			{
				feedback::add_error_msg('Charge Declined: '.billing::get_error(false));
				return;
			}
			else
			{
				$this->go_result = RESULT_CHARGE_SUCCESS;
				$this->create_client_from_propect($payment_parts, 'cc', $cc_id, true, $total);
			}
		}
		else if($this->prospect->payment_method == "none"){
			$this->create_client_from_propect($payment_parts, $this->prospect->payment_method, 0, false);
		}
		else {
			$this->create_client_from_propect($payment_parts, $this->prospect->payment_method);
		}
		
	}
		
	
	private function create_client_from_propect($payment_parts, $payment_method, $payment_id = 0, $do_charge = true, $total = 0)
	{
		// init success msg
		$msg = array();
		if ($do_charge) {
			$msg[] = 'Prospect Payment Processed';
		}

		// check if we need to create client
		if ($this->prospect->client_id) {
			$client = new client(array('id' => $this->prospect->client_id));
		}
		else {
			$client = client::create(array(
				'name' => $this->prospect->company
			));
			$msg[] = 'Client created';
		}
		
		// even if we
		if ($payment_method == 'cc')
		{
			// update cc with client
			db::update("eppctwo.ccs", array(
				'foreign_table' => 'clients',
				'foreign_id' => $client->id
			), "id = $payment_id");
			$payment_fid = billing::$order_id;
		}
		else
		{
			$payment_fid = '';
		}
		
		if ($do_charge)
		{
			// insert payment and payment parts
			$payment = client_payment::create(array(
				'client_id' => $client->id,
				'user_id' => user::$id,
				'pay_id' => $payment_id,
				'pay_method' => $payment_method,
				'fid' => $payment_fid,
				'date_received' => date(util::DATE, $_SERVER['REQUEST_TIME']),
				'date_attributed' => date(util::DATE, $_SERVER['REQUEST_TIME']),
				'amount' => $total,
				'notes' => 'SAP Payment'
			));
			
			$payment_parts->client_payment_id = $payment->id;
			$payment_parts->client_id = $client->id;
			$payment_parts->insert();
		}
		
		// create contact
		$contact = contacts::create(array(
			'client_id' => $client->id,
			'name' => $this->prospect->name,
			'title' => $this->prospect->title,
			'email' => $this->prospect->email,
			'phone' => $this->prospect->phone,
			'fax' => $this->prospect->fax,
			'street' => $this->prospect->address,
			'city' => $this->prospect->city,
			'state' => $this->prospect->state,
			'zip' => $this->prospect->zip,
			'country' => $this->prospect->country
		));
		
		// create billing contact
		$billing_contact = contacts::create(array(
			'client_id' => $client->id,
			'name' => $this->prospect->company,
			'title' => $this->prospect->title,
			'email' => $this->prospect->email,
			'phone' => $this->prospect->phone,
			'fax' => $this->prospect->fax,
			'street' => $this->prospect->address,
			'city' => $this->prospect->city,
			'state' => $this->prospect->state,
			'zip' => $this->prospect->zip,
			'country' => $this->prospect->country
		));
		
		$departments = array_unique($payment_parts->get_deparment());
		
		// get current departments for this client
		// only create accounts for new departments
		$client_depts = account::get_all(array(
			'select' => array("account" => array("dept", "id")),
			'where' => "client_id = :cid",
			'data' => array("cid" => $client->id),
			'key_col' => "dept"
		));
		// data to create accounts is just about the same for all at this point
		$day = date('j');
		$account_data = array(
			'client_id' => $client->id,
			'division' => 'service',
			'name' => $client->name,
			'status' => 'Active',
			'url' => $this->prospect->url,
			'signup_dt' => date(util::DATE_TIME),
			'bill_day' => $day,
			'prev_bill_date' => \epro\TODAY,
			'next_bill_date' => util::delta_month(\epro\TODAY, 1, $day)
		);
		if ($payment_id) {
			$account_data['cc_id'] = $payment_id;
		}
		foreach ($departments as $department) {
			if ($client_depts->key_exists($department)) {
				// what to do with sales_client_info?
				// what if there is already a record and sales_rep is different?
				// should payments be tied to SAPs so that sales reps
				//  can be properly attributed?
				continue;
			}
			$account_data['dept'] = $department;
			$class = "as_{$department}";
			$account = $class::create($account_data);

			$msg[] = strtoupper($department).' account created';

			// create record for sales rep, default to Inbound
			sales_client_info::create(array(
				'client_id' => $client->id,
				'account_id' => $account->id,
				'sales_rep' => $this->prospect->user_id,
				'type' => 'Inbound'
			));
		}
		
		//UPDATE PROSPECT
		$this->prospect->update_from_array(array(
			'status' => 'Charged',
			'client_id' => $client->id,
			'revenue' => $total
		));

		if (!empty($msg)) {
			feedback::add_success_msg(implode(', ', $msg));
		}
	}
	
	public function display_delete_prospect()
	{
		$this->delete_prospect();
	}
	
	public function delete_prospect()
	{
		prospects::destroy($this->prospect->id);

		//remove file from wpro
		$this->delete_contract($this->prospect->url_key);

		cgi::redirect('contracts/prospect/all/');
	}

	private function delete_contract($url_key)
	{
		$r = util::wpro_post('sap', 'delete', array(
			'url_key' => $url_key
		));
	}
	
	private function print_big_buttons()
	{
	?>
		<?php if($this->prospect->payment_method != "" && $this->prospect->payment_method != "credit"){ ?>
		<div id="payment_type_alert">
			<p>Paid By <?php echo $this->prospect->payment_method ?></p>
		</div>
		<div class="clear"></div>
		<?php } ?>
		
		<div id="big_buttons">
			
			<?php if($this->prospect->status=="Signed" && (user::has_role('Leader') || user::is_admin() || user::has_role('Charge Master'))){
			
				$submit_value = "";
				switch($this->prospect->payment_method){
					case 'credit':
						$submit_value = 'Charge';
						break;
					case 'check':
						$submit_value = 'Deposit Check';
						break;
					case 'wire':
						$submit_value = 'Deposit Wire';
						break;
					case 'none':
						$submit_value = "Create";
				}
				
				if(!empty($submit_value)){
					echo '<input type="submit" a0="charge_prospect" value="'.$submit_value.'" />';
				} else {
					echo "Error: Undefined Payment Method";
				}

				if (user::is_admin()){
					echo ' <input type="submit" a0="action_unsign" value="Unsign" />';
				}
			}
			?>
			
			<?php if($this->prospect->status!="Charged"){ ?>
			<input type="submit" a0="delete_prospect" value="Delete" onClick="return confirm('Are you sure you want to delete this prospect?');" />
			<?php } ?>
		</div>
		
	<?php
	}
	
	
	private function print_order_table_row($var, $contract_length)
	{
		$first_month = $var['value'];
		$note = $var['note'];
		$description = empty($var['description'])?$var['name']:$var['description'];
		
		switch($var['type']){
			
			case 'split_pay':
				$monthly = "N/A";
				$total = $var['value']*2;
				break;
			
			case 'no_first':
				$monthly = util::format_dollars($var['value']);
				$total = $var['value']*($contract_length-1);
				$first_month = $var['value'] = 0;
				break;
			
			case 'other':
				$monthly = "N/A";
				$total = $var['value'];
				break;
			
			case 'setup_fee':
				$monthly = "N/A";
				$total = $var['value'];
				break;
			
			default:
				$monthly = util::format_dollars($var['value']);
				$total = $var['value']*$contract_length;
			
		}
		
	?>
		<div class="table_row<?php if(!$var['order_table']) echo " faded"; ?>" id="<?php echo $var['name']."-".$var['package_id'] ?>" row_order="<?php echo $var['row_order'] ?>" var_name="<?php echo $var['name'] ?>" package_id="<?php echo $var['package_id'] ?>" >
			<div class="td desc">
				<div class="display_mode">
					<span class="description"><?php echo $description ?></span>
					<button class="edit_desc_btn hidden">Edit</button>
					<button class="toggle_show_btn hidden"><?php echo $var['order_table']?'Hide':'Show' ?></button>
					<div class='note'><?= $note ?></div>
				</div>
				<div class="edit_mode hidden">
					<textarea class="edit_desc"><?php echo $description ?></textarea>
					<textarea class="edit_note"><?php echo $note ?></textarea>
					<button class="save_desc_btn">Save</button>
					<button class="cancel_desc_btn">Cancel</button>
				</div>
			</div>
			<div class="td"><?php echo $monthly ?></div>
			<div class="td<?php if($this->prospect->hide_total) echo " faded"; ?>"><?php echo util::format_dollars($total) ?></div>
			<div class="td"><?php echo util::format_dollars($first_month) ?></div>
		</div>
	<?php
		return $var['order_table']?$var['value']:0;
	}
	
	
	protected function action_prospects_submit()
	{
		if(empty($_POST['prospects_url_key'])){
			feedback::add_error_msg('A url key is required.');
			return;
		}
		
		//check for a unique key
		if(!$this->prospect->check_url_key($_POST['prospects_url_key'])){
			feedback::add_error_msg('This url key is already in use.');
			return;
		}
		
		$date = date(util::DATE);
		$this->prospect->mod_date = $date;

		//New prospect
		if(empty($this->prospect->id)){

			//Set some prospect attrs
			$this->prospect->user_id = user::$id;
			$this->prospect->create_date = $date;
			$this->prospect->status = "Pending";
			$this->prospect->put_from_post(array('extra_cols' => array('mod_date', 'user_id', 'create_date', 'status')));
			
			if(!empty($this->prospect->id)){
				proposal::create_new($this->prospect->id, $this->prospect->layout);
				cgi::redirect('contracts/prospect/edit/?pid='.$this->prospect->id);
			}
			
		} else {

			//e($this->prospect);
			//db::dbg();
			//$this->prospect->update_from_array($_POST);
			$this->prospect->put_from_post(array('extra_cols' => array('mod_date', 'user_id', 'create_date', 'status')));

			//e($this->prospect);
			//die;
		}
	}
	
	protected function edit_payment_submit()
	{
		$d = $_POST;
		$cc_info = array(
			'name' => $d['name'],
			'country' => $d['ccs_country'],
			'zip' => $d['zip'],
			'cc_number' => $d['cc_number'],
			'cc_type' => $d['ccs_cc_type'],
			'cc_exp_month' => $d['ccs_cc_exp_month'],
			'cc_exp_year' => $d['ccs_cc_exp_year'],
			'cc_code' => $d['cc_code']
		);
		
		if (empty($d['id'])){
			if(!billing::cc_new('prospects', $this->prospect->id, $cc_info)){
				feedback::add_error_msg("Error: Could not save credit card info.");
			}
		} else {
			if(!billing::cc_update($d['id'], $cc_info)){
				feedback::add_error_msg("Error: Could not update credit card info.");
			}
		}
	}
	
	public function ajax_add_package_var(){
		$package = new package($_POST['package_id']);
		$package->add_var($_POST, $this->prospect->id);
	}
	
	public function ajax_delete_package_var(){
		$package = new package($_POST['package_id']);
		$package->delete_var($_POST['name'], $this->prospect->id);
	}
	
	public function ajax_add_package(){
		$proposal = new proposal($this->prospect);
		$success = $proposal->add_package($_POST['package_id']);
		
		//Return useful info
		if(!$success) {
			echo 'Error adding package';
			return;
		}
		echo 'TRUE';
		return;
	}
	
	public function ajax_delete_package(){
		$proposal = new proposal($this->prospect);
		$proposal->delete_package($_POST['package_id']);
	}
	
	public function ajax_change_package(){
		$proposal = new proposal($this->prospect);
		$proposal->delete_package($_POST['package_id']);
		$proposal->add_package($_POST['new_package_id']);
	}
	
	public function ajax_save_package_vars(){
		foreach($_POST['package_vars'] as $var){
			list($name, $val, $charge, $discount) = explode(':', $var);
			//make sure var is a number
			$val = preg_replace('/[^0-9.]/', '', $val);
			$this->prospect->edit_var($_POST['package_id'], $name, $val, $charge, $discount);
		}
	}
	
	public function ajax_edit_package_var(){
		if($this->prospect->update_var($_POST)!==FALSE) echo 'TRUE';
	}
	
	public function ajax_edit_total_display(){
		if(db::update(
			"contracts.prospects",
			array("hide_total" => $_POST['hide_total']),
			"id = '{$this->prospect_id}'"
		)!==FALSE) echo 'TRUE';
	}
	
	public function ajax_edit_var_display(){
		if($this->prospect->update_var($_POST)!==FALSE) echo 'TRUE';
	}
	
	public function ajax_edit_var_order(){
		foreach($_POST['data'] as $order => $row_id){
			list($name, $package_id) = explode('-', $row_id);
			$this->prospect->update_var($data = array(
			    'package_id' => $package_id,
			    'name' => $name,
			    'row_order' => $order
			));
		}
		
	}
	
}
?>