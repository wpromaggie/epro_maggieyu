<?php
/**
 * epro routing page
 */

/* Users */
$app->add_route('/users','action_users_users');
$app->add_route('/users/:id','action_users_users');
$app->add_route('/users/auth','action_users_auth');
$app->add_route('/users/auth/:username','action_users_auth');

/* Log */
$app->add_route('/log','action_log_test');


/* Accounting */
$app->add_route('/accounting/:cid','action_accounting_report');
$app->add_route('/accounting/report','action_accounting_report');
$app->add_route('/accounting/report/:attributes','action_accounting_report');


/* Clients */
$app->add_route('/client/:id','action_client_client_info');
$app->add_route('/clients/:cond','action_client_clients');
$app->add_route('/clients/account/:id','action_client_account_info');
$app->add_route('/clients/dept/:name','action_client_department');


/* Delly */
$app->add_route('/delly/jobs','action_delly_jobs'); //[GET]
$app->add_route('/delly/jobs/:cond','action_delly_jobs'); //[GET]
$app->add_route('/delly/job/:id','action_delly_job'); //[GET, PUT, DELETE]
$app->add_route('/delly/job','action_delly_job'); //[POST]
$app->add_route('/delly/job/status/:id','action_delly_job_status');

/* Report */
$app->add_route('/report/ppc','action_report_ppc');
$app->add_route('/report/ppc/:id','action_report_ppc');
$app->add_route('/report/ppc/run/:id','action_report_ppc_run');
$app->add_route('/report/ppc/status/:id','action_report_ppc_status');
$app->add_route('/report/ppc/json/:id','action_report_ppc_download');
$app->add_route('/report/ppc/excel/:id','action_report_ppc_download');
$app->add_route('/report/ppc/sheets','action_report_ppc_sheets');
$app->add_route('/report/ppc/sheet/tables','action_report_ppc_sheet_tables');
$app->add_route('/report/ppc/download','action_report_ppc_download');
/*
	Uncomment to debug route tree
	$app->get_route_tree();
*/

$app->set_route();
?>
