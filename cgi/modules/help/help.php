<?php

class mod_help extends module_base
{
	protected $m_name = 'help';

	public $skin = 'cameo';
	
	public function pre_output()
	{
		
	}
	
	public function get_menu()
	{
		return new Menu(array(
			new Menu(array(
				new MenuItem('Client Help Request Form'  ,'client_help_request_form')
			), 'help/forms', null, 'Forms')
		), 'help');
	}
	
	public function display_index()
	{
		?>
		<blockquote>
		"I get by with a little help from my friends"
			<footer>The Beatles</footer>
		</blockquote>
		<?
	}

	public function pre_output_forms()
	{
		if (!method_exists($this, g::$p3)) die('Invalid method');

		if (method_exists($this, 'pre_output_'.g::$p3)) {
			$this->{'pre_output_'.g::$p3}();
		}
	}

	public function display_forms()
	{
		$this->{g::$p3}();
	}

	public function pre_output_client_help_request_form()
	{
		util::load_lib('asana_lib');
		$asana = new AsanaLib();
		$asana->connect();

		if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST)){

			//
			// Start of asana task formatting
			//

			// client name
			$task_name = $_POST['client_name'];

			// client task summary
			$task_notes = '[SUMMARY]'.PHP_EOL.$_POST['summary'].PHP_EOL.PHP_EOL;

			// does the client use a cms
			if (isset($_POST['use_cms'])){
				$task_notes .= '[CMS INFO]'."\n";
				foreach($_POST['cms'] as $cms_field => $value){
					$task_notes .= $cms_field.': '.$value."\n";
				}
				$task_notes .= "\n";
			}

			// does the client use an ftp
			if (isset($_POST['use_ftp'])){
				$task_notes .= '[FTP INFO]'."\n";
				foreach($_POST['ftp'] as $ftp_field => $value){
					$task_notes .= $ftp_field.': '.$value."\n";
				}
				$task_notes .= "\n";
			}

			// what is the convertion tracking type/method
			$task_notes .= '[Conversion Tracking]'."\n";
			foreach($_POST['ct'] as $ct_field => $value){
				if(empty($value)) continue;
				$task_notes .= $ct_field.': '.$value."\n";
			}
			$task_notes .= "\n";

			// who made the request
			$task_notes .= '[SUBMITTED BY]'."\n";
			$task_notes .= user::$realname.' | '.user::$name;

			//
			// End of asana task formatting
			//

			$workspaceId = '14825485509986'; // wpromote.com workspace
			$projectId = '15124430178793'; // Client Requests

			// First we create the task
			$result = $asana->api->createTask(array(
			    'workspace' => $workspaceId, // Workspace ID
			    'name' => $task_name, // Name of task
			    "notes" => $task_notes
			    //'assignee' => '', // Assign task to...
			    //'followers' => array() // We add some followers to the task...
			));

			// As Asana API documentation says, when a task is created, 201 response code is sent back so...
			if ($asana->api->responseCode != '201' || is_null($result)) {
			    feedback::add_error_msg('Error while trying to connect to Asana, response code: <b>' . $asana->api->responseCode . '</b>');
			    return;
			}

			$resultJson = json_decode($result);

			$taskId = $resultJson->data->id; // Here we have the id of the task that have been created

			// Now we do another request to add the task to a project
			$result = $asana->api->addProjectToTask($taskId, $projectId);

			if ($asana->api->responseCode != '200') {
			    feedback::add_error_msg('Error while assigning project to task: <b>' . $asana->api->responseCode . '</b>');
			    return;
			}

			feedback::add_success_msg('<b>Awesome!</b> Your request has been successfully submitted.');

		}

	}

	public function client_help_request_form()
	{
		?>
		<h1>Client Help Request Form</h1>

		<p>In order to achieve the best turn around on adding content such as conversion tracking to a client’s site, we need the following information:</p>

		<section class="panel">
			<header class="panel-heading">What is the name of the client?</header>
			<div class="panel-body">
				<input type="text" name="client_name" class="form-control" placeholder="Wpromote" data-parsley-required="true" data-parsley-trigger="change"></textarea>
			</div>
		</section>

		<section class="panel">
			<header class="panel-heading">In a few words, what are you trying to accomplish?</header>
			<div class="panel-body">
				<textarea name="summary" class="form-control" rows="5" data-parsley-required="true" data-parsley-trigger="change"></textarea>
			</div>
		</section>

		<section class="panel">
			
			<header class="panel-heading">
					<label class="checkbox checkbox-custom">
                        <input type="checkbox" data-toggle="cms-form" name="use_cms" value="1" />
                        <i class="checkbox"></i>
                        Does the client use a CMS?
                    </label>
			</header>

			<div class="panel-body">

				<div class="form-horizontal" id="cms-form" style="display: none;">
					
					<div class="form-group">
						<label class="col-sm-2 control-label">Name of CMS</label>
						<div class="col-sm-10">
							<input name="cms[name]" type="text" class="form-control" list="cmsNames" placeholder="WordPress" />
							<datalist id="cmsNames">
								<option value="WordPress">
								<option value="Joomla">
								<option value="ModX">
								<option value="TextPattern">
								<option value="RefineryCMS">
								<option value="Drupal">
								<option value="Concrete5">
								<option value="DotNetNuke">
								<option value="Umbraco">
								<option value="TinyCMS">
						    </datalist>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label">URL</label>
						<div class="col-sm-10">
							<input type="text" name="cms[url]" class="form-control" placeholder="http://wpromote.wordpress.com" />
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label">Username</label>
						<div class="col-sm-10">
							<input type="text" name="cms[username]" class="form-control" />
							<p class="help-block no-margin">Make sure this user has admin access.</p>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label">Password</label>
						<div class="col-sm-10">
							<input type="text" name="cms[password]" class="form-control" />
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label">Documentation</label>
						<div class="col-sm-10">
							<input type="text" name="cms[documentation]" class="form-control" placeholder="http://codex.wordpress.org/"/>
						</div>
					</div>

				</div>

			</div>

		</section>

		<section class="panel">
			
			<header class="panel-heading">
					<label class="checkbox checkbox-custom">
                        <input type="checkbox" data-toggle="ftp-form" name="use_ftp" value="1" />
                        <i class="checkbox"></i>
                        Does the client use FTP?
                    </label>
			</header>

			<div class="panel-body">

				<div class="form-horizontal" id="ftp-form" style="display: none;">

					<div class="form-group">
						<label class="col-sm-2 control-label">Host</label>
						<div class="col-sm-10">
							<input type="text" name="ftp[host]" class="form-control" placeholder="http://wpromote.com/"/>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label">Username</label>
						<div class="col-sm-10">
							<input type="text" name="ftp[username]" class="form-control" />
							<p class="help-block no-margin">Make sure this user has full access.</p>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label">Password</label>
						<div class="col-sm-10">
							<input type="text" name="ftp[password]" class="form-control" />
						</div>
					</div>

				</div>

			</div>

		</section>

		<section class="panel">
			
			<header class="panel-heading">
				Adding General Conversion Tracking or Monetary Conversion Tracking?
			</header>

			<div class="panel-body">

				<div class="form-horizontal">

					<div class="form-group">

						<div class="col-sm-10">

	                        <label class="radio radio-custom">
	                            <input type="radio" name="ct[type]" checked="checked" value="general">
	                            <i class="radio"></i>General</label>
	                        <label class="radio radio-custom">
	                            <input type="radio" name="ct[type]" value="monetary">
	                            <i class="radio"></i>Monetary</label>

	                    </div>

                    </div>

				</div>

			</div>

		</section>

		<section class="panel">
			
			<header class="panel-heading">
				Method for Implimentin Conversion Tracking (Choose on or more)
			</header>

			<div class="panel-body">

				<div class="form-horizontal">

					<div class="form-group">
                        <label class="col-sm-2 control-label">FTP</label>
                        <div class="col-sm-10">

                            <div class="input-group mg-b-md">
                                <span class="input-group-addon">
                                    <input type="checkbox" class="enable-text">
                                </span>
                                <input type="text" class="form-control" name="ct[ftp]" disabled>
                                
                            </div>

                            <p class="help-block" style="margin: -15px 0 0 40px;">What language is the file?</p>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">CMS</label>
                        <div class="col-sm-10">

                            <div class="input-group mg-b-md">
                                <span class="input-group-addon">
                                    <input type="checkbox" class="enable-text">
                                </span>
                                <input type="text" class="form-control" name="ct[cms]" disabled>
                                
                            </div>

                            <p class="help-block" style="margin: -15px 0 0 40px;">What CMS?</p>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Edit Code</label>
                        <div class="col-sm-10">

                            <div class="input-group mg-b-md">
                                <span class="input-group-addon">
                                    <input type="checkbox" class="enable-text">
                                </span>
                                <input type="text" class="form-control" name="ct[code]" disabled>
                                
                            </div>

                            <p class="help-block" style="margin: -15px 0 0 40px;">What language is the website written in?</p>

                        </div>
                    </div>

				</div>

			</div>

		</section>

		<div class="form-group">
            <div class="col-sm-4">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i>Submit</button>
            </div>
        </div>
		<?
	}
	
}
































?>