<?php
require_once 'time_report.php';

/**
 * Comment
 */
class mod_timecard extends module_base
{
	const easy_date_format = 'F jS Y';
	const easy_time_format = 'g:i a';
	const adjust_time_format = 'H:i';

	private $request_dt;
	private $timezone;

	public function get_menu()
	{
		$time_menu = array();
		if (user::has_role('Leader', 'timecard')) {
			$time_menu[] = new MenuItem('Calendar', array('timecard', 'calendar'));
		}
		if (user::has_role('Leader', 'timecard') || user::is_developer()) {
			$time_menu[] = new MenuItem('Adjust', array('timecard', 'adjust'));
			$time_menu[] = new MenuItem('Report', array('timecard','report'));
			$time_menu[] = new MenuItem('Exempt Status', array('timecard','exempt_status'));
		}
		return $time_menu;
	}

	public function pre_output()
	{
		$this->timezone = new DateTimeZone('America/Los_Angeles');
		$this->request_dt = new DateTime('@'.$_SERVER['REQUEST_TIME']);
		$this->request_dt->setTimezone($this->timezone);
	}

	/**
	 * Kind of an override hack to keep fools from fooling around at home.
	 */
	public function output()
	{
		$offsite_ok = users::get_all(array(
			'select' => "id",
			'where' => "id = :uid && offsite_clockin_ok",
			'data' => array('uid' => user::$id)
		));
		if (!(
			// dev
			util::is_dev() ||
			// epro local now
			strpos(cgi::$ip, '10.73.') === 0 || strpos(cgi::$ip, '192.168.') === 0 ||
			// external ip
			cgi::$ip == '67.159.165.130' || cgi::$ip == '67.159.165.131' || cgi::$ip == '67.159.165.138' ||
			// external wireless?
			cgi::$ip == '216.36.111.79' ||
			// hr
			user::has_role(array('Member', 'Leader'), 'timecard') ||
			// user can clock in from offsite
			$offsite_ok->count() > 0 ||
			// developer
			user::is_developer()
		)) {
			echo "Please adjust your time on the work network (your IP: ".cgi::$ip.")<br /><br />";
			return;
		}
		parent::output();
	}

	public function display_index()
	{
		if (!user::is_logged_in()) {
			return;
		}
		$today_mysql = $this->request_dt->format(util::DATE);
		$ml_in = $this->ml_all_current_in($today_mysql);
		?>
		<div class="time_clocking">
			<input type="submit" value="Clock In" a0="action_clock_in">
			<input type="submit" value="Clock Out" a0="action_clock_out">
		</div>
		<?php
		$today_times = $this->get_day(user::$id, $today_mysql);
		$today_total = 0;
		?>
		<div class="time_today">
			<div class="date">
			<?php echo $this->request_dt->format(self::easy_date_format);?>
			</div>
			<table>
				<tr>
					<th>Clock In</th>
					<th>Clock Out</th>
					<th>Total</th>
				</tr>
			<?php foreach ($today_times as $record) { ?>
				<?php
				$display_in = '-';
				$in_dt = DateTime::createFromFormat(util::DATE_TIME, $record['clock_in'], $this->timezone);
				if ($in_dt != NULL) {
					$display_in = $in_dt->format(self::easy_time_format);
				}

				$display_out = '-';
				$out_dt = DateTime::createFromFormat(util::DATE_TIME, $record['clock_out'], $this->timezone);
				if ($out_dt != NULL) {
					$display_out = $out_dt->format(self::easy_time_format);
				}

				$record_total = $record['total'];
				if ($in_dt != NULL && $out_dt == FALSE) {
					$record_total = $this->request_dt->format('U') - $in_dt->format('U');
				}

				$today_total += $record_total;
				?>
				<tr class="record">
					<td class="clock_in"><?php echo $display_in;?></td>
					<td class="clock_out"><?php echo $display_out;?></td>
					<td class="total"><?php echo $this->ml_pretty_total($record_total);?></td>
				</tr>
			<?php } ?>
			<?php if (count($today_times) >= 2) { ?>
				<tr class="total">
					<td></td>
					<td></td>
					<td class="grand_total"><?php echo $this->ml_pretty_total($today_total);?></td>
				</tr>
			<?php } ?>
			</table>
		</div>

		<div class="whos_here">
			<div class="who_title">Who is in?</div>
			<?php echo $ml_in; ?>
		</div>
		<?php
	}
	
	protected function action_set_exempt()
	{
		$uid = $_POST['user_select'];
		$r = db::update("eppctwo.users", array(
			'exempt' => $_POST['is_exempt']
		), "id = '{$uid}'");
		if ($r)
		{
			feedback::add_success_msg("Exempt Status Updated");
		}
		else
		{
			feedback::add_error_msg("Error Updating Exempt Status");
		}
	}
	
	public function display_exempt_status()
	{
		if (!($this->is_user_leader() || user::is_developer()))
		{
			return;
		}
		$users = db::select("
			select id, realname
			from users
			".users::get_test_where_clause('where')."
			order by realname asc
		");
		
		array_unshift($users, array('', ' - Select - '));
		
		$ml_exempt = '';
		$uid = $_POST['user_select'];
		if ($uid)
		{
			$is_exempt = db::select_one("	
				select exempt
				from eppctwo.users
				where id = '$uid'
			");
			$options = array(	
				array('1', 'Yes'),
				array('0', 'No')
			);
			$ml_exempt = '
				<tr>
					<td>Is Exempt?</td>
					<td>'.cgi::html_radio('is_exempt', $options, $is_exempt).'</td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_set_exempt" value="Submit" /></td>
				</tr>
			';
		}
		?>
		<h2>Exempt Status</h2>
		<table>
			<tbody>
				<tr>
					<td>User</td>
					<td><?php echo cgi::html_select('user_select', $users, $uid, array('submit_on_change' => 1)); ?></td>
				</tr>
				<?php echo $ml_exempt; ?>
			</tbody>
		</table>
		<?php
	}
	
	public function display_adjust()
	{
		if (!(user::has_role('Leader', 'timecard') || user::is_developer())) {
			return;
		}
		?>
		<div>
			<label>User:</label>
			<?php $select_name = $this->print_userSelect("adjust_user", $_POST['adjust_user']); ?>
			<label>Date:</label>
			<input type="text" class="date_input" name="adjust_date" value="<?php echo $_POST['adjust_date'];?>">
			<input type="submit" value="Adjust">

			<?php if ($_POST['adjust_user'] != '' && $_POST['adjust_date'] != '') {?>
			<div>
				
				<div id="current_name"><?php echo $select_name;?></div>
				<div id="current_user"><?php echo $_POST['adjust_user'];?></div>
				<div id="current_date"><?php echo $_POST['adjust_date'];?></div>

				<table id="adjust_table">
					<tr>
						<th>in</th>
						<th>out</th>
						<th>edit</th>
					</tr>
				<?php foreach ($this->get_day($_POST['adjust_user'], $_POST['adjust_date']) as $record) { ?>
					<?php
					$time_id = $record['id'];
					$cin_dt = DateTime::createFromFormat(util::DATE_TIME, $record['clock_in'], $this->timezone);
					$cout_dt = DateTime::createFromFormat(util::DATE_TIME, $record['clock_out'], $this->timezone);
					$cin_display = ($cin_dt === FALSE)?'':$cin_dt->format(self::adjust_time_format);
					$cout_display = ($cout_dt === FALSE)?'':$cout_dt->format(self::adjust_time_format);
					?>
					<tr time_id="<?php echo $time_id;?>">
						<td class="c_in"><input type="text" class="time_input" value="<?php echo $cin_display;?>"></td>
						<td class="c_out"><input type="text" class="time_input" value="<?php echo $cout_display;?>"></td>
						<td>
							<button type="button" class="update_button">Update</button>
							<button type="button" class="delete_button">Delete</button>
						</td>
					</tr>
				<?php } ?>
					<tr id="add_row">
						<td class="c_in"><input type="text" class="time_input"></td>
						<td class="c_out"><input type="text" class="time_input"></td>
						<td><button type="button" id="add_button">Add</button></td>
					</tr>
				</table>
			</div>
			<?php } ?>
			
		</div>
		<?php
	}
	
	public function ajax_adjust_add()
	{
		//Attempt to format the incoming times
		$in_string = $_POST['date'] . ' ' . $_POST['clock_in'] . ':00';
		$in_dt = DateTime::createFromFormat(util::DATE_TIME, $in_string, $this->timezone);
		$out_string = $_POST['date'] . ' ' . $_POST['clock_out'] . ':00';
		$out_dt = DateTime::createFromFormat(util::DATE_TIME, $out_string, $this->timezone);

		//Check that clock in time matched format
		if ($in_dt === FALSE) {
			echo 'FALSE';
			return;
		}

		//Build insert query
		$user_id = $_POST['user_id'];
		$date = $_POST['date'];
		$clock_in = $in_dt->format(util::DATE_TIME);
		$type = 'work';

		if ($out_dt === FALSE) {
			$add_query = "
				INSERT INTO time_temp
				(user_id, date, type, clock_in)
				VALUES ('$user_id', '$date', '$type', '$clock_in')";
		} else {
			$clock_out = $out_dt->format(util::DATE_TIME);
			$total = $out_dt->getTimestamp() - $in_dt->getTimestamp();

			$add_query = "
				INSERT INTO time_temp
				(user_id, date, type, clock_in, clock_out, total)
				VALUES ('$user_id', '$date', '$type', '$clock_in', '$clock_out', '$total')";
		}

		//Add time record to database
		$add_result = db::exec($add_query);
		if ($add_result === FALSE) {
			echo 'FALSE';
			return;
		}
		
		//Get id of new time record
		$record_id = db::last_id();

		//Create new row for adjust table
		$in_display = $in_dt->format(self::adjust_time_format);
		$out_display = ($out_dt === FALSE)?'':$out_dt->format(self::adjust_time_format);
		?>
		<tr time_id="<?php echo $record_id;?>">
			<td class="c_in"><input type="text" class="time_input" value="<?php echo $in_display;?>"></td>
			<td class="c_out"><input type="text" class="time_input" value="<?php echo $out_display;?>"></td>
			<td>
				<button type="button" class="update_button">Update</button>
				<button type="button" class="delete_button">Delete</button>
			</td>
		</tr>
		<?php
	}
	
	public function ajax_adjust_delete()
	{
		//Get id of time record to delete
		$time_id = (int)$_POST['time_id'];

		//Set up delete query
		$del_query = "DELETE FROM time_temp WHERE id='$time_id' LIMIT 1";

		//Remove the time record
		$del_result = db::exec($del_query);

		//Make sure the delete happened
		if ($del_result === FALSE || $del_result < 1) {
			echo 'FALSE';
			return;
		}

		//Return true if all is well
		echo 'TRUE';
		return;
	}

	public function ajax_adjust_update()
	{
		//Get info together
		$in_string = $_POST['date'] . ' ' . $_POST['clock_in'] . ':00';
		$in_dt = DateTime::createFromFormat(util::DATE_TIME, $in_string, $this->timezone);
		$out_string = $_POST['date'] . ' ' . $_POST['clock_out'] . ':00';
		$out_dt = DateTime::createFromFormat(util::DATE_TIME, $out_string, $this->timezone);

		//Delicious error chex
		if ($in_dt === FALSE) {
			echo 'FALSE';
			return;
		}

		//Build update query
		$time_id = $_POST['time_id'];
		$date = $_POST['date'];
		$clock_in = $in_dt->format(util::DATE_TIME);

		if ($out_dt === FALSE) {
			$update_query = "
				UPDATE time_temp
				SET clock_in='$clock_in', clock_out=null, total=null
				WHERE id='$time_id'
				LIMIT 1";
		} else {
			$clock_out = $out_dt->format(util::DATE_TIME);
			$total = $out_dt->getTimestamp() - $in_dt->getTimestamp();

			$update_query = "
				UPDATE time_temp
				SET clock_in='$clock_in', clock_out='$clock_out', total='$total'
				WHERE id='$time_id'
				LIMIT 1";
		}

		//Run that query son/daughter! (girls write code too)
		$update_result = db::exec($update_query);
		if ($update_result === FALSE) {
			echo 'FALSE';
			return;
		}

		//return
		echo 'TRUE';
		//echo json_encode(array('success'=>'FALSE'));
		return;
	}
	
	public function pre_output_report()
	{
		list($this->start_date, $this->end_date, $this->type, $this->who, $this->user) = util::list_assoc($_POST, 'start_date', 'end_date', 'type', 'who', 'user');
		if (!util::is_valid_date_range($this->start_date, $this->end_date))
		{
			$this->end_date = date(util::DATE);
			$this->start_date = substr($this->end_date, 0, 7).'-01';
		}
		if (empty($this->who))
		{
			$this->who = 'all';
		}
	}
	
	public function display_report()
	{
		if (!(user::has_role('Leader', 'timecard') || user::is_developer()))
		{
			return;
		}
		?>
		<h1>Reports!</h1>
		<table>
			<tbody>
				<?php echo cgi::date_range_picker($this->start_date, $this->end_date, array('table' => false)); ?>
				<tr>
					<td>Report</td>
					<td><?php echo cgi::html_select('type', TimeReport::getReportTypes(), $this->type); ?></td>
				</tr>
				<tr>
					<td>Who</td>
					<td><?php echo cgi::html_radio('who', TimeReport::getUserTypes(), $this->who); ?></td>
				</tr>
				<tr id="user">
					<td>Employee</td>
					<td><?php $this->print_userSelect('user', $this->user); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_run_report" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
		echo $this->ml_rep;
	}
	
	public function action_run_report()
	{
		$rep = TimeReport::obtainReport($this->type);
		$q = $rep->buildQuery($this->start_date, $this->end_date, $this->who, $this->user);
		$data = $rep->runQuery($q);
		$this->ml_rep = $rep->mlResult($data);
		
		//e($data);
	}
	
	protected function print_userSelect($name, $select_id)
	{
		$select_name = '';
		$user_query = "
			SELECT id, realname
			FROM users
			".users::get_test_where_clause('where')."
			ORDER BY realname
		";
		$user_result = db::select($user_query, 'ASSOC');
		
		echo "<select name='$name'>\n";
		echo "<option value=''>Select</option>\n";
		for ($i = 0, $ci = count($user_result); $i < $ci; ++$i) {
			$user_row = $user_result[$i];
			if ($user_row['id'] == $select_id) {
				$select_name = $user_row['realname'];
				echo "<option selected='selected' value='".$user_row['id']."'>";
			}
			else {
				echo "<option value='".$user_row['id']."'>";
			}
			echo $user_row['realname'];
			echo "</option>\n";
		}
		echo "</select>\n";
		
		return $select_name;
	}

	protected function ml_pretty_total($seconds)
	{
		$h = floor($seconds / 3600);
		$m = round(($seconds - $h*3600)/60);
		return sprintf('%d:%02d', $h, $m);
	}

	protected function action_clock_in()
	{
		$user_id = user::$id;
		$today_date = $this->request_dt->format(util::DATE);
		
		//FIRST make sure not already clocked in!
		$is_clockin = $this->user_current_in($user_id, $today_date);
		if ($is_clockin != FALSE) {
			feedback::add_error_msg('Already clocked in.');
			return FALSE;
		}
		
		//Now clock them in
		$in_clock = $this->request_dt->format(util::DATE_TIME);
		$in_type = 'work';

		$clockin_query = "INSERT INTO time_temp"
			." (user_id,date,clock_in,type)"
			." VALUES ('$user_id','$today_date','$in_clock','$in_type')";

		$clockin_result = db::exec($clockin_query);
		if (!$clockin_result) {
			die("Error clocking in: " . db::last_error());
		}
		cgi::redirect('timecard');
	}

	protected function action_clock_out()
	{
		$user_id = user::$id;
		$today_date = $this->request_dt->format(util::DATE);
		
		//Check if clocked in
		$clock_in = $this->user_current_in($user_id, $today_date);
		if (!$clock_in) {
			feedback::add_error_msg('Must clock in before clocking out.');
			return FALSE;
		}

		$clock_in_dt = new DateTime($clock_in, $this->timezone);
		$clock_out = $this->request_dt->format(util::DATE_TIME);
		$clock_out_dt = $this->request_dt;

		$total_seconds = $clock_out_dt->format('U') - $clock_in_dt->format('U');

		$clockout_query = "UPDATE time_temp"
			." SET clock_out = '$clock_out', total = '$total_seconds'"
			." WHERE user_id = '$user_id'"
			." AND date = '$today_date'"
			." AND clock_in = '$clock_in'"
			." LIMIT 1";
		$clockout_result = db::exec($clockout_query);
		if (!$clockout_result) {
			die("Error clocking out: " . db::last_error());
		}
		cgi::redirect('timecard');
	}

	protected function user_current_in($user_id, $date)
	{
		//Try to grab all records with clock-in but no clock-out
		return db::select_one("SELECT clock_in FROM time_temp"
			." WHERE user_id = '$user_id'"
			." AND date = '$date'"
			." AND clock_in IS NOT NULL"
			." AND clock_out IS NULL"
			." ORDER BY clock_in DESC
		");
	}

	protected function ml_all_current_in($date)
	{
		$users = db::select("
			SELECT u.id, u.realname, t.clock_in
			FROM users AS u, time_temp AS t
			WHERE t.date = '$date'
			AND t.clock_in IS NOT NULL
			AND t.clock_out IS NULL
			AND t.user_id = u.id
			".users::get_test_where_clause()."
			ORDER BY u.realname
		");
		$day_start_time = strtotime($date);
		$ml = '';
		for ($i = 0; list($uid, $uname, $in) = $users[$i]; ++$i)
		{
			$in_time = strtotime($in);
			$diff = $in_time - $day_start_time;
			
			// before 8:30
			if ($diff < 32400)
			{
				$ml_on_time = 'ontime';
			}
			// before 8:40
			else if ($diff < 33000)
			{
				$ml_on_time = 'kindaontime';
			}
			else
			{
				$ml_on_time = 'youarelateandyoushouldfeelbad';
			}
			$ml .= '
				<div class="person '.$ml_on_time.'">
					'.$uname.'
				</div>
			';
		}
		return $ml;
	}

	protected function get_day($user_id, $date)
	{
		$day_query = "SELECT * FROM time_temp"
			." WHERE user_id = '$user_id'"
			." AND date = '$date'"
			." ORDER BY clock_in ASC";
		return db::select($day_query, 'ASSOC');
	}

	public function pre_output_calendar()
	{
		util::load_lib('hr');
		cgi::include_widget('calendar');

		require(__DIR__.'/wid.employee_calendar.php');
	}

	public function display_calendar()
	{
		$cal = new employee_calendar($this);
		$cal->output();
	}
}

?>
