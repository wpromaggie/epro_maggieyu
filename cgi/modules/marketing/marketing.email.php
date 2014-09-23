<?php
class mod_marketing_email extends mod_marketing
{
	public function display_unsub()
	{	
		$data = db::select('select * from email_tracking order by src asc', 'assoc', array('src'));
		$rows = array();
		foreach($data as $src => $contacts){
			$rows[$src] = array(
			    'views' => 0,
			    'unsubs' => 0
			);
			foreach($contacts as $c){
				$rows[$src]['views']++;
				if($c['unsubed']){
					$rows[$src]['unsubs']++;
				}
			}
			
		}
		?>
			<h2>Email Unsub Data</h2>
			<table class="wpro_table" style="border-collapse: collapse; margin-top: 10px;">
				<thead>
					<tr>
						<th>Source</th>
						<th>Total Views</th>
						<th>Unsubs</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$i = 1;
				foreach($rows as $src => $stats){
					$tr_class = ($i%2) ? 'odd' : 'even';
					echo "<tr class='{$tr_class}'>";
						echo "<td>{$src}</td>";
						echo "<td>{$stats['views']}</td>";
						echo "<td>{$stats['unsubs']}</td>";
					echo '</tr>';
					$i++;
				}
				?>
				</tbody>
			</table>
		<?php
	}
	
	public function sts_record_action()
	{
		if($_POST['action']=='view'){
			db::insert('eppctwo.email_tracking', array(
			    'email' => $_POST['email'],
			    'src' => $_POST['src'],
			    'created' => date(util::DATE_TIME)
			));
		} else if($_POST['action']=='unsub') {
			db::update('eppctwo.email_tracking', array(
			    'src' => $_POST['src'],
			    'unsubed' => 1,
			    'unsub_date' => date(util::DATE_TIME)
			), "email='{$_POST['email']}'");
		}
	}
}
?>
