<?php
class mod_surveys extends module_base
{
	private $survey;
	
	public function get_menu()
	{
		$menu = array();
		$menu[] = new MenuItem('Layouts', array('surveys'));
		$menu[] = new MenuItem('Clients', array('surveys', 'clients'));
		$menu[] = new MenuItem('Unattached', array('surveys', 'clients', 'unattached'));
		return $menu;
	}
	
	public function pre_output()
	{
		$this->survey = new survey();
	}
	
	public function head()
	{
		
	}
	
	public function action_add_layout()
	{
		$id = $this->survey->add_layout($_POST['layout']);
		cgi::redirect('surveys/edit?id='.$id);
	}

	public function display_index()
	{
		$layouts = $this->survey->get_all_layouts();
	?>
		<h1>Welcome to Survey Land!!</h1>
		
		<div id="add_layout_form">
			<div>
				<label>Layout Name</label>
				<input type="text" name="layout[name]" value="" />
			</div>
			<div>
				<label>Select Default Department</label>
				<select name="layout[default_dept]">
					<option value=""></option>
					<?php
					foreach(survey::get_departments() as $text => $value){
						echo "<option value='$value'>$text</option>";
					}
					?>
				</select>
			</div>
			<input type="submit" class="submit" a0="action_add_layout" value="Add Layount" />
		</div>
		
		<div id="survey_layouts">
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Dept</th>
						<th>Last Edit</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($layouts as $layout){ ?>
					<tr>
						<td><a href="https://<?php echo \epro\WPRO_DOMAIN ?>/survey/demo/<?php echo $layout['id']; ?>" target="_blank"><?php echo $layout['name']; ?></a></td>
						<td><?php echo $layout['default_dept']; ?></td>
						<td><?php echo $layout['last_mod']; ?></td>
						<td>
							<a href="<?php echo cgi::href('surveys/edit?id='.$layout['id']); ?>">Edit</a> |
							<a href="<?php echo cgi::href('surveys/delete?id='.$layout['id']); ?>" onclick="return confirm('Are you sure you want to delete this layout?');">Delete</a>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php
	}
	
	public function display_delete()
	{
		$this->survey->delete_layout($_GET['id']);
		cgi::redirect('surveys');
	}

	//todo: this function should be an action (how do we link to an action)
	public function action_download_file()
	{
		$file = db::select_row('
			SELECT * FROM surveys.client_files
			WHERE id = '.$_POST['fileid']
		, "ASSOC");

		// We'll be outputting a ...
		header('Content-type: '.$file['type']);

		// It will be called...
		header('Content-Disposition: attachment; filename="'.pathinfo($file['filename'], PATHINFO_BASENAME).'"');

		// The source is...
		readfile('http://'.\epro\MEDIA_DOMAIN.'/survey/'.$file['client_survey_id'].'/'.$file['filename']);
	}
	
	public function display_view()
	{
		$client_survey = db::select_row("SELECT * FROM surveys.client_surveys WHERE id={$_GET['id']}", 'ASSOC');
		$layout = db::select_row("SELECT * FROM surveys.layouts WHERE id={$client_survey['layout_id']}", "ASSOC");
		
		$survey = $this->survey->build_client_survey($_GET['id']);
		foreach($survey['format'] as $cat){
			$ml .= "<div class='cat'>";
			$ml .= "<h3>{$cat['name']}</h3>";
			$i=1;
			foreach($cat['questions'] as $q){
				$ml .= "<h4>$i. {$q['q_text']}</h4>";
				$ml .= "<p>{$q['a_text']}</p>";
				$i++;
			}
			$ml .= "</div>";
		}
		$ml .= "<h3>Attachments</h3>";
		foreach($survey['files'] as $file){
			$ml .= '<div class="file">';
				$ml .= '<a href="#" a0="action_download_file" data-fileid="'.$file['id'].'">';

					if (strpos($file['type'], 'image') !== false) {
						$ml .= '<img src="http://'. \epro\MEDIA_DOMAIN .'/survey/'. $file['client_survey_id'] .'/'. $file['filename'] .'" width="128" />';
					} else {
						$ml .= $file['filename'];
					}


				$ml .= '</a>';
			$ml .= '</div>';
		}
	?>
		<div id="details" style="float: right;">
			<?php echo "Status: ".$survey['status']; ?>
		</div>
		
		<div id="client-settings">
			<div>
				<label>URL Key</label>
				<input type="text" name="client_survey[urlkey]" value="<?php echo $client_survey['urlkey'] ?>"/>
			</div>
			<div>
				<label>Manager</label>
				<select name="client_survey[user_id]">
					<?php
					foreach(users::get_all_users() as $u){
						$selected = ($u['id']==$client_survey['user_id']) ? ' selected' : '';
						echo "<option value='{$u['id']}'$selected>{$u['realname']}</option>";
					}
					?>
				</select>
			</div>
			<div>
				<input type="hidden" name="client_survey[id]" value="<?php echo $client_survey['id'] ?>" />
				<input type="submit" value="Update" a0="action_update_client_survey" />
			</div>
		</div>
	<?php
		echo $ml;
		e($layout);
	}
	
	public function action_update_client_survey()
	{
		$client_survey = $_POST['client_survey'];
		db::update('surveys.client_surveys', $client_survey, 'id='.$client_survey['id']);
	}

	public function sts_get_file()
	{
		$file = db::select_row('
			SELECT * FROM surveys.client_files
			WHERE id = '.$_POST['id'],
		'ASSOC');
		echo json_encode($file);
	}

	public function sts_remove_file()
	{
		//get file record
		$file = db::select_row('
			SELECT * FROM surveys.client_files
			WHERE id = '.$_POST['id'],
		'ASSOC');

		//delete record
		db::exec('
			DELETE FROM surveys.client_files
			WHERE id = '.$_POST['id']
		);

		echo json_encode($file);
	}
	
	public function sts_get_survey()
	{
		 $s = $this->survey->find_client_survey_by_urlkey($_POST['urlkey']);
		 if(empty($s) || $s['status']=="deleted"){
			 return '';
		 }
		 $survey = $this->survey->build_client_survey($s['id']);
		 $survey['layout'] = db::select_row("SELECT * FROM surveys.layouts WHERE id={$survey['layout_id']}", "ASSOC");
		 $survey['client'] = db::select_row("SELECT * FROM clients WHERE id={$survey['client_id']}", 'ASSOC');
		 $survey['user'] = db::select_row("SELECT * FROM users WHERE id={$survey['user_id']}", 'ASSOC');

		 echo serialize($survey);
	}
	
	public function sts_get_survey_layout()
	{
		 $survey = $this->survey->build_layout_survey($_POST['layout_id']);
		 $survey['layout'] = db::select_row("SELECT * FROM surveys.layouts WHERE id={$_POST['layout_id']}", "ASSOC");
		 echo serialize($survey);
	}
	
	public function sts_save_answers()
	{
		//db::dbg();
		//e($_POST);
		unset($_POST['_sts_func_']);
		
		$id = $_POST['id'];
		unset($_POST['id']);
		
		$action = $_POST['action'];
		unset($_POST['action']);

		$files = json_decode($_POST['file'], true);
		unset($_POST['file']);
		//e($files);
		//exit;

		foreach($files as $file){
			//db::dbg();
			db::insert('surveys.client_files', array(
				'client_survey_id' => $id,
				'filename' => $file['name'],
				'type' => $file['type']
			));
			//exit;
		}
		
		$update_fields = array(
			'last_mod' => date(util::DATE_TIME) 
		);
		
		foreach($_POST as $q_id => $text){
			db::insert_update(
				'surveys.client_questions', 
				array('id'), 
				array(
				    'id' => $q_id,
				    'a_text' => $text
				)
			);
		}

		if($action=="complete"){

			$update_fields['status'] = 'complete';
			$survey = $this->survey->find_client_survey_by_id($id);

			$layout = db::select_row("SELECT * FROM surveys.layouts WHERE id={$survey['layout_id']}", "ASSOC");

			if (!empty($survey['client_id'])){
				$client = db::select_row("SELECT * FROM clients WHERE id={$survey['client_id']}", 'ASSOC');
			}
			else {
				$client = array('name' => 'Unassigned client');
			}

			//send email
			$emailto =  db::select_one("SELECT username FROM users WHERE id={$survey['user_id']}", 'ASSOC');
			
			//email body
			$emailbody = "{$client['name']} has completed their client survey. https://".\epro\WPRO_DOMAIN."/survey/view/".$survey['urlkey'];

			//Infographic-Request
			if ($layout['id']==13) {

				if($emailto != 'rfarrell@wpromote.com'){
					$emailto .= ', rfarrell@wpromote.com';
				}
				$emailto .= ', mcalderon@wpromote.com, jgodinho@wpromote.com';

				$emailbody .= "\n\n";
				$survey_results = $this->survey->build_client_survey($id);

				foreach($survey_results['format'] as $cat){
					$emailbody .= $cat['name']."\n";
					$i=1;
					foreach($cat['questions'] as $q){
						$emailbody .= $i." ".$q['q_text']."\n";
						$emailbody .= $q['a_text']."\n";
						$i++;
					}
					$emailbody .= "\n";
				}

				//attachments
				$additional_headers = array();
				if(!empty($survey_results['files'])){
					$attachments = array();
					foreach($survey_results['files'] as $file){
						$attachments[] = array(
							'name' => $file['filename'],
							'path' => 'http://'. \epro\MEDIA_DOMAIN .'/survey/'. $file['client_survey_id'] .'/'. $file['filename']
						);
					}
					$additional_headers = array('attachments' => $attachments);
				}
				
				util::mail('clientsurveys@wpromote.com', $emailto, 'New Request for an Infographic', $emailbody, $additional_headers);

			}
			else {
				//$emailbody .= print_r($layout, true);
				util::mail('clientsurveys@wpromote.com', $emailto, 'Client Survey Submit', $emailbody);
			}
		}
		

		db::update('surveys.client_surveys', $update_fields, 'id='.$id);
		
	}
	
}
?>