<?php

require(\epro\COMMON_PATH.'sap_functions.php');
require(\epro\COMMON_PATH.'billing.php');
require(\epro\WPROPHP_PATH.'html.php');

define('RESULT_CHARGE_SUCCESS', 1);
define('RESULT_CHARGE_FAIL', 2);

class mod_sales extends module_base
{
	protected $m_name = 'sales';
	private $prospect;
	
	public function get_menu()
	{
		return new Menu(array(
			new MenuItem('Dashboard'           ,'index'),
			new MenuItem('Manage Leads'        ,'manage_leads'    ,array('role' => 'Lead Manager')),
			new MenuItem('Manage Lead URLs'    ,'url_leads'       ,array('role' => 'Lead Manager')),
			new MenuItem('Edit Sap'            ,'edit_sap'        ,array('role' => 'Leader')),
			new MenuItem('New Clients List'    ,'new_clients_list',array('role' => array('Director', 'Leader'))),
			new MenuItem('Commission Breakdown','rep_breakdown'),
			new MenuItem('Set Commissions'     ,'set_commissions' ,array('role' => 'Leader')),
			new MenuItem('Hierarchy'           ,'hierarchy'       ,array('role' => 'Leader')),
			new MenuItem('SBS Contacts'        ,'sbs_contacts')
		), 'sales');
	}
	
	public function pre_output()
	{
		util::load_lib('as');
		util::load_lib('ppc');
		if (!empty($_GET['id'])) g::$client_id = $_GET['id'];
		$this->prospect = db::select_row("
				select id, name
				from prospects
				where id = '".g::$client_id."'
		", 'ASSOC');
		$this->call_member($_POST['go']);
	}
	
	public function output()
	{
		$this->call_member(g::$p2, 'index');
	}

        protected function print_prospect_table($rep_id, $prospects, &$total_stats, $sales_rep, $start_date = null, $end_date = null){
            $stats = array ();
            $total_close_seconds = 0;

            $ml = '';
            for ($i = 0; list($id, $cl_id, $name, $status, $prospect_company, $create_date, $close_date, $revenue) = $prospects[$i]; ++$i)
            {
                $close_date_display = ($close_date == "0000-00-00") ? "" : $close_date;

                $ml .= '
                        <tr id="'.$cl_id.'"'.(($i % 2) ? ' class="even"' : '').'>
                                <td>'.($i + 1).'</td>
                                <td><a href="'.cgi::href('sales/details/?client='.$id).'">'.$name.'</a></td>
                                <td>'.$prospect_company.'</td>
                                <td>'.$status.'</td>
                                <td>'.$create_date.'</td>
                                <td>'.$close_date_display.'</td>
                                <td>'.util::format_dollars($revenue).'</td>
                        </tr>
                ';

                $stats['total']++;
                $total_stats['total']++;

                        if($status=='Signed'){

                            $stats['returned']++;
                            $total_stats['returned']++;

                        } else if($status=='Charged'){

                            $stats['returned']++;
                            $total_stats['returned']++;
                            $stats['charged']++;
                            $total_stats['charged']++;
                            $stats['revenue'] += $revenue;
                            $total_stats['revenue'] += $revenue;

                        }

                        if($close_date >= $create_date){
                            $total_close_seconds += strtotime($close_date)-strtotime($create_date);
                            $total_stats['total_close_seconds'] += strtotime($close_date)-strtotime($create_date);
                        }

		}

                $display_stats = array(
                    'total' => $stats['total'],
                    'returned' => $stats['returned']
                );
                $display_stats['percent'] = util::format_percent(util::safe_div($stats['returned'], $stats['total'])*100);
                $display_stats['revenue'] = util::format_dollars($stats['revenue']);
                $display_stats['avg_revenue'] = util::format_dollars(util::safe_div($stats['revenue'], $stats['charged']));
                $display_stats['avg_close_time'] = round(util::safe_div(util::safe_div($total_close_seconds, $stats['returned']), 86400), 2)." days";


                $st = '';
                foreach($display_stats as $label => $value){
                    $st .= '<tr>';
                    $st .= '<th>'.$label.'</th>';
                    $st .= '<td>'.$value.'</td>';
                    $st .= '</tr>';
                }
                
        if ($start_date && (user::is_admin() || $this->is_user_leader()))
        {
					$ml_dates = '
						<table class="date_range_picker">
							<tbody>
								'.cgi::date_range_picker($start_date, $end_date, array('table' => false)).'
								<tr>
									<td></td>
									<td><input type="submit" value="Set Dates" /></td>
								</tr>
							</tbody>
						</table>
					';
				}
        ?>
                <table class="prospects_list" rep_id="<?php echo $rep_id; ?>">
                    <caption><?php echo $sales_rep; ?> Prospects</caption>
                    <tr class="headers">
                            <th></th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Date Closed</th>
                            <th>Revenue</th>
                    </tr>
                    <?php echo $ml; ?>
		</table>

	<div class="lft">
                <table class="stats">
                    <caption><?php echo $sales_rep; ?> Stats</caption>
                    <?php echo $st; ?>
                </table>
                
				<?php echo $ml_dates; ?>
                <table rep_id="<?php echo $rep_id; ?>" class="client_breakdown" id="client_breakdown_<?php echo $rep_id; ?>">
								</table>
							</div>

                <div class="clear"></div>

        <?php
        }

        protected function index(){
            $order_by = isset($_POST['sort_prospects']) ? $_POST['sort_prospects'] : "";
            $sort_prospects_options = array(
                "Date Added" => "create_date",
                "Date Closed" => "close_date",
                "Revenue" => "revenue"
            );

            $sql = "SELECT id, client_id, name, status, prospect_company, create_date, close_date, revenue
                    FROM prospects
                    WHERE
                        company = '".g::$company."' &&
                        status <> 'Deleted' &&
                        user = '".$_SESSION['id']."'
                    ORDER BY ";

            if($order_by && $order_by != 'create_date'){
                $sql .= "{$order_by} DESC, ";
            }
            $sql .= "create_date DESC, name DESC";
					
            $my_prospects = db::select($sql);

            $total_stats = array ();

		list($start_date, $end_date) = util::list_assoc($_POST, 'start_date', 'end_date');
		if (empty($start_date))
		{
			$end_date = date(util::DATE);
			$start_date = substr($end_date, 0, 7).'-01';
		}
        ?>
            <!--<p><a href="<?php echo '/sales/add/'; ?>" onclick="return form_go(event);">New Prospect</a></p>-->
            <p><a href="<?php echo '/sales/add_sb_pro/'; ?>" onclick="return form_go(event);">New SB PRO Account</a></p>

            <label>Order By: </label>
            <select id="sort_prospects" name="sort_prospects">
                <?php
                    foreach($sort_prospects_options as $label => $value){
                        echo "<option value={$value}";
                        if($order_by==$value){
                            echo " selected";
                        }
                        echo ">{$label}</option>";
                    }
                ?>
            </select>

        <?php

            $this->print_prospect_table($_SESSION['id'], $my_prospects, $total_stats, 'My', $start_date, $end_date);
            
            $child_users = db::select("
							select u.id, u.username, u.realname
							from eppctwo.sales_hierarch sh, eppctwo.users u
							where
								sh.pid = '".user::$id."' &&
								sh.cid = u.id
            ", 'ASSOC');
            
            if ($this->is_user_leader() || $child_users){
							
							if ($this->is_user_leader())
							{
                $sales_list = db::select("
                    select realname, id, username
                    from users u, user_guilds ug
                    where
                        company = '".g::$company."' &&
                        u.id = ug.user_id &&
                        (ug.guild_id = 'sales' || ug.guild_id = 'sbr' || username = 'mike@wpromote.com') &&
                        id <> '".user::$id."' &&
                        username <> 'ryan@wpromote.com'
                    order by realname asc
                ", "ASSOC");
							}
							else
							{
								$sales_list = $child_users;
							}
           

                foreach($sales_list as $user){

                    $sql = "
                        SELECT id, client_id, name, status, prospect_company, create_date, close_date, revenue
                        from prospects
                        where
                                company = '".g::$company."' &&
                                status <> 'Deleted' &&
                                user = '".$user['id']."'
                        ORDER BY ";

                    if($order_by!=""){
                        $sql .= "{$order_by} DESC, ";
                    }
                    $sql .= "create_date DESC, name DESC";

                    $prospects = db::select($sql);

                    if(!empty($prospects)){

                        $this->print_prospect_table($user['id'], $prospects, $total_stats, $user['realname']."'s");

                    }
                    
               }
               
               $payments_query = '';
            }
            else
            {
							$cl_ids = db::select("select distinct client_id from eppctwo.prospects where user = '{$_SESSION['id']}'");
							$payments_query = "p.client_id in ('".implode("','", $cl_ids)."'')";
						}
						
						$payments = db::select("
							select p.client_id cl_id, p.id pid, p.amount total, p.date_attributed date, group_concat(pp.type separator '\t') part_types, group_concat(pp.amount separator '\t') part_amounts
							from eppctwo.client_payment p, eppctwo.client_payment_part pp
							where
								".(($payments_query) ? "$payments_query && " : '')."
								p.date_attributed between '$start_date' and '$end_date' &&
								pp.type in ('".implode("','", client_payment_part::get_management_part_types())."') &&
								p.id = pp.client_payment_id
							group by p.client_id, p.id
						", 'ASSOC');

            $display_stats = array(
                'total' => $total_stats['total'],
                'returned' => $total_stats['returned']
            );
            $display_stats['percent'] = util::format_percent(util::safe_div($total_stats['returned'], $total_stats['total'])*100);
            $display_stats['revenue'] = util::format_dollars($total_stats['revenue']);
            $display_stats['avg_revenue'] = util::format_dollars(util::safe_div($total_stats['revenue'], $total_stats['charged']));
            $display_stats['avg_close_time'] = round(util::safe_div(util::safe_div($total_stats['total_close_seconds'], $total_stats['returned']), 86400), 2)." days";

            $st = '';
            foreach($display_stats as $label => $value){
                $st .= '<tr>';
                $st .= '<th>'.$label.'</th>';
                $st .= '<td>'.$value.'</td>';
                $st .= '</tr>';
            }
            
        ?>

            <table class="stats">
                <caption>Total Stats</caption>
                <?php echo $st; ?>
            </table>

            <div class="clear"></div>

        <?php
					if (0)//user::is_admin() || $this->is_user_leader())
					{
						cgi::add_js_var('type_to_dept', client_payment_part::$part_types);
						cgi::add_js_var('payments', $payments);
					}
        
        }

	protected function details()
	{
		if (isset($_GET['id'])) {
			$this->prospect = db::select_row("
				select id, name
				from prospects
				where id = '" . $_GET['id'] . "'
			", 'ASSOC');
		}
		list($cl_id, $user_id, $mod_date, $url_key, $status, $prospect_company, $title, $address, $city, $state, $country, $zip, $url, $email, $phone, $fax, $revenue,
			$ppc_package, $ppc_budget, $ppc_mgmt_perc, $ppc_mgmt, $ppc_setup_fee, $ppc_contract_length, $ppc_discount, $ppc_clicks,
			$seo_package, $seo_amount, $seo_discount,
			$smo_package, $smo_amount, $smo_discount,
			$slb_package, $slb_amount, $slb_discount,
			$infographic, $ig_num, $seo_ig_amount, $seo_ig_discount,
			$wd_package, $wd_op, $wd_landing_page, $wd_landing_page_testing, $wd_num_landing_pages, $wd_deliverables, $wd_amount, $wd_discount, $wd_pay_half, $wd_first_month_amount,
			$payment_method,
			$fba_package, $fba_budget, $fba_mgmt_perc, $fba_mgmt, $fba_setup_fee, $fba_discount, $fba_clicks,
                        $ql_pro_package, $ql_pro_budget, $ql_pro_mgmt_fee, $ql_pro_setup_fee, $ql_pro_ct_fee,
			$gs_pro_package, $gs_pro_hours, $gs_pro_mgmt_fee, $gs_pro_setup_fee
		) = db::select_row("
			SELECT client_id, user, mod_date, url_key, status, prospect_company, title, address, city, state, country, zip, url, email, phone, fax, revenue,
			ppc_package, ppc_budget, ppc_mgmt_perc, ppc_mgmt, ppc_setup_fee, ppc_contract_length, ppc_discount, ppc_clicks,
			seo_package, seo_amount, seo_discount,
			smo_package, smo_amount, smo_discount,
			slb_package, slb_amount, slb_discount,
			infographic, ig_num, seo_ig_amount, seo_ig_discount,
			wd_package, wd_op, wd_landing_page, wd_landing_page_testing, wd_num_landing_pages, wd_deliverables, wd_amount, wd_discount, wd_pay_half, wd_first_month_amount,
			payment_method,
			fba_package, fba_budget, fba_mgmt_perc, fba_mgmt, fba_setup_fee, fba_discount, fba_clicks,
                        ql_pro_package, ql_pro_budget, ql_pro_mgmt_fee, ql_pro_setup_fee, ql_pro_ct_fee,
			gs_pro_package, gs_pro_hours, gs_pro_mgmt_fee, gs_pro_setup_fee
			FROM prospects
			WHERE id='".$this->prospect['id']."'
		");

		//create an absolute url address from the url if needed
		$absurl = $url;
		if(!substr_count($url, "http://www.")){
			$absurl = "http://www.".$url;
		}
		$client = array (
					array('label' => 'Company Name', 'data' => $prospect_company),
					array('label' => 'Title', 'data' => $title),
					array('label' => 'Address', 'data' => $address),
					array('label' => 'City', 'data' => $city),
					array('label' => 'State', 'data' => $state),
					array('label' => 'Country', 'data' => $country),
					array('label' => 'Zip', 'data' => $zip),
					array('label' => 'URL', 'data' => "<a href=\"".$absurl."\" target=\"_blank\">".$url."</a>"),
					array('label' => 'Email', 'data' => "<a href=\"mailto:".$email."\">".$email."</a>"),
					array('label' => 'Phone', 'data' => $phone),
					array('label' => 'Fax', 'data' => $fax),
					array('label' => 'Contract Length', 'data' => "$ppc_contract_length month")
				);
				
		$ppc_clicks = ($ppc_clicks) ? "Wpro Pays Clicks" : "Client Pays Clicks (default)";
		$ppc = array (
					array('label' => 'PPC Package', 'data' => $ppc_package),
					array('label' => 'Clicks', 'data' => $ppc_clicks),
					array('label' => 'Budget', 'data' => "$".$ppc_budget),
					array('label' => 'Management Fee %', 'data' => $ppc_mgmt_perc."%"),
					array('label' => 'Management Fee', 'data' => "$".$ppc_mgmt),
					array('label' => 'PPC Setup Fee', 'data' => "$".$ppc_setup_fee),
					array('label' => 'PPC Discount', 'data' => $ppc_discount)
				);	
		$seo = array (
					array('label' => $seo_package, 'data' => "$".$seo_amount, 'discount' => $seo_discount)
				);
		$smo = array (
					array('label' => $smo_package, 'data' => "$".$smo_amount, 'discount' => $smo_discount)
				);
		$slb = array (
					array('label' => 'SLB Package', 'data' => 'Package '.$slb_package),
					array('label' => 'Monthly Fee', 'data' => '$'.$slb_amount),
					array('label' => 'Discount', 'data' => '$'.$slb_discount)
				);
		$ig = array (
					array('label' => '# of Graphics', 'data' => $ig_num),
					array('label' => 'Total Cost', 'data' => "$".$seo_ig_amount, 'discount' => $seo_ig_discount)
				);
		$fba_clicks = ($fba_clicks) ? 'Wpro Pays Clicks' : 'Client Pays Clicks (default)';
		$fba = array (
					array('label' => 'Clicks', 'data' => $fba_clicks),
					array('label' => 'Budget', 'data' => "$".$fba_budget),
					array('label' => 'Management Fee %', 'data' => $fba_mgmt_perc."%"),
					array('label' => 'Management Fee', 'data' => "$".$fba_mgmt),
					array('label' => 'Setup Fee', 'data' => "$".$fba_setup_fee),
					array('label' => 'Discount', 'data' => $fba_discount)
				);

		if($wd_op) $wd_op = "yes"; else $wd_op = "no";
		if($wd_landing_page) $wd_landing_page = "yes"; else $wd_landing_page = "no";
		if($wd_landing_page_testing) $wd_landing_page = "yes (with testing)";
		$wd_pay_half = ($wd_pay_half) ? "yes" : "no";
		if(!empty($wd_deliverables)){
			$wd_deliverables = str_replace(":", ", ", $wd_deliverables);
		}
			
		$webdev = 	array (
						array('label' => 'Web Package', 'data' => $wd_package),
						array('label' => 'Landing Page Optimization', 'data' => $wd_op),
						array('label' => 'Landing Page Dev', 'data' => $wd_landing_page),
						array('label' => 'Number of Pages', 'data' => $wd_num_landing_pages),
						array('label' => 'Deliverables', 'data' => $wd_deliverables),
						array('label' => 'Total Amount', 'data' => "$".$wd_amount),
						array('label' => 'Discount', 'data' => "$".$wd_discount),
						array('label' => 'Split Pay', 'data' => $wd_pay_half),
						array('label' => '1st Month Amount', 'data' => "$".$wd_first_month_amount)
					);
                
                $ql_pro = array (
                        array('label' => 'QL PRO Package', 'data' => $ql_pro_package),
                        array('label' => 'Budget', 'data' => "$".$ql_pro_budget),
                        array('label' => 'Management Fee', 'data' => "$".$ql_pro_mgmt_fee),
                        array('label' => 'Setup Fee', 'data' => "$".$ql_pro_setup_fee),
                        array('label' => 'Conversion Tracking Fee', 'data' => "$".$ql_pro_ct_fee)
                );
		
		$gs_pro = array (
                        array('label' => 'GS PRO Package', 'data' => $gs_pro_package),
                        array('label' => 'Hours', 'data' => $gs_pro_hours.' hours'),
                        array('label' => 'Management Fee', 'data' => "$".$gs_pro_mgmt_fee),
                        array('label' => 'Setup Fee', 'data' => "$".$gs_pro_setup_fee)
                );
                
		$sap_url = util::get_sap_url($url_key);
                
		if ($cl_id)
		{
			$cl_links = as_lib::get_client_department_links($cl_id);
			if ($cl_links) $ml_client_link = ' - '.$cl_links;
		}
		else
		{
			$ml_client_link = '';
		}
		?>
        <div id="client_info">
						<span id="date">
							Updated: <?php echo date("F j, Y", strtotime($mod_date));?><br />
							Status: <?php echo $status; ?><br />
                                                        <?php if($status!="Charged" && (user::is_admin() || $this->is_user_leader())) echo '<input type="submit" a0="close_contract" value="Close Contract" />'; ?>
						</span>
            <h2><?php echo $this->prospect['name']; ?></h2>
            
            <a href="<?php echo $sap_url; ?>" target="_blank">SAP Link</a>
            <?php echo $ml_client_link; ?>
            <br />
            <?php
            if ($this->is_user_leader())
            {
							$this->details_reassign_select($user_id);
						}
            ?>
 			<div class="column block">
            	<table width="100%">
                  <tr>
                  	<td class="title" colspan="2">Account</td>
                  </tr>
                  <tr>
                  	<td id="account_links" class="title" colspan="2">
			<?php

			//
			// SB PRO LINKS
			//
			if($ql_pro_package || $gs_pro_package){
				if($status!="Charged"){ ?>

					<a href="/sales/edit_sb_pro/?client=<?php echo $this->prospect['id']; ?>" onclick="return form_go(event);">Edit Settings</a>
                    | <a href="<?= cgi::href('sbr/new_order?prospect_id='.$this->prospect['id']) ?>" target="_blank">SBR Order</a>
					| <a href="#" id="delete_prospect">Delete Prospect</a>

				<? } else { ?>

					<!-- <a href="/sales/edit_payment/" onclick="return form_go(event);">Edit Payment</a> -->

				<?php } ?>
					
			<?php
			//
			// NORMAL SAP LINKS
			//
			} else {
                        
                        
                                        if($status=="Pending"){ ?>

                                                <a href="/sales/edit/?client=<?php echo $this->prospect['id']; ?>" onclick="return form_go(event);">Edit Settings</a>

                                                | <a href="/sales/edit_proposal/?client=<?php echo $this->prospect['id']; ?>" onclick="return form_go(event);">Edit Proposal</a>

                                        <?php }
                                        
                                        if (!$cl_id && ($status == 'Signed' || $this->is_user_leader()))
                                        {
                                                echo '<a href="'.cgi::href('sales/create_no_charge', true).'">Create No Charge</a>';
                                        }
                                        if($payment_method=='credit'){ ?>

                                                <a href="/sales/edit_payment/" onclick="return form_go(event);">Edit Payment</a>
						
						<?php if($status!="Charged"){ ?>
						
							| <a href="/sales/charge/" onclick="return form_go(event);">Charge</a>
						
						<?php } ?>

                                        <?php } else if ($payment_method=='check') { ?>

                                                <a href="/sales/submit_check/" onclick="return form_go(event);">Deposit Check</a>

                                        <?php } else if ($payment_method=='wire') { ?>

                                                <a href="/sales/submit_wire/" onclick="return form_go(event);">Confirm Wire Transfer</a>

                                        <?php

                                        }
					
					if(user::is_admin() || $this->is_user_leader()) { ?>

						| <a href="#" id="delete_prospect">Delete Prospect</a>

					<?php } 
					
                            }
                            
                            ?>

                    </td>
                  </tr>
                  <?php 
				  	for($i=0;$i<count($client);$i++){
						echo "<tr>";
						echo "<td class=\"label\">".$client[$i]['label']."</td>";
						echo "<td class=\"data\">".$client[$i]['data']."</td>";
						echo "</tr>";
					}
				  ?>
                </table>

          <?php if($payment_method=="check"){ ?>

                    <table width="100%">
                        <tr>
                            <td class="title" colspan="2">Payment Info - Checks</td>
                        </tr>
                        <tr>
                            <td class="label"><b>Check Number</b></td>
                            <td class="data"><b>Amount</b></td>
                        </tr>
                         <?php
                            $results = db::select("SELECT * FROM checks WHERE foreign_id = ".$this->prospect['id'], 'ASSOC');
                            for ($i = 0, $ci = count($results); $i < $ci; ++$i) {
                            	$row = $results[$i];
                                    echo "<tr>";
                                    echo "<td class=\"label\">".$row['check_number']."</td>";
                                    echo "<td class=\"data\">".$row['amount']."</td>";
                                    echo "</tr>";
                            }
                          ?>
                    </table>

          <?php }
            if($status == "Charged" && (user::is_admin() || $this->is_user_leader())){
                
                ?>

                <table width="100%">
                    <tr>
                        <td class="title" colspan="2">Update Revenue</td>
                    </tr>
                    <tr>
                        <td class="label">Revenue</td>
                        <td class="data">$<input type="text" name="revenue" value="<?php echo $revenue; ?>"/></td>
                    </tr>
                    <tr>
                        <td class="label"></td>
                        <td class="data"><input type="submit" a0="update_revenue" value="Update" /></td>
                    </tr>
                </table>

                <?php
            }
          ?>
            </div>

			<div class="column">
				
				<?php if ($ppc_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">PPC Management</td>
						</tr>
						<?php 
						for($i=0;$i<count($ppc);$i++){
							echo "<tr>";
							echo "<td class=\"label\">".$ppc[$i]['label']."</td>";
							echo "<td class=\"data\">".$ppc[$i]['data']."</td>";
							echo "</tr>";
						}
						?>
					</table>  
				</div>
				<?php } ?>
				
				<?php if ($seo_package || $smo_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">SEO/SMO Packages</td>
						</tr>
						<?php 
						for($i=0;$i<count($seo);$i++){
							//check is an seo package exists
							if($seo[$i]['label'] != 0){
								echo "<tr>";
								echo "<td class=\"label\">SEO Package ".$seo[$i]['label']."</td>";
								echo "<td class=\"data\">".$seo[$i]['data'];
								if($seo[$i]['discount']){
									echo " ($".$seo[$i]['discount']." discount)";
								}
								echo "</td>";
								echo "</tr>";
							}
							//check is an smo package exists
							if($smo[$i]['label'] != 0){
								echo "<tr>";
								echo "<td class=\"label\">SMO Package ".$smo[$i]['label']."</td>";
								echo "<td class=\"data\">".$smo[$i]['data'];
								if($smo[$i]['discount']){
									echo " ($".$smo[$i]['discount']." discount)";
								}
								echo "</td>";
								echo "</tr>";
							}
						}
						?>
					</table>  
				</div>
				<?php } ?>
				
				<?php if ($slb_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">Social Link Building</td>
						</tr>
						<?php 
						for($i=0;$i<count($slb);$i++){
							echo "<tr>";
							echo "<td class=\"label\">".$slb[$i]['label']."</td>";
							echo "<td class=\"data\">".$slb[$i]['data']."</td>";
							echo "</tr>";
						}
						?>
					</table>  
				</div>
				<?php } ?>

				<?php if ($infographic) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">Infographic</td>
						</tr>
						<?php
						for($i=0;$i<count($ig);$i++){
							//check is an seo package exists

								echo "<tr>";
								echo "<td class=\"label\">".$ig[$i]['label']."</td>";
								echo "<td class=\"data\">".$ig[$i]['data'];
								if($ig[$i]['discount']){
									echo " ($".$ig[$i]['discount']." discount)";
								}
								echo "</td>";
								echo "</tr>";

						}
						?>
					</table>  
				</div>
				<?php } ?>

				<?php if ($fba_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">Facebook Advertising</td>
						</tr>
						<?php 
						for($i=0;$i<count($fba);$i++){
							echo "<tr>";
							echo "<td class=\"label\">".$fba[$i]['label']."</td>";
							echo "<td class=\"data\">".$fba[$i]['data']."</td>";
							echo "</tr>";
						}
						?>
					</table>  
				</div>
				<?php } ?>

				<?php if ($wd_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">Landing Page Development</td>
						</tr>
						<?php
						for($i=0;$i<count($webdev);$i++){
							echo "<tr>";
							echo "<td class=\"label\">".$webdev[$i]['label']."</td>";
							echo "<td class=\"data\">".$webdev[$i]['data']."</td>";
							echo "</tr>";
						}
						?>
					</table>
				</div>
				<?php } ?>
                                
                                <?php if ($ql_pro_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">QuickList PRO</td>
						</tr>
						<?php 
						for($i=0;$i<count($ql_pro);$i++){
							echo "<tr>";
							echo "<td class=\"label\">".$ql_pro[$i]['label']."</td>";
							echo "<td class=\"data\">".$ql_pro[$i]['data']."</td>";
							echo "</tr>";
						}
						?>
					</table>  
				</div>
				<?php } ?>
				
				<?php if ($gs_pro_package) { ?>
				<div class="block">
					<table width="100%">
						<tr>
						<td class="title" colspan="2">GoSEO PRO</td>
						</tr>
						<?php 
						for($i=0;$i<count($gs_pro);$i++){
							echo "<tr>";
							echo "<td class=\"label\">".$gs_pro[$i]['label']."</td>";
							echo "<td class=\"data\">".$gs_pro[$i]['data']."</td>";
							echo "</tr>";
						}
						?>
					</table>  
				</div>
				<?php } ?>
			</div>
            
            <div class="clear"></div>
        </div>
		<?php
	}
	
	public function action_reassign_prospect()
	{
		$new_user = $_POST['reassign'];
		list($name) = db::select_row("select realname from eppctwo.users where id = '$new_user'");
		
		$r = db::update("eppctwo.prospects", array('user' => $new_user), "id='".$this->prospect['id']."'");
		if ($r)
		{
			feedback::add_success_msg("Prospect reassigned to {$name}");
		}
		else
		{
			feedback::add_error_msg("Error reassigning prospect");
		}
	}
	
	private function details_reassign_select($cur_user)
	{
		$users = db::select("
			select u.id, u.realname
			from eppctwo.users u, eppctwo.user_guilds g
			where g.guild_id = 'sales' && g.user_id = u.id
		");
		array_unshift($users, array('0' => ''));
		?>
		<div>
			<label>Re-Assign To</label>
			<?php echo cgi::html_select('reassign', $users, $cur_user); ?>
			<input type="submit" a0="action_reassign_prospect" value="Submit" />
		</div>
		<?php
	}
	
        protected function close_contract(){
                db::exec("
                    update prospects
                    set
                        status = 'Charged',
                        payment_method = 'manual',
                        close_date = '".date('Y-m-d')."'
                    where id = '".g::$client_id."'"
                );
        }

	protected function edit_prospect_submit()
	{
                
                //cgi::print_r($_POST);
                //exit();
                
		$d = $_POST;
		@array_walk($d, array('db', 'escape'));
		$this->set_common_edit_fields($d);
		
		$common_cols = array(
			'parent_id',
			'mod_date', 'url_key', 'name', 'prospect_company', 'title', 'address', 'city', 'state', 'country', 'zip', 'url', 'email', 'phone', 'fax',
			'ppc_package', 'ppc_budget', 'ppc_mgmt_perc', 'ppc_mgmt', 'ppc_setup_fee', 'ppc_contract_length', 'ppc_discount', 'ppc_clicks',
			'seo_package', 'seo_package_amount', 'seo_blog', 'seo_blog_amount', 'seo_blog_mgmt', 'seo_blog_mgmt_amount', 'seo_amount', 'seo_monthly_amount', 'seo_discount',
			'smo_package', 'smo_amount', 'smo_discount',
			'slb_package', 'slb_amount', 'slb_discount',
			'infographic', 'ig_num', 'seo_ig_amount', 'seo_ig_discount',
			'wd_package', 'wd_op', 'wd_landing_page', 'wd_landing_page_testing',
			'wd_num_landing_pages', 'wd_amount', 'wd_discount', 'wd_pay_half', 'wd_first_month_amount', 'wd_deliverables',
			'fba_package', 'fba_budget', 'fba_mgmt_perc', 'fba_mgmt', 'fba_setup_fee', 'fba_discount', 'fba_clicks',
                        'ql_pro_package', 'ql_pro_budget', 'ql_pro_mgmt_fee', 'ql_pro_setup_fee', 'ql_pro_ct_fee',
			'gs_pro_package','gs_pro_hours', 'gs_pro_mgmt_fee', 'gs_pro_setup_fee'
		);

		$numeric_cols = array(
			'ppc_budget' => 'PPC Budget',
			'ppc_mgmt_perc' => 'PPC Management Fee %',
			'ppc_mgmt' => 'PPC Management Fee',
			'ppc_setup_fee' => 'PPC Setup Fee',
			'ppc_contract_length' => 'Contract Length',
			'ppc_discount' => 'PPC Discount',
			'seo_amount' => 'SEO Amount',
			'seo_discount' => 'SEO Discount',
			'smo_amount' => 'SMO Amount',
			'smo_discount' => 'SMO Discount',
			'slb_amount' => 'Social Link Building Amount',
			'slb_discount' => 'Social Link Building Discount',
			'ig_num' => 'Number of Infographics',
			'seo_ig_amount' => 'Infographic Amount',
			'seo_ig_discount' => 'Infographic Discount',
			'wd_num_landing_pages' => 'Web Dev Pages',
			'wd_amount' => 'Web Dev Amount',
			'wd_discount' => 'Web Dev Discount',
			'fba_budget' => 'FB Ads Budget',
			'fba_mgmt_perc' => 'FB Ads Management Fee %',
			'fba_mgmt' => 'FB Ads Management Fee',
			'fba_setup_fee' => 'FB Ads Setup Fee',
			'fba_discount' => 'FB Ads Discount'
		);

		//check required fields
		if ($d['name'] == "" && !isset($d['parent_selected'])) {
			//the name can be blank if we just selected a parent
			// output error
			feedback::add_error_msg("Submit Error: Contact Name is a required field.");
			return false;
		}

		//check url_key
		$sql = "SELECT id FROM prospects WHERE url_key = '{$d['url_key']}'";
		if (@$this->prospect['id']) {
			$sql .= " && id <> " . $this->prospect['id'];
		}
		if (db::count_select($sql)) {
			feedback::add_error_msg("Duplicate SAP URL found. Please rename the URL for this contract.");
			return false;
		}

		//check numeric fields
		foreach ($numeric_cols as $col => $col_name) {
			if (!is_numeric($d[$col]) && $d[$col] != "") {
				feedback::add_error_msg("Submit Error: {$col_name} has an invalid value. Must be numeric characters only.");
				return false;
			}
		}
		//db::dbg();
		// if we have a prospect id, it was an edit
		if (@$this->prospect['id']) $this->update_prospect($d, $common_cols);
		else $this->new_prospect($d, $common_cols);
                
                //
                //for ql pro clients, we need to check for billing info as well
                //
                if(isset($d['cc_number'])){
                        
                        //save cc info
                        $cc_info = array(
                                'name' => $d['cc_name'],
                                'country' => $d['cc_country'],
                                'zip' => $d['cc_zip'],
                                'cc_number' => $d['cc_number'],
                                'cc_type' => $d['cc_type'],
                                'cc_exp_month' => $d['cc_exp_month'],
                                'cc_exp_year' => $d['cc_exp_year'],
                                'cc_code' => $d['cc_code']
                                //'cc_number_print' => str_repeat('*', (strlen($d['cc_number']) -4)).substr($d['cc_number'], -4)
                        );
                        
                        // check if previous payment info exists for this prospect
                        $cc_id = db::select_one("SELECT id FROM ccs WHERE foreign_table = 'prospects' && foreign_id='{$this->prospect['id']}'");
                        
                        //make sure the payment method is set to credit
                        db::exec('UPDATE prospects SET payment_method = "credit" WHERE id="'.$this->prospect['id'].'"');
                        
                        if (empty($cc_id)){
                                billing::cc_new('prospects', $this->prospect['id'], $cc_info);
                        } else {
                                billing::cc_update($cc_id, $cc_info);
                        }
                        
                }
                
	}
	
	protected function edit_payment_submit()
	{
		$d = $_POST;
		@array_walk($d, 'escape_single_quotes');
		if($d['payment_options']=="credit"){
			// store post values needed for the cc table
			$cc_info = array(
				'name' => $d['cc_name'],
				'country' => $d['cc_country'],
				'zip' => $d['cc_zip'],
				'cc_number' => $d['cc_number'],
				'cc_type' => $d['cc_type'],
				'cc_exp_month' => $d['cc_exp_month'],
				'cc_exp_year' => $d['cc_exp_year'],
				'cc_code' => $d['cc_code']
			);
			db::exec("
				update prospects
				set payment_method='credit'
				where id='".$this->prospect['id']."'
			");
			billing::cc_update($_POST['pid'], $cc_info);
		} else if($d['payment_options']=="check"){
			// store post values needed for the check table
			$check_info = array(
				'name' => $d['check_name'],
				'phone' => $d['phone'],
				'account_type' => $d['account_type'],
				'account_number' => $d['account_number'],
				'routing_number' => $d['routing_number'],
				'check_number' => $d['check_number'],
				'drivers_license' => $d['drivers_license'],
				'drivers_license_state' => $d['drivers_license_state']
			);
			db::exec("
				update prospects
				set payment_method='check'
				where id='".$this->prospect['id']."'
			");
			billing::check_update($_POST['pid'], $check_info);
		}
		
		feedback::add_success_msg('Prospect Payment Updated');
	}
	
	protected function set_common_edit_fields(&$d)
	{
		// convert spaces to dashes, then make sure everything is either a word character or a dash
		$d['url_key'] = preg_replace("/[^\w-]/", '', str_replace(' ', '-', $d['url_key']));
		
		// single checkboxes
		$checkboxes = array('wd_op', 'wd_landing_page');
		foreach ($checkboxes as $checkbox)
		{
			if (!array_key_exists($checkbox, $d)) $d[$checkbox] = 0;
		}

		// group checkboxes
		$groups = array('wd_deliverables');
		foreach($groups as $g)
		{
			if (array_key_exists($g, $d)) $d[$g] = implode(":", $d[$g]);
		}

		$d['mod_date'] = date(util::DATE);
	}

        protected function parent_client_select(){
            //grab client information from the parent
            $parent_fields = array("name", "prospect_company", "title",
                "address", "city", "state", "zip", "country", "url",
                "email", "phone", "fax");
            $sql = "SELECT ".implode(", ", $parent_fields)."
                    FROM prospects
                    WHERE id = {$_POST['parent_id']}";
            $parent = db::select_row($sql, "ASSOC");

            //replace current client information with the parents info
            foreach($parent_fields as $key){
               $_POST[$key] = $parent[$key];
            }

            //let the submit function know that a parent was selected
            //so that some required fields can remian blank
            $_POST['parent_selected'] = 1;
            $this->edit_prospect_submit();
        }
	
	protected function update_prospect(&$d, &$cols)
	{
		$vals = '';

		for ($i = 0, $count = count($cols); $i < $count; ++$i)
		{
			$col = $cols[$i];
			$val = $d[$col];
			
			// make sure numerical columns are numeric
			if ($this->is_numeric_col($col) && !is_numeric($val)) $val = 0;
			
			if ($i > 0) $vals .= ', ';
			$vals .= "$col='".db::escape($val)."'";
		}
		
		db::exec("
			update prospects
			set $vals
			where id='".$this->prospect['id']."'
		");
		
		feedback::add_success_msg('Prospect Updated');
	}
	
	protected function new_prospect(&$d, &$cols)
	{
		// new specific columns
		$cols = array_merge(array('company', 'user', 'status', 'create_date'), $cols);
		
		// set stuff that wasn't posted
		$d['company'] = 1;
		$d['user'] = $_SESSION['id'];
		$d['create_date'] = date(util::DATE);
		$d['status'] = 'Pending';
		
		$insert_data = array();
		for ($i = 0, $count = count($cols); $i < $count; ++$i)
		{
			$col = $cols[$i];
			$val = $d[$col];

			if (is_array($val)) {
				$val = implode(":", $val);
			}

			// make sure numerical columns are numeric
			if ($this->is_numeric_col($col) && !is_numeric($val)) $val = 0;
			$insert_data[$col] = $val;
		}
		$id = db::insert("eppctwo.prospects", $insert_data);

		$this->prospect = db::select_row("
			select id, name
			from prospects
			where id = '$id'
		", 'ASSOC');

		feedback::add_success_msg('New Prospect Created');

	}
	
	private function is_numeric_col($col)
	{
		return (
			(strpos($col, '_amount') !== false) ||
			(strpos($col, '_package') !== false) ||
			($col == 'wd_num_landing_pages' || $col == 'wd_landing_page_testing' || $col == 'wd_discount') ||
			($col == 'ppc_budget' || $col == 'ppc_mgmt' || $col == 'ppc_discount' || $col == 'ppc_clicks' || $col == 'ppc_setup_fee') || 
			($col == 'seo_discount' || $col == 'smo_discount' || $col == 'slb_discount') ||
			($col == 'fba_budget' || $col == 'fba_mgmt' || $col == 'fba_discount' || $col == 'fba_setup_fee')
		);
	}
	
	private function set_charge_credit_ml(&$ml)
	{
		$cc_id = billing::get_prospect_cc_id($this->prospect['id']);
		$cc_info = billing::cc_get_display($cc_id);
		list($name, $country, $zip, $cc_number, $cc_type) = util::list_assoc($cc_info, 'name', 'country', 'zip', 'cc_number', 'cc_type');
		
		$ml = '
			<tr>
				<td>Billing Name</td>
				<td>'.$name.'</td>
			</tr>
			<tr>
				<td>Billing Country</td>
				<td>'.$country.'</td>
			</tr>
			<tr>
				<td>Billing Zip</td>
				<td>'.$zip.'</td>
			</tr>
			<tr>
				<td>Card Type</td>
				<td>'.$cc_type.'</td>
			</tr>
			<tr>
				<td>Card Number</td>
				<td>'.$cc_number.'</td>
			</tr>
		';
	}
	
	private function set_department_charges_ml(&$ml, $department_charges, $info)
	{
		$total = 0;

		$total_discount = 0;
		$discount_keys = array();

		$ml = '';
		foreach ($department_charges as $k => $v)
		{
			$amount = $info[$k];
			

			// check for discount;
			list($department) = explode('_', $k);
			$discount_key = $department.'_discount';
			$discount = $info[$discount_key];
			if ($discount > 0 && !array_key_exists($discount_key, $discount_keys))
			{
				// there are multiple ppc amounts, make sure we only count the discount once
				$discount_keys[$discount_key] = 1;
				$total_discount += $discount;
			}
		
			$total += $amount;
			$ml .= '
				<tr>
					<td>'.$v.'</td>
					<td><input type="text" name="'.$k.'" value="'.$amount.'" is_amount_part=1 onkeyup="prospect_charge_amount_change();" /></td>
				</tr>
			';
		}

		if ($total_discount > 0)
		{
			$ml .= '
				<tr>
					<td>Discounts</td>
					<td id="discount">'.util::format_dollars($total_discount).'</td>
				</tr>
			';
			$total -= $total_discount;
		}

		$ml .= '
			<tr>
				<td>Total</td>
				<td id="total_amount">'.util::format_dollars($total).'</td>
			</tr>
		';
	}
	
	private function set_department_charges(&$department_charges)
	{
		$department_charges = array(
			'ppc_mgmt' => 'PPC Mgmt',
			'ppc_setup_fee' => 'PPC Setup',
			'ppc_budget' => 'PPC Budget',
			'smo_amount' => 'SMO',
			'seo_amount' => 'SEO',
			//'seo_ig_amount' => 'Infographic',
			'wd_amount' => 'Web Dev',
                        //'ql_pro_budget' => 'QL PRO', //client pays to google
                        'ql_pro_mgmt_fee' => 'QL PRO Mgmt',
                        'ql_pro_setup_fee' => 'QL PRO Setup',
                        'ql_pro_ct_fee' => 'QL PRO Tracking',
			'gs_pro_mgmt_fee' => 'GS PRO Mgmt',
                        'gs_pro_setup_fee' => 'GS PRO Setup'
		);
	}
	
	protected function charge()
	{
		if ($this->go_result == RESULT_CHARGE_SUCCESS)
		{
			$this->print_header_links();
			return;
		}
		$this->set_department_charges($department_charges);
                
                //print_r($department_charges);

		// get discounts
		$discounts = array();
		$disc_except = array('ql', 'gs');
		foreach ($department_charges as $k => $v)
		{
			list($department) = explode('_', $k);
                        if(in_array($department, $disc_except)) continue;
			$discount_key = $department.'_discount';
			if (!array_key_exists($discount_key, $discounts)) $discounts[$discount_key] = 1;
		}
                
                //db::dbg();

		$prospect_info = db::select_row("
			select payment_method, ".implode(', ', array_keys($department_charges)).", ".implode(', ', array_keys($discounts))."
			from prospects
			where id='".$this->prospect['id']."'
		", 'ASSOC');

                //cgi::print_r($prospect_info);
                //exit();
		
		$this->set_department_charges_ml($ml_department_charges, $department_charges, $prospect_info);
		
		$payment_method = $prospect_info['payment_method'];
		$func = 'set_charge_'.$payment_method.'_ml';
		$this->$func($ml_charge_info);
		
		?>
		<h1>Charge - <?php echo $this->prospect['name']; ?></h1>
		<?php $this->print_header_links(); ?>
		<table>
			<tr>
				<td>Payment Method</td>
				<td><?php echo $payment_method; ?></td>
			</tr>
			<?php echo $ml_charge_info; ?>
			<?php echo $ml_department_charges; ?>
			<?php if (dbg::is_on()) { ?>
			<tr>
				<td></td>
				<td><input type="checkbox" name="do_not_charge" value="1" /> Do not charge</td>
			</tr>
			<?php } ?>
			<tr>
				<td></td>
				<td><input type="submit" a0="charge_go" value="Submit" /></td>
			</tr>
		</table>
		<?php
	}

	protected function submit_check()
	{
		?>
		<h1>Submit Check Info - <?php echo $this->prospect['name']; ?></h1>
		<?php $this->print_header_links(); ?>
		<?php
		if ($_POST['a0'] != 'action_submit_check')
		{
			echo '<input type="submit" a0="action_submit_check" value="Check Deposited, Create Client In E2" />';
		}
	}
	
	protected function submit_wire()
	{
		?>
		<h1>Submit Check Info - <?php echo $this->prospect['name']; ?></h1>
		<?php $this->print_header_links(); ?>
		<?php
		if ($_POST['a0'] != 'action_submit_wire')
		{
			echo '<input type="submit" a0="action_submit_wire" value="Wire Transfer Confirmed, Create Client In E2" />';
		}
	}
	
	protected function action_submit_check()
	{
		$this->set_department_charges($department_charges);
		$prospect_info = db::select_row("
			select payment_method, ".implode(', ', array_keys($department_charges))."
			from eppctwo.prospects
			where id='".$this->prospect['id']."'
		", 'ASSOC');
		$this->set_payment_parts_and_total($prospect_info, $payment_parts, $total);
		$this->create_client_from_propect($payment_parts, 'check', 0, true, $total);
	}
	
	protected function action_submit_wire()
	{
		$this->set_department_charges($department_charges);
		$prospect_info = db::select_row("
			select payment_method, ".implode(', ', array_keys($department_charges))."
			from eppctwo.prospects
			where id='".$this->prospect['id']."'
		", 'ASSOC');
		$this->set_payment_parts_and_total($prospect_info, $payment_parts, $total);
		$this->create_client_from_propect($payment_parts, 'wire', 0, true, $total);
	}

	protected function action_create_no_charge()
	{
		$this->set_department_charges($department_charges);
		$prospect_info = db::select_row("
			select payment_method, ".implode(', ', array_keys($department_charges))."
			from eppctwo.prospects
			where id='".$this->prospect['id']."'
		", 'ASSOC');
		$this->set_payment_parts_and_total($prospect_info, $payment_parts, $total);
		if ($prospect_info['payment_method'] == 'credit')
		{
			$payment_method = 'cc';
			$pay_id = billing::get_prospect_cc_id($this->prospect['id']);
		}
		else
		{
			$payment_method = $prospect_info['payment_method'];
			$pay_id = 0;
		}
		$this->create_client_from_propect($payment_parts, $payment_method, $pay_id, false);
	}
	
	public function create_no_charge()
	{
		$this->print_header_links();
	}
	
	protected function charge_show_success()
	{
		feedback::add_success_msg("Client Charged");
	}
	
	protected function sap_payment_type_to_client_payment_type($type)
	{
		switch ($type)
		{
			case ('ppc_mgmt'): return 'PPC Management';
			case ('ppc_setup_fee'): return 'PPC Management';
			case ('ppc_budget'): return 'PPC Budget';
			case ('smo_amount'): return 'SMO Management';
			case ('seo_amount'): return 'SEO Management';
			case ('wd_amount'): return 'WebDev';
				
			//Need to break these payemnt parts up 
                        case ('ql_pro_budget'): return 'QuickList Pro';
                        case ('ql_pro_mgmt_fee'): return 'QuickList Pro';
                        case ('ql_pro_setup_fee'): return 'QuickList Pro';
			case ('ql_pro_ct_fee'): return 'QuickList Pro';
				
			case('gs_pro_mgmt_fee'): return 'GoSEO Pro';
			case('gs_pro_setup_fee'): return 'GoSEO Pro';
		}
	}
	
	private function set_payment_parts_and_total($payment_data, &$payment_parts, &$total)
	{
		// set payment parts, total amount
		$total = 0;
		$this->set_department_charges($department_charges);
		$payment_parts = client_payment_part::new_array(array('type' => 'ASSOC'));
		foreach ($department_charges as $k => $display_text)
		{
			$amount = $payment_data[$k];
			if ($amount > 0)
			{
				$payment_type = $this->sap_payment_type_to_client_payment_type($k);
				if ($payment_parts->key_exists($payment_type))
				{
					$payment_part = &$payment_parts->i($payment_type);
					$payment_part->amount += $amount;
				}
				else
				{
					$payment_part = new client_payment_part(array(
						'user_id' => user::$id,
						'type' => $payment_type,
						'amount' => $amount
					));
					$payment_parts->set($payment_type, $payment_part);
				}
				$total += $amount;
			}
		}
		//e($payment_parts);
	}
	
	protected function charge_go()
	{
		$this->set_payment_parts_and_total($_POST, $payment_parts, $total);
		
		$cc_id = billing::get_prospect_cc_id($this->prospect['id']);
		if (empty($_POST['do_not_charge']) && !billing::charge($cc_id, $total))
		{
			$this->go_result = RESULT_CHARGE_FAIL;
			feedback::add_error_msg('Charge Declined: '.billing::get_error(false));
			return;
		}
		else
		{
			$this->go_result = RESULT_CHARGE_SUCCESS;
			$this->create_client_from_propect($payment_parts, 'cc', $cc_id, true, $total);
		}
	}
	
	private function create_client_from_propect($payment_parts, $payment_method, $payment_id = 0, $do_charge = true, $total = 0)
	{
		$pinfo = db::select_row("select * from eppctwo.prospects where id='".$this->prospect['id']."'", 'ASSOC');
		
		//check for goseo pro account
		if($pinfo['gs_pro_package']){
			util::load_lib('sbs');
			
			$payment_amount = $pinfo['gs_pro_mgmt_fee'] + $pinfo['gs_pro_setup_fee'];
			
			$gs_info = array(
			    'prospect_id' => $this->prospect['id'],
			    'plan' => 'Pro',
			    'partner' => '',
			    'source' => '',
			    'subid' => '',
			    'ip' => '',
			    'browser' => '',
			    'referer' => '',
			    'comments' => '',
			    'contract_length' => $pinfo['ppc_contract_length'],
			    'name' => $pinfo['name'],
			    'email' => $pinfo['email'],
			    'phone' => $pinfo['phone'],
			    'url' => $pinfo['url'],
			    'dept' => 'gs',
			    'pay_option' => 'standard',
			    'sales_rep' => $pinfo['user'],
			    'setup_fee' => $payment_amount, //this is unique for orders coming through contracts, look into changing
			    'cc_type' => '',
			    'cc_number_text' => '',
			    'cc_exp_month' => '',
			    'cc_exp_year' => '',
			    'jk_security_code' => '',
			    'cc_country' => '',
			    'cc_zip' => '',
			    'cc_name' => ''
			);
			// sbs/gs does not exist anymore
			$r = util::e2_post('sbs/gs', 'new_order', $gs_info);
			$client = new clients();
			$client->id = $r['client_id'];
			$do_charge = true;
		} else {
		
			// create client
			$client_info = array(
				'company' => 1,
				'name' => $pinfo['prospect_company'],
				'status' => 'On'
			);
			if ($payment_method == 'cc')
			{
				$client_info['cc_id'] = $payment_id;
			}
			$client = new clients($client_info);
			$client->put();
			util::set_client_external_id($client->id);

			// even if we
			if ($payment_method == 'cc')
			{
				// update cc with client
				db::update("eppctwo.ccs", array(
					'foreign_table' => 'clients',
					'foreign_id' => $client->id
				), "id = $payment_id");
				$payment_fid = billing::$order_id;
			}
			else
			{
				$payment_fid = '';
			}

			if ($do_charge)
			{
				// insert payment and payment parts
				$payment = new client_payment(array(
					'client_id' => $client->id,
					'user_id' => user::$id,
					'pay_id' => $payment_id,
					'pay_method' => $payment_method,
					'fid' => $payment_fid,
					'date_received' => date(util::DATE, $_SERVER['REQUEST_TIME']),
					'date_attributed' => date(util::DATE, $_SERVER['REQUEST_TIME']),
					'amount' => $total,
					'notes' => 'SAP Payment'
				));
				$payment->put();

				$payment_parts->client_payment_id = $payment->id;
				$payment_parts->client_id = $client->id;
				$payment_parts->put();
			}

			// create contact
			$contact = new contacts(array(
				'client_id' => $client->id,
				'name' => $pinfo['name'],
				'title' => $pinfo['title'],
				'email' => $pinfo['email'],
				'phone' => $pinfo['phone'],
				'fax' => $pinfo['fax'],
				'street' => $pinfo['address'],
				'city' => $pinfo['city'],
				'state' => $pinfo['state'],
				'zip' => $pinfo['zip'],
				'country' => $pinfo['country']
			));
			$contact->put();

			// create billing contact
			$billing_contact = new contacts(array(
				'client_id' => $client->id,
				'name' => $pinfo['prospect_company'],
				'title' => $pinfo['title'],
				'email' => $pinfo['email'],
				'phone' => $pinfo['phone'],
				'fax' => $pinfo['fax'],
				'street' => $pinfo['address'],
				'city' => $pinfo['city'],
				'state' => $pinfo['state'],
				'zip' => $pinfo['zip'],
				'country' => $pinfo['country']
			));
			$billing_contact->put();

			$departments = array_unique($payment_parts->get_deparment());

			foreach ($departments as $department)
			{
				$department_data = array(
					'company' => 1,
					'client' => $client->id,
					'billing_contact_id' => $billing_contact->id,
					'url' => $pinfo['url']
				);

				//special sbs logic
				if($department=='sbs'){
					if($pinfo['ql_pro_package']){
						$department = 'ppc';
						$department_data['budget'] = $pinfo['ql_pro_budget'];
						$department_data['who_pays_clicks'] = 'Client';
					}
				}

				db::insert("eppctwo.clients_{$department}", $department_data);
			}
		
		}
		
		db::exec("
			update eppctwo.prospects
			set
				status = 'Charged',
				client_id = '{$client->id}'
			where id = '".$this->prospect['id']."'
		");
		
		db::insert_update("eppctwo.sales_client_info", array('client_id'), array(
			'client_id' => $client->id,
			'sales_rep' => db::select_one("select user from eppctwo.prospects where id = '".$this->prospect['id']."'")
		));
		
		feedback::add_success_msg((($do_charge) ? 'Prospect Payment Processed, ' : '').'Client Created');
	}

        protected function update_revenue(){
            $revenue = $_POST['revenue'];
            db::exec("UPDATE prospects set revenue = {$revenue} WHERE id = {$this->prospect['id']}");
        }

        protected function delete_prospect()
	{
		db::exec("UPDATE prospects set status = 'Deleted' WHERE id = {$this->prospect['id']}");
                $location  = (IS_LOCAL) ? "/e2/sales/" : "/sales/";
                header("Location: {$location}");
	}
	
	protected function edit()
	{
		$this->show_prospect_form();
	}
        
        protected function edit_sb_pro()
	{
		$this->show_sb_pro_form();
	}
	
	protected function edit_payment()
	{
		$this->show_payment_form();
	}
	
	protected function edit_proposal()
	{
		$this->show_proposal_form();
	}
	
	protected function add($info = null)
	{
        //this is ancient, lets start removing it...
        echo 'phasing out these ancient ways...';
        return;

		// if we're here and we have a prospect id, that means form was just submitted, show prospect home instead
		if (@$this->prospect['id']) $this->index();
		else $this->show_prospect_form();
	}
  
	public function pre_output_add_ql_pro()
	{
		if (!user::has_module_observer_access() && !user::has_role('QL Pro'))
		{
			cgi::redirect('');
		}
	}
        
        protected function add_sb_pro($info = null)
	{
		// if we're here and we have a prospect id, that means form was just submitted, show prospect home instead
		if (@$this->prospect['id']) $this->index();
		else $this->show_sb_pro_form();
	}
	
	protected function edit_proposal_submit()
	{ 
                //grab the default text
		$default = db::select("select * from sap_text where client_id='0'", 'ASSOC', 's1', 's2', 's3', 'list_order');

                //grab the customized text for existing items
		$custom = db::select("select * from sap_text where client_id='".$this->prospect['id']."'", 'ASSOC', 's1', 's2', 's3', 'list_order');
		
                
                //grab the new list items
                $custom_add = db::select("select * from sap_text_client_add where client_id='".$this->prospect['id']."'", 'ASSOC', 's1', 's2', 's3', 'list_order');

		$d = $_POST;
		@array_walk($d, 'escape_single_quotes');
		
		$set_default_keys = array();
		
		$sap_vars = array();

                $order   = array("\r\n", "\n", "\r");
                $replace = '<br />';

		foreach($d as $key => $value){
			if(strpos($key,"sap_var") === 0){
				$key = substr_replace($key,"",0,8);
				$sap_vars[$key]=str_replace($order, $replace, $value);
			}
		}
		
		// loop through our sap text areas
		foreach($sap_vars as $key => $value){
			$make_default = false;
			
			if(!empty($_POST['default'])){
				if(in_array($key,$_POST['default'])){
					$make_default = true;
				}
			}
		
			$index = explode("-",$key);  // array of indexes

			
			if($make_default){
				// check for changes made back to default by checkbox
				// delete old text
				$sql  = "DELETE FROM sap_text ";
				$sql .= "WHERE s1='".$index[0]."' AND ";
				$sql .= "s2='".$index[1]."' AND ";
				$sql .= "s3='".$index[2]."' AND ";
				$sql .= "list_order=".$index[3]." AND ";
				$sql .= "client_id='".$this->prospect['id']."'";
				if(db::exec($sql)){
					$msg = "make default selected in section: ".$index[0];
					feedback::add_success_msg($msg);
				}
			}
			else if(!empty($custom_add[$index[0]][$index[1]][$index[2]][$index[3]])){
                            
                            // check for changes made to the text in custom_add
                            if($custom_add[$index[0]][$index[1]][$index[2]][$index[3]]['text']!=(stripslashes($value))){
                                // changes were made -- update the custom add table
                                // check to see if the custom add text was deleted, and delete the entry with the client_id
                                if(stripslashes($value)==""){
                                        // the custom_add text was deleted
                                        // delete the entry with the client id
                                        $sql  = "DELETE FROM sap_text_client_add ";
                                        $sql .= "WHERE s1='".$index[0]."' AND ";
                                        $sql .= "s2='".$index[1]."' AND ";
                                        $sql .= "s3='".$index[2]."' AND ";
                                        $sql .= "list_order='".$index[3]."' AND ";
                                        $sql .= "client_id='".$this->prospect['id']."'";
                                        if(db::exec($sql)){
                                                $msg = "returned a field to default in section: ".$index[0];
                                                feedback::add_success_msg($msg);
                                        }
                                } else {
                                        // new custom info was entered
                                        // update text in database with client id
                                        $sql  = "UPDATE sap_text_client_add ";
                                        $sql .= "SET text = '".db::escape($value)."' ";
                                        $sql .= "WHERE ";
                                        $sql .= "(s1='".$index[0]."' && s2='".$index[1]."' && s3='".$index[2]."' && list_order='".$index[3]."' && client_id='".$this->prospect['id']."')";
                                        if(db::exec($sql)){
                                                $msg = "Added custom item in section: ".$index[0];
                                                feedback::add_success_msg($msg);
                                        }
                                }
                            }
                        }
			// this key exists in the custom array
			else if(!empty($custom[$index[0]][$index[1]][$index[2]][$index[3]])){
				// check for changes made to the text in custom
				if($custom[$index[0]][$index[1]][$index[2]][$index[3]]['text']!=(stripslashes($value))){
					// changes were made -- update the custom table
					// check to see if the custom text was changed back to the default by hand, and delete the entry with the client_id
					if((stripslashes($value)==$default[$index[0]][$index[1]][$index[2]][$index[3]]['text'])){
						// the custom text was changed back to the default
						// delete the entry with the client id
						$sql  = "DELETE FROM sap_text ";
						$sql .= "WHERE s1='".$index[0]."' AND ";
						$sql .= "s2='".$index[1]."' AND ";
						$sql .= "s3='".$index[2]."' AND ";
						$sql .= "list_order=".$index[3]." AND ";
						$sql .= "client_id='".$this->prospect['id']."'";
						if(db::exec($sql)){
							$msg = "returned a field to default in section: ".$index[0];
							feedback::add_success_msg($msg);
						}
					} else {
						// new custom info was entered
						// update text in database with client id
						$sql  = "UPDATE sap_text ";
						$sql .= "SET text = '".db::escape($value)."' ";
						$sql .= "WHERE ";
						$sql .= "(s1='".$index[0]."' && s2='".$index[1]."' && s3='".$index[2]."' && list_order=".$index[3]." && client_id='".$this->prospect['id']."')";
						if(db::exec($sql)){
							$msg = "Updated item in section: ".$index[0];
							feedback::add_success_msg($msg);
						}
					}
				}
			
			} else if(!empty($default[$index[0]][$index[1]][$index[2]][$index[3]])){
				// this key was not found in custom, time to check default
				// this key exists in default
				// check for changes made
				
				if($default[$index[0]][$index[1]][$index[2]][$index[3]]['text']!=(stripslashes($value))){
					$insert_data = array(
						's1' => $index[0],
						's2' => $index[1],
						's3' => $index[2],
						'list_order' => $index[3],
						'text' => $value,
						'client_id' => $this->prospect['id']
					);
					if (db::insert("eppctwo.sap_text", $insert_data)) {
						$msg = "New field added to section: ".$index[0];
						feedback::add_success_msg($msg);
					}
				}
					
			} else {
				// this key did not exist in default or custom so we insert it into the custom_add table
				$insert_data = array(
					's1' => $index[0],
					's2' => $index[1],
					's3' => $index[2],
					'list_order' => $index[3],
					'text' => $value,
					'client_id' => $this->prospect['id']
				);
				if (db::insert("eppctwo.sap_text_client_add", $insert_data)) {
					$msg = "New field added to section: ".$index[0];
					feedback::add_success_msg($msg);
				}
			}
	
		} // end foreach

		
		if(!feedback::is_feedback()){
			feedback::add_success_msg('no changes found');
		}
	}
	
	protected function show_proposal_form()
	{
		$order_details = @db::select_row("
			select *
			from prospects
			where id='{$this->prospect['id']}'
		", "ASSOC");
		add_derived_vars($order_details);

		//print_r($order_details);

		list($wpro_email, $wpro_name, $wpro_phone_ext) = @db::select_row("
			select username, realname, phone_ext
			from users
			where id='{$order_details[user]}'
		");
		$absurl = $order_details[url];
		if(!substr_count($order_details[url], "http://www.")){
			$absurl = "http://www.".$order_details[url];
		}


		$default = db::select("select * from sap_text where client_id='0' ORDER BY list_order", 'ASSOC', 's1', 's2', 's3', 'list_order');
		$custom_text = db::select("select * from sap_text where client_id='".$this->prospect['id']."' ORDER BY list_order", 'ASSOC', 's1', 's2', 's3', 'list_order');
                $custom_add = db::select("select * from sap_text_client_add where client_id='".$this->prospect['id']."' ORDER BY list_order", 'ASSOC', 's1', 's2', 's3', 'list_order');
		
		$updated_text = $default;
		
		foreach($custom_text as $key1 => $temp1){
			foreach($temp1 as $key2 => $temp2){
				foreach($temp2 as $key3 => $temp3){
					foreach($temp3 as $key4 => $temp4){
						$updated_text[$key1][$key2][$key3][$key4]['text'] = $custom_text[$key1][$key2][$key3][$key4]['text'];
						
					}
				}
			}
		}

		foreach($custom_add as $key1 => $temp1){
			foreach($temp1 as $key2 => $temp2){
				foreach($temp2 as $key3 => $temp3){
					foreach($temp3 as $key4 => $temp4){
						$updated_text[$key1][$key2][$key3][$key4]['text'] = $custom_add[$key1][$key2][$key3][$key4]['text'];

					}
				}
			}
		}



		//parse number
		$phone1 =  substr($wpro_phone_ext,0,3);
		$phone2 =  substr($wpro_phone_ext,3,4);


	?>
    <div id="proposal_form">
     
    <div id="info" class="box">
    	<h2>Client & Wpro Information</h2>
        <div id="wpro_info">
        	<h3>Wpromote Contact Information:</h3>
           	<ul>
            	<li><span class="label">Contact:</span> <?php echo $wpro_name; ?></li>
                <li><span class="label">Phone:</span> 310.<?php echo $phone1; ?>.<?php echo $phone2;?> (Toll Free:  866.977.8668 x.<?php echo $phone2;?>)</li>
				<li><span class="label">Fax:</span> 310.356.3228</li>
				<li><span class="label">Email:</span> <a href="mailto:<?php echo $wpro_email;?>"><?php echo $wpro_email;?></a></li>
            </ul>
        </div>
        <div id="client_info">
        	<h3>Client Contact Information:</h3>
        	<ul>
            	<li><span class="label">Contact:</span> <?php echo $order_details[name];?></li>
				<li><span class="label">Phone:</span> <?php echo $order_details[phone];?></li>
				<li><span class="label">Website:</span> <?php echo $order_details[url];?></li>
				<li><span class="label">Email:</span> <a href="mailto:<?php echo $order_details[email];?>"><?php echo $order_details[email];?></a></li>
            </ul>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="box">
	
    	<h2>Statement of Confidentiality</h2>
		
        <?php create_par($updated_text, $order_details, 'confidentiality', '', ''); ?>
		
    </div>
    
    <div id="terms">
	
    	<h2>General Contract Terms</h2>
        <?php $order_details['formatted_date'] = date("F j, Y", strtotime($order_details[mod_date])); ?>
        <?php create_par($updated_text, $order_details, 'terms', 'general', ''); ?>
        
        <ol>
        	<li class="terms_heading">Overview</li>
			
            <?php
			
				$list_style = 'id="overview_list"';
				
				create_list($updated_text, $order_details, 'terms', 'overview', '', $list_style);
                                

            
			?>
			
            <li class="terms_heading">Campaign Goals</li>
			
				<?php create_list($updated_text, $order_details, 'terms', 'goals', ''); ?>
				
                <p>Customer Support is available via phone, email or online chat Monday through Friday, between the hours of 8:30 PST and 5:30 PST, excluding national holidays.</p>
            	 
                 <li class="terms_heading">Deliverables</li>
                 
                 <?php
                	switch($order_details[ppc_package]){
					
						case 1: //express_ppc
                        
							echo "<h3>Express PPC</h3>";
						 
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'express_ppc_dashes');
							
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'express_ppc'); 
							
							break;
								
						case 2: //premium_ppc 
                        
							//echo "<h3>Premium PPC Management</h3>";
							
							create_title($updated_text, $order_details, 'terms', 'deliverables', 'ppc_title');
							
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'premium_ppc_dashes');
                        
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'premium_ppc');
                 
							break;
							
					}
					
					if($order_details['seo_package']){
						echo "<div id=\"seo_package\">";
							
							$seo_package = "";
							if($order_details['seo_package']==5){
								$seo_package = "_local";
								create_title($updated_text, $order_details, 'terms', 'deliverables', 'seo_local_title');
							} else {
								$seo_package = $order_details['seo_package'];
								create_title($updated_text, $order_details, 'terms', 'deliverables', 'seo_title');
							}
							
							
							$list_style = 'id="seo_package_contents" class="dashes"'; 
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'seo'.$seo_package, $list_style, 'list_small');
							
							create_par($updated_text, $order_details, 'terms', 'deliverables', 'seo'.$seo_package.'_includes');
							
							echo "<div class=\"clear\"></div>";
							echo "<div class=\"monthly_breakdown\">";
							
							for($i=1;$i<=6;$i++){
								echo "<div class=\"month_details\" id=\"month{$i}\">";
								echo "	<h4>Month {$i}</h4>";
										create_list($updated_text, $order_details, 'terms', 'deliverables', 'seo'.$seo_package.'_month'.$i,'', 'list_small');
								echo "</div>";
								if(!($i%2)){
									echo "<div class=\"clear\"></div>";
								}
							}
							echo "</div>";
						echo "</div>";
					}
					
					if($order_details['smo_package']){
						echo "<div id=\"social_media_package\">";
							
							//echo "<h3>Social Media Optimization Package ".$order_details[smo_package]."</h3>";
							create_title($updated_text, $order_details, 'terms', 'deliverables', 'smo_title');
							
							
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'smo'.$order_details[smo_package],'','list_small');
							echo "<div class=\"clear\"></div>";
							
							//get_table('smo'.$order_details[seo_package]);
							
							/*
							
							echo "<div class=\"monthly_breakdown\">";
							
							for($i=1;$i<=2;$i++){
								echo "<div class=\"month_details\" id=\"month{$i}\">";
								echo "	<h4>Month {$i}";
								if($i==2) echo " & Ongoing";
								echo"</h4>";
										create_list($updated_text, $order_details, 'terms', 'deliverables', 'smo_month'.$i,'','list_small');
								echo "</div>";
								if(!($i%2)){
									echo "<div class=\"clear\"></div>";
								}
							}
							echo "</div>";
							* 
							* */
							
						echo "</div>";
						echo "<div class=\"clear\"></div>";
					}
					
					if($order_details['infographic']){
						echo "<div id=\"infographic_package\">";
							
							//echo "<h3>Infographic Creation & Distribution</h3>";
							create_title($updated_text, $order_details, 'terms', 'deliverables', 'infographic_title');
							
							echo "<div class='details'>";
								create_list($updated_text, $order_details, 'terms', 'deliverables', 'infographic','','list_small');
							echo "</div>";
					}

					if ($order_details['fba_package']) {
						create_title($updated_text, $order_details, 'terms', 'deliverables', 'fba_title');
						create_list($updated_text, $order_details, 'terms', 'deliverables', 'fba_dashes');
						create_list($updated_text, $order_details, 'terms', 'deliverables', 'fba');
					}
					
					if ($order_details['slb_package']) {
						$pkg_num = $order_details['slb_package'];
						create_title($updated_text, $order_details, 'terms', 'deliverables', 'slb_p'.$pkg_num.'_title');
						create_list($updated_text, $order_details, 'terms', 'deliverables', 'slb_p'.$pkg_num, 'class="dashes"', 'list_small');
					}
					?>
                    
                     <?php
					
					switch($order_details[wd_package]){
					
						case 1:
							create_title($updated_text, $order_details, 'terms', 'deliverables', 'wd_title_basic');
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'basic_website_service');
							break;
						case 2:
							create_title($updated_text, $order_details, 'terms', 'deliverables', 'wd_title_midrange');
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'midrange_website_service');
							break;
						case 3:
							create_title($updated_text, $order_details, 'terms', 'deliverables', 'wd_title_top');
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'top_website_service');
							break;
					}
				
                                        if($order_details['wd_deliverables']) {
                                                $wd_delivs = explode(":", $order_details['wd_deliverables']);
						echo "<h4>Included Website Deliverables</h4><br />";
                                                echo "<ul>";
						foreach($wd_delivs as $wd){
                                                    echo "<li>{$wd}</li>";
                                                }
                                                echo "</ul>";
                                                
                                                echo "<div class='clear'></div>";

                                                echo "<h4>Excluded Website Deliverables</h4><br />";
                                                echo "<ul>";
                                                $wd_delivs_diff = excluded_webdev_options($wd_delivs);
						foreach($wd_delivs_diff as $wdd){
                                                    echo "<li>{$wdd}</li>";
                                                }
                                                echo "</ul>";

					}
			   
			   		if($order_details[wd_landing_page]){
						if($order_details[wd_landing_page_testing]){
							echo "<h3>Landing Page Design & Testing</h3>";
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'landing_page_design_testing');
						} else {
							echo "<h3>Landing Page Design</h3>";
							create_list($updated_text, $order_details, 'terms', 'deliverables', 'landing_page_design');
						}
					}
			   
			   ?>
               
            
			<li class="terms_heading">Service Fees</li>
			
				<div id="service_fees">
				
				<?php
				
					switch($order_details['ppc_package']){
						
						case 1: //express ppc
						
							echo "<h3>Express PPC Management - Monthly Service Fee</h3>";
							
							create_list($updated_text, $order_details, 'terms', 'fees', 'express_ppc');
							
							break;
							
						case 2: //premium ppc
						
							echo "<h3>Premium PPC Management  - Monthly Service Fee</h3>";
							
							create_par($updated_text, $order_details, 'terms', 'fees', 'premium_ppc_info');
							
							create_list($updated_text, $order_details, 'terms', 'fees', 'premium_ppc');
							
							break;
							
					}
				
					if($order_details['seo_package']) {
						
						echo "<h4>SEO</h4>";
						
						create_par($updated_text, $order_details, 'terms', 'fees', 'seo');
						
					}
					
					if($order_details['infographic']) {
						
						echo "<h4>Infographic Creation & Distribution</h4>";
						
						create_par($updated_text, $order_details, 'terms', 'fees', 'infographic');
						
					}
					
					if ($order_details['fba_package']) {
						create_title($updated_text, $order_details, 'terms', 'fees', 'fba_title');
						create_par($updated_text, $order_details, 'terms', 'fees', 'fba_info');
						create_list($updated_text, $order_details, 'terms', 'fees', 'fba');
					}
					
					if ($order_details['slb_package']) {
						create_title($updated_text, $order_details, 'terms', 'fees', 'slb_title');
						create_par($updated_text, $order_details, 'terms', 'fees', 'slb');
					}
					
					if($order_details['wd_package']) {
						
						echo "<h4>Web Development Package</h4>";
						
						switch($order_details['wd_package']){
							
							case 1:
								create_par($updated_text, $order_details, 'terms', 'fees', 'basic_website_service');
								break;
								
							case 2:
								create_par($updated_text, $order_details, 'terms', 'fees', 'midrange_website_service');
								break;
								
							case 3:
								create_par($updated_text, $order_details, 'terms', 'fees', 'top_website_service');
								break;
						}
						
					}

                                        

                                        if($order_details[wd_pay_half]) {

                                            echo "<h4>Web Development Split-Pay</h4>";

                                            create_par($updated_text, $order_details, 'terms', 'fees', 'splitpay');

                                        }
					
					if($order_details['wd_landing_page']) {
						
						if($order_details['wd_landing_page_testing']){
						
							echo "<h4>Landing Page Design & Testing</h4>";
						
							create_par($updated_text, $order_details, 'terms', 'fees', 'landing_page_design_testing');
						
						} else {
						
							echo "<h4>Landing Page Design</h4>";
						
							create_par($updated_text, $order_details, 'terms', 'fees', 'landing_page_design');
							
						}
						
					}
					
					if($order_details['wd_op']) {
						
						echo "<h4>Landing Page Optimization</h4>";
						
						create_par($updated_text, $order_details, 'terms', 'fees', 'landing_page_op');
						
					}

                                        
					
					if($order_details['smo_package']) {
						
						echo "<h4>SMO</h4>";
						
						create_par($updated_text, $order_details, 'terms', 'fees', 'smo');
						
					}
					
				?>
				
				</div> <!-- service fees -->
				
				
				<?php
				
					//Client Feedback Timeliness
					
					if($order_details['wd_amount']){ 	
						
						echo '<li class="terms_heading">Client Feedback Timeliness</li>';
                
						echo '<div id="webdev_terms">';
						
							create_par($updated_text, $order_details, 'terms', 'feedback', 'web_dev');
						
							create_list($updated_text, $order_details, 'terms', 'feedback', 'web_dev_list');
                    
						echo '</div>';
    
					}
				?>
				
				
				<li class="terms_heading">Contract Terms</li>
				
				<div id="contract_terms">
				
					<?php //create_list($updated_text, $order_details, 'terms', 'contract', '','','list_big'); ?>
					<?php
						$contract_type = "";
						if($order_details['seo_package']) {
							$contract_type = "seo";
						} else if($order_details['ppc_package']){
							$contract_type = "ppc";
						}
						create_contract_terms_list($updated_text, $order_details, $contract_type);
					?>
				
				</div>
				
				<div id="order_details">
				
					<?php output_order_table($order_details); ?>
				
				</div>

                                <div id="agree">
                                    <?php
                                        if($order_details['ppc_package']==1){
                                            create_par($updated_text, $order_details, 'agree', 'express', '');
                                        } else if ($order_details['ppc_package']==2){
                                            create_par($updated_text, $order_details, 'agree', 'premium', '');
                                        } else {
                                            create_par($updated_text, $order_details, 'agree', '', '');
                                        }
                                    ?>
                                </div>
				
        </ol>
        
    </div>
    
    
    </div>
 
        
    <?php
	}
        
        protected function show_sb_pro_form()
	{
                // try getting client info
		// if we don't get it, it's new. if we do, it's an edit
		$d = @db::select_row("SELECT * FROM prospects WHERE id='".$this->prospect['id']."'", 'ASSOC');
		$is_new = (empty($d));
		$is_edit = !$is_new;
		
                $d['country'] = (@array_key_exists('country', $d)) ?  $d['country'] : 'US';
		
                $cc = array();
		$cc_number = $cc_code = "";
                if($is_edit){
                        $cc = @db::select_row("SELECT * FROM ccs WHERE foreign_table='prospects' AND foreign_id='{$this->prospect['id']}'", 'ASSOC');
                        //cgi::print_r($cc);
                        $cc_info = billing::cc_get_display($cc['id']);
			$cc_number = $cc_info['cc_number'];
			$cc_code = $cc_info['cc_code'];
                }
		
		
                $cc['country'] = (@array_key_exists('country', $cc)) ?  $cc['country'] : 'US';
                
                $user = @db::select_row("select * from users where id='".$d['user']."'", 'ASSOC');
		
		$states = db::select("
			SELECT short value, text caption
			FROM states
			ORDER BY caption ASC
		", 'ASSOC');
		
		$countries = db::select("
			SELECT a2 value, country caption
			FROM countries
			ORDER BY caption ASC
		", 'ASSOC');
                
                $ccs = array(
                        array('value' => 'amex', 'caption' => 'AmEx'),
                        array('value' => 'disc', 'caption' => 'Discovery'),
                        array('value' => 'mc', 'caption' => 'MasterCard'),
                        array('value' => 'visa', 'caption' => 'Visa')
                );
                
                // month select for cc exp date (1999 is an arbitrary year)
                $month_options = array();
                for ($i = 1; $i <= 12; ++$i)
                {
                        $month_num = str_pad($i, 2, '0', STR_PAD_LEFT);
                        $month_options[] = array('value' => $month_num, 'caption' => $month_num.' - '.date('M', strtotime('1999-'.$month_num.'-01')));
                }

                $day_options = array();
                for ($i = 1; $i <= 31; ++$i){
                    $day_num = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $day_options[] = array('value' => $day_num, 'caption' => $day_num);
                }

                // year select for cc exp date
                $year_options = array();
                for ($i = 0, $cur_year = date('Y'); $i < 10; ++$i, ++$cur_year)
                $year_options[] = array('value' => $cur_year, 'caption' => $cur_year);
                
                $ql_packages = array(
			array('value' => '', 'caption' => '----'),
                        array('value' => '1', 'caption' => 'Tier 1')
                );
		
		$seo_packages = array(
		        array('value' => '', 'caption' => '----'),
                        array('value' => '1', 'caption' => 'Tier 1'),
                        array('value' => '2', 'caption' => 'Tier 2'),
			array('value' => '3', 'caption' => 'Tier 3')
                );
                
                //
                //HTML START 
                //
        ?>
                <h2><?php echo (($is_new) ? "Add New QL PRO Client" : 'Edit QL PRO Client Info'); ?></h2>
		<?php $this->print_header_links(); ?>
                
                <fieldset>
                        
                        <legend>Sales Person & Client Information</legend>
                        <div class="row">
                                <div>
                                        <label>Sales Person: </label>
                                        <span style="font-weight:bold;"><?php echo $user['realname']; ?></span>
                                </div>
                        </div>
                
                        <div class="row">
                                <div>
                                        <label>Contact Name</label>
                                        <input type="text" name="name" id="name" class="required" focus_me=1 value="<?php echo $d['name']; ?>" />
                                </div>
                                <div>
                                        <label>Company Name</label>
                                        <input type="text" name="prospect_company" value="<?php echo $d['prospect_company']; ?>" />
                                </div>
                                        <div>
                                        <label>Title</label>
                                        <input type="text" name="title" value="<?php echo $d['title']; ?>" />
                                </div>
                        </div>
                        
                        <div class="row">
                                <div>
                                        <label>SAP URL</label>
                                        <input type="text" name="url_key" class="required" dirty=0 value="<?php echo $d['url_key']; ?>" />
                                </div>
                                <div>
                                        <label>Web Address</label>
                                        <input type="text" name="url" value="<?php echo $d['url']; ?>" />
                                </div>
                                <div>
                                        <label>Email</label>
                                        <input type="text" name="email" value="<?php echo $d['email']; ?>" />
                                </div>
                                <div>
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="<?php echo $d['phone']; ?>" />
                                </div>
                                <div>
                                        <label>Fax</label>
                                        <input type="text" name="fax" value="<?php echo $d['fax']; ?>" />
                                </div>
                        </div>
                        
                        <div class="row">
                                <div>
                                        <label>Address</label>
                                        <input type="text" name="address" value="<?php echo $d['address']; ?>" />
                                </div>
                                <div>
                                        <label>City</label>
                                        <input type="text" name="city" value="<?php echo $d['city']; ?>" />
                                </div>
                                <div>
                                        <label>State</label>
                                        <?php echo html::select(html::options($states, $d['state']), array('name' => 'state')); ?>
                                </div>
                                <div>
                                        <label>Country</label>
                                        <?php echo html::select(html::options($countries, $d['country']), array('name' => 'country')); ?>
                                </div>
                        </div>
			
			<div class="row">
				<label>Contract Length (in months)</label>
				<input type="text" class="num" name="ppc_contract_length" value="<?php echo $d['ppc_contract_length']; ?>" />
			</div>
                        
                </fieldset>
                
                <fieldset>
                        <legend>QuickList PRO</legend>
                        <div class="row">
                                <div>
                                        <label>QuickList PRO Package</label>
                                        <?php echo html::select(html::options($ql_packages, $d['ql_pro_package']), array('name' => 'ql_pro_package', 'id' => 'ql_pro_package')); ?>
                                </div>
                                <div>
                                        <label>Budget</label><input type="text" id="ql_pro_budget" name="ql_pro_budget" class="num" value="<?php echo $d['ql_pro_budget']; ?>" />
                                </div>
                                <div>
                                        <label>Management Fee</label>
                                        <input type="text" id="ql_pro_mgmt_fee" name="ql_pro_mgmt_fee" class="num ql_fee" value="<?php echo $d['ql_pro_mgmt_fee']; ?>" />
                                </div>
                                <div>
                                        <label>Setup Fee</label>
                                        <input type="text" name="ql_pro_setup_fee" class="num ql_fee" value="<?php echo $d['ql_pro_setup_fee']; ?>" />
                                </div>
                                <div>
                                        <label>Conversion Tracking Setup Fee</label>
                                        <input type="text" name="ql_pro_ct_fee" id="ql_pro_ct_fee" class="num" value="<?php echo $d['ql_pro_ct_fee']; ?>" />
                                </div>
                        </div>
                </fieldset>
		
		<fieldset>
                        <legend>GoSEO PRO</legend>
                        <div class="row">
                                <div>
                                        <label>GoSEO PRO Package</label>
                                        <?php echo html::select(html::options($seo_packages, $d['gs_pro_package']), array('name' => 'gs_pro_package', 'id' => 'gs_pro_package')); ?>
                                </div>
                                <div>
                                        <label>Hours of Work</label>
					<input type="text" id="gs_pro_hours" name="gs_pro_hours" class="num" value="<?php echo $d['gs_pro_hours']; ?>" />
                                </div>
                                <div>
                                        <label>Management Fee</label>
                                        <input type="text" id="gs_pro_mgmt_fee" name="gs_pro_mgmt_fee" class="num" value="<?php echo $d['gs_pro_mgmt_fee']; ?>" />
                                </div>
                                <div>
                                        <label>Setup Fee</label>
                                        <input type="text" id="gs_pro_setup_fee" name="gs_pro_setup_fee" class="num" value="<?php echo $d['gs_pro_setup_fee']; ?>" />
                                </div>
                        </div>
                </fieldset>
                
                <fieldset>
                        
                        <legend>Billing Information</legend>
                        
                        <input name="payment_method" type="hidden" value="credit" />
                        
                        <div class="row">
                                <div>
                                        <label>Credit Card Type</label>
                                        <?php echo html::select(html::options($ccs, $cc['cc_type']), array('name' => 'cc_type')); ?>
                                </div>
                                <div>
                                        <label>Name On Card</label>
                                        <input type="text" name="cc_name" value="<?php echo $cc['name'] ?>" />
                                </div>
                                <div>
                                        <label>Zipcode</label>
                                        <input name="cc_zip" type="text" value="<?php echo $cc['zip'] ?>" />
                                </div>
                                <div>
                                        <label>Country</label>
                                        <?php echo html::select(html::options($countries, $cc['country']), array('name' => 'cc_country')); ?>
                                </div>
                        </div>
                        
                        <div class="row">
                                <div>
                                        <label>Credit Card Number</label>
                                        <input name="cc_number" type="text" maxlength=16 value="<?php echo $cc_number ?>" />
                                        
                                </div>
                                <div>
                                        <label>VCC</label>
                                        <input name="cc_code" type="text" value="<?php echo $cc_code ?>" />
                                </div>
                                <div>
                                        <label>Expiration Date</label>
                                        
                                        <select name="cc_exp_month">
                                        <?php 
                                                for($i=0;$i<count($month_options);$i++){
                                                        echo "<option value=\"".$month_options[$i]['value']."\" ";
                                                        if($month_options[$i]['value']==$cc['cc_exp_month']){
                                                                echo "selected ";
                                                        }
                                                        echo ">".$month_options[$i]['caption']." </option>";
                                                } 
                                        ?>
                                        </select>
                                        
                                        <select name="cc_exp_year">
                                        <?php 
                                                for($i=0;$i<count($year_options);$i++){
                                                        echo "<option value=\"".$year_options[$i]['value']."\" ";
                                                        if($year_options[$i]['value']==$cc['cc_exp_year']){
                                                                echo "selected ";
                                                        }
                                                        echo ">".$year_options[$i]['caption']." </option>";
                                                } 
					?>
                                        </select>
                                        
                                </div>
                        </div>
                        
                </fieldset>
                
                <div class="clear"></div>
                
                <input type="submit" value="Submit" onclick="return prospectFieldCheck();" /></td>
                
        <?php
        }
	
	protected function show_prospect_form()
	{
		// try getting client info
		// if we don't get it, it's new. if we do, it's an edit
		$d = @db::select_row("select * from prospects where id='".$this->prospect['id']."'", 'ASSOC');
		$is_new = (empty($d));
		$is_edit = !$is_new;

		//if there was a form error, lets populate the form with the post variables
		foreach($_POST as $key => $value){
			if(empty($d[$key])) {
				if ($key == "wd_deliverables") {
					$d[$key] = implode(':', $value);
				} else {
					$d[$key] = $value;
				}
			}
		}
		
		$user = @db::select_row("select * from users where id='".$d['user']."'", 'ASSOC');
		
		$states = db::select("
			select short value, text caption
			from states
			order by caption asc
		", 'ASSOC');
		
		$countries = db::select("
			select a2 value, country caption
			from countries
			order by caption asc
		", 'ASSOC');
		
		$country = (@array_key_exists('country', $d)) ? $d['country'] : 'US';

		//grab a list of all the current prospects to set a parent id
		//$prospects = db::select("select prospect_company id, prospect_company from prospects ORDER BY prospect_company ASC", 'ASSOC');
		$prospects = db::select("select id, prospect_company from prospects GROUP BY prospect_company", 'ASSOC');


		// we'll be getting these from database later
		$ppc_packages_tmp = array(
			array('value' => '1', 'caption' => 'Express PPC'),
			array('value' => '2', 'caption' => 'Premium PPC MGMT'),
		);
		$ppc_packages = array_merge(array(array('value' => 0, 'caption' => '')), $ppc_packages_tmp);
		
		$seo_packages_tmp = array(
			array('value' => 5, 'caption' => 'Local/Small Business Package'),
			array('value' => 1, 'caption' => 'Package 1'),
			array('value' => 2, 'caption' => 'Package 2'),
			array('value' => 3, 'caption' => 'Package 3')
		);
		$seo_packages = array_merge(array(array('value' => 0, 'caption' => '')), $seo_packages_tmp);
		
		$smo_packages_tmp = array(
			array('value' => 1, 'caption' => 'Package 1'),
			array('value' => 2, 'caption' => 'Package 2'),
			array('value' => 3, 'caption' => 'Package 3')
		);
		$smo_packages = array_merge(array(array('value' => 0, 'caption' => '')), $smo_packages_tmp);
		
		$slb_packages_tmp =  array(
			array('value' => 1, 'caption' => 'Package 1'),
			array('value' => 2, 'caption' => 'Package 2')
		);
		$slb_packages = array_merge(array(array('value' => 0, 'caption' => '')), $slb_packages_tmp);
		
		$wd_packages_tmp = array(
			array('value' => 1, 'caption' => 'Package 1'),
			array('value' => 2, 'caption' => 'Package 2'),
			array('value' => 3, 'caption' => 'Package 3')
		);
		$wd_packages = array_merge(array(array('value' => 0, 'caption' => '')), $wd_packages_tmp);

		// pulled from e2common so we can keep track of all available options
		// to later display deliverables that are not checked
		$wd_deliverables = get_webdev_options();

		$selected_wd_deliverables = array();
		if (@array_key_exists('wd_deliverables', $d)) {
			$selected_wd_deliverables = explode(':', $d['wd_deliverables']);
		}
		?>
		<h2><?php echo (($is_new) ? "Add New Client" : 'Edit Client Info'); ?></h2>
		<?php $this->print_header_links(); ?>
                
		<div id="parent_client">
				<label>Parent Client: </label>
				<select id="parent_id" name="parent_id">
					<option value=""></option>
					<?php
					foreach ($prospects as $p) {
						echo "<option value=" . $p['id'];
						if ($d['parent_id'] == $p['id']) {
							echo " selected";
						}
						echo ">{$p['prospect_company']}</option>";
					}
					?>
				</select>
			</div>
                
        <fieldset>
            <legend>Sales Person & Client Information</legend>
            <div class="row">
                <div>
                    <label>Sales Person: </label>
                    <span style="font-weight:bold;"><?php echo $user['realname']; ?></span>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Contact Name</label>
                    <input type="text" name="name" id="name" class="required" focus_me=1 value="<?php echo $d['name']; ?>" />
                </div>
                <div>
                    <label>Company Name</label>
                    <input type="text" name="prospect_company" value="<?php echo $d['prospect_company']; ?>" />
                </div>
                <div>
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo $d['title']; ?>" />
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Address</label><input type="text" name="address" value="<?php echo $d['address']; ?>" />
                </div>
                <div>
                    <label>City</label><input type="text" name="city" value="<?php echo $d['city']; ?>" />
                </div>
                <div>
                    <label>State</label><?php echo html::select(html::options($states, $d['state']), array('name' => 'state')); ?>
                </div>
                <div>
                    <label>Zipcode</label><input type="text" name="zip" value="<?php echo $d['zip']; ?>" />
                </div>
            </div>
            <div class="row">
                <label>Country</label><?php echo html::select(html::options($countries, $country), array('name' => 'country')); ?>
            </div>
            <div class="row">
            	<div>
					<label>SAP URL</label><input type="text" name="url_key" class="required" dirty=0 value="<?php echo $d['url_key']; ?>" />
                </div>
                <div>
                    <label>Web Address</label><input type="text" name="url" value="<?php echo $d['url']; ?>" />
                </div>
                <div>
                    <label>Email</label><input type="text" name="email" value="<?php echo $d['email']; ?>" />
                </div>
                <div>
                    <label>Phone</label><input type="text" name="phone" value="<?php echo $d['phone']; ?>" />
                </div>
                <div>
                    <label>Fax</label><input type="text" name="fax" value="<?php echo $d['fax']; ?>" />
                </div>
            </div>
            <div class="row">
                <label>Contract Length (in months)</label><input type="text" class="num" name="ppc_contract_length" value="<?php echo $d['ppc_contract_length']; ?>" />
            </div>
        </fieldset>
        
        <fieldset>
            <legend>PPC Management</legend>
            <div class="row">
            	<div>
                	<label>PPC Package</label><?php echo html::select(html::options($ppc_packages, $d['ppc_package']), array('name' => 'ppc_package')); ?>
                </div>
               	<div>
                	<label>Budget</label><input type="text" id="ppc_budget" name="ppc_budget" class="num" value="<?php echo $d['ppc_budget']; ?>" />
                </div>
                <div>
                	<label>Management Fee <small>(min $1000)</small></label>
                	<input type="text" id="ppc_mgmt_perc" name="ppc_mgmt_perc" class="num" value="<?php echo $d['ppc_mgmt_perc']; ?>" maxlength="2" style="width: 40px; margin-right: 4px;" />% = $<input type="text" id="ppc_mgmt" name="ppc_mgmt" class="num" value="<?php echo $d['ppc_mgmt']; ?>" style="width: 80px; margin-left: 4px;" />
                </div>
                 <div>
                	<label>Setup Fee</label><input type="text" name="ppc_setup_fee" class="num" value="<?php echo $d['ppc_setup_fee']; ?>" />
                </div>
               
            </div>
            <div class="row">
             	<div>
                    <input type="checkbox" class="inputCheckbox" name="ppc_clicks" value=1 <?php echo ((empty($d['ppc_clicks'])) ? '' : 'checked'); ?>/><span>Wpromote Pays Clicks</span>
                </div>
             	
                <div>
                    <label>Discount</label><input type="text" name="ppc_discount" class="num" value="<?php echo $d['ppc_discount']; ?>" />
                </div>
                
            </div>
        </fieldset>
        
        <fieldset class="half">
            <legend>Search Engine Optimization</legend>
            <div class="row">
                <div>
                	<label>Package</label><?php echo html::select(html::options($seo_packages, $d['seo_package']), array('name' => 'seo_package')); ?>
                    
                </div>
                <div>
                	<label>Package Total/Month</label>
                        <input type="text" name="seo_package_amount" class="num"
                               value="<?php echo $d['seo_package_amount']; ?>"
                               onchange="update_seo_total();"
                        />

                </div>
            </div>
            <div class="row" style='display: none;'>
                <div>
                    <input type="checkbox" class="inputCheckbox" name="seo_blog" value=1 <?php echo ((empty($d['seo_blog'])) ? '' : 'checked'); ?>/>
                    <span>Add Blog</span>
                </div>
                <div>
                    <label>Blog Total</label>
                    <input type="text" name="seo_blog_amount" class="num"
                           value="<?php echo $d['seo_blog_amount']; ?>"
                           onchange="update_seo_total();"
                           <?php echo ((!empty($d['seo_blog'])) ? '' : 'disabled'); ?>
                    />
                </div>
            </div>
            <div class="row" style='display: none;'>
                <div>
                    <input type="checkbox" class="inputCheckbox" name="seo_blog_mgmt" value=1 <?php echo ((empty($d['seo_blog_mgmt'])) ? '' : 'checked'); ?>/><span>Add Blog Mgmt</span>
                </div>
                <div>
                    <label>Blog Mgtm Fee/Month</label>
                    <input type="text" name="seo_blog_mgmt_amount" class="num"
                           value="<?php echo $d['seo_blog_mgmt_amount']; ?>"
                           onchange="update_seo_total();"
                           <?php echo ((!empty($d['seo_blog_mgmt'])) ? '' : 'disabled'); ?>
                    />
                </div>
            </div>
            
            <div class='clear'></div>

            <div>
                    <label>First Month Total</label><input type="text" name="seo_amount" class="num" value="<?php echo $d['seo_amount']; ?>" />
            </div>
            <div>
                    <label>Monthly Total</label><input type="text" name="seo_monthly_amount" class="num" value="<?php echo $d['seo_monthly_amount']; ?>" />
            </div>
            <div>
                    <label>Discount</label><input type="text" name="seo_discount" class="num" value="<?php echo $d['seo_discount']; ?>" />
            </div>

        </fieldset>
        
        <fieldset class="half">
            <legend>Social Media Optimization</legend>
            <div class="row">
                <div>
                	<label>Package</label><?php echo html::select(html::options($smo_packages, $d['smo_package']), array('name' => 'smo_package')); ?>
                </div>
                <div>
                	<label>Amount</label><input type="text" name="smo_amount" class="num" value="<?php echo $d['smo_amount']; ?>" />
                </div> 
                <div>
                	<label>Discount</label><input type="text" name="smo_discount" class="num" value="<?php echo $d['smo_discount']; ?>" />
                </div>
                
            </div>
        </fieldset>
		
		<fieldset class="half">
            <legend>Social Link Building</legend>
            <div class="row">
                <div>
                	<label>Package</label><?php echo html::select(html::options($slb_packages, $d['slb_package']), array('name' => 'slb_package')); ?>
                </div>
                <div>
                	<label>Amount</label><input type="text" name="slb_amount" class="num" value="<?php echo $d['slb_amount']; ?>" />
                </div> 
                <div>
                	<label>Discount</label><input type="text" name="slb_discount" class="num" value="<?php echo $d['slb_discount']; ?>" />
                </div>
                
            </div>
        </fieldset>
        
        <fieldset class="half" style="margin-top: 15px;">
            <legend>Infographic Creation & Distribution</legend>
            <div class="row">
                <div>
					<input type="hidden" name="infographic" value="0" />
                	<label><input type="checkbox" id="infographic" name="infographic" value="1" <?php echo ((empty($d['infographic'])) ? '' : 'checked'); ?>/> Add Infogrphic</label>
                </div>
                <div>
                	<label>Number of Infographics</label>
                	<input type="text" id="ig_num" name="ig_num" class="num" value="<?php echo $d['ig_num']; ?>" />
                </div>
                <div>
                	<label>Amount</label><input type="text" id="seo_ig_amount" name="seo_ig_amount" class="num" value="<?php echo $d['seo_ig_amount']; ?>" />
                </div> 
                <div>
                	<label>Discount</label><input type="text" name="seo_ig_discount" class="num" value="<?php echo $d['seo_ig_discount']; ?>" />
                </div>
                
            </div>
        </fieldset>
	
		<div class="clear"></div>
		
		<fieldset>
			<legend>Facebook Advertising</legend>
			<div class="row">
				<div>
					<input type="hidden" name="fba_package" value="0" />
					<input type="checkbox" id="fba_package" name="fba_package" value=1 <?php echo ((empty($d['fba_package'])) ? '' : 'checked'); ?>/>
					<span>Select Facebook Advertising</span>
				</div>
				<div>
					<label>Budget</label><input type="text" id="fba_budget" name="fba_budget" class="num" value="<?php echo $d['fba_budget']; ?>" />
				</div>
				<div>
					<label>Management Fee <small>(min $1000)</small></label>
					<input type="text" id="fba_mgmt_perc" name="fba_mgmt_perc" class="num" value="<?php echo $d['fba_mgmt_perc']; ?>" maxlength="2" style="width: 40px; margin-right: 4px;" />% = $<input type="text" id="fba_mgmt" name="fba_mgmt" class="num" value="<?php echo $d['fba_mgmt']; ?>" style="width: 80px; margin-left: 4px;" />
				</div>
				 <div>
					<label>Setup Fee</label><input type="text" name="fba_setup_fee" class="num" value="<?php echo $d['fba_setup_fee']; ?>" />
				</div>
			</div>
			<div class="row">
				<div>
					<input type="hidden" name="fba_clicks" value="0" />
					<input type="checkbox" id="fba_clicks" name="fba_clicks" value=1 <?php echo ((empty($d['fba_clicks'])) ? '' : 'checked'); ?>/>
					<span>Wpromote Pays Clicks</span>
				</div>
				<div>
					<label>Discount</label><input type="text" name="fba_discount" class="num" value="<?php echo $d['fba_discount']; ?>" />
				</div>
			</div>
		</fieldset>

		<div class="clear"></div>

		<fieldset>
        <legend>Web Development</legend>
            <div class="row">
            	<div>
                	<label>Package</label><?php echo html::select(html::options($wd_packages, $d['wd_package']), array('name' => 'wd_package')); ?>
                </div>
                 <div>
                	<label>Package Amount</label><input type="text" name="wd_package_amount" class="num" value="<?php echo $_POST['wd_package_amount']; ?>" readonly="readonly" />
                </div>
                <div>
                	<label>Number of Pages</label><input type="text" name="wd_num_landing_pages" class="num" value="<?php echo $d['wd_num_landing_pages']; ?>" />
                </div>
                <div>
                	<label>Total Amount</label><input type="text" name="wd_amount" class="num" value="<?php echo $d['wd_amount']; ?>" />
                </div>
                 
             </div>
             <div class="row">
                <div>
                	<input type="checkbox" class="inputCheckbox" name="wd_op" value=1 <?php echo ((empty($d['wd_op'])) ? '' : 'checked'); ?>/><span>Landing Page Optimization</span>
                </div>
                <div>
                	<input type="checkbox" class="inputCheckbox" name="wd_landing_page" value=1 <?php echo ((empty($d['wd_landing_page'])) ? '' : 'checked'); ?> /><span>Landing Page Design</span>
                </div>
                <div>
                	<input type="checkbox" class="inputCheckbox" name="wd_landing_page_testing" value=1 <?php echo ((empty($d['wd_landing_page_testing'])) ? '' : 'checked'); ?>/><span>Include Testing</span>
                </div>
            </div>
            <div id="web_deliver" class="row">
                <p><b>Deliverables</b></p>
                <?php
                    foreach($wd_deliverables as $item){
                        $checked = in_array($item, $selected_wd_deliverables) ? "checked='checked'" : "";
                        echo "<div>";
                        echo "<input type='checkbox' class='inputCheckbox' name='wd_deliverables[]' value='{$item}' $checked />";
                        echo "<span>{$item}</span>";
                        echo "</div>";
                    }
                ?>
            </div>
            <div class="row">
                <div>
                        <input type="checkbox" class="inputCheckbox" name="wd_pay_half" value=1 <?php echo ((empty($d['wd_pay_half'])) ? '' : 'checked'); ?>/><span>Split Pay Plan</span>
                </div>
                <div>
                	<label>1st Month Total</label><input type="text" name="wd_first_month_amount" class="num" value="<?php echo $d['wd_first_month_amount']; ?>" />
                </div>
            </div>
            <div class="row">
            	<div>
                	<label>Discount</label><input type="text" name="wd_discount" class="num" value="<?php echo $d['wd_discount']; ?>" />
                </div>
           	</div>
        </fieldset>
		
		<input type="submit" value="Submit" onclick="return prospectFieldCheck();" /></td>
		<?php
	}
	
	private function print_header_links()
	{
		$ml_back_to_prospect = ($this->prospect['id']) ? '<a href="'.cgi::href('sales/details?client='.$this->prospect['id']).'">Back to Prospect</a>' : '';
		?>
		<p>
			<a href="<?php echo cgi::href('sales'); ?>">Sales Home</a>
			<?php echo $ml_back_to_prospect; ?>
		</p>
		<?php
	}
	
	protected function show_payment_form()
	{
		// try getting client payment info
		$d = @db::select_row("select * from prospects where id='".$this->prospect['id']."'", 'ASSOC');
		
		if($d['payment_method']=="credit"){
		
			$cc_id = billing::get_prospect_cc_id($this->prospect['id']);
			$p = db::select_row("select * from eppctwo.ccs where id = '$cc_id'", 'ASSOC');
			$cc_info = billing::cc_get_display($p['id']);
			$cc_number = $cc_info['cc_number'];
			$cc_code = $cc_info['cc_code'];
			
		} else{
			$p = @db::select_row("select * from checks where foreign_id='".$this->prospect['id']."'", 'ASSOC');
			$check_info = billing::check_get_actual($p['id']);
			$account_number = $check_info['account_number'];
			$routing_number = $check_info['routing_number'];
		}
		
		// month select for cc exp date (1999 is an arbitrary year)
			$month_options = array();
			for ($i = 1; $i <= 12; ++$i)
			{
				$month_num = str_pad($i, 2, '0', STR_PAD_LEFT);
				$month_options[] = array('value' => $month_num, 'caption' => $month_num.' - '.date('M', strtotime('1999-'.$month_num.'-01')));
			}
			
		// year select for cc exp date
			$year_options = array();
			for ($i = 0, $cur_year = date('Y'); $i < 10; ++$i, ++$cur_year)
			$year_options[] = array('value' => $cur_year, 'caption' => $cur_year);
		
		$states = db::select("
			select short value, text caption
			from states
			order by caption asc
		", 'ASSOC');
		
		$countries = db::select("
			select a2 value, country caption
			from countries
			order by caption asc
		", 'ASSOC');
		
		$country = (@array_key_exists('country', $p)) ? $p['country'] : 'US';
	
		?>
        
		<div id="payment" onLoad="loader()">
        <h2>Edit Payment Info</h2>
        <?php $this->print_header_links(); ?>
    	<fieldset>
            <div>
                <label>Payment Method</label>
                <select id="payment_options" name="payment_options" onChange="checkPayment('payment_options')">
                    <option <?php if($d['payment_method']!="check") echo "selected"; ?> value="credit">Credit Card</option>
                    <option <?php if($d['payment_method']=="check") echo "selected"; ?> value="check">Check</option>
                </select>
            </div>
            
            <div id="payment_type">
                <div id="credit_card" class="method">
                    <div>
                    <label>Credit Card Type</label>
                    <select name="cc_type">
                        <option <?php if($p['cc_type']=="visa") echo "selected"; ?> value="visa">Visa</option>
                        <option <?php if($p['cc_type']=="discover") echo "selected"; ?> value="discover">Discover</option>
                        <option <?php if($p['cc_type']=="amex") echo "selected"; ?> value="amex">AmEx</option>
                        <option <?php if($p['cc_type']=="mc") echo "selected"; ?> value="mc">MasterCard</option>
                    </select>
                    </div>

                    <div>
                        <label>Billing Name</label>
                       <input type="text" name="cc_name" value="<?php echo $p['name']; ?>" />
                    </div>

                    <div>
                        <label>Zip</label>
                        <input name="cc_zip" type="text" value="<?php echo $p['zip']; ?>" />
                    </div>
                    
                    <div>
                    <label>Country</label>
                    <?php echo html::select(html::options($countries, $country), array('name' => 'cc_country')); ?>
                    </div>
                    
                    <div>
                    <label>Credit Card Number</label>
                    <input name="cc_number" type="text" value="<?php echo $cc_number; ?>" maxlength=16 />
                    </div>
                    <div>
                    <label>VCC</label>
                    <input name="cc_code" type="text" value="<?php echo $cc_code; ?>"/>
                    </div>
                    <div class="left">
                    <label>Expiration Date</label>
                    <select name="cc_exp_month">
                    <?php 
						
						for($i=0;$i<count($month_options);$i++){
							echo "<option value=\"".$month_options[$i]['value']."\" ";
							if($month_options[$i]['value']==$p['cc_exp_month']){
								echo "selected ";
							}
							echo ">".$month_options[$i]['caption']." </option>";
						} 
					?>
                    </select>
                  	<select name="cc_exp_year">
                    <?php 
						for($i=0;$i<count($year_options);$i++){
							echo "<option value=\"".$year_options[$i]['value']."\" ";
							if($year_options[$i]['value']==$p['cc_exp_year']){
								echo "selected ";
							}
							echo ">".$year_options[$i]['caption']." </option>";
						} 
					?>
                    </select>
                    </div>
                    
                </div>
                
                <div id="check" class="method">
                    <div>
                    <label>Name</label>
                    <input type="text" name="check_name" value="<?php echo $p['name'];  ?>" />
                    </div>
                    <div class="left">
                    <label>Account Type</label>
                    <select name="account_type">
                        <option value="checking">Checking</option>
                        <option value="savings">Savings</option>
                    </select>
                    </div>
                    <div class="left">
                    <label>Bank Routing Number</label>
                    <input type="text" value="<?php echo $routing_number; ?>" name="routing_number" />
                    </div>
                    <div class="left">
                    <label>Account Number</label>
                    <input type="text" value="<?php echo $account_number; ?>" name="account_number"/>
                    </div>
                    
                     <div class="left">
                    <label>Check Number</label>
                    <input type="text" value="<?php echo $p['check_number']; ?>" name="check_number"/>
                    </div>
                    
                    <div class="clear"></div>
                    
                    <div>
                    <label>Phone</label>
                    <input type="text" value="<?php echo $p['phone']; ?>" name="phone" />
                    </div>
                   
                    <div class="left">
                    <label>Driver's License Number</label>
                    <input type="text" value="<?php echo $p['drivers_license']; ?>" name="drivers_license"/>
                    </div>
                    <div class="left">
                    <label>License State</label>
                    <?php echo html::select(html::options($states, $p['drivers_license_state']), array('name' => 'drivers_license_state')); ?>   
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset>
        	<input type="submit" value="Submit" a0="edit_payment_submit" >
        </fieldset>
        </fieldset>
        <input type="hidden" name="pid" value="<?php echo $p['id']; ?>" />
    </div>
		<?php
	}


        protected function edit_sap()
	{
		$default = db::select("select * from sap_text
							where client_id='0'",
							'ASSOC', 's1', 's2', 's3', 'list_order');
							
							

		?>




		<h2>Statement of Confidentiality</h2>
        <?php $this->edit_default_field($default, 'confidentiality', '', '','par'); ?>

                <h2>General Contract Terms</h2>
                <?php $this->edit_default_field($default, 'terms', 'general', '','par'); ?>

        <ol>

        	<li>Overview</li>
            	<?php $this->edit_default_field($default, 'terms', 'overview', '','list'); ?>

            <li>Campaign Goals</li>
				<?php $this->edit_default_field($default, 'terms', 'goals', '','list'); ?>

            <li>Deliverables</li>

            	<h4>Express PPC</h4>
				<?php $this->edit_default_field($default, 'terms', 'deliverables', 'express_ppc_dashes','list_small'); ?>
                <?php $this->edit_default_field($default, 'terms', 'deliverables', 'express_ppc','list'); ?>

                <?php $this->edit_default_field($default, 'terms', 'deliverables', 'ppc_title', 'title'); ?>
				<?php $this->edit_default_field($default, 'terms', 'deliverables', 'premium_ppc_dashes','list_small'); ?>
                <?php $this->edit_default_field($default, 'terms', 'deliverables', 'premium_ppc','list'); ?>

                <?php
                $this->edit_default_field($default, 'terms', 'deliverables', 'seo_title', 'title');
				for($j=1;$j<=4;$j++){
					echo "<h4>SEO Package {$j}</h4>";
					$this->edit_default_field($default, 'terms', 'deliverables', 'seo'.$j,'list_small');
					for($i=1;$i<=6;$i++){
						echo "<div class=\"month_details\">";
						echo "	<h5>SEO Package {$j} - Month {$i}</h5>";
								$this->edit_default_field($default, 'terms', 'deliverables', 'seo'.$j.'_month'.$i, 'list_small');
						echo "</div>";
					}
				}
				?>

                 <?php
                $this->edit_default_field($default, 'terms', 'deliverables', 'smo_title', 'title');
				for($j=1;$j<=3;$j++){
					echo "<h4>SMO Package {$j}</h4>";
					$this->edit_default_field($default, 'terms', 'deliverables', 'smo'.$j,'list_small');
				}
				echo "<h5>SMO Month 1</h5>";
				$this->edit_default_field($default, 'terms', 'deliverables', 'smo_month1', 'list');
				
				echo "<h5>SMO Month 2 & Ongoing</h5>";
				$this->edit_default_field($default, 'terms', 'deliverables', 'smo_month2', 'list');
				?>
				
				<h4>Infographic<h4/>
				<?php
					$this->edit_default_field($default, 'terms', 'deliverables', 'infographic_title', 'title');
					$this->edit_default_field($default, 'terms', 'deliverables', 'infographic', 'list_small');
				?>

				<h4>Facebook Advertising<h4/>
				<?php
				$this->edit_default_field($default, 'terms', 'deliverables', 'fba_title', 'title');
				$this->edit_default_field($default, 'terms', 'deliverables', 'fba_dashes','list_small');
                $this->edit_default_field($default, 'terms', 'deliverables', 'fba','list');
				?>

				<h4>Social Link Building</h4>
				<h5>SLB - Package 1</h5>
				<?php
				$this->edit_default_field($default, 'terms', 'deliverables', 'slb_p1_title', 'title');
				$this->edit_default_field($default, 'terms', 'deliverables', 'slb_p1', 'list_small');
				?>
				<h5>SLB - Package 2</h5>
				<?php
				$this->edit_default_field($default, 'terms', 'deliverables', 'slb_p2_title', 'title');
				$this->edit_default_field($default, 'terms', 'deliverables', 'slb_p2', 'list_small');
				?>
				

				<?php
				$web_service_names = array('basic','midrange','top');
				for($i=0;$i<3;$i++){
					echo "<h4>Web Development Package - ".$web_service_names[$i]."</h4>";
					$this->edit_default_field($default, 'terms', 'deliverables', 'wd_title_'.$web_service_names[$i], 'title');
					$this->edit_default_field($default, 'terms', 'deliverables', $web_service_names[$i].'_website_service','list_small');
				}
				?>

                <?php
				echo "<h4>Landing Page Design</h4>";
				$this->edit_default_field($default, 'terms', 'deliverables', 'landing_page_design', 'list_small');
				?>

                <?php
				echo "<h4>Landing Page Design & Testing</h4>";
				$this->edit_default_field($default, 'terms', 'deliverables', 'landing_page_design_testing', 'list_small');
				?>

        	<li>Service Fees</li>

             	<h4>Express PPC Management - Monthly Service Fee</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'express_ppc','list'); ?>

                <h4>Premium PPC Management  - Monthly Service Fee</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'premium_ppc_info','par'); ?>
                <?php $this->edit_default_field($default, 'terms', 'fees', 'premium_ppc','list'); ?>

               	<h4>SEO</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'seo','par'); ?>
				
				<h4>Infographic Creation & Distribution</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'infographic','par'); ?>

				<h4>Facebook Advertising<h4/>
				<?php
				$this->edit_default_field($default, 'terms', 'fees', 'fba_title','title');
				echo '<br />';
				$this->edit_default_field($default, 'terms', 'fees', 'fba_info','par');
				$this->edit_default_field($default, 'terms', 'fees', 'fba','list');
				?>

				<h4>Social Link Building</h4>
				<?php
				$this->edit_default_field($default, 'terms', 'fees', 'slb_title', 'title');
				echo '<br />';
				$this->edit_default_field($default, 'terms', 'fees', 'slb', 'par');
				?>

                <h4>Basic Website Service</h4>
                <?php $this->edit_default_field($default, 'terms', 'fees', 'basic_website_service','par'); ?>
                <h4>Midrange Website Service</h4>
                <?php $this->edit_default_field($default, 'terms', 'fees', 'midrange_website_service','par'); ?>
                <h4>Top Website Service</h4>
                <?php $this->edit_default_field($default, 'terms', 'fees', 'top_website_service','par'); ?>

                <h4>Landing Page Design</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'landing_page_design','par'); ?>
                <h4>Landing Page Design & Testing</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'landing_page_design_testing','par'); ?>

                <h4>Landing Page Optimization</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'landing_page_op', 'par'); ?>

                <h4>SMO</h4>
				<?php $this->edit_default_field($default, 'terms', 'fees', 'smo', 'par'); ?>
				
				
				
				<li>Client Feedback Timeliness</li>
                <?php $this->edit_default_field($default, 'terms', 'feedback', 'web_dev', 'par'); ?>
                <?php $this->edit_default_field($default, 'terms', 'feedback', 'web_dev_list', 'list'); ?>
				
				

                <li>Contract Terms</li>
                <?php $this->edit_default_field($default, 'terms', 'contract', '','list_big'); ?>

				<h5>Contract Terms - PPC</h5>
				<?php $this->edit_default_field($default, 'terms', 'contract', 'ppc', 'list_big', 9); ?>
				
				<h5>Contract Terms - SEO</h5>
				<?php $this->edit_default_field($default, 'terms', 'contract', 'seo', 'list_big', 9); ?>

                <li>Agreement</li>
                <h4>Default Agreement</h4>
                <?php $this->edit_default_field($default, 'agree', '', '', 'par'); ?>

                <h4>Express PPC Agreement</h4>
                <?php $this->edit_default_field($default, 'agree', 'express', '', 'par'); ?>

                <h4>Premium PPC Agreement</h4>
                <?php $this->edit_default_field($default, 'agree', 'premium', '', 'par'); ?>

        </ol>

        <?php
	}

        protected function edit_default_field($default,$s1,$s2,$s3,$type,$list_item = -1){

		if($type=="par"){

			$text = $default[$s1][$s2][$s3][0]['text'];

			echo "<textarea name=\"{$s1}-{$s2}-{$s3}-0\" id=\"{$s1}-{$s2}-{$s3}-0\" class=\"par\" onchange=\"mark_edit(this)\">";
			echo $text;
			echo "</textarea>";
			echo "<br>";

		} else if($type=="title"){

			$text = $default[$s1][$s2][$s3][0]['text'];

			echo "<div class='clear'></div>";
			echo "<textarea name=\"{$s1}-{$s2}-{$s3}-0\" id=\"{$s1}-{$s2}-{$s3}-0\" class=\"title\" onchange=\"mark_edit(this)\">";
			echo $text;
			echo "</textarea>";
			echo "<br>";

		} else if($list_item >= 0) {
			
			$text = $default[$s1][$s2][$s3][$list_item]['text'];

			echo "<div class=\"{$type}\" >";
			echo "<textarea name=\"{$s1}-{$s2}-{$s3}-{$list_item}\" id=\"{$s1}-{$s2}-{$s3}-{$list_item}\" onchange=\"mark_edit(this)\">";
			echo $text;
			echo "</textarea>";
			echo "</div>";
			
		} else {

			$list_length = count($default[$s1][$s2][$s3]);
			echo "<div id=\"{$s1}-{$s2}-{$s3}\">";
			echo "<ul class=\"{$type}\">";
			for($i=1;$i<$list_length+1;$i++){
				$text = $default[$s1][$s2][$s3][$i]['text'];
				echo "<li>";
				echo "<textarea name=\"{$s1}-{$s2}-{$s3}-{$i}\" id=\"{$s1}-{$s2}-{$s3}-{$i}\"  onchange=\"mark_edit(this)\">";
				echo $text;
				echo "</textarea>";
				echo "</li>";
			}
			echo "</ul>";
			echo "<div class=\"clear\"></div>";
			echo "<input type=\"button\" value=\"Add List Item\" onclick=\"addDefaultField(this)\" />";
			echo "</div>";
		}

		echo "<input type=\"submit\" value=\"Save\" a0=\"edit_default_field_submit\" />";

	}

        protected function edit_default_field_submit(){

		$d = $_POST;
		//@array_walk($d, 'escape_single_quotes');

                $order   = array("\r\n", "\n", "\r");
                $replace = '<br />';


		$sap_vars = array();
		foreach($d as $key => $value){
			if(strpos($key,"sap_edit") === 0){
				$key = substr_replace($key,"",0,9);
				$sap_vars[$key]=str_replace($order, $replace, $value);
			}
		}

		// loop through our sap text areas

		foreach($sap_vars as $key => $value){

			$index = explode("-",$key);  // array of indexes

                        //$sql =  "INSERT INTO sap_text ";
                        //$sql .= "SET text = '".$value."' ";
                        //$sql .= "WHERE ";
                        //$sql .= "(s1='".$index[0]."' && s2='".$index[1]."' && s3='".$index[2]."' && list_order=".$index[3]." && type='list' && client_id='0') ";
                        //$sql .= "ON DUPLICATE KEY UPDATE";
                        //echo $sql;

                        $table = 'sap_text';
                        $key_cols = array('s1','s2','s3','list_order');
                        $data = array(
                            's1'=>$index[0],
                            's2'=>$index[1],
                            's3'=>$index[2],
                            'list_order'=>$index[3],
                            'type'=>'list',
                            'client_id'=>'0',
                            'text'=>$value
                        );

                        //db::dbg();

			if(db::insert_update($table,$key_cols,$data)){

				$msg = "Updated item in section: {$index[0]}-{$index[1]}-$index[2]";
				feedback::add_success_msg($msg);
			}
		}

	}


        protected function edit_sap2(){
            global $default;
            $default = db::select("SELECT * FROM sap_text
                                WHERE client_id='0' ORDER BY list_order",
                                'ASSOC', 's1', 's2', 's3', 'list_order');
        ?>
            <p><a href="#" class="slide">Statement of Confidentiality</a></p>
            <?php $this->edit_default_field2('confidentiality'); ?>

            <p><a href="#" class="slide">Overview</a></p>
            <?php $this->edit_default_field2('terms', 'overview', '','list'); ?>

            
        <?php
        }

        protected function edit_default_field2($s1='',$s2='',$s3='',$type='par'){
            global $default;
            $out  =  "<div class=\"edit_block\">";
            if($type=="par"){
                $out .=      "<textarea class=\"default_text\" s1=\"{$s1}\" s2=\"{$s2}\" s3=\"{$s3}\" list_order=\"0\">{$default[$s1][$s2][$s3][0]['text']}</textarea>";
            } else if($type=="list"){
                foreach($default[$s1][$s2][$s3] as $list_order => $content){
                    $out .=      "<textarea class=\"default_text list\" s1=\"{$s1}\" s2=\"{$s2}\" s3=\"{$s3}\" list_order=\"{$list_order}\">{$content['text']}</textarea>";
                }   
            }
            $out .=      "<div class=\"actions\">
                            <input type=\"submit\" value=\"Save\" class=\"save\" />
                            <input type=\"submit\" value=\"Cancel\" class=\"cancel\" />
                        </div>
                    </div>";
           echo $out;
        }

        public function ajax_save_default_text(){

            //format text from database entry
            $order   = array("\r\n", "\n", "\r");
            $replace = '<br />';
            $formated_text = str_replace($order, $replace, $_POST['text']);

            $sql = "UPDATE sap_text SET text = '{$formated_text}' WHERE
                        client_id = '0' AND
                        s1 = '{$_POST['s1']}' AND
                        s2 = '{$_POST['s2']}' AND
                        s3 = '{$_POST['s3']}' AND
                        list_order = '{$_POST['list_order']}'
                    LIMIT 1;
            ";
            if(db::exec($sql)){
                $msg = "Success!!";
            } else {
                $msg = "Update Failed or no changes were found";
            }

            // return results
            $response = array(
                'msg' => $msg
            );
            echo json_encode($response);
        }
        
	public function new_clients_list()
	{
		util::load_rs('agency');
		
		$start_date = util::unempty($_POST['start_date'], date('Y-m-01'));
		$end_date = util::unempty($_POST['end_date'], date(util::DATE));
		
		$new_client_depts = array(
			'ppc' => array(),
			'seo' => array(),
			'smo' => array(),
			'email' => array()
		);
		$new_client_part_types = array();
		foreach (client_payment_part::$part_types as $type => $dept) {
			if (array_key_exists($dept, $new_client_depts)) {
				$new_client_depts[$dept][] = $type;
				$new_client_part_types[] = $type;
			}
		}
		// get payment ids for payment in date range that have at least 1 of part types we are interested in
		//grouping by client_id to ensure that the client_id appears once and the first most bill accessed in furthere calculation
		$payments_in_range = db::select("
				select p.id, p.client_id, p.date_attributed date, p.amount total, group_concat(pp.type separator '\t') part_types, group_concat(pp.amount separator '\t') part_amounts
				from eppctwo.client_payment p, eppctwo.client_payment_part pp
				where
					p.date_attributed between :start and :end &&
					p.amount > 0 &&
					pp.type in (:part_types) &&
					p.id = pp.client_payment_id
				group by p.client_id
		", array(
			"start" => $start_date,
			"end" => $end_date,
			"part_types" => $new_client_part_types
		), 'ASSOC');
		$cl_ids_in_range = array();
		foreach ($payments_in_range as $p) {
			$cl_ids_in_range[] = $p['client_id'];
		}

		// filter out client that have earlier payments
		$clients_paid_before_range = db::select("
			select distinct client_id, 1
			from eppctwo.client_payment
			where
				date_attributed < :start &&
				client_id in (:cids)
		", array(
			"start" => $start_date,
			"cids" => $cl_ids_in_range
		), 'NUM', 0);

		$payments = array();
		$cl_ids = array();
		foreach ($payments_in_range as $p) {
			$cl_id = $p['client_id'];
			if (!isset($clients_paid_before_range[$cl_id])) {
				$cl_ids[] = $cl_id;
				$payments[] = $p;
			}
		}

		// get client names
		$ac_info = db::select("
			select distinct client_id, id as aid, dept, name
			from eac.account
			where client_id in (:cids)
		", array(
			"cids" => $cl_ids
		), 'ASSOC', 'client_id');

		foreach ($payments as &$p) {
			$cl_id = $p['client_id'];
			if (!isset($ac_info[$cl_id])) {
				$ac_info[$cl_id] = array(
					'aid' => '??',
					'name' => '??'
				);
			}
			$p = array_merge($p, $ac_info[$cl_id]);
		}

		$reps = db::select("
			select distinct s.client_id, u.realname
			from eppctwo.users u, eppctwo.sales_client_info s
			where
				s.client_id in (:cids) &&
				u.id = s.sales_rep
		", array(
			"cids" => $cl_ids
		), 'NUM', 0);
		
		?>
		<h2>New Clients</h2>
		<?php echo cgi::date_range_picker($start_date, $end_date); ?>
		<div id="payments"></div>
		<?php
		cgi::add_js_var('payments', $payments);
		cgi::add_js_var('reps', $reps);
	}
	
	public function sbs_contacts()
	{
		list($status_filter, $start_date, $end_date, $search) = util::list_assoc($_POST, 'status_filter', 'start_date', 'end_date', 'search');
		
		$cols = array(
			'Id' => 'id',
			'Time' => 'created',
			'Name' => 'name',
			'Email' => 'email',
		        'Order Page' => 'interest_page_id',
			'Phone' => 'phone',
			'IP' => 'ip',
			'Status' => 'status',
			'Source' => 'source',
			'Referer' => 'referer',
			'Sub ID' => 'subid',
			'Category' => 'cat',
			'Referring URL' => 'referring_url'
		);
		
		$status_options = array(
			'all',
			'new',
			'incomplete',
			'called',
			'contacted'
		);
		
		// default 1 week
		if (empty($start_date))
		{
			$end_date = date(util::DATE);
			$start_date = date(util::DATE, time() - 518400);
		}
		
		if ($status_filter && $status_filter != 'all'){
			$where_status = "status = '$status_filter'";
		} else { 
			$where_status = "status <> 'deleted'";
		}
		
		if ($search)
		{
			$tmp = array();
			foreach ($cols as $col)
			{
				if ($col != 'status')
				{
					$tmp[] = "$col like '%$search%'";
				}
			}
			$where_search = "&& (".implode(" || ", $tmp).")";
		}
		
		$contacts = db::select("
			Select *
			from sbs_contacts
			where
				created between '$start_date' and '$end_date 23:59:59' &&
				$where_status
				$where_search
			ORDER BY created DESC
		", 'ASSOC');
		
		$table = "<table width='100%' cellspacing='0' cellpadding='0'><thead><tr>";
		foreach($cols as $col => $key){
			$table .= "<th>$col</th>";
		}
		$table .= "</tr></thead><tbody>";
		
		foreach($contacts as $contact){
			$table .= "<tr>";
			foreach($cols as $col){
				
				$value = $contact[$col];
				if($col=='name'){
					$value = ($value=="") ? "noname" : $value;  
					$value = "<a href='".cgi::href("sales/view_sbs_contact/?id={$contact['id']}")."'>$value</a>";
				} else if($col=='interest_page_id'){
					if($value){
						$value = "<a href='".$this->wpro_interest_page_link($contact['id'])."' target='_blank'>View</a>";
					} else {
						$value = "";
					}
				}
				
				$table .= "<td".(($col == 'created') ? ' class="nowrap"' : '').">$value</td>";
			}
			$table .= "</tr>";
		}
		$table .= "</tbody></table>";
		
	?>
	<h1>SBS Contacts</h1>
	<a href="<?php echo cgi::href('sales/new_sbs_contact') ?>">Create New</a> | 
	<a href="http://<?php echo \epro\WPRO_DOMAIN ?>/small-business-solutions/consult" target="_blank">Wpromote Form</a> |
    <a href="<?php echo cgi::href('sales/view_import_sbs_contacts') ?>">Import from CSV</a>
	<br />
	<table>
		<tbody>
			<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
			<tr>
				<td><label>Status</label></td>
				<td><?php echo cgi::html_select('status_filter', $status_options, $status_filter); ?></td>
			</tr>
			<tr>
				<td><label>Search</label></td>
				<td><input type="text" name="search" value="<?php echo htmlentities($search); ?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" value="Submit" /></td>
			</tr>
		</tbody>
	</table>
	
	<?php echo $table ?>
	
	<?php
	}

    //
    //
    // Best Leads Plus
    //
    //
    public function action_import_contacts()
    {
        if(empty($_FILES['contacts']['tmp_name'])){
            return;
        }

        $contacts_for_avv = array();
        $output = "";
        $row = 1;
        if (($handle = fopen($_FILES['contacts']['tmp_name'], "r")) !== FALSE) {
            $row_num = 0;
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row_num==0){
                    //validate the file
                    $expected_values = array('Company Name', 'Contact', 'E-Mail', 'Phone Number', 'Phone Number 2', 'Date');
                    for($i=0;$i<count($row);$i++){
                        if($row[$i]!=$expected_values[$i]){
                           feedback::add_error_msg("Invalid file format.");
                           return;
                        }
                    }
                }
                else {
                    //build contact
                    list($company, $name, $email, $phone, $phone2, $date) = $row;

                    $duplicate = db::select_row('SELECT id FROM sbs_contacts WHERE email = "'.$email.'"');
                    if ($duplicate){
                        continue;
                    }

                    $contact = array(
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'company' => $company,
                        'url' => '',
                        'created' => date(util::DATE_TIME),
                        'referer' => 'blp',
                        'status' => 'new',
                        'source' => 'CSV import'
                    );

                    $id = db::insert('sbs_contacts', $contact);
                    if($id){
                        $contacts_for_avv[] = $contact;
                    }
                }
                $row_num++;
            }
            fclose($handle);

            if(!empty($contacts_for_avv)){
                sales_lib::post_to_avv($contacts_for_avv);
                feedback::add_success_msg("Imported Contacts!");
            }
            else {
                feedback::add_error_msg("No new contacts found.");
            }
        }
    }

    public function view_import_sbs_contacts()
    {
    ?>
        <div>
            <lable>Import Contacts</label>
            <input type="file" name="contacts" />
        </div>

        <input type="submit" a0="action_import_contacts" />
    <?
    }
	
	public function new_sbs_contact(){
	?>
		<div id='sbs-view-contact'>
			<h2>New Contact</h2> <br/>
		
			<div class='editable'>
			
				<div>
					<label>name: </label>
					<input type="text" value="" name='name'>
				</div>
				
				<div>
					<label>email: </label>
					<input type="text" value="" name='email'>
				</div>
				
				<div>
					<label>phone: </label>
					<input type="text" value="" name='phone'>
				</div>
				
				<div>
					<label>company: </label>
					<input type="text" value="" name='company'>
				</div>
				
				<div>
					<label>url: </label>
					<input type="text" value="" name='url'>
				</div>
			
			</div>
			
			<input type="submit" a0="action_submit_new_contact" value="Save" name="submit" />
			
		</div>
	<?php
	}
	
	public function action_submit_new_contact(){
		
		$fields = array(
			'name', 'email', 'phone','company', 'url'
		);
		$contact_data = array();
		foreach($fields as $field){
			$contact_data[$field] = $_POST[$field];
		}
		$contact_data['created'] = date(util::DATE_TIME);
		$contact_data['referer'] = 'manual';
		$id = db::insert('sbs_contacts', $contact_data);
		if($id){
			cgi::redirect('sales/view_sbs_contact/?id='.$id);
		} else {
			feedback::add_error_msg("Invalid input found.");
		}

	}
	
	public function action_edit_contact(){
		$update_fields = array(
			'name', 'email', 'phone','company', 'url', 'budget', 'status', 'notes'
		);
		$contact_data = array();
		foreach($update_fields as $field){
			$contact_data[$field] = $_POST[$field];
		}
		
		//db::dbg();

		//handle interest page data
		$interest_page_data = array();
		$products = array('ql', 'sb', 'gs');
		$delete_page = true;
		foreach($products as $p){
			$interest_page_data[$p.'_package'] = $_POST[$p]['package'];

			if(empty($interest_page_data[$p.'_package'])){
				$interest_page_data[$p.'_cost'] = 0;
				$interest_page_data[$p.'_setup_fee'] = 0;
			} else {
				$interest_page_data[$p.'_cost'] = $_POST[$p]['cost'];
				$interest_page_data[$p.'_setup_fee'] = $_POST[$p]['setup_fee'];
				$delete_page = false;
			}

			if($p=='sb'){
				$interest_page_data[$p.'_fanpage'] = (isset($_POST[$p]['fanpage']) && !empty($interest_page_data[$p.'_package']) ) ? 1 : 0;
			}
		}
		$interest_page_id = db::select_one("select interest_page_id from sbs_contacts where id={$_GET['id']}");
		if(!$delete_page){
			
			foreach(array('user_id', 'start_date', 'other_date', 'details', 'contract_length') as $field){
				$interest_page_data[$field] = $_POST[$field];
			}
			
			if($interest_page_id!='0'){
				db::update('sbs_interest_page', $interest_page_data, 'id = '.$interest_page_id);
			} else {
				$contact_data['interest_page_id'] = db::insert('sbs_interest_page', $interest_page_data);
			}
		} else {
			//delete interest page
			$contact_data['interest_page_id'] = 0;
			if($interest_page_id){
				db::exec('delete from sbs_interest_page where id='.$interest_page_id);
			}
		}
		
		db::update('sbs_contacts', $contact_data, 'id = '.$_POST['id']);
		feedback::add_success_msg("Contact updated!");
	}
	
	public function action_delete_contact(){
		db::update('sbs_contacts', array('status' => 'deleted'), 'id = '.$_POST['id']);
		cgi::redirect("sales/sbs_contacts");
	}
	
	public function action_contact_to_avv(){
		$contact = db::select_row("select * from sbs_contacts where id={$_GET['id']}", "assoc");
		$response = sales_lib::post_to_avv(array($contact));
		e($response);
	}
	
	public function view_sbs_contact(){
		
		if(empty($_GET['id'])){
			return;
		}
		
		$contact = db::select_row("Select * from sbs_contacts where id={$_GET['id']}", "assoc");
		$interest_page = array();
		if($contact['interest_page_id']){
			$interest_page = db::select_row("Select * from sbs_interest_page where id={$contact['interest_page_id']}", "assoc");
		}
		
		$status_options = array(
			'new',
			'incomplete',
			'called',
			'contacted'
		);
		
		$sbs_packages = array(
		    'None' => '',
		    'Starter' => 'starter',
		    'Core' => 'core',
		    'Premier' => 'premier'
		);
		
		$users = users::get_all_users();
		$user_options = '';
		$default_user = isset($interest_page['user_id']) ? $interest_page['user_id'] : user::$id;
		foreach($users as $u){
			$selected = ($default_user==$u['id']) ? ' selected' : '';
			$user_options .= "<option value='{$u['id']}'$selected>{$u['realname']}</option>";
		}
		
	?>
		<a href="<?php echo cgi::href("sales/sbs_contacts") ?>">back</a>
		<div id='sbs-view-contact'>
		
			<input name='id' value='<?php echo $contact['id'] ?>' type='hidden' />
		
			<h2>View Contact</h2> <br/>
		
			<div class='editable'>
			
				<div>
					<label>*name: </label>
					<input type="text" value="<?php echo $contact['name'] ?>" name='name' class="required">
				</div>
				
				<div>
					<label>*email: </label>
					<input type="text" value="<?php echo $contact['email'] ?>" name='email' class="required">
				</div>
				
				<div>
					<label>phone: </label>
					<input type="text" value="<?php echo $contact['phone'] ?>" name='phone'>
				</div>
				
				<div>
					<label>*company: </label>
					<input type="text" value="<?php echo $contact['company'] ?>" name='company' class="required">
				</div>
				
				<div>
					<label>url: </label>
					<input type="text" value="<?php echo $contact['url'] ?>" name='url'>
				</div>
				
				<div>
					<label>budget: </label>
					$<input type="text" value="<?php echo $contact['budget'] ?>" name='budget'>
				</div>
				
				<div>
					<label>status: </label>
					<select name="status" name='status'>
						<?php echo html::options($status_options, $contact['status']) ?>
					</select>
				</div>
			
			</div>
			
			
			<div id='stats'>
			
				<div>
					<b>source:</b> <?php echo $contact['source'] ?>
				</div>
				<div>
					<b>referer:</b> <?php echo $contact['referer'] ?>
				</div>
                                <div>
					<b>referring url:</b> <?php echo $contact['referring_url'] ?>
				</div>
				<div>
					<b>interests:</b> <?php echo $contact['interests'] ?>
				</div>
                                <div>
                                        <b>category:</b> <?php echo $contact['cat'] ?>
                                </div>
				<div>
					<b>created:</b> <?php echo date('F j, Y, g:i a', strtotime($contact['created'])) ?>
				</div>
				
				<div id='notes' style='margin-top: 20px;'>
					<b>Notes: </b><br />
					<textarea name='notes' style='width: 300px; height: 100px;' ><?php echo $contact['notes'] ?></textarea>
				</div>
			
			</div>
			
			<div class="clear"></div>
			
			<div id="package-info">
				
				<h3>Intent to Order</h3>
				
				<div>
					<label>Sales Contact</label>
					<select name="user_id">
						<?php echo $user_options ?>
					</select>
				</div>
				
				<div class="package">
					<h4>QuickList Packages</h4>
					<select class="package-select" name="ql[package]">
						<?php echo self::package_select($sbs_packages, $interest_page['ql_package']) ?>
					</select>
					<br />
					<label>cost</label>
					<input name="ql[cost]" class="cost" type="text" value="<?php echo $interest_page['ql_cost'] ?>" />
					<br />
					<label>setup fee</label>
					<input name="ql[setup_fee]" class="setup-fee" type="text" value="<?php echo $interest_page['ql_setup_fee'] ?>" />
				</div>
				
				<div class="package">
					<h4>SocialBoost Packages</h4>
					<select class="package-select" name="sb[package]">
						<?php echo self::package_select($sbs_packages, $interest_page['sb_package']) ?>
					</select>
					<br />
					<label>cost</label>
					<input name="sb[cost]" class="cost" type="text" value="<?php echo $interest_page['sb_cost'] ?>" />
					<br />
					<label>setup fee</label>
					<input name="sb[setup_fee]" class="setup-fee" type="text" value="<?php echo $interest_page['sb_setup_fee'] ?>" />
					<br />
					<label>fanpage</label>
					<input  name="sb[fanpage]" type="checkbox" value="1" <?php if($interest_page['sb_fanpage']) echo "checked='checked'"; ?> />
				</div>
				
				<div class="package">
					<h4>GoSEO Packages</h4>
					<select class="package-select" name="gs[package]">
						<?php echo self::package_select($sbs_packages, $interest_page['gs_package']) ?>
					</select>
					<br />
					<label>cost</label>
					<input name="gs[cost]" class="cost" type="text" value="<?php echo $interest_page['gs_cost'] ?>" />
					<br />
					<label>setup fee</label>
					<input name="gs[setup_fee]" class="setup-fee" type="text" value="<?php echo $interest_page['gs_setup_fee'] ?>" />
				</div>
				
				<div class="clear"></div>
				
				<div>
					<h4>Additional Data</h4>
					<label>Campaign Start Date</label>
					<input class="date" type="text" name="start_date" value="<?php echo $interest_page['start_date'] ?>" />
					<br />
					<label>Contract Length</label>
					<input type="number" name="contract_length" value="<?php echo $interest_page['contract_length']?>" min="0" max="12">
					months
					<br />
					<label>Waive Setup Date</label>
					<input class="date" type="text" name="other_date" value="<?php echo $interest_page['other_date'] ?>" />
					<br />
					<label>Details</label>
					<textarea name="details"><?php echo $interest_page['details'] ?></textarea>
				</div>
				
				<?php if($contact['interest_page_id']){ 
					echo "<a href='".$this->wpro_interest_page_link($contact['id'])."' target='_blank'>View Order Page</a>";
				} ?>
			
			</div>
			
			<div class="clear"></div>
			
			<input a0="action_edit_contact" type="submit" value="Save" name="submit" />

			<input a0="action_delete_contact" type="submit" value="Delete" name="delete" />

			<input a0t="action_contact_to_avv" type="submit" value="Push to AVV" name="avv" />
			
		</div>
		
	<?php
	
	}
	
	private static function package_select($packages, $default=''){
		$packages_options = "";
		foreach($packages as $label => $val){
			$selected = ($val==$default) ? ' selected' : '';
			$packages_options .= "<option value='$val'$selected>$label</option>";
		}
		return $packages_options;
	}
	
	private function wpro_interest_page_link($contact_id){
		return "http://".\epro\WPRO_DOMAIN.'/small-business-products/order-details/'.$contact_id;
	}
	
	public function sts_order_page(){
		$contact = db::select_row("Select * from sbs_contacts where id={$_POST['contact_id']}", "assoc");
		if($contact['interest_page_id']){
			$interest_page = db::select_row("Select * from sbs_interest_page where id={$contact['interest_page_id']}", "assoc");
			$user = db::select_row('select realname, username, phone_ext from users where id='.$interest_page['user_id'], "assoc");
			$r = array(
			    'contact' => $contact,
			    'interest_page' => $interest_page,
			    'user' => $user
			);
		} else {
			$r = 0;
		}
		
		echo json_encode($r);
	}
	
	public function sts_sbs_contact(){
		
		//db::dbg();
		unset($_POST['_sts_func_']);
		
		$_POST['created'] = date(util::DATE_TIME);
		
		$id="";
		if(isset($_POST['id'])){
			$id = $_POST['id'];
			unset($_POST['id']);
		}
		
		if(isset($_POST['phone'])){
			$_POST['phone'] = preg_replace("/[^0-9]/", "", $_POST['phone']);
		}
		
		if($id!=""){
			db::update('sbs_contacts', $_POST, 'id = '.$id);
		} else {
			$id = db::insert('sbs_contacts', $_POST);
			$_POST['id'] = $id;
		}
		
		if($_POST['status']=="new"){
			sales_lib::post_to_avv(array($_POST));
		}
		
		//return id
		echo json_encode($id);
		
	}
	
	
	public function avv_test(){
		
		if(util::is_dev()){
		
			$contact = db::select_row("
				select *
				from sbs_contacts
				WHERE id = 1001
				LIMIT 1
			", 'ASSOC');
			
			e($contact);
			
			$response = sales_lib::post_to_avv(array($contact));
			
			e($response);
		
		}
		
		
	}
	
	public function pre_output_set_commissions()
	{
		$type_to_dept = $this->get_pay_type_to_dept_map();
		$this->depts = array_filter(array_unique(array_values($type_to_dept)));
		sort($this->depts);
		$this->com_types = sales_client_info::$types;
	}
	
	/*
	 * some of these differ from how they are considered for accouting purposes
	 */
	protected function get_pay_type_to_dept_map()
	{
		$type_to_dept = client_payment_part::$part_types;
		foreach ($type_to_dept as $type => $dept) {
			if (strpos($type, 'Media ') === 0) {
				$type_to_dept[$type] = 'media';
			}
		}
		return $type_to_dept;
	}
	
	public function set_commissions()
	{
		if (!$this->is_user_leader())
		{
			$this->index();
			return;
		}
		util::load_rs('as');
		
		// make grid with commission types along the top, part types along the left
		?>
		<h1>Set Commissions</h1>
		<table>
			<thead>
				<tr>
					<!-- empty header for part types column along the left -->
					<th></th>
					<?php echo $this->set_commissions_ml_com_type_headers(); ?>
				</tr>
			</thead>
			<tbody>
				<?php echo $this->set_commissions_ml_part_types_grid(); ?>
				<tr>
					<td></td>
					<td colspan=5><input type="submit" a0="action_set_commissions_submit" value="Update" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function set_commissions_ml_com_type_headers()
	{
		$ml = '';
		foreach ($this->com_types as $type)
		{
			$ml .= '<th>'.$type.'</th>';
		}
		return $ml;
	}
	
	public function set_commissions_ml_part_types_grid()
	{
		$vals = db::select("
			select dept, com_type, percent
			from eppctwo.sales_commission
		", 'NUM', array(0), 1);
		
		$ml = '';
		foreach ($this->depts as $dept)
		{
			$ml_row = '';
			foreach ($this->com_types as $com_type)
			{
				$key = "{$dept}_{$com_type}";
				$val = $vals[$dept][$com_type];
				$ml_row .= '<td><input class="com_input" type="text" name="'.$key.'" id="'.$key.'" value="'.$val.'" /></td>'."\n";
			}
			$ml .= '
				<tr>
					<td><b>'.util::display_text($dept).'</b></td>
					'.$ml_row.'
				</tr>
			';
		}
		return $ml;
	}
	
	public function action_set_commissions_submit()
	{
		foreach ($this->depts as $dept)
		{
			foreach ($this->com_types as $com_type)
			{
				$key = "{$dept}_{$com_type}";
				$com_percent = ($_POST[$key]) ? $_POST[$key] : 0;
				$sc = new sales_commission(array(
					'dept' => $dept,
					'com_type' => $com_type,
					'percent' => $com_percent
				));
				$sc->put();
			}
		}
		feedback::add_success_msg('Commissions Updated');
	}
	
	public function hierarchy()
	{
		$this->print_hierarchy_new();
		$this->print_hierarchy_current();
	}
	
	private function print_hierarchy_current()
	{
		$hierarchs = db::select("
			select sh.id, u1.realname pname, u2.realname cname
			from eppctwo.sales_hierarch sh, eppctwo.users u1, eppctwo.users u2
			where
				sh.pid = u1.id &&
				sh.cid = u2.id
			order by pname asc, cname asc
		");
		
		$ml = '';
		for ($i = 0; list($hid, $parent_name, $child_name) = $hierarchs[$i]; ++$i)
		{
			$ml .= '
				<tr hid="'.$hid.'">
					<td><a href="" class="hierarch_delete_a">'.($i + 1).'</a></td>
					<td>'.$parent_name.'</td>
					<td>'.$child_name.'</td>
				</tr>
			';
		}
		?>
		<table>
			<thead>
				<tr>
					<th></th>
					<th>Parent</th>
					<th>Child</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
		</table>
		<input type="hidden" name="delete_hid" id="delete_hid" value="" />
		<?php
	}
	
	private function print_hierarchy_new()
	{
		$users = db::select("
			select id, realname
			from eppctwo.users
			where password <> ''
			order by realname asc
		");
		?>
		<fieldset>
			<legend>Add New</legend>
			<table>
				<tbody>
					<tr>
						<td>Parent</td>
						<td><?php echo cgi::html_select('parent', $users); ?></td>
					</tr>
					<tr>
						<td>Child</td>
						<td><?php echo cgi::html_select('child', $users); ?></td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" a0="action_new_hierarch" value="Submit" /></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div class="clr"></div>
		<?php
	}
	
	public function action_new_hierarch()
	{
		list($pid, $cid) = util::list_assoc($_POST, 'parent', 'child');
		
        // kw, 2013-02-15: doesn't actually break anything, and
        //  cyclical relationship was requested, so there you go
		// make sure inverse relationship does not already exist
		// $broken_universe = db::select_one("
		// 	select id
		// 	from eppctwo.sales_hierarch
		// 	where
		// 		pid = $cid &&
		// 		cid = $pid
		// ");
		// if ($broken_universe)
		// {
		// 	return feedback::add_error_msg('You sick bastard');
		// }
		
		// duplicate?
		$dup = db::select_one("
			select id
			from eppctwo.sales_hierarch
			where
				pid = $pid &&
				cid = $cid
		");
		if ($dup)
		{
			return feedback::add_error_msg('Parent/child relationship already exists');
		}
		
		db::insert("eppctwo.sales_hierarch", array(
			'pid' => $pid,
			'cid' => $cid
		));
		feedback::add_success_msg("New parent/child relationship added");
	}
	
	public function action_delete_hierarch()
	{
		$hid = $_POST['delete_hid'];
		list($pname, $cname) = db::select_row("
			select u1.realname pname, u2.realname cname
			from eppctwo.sales_hierarch sh, eppctwo.users u1, eppctwo.users u2
			where
				sh.id = $hid &&
				sh.pid = u1.id &&
				sh.cid = u2.id
		");
		
		db::exec("delete from eppctwo.sales_hierarch where id = $hid");
		feedback::add_success_msg("Deleted $pname -> $cname");
	}
}


?>