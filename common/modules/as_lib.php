<?php

class as_lib
{
	public static function get_client_department_links($cid, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'sub_url' => false,
			'do_include_other_links' => true
		));

		$accounts = account::get_all(array(
			'select' => array("account" => array("id", "division", "dept")),
			'where' => "client_id = :cid",
			'data' => array("cid" => $cid)
		));

		for ($i = 0, $ci = $accounts->count(); $i < $ci; ++$i) {
			$a = $accounts->a[$i];
			if ($ml) $ml .= ' &bull; ';
			$href =
				'/account/'.$a->division.'/'.$a->dept.
				(($opts['sub_url']) ? ('/'.$opts['sub_url']) : '').
				'?aid='.$a->id
			;
			$ml .= '<a href="'.$href.'">'.util::display_text($a->dept).'</a>';
		}
		
		$sap_urls = util::get_client_sap_urls($cid);
		if (!empty($sap_urls) && $opts['do_include_other_links']) {
			foreach($sap_urls as $sap_url) {
				$tokens = explode('/', $sap_url);
				$ml .= ' &bull; <a href="'.$sap_url.'" target="_blank">SAP ('.end($tokens).')</a>';
			}
		}

		//export client to wpro dashboard
		if ((user::is_admin() || user::has_role('Dashboard Admin')) && $opts['do_include_other_links']) {
			$ml .= ' | <a href="'.cgi::href('client/export_to_dash').'?cid='.$cid.'">Export Client to Dashboard</a>';
			$ml .= ' | <a href="'.cgi::href('client/tie_to_enet').'?cid='.$cid.'">Tie Client From Dashboard</a>';
		}
		
		return $ml;
	}
	
	public static function get_client_survey_links($cid)
	{
		$surveys = db::select("
			SELECT * 
			FROM surveys.client_surveys 
			WHERE client_id = :cid
				AND status <> 'deleted'
		", array(
			"cid" => $cid
		), "ASSOC");
		
		$ml = "<p>Client Surveys:";
		if(!empty($surveys)){
			$i=0;
			foreach($surveys as $s){
				if($i) $ml .= " |";
				$ml .= " <a href='".cgi::href("surveys/view/?id={$s['id']}")."'>{$s['urlkey']}</a>";
				$i++;
			}
		}
		
		$ml .= " | <a href='".cgi::href("surveys/clients/add?cl_id=$cid")."'>Create New Survey</a>";
		$ml .= "</p>";
		return $ml;
	}
	
	public static function record_payment($account, $data, $opts = array())
	{
		util::load_lib('billing');
		
		util::set_opt_defaults($opts, array(
			'do_charge' => true,
			'charge_type' => BILLING_CC_CHARGE,
			'pay_method' => 'cc',
			'success_msg' => 'Payment Processed'
		));
		
		// data keys
		// pay_id is the database id of the object (eg credit card) being used to process the payment
		// updated_id is the client_payment id is this is an update rather than a new charge
		
		$part_types = client_payment_part::get_enum_vals('type');
		$total = 0;
		$payment_parts = client_payment_part::new_array();
		foreach ($part_types as $type)
		{
			$amount = str_replace(array('$', ',', ' '), '', $data['amount_'.util::simple_text($type)]);
			if (is_numeric($amount))
			{
				if ($opts['charge_type'] == BILLING_CC_REFUND && $amount > 0) $amount *= -1;
				$total += $amount;
				$payment_parts->push(new client_payment_part(array(
					'client_id' => $account->client_id,
					'type' => $type,
					'amount' => $amount
				)));
			}
		}
		if ($payment_parts->count() == 0)
		{
			if (class_exists('feedback'))
			{
				feedback::add_error_msg('No valid numbers found for payment');
			}
			return false;
		}
		else
		{
			if ($opts['do_charge'])
			{
				// refunds are positive with type of BILLING_CC_REFUND
				$payment_gateway_total = ($opts['charge_type'] == BILLING_CC_REFUND) ? -$total : $total;
				if (billing::charge($data['pay_id'], $payment_gateway_total, $opts['charge_type']))
				{
					$fid = billing::$order_id;
				}
				else
				{
					if (class_exists('feedback'))
					{
						feedback::add_error_msg('Billing Error: '.billing::get_error());
					}
					return false;
				}
			}
			else
			{
				$fid = '';
			}
			
			if ($data['update_id'])
			{
				$payment = new client_payment();
				$payment->id = $data['update_id'];
				$payment->notes = $data['notes'];
				$payment->date_attributed = $data['date_attributed'];
				$payment->amount = $total;
				$payment->pay_method = $opts['pay_method'];
				if (!empty($data['date_received'])) {
					$payment->date_received = $data['date_received'];
				}
			}
			else
			{
				if (empty($data['date_received'])) {
					$data['date_received'] = date(util::DATE);
				}

				// user: will be set under cgi, otherwise use 0 to indicate cli
				$payment = new client_payment(array(
					'client_id' => $account->client_id,
					'user_id' => (class_exists('user')) ? user::$id : 0,
					'pay_id' => $data['pay_id'],
					'pay_method' => $opts['pay_method'],
					'fid' => $fid,
					'date_received' => $data['date_received'],
					'date_attributed' => $data['date_attributed'],
					'amount' => $total,
					'notes' => $data['notes']
				));
			}
			$payment->put();
			
			$payment_parts->client_payment_id = $payment->id;
			$payment_parts->put();
			
			if (class_exists('feedback'))
			{
				feedback::add_success_msg($opts['success_msg']);
			}
		}
		return true;
	}
}

?>