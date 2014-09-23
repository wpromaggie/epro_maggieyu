<?php
require_once('cli.php');

db::dbg();

//find all groups being canceled today
$cancels = db::select("
	select * from sb_groups 
	where cancel_date = ".date(DATE)
, "ASSOC");

if(!empty($cancels)){
	foreach($cancels as $cancel){
		$sql = "UPDATE sb_groups 
				SET processed = 'canceled', run_status = 'paused', edit_status = 'wpro', cancel_date = '".date(DATE)."' 
				WHERE id = ".$cancel['id'];
		db::exec($sql);
		
		$sql = "DELETE FROM sb_payments WHERE group_id = ".$cancel['id']." AND status = 'PENDING'";
		db::exec($sql);
	}
}


?>
