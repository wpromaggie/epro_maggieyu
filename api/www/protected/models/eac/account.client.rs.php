<?php

class client extends rs_object
{
	public static $db, $cols, $primary_key, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$has_many = array('account');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('name'   ,'char'    ,128 ,''      )
		);
	}
	
	// c and 8 random digits
	// [10 mil to 100 mil)
	protected function uprimary_key($i)
	{
		return 'C'.mt_rand(10000000, 99999999);
	}
	
	public function get_or_assign_product_manager($opts = array())
	{
		util::set_opt_defaults($opts, array(
			'force_new' => false
		));

		if (!$opts['force_new']) {
			if ($this->account && $this->account->manager) {
				$managers = array($this->account->manager);
			}
			else {
				$managers = db::select("
					select a.manager
					from eac.account a, eac.product p
					where
						a.client_id = '{$this->id}' &&
						a.id = p.id
				");
			}
		}

		// we have 1 or more managers for this client, return the  most common
		if ($managers) {
			$man_counts = array();
			$max_man = false;
			foreach ($managers as $manager) {
				$man_counts[$manager]++;
				if (!$max_man || $man_counts[$manager] > $max_counts[$max_man]) {
					$max_man = $manager;
				}
			}
			return $max_man;
		}
		// no managers assigned, randomly get one
		else {
			$manager_counts = db::select("
				select a.manager, count(*)
				from eac.account a, eac.product p
				where
					a.status = 'Active' &&
					a.id = p.id
				group by manager
			");
			// get active reps, default their count to 1 (which gives
			// reps with no accounts high probably to be selected)
			$active_reps = db::select("
				select users_id, 1
				from eppctwo.sbs_account_rep
			", 'NUM', 0);
			$num_reps = count($active_reps);
			
			// since each init'd to 1
			$sum = $num_reps;
			
			// add counts for reps that have them, calc sum
			for ($i = 0, $num_managers = count($manager_counts); list($mid, $mcount) = $manager_counts[$i]; ++$i) {
				if (array_key_exists($mid, $active_reps)) {
					$active_reps[$mid] = $mcount;
					$sum += $mcount - 1;
				}
			}
			
			// we want it to be more likely to assign to manager with less accounts,
			// use inverse of percentage of sum for each rep
			$r = ((mt_rand() / mt_getrandmax()) * ($num_reps - 1));
			$running_sum = 0;
			foreach ($active_reps as $rep_id => $rep_count) {
				$running_sum += 1 - ($rep_count / $sum);
				if ($running_sum >= $r) {
					break;
				}
			}
			return $rep_id;
		}
	}
	
	// todo: option to get all actual or display ccs for a client in billing
	//       so we don't need this extra loop to get them one by one
	public static function get_cc_options($cl_id)
	{
		$cc_ids = ccs::get_all(array(
			'select' => array("ccs" => array("id")),
			'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
			'where' => "cc_x_client.client_id = '".db::escape($cl_id)."'"
		));
		
		$cc_options = array();
		for ($i = 0, $ci = $cc_ids->count(); $i < $ci; ++$i)
		{
			$cc_id = $cc_ids->i($i)->id;
			$cc = billing::cc_get_display($cc_id);
			$cc_options[] = array($cc_id, $cc['cc_number'].', '.$cc['cc_exp_month'].'/'.$cc['cc_exp_year']);
		}
		return $cc_options;
	}
}

?>