<?php

class action_accounting_report extends response_object{
	protected static function GET($args = NULL){
		//return array(self::get_payments($args),200);
		$r = self::get_payments($args);
		if($r)
			return array($r,200);
		else
			return array(false,404);
	}

	private static function get_payments($cid){
		$acct_details = mod_eac_account::get_account_details_by_cid($cid);
		$acct_payments = mod_eppctwo_client_payment::get_payments_by_cid($cid);
		//Logger($acct_payments);
		$payments_d = array();
		foreach($acct_payments as $payment){
			if(!isset($payments_d[$payment['cp_id']])){
				$payments_d[$payment['cp_id']] = array(
					'payment_id'=>$payment['cp_id'],
					'payment_method'=>strtoupper($payment['pay_method']),
					'link_point_transaction_id'=>$payment['fid'],
					'date_received'=>$payment['date_received'],
					'date_attributed'=>$payment['date_attributed'],
					'total_amount'=>$payment['total_amount'],
					'items'=>array(),
				);
			}

			$payments_d[$payment['cp_id']]['items'][] = array(
				'type'=>$payment['cpp_types'],
				'amount'=>$payment['cpp_amount'],
				'rep_pay_num'=>$payment['rep_pay_num'],
				'rep_commission'=>$payment['rep_comm']
			);

		}
		sort($payments_d);
		return $payments_d;
	}
}

?>