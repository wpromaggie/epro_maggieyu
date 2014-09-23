<?php
class survey
{
	protected $layout_id;
	protected $client;
	
	protected static $departments = array(
	    'SEO' => 'seo',
	    'Social Media' => 'sm',
	    'PPC' => 'ppc'
	);
	
	protected static $question_types = array(
	    'textbox',
	    'input',
	    'par'
	);

	function __construct($layout_id=null)
	{
		$this->layout_id = $layout_id;
	}
	
	private static function decode_id($node_id)
	{
		if(is_numeric($node_id)) return array('', $node_id);
		$node_id = db::escape($node_id);
		return explode('_', $node_id);
	}
	
	public static function get_departments()
	{
		return self::$departments;
	}
	
	public static function get_question_types()
	{
		return self::$question_types;
	}
	
	public function find_client_surveys()
	{
		$client_surveys = db::select("
			SELECT * 
			FROM surveys.client_surveys
			WHERE
				status <> 'deleted' AND
				client_id <> ''
			ORDER BY last_mod DESC
		", "ASSOC");
		return $client_surveys;
	}

	public function find_unattached_surveys()
	{
		$client_surveys = db::select("
			SELECT * 
			FROM surveys.client_surveys
			WHERE
				status <> 'deleted' AND
				client_id = ''
			ORDER BY last_mod DESC
		", "ASSOC");
		return $client_surveys;
	}
	
	public function find_client_survey_by_urlkey($urlkey="")
	{
		return db::select_row("
			SELECT * 
			FROM surveys.client_surveys
			WHERE urlkey = '$urlkey'
		", "ASSOC");
	}
	
	public function find_client_survey_by_id($id=0)
	{
		return db::select_row("
			SELECT * 
			FROM surveys.client_surveys
			WHERE id = $id
		", "ASSOC");
	}
	
	public function delete_client_survey($id)
	{
		$r = db::update('surveys.client_surveys', array('status' => 'deleted'), 'id='.$id);
		return $r;
	}
	
	public function unlock_client_survey($id)
	{
		$r = db::update('surveys.client_surveys', array('status' => 'active'), 'id='.$id);
		return $r;
	}
	
	public function build_client_survey($id)
	{
		
		$client_survey = db::select_row("
			SELECT * 
			FROM surveys.client_surveys
			WHERE id = $id
		", "ASSOC");
		$client_survey['format'] = array();
		
		//find cats
		$cats = db::select("
			SELECT * FROM surveys.client_categories
			WHERE client_survey_id = $id
			ORDER BY `order`
		", "ASSOC");
		
		foreach($cats as $cat){
			$qs = db::select("
				SELECT * FROM surveys.client_questions
				WHERE client_survey_id = $id AND client_category_id = {$cat['id']}
				ORDER BY `order`
			", "ASSOC");
			$cat['questions'] = $qs;
			$client_survey['format'][] = $cat;
		}

		//find files
		$files = db::select("
			SELECT * FROM surveys.client_files
			WHERE client_survey_id = $id
		", "ASSOC");
		$client_survey['files'] = $files;
		
		return $client_survey;
	}
	
	public function build_layout_survey($id)
	{
		$survey['format'] = array();
		//find cats
		$cats = db::select("
			SELECT * FROM surveys.categories
			WHERE layout_id = $id
			ORDER BY `order`
		", "ASSOC");
		
		foreach($cats as $cat){
			$qs = db::select("
				SELECT * FROM surveys.questions
				WHERE category_id = {$cat['id']}
				ORDER BY `order`
			", "ASSOC");
			$cat['questions'] = $qs;
			$survey['format'][] = $cat;
		}
		
		return $survey;
	}
	
	public function create_client_survey($survey)
	{
		$client_survey_id = db::insert("surveys.client_surveys", array(
		    'layout_id' => $survey['layout_id'],
		    'client_id' => $survey['client_id'],
		    'urlkey' => $survey['urlkey'],
		    'last_mod' => date(util::DATE_TIME),
		    'user_id' => $_SESSION['id']
		));
		if(empty($client_survey_id)) return false;
		
		$cats = db::select("
			SELECT * FROM surveys.categories as cat
			WHERE cat.layout_id = {$survey['layout_id']}
			ORDER BY cat.order
		", 'ASSOC');
			
		foreach($cats as $cat){
			
			$client_category_id = db::insert("surveys.client_categories", array(
			    'client_survey_id' => $client_survey_id,
			    'name' => $cat['name'],
			    'order' => $cat['order']
			));
			if(empty($client_category_id)) return false;
			
			$questions = db::select("
				SELECT * FROM surveys.questions as q
				WHERE q.category_id = {$cat['id']}
				ORDER BY q.order
			", 'ASSOC');
				
			foreach($questions as $q){
				db::insert("surveys.client_questions", array(
				    'client_survey_id' => $client_survey_id,
				    'q_text' => $q['text'],
				    'type' => $q['type'],
				    'client_category_id' => $client_category_id,
				    'order' => $q['order']
				));
			}
		}
		
		return $client_survey_id;
	}
	
	public function add_layout($layout)
	{
		if($layout['name']==""){
			return false;;
		}
		$layout['last_mod'] = date('Y-m-d H:i:s');
		return db::insert(
			'surveys.layouts',
			$layout
		);
	}
	
	public function get_all_layouts()
	{
		$layouts = db::select("
			SELECT * 
			FROM surveys.layouts
			WHERE status = 'active'
		", "ASSOC");
		return $layouts;
	}
	
	public function delete_layout($id)
	{
		db::update(
			"surveys.layouts",
			array('status' => 'deleted'),
			"id = ".$id
		);
	}
	
	public function get_layout()
	{
		$q = "
			SELECT *
			FROM surveys.layouts
			WHERE id = $this->layout_id
		";
		return db::select_row($q, 'ASSOC');
	}
	
	public function build()
	{
		$cats = db::select("
			SELECT * FROM surveys.categories as cat
			WHERE cat.layout_id = '$this->layout_id'
			ORDER BY cat.order
		", 'ASSOC');
		$survey = array();
		foreach($cats as $cat){
			$questions = db::select("
				SELECT * FROM surveys.questions as q
				WHERE q.category_id = {$cat['id']}
				ORDER BY q.order
			", 'ASSOC');
			$qs = array();
			foreach($questions as $q){
				$qs[] = array(
				    'q_id' => $q['id'],
				    'text' => $q['text'],
				    'type' => $q['type']
				);
			}
			$survey[] = array(
				'cat_id' => $cat['id'],
				'cat_name' => $cat['name'],
				'questions' => $qs
			);
		}
		return $survey;
	}
	
	public function rename($text)
	{
		$text = strip_tags($text);
		db::update("surveys.layouts", array('name' => $text), "id=".$this->layout_id);
	}
	
	public function add_q($cat_id, $order)
		{
		list($ls, $id) = self::decode_id($cat_id);
		$id = db::insert("surveys.questions", array(
			'category_id' => $id,
			'order' => $order
		));
		return $id;
	}
	
	public function add_cat($order, $layout_id)
	{
		$id = db::insert("surveys.categories", array(
			'layout_id' => $layout_id,
			'name' => 'New Category',
			'order' => $order
		));
		return $id;
	}
	
	public function update_text($node_id, $text, $type='textbox')
	{
		list($ls, $id) = self::decode_id($node_id);
		$text = str_replace("\r\n", '', $text);
		if($ls=="q"){
			db::insert_update('surveys.questions', array('id'), array(
			    'id' => $id,
			    'text' => $text,
			    'type' => $type
			));
		} else if($ls=="cat"){
			db::insert_update('surveys.categories', array('id'), array(
			    'id' => $id,
			    'name' => $text
			));
		}
	}
	
	public function delete_node($node_id)
	{
		list($ls, $id) = self::decode_id($node_id);
		if($ls=="q"){
			db::exec('
				DELETE FROM surveys.questions 
				WHERE id='.$id.' 
				LIMIT 1'
			);
		} else if($ls=="cat") {
			db::exec('
				DELETE FROM surveys.questions 
				WHERE category_id='.$id
			);
			db::exec('
				DELETE FROM surveys.categories 
				WHERE id='.$id.' 
				LIMIT 1'
			);
		}
	}
	
	public function move_node($node_id, $old_order, $new_order, $parent_id)
	{
		//db::dbg();
		list($type, $node_id) = self::decode_id($node_id);
		list($parent_type, $parent_id) = self::decode_id($parent_id);
		
		$old_order = db::escape($old_order);
		$new_order = db::escape($new_order);
		
		$table = "";
		$where_clause = "";
		if($type=="q"){
			list($c, $cat_id) = self::decode_id($cat_id);
			$table = "questions";
			$where_clause = "WHERE category_id=$parent_id";
		} else if($type=="cat"){
			$table = "categories";
			$where_clause = "WHERE layout_id=$parent_id";
		} else {
			exit;
		}
		
		$siblings = db::select("
			SELECT id 
			FROM surveys.$table 
			$where_clause
			ORDER BY `order` ASC
		");
		
		//REMOVE ORDER GAPS
		for($i=0;$i<count($siblings);$i++){
			db::update(
				"surveys.$table",
				array('order' => $i),
				"id={$siblings[$i]}"
			);
		}

		//Increment/decrement order for nodes affected by the move
		if ($old_order < $new_order) {
			db::exec("
				UPDATE surveys.$table
				SET `order` = `order` - 1
				$where_clause AND `order` > $old_order AND `order` <= $new_order
			");
		} else {
			db::exec("
				UPDATE surveys.$table
				SET `order` = `order` + 1
				$where_clause AND `order` < $old_order AND `order` >= $new_order
			");
		}

		//Set new order for moved node
		db::exec("
			UPDATE surveys.$table
			SET `order` = $new_order
			WHERE id = $node_id
		");
	}
	
	
}
?>