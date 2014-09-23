<?php
require_once('cli.php');

$prospects = db::select("SELECT id, create_date, expire_date 
	FROM contracts.prospects 
	WHERE status = 'Pending' AND expire_date = '0000-00-00'
", 'ASSOC');

foreach($prospects as $prospect){

	$expire_date = date('Y-m-d', strtotime('+1 month', strtotime($prospect['create_date'])));

	$query = "UPDATE contracts.prospects SET expire_date = '{$expire_date}' WHERE id = {$prospect['id']} LIMIT 1";
	
	db::exec($query);
	echo "Updated prospect ({$prospect['id']})\n\n";
}

?>