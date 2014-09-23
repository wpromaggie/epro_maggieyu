<?php
require_once('cli.php');

ini_set('memory_limit', '256M');

$clients = db::select("
	select id, d, plan, pay_option, cancel_date
	from sb_groups
	where
		partner = 'BM'
	order by id asc
", 'NUM', 0);

$payments = db::select("
	select group_id, paid_date, amount
	from sb_payments
	where result = 'APPROVED'
	order by paid_date asc
");

$pre_pay_clients = array();
$cl_out = "ID\tPLAN\tPAY OPTION\tSIGNUP\tCANCEL\n";
foreach ($clients as $cl_id => &$info)
{
	list($signup_date, $plan, $pay_option, $cancel) = $info;
	switch ($pay_option)
	{
		case ('buy 6 months'): $pay_option = '6_1'; break;
		default:               $pay_option = 'standard'; break;
	}
	if ($pay_option != 'standard')
	{
		$pre_pay_clients[$cl_id] = 1;
	}
	if ($cancel == '0000-00-00') $cancel = '';
	$cl_out .= "$cl_id\t$plan\t$pay_option\t$signup_date\t$cancel\n";
}

$first_client_payments = array();
$pay_out = "ID\tDATE\tAMOUNT\tTYPE\n";
for ($i = 0; list($cl_id, $d, $amount) = $payments[$i]; ++$i)
{
	if (!array_key_exists($cl_id, $clients)) continue;
	
	
	if (!array_key_exists($cl_id, $first_client_payments))
	{
		$first_client_payments[$cl_id] = 1;
		$type = (array_key_exists($cl_id, $pre_pay_clients)) ? 'Pre-Pay' : 'Order';
	}
	else
	{
		$type = (array_key_exists($cl_id, $pre_pay_clients)) ? 'Pre-Pay Recurring' : 'Recurring';
	}
	$pay_out .= "$cl_id\t$d\t$amount\t$type\n";
}

file_put_contents('tmp/sb_bm_clients.txt', $cl_out);
file_put_contents('tmp/sb_bm_payments.txt', $pay_out);

do_it('scp -i /home/swigen/.ssh/id_dsa tmp/sb_bm_clients.txt ihave823filz@nternal.wpromote.com:"/srv/Vault/Department\\ Folders/Viral\\ -\\ SMO/sb_payment_data/"');
do_it('scp -i /home/swigen/.ssh/id_dsa tmp/sb_bm_payments.txt ihave823filz@nternal.wpromote.com:"/srv/Vault/Department\\ Folders/Viral\\ -\\ SMO/sb_payment_data/"');

function do_it($cmd)
{
        echo "$cmd\n";
        passthru($cmd);
}


?>