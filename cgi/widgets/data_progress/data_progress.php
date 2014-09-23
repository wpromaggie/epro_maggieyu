<?php

class wid_data_progress extends widget_base
{
	public function output()
	{
		$markets = util::get_ppc_markets('ASSOC');
		$ml_rows = '';
		foreach ($markets as $market => $text)
		{
			$ml_rows .= '
				<tr market="'.$market.'">
					<td>'.$text.'</td>
					<td class="data_progress_time"></td>
					<td class="data_progress_status"></td>
				</tr>
			';
		}
		?>
		<fieldset id="data_progress" ejo>
			<legend>Data Progress</legend>
			<table>
				<thead>
					<tr>
						<th>Market</th>
						<th>Time</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $ml_rows; ?>
				</tbody>
			</table>
		</fieldset>
		<div class="clr"></div>
		<?php
	}
	
	public function ajax_get_status()
	{
		//db::dbg();
		$stati = db::select("
			select market, t, status
			from eppctwo.market_data_status
			where d = '".date(util::DATE, time() - 86400)."'
		", 'ASSOC', 'market');
		
		echo json_encode($stati);
	}
}

?>