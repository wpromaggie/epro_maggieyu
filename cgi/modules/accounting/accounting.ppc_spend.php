<?php

class mod_accounting_ppc_spend extends mod_accounting
{
	protected static $data_ids;
	protected static $markets;
	
	public function pre_output()
	{
		parent::pre_output();
		
		self::$data_ids = range(0, 15);
		self::$markets = array('g' => 'Google', 'm' => 'MSN');
	}
	
	public function display_index()
	{
		list($start_date, $end_date) = util::list_assoc($_POST, 'start_date', 'end_date');
		if (empty($start_date))
		{
			$end_date = date(util::DATE);
			$start_date = substr($end_date, 0, 7).'-01';
		}

		// kw 2013-10-03: update to use new object tables
		// this might be a little slower now, might need to map/reduce
		// or have job run in middle of night that calculates for set time periods
		echo "coming soon<br>\n";
		return;
		
		$data = array();
		foreach (self::$markets as $market => $market_text)
		{
			$qs = array();
			for ($i = 0, $ci = count(self::$data_ids); $i < $ci; ++$i)
			{
				$data_id = self::$data_ids[$i];
				$qs[] = "(
					select p.who_pays_clicks who_pays, sum(d.cost) spend
					from eppctwo.clients_ppc p, {$market}_data.clients_{$data_id} d
					where
						d.data_date between '$start_date' and '$end_date' &&
						d.client = p.client
					group by p.who_pays_clicks
				)";
			}
			$market_data = db::select("
				select '$market_text' market, who_pays, sum(spend) spend
				from (".implode(" union all ", $qs).") tt
				group by who_pays
			", 'ASSOC');
			$data = array_merge($data, $market_data);
		}
		
		?>
		<table>
			<tbody>
				<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		
		<p class="note">
			* Clients who are no longer active have their data removed after approximately 70 days,
			so older spend data may not reflect how much was actually spent.
		</p>
		
		<h2>Who Pays</h2>
		<div class="data_slice" id="by_who_pays">
		</div>
		
		<h2>Market</h2>
		<div class="data_slice" id="by_market">
		</div>
		
		<h2>All</h2>
		<div class="data_slice" id="all_data">
		</div>
		<?php
		cgi::add_js_var('data', $data);
	}
	
	public function display_account_list()
	{
		list($start_date, $end_date, $who_pays, $market) = util::list_assoc($_REQUEST, 'start_date', 'end_date', 'who_pays', 'market');
		
		$markets = ($market) ? array(strtolower($market[0]) => self::$markets[strtolower($market[0])]) : self::$markets;
		
		$tmp = array();
		foreach ($markets as $market => $market_text)
		{
			$qs = array();
			for ($i = 0, $ci = count(self::$data_ids); $i < $ci; ++$i)
			{
				$data_id = self::$data_ids[$i];
				$qs[] = "(
					select p.client, c.name, sum(d.cost) spend
					from eppctwo.clients_ppc p, eppctwo.clients c, {$market}_data.clients_{$data_id} d
					where
						d.data_date between '$start_date' and '$end_date' &&
						".(($who_pays) ? "p.who_pays_clicks = '".db::escape($who_pays)."' &&" : "")."
						d.client = p.client &&
						p.client = c.id
					group by p.client, c.name
				)";
			}
			$market_data = db::select(implode(" union all ", $qs), array(), 'ASSOC', 'client');
			foreach ($market_data as $cl_id => $cl_info)
			{
				if (array_key_exists($cl_id, $tmp))
				{
					$tmp[$cl_id]['spend'] += $cl_info['spend'];
				}
				else
				{
					$tmp[$cl_id] = $cl_info;
				}
			}
		}
		
		$data = array();
		foreach ($tmp as $cl_id => $cl_info)
		{
			$data[] = array_merge(array('cl_id' => $cl_id), $cl_info);
		}
		usort($data, array($this, 'account_list_cmp'));
		
		$total = 0;
		$ml = '';
		for ($i = 0, $ci = count($data); $i < $ci; ++$i)
		{
			$d = $data[$i];
			$ml .= '
				<tr>
					<td>'.($i + 1).'</td>
					<td><a href="'.cgi::href('ppc/client?cl_id='.$d['cl_id']).'" target="_blank">'.$d['name'].'</a></td>
					<td>'.util::format_dollars($d['spend']).'</td>
				</tr>
			';
			$total += $d['spend'];
		}
		
		$ml_total = '
			<tr>
				<td></td>
				<td>Total</td>
				<td>'.util::format_dollars($total).'</td>
			</tr>
		';
		
		?>
		<table>
			<thead>
				<tr>
					<th></th>
					<th>Client</th>
					<th>Spend</th>
				</tr>
			</thead>
			<thead>
				<?php echo $ml_total; ?>
			</thead>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
			<tfoot>
				<?php echo $ml_total; ?>
			</tfoot>
		</table>
		<?php
	}
	
	public function account_list_cmp($a, $b)
	{
		$aval = $a['spend'];
		$bval = $b['spend'];
		if ($aval == $bval)
		{
			return strcasecmp($a['name'], $b['name']);
		}
		else
		{
			return ($aval < $bval) ? 1 : -1;
		}
	}
}

?>