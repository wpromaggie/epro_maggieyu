<?php
/*
tmp_auto_table_gen.php
*/

require_once('cli.php');
util::load_lib('eac','account','agency','ppc','delly','rs');
cli::run();

/**
 * autogen_rs_model
 * Parameters
 *		-d database name
 *		-l model destination
 */
class autogen_rs_model{
	private $_dest, $_db_name;

	public function run(){
		list($database,$dest) = util::list_assoc(cli::$args,'d','l');
		$this->_dest = $dest;
		$this->_db_name = $database;

		e("Creating Tables for Database {$database}!");
		$tables_contents = $this->get_all_table_desc($database);
		$this->create_file($database,$tables_contents);	
	}

	protected function get_index($db,$table){
		return db::select("show index from {$db}.{$table}");
	}

	protected function get_table_desc($db,$table){
		return db::select("describe {$db}.{$table}");
	}

	protected function get_all_table_desc($db){
		$all_tables = db::select("show tables from {$db}");
		$all_tables_desc = array();
		foreach($all_tables as $table){
			$all_tables_desc[$table] = $this->get_table_desc($db,$table);
		}
		return $all_tables_desc;
	}// end of get_all_table_desc

	// create in the final destination
	protected function create_file($db,$tables){
		// for every table
		foreach($tables as $table=>$fields){
			//print "$table\n";
			$cols = "self::\$cols = self::init_cols(\n";
			$primary_key = "self::\$primary_key = array("	;
			// for every structure 
			foreach($fields as $key=>$field){
				$cols .= "\t\t\tnew rs_col(";
				// for every field value -> array(0=>FIELD,1=>TYPE,2=>NULL,3=>KEY,4=>DEFAULT,5=>EXTRA)
				foreach($field as $k=>$v){
					if($k == 3 && $v == "PRI"){
						$primary_key .= "'" . $fields[$key][0] . "',";
					}
					$cols .= $this->create_set_table_definition($k,$v);
				}
				$cols .= "),\n";
			}
			$cols = substr($cols, 0,-2) . "\n\t\t\t);";
			$primary_key = substr($primary_key,0,-1) . ");";
			
			$filetext = $this->create_template($db,$table,$primary_key,$cols);
			$this->write_file($filetext,"{$table}.rs.php",$db);
		}
	}// end of create_file method

	protected function write_file($filetext,$filename,$db = Null){
		// create directory if it does not exist
		if(!file_exists($this->_dest."/{$db}")){
			e("Creating Directory {$this->_dest}/{$db}");
			mkdir($this->_dest."{$db}");
		}

		// create file only if it does not exist
		$new_file = "{$this->_dest}/{$this->_db_name}/{$filename}";
		if(!file_exists($new_file)){
			e("Creating New File: {$new_file}");
			$fp = fopen($new_file,"w");
			fwrite($fp,$filetext);
			fclose($fp);
		}
	}// end of write_file method

	protected function create_template($db,$table,$primary_key,$cols){
		$content  = "<?php\n\n";
		$content .= "class mod_{$db}_{$table} extends rs_object{\n";
		$content .= "\tpublic static \$db, \$cols, \$primary_key;\n\n";
		$content .= "\tpublic static function set_table_definition(){\n";				
		$content .= "\t\tself::\$db = '{$db}';\n";
		$content .= "\t\t{$primary_key}\n";
		$indexes = $this->create_index($db,$table);
		if(!empty($indexes)){
			$content .= "\t\tself::\$indexes = array(array(";
			foreach($indexes as $index){
				$content .= "'$index',";
			}
			$content = substr($content,0,-1) . "));\n\n";
		}
		$uniques = $this->create_uniques($db,$table);
		if(!empty($uniques)){
			$content .= "\t\tself::\$uniques = array(array(;";
			foreach($uniques as $unique){
				$content .= "'$unique',";
			}
			$content = substr($content,0,-1) . "));\n\n";
		}
		$content .= "\t\t{$cols}\n";
		$content .="\t}\n";//end of function set_table_definition
		$content .="}\n";//end of class
		$content .="?>\n"; 
		return $content;
	}// end create_template method

	protected function create_uniques($db,$table){
		$uniques = db::select("show index from {$db}.{$table}");
		$names = array();
		if(!empty($uniques)){
			foreach($uniques as $unique){
				//print $index[2] . "\n";
				if($unique[2] != "PRIMARY" && $index[1] != 1){
					$names[] = $index[4];
				}
			}
		}	
		return $names;
	}

	protected function create_index($db,$table){
		$indexes = db::select("show index from {$db}.{$table}");
		$names = array();
		if(!empty($indexes)){
			foreach($indexes as $index){
				//print $index[2] . "\n";
				if($index[2] != "PRIMARY" && $index[1] != 0){
					$names[] = $index[4];
				}
			}
		}	
		return $names;
	}
	protected function create_set_table_definition($k,$v){
		// FIELD	
		if($k == 0){
			$v = "'$v'";
			$format = str_pad($v, 30 - count($v)) . ",";
			return $format;
		}
		// TYPE
		elseif($k == 1){
			if(preg_match("/int/",$v)){
				$v = explode(" ",$v);
				$v = $v[0];
			}
			if(preg_match("/\(/", $v) ){
				$arg = explode("(",$v);
				$arg[0] = "'$arg[0]'";
				$arg[1] = substr($arg[1],0,-1);
				$arg[1] = "$arg[1]";					
				$format  = str_pad( $arg[0],25 - count($arg[0]) );
				$format .= "," . str_pad($arg[1],7 - count($arg[1]) ) . ",";
			}
			else{
				$v = "'$v'";
				$format = str_pad($v,25 - count($v)) . ",''    ,";
			}
			return $format;
		}
		// NULL
		elseif($k == 2){

		}
		// PRIMARY KEY
		elseif($k == 3){

		}
		// DEFAULT 
		elseif($k == 4){

		}
		// EXTRA
		else{
			return "''\t\t";
		}
	}// end of create_set_table_definition
}

?>