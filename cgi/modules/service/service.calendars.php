<?php
cgi::include_widget('calendar');

class mod_service_calendars extends mod_service
{
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'billing';
	}
	
	public function display_billing()
	{
		$class = 'as_'.$this->dept;
		$managers = $class::get_manager_options();
		if (array_key_exists('manager', $_POST)) {
			$manager = $_POST['manager'];
			if ($manager != $_SESSION[$this->dept.'_calendars_billing_manager']) {
				$_SESSION[$this->dept.'_calendars_billing_manager'] = $manager;
			}
		}
		else if (array_key_exists($this->dept.'_calendars_billing_manager', $_SESSION)) {
			$manager = $_SESSION[$this->dept.'_calendars_billing_manager'];
		}
		else {
			// default to user if user is a manager
			$manager = false;
			for ($i = 0; list($uid, $realname) = $managers[$i]; ++$i) {
				if (user::$id == $uid) {
					$manager = $uid;
					break;
				}
			}
		}
		array_unshift($managers, array('', 'All'));
		
		$cal = new billing_calendar($this->dept, $manager);
		
		?>
		<table>
			<tbody>
				<tr>
					<td><label for="manager" class="p_r8">Manager</label></td>
					<td><?php echo cgi::html_select('manager', $managers, $manager, array('submit_on_change' => 1)); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
		$cal->output();
	}
}

class billing_calendar extends wid_calendar
{
	public $dept, $manager;

	public function __construct($dept, $manager)
	{
		$this->dept = $dept;
		$this->manager = $manager;
	}
	
	protected function get_data($start_time, $end_time)
	{
		$qwhere = array(
			"next_bill_date between :start and :end",
			"dept = :dept"
		);
		$qdata = array(
			'start' => date(util::DATE, $start_time),
			'end' => date(util::DATE, $end_time),
			'dept' => $this->dept
		);
		if ($this->manager) {
			$qwhere[] = "manager = :manager";
			$qdata['manager'] = $this->manager;
		}
		$data = db::select("
			select id, name, next_bill_date
			from eac.account
			where ".implode(" && ", $qwhere)."
			order by name asc
		", $qdata, 'ASSOC', array('next_bill_date'));
		
		return $data;
	}
	
	protected function get_href(&$d)
	{
		return (cgi::href('account/service/'.$this->dept.'/billing?aid='.$d['id']));
	}
	
	protected function display_data(&$d)
	{
		return ('&bull; '.$d['name']);
	}
}

?>