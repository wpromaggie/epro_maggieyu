<?php
class mod_surveys_clients extends mod_surveys
{
	private $survey;
	
	public function pre_output()
	{
		$this->survey = new survey();
		if(isset($_GET['action'])){
			$action_func = 'action_'.$_GET['action'];
			$this->{$action_func}();
		}
	}
	
	public function display_index()
	{
		$client_surveys = $this->survey->find_client_surveys();
		$ml = "";
		foreach($client_surveys as $cs){
			
			$client = db::select_row("
				SELECT id, name
				FROM eac.client
				WHERE id = :cid
				LIMIT 1
			", array(
				"cid" => $cs['client_id']
			), 'ASSOC');
				
			$contact = db::select_row("
				SELECT *
				FROM eppctwo.contacts
				WHERE client_id = :cid
				LIMIT 1
			", array(
				"cid" => $cs['client_id']
			), 'ASSOC');
				
			$manager = db::select_row("
				select * from eppctwo.users
				where id = {$cs['user_id']}
				limit 1
			", 'ASSOC');
				
			$survey_preview_link = '<a href="'.cgi::href('surveys/view?id='.$cs['id']).'">View</a>';
			$delete_link = '<a href="'.cgi::href('surveys/clients/?action=delete&id='.$cs['id']).'">Delete</a>';
			$post_to_wpro = '<a href="'.cgi::href('surveys/clients/?action=post_to_wpro&id='.$cs['id']).'">Post to Dash</a>';

			$action_links = "$survey_preview_link | $delete_link | $post_to_wpro";
			if($cs['status']=="complete"){
				$action_links .= ' | <a href="'.cgi::href('surveys/clients/?action=unlock&id='.$cs['id']).'">Unlock</a>';
			}
			
			$ml .= "<tr>";
				$ml .= "<td><a href=\"https://".\epro\WPRO_DOMAIN."/survey/view/{$cs['urlkey']}"."\" target=\"_blank\">{$cs['urlkey']}</a></td>";
				$ml .= "<td>{$client['name']}</td>";
				if($contact){
					$ml .= "<td>{$contact['name']}</td>";
					$ml .= "<td>{$contact['email']}</td>";
				} else {
					$ml .= "<td></td><td></td>";
				}
				$ml .= "<td>{$cs['status']}</td>";
				$ml .= "<td>{$cs['last_mod']}</td>";
				$ml .= "<td>{$manager['realname']}</td>";
				$ml .= "<td>$action_links</td>";
			$ml .= "</tr>";
		}
	?>
		<table id="client-surveys" width="100%">
			<thead>
				<tr style="text-align: left;">
					<th>URL Key</th>
					<th>Company</th>
					<th>Contact Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Last Modified</th>
					<th>Manager</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
		</table>
	<?php
	}

	public function display_unattached()
	{
		$client_surveys = $this->survey->find_unattached_surveys();
		$ml = "";
		foreach($client_surveys as $cs){
				
			$manager = db::select_row("
				select * from eppctwo.users
				where id = {$cs['user_id']}
				limit 1
			", 'ASSOC');
				
			$survey_preview_link = '<a href="'.cgi::href('surveys/view?id='.$cs['id']).'">View</a>';
			$delete_link = '<a href="'.cgi::href('surveys/clients/?action=delete&id='.$cs['id']).'">Delete</a>';
			$action_links = "$survey_preview_link | $delete_link";
			if($cs['status']=="complete"){
				$action_links .= ' | <a href="'.cgi::href('surveys/clients/?action=unlock&id='.$cs['id']).'">Unlock</a>';
			}
			
			$ml .= "<tr>";
				$ml .= "<td><a href=\"https://".\epro\WPRO_DOMAIN."/survey/view/{$cs['urlkey']}"."\" target=\"_blank\">{$cs['urlkey']}</a></td>";
				$ml .= "<td>{$cs['status']}</td>";
				$ml .= "<td>{$cs['last_mod']}</td>";
				$ml .= "<td>{$manager['realname']}</td>";
				$ml .= "<td>$action_links</td>";
			$ml .= "</tr>";
		}
		?>
		<p><a href="<?php echo cgi::href('surveys/clients/add'); ?>">Create New Survey</a></p>
		<table id="client-surveys" width="100%">
			<thead>
				<tr style="text-align: left;">
					<th>URL Key</th>
					<th>Status</th>
					<th>Last Modified</th>
					<th>Manager</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
		</table>
	<?php
	}
	
	public function display_add()
	{
		if(empty($_GET['cl_id'])){
			$_GET['cl_id'] = '';
			$client = array(
				'name' => 'Unknown'
			);
		} 
		else {
			$client = db::select_row("
				SELECT id, name
				FROM eac.client
				WHERE id = '{$_GET['cl_id']}'
			", "ASSOC");
			if(empty($client)){
				feedback::add_error_msg("Invalid client id");
				return;
			}
		}
		
		$survey = $_POST['survey'];
		
		$layouts = $this->survey->get_all_layouts();
		$layout_options = "<option value=''>--- Select the survey ---</options>";
		foreach($layouts as $l){
			
			$selected = "";
			if(isset($survey['layout_id']) && $survey['layout_id']==$l['id']){
				$selected = " selected='selected'";
			}
			
			$layout_options .= "<option value='{$l['id']}'$selected>{$l['name']}</option>";
		}
	?>
		<h2>Create Create Survey: <?php echo $client['name']; ?></h2>
		
		<input type="hidden" id="client_id" name="survey[client_id]" value="<?php echo $_GET['cl_id'] ?>" />
		
		<div class="input-field">
			<select id="layout-select" name="survey[layout_id]">
				<?php echo $layout_options ?>
			</select>
		</div>
		
		<div class="input-field">
			<label>URL Key: </label>
			<input type="text" id="urlkey" name="survey[urlkey]" value="<?php echo $survey['urlkey'] ?>" />
		</div>
		
		<input type="submit" a0="action_create_survey" value="Submit" />
	<?php
	}
	
	public function action_delete()
	{
		$this->survey->delete_client_survey($_GET['id']);
		cgi::redirect('surveys/clients');
	}
	
	public function action_unlock()
	{
		$this->survey->unlock_client_survey($_GET['id']);
		cgi::redirect('surveys/clients');
	}

	public function action_post_to_wpro()
	{
		$survey = $this->survey->find_client_survey_by_id($_GET['id']);

		$this->survey = new Survey($survey['layout_id']);
		$layout = $this->survey->get_layout();

		$survey['title'] = $layout['name'];

		//e($survey);
		util::wpro_post('dashboard', 'submit_survey', $survey);
	}
	
	public function action_create_survey()
	{
		$survey = $_POST['survey'];
		$urlkey = $survey['urlkey'];
		
		if(empty($survey['layout_id'])){
			feedback::add_msg('You must select a survey layout.', 'error');
			return;
		}
		
		//validate urlkey
		if(preg_match('/[^a-z\-0-9]/i', $urlkey)){
			feedback::add_msg('Only alphanumeric characters and -\'s are accepted for the url key.', 'error');
			return;
		}
		
		//check for duplicate urlkeys
		$duplicate = db::select_row("SELECT urlkey FROM surveys.client_surveys WHERE urlkey='$urlkey' LIMIT 1");
		if($duplicate){
			feedback::add_msg('There is already a survey with this url key.', 'error');
			return;
		}
		
		$this->survey->create_client_survey($survey);

		if (!empty($survey['client_id'])){
			cgi::redirect('surveys/clients');
		}
		else {
			cgi::redirect('surveys/clients/unattached');
		}
		
	}
	
}
?>
