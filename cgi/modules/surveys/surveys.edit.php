<?php
class mod_surveys_edit extends mod_surveys
{
	
	private $survey;
	private $layout_id;
	
	public function pre_output()
	{
		if(empty($_GET['id'])){
			cgi::redirect('surveys');
		}
		$this->layout_id = $_GET['id'];
		$this->survey = new survey($this->layout_id);
	}
	
	public function display_index()
	{
		$survey_parts = $this->survey->build();
		$layout = $this->survey->get_layout();
		$q_types = survey::get_question_types();
	?>

		<div id="edit_display" style="display: none;">
			<h4>Edits</h4>
		</div>
		
		<div id="edit_survey">
			
			<div id="survey_title">
				<div id="survey_title_display">
					<h2 id="survey_title_text"><?php echo $layout['name']; ?></h2>
					<button type="button" id="rename_layout_btn">Rename</button>
				</div>
				<div id="survey_title_edit">
					<input id="edit_survey_title" value="<?php echo $layout['name']; ?>" />
					<button type="button" id="save_rename_layout_btn">Save</button>
					<button type="button" id="cancel_rename_layout_btn">Cancel</button>
				</div>
			</div>
			
			<?php
			foreach($survey_parts as $cat){
				echo '<div id="cat_'.$cat['cat_id'].'" class="cat">';
				echo '<div class="cat_name">'.$cat['cat_name'].'</div>';
				echo '<input class="edit_cat_name" value="'.$cat['cat_name'].'" />';
				echo '<div class="questions">';
				foreach($cat['questions'] as $q){
					//e($q);
					echo '<div id="q_'.$q['q_id'].'" class="q">';
					echo '<div class="text">'.$q['text'].'</div>';
					echo '<textarea class="edit_text">'.$q['text'].'</textarea>';
					echo self::q_type_select($q_types, $q['type']);
					echo '</div>';
				}
				echo '</div>';
				echo '</div>';
			}
			?>
			
			<input type="submit" id="add_cat" value="Add Category" />
			
		</div>

		<div id="save_cancel">
			<input type="submit" id="save_edits" value="Save" a0="action_save_edits"/>
			<input type="submit" id="cancel_edits" value="Cancel" a0="action_cancel_edits"/>
		</div>

		<div id="edit_buffer"></div>

		<div id="edit_survey_controls" style="display: none;">
			
			<div id="cat_structure">
				<div class="cat">
					<div class="cat_name">New Category</div>
					<input class="edit_cat_name" value="New Category" />
					<div class="questions"></div>
				</div>
			</div>
			
			<div id="q_structure">
				<div id="" class="q">
					<div class="text"></div>
					<textarea class="edit_text"></textarea>
					<?php echo self::q_type_select($q_types); ?>
				</div>
			</div>
			
			<!-- Controls for editing categories nodes -->
			<div class="cat_name_edit_btns">
				<button type="button" class="edit_cat_name_btn">Edit Name</button>
				<button type="button" class="save_cat_name_btn">Save</button>
				<button type="button" class="cancel_cat_name_btn">Cancel</button>
				<div class="clear"></div>
			</div>
			
			<!-- Controls for editing category position -->
			<div class="cat_move_btns">
				<button type="button" class="cat_up_btn">Move Up</button>
				<button type="button" class="cat_down_btn">Move Down</button>
				<div class="clear"></div>
			</div>
			
			<!-- Controls for editing categories nodes -->
			<div class="cat_edit">
				<button type="button" class="add_q_btn">Add Question</button>
				<button type="button" class="delete_cat_btn">Delete</button>
				<div class="clear"></div>
			</div>
			
			<!-- Controls for editing questions -->
			<div class="q_edit">
				<div class="q_edit_actions">
					<button type="button" class="edit_btn">Edit</button>
					<button type="button" class="delete_btn">Delete</button>
					<button type="button" class="save_btn" style="display: none;">Save</button>
					<button type="button" class="cancel_btn" style="display: none;">Cancel</button>
				</div>
			</div>
		</div>
	<?php
	}
	
	protected static function q_type_select($options=array(), $default='textbox')
	{
	?>
		<div class="q_edit_type">
			<select class="q_type">
				<?php
				foreach($options as $value){
					$selected = ($default==$value) ? ' selected' : '';
					echo "<option value='$value'$selected>$value</option>";
				}
				?>
			</select>
		</div>
	<?php
	}
	
	public function action_save_edits()
	{
		//db::dbg();
		$ops_array = $_POST['ops_array'];
		$ops_array_length = count($ops_array);
		for ($i=0; $i<$ops_array_length; $i++) {
			$op = $ops_array[$i];
			
			switch($op['op_type']){
				
				case 'add_q':
					//Add node, then get new and placeholder (old) node ids
					$new_node_id = $this->survey->add_q($op['cat_id'], $op['node_order']);
					$old_node_id = $op['node_id'];

					//Substitute the new node id for the placeholder id in upcoming ops
					for ($j=$i+1; $j<$ops_array_length; $j++) {
						$future_op = &$ops_array[$j];
						if ($future_op['node_id'] == $old_node_id) {
							$future_op['node_id'] = "q_$new_node_id";
						}
					}
					break;
					
				case 'add_cat':
					//Add node, then get new and placeholder (old) node ids
					$new_node_id = $this->survey->add_cat($op['node_order'], $this->layout_id);
					$old_node_id = $op['node_id'];

					//Substitute the new node id for the placeholder id in upcoming ops
					for ($j=$i+1; $j<$ops_array_length; $j++) {
						$future_op = &$ops_array[$j];
						if ($future_op['node_id'] == $old_node_id) {
							$future_op['node_id'] = "cat_$new_node_id";
						} else if($future_op['cat_id'] == $old_node_id){
							$future_op['cat_id'] = "cat_$new_node_id";
						}
					}
					break;
				
				case 'edit':
					$this->survey->update_text($op['node_id'], $op['text'], $op['node_type']);
					break;
				
				case 'move':
					if(!isset($op['parent_id'])){
						$op['parent_id'] = $this->layout_id;
					}
					$this->survey->move_node($op['node_id'], $op['start_order'], $op['stop_order'], $op['parent_id']);
					break;
				
				case 'delete':
					$this->survey->delete_node($op['node_id']);
					break;
				
				case 'rename':
					$this->survey->rename($op['text']);
					break;
				
				default:
					break;
				
			}
			
		}
	}

	public function action_cancel_edits()
	{
		//Clears post vars in case the user refreshes the page later
		cgi::redirect('surveys/edit?id='.$this->layout_id);
	}
}
?>
