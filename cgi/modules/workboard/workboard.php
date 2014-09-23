<?php
/**
 * Represents a Task, and allows for pretty printing
 * @author Matthew Alvarez Monahan
 */
class Task {

	public $taskId;
	public $clientId;
	public $clientName;
	public $department;
	public $active;
	public $startDate;
	public $endDate;
	public $description;
	
	/**
	 * Returns an html representation of this Task
	 */
	 public function toHtml() {
		$html;
		$html .= "<div class=\"task\">\n";
		$html .= "	<div class=\"header\">\n";
		$html .= "		<span class=\"client\">".$this->clientName."</span>\n";
		$html .= "		<br />\n";
		$html .= "		<span class=\"department\">".$this->department."</span>\n";
		$html .= "	</div>\n";
		$html .= "	<div class=\"body\">\n";
		$html .= "		<span class=\"date\">".$this->startDate."</span>\n";
		$html .= "		<span class=\"date\">".$this->endDate."</span>\n";
		$html .= "		<br />\n";
		$html .= "		<span class=\"description\">$this->description</span>\n";
		$html .= "	</div>\n";
		$html .= "</div>\n";
		return $html;
	 }
}

/**
 * Workboard module. The workboard allows you to track and view the work that's
 * been assigned to you and various other people on your team. Work is tracked
 * by creating "tasks" for the things that need to get done. Tasks allow you to
 * track the work that you're currently responsible for, the details associated
 * with that work, and who is responsible for the other tasks within your team.
 * @author Matthew Alvarez Monahan
 */
class mod_workboard extends module_base {
	protected $m_name = 'workboard';

	public function get_menu()
	{
		return array(
			new MenuItem('View', array('workboard', 'view')),
			new MenuItem('Create', array('workboard', 'create')),
			new MenuItem('Assign', array('workboard', 'assign')),
			new MenuItem('Edit', array('workboard', 'edit'))
		);
	}

	public function pre_output() {
		$this->call_member($_POST['go']);
	}

	public function output() {
		echo "<div id=\"workboard\">\n";
        echo "<h1>Workboard</h1>\n";
		
		$this->call_member(g::$p2, 'view');
		echo "\n</div>\n";
	}

	/**
	 * Prints out the View page for the Workboard.
	 */
	protected function view() {

		//Print some silly welcome message
		echo "Welcome to the Jungle<br />\n";
		echo "Your current tasks:<br />\n";

		//Snag the userID of the current user
		$user_id = $_SESSION["id"];

		//Snag some tasks from the database
		$tasks = $this->getUserTasks($user_id);

		//Print the tasks
		foreach ($tasks as &$task) {
			echo $task->toHtml();
		}
	}

	/**
	 * Prints out the Create page for the Workboard.
	 */
	protected function create() {
		echo "Create new task:<br />\n";
		echo "User: ".$this->userSelect()."<br />";
		echo "Client: ".$this->clientSelect()."<br />";
		echo "Department: ".$this->departmentSelect()."<br />";
		echo "Start: <input type=\"text\" name=\"start_date\" value=\"".date("Y-m-d")."\" /><br />";
		echo "End: <input type=\"text\" name=\"end_date\" value=\"".date("Y-m-d")."\" /><br />";
		echo "Description:<br />";
		echo "<textarea name=\"description\" rows=\"4\" cols=\"20\">It's a dead man's party...</textarea><br />";
		echo "<input type=\"submit\" value=\"Create\" go=\"processCreate\" />\n";
	}

	protected function processCreate() {
		
		//Insert the task into the task table
		$task_insert = "INSERT INTO `tasks`"
			." (`client_id`,`department`,`start`,`end`,`description`)"
			." VALUES ('"
			.$_POST["client_select"] . "','"
			.$_POST["department_select"] . "','"
			.$_POST["start_date"] . "','"
			.$_POST["end_date"] . "','"
			.$_POST["description"] . "')";

		$result = mysql_query($task_insert);
		if (!$result) {
			die("Failed to create task");
		}

		//Insert the task and user into the task assignment table
		$task_id = mysql_insert_id();
		$user_id = $_POST["user_select"];
		$assign_insert = "INSERT INTO `task_assignment` (`user_id`,`task_id`)"
			." VALUES ('".$user_id."','".$task_id."')";

		$result = mysql_query($assign_insert);
		if (!$result) {
			die("Failed to create task assignment");
		}

		//This is kinda like a redirect...
		g::$p2 = 'index';
	}

	/**
	 * Prints out the Assign page for the Workboard.
	 */
	protected function assign() {
		//Print some silly welcome message
		echo "Welcome to the Amphitheatre<br />\n";

		//if a user was picked, show the user's tasks
		if ($_POST["user_select"]) {
			//Snag the userID of the current user
			$userId = $_POST["user_select"];

			//Print the selected user's name
			echo "Tasks for ".$this->getUserName($userId).":<br />\n";

			//Get the tasks from the DB
			$tasks = $this->getUserTasks($userId);

			//Print the tasks
			foreach ($tasks as &$task) {
				echo $task->toHtml();
			}
		}

		//pick a user to view their tasks
		echo "<br />\n";
		echo $this->userSelect();
		echo "<input type=\"submit\" value=\"Check them out\" />\n";
		echo "<br />\n";
	}

	/**
	 * Prints out the Edit page for the Workboard.
	 */
	protected function edit () {
		//Print some silly welcome message
		echo "Welcome to the Cafeteria<br />\n";
		
	}

	/**
	 * Get's the name of the user with the given ID.
	 * @param integer $userId the ID of the user.
	 * @return string the name of the user.
	 */
	protected function getUserName($userId) {

		//Check for a numeric userID
		if (!is_numeric($userId)) {
			die("bogus non-numeric userId: ".$userId);
		}

		//Query the DB
		$query = "SELECT `realname` FROM `users` WHERE id='".$userId."' LIMIT 1";
		$result = mysql_query($query);
		if (!$result) {
			die("Error running query: ".$query."\n".db::last_error());
		}

		//Get the result and check it
		$name = mysql_result($result, 0);
		if (!$name) {
			$name = "Mr. Invalid User";
		}
		return $name;
	}

	/**
	 * Uses the given userId to query the database for tasks assigned to that user
	 * @param integer $userId the ID of the user to get tasks for.
	 * @return array of Tasks that have been assigned to the given user.
	 */
	protected function getUserTasks($userId) {

		//The task array to return
		$task_array = array();

		//Snag some tasks from the database
		$query = "SELECT"
			." task_assignment.task_id AS task_id,"
			." tasks.client_id AS client_id,"
			." tasks.department AS department,"
			." tasks.active AS active,"
			." tasks.start AS start,"
			." tasks.end AS end,"
			." tasks.description AS description,"
			." clients.name AS client_name"
			." FROM `tasks`,`task_assignment`,`clients`"
			." WHERE task_assignment.user_id='".$userId."'"
			." AND task_assignment.task_id = tasks.task_id"
			." AND tasks.client_id = clients.id;";
		$result = mysql_query($query);
		if (!$result) {
			die("error pulling tasks from database.");
		}

		//build the task array
		$num_rows = mysql_num_rows($result);
		for ($i = 0; $i < $num_rows; $i++) {
			$row = mysql_fetch_assoc($result);

			$task = new Task();
			$task->taskId = $row["task_id"];
			$task->clientId = $row["client_id"];
			$task->clientName = $row["client_name"];
			$task->department = $row["department"];
			$task->active = $row["active"];
			$task->startDate = $row["start"];
			$task->endDate = $row["end"];
			$task->description = $row["description"];

			$task_array[$i] = $task;
		}

		//return the array of tasks
		return $task_array;
	}

	/**
	 * Builds a select field containing the names and userIDs of all of
	 * the people working at wpro. Use it in a form.
	 * @param string $selectName the name to use for this field
	 * @return string the html for a user select form field
	 */
	protected function userSelect($selectName = "user_select") {

		//start the user select field
		$user_select = "<select name=\"".$selectName."\">\n";

		//get users from the database
		$user_query = "SELECT `realname`, `id` FROM `users`";
		$result = mysql_query($user_query);
		if (!$result) {
			die("Error running query: ".$user_query);
		}

		//add users as options in the select dropdown
		$num_rows = mysql_num_rows($result);
		for ($i=0; $i<$num_rows; $i++) {
			$row = mysql_fetch_assoc($result);
			$user_select .= "\t<option value=\""
				.$row["id"]
				."\">"
				.$row["realname"]
				."</option>\n";
		}

		//close the user select field
		$user_select .= "</select>\n";

		return $user_select;
	}

	/**
	 * Builds a select field containing the names of all of the clients of
	 * wpro. Use it in a form and people will be able to select some client
	 * @param string $selectName the name to use for this field
	 * @return string the html for a user select form field
	 */
	 protected function clientSelect($selectName = "client_select") {

		//start the user select field
		$client_select = "<select name=\"".$selectName."\">\n";

		//get users from the database
		$client_query = "SELECT `name`, `id` FROM `clients` WHERE `status`=\"On\"";
		$result = mysql_query($client_query);
		if (!$result) {
			die("Error running query: ".$client_query);
		}

		//add users as options in the select dropdown
		$num_rows = mysql_num_rows($result);
		for ($i=0; $i<$num_rows; $i++) {
			$row = mysql_fetch_assoc($result);
			$client_select .= "\t<option value=\""
				.$row["id"]
				."\">"
				.$row["name"]
				."</option>\n";
		}

		//close the user select field
		$client_select .= "</select>\n";

		return $client_select;
	}

	/**
	 * Creates a select field containing the names of all the departments
	 * in wpromote. Use it in a form so people can pick a department.
	 * @param string $selectName the name to use for this field
	 * @return string the html used to create a department combo-box
	 */
	protected function departmentSelect($selectName = "department_select") {

		$department_names = array("Programming","Sales","PPC","QuickList","Web Dev");

		//Start the select field
		$department_select = "<select name=\"".$selectName."\">\n";

		//Add an option for each department
		foreach ($department_names as $name) {
			$department_select .= "\t<option value=\"".$name."\">".$name."</option>";
		}

		//Close the select field
		$department_select .= "</select>\n";

		return $department_select;
	}
}
?>