<?php

require_once(\epro\WPROPHP_PATH.'html.php');

define('SPEND_MAX_RESULTS_PER_PAGE', 50);
define('SPEND_DEFAULT_PLAN', 'Silver');

define('FACEBOOK_GUIDE_ID', 1);
define('TWITTER_GUIDE_ID', 2);

define('LIKE_AMOUNT', 100);

define('IMG_MAX_W', 110);
define('IMG_MIN_W', 60);
define('IMG_MAX_H', 80);
define('IMG_MIN_H', 60);

define('MAX_FILE_SIZE', 4194304);   // expressed in bytes
                                    //      10240 =  10KB
                                    //     102400 = 100KB
                                    //    1048576 =   1MB
                                    //   10485760 =  10MB


class mod_sb extends mod_sbs
{
	protected $m_name = 'sb';
        protected $order_info;
	
	public function get_menu()
	{
		return new Menu(
			array(
				new MenuItem('Queues'    		   ,'queues'),
				new MenuItem('$pend'           ,'spend'),
				new MenuItem('Calendars'       ,'calendars'),
				new MenuItem('Process Payments','process_payments'),
				new MenuItem('FB Data Upload'  ,'fb_data_upload'),
				new MenuItem('Settings'        ,'settings'),
				new MenuItem('Total Revenue'   ,'total_revenue')
			),
			'sbs/sb'
		);
	}
	
	public function pre_output()
	{
		parent::pre_output();
                if (!empty($_REQUEST['oid'])) g::$group_id = $_REQUEST['oid'];
                if (!empty($_REQUEST['cid'])) g::$client_id = $_REQUEST['cid'];

                if(g::$group_id){
                    $this->order_info = db::select_row("
                            select *
                            from sb_groups
                            where id = '".g::$group_id."'
                    ", 'ASSOC');
                    
                    
                    g::$client_id = $this->order_info['client_id'];
                }

                if(g::$client_id){
                    $this->client_info = db::select_row("
                            SELECT *
                            FROM contacts
                            WHERE client_id = '".g::$client_id."'
                    ", 'ASSOC');
                }

                if (g::$p2 == 'edit_ad' || g::$p2 == 'due_payments')
		{

                    cgi::add_js('jquery-ui.js');
                    cgi::add_js('jquery.ui.core.js');
                    cgi::add_js('jquery.ui.widget.js');
                    cgi::add_js('jquery.ui.draggable.js');
                    cgi::add_js('jquery.ui.droppable.js');
                    

		} else if(g::$p2 == 'export_excel'){
                    $this->call_member('export_excel');

                } else if(g::$p2 == 'download_images'){
                    $this->call_member('download_images');
                }
		
	}
	
	/*
	public function output()
	{
		$this->call_member(g::$p2, 'index');
		
	}
	*/
	
	public function display_index()
	{
	?>
		<h1>SocialBoost!</h1>
	<?php
	}

	protected function get_plan_cost($plan){
		switch($plan){
			case 'silver':
				return 99;
			case 'gold':
				return 247;
			case 'platinum':
				return 495;
			default:
				break;
		}
		return false;
	}

        protected function get_list_from_ad(&$list, $table, $col, $id){
            $list_array = db::select("SELECT {$col} FROM {$table} WHERE ad_id = {$id}");
            if(!empty($list_array)){
                $list = implode(', ',$list_array);
            } else {
                $list = '';
            }
        }

        protected function get_relationships_from_ad($id){
            $relationships = db::select("SELECT * FROM sb_ad_relationship WHERE ad_id = {$id}");
            $list_array = array();
            foreach($relationships as $key => $value){
                if($key!='ad_id'){
                    if($value == 1){
                        $list_array[] = $key;
                    }
                }
            }
            if(!empty($list_array)){
                $list = implode(', ',$list_array);
            }
            return $list;
        }

        protected function get_location_from_ad(&$list, $type, $id){
            $list_array = array();
            if($type == "city"){
                $cities = db::select("SELECT * FROM sb_ad_location WHERE ad_id = {$id} AND city <> ''", 'ASSOC');
                //cgi::print_r($cities);
                foreach($cities as $city){
                    $list_array[] = $city['city'].', '.$city['state'];
                }
                if(!empty($list_array)){
                    $list = implode(': ',$list_array);
                } else {
                    $list = '';
                }
            } else {
                $states = db::select("SELECT * FROM sb_ad_location WHERE ad_id = {$id} AND city = ''", 'ASSOC');
                foreach($states as $state){
                    $list_array[] = $state['state'];
                }
                if(!empty($list_array)){
                    $list = implode(', ',$list_array);
                } else {
                    $list = '';
                }
            }
        }

        public function get_excel_grp_data($grp_id, &$data){
             $group = db::select_row("
                    select *
                    from sb_groups
                    where id = '".$grp_id."'
            ", 'ASSOC');

            $ads = db::select("SELECT * FROM sb_ads WHERE group_id = ".$grp_id, 'ASSOC');

            foreach($ads as $ad){

                $img_path = '';
                if($ad['image']){
                    $img_path = $grp_id.'/'.$ad['image'];
                }

                self::get_list_from_ad($keywords, 'sb_keywords', 'text', $ad['id']);

                $ad_states = '';
                $ad_cities = '';
                $radius = '';
                if($ad['location_type']=='city'){
                    self::get_location_from_ad($ad_cities, 'city', $ad['id']);
                    $radius = $ad['radius'];
                } else if ($ad['location_type']=='state'){
                    self::get_location_from_ad($ad_states, 'state', $ad['id']);
                }

                $colleges = '';
                $majors = '';
                $college_year_min = '';
                $college_year_max = '';
                if($ad['education_status']=="College Grad" || $ad['education_status']=='College'){
                    self::get_list_from_ad($colleges, 'sb_ad_college', 'college', $ad['id']);
                    self::get_list_from_ad($majors, 'sb_ad_major', 'major', $ad['id']);

                    if($ad['education_status']=='college'){
                        $college_year_min = $ad['college_year_min'];
                        $college_year_max = $ad['college_year_max'];
                    }
                }

                self::get_list_from_ad($companies, 'sb_ad_company', 'company', $ad['id']);

                $relationships = self::get_relationships_from_ad($ad['id']);

                $data[] = array (
                    'campaign_name' => $group['id'],
                    'campaign_daily_budget' => $group['daily_budget'],
                    'campaign_lifetime_budget' => '',
                    'campaign_time_start' => $group['time_start'],
                    'campaign_time_stop' => $group['time_stop'],
                    'campaign_run_status' => $group['run_status'],
                    'ad_status' => $ad['status'],
                    'ad_name' => $group['id'].'_'.$ad['id'],
                    'bid_type' => $ad['bid_type'],
                    'max_bid' => $ad['max_bid'],
                    'title' => $ad['title'],
                    'body' => $ad['body_text'],
                    'image' => $img_path,
                    'link' => $ad['link'],
                    'country' => $ad['country'],
                    'state' => $ad_states,
                    'city' => $ad_cities,
                    'radius' => $radius,
                    'age_min' => $ad['min_age'],
                    'age_max' => $ad['max_age'],
                    'broad_age' => '',
                    'gender' => $ad['sex'],
                    'Likes and Interests' => $keywords,
                    'education_status' => $ad['education_status'],
                    'college' => $colleges,
                    'major' => $majors,
                    'college_year_min' => $college_year_min,
                    'college_year_max' => $college_year_max,
                    'company' => $companies,
                    'relationship_status' => $relationships,
                    'interested_in' => $ad['interested_in'],
                    'language' => $ad['language'],
                    'birthday' => $ad['birthday']
                );
                db::update(
                	"eppctwo.sb_ads",
                	array("edit_status" => 'current'),
                	"id = ".$ad['id']
                );
            }
            if($group['processed']!="canceled" && $group['processed']!="deleted"){
                db::exec("UPDATE sb_groups SET processed = 'processed', edit_status = 'current' WHERE id = ".$grp_id);
            } else {
                db::exec("UPDATE sb_groups SET edit_status = 'current' WHERE id = ".$grp_id);
            }
        }

        public function export_excel(){
            require_once(\epro\WPROPHP_PATH.'excel.php');

            $base_name = $title = "";

            $col_names = array (
                'campaign_name',
                'campaign_daily_budget',
                'campaign_lifetime_budget',
                'campaign_time_start',
                'campaign_time_stop',
                'campaign_run_status',
                'ad_status',
                'ad_name',
                'bid_type',
                'max_bid',
                'title',
                'body',
                'image',
                'link',
                'country',
                'state',
                'city',
                'radius',
                'age_min',
                'age_max',
                'broad_age',
                'gender',
                'Likes and Interests',
                'education_status',
                'college',
                'major',
                'college_year_min',
                'college_year_max',
                'company',
                'relationship_status',
                'interested_in',
                'language',
                'birthday',
                'connections',
                'excluded_connections',
                'creative_type',
                'link_object_id',
                'friends_of_connections'
            );

            $data = array();
            $data[0] = "";
            $data[1] = $col_names;


            if(isset($_POST['export_type'])){
                switch($_POST['export_type']){
                    case 'all':
                        $orders = db::select("SELECT id from sb_groups WHERE processed <> 'incomplete'");
                        break;
                    case 'new':
                        $orders = db::select("SELECT id from sb_groups WHERE processed = 'new'");
                        break;
                    case 'edited':
                        $orders = db::select("SELECT id from sb_groups WHERE edit_status = 'wpro' && processed <> 'incomplete'");
                        break;
                    case 'current':
                        $orders = db::select("SELECT id from sb_groups WHERE edit_status = 'current' && processed <> 'incomplete'");
                        break;
                    default:
                        feedback::add_error_msg("Invalid export type");
                        break;
                }
                $base_name = $title = "sb_bulk_upload";
                foreach($orders as $order_id){
                    $this->get_excel_grp_data($order_id, $data);
                }
            } else {
                $base_name = $title = "sb_".g::$group_id."_upload";
                $this->get_excel_grp_data(g::$group_id, $data);
            }
            

            $xls_info = array (
                "summary" => array (
                    "data" => $data,
                    "formatting" => array (
                        array(
                            'where' => array (
                                array(1,1),
                                array(0,36)
                            ),
                            'what' => array (
                                'font' => 'bold',
                                'alignment' => 'center'
                            )
                        )
                    ),
                   "images" => array ()
                )
            );


           //cgi::print_r($xls_info);
           excel_write($xls_info, $base_name, $title);
           //cgi::print_r($xls_info);
           exit;

        }

        public function download_images(){
            $url = 'http://'.\epro\WPRO_DOMAIN.'/account/sb_download_images/';
            if(isset(g::$group_id)){
                $url .= '?group_id='.g::$group_id;
            }
            header( 'Location: '.$url ) ;
        }

        public function upload_image(){
            if(isset($_FILES['new_image']['name'])){
                $_FILES['new_image']['contents'] = urlencode(base64_encode(file_get_contents($_FILES['new_image']['tmp_name'])));
                $_FILES['new_image']['group_id'] = g::$group_id;
                util::wpro_post('account', 'sb_upload_image', $_FILES['new_image']);
            }
        }

        public function delete_contact(){
            $sql = "UPDATE contacts SET status = 'deleted' WHERE client_id = ".g::$client_id;
            //echo $sql;
            db::exec($sql);

            $sql = "UPDATE sb_groups SET processed = 'deleted', edit_status = 'wpro', run_status = 'paused' WHERE client_id = ".g::$client_id;
            //echo $sql;
            db::exec($sql);
        }

         public function destroy_contact(){

            //find all groups and ads related to the client
            $sql = "SELECT * FROM sb_groups WHERE client_id = ".g::$client_id;
            $groups = db::select($sql,"ASSOC");

            foreach($groups as $group){
                $sql = "DELETE FROM sb_ads WHERE group_id = ".$group['id'];
                db::exec($sql);
                $sql = "DELETE FROM sb_groups WHERE id = ".$group['id']." LIMIT 1";
                db::exec($sql);
            }

            $sql = "DELETE FROM sbs_payment WHERE client_id = ".g::$client_id;
            db::exec($sql);

            //completly delete all records of this client
            $sql = "DELETE FROM clients WHERE id = ".g::$client_id." LIMIT 1";
            db::exec($sql);
            $sql = "DELETE FROM clients_sb WHERE client = ".g::$client_id." LIMIT 1";
            db::exec($sql);
            $sql = "DELETE FROM contacts WHERE client_id = ".g::$client_id." LIMIT 1";
            db::exec($sql);

            g::$p2 = 'clients';

         }

        public function activate_contact(){
            $sql = "UPDATE contacts SET status = 'active' WHERE client_id = ".g::$client_id;
            //echo $sql;
            db::exec($sql);

            $sql = "UPDATE sb_groups SET processed = 'processed', edit_status = 'wpro', run_status = 'actuve' WHERE client_id = ".g::$client_id;
            //echo $sql;
            db::exec($sql);
        }

        public function cancel_order(){
            $todays_date = date("Y-m-d");
            $cancel_date = ($_POST['cancel_date']=="") ? date("Y-m-d") : $_POST['cancel_date'];

            $today = strtotime($todays_date);
            $cancel = strtotime($cancel_date);

            if($cancel > $today) {
                $this->order_info['cancel_date'] = $cancel_date;
                $sql = "UPDATE sb_groups SET cancel_date = '".$cancel_date."' WHERE id = ".g::$group_id;
            } else {
                $this->order_info['processed'] = 'canceled';
                $this->order_info['run_status'] = 'paused';
                $this->order_info['edit_status'] = 'wpro';
                $this->order_info['cancel_date'] = $cancel_date;
                $sql = "UPDATE sb_groups SET processed = 'canceled', run_status = 'paused', edit_status = 'wpro', cancel_date = '".$cancel_date."' WHERE id = ".g::$group_id;
            }
            //echo $sql;
            db::exec($sql);

            //$sql = "DELETE FROM sb_payments WHERE group_id = ".g::$group_id." AND status = 'PENDING'";
            //db::delete($sql);
        }
        
        public function decline_order(){
			
			$cancel_date = date("Y-m-d");
			
			$this->order_info['processed'] = 'declined';
			$this->order_info['run_status'] = 'paused';
			$this->order_info['edit_status'] = 'wpro';
			$this->order_info['cancel_date'] = $cancel_date;
			$sql = "UPDATE sb_groups SET processed = 'declined', run_status = 'paused', edit_status = 'wpro', cancel_date = '".$cancel_date."' WHERE id = ".g::$group_id;
           
            db::exec($sql);
            feedback::add_success_msg('Order has been declined');
        }

        public function activate_order(){
            //db::dbg();
            $sql = "UPDATE sb_groups SET processed = 'processed', run_status = 'active', edit_status = 'wpro', cancel_date = '0000-00-00' WHERE id = ".g::$group_id;
            //echo $sql;
            db::exec($sql);
            $this->order_info['processed'] = 'processed';
            $this->order_info['run_status'] = 'active';
            $this->order_info['edit_status'] = 'wpro';
            $this->order_info['cancel_date'] = '0000-00-00';
            feedback::add_success_msg('Order has been activated');

            //send update to wpromote
            util::wpro_post('account', 'activate_sb_group', array('group_id' => g::$group_id));
        }

        public function reactivate_order(){
            $sql = "UPDATE sb_groups SET processed = 'processed', run_status = 'active', edit_status = 'wpro', cancel_date = '0000-00-00' WHERE id = ".g::$group_id;
            //echo $sql;
            db::exec($sql);
            $this->order_info['processed'] = 'processed';
            $this->order_info['run_status'] = 'active';
            $this->order_info['edit_status'] = 'wpro';
            $this->order_info['cancel_date'] = '0000-00-00';
            feedback::add_success_msg('Order has been reactivated');
        }
        
        public function renew_order(){
            $sql = "UPDATE sb_groups SET processed = 'new', run_status = 'active', edit_status = 'new', cancel_date = '0000-00-00' WHERE id = ".g::$group_id;
            //echo $sql;
            db::exec($sql);
            $this->order_info['processed'] = 'new';
            $this->order_info['run_status'] = 'active';
            $this->order_info['edit_status'] = 'new';
            $this->order_info['cancel_date'] = '0000-00-00';
            feedback::add_success_msg('Order has been renewed');
        }

        public function update_grp_run_status(){
            $run_status = $_REQUEST['run_status'];
            $sql = "UPDATE sb_groups SET run_status = '{$run_status}', edit_status = 'wpro' WHERE id = ".g::$group_id;
            db::exec($sql);
            feedback::add_success_msg('Run status had been set to '.$run_status);
        }

        public function update_ad_run_status(){
            $run_status = $_REQUEST['run_status'];
            $ad_id = $_REQUEST['selected_ad'];
            $sql = "UPDATE sb_ads SET status = '{$run_status}', edit_status = 'wpro' WHERE id = ".$ad_id;
            //echo $sql;
            db::exec($sql);
            $this->update_grp_status(g::$group_id);
            feedback::add_success_msg('Run status had been set to '.$run_status);
        }

        public function update_order(){

            
            //iterate through each ad that is posted and insert or update the ads table
            foreach($_POST['ads'] as $ad){

                $ad_id = $ad['id'];

                $update_vars = array(
                    'group_id' => g::$group_id,
                    'title' => $ad['title'],
                    'image' => $ad['image'],
                    'body_text' => $ad['body_text'],
                    'location' => $ad['location'],
                    'min_age' => $ad['min_age'],
                    'max_age' => $ad['max_age'],
                    'sex' => $ad['sex'],
                    'client_creates' => 0,
                    'create_date' => strftime("%Y-%m-%d %H:%M:%S", time())
                );

                //check if the ad already has an id
                if(!empty($ad_id)){

                   //we do an update on that ad
                   $where = 'id='.$ad_id;
                   db::update('sb_ads', $update_vars, $where);

                //if the ad has no id set, its new and must be inserted
                } else {

                    $ad_id = db::insert('sb_ads', $update_vars);

                }

                //now we check the keywords that were submitted with the ad
                foreach($ad['keywords'] as $keyword){

                    $keyword_id = $keyword['id'];

                    $update_keyword_vars = array(
                        'group_id' => g::$group_id,
                        'ad_id' => $ad_id,
                        'text' => $keyword['text']
                    );

                     //check if the ad already has an id
                    if(!empty($keyword_id)){

                       //we do an update on that ad
                        $where = 'id='.$keyword_id;
                        if(!empty($keyword['text'])){
                            db::update('sb_keywords', $update_keyword_vars, $where);
                        } else {
                            db::exec('DELETE FROM sb_keywords WHERE '.$where.' LIMIT 1');
                        }

                    //if the ad has no id set, its new and must be inserted
                    } else {

                        if(!empty($keyword['text'])){
                            $keyword_id = db::insert('sb_keywords', $update_keyword_vars);
                        }

                    }

                }

            }

            feedback::add_success_msg('Your ads have been updated.');

            
        }

        public function save_cc(){
           $update_pairs = array();
           
           //print_r($_POST['cc']);
           
           foreach($_POST['cc'] as $field => $value){
               if($field == "cc_number" || $field == "cc_code"){
                   if(is_numeric($value) || util::is_dev()){
                       $value = db::escape(util::encrypt($value));
                   } else {
                       continue;
                   }
               }
               $update_pairs[] = "{$field} = '{$value}'";
           }
           $update_str = implode(", ", $update_pairs);
           
           
           $sql = "UPDATE ccs SET {$update_str} WHERE id=".$_POST['cc_select'];
           
           db::exec($sql);
        }
        
        public function delete_cc(){
           $sql = "UPDATE ccs SET status = 'Inactive' WHERE id=".$_POST['cc_select'];
           db::exec($sql);
           
           cgi::redirect('sbs/sb/billing_info?cid='.$_POST['cid']);
        }

        public function display_edit_cc(){
            require(\epro\COMMON_PATH.'billing.php');

            $cc_types = array(
                "Visa" => "visa",
                "MasterCard" => "mastercard",
                "AmEx" => "amex",
                "Discover" => "discover"
            );

            $cc = db::select_row("Select * FROM ccs WHERE id = {$_POST['cc_select']}", "ASSOC");
            if(empty($cc)){
                exit("Credit card not found!");
            }
            $cc_display = billing::cc_get_display($_POST['cc_select']);

        ?>
                <div id="breadcrumb" style="margin-bottom: 10px;">
                    <ul>
                        <li><a href="/sbs/sb/client_info/?cid=<?php echo g::$client_id; ?>">Client Profile</a> &raquo;</li>
                        <li><a href="/sbs/sb/billing_info/?cid=<?php echo g::$client_id; ?>">Billing</a> &raquo;</li>
                    </ul>
                    <div class="clear"></div>
                </div>
                
                
                <div style="margin: 15px 0;">
                        <input type="hidden" value="<?php echo g::$client_id; ?>" name="cid" />
                        <input type="hidden" value="<?php echo $_POST['cc_select']; ?>" name="cc_select" />

                        <div>
                            <label>Name</label>
                            <input type="text" id="name" name="cc[name]" value="<?php echo $cc['name']; ?>" />
                        </div>
                        <div>
                            <label>Type</label>
                            <select name="cc[cc_type]" id="cc_type">
                                <?php
                                    foreach($cc_types as $label => $value){
                                        echo "<option value='{$value}'";
                                        if($value == $cc['cc_type']){
                                            echo " selected";
                                        }
                                        echo ">{$label}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label>Number</label>
                            <input type="text" id="cc_number" name="cc[cc_number]" value="<?php echo $cc_display['cc_number']; ?>" />
                        </div>
                        <div>
                            <label>Code</label>
                            <input type="text" id="cc_code" name="cc[cc_code]" value="<?php echo $cc_display['cc_code']; ?>" />
                        </div>
                        <div>
                            <label>Exp Date</label>
                            <select name="cc[cc_exp_month]">
                                <?php for($i=1; $i<=12; $i++){
                                    $month_num = str_pad($i, 2, '0', STR_PAD_LEFT);
                                    echo "<option value='{$month_num}'";
                                    if($month_num == $cc['cc_exp_month']){
                                        echo " selected";
                                    }
                                    echo ">{$month_num}</option>";
                                }
                                ?>
                            </select>
                            <select name="cc[cc_exp_year]">
                                <?php for ($i = 0, $cur_year = date('Y'); $i < 10; ++$i, ++$cur_year){
                                    echo "<option value='{$cur_year}'";
                                    if($cur_year == $cc['cc_exp_year']){
                                        echo " selected";
                                    }
                                    echo ">{$cur_year}</option>";
                                }
                                ?>
                            </select>
                        </div>
                </div>

                <input type="submit" value="Save Changes" a0="save_cc" />
                <input type="submit" value="Delete" a0="delete_cc" />
        <?php
        }

        public function display_billing_info(){

            require_once(\epro\COMMON_PATH.'billing.php');
            
            $ccs = db::select("SELECT * FROM ccs WHERE status = 'Active' && foreign_table = 'clients' && foreign_id = ".g::$client_id, "ASSOC");
            $cc_options = "";
            foreach($ccs as $cc){
                $cc_display = billing::cc_get_display($cc['id']);
                //print_r($cc_display);
                $cc_options .= "<option value='{$cc['id']}'>{$cc_display['name']} : {$cc_display['cc_number']}</option>";
            }

            $payments = db::select("
                SELECT *
                FROM sbs_payment
                WHERE client_id = ".g::$client_id." AND department = 'sb'
                ORDER BY id DESC;
            ", 'ASSOC');

            $groups = db::select("
                SELECT *
                FROM sb_groups
                WHERE client_id = ".g::$client_id."
                ORDER BY display_name ASC;
            ", 'ASSOC');

            $group_options = "";
            $upcoming_payments = "";
            foreach($groups as $group){
							$trial_text = ($this->is_trial_first_payment($group)) ? ' (Trial Payment)' : '';
                $group_options .= "<option value='{$group['id']}'>{$group['display_name']}{$trial_text}</option>";
                
                //set inc payemts for each group (amt, date)
                $upcoming_payments .= "<tr>";
                $upcoming_payments .= "	<td>$".number_format(sbs_lib::get_recurring_amount('sb', $group['plan'], $group['pay_option']), 2)."</td>";
                $upcoming_payments .= "	<td>".$group['next_bill_date']."</td>";
                $upcoming_payments .= "</tr>";
            }
            
            $payment_out = "";
            foreach($payments as $payment){
                if($payment['pay_method']=="cc"){
                    $cc_display = billing::cc_get_display($payment['pay_id']);
                } else {
                    $cc_display['cc_number'] = '----------';
                    $cc_display['name'] = '----------';
                }
                $group_name = db::select_one("SELECT display_name FROM sb_groups WHERE id = {$payment['account_id']}");
                $payment_out .= "<tr>";
                $payment_out .= "<td>$".number_format($payment['amount'],2)."</td>";
                $payment_out .= "<td>".$group_name."</td>";
                $payment_out .= "<td>".$cc_display['cc_number']."</td>";
                $payment_out .= "<td>".$cc_display['name']."</td>";
                $payment_out .= "<td>".$payment['type']."</td>";
                $payment_out .= "<td>".$payment['d']."</td>";
                $payment_out .= "<td>".$payment['notes']."</td>";
                $payment_out .= "</tr>";
            }

        ?>
                <div id="billing">
            
                <input type='hidden' name='cid' value="<?php echo g::$client_id; ?>">

                <div id="breadcrumb">
                    <ul>
                        <li><a href="/sbs/sb/client_info/?cid=<?php echo g::$client_id; ?>">Client Profile</a> &raquo;</li>
                    </ul>
                    <div class="clear"></div>
                </div>

                <h2><span>Client: </span><?php echo $this->client_info['name'] ?></h2>

                <h3>Edit Credit Card Info</h3>
                <div id="edit-cc-form">
                    <label>Select Card</label>
                    <select id="cc_select" name="cc_select">
                        <option value="">---------</option>
                        <?php echo $cc_options; ?>
                    </select>
                </div>
                

                <h3>Charge/Refund Client</h3>
                <div id="charge-form">
                    
                    <div>
                        <label>Charge Type</label>
                        <select id="charge_type" name="charge_type">
                            <option value="SALE">CHARGE</option>
                            <option value="CREDIT">REFUND</option>
                        </select>
                    </div>
                        <div>
                                <label>Credit Card</label>
                                <select id="cc" name="cc_id">
                                        <?php echo $cc_options; ?>
                                </select>
                        </div>
                    <div>
                        <label>Group Name</label>
                        <select id="payment_group" name="payment_group">
                            <?php echo $group_options; ?>
                        </select>
                    </div>
                    <div>
                        <label>Amount</label>
                        $<input type="text" id="payment_amount" name="payment_amount" value="0.00"/>
                    </div>
                    <div>
                        <label>Notes</label>
                        <input type="text" id="payment_notes" name="payment_notes" />
                    </div>
                    <div id="payment-submit-wrap">
                        <input type="submit" id="payment-submit" a0="payment_submit" value="Submit Payment" />
                    </div>
                </div>
                
                
                <h3>Edit Billing Date (only use prior to reoccuring payments)</h3>
                <div id="edit-billing-date-form">
					<div>
						<label>Select Group</label>
						<select id="group_select" name="billing_date[group_id]">
							<?php echo $group_options; ?>
						</select>
                    </div>
                    <div>
						<label>Select New Date</label>
						<input class="date_input" type="text" name="billing_date[date]" value="" />
                    </div>
                    <div id="billing-date-submit-wrap">
                        <input type="submit" id="billing-date-submit" a0="billing_date_submit" value="Submit New Date" />
                    </div>
                </div>
                
                
                <h3>Upcoming Payments</h3>
				<table>
					<thead>
						<tr>
							<th>Amount</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
						<?php echo $upcoming_payments ?>
					</tbody>
				</table>

                <h3>Payment History</h3>
                 <table id="pay_table">
                    <thead id="pay_thead">
                        <tr>
                            <th>Amount</th>
                            <th>Group Name</th>
                            <th>Card Number</th>
                            <th>Billing Name</th>
                            <th>Type</th>
                            <th>Paid Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="pay_tbody">
                    <?php
                        echo $payment_out;
                    ?>
                    </tbody>
                </table>

                </div>
                
        <?php
        }
        
        function billing_date_submit(){
			$group_id = $_POST['billing_date']['group_id'];
			$date = $_POST['billing_date']['date'];
			
			//db::dbg();
			$group = db::select_row("
                SELECT *
                FROM sb_groups
                WHERE id = ".$group_id
            , 'ASSOC');
			
			list($year, $month, $day) = explode('-', $date);
			
			list($months_paid, $months_free) = sbs_lib::get_pay_option_months($group['pay_option']);
			
			if ($group['trial_length']){
				$next_bill_date = date(util::DATE, strtotime("$date +".($group['trial_length'] + 1)." days"));
				$bill_day = date('j');
			} else {
				$next_bill_date = util::delta_month($date, $months_paid + $months_free, $day);
				$bill_day = ltrim(substr($next_bill_date, 8), '0');
			}
			
			db::update('sbs_payment', array(
				'd' => $date
			), 'account_id = '.$group_id.' AND type = "Order" AND department = "sb"');
			
			
			$data = array(
				'signup_date' => $date,
				'bill_day' => $day,
				'first_bill_date' => $date,
				'last_bill_date' => $date,
				'next_bill_date' => $next_bill_date
			);
			db::update('sb_groups', $data, 'id = '.$group_id);
		}

		private function is_trial_first_payment($group)
		{
			$num_payments = db::select_one("select count(*) from eppctwo.sbs_payment where department = 'sb' && account_id = '{$group['id']}'");
			return ($group['trial_length'] && ($num_payments == 0 || ($num_payments == 1 && $group['is_likepage'] && $group['trial_auth_amount'])));
		}
	
		public function payment_submit()
		{
                        util::load_lib('billing');
                        #db::dbg();
                        #billing::dbg();

                        $gid = $_POST['payment_group'];
                        $group = db::select_row("SELECT * FROM sb_groups WHERE id = {$gid}", 'ASSOC');
                        $cc = db::select_row("SELECT * FROM ccs WHERE id = {$_POST['cc_id']}", 'ASSOC');
                        
                        //print_r($cc);
                        //return;

                        // not a trial first payment if not a sale
                        $is_trial_first_payment = ($_POST['charge_type'] == 'SALE' && $this->is_trial_first_payment($group));

                        // trial auth amount was empty when trials first started, and auth was for full amount of order.
                        // so if trial auth amount is empty, issue post auth
                        // also make sure it's not a refund
                        if ($is_trial_first_payment && !$group['trial_auth_amount'])
                        {
                                $amount = $group['trial_amount'];
                                $charge_success = billing::post_auth($group['trial_auth_id']);
                        }
                        else
                        {
                                $amount = ($is_trial_first_payment) ? $group['trial_amount'] : $_POST['payment_amount'];
                                $charge_success = billing::charge($cc['id'], $amount, $_POST['charge_type']);
                        }
		
                        // add payment to sb_payments
                        if ($charge_success)
                        {
                                if ($is_trial_first_payment)
                                {
                                        $status = "PAID";
                                        $payment_type == 'Order';
                                }
                                else if ($_POST['charge_type']=="SALE")
                                {
                                        $status = "PAID";
                                        $payment_type = "Recurring";
                                }
                                else
                                {
                                        $status = "REFUNDED";
                                        $amount *= -1;
                                        $payment_type = "Refund Old";
                                }

                                //db::dbg();
                                $payment_id = db::insert("sbs_payment", array(
                                        'client_id' => $_POST['cid'],
                                        'account_id' => $_POST['payment_group'],
                                        'pay_id' => $cc['id'],
                                        'pay_method' => 'cc',
                                        'd' => date(util::DATE),
                                        't' => date(util::TIME),
                                        'department' => 'sb',
                                        'type' => $payment_type,
                                        'amount' => (float)$amount,
                                        'do_charge' => 1,
                                        'notes' => $_POST['payment_notes']
                                ));

                                if ($payment_type == 'Recurring' || $payment_type == 'Order')
                                {
                                        //update group for next payment
                                        list($months_paid, $months_free) = sbs_lib::get_pay_option_months($group['pay_option']);

                                        $last_bill_date = $group['next_bill_date']; //the previous next date becomes the last date

                                        $updates = array(
                                                'latest_payment_status' => 'APPROVED',
                                                'last_bill_date' => $last_bill_date
                                        );

                                        if ($is_trial_first_payment)
                                        {
                                                $bill_day = ltrim(substr($group['next_bill_date'], 8), '0');
                                                $updates['bill_day'] = $bill_day;

                                                util::wpro_post('account', 'sb_update_group', array(
                                                        'id' => $_POST['payment_group'],
                                                        'bill_day' => $bill_day
                                                ));
                                        }
                                        else
                                        {
                                                $bill_day = $group['bill_day'];
                                        }
                                        $updates['next_bill_date'] = util::delta_month($last_bill_date, $months_paid + $months_free, $bill_day);
                                        db::update("sb_groups", $updates, "id={$_POST['payment_group']}");
                                }
                                $success = $_POST['charge_type']." Successfull!!";
                                feedback::add_success_msg($success);
                        }
                        else
                        {
                                $error  = $_POST['charge_type']." ERROR: ";
                                $error .= billing::get_error(false);
                                feedback::add_error_msg($error);
                        }
                }

        public function ajax_submit_payment(){

            require(\epro\COMMON_PATH.'billing.php');
            #db::dbg();
            #billing::dbg();
                   
            // try to charge the clients credit cards
            $ccs = db::select("SELECT * FROM ccs WHERE foreign_id = {$_POST['cid']} AND status = 'Active'", "ASSOC");
            $group = db::select_row("SELECT * FROM sb_groups WHERE id = {$_POST['gid']}", "ASSOC");
            
            $is_trial_first_payment = $this->is_trial_first_payment($group);
            if(!empty($ccs)){
				
                foreach($ccs as $cc){
					
										// trial auth amount was empty when trials first started, and auth was for full amount of order.
										// so if trial auth amount is empty, issue post auth
										if ($is_trial_first_payment && !$group['trial_auth_amount'])
										{
											$amount = $group['trial_amount'];
											$charge_success = billing::post_auth($group['trial_auth_id']);
											$type = 'Order';
										}
										else
										{
											$amount = ($is_trial_first_payment) ? $group['trial_amount'] : $_POST['amount'];
											$charge_success = billing::charge($cc['id'], $amount);
											$type = 'Recurring';
										}
                    

                    // add payment to sb_payments
                    if($charge_success){
						
                        db::insert("sbs_payment", array(
							'client_id' => $_POST['cid'],
							'account_id' => $_POST['gid'],
                            'pay_id' => $cc['id'],
                            'pay_method' => 'cc',
                            'd' => date(util::DATE),
                            't' => date(util::TIME),
                            'department' => 'sb',
                            'type' => $type,
                            'pay_option' => $group['pay_option'],
                            'amount' => $amount,
                            'do_charge' => 1,
                        ));
                        
                        list($months_paid, $months_free) = sbs_lib::get_pay_option_months($group['pay_option']);
                        
                        $last_bill_date = $group['next_bill_date']; //the previous next date becomes the last date
                        
			$updates = array(
				'latest_payment_status' => 'APPROVED',
				'last_bill_date' => $last_bill_date
			);
			if ($is_trial_first_payment)
			{
				$bill_day = ltrim(substr($group['next_bill_date'], 8), '0');
				$updates['bill_day'] = $bill_day;
				
				util::wpro_post('account', 'sb_update_group', array(
					'id' => $_POST['gid'],
					'bill_day' => $bill_day
				));
			}
			else
			{
				$bill_day = $group['bill_day'];
			}
			$updates['next_bill_date'] = util::delta_month($last_bill_date, $months_paid + $months_free, $bill_day);

			db::update("sb_groups", $updates, "id={$_POST['gid']}");
			break;
		}
		else
		{
			$error = billing::get_error(false);
			db::update_array("sb_groups", array(
				'latest_payment_status' => 'DECLINED'
			), "id={$_POST['gid']}");
		}


                }
              
            } else {
                //no credit cards were found
                $charge_success = false;
                $error = "No card found for this client";
            }

            // return results
            $response = array(
                'success' => $charge_success,
                'payment_date' => date(util::DATE),
                'payment_amount' => $_POST['amount'],
                'err_msg' => $error
            );
            echo json_encode($response);
        }

        public function export(){
            $total_orders = db::select("SELECT * from sb_groups");
            $new_orders = db::select("SELECT * from sb_groups WHERE processed = 'new'");
            $edited_orders = db::select("SELECT * from sb_groups WHERE edit_status = 'wpro'");
            $current_orders = db::select("SELECT * from sb_groups WHERE edit_status = 'current'");

        ?>
            <h2>Bulk Export</h2>
            <p>Select what to export</p>
            <select id="export_type" name="export_type" >
                <option value="edited">Ready For Facebook Orders</option>
                <option value="current">In Facebook Orders</option>
                <option value="all">All Orders</option>
            </select>
            <input type="submit" id="export_many" value="Export Excel"/>
            <input type="submit" id="dl_images_many" value="Download All Images Zip" a0="download_images" />
            
            <h3>Order Stats</h3>
            <p>New orders: <?php echo count($new_orders); ?></p>
            <p>Ready for facebook orders: <?php echo count($edited_orders); ?></p>
            <p>In Facebook: <?php echo count($current_orders); ?></p>
            <p>Total orders: <?php echo count($total_orders); ?></p>
        <?php
        }

        public function update_settings(){
            $settings = $_POST['settings'];

            foreach($settings as $key => $value){
                $attribute_pairs[] = "{$key} = '{$value}'";
            }

            $sql  = "UPDATE sb_settings SET ";
            $sql .= join(", ", $attribute_pairs);
            $sql .= " WHERE name = 'default'";

            db::exec($sql);

            feedback::add_success_msg('Default settings updated');
        }

        public function update_files(){
            $files = $_POST['files'];

            foreach($files as $file_id => $file){

                foreach($file as $key => $value){
                    $attribute_pairs[] = "{$key} = '{$value}'";
                }

                $sql  = "UPDATE files SET ";
                $sql .= join(", ", $attribute_pairs);
                $sql .= " WHERE id = {$file_id}";

                db::exec($sql);

            }
            feedback::add_success_msg('Default settings updated');
        }
        
        public function import()
        {
                if (array_key_exists('data_submit', $_POST)) $this->import_data();
                ?>
                <h2>Import Data</h2>
                <input type="file" name="data" />
                <input type="submit" name="data_submit" value=" Go " />
                <?php
        }
				
        public function import_data()
        {
                require(\epro\WPROPHP_PATH.'csv.php');

                $ca_to_cl = db::select("
                        select id, client_id
                        from eppctwo.sb_groups
                ", 'NUM', 0);

                csv::read($data, $_FILES['data']['tmp_name']);

                // inspect first line to make sure columns are where we expect
                if (trim(implode(',', $data[0])) != 'Date,Campaign Name,Campaign ID,Ad Name,Ad ID,Impressions,Social Impressions,Social %,Clicks,Social Clicks,CTR,Social CTR,Actions,Action Rate,Conversions,Cost Per Conversion,CPC,CPM,Spent (USD),Unique Impressions,Unique Clicks,Unique CTR')
                {
                        feedback::add_error_msg('Data does not appear to be in correct format. Please double check the file and contact an administrator if problem persists.');
                        return false;
                }

                $upload_count = 0;
                for ($i = 1, $num_rows = count($data); $i < $num_rows; ++$i)
                {
                        list($date, $ca_name, $ca_id, $ad_name, $ad_id, $imps, $social_imps, $social_per, $clicks, $social_clicks, $ctr, $social_ctr, $acts, $act_rate, $convs, $conv_rate, $cpc, $cpm, $cost, $unique_imps, $unique_clicks, $unique_ctr) = $data[$i];

                        // convert to standard date format
                        $date = date(util::DATE, strtotime($date));

                        list($e2_ca_id, $e2_ad_id) = explode('_', $ad_name);

                        // format of the ad name for our uploads is [group_id]_[ad_id]
                        // make sure this row is from one of our uploads
                        if (!is_numeric($e2_ca_id) || !is_numeric($e2_ad_id)) continue;

                        $cl_id = (array_key_exists($ca_name, $ca_to_cl)) ? $ca_to_cl[$ca_name] : '';

                        // names are IDs!?!?
                        $r = db::insert_update("eppctwo.sb_data_ads", array('ad_id', 'd'), array(
                                'client_id' => $cl_id,
                                'campaign_id' => $e2_ca_id,
                                'ad_id' => $e2_ad_id,
                                'd' => $date,
                                'imps' => $imps,
                                'clicks' => $clicks,
                                'convs' => $convs,
                                'cost' => $cost
                        ), false);
                        if ($r) ++$upload_count;
                }

                feedback::add_success_msg($i.' rows successfully processed, '.$upload_count.' updates');
        }
				
        public function display_settings(){
            $sql = "SELECT * FROM sb_settings WHERE name = 'default'";
            $settings = db::select_row($sql, 'ASSOC');

            $files = db::select("SELECT * FROM files ORDER BY id ASC", "ASSOC");

        ?>
            <h2>Settings</h2>
            
            <h3>Default Bid Settings</h3>
            <label>Max Bid: </label>
            <input name="settings[max_bid]" id="max_bid" value="<?php echo $settings['max_bid']; ?>" type="text" /><br>

            <label>Daily Budget: </label>
            <span>Silver</span>
            <input name="settings[silver_daily_budget]" id="daily_budget" value="<?php echo $settings['silver_daily_budget']; ?>" type="text" />
            <span>Gold</span>
            <input name="settings[gold_daily_budget]" id="daily_budget" value="<?php echo $settings['gold_daily_budget']; ?>" type="text" />
            <span>Platinum</span>
            <input name="settings[platinum_daily_budget]" id="daily_budget" value="<?php echo $settings['platinum_daily_budget']; ?>" type="text" />
            <br>
            <input type="submit" a0="update_settings" value="Update Settings" />

            <div id="edit_guides">
            <h3>Guides</h3>
            <?php
                $output = "";
                foreach($files as $file){
                    $output .= "<div class='guide_block'>";
                    $output .= "<label>Name: </label>";
                    $output .= "<input name=\"files[{$file['id']}][name]\" class=\"file_name\" value=\"{$file['name']}\" type=\"text\" />";
                    $output .= "<label>Description: </label>";
                    $output .= "<textarea name=\"files[{$file['id']}][description]\" class=\"file_description\" rows=4 cols=30 >{$file['description']}</textarea>";
                    $output .= "<label>Last Updated: </label>";
                    $output .= "<input type=\"text\" name=\"files[{$file['id']}][date]\" class=\"date_input\" value=\"{$file['date']}\"><br>";
                    $output .= "</div>";
                }
                echo $output;
            ?>
            <br>
            <input type="submit" a0="update_files" value="Update Files" />
            </div>


            
        <?php
        }

        public function save_client_info(){
            $contact_data = $_POST['contact'];
            if($_POST['password']!=""){
                $contact_data['password'] = md5($_POST['password']);
            }
            //db::dbg();
            if(db::update("contacts", $contact_data, "client_id=".g::$client_id)){
                //send changes to wpromote
                $contact_data['client_id'] = g::$client_id;
                util::wpro_post('account', 'update_client_info', $contact_data);
                feedback::add_success_msg('Client info has been updated!');
            } else {
                feedback::add_error_msg('No chages were made!');
            }
        }

        public function save_billing_info(){
            $contact_data = $_POST['contact'];
            if($_POST['password']!=""){
                $contact_data['password'] = md5($_POST['password']);
            }
            if(db::update("contacts", $contact_data, "client_id=".g::$client_id)){
                feedback::add_success_msg('Client info has been updated!');
            } else {
                feedback::add_error_msg('No chages were made!');
            }
        }

        public function display_edit_client_info(){
            
            $contact = db::select_row("SELECT * FROM contacts WHERE client_id = ".g::$client_id, "ASSOC");
        ?>

            <input type='hidden' name='cid' value="<?php echo g::$client_id; ?>">

            <div id="breadcrumb">
                <ul>
                    <li><a href="/sbs/sb/clients">All Clients</a> &raquo;</li>
                    <li><a href="/sbs/sb/client_info/?cid=<?php echo g::$client_id;?>">Client Profile</a> &raquo;</li>
                </ul>
                <div class="clear"></div>
            </div>

            <h2><span>Client: </span><?php echo $this->client_info['name'] ?></h2>

            <div id="client_edit_fields">

                <h3>Login Info</h3>
                 <div>
                    <label>Email</label>
                    <input type="text" id="email" name="contact[email]" value="<?php echo $contact['email']; ?>" />
                </div>
                <div>
                    <label>Reset Password</label>
                    <input type="text" id="password" name="password" value="" />
                </div>

                <h3>Contact Info</h3>
                <div>
                    <label>Name</label>
                    <input type="text" id="name" name="contact[name]" value="<?php echo $contact['name']; ?>" />
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" id="phone" name="contact[phone]" value="<?php echo $contact['phone']; ?>" />
                </div>
                <div>
                    <label>Zip</label>
                    <input type="text" id="zip" name="contact[zip]" value="<?php echo $contact['zip']; ?>" />
                </div>
                <div>
                    <label>Country</label>
                    <input type="text" id="country" name="contact[country]" value="<?php echo $contact['country']; ?>" />
                </div>

                <input type="submit" value="Save Contact" a0="save_client_info" />
                <a href="/sbs/sb/client_info/?cid=<?php echo g::$client_id;?>">Cancel</a>

            </div>
            
        <?php 
        }

        public function update_user_files(){
            //remove all current sb_client_files
            $sql = "DELETE from sb_client_files WHERE client_id=".g::$client_id;
            db::exec($sql);

            if(!empty($_POST['client_files'])){
                foreach($_POST['client_files'] as $k => $file_id){
                    $data = array("client_id"=>g::$client_id, "file_id"=>$file_id);
                    db::insert("sb_client_files",$data);
                }
            }

            feedback::add_success_msg('Client file access has been updated!');

        }

        public function display_client_info(){
            $groups = db::select("
			SELECT *
			FROM sb_groups WHERE client_id = '".g::$client_id."'
			ORDER BY d desc, t desc
		", 'ASSOC');

            $files = db::select("SELECT * FROM files", 'ASSOC');

            $tbody = "";
            //Group Name | OID | Wpro Status | FB Status | Budget | Package | Pay Plan | Latest Payment Status
            foreach($groups as $group){
                if($group){
                    $tbody .= "<tr oid=\"{$group['id']}\">";
                        $tbody .= "<td>{$group['display_name']}</td>";
                        $tbody .= "<td>{$group['oid']}</td>";
                        $tbody .= "<td>{$group['processed']}</td>";
                        $tbody .= "<td>{$group['run_status']}</td>";
                        $tbody .= "<td>{$group['daily_budget']}</td>";
                        $tbody .= "<td>{$group['plan']}</td>";
                        $tbody .= "<td>{$group['pay_option']}</td>";
                        $tbody .= "<td>{$group['latest_payment_status']}</td>";
                        $tbody .= "<td>{$group['wpro_comments']}</td>";
                    $tbody .= "</tr>";
                }
            }

        ?>
            <input type='hidden' name='cid' value="<?php echo g::$client_id; ?>">

            <div id="breadcrumb">
                <ul>
                    <li><a href="/sbs/sb/clients">All Clients</a> &raquo;</li>
                </ul>
                <div class="clear"></div>
            </div>

            <h2><span>Client: </span><?php echo $this->client_info['name'] ?></h2>

            <div id="client_details">
                <div class="detail">
                   <p>
                        <span class="detail_title">Client Name</span>
                    <?php
                        echo $this->client_info['name'];
                    ?>
                    </p>
                </div>
                <div class="detail">
                   <p>
                        <span class="detail_title">Email</span>
                    <?php
                        echo $this->client_info['email'];
                    ?>
                    </p>
                </div>
                <div class="detail">
                   <p>
                        <span class="detail_title">Phone</span>
                    <?php
                        echo $this->client_info['phone'];
                    ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Login Status</span>
                    <?php
                        echo $this->client_info['status'];
                    ?>
                    </p>
                </div>
                <?php
                if($this->client_info['status'] == 'inactive'){
                    $activation_link = \epro\WPRO_DOMAIN."/account/password-set/?k=".$this->client_info['authentication'];
                ?>
                    <div class="detail">
                        <p>
                            <span class="detail_title">Activation Link</span>
                            <a href="https://<?php echo $activation_link ?>" target="_blank"><?php echo $this->client_info['authentication']; ?></a>
                        </p>
                    </div>

                <?php
                }
                ?>
                <div class="detail">
                    <p>
                        <span class="detail_title">Billing</span>
                        <a href="#" id="view_billing">view</a>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Action</span>
                        <?php if($this->client_info['status']!="deleted"){ ?>
                                    <input type="submit" value="Edit Account" id="edit_account" />
                                    <input type="submit" value="Delete Account" id="delete_account" a0="delete_contact" />
                        <?php } else { ?>
                                    <input type="submit" value="Activate Account" id="activate_account" a0="activate_contact" />
                                    <input type="submit" value="Destroy Account" id="destroy_account" a0="destroy_contact" />
                        <?php } ?>
                    </p>
                </div>
            </div>

            <div class="clear"></div>

            <div id="file_access">
                <?php
                foreach($files as $file){
                    $checked = '';
                    $has_file = db::select_one("SELECT file_id FROM sb_client_files WHERE file_id={$file['id']} and client_id=".g::$client_id);
                    if($has_file){
                        $checked = "checked";
                    }

                    echo "<p>";
                    echo "<label>{$file['name']}</label> ";
                    echo "<input type='checkbox' name='client_files[]' value='{$file['id']}' {$checked}/>";
                    echo "</p>";
                }
                ?>
                <div class="clear"></div>
                <input type="submit" a0="update_user_files" value="Update File Access" />
            </div>

            <table id="os_table" class="clickable">
                <thead id="os_thead">
                    <tr>
                        <th>Group Name</th>
                        <th>OID</th>
                        <th>Status</th>
                        <th>Facebook Run Status</th>
                        <th>Budget</th>
                        <th>Package</th>
                        <th>Pay Plan</th>
                        <th>Latest Payment Status</th>
                        <th>Wpro Comment</th>
                    </tr>
                </thead>
                <tbody id="os_tbody">
                    <?php echo $tbody; ?>
                </tbody>
            </table>
            <input type="hidden" name="oid" id="order_id" value="0" />


        <?php
        }

        public function create_new_ad(){

            //get default settings for new orders
            $settings = db::select_row("SELECT * FROM sb_settings WHERE name = 'default'", "ASSOC");
            $order_info = db::select_row("
                select *
                from sb_groups
                where id = '".g::$group_id."'
            ", 'ASSOC');
            
            $ad_id = db::insert("sb_ads", array(
                'group_id' => g::$group_id,
                'edit_status' => 'client',
                'status' => 'active',
                'bid_type' => 'cpc',
                'name' => "New Ad",
                'link' => $order_info['url'],
                'location_type' => 'country',
                'country' => 'US',
                'create_date' => date( 'Y-m-d H:i:s', time() ),
                'max_bid' => $settings['max_bid'],
                'edit_status' => 'wpro'
            ));

        }

        public function display_order_details(){
            //cgi::print_r($this->order_info);
            //$ads = "Select ";
            $sql = "SELECT sum(imps) as imps, sum(cost) as cost FROM sb_data_ads WHERE campaign_id = ".$this->order_info['id'];
            $group_data = db::select_row($sql, "ASSOC");

            $safe_link = preg_replace("/^(http(s)*:\/\/)*/","",$this->order_info['url']);
            $safe_link = "http://".$safe_link;

        ?>

                <input type='hidden' name='oid' value="<?php echo $this->order_info['id']; ?>">

                <div id="change_group">
                    <span>View a different group: </span>
                    <?php self::get_group_select($this->order_info['contact_id']); ?>
                </div>

                <div id="breadcrumb">
                    <ul>
                        <li><a href="/sbs/sb/clients">All Clients</a> &raquo;</li>
                        <li><a href="/sbs/sb/client_info/?cid=<?php echo $this->order_info['client_id']; ?>">Client Profile</a> &raquo;</li>
                    </ul>
                    <div class="clear"></div>
                </div>

                <h2><span>Group: </span><?php echo $this->order_info['display_name'] ?></h2>
                <a href="<?php echo cgi::href('sbs/sb/account/?aid='.g::$group_id) ?>">(dash view)</a>
                
                <?php if($this->order_info['processed']=="canceled"){ ?>
                <span id="canceled">Order Canceled</span>
                <?php } else if ($this->order_info['processed']=="declined"){
					echo "<span id=\"canceled\">DECLINED</span>";
				} else if($this->order_info['cancel_date']!="0000-00-00"){
                    echo "<span id=\"canceled\">Pending Cancel Date: {$this->order_info['cancel_date']}</span>";
                }
                ?>


                <div id="ad_details">
                    <div class="detail">
                        <p>
                            <span class="detail_title">Group Name</span>
                            <span id="group_name_display">
                                <?php echo $this->order_info['display_name']; ?>
                                <a href="#" class="nogo" id="edit_group_name">edit</a>
                            </span>

                        </p>
                        <div id="edit_group_name_block" class="hidden">
                            <input type="text" id="group_name" name="group_name" value="<?php echo $this->order_info['display_name']; ?>" />
                            <input type="submit" value="save" a0="save_group_name" />
                            <input type="submit" class="nogo cancel" value="cancel" />
                        </div>
                    </div>
                    
                    <!--
                    <div class="detail">
                        <p>
                            <span class="detail_title">OID</span>
                            <span id="group_oid_display">
                                <?php //echo $this->order_info['oid']; ?>
                                <a href="#" class="nogo" id="edit_group_oid">edit</a>
                            </span>

                        </p>
                        <div id="edit_group_oid_block" class="hidden">
                            <input type="text" id="group_oid" name="group_oid" value="<?php //echo $this->order_info['oid']; ?>" />
                            <input type="submit" value="save" a0="save_group_oid" />
                            <input type="submit" class="nogo cancel" value="cancel" />
                        </div>
                    </div>
                    -->
                    
                    <div class="detail">
                        <p>
                            <span class="detail_title">FB Campaign ID (Group ID)</span>
                        <?php
                            echo g::$group_id;
                        ?>
                        </p>
                    </div>
                    
                    <div class="detail">
                        <p>
                            <span class="detail_title">FB Run Status</span>
                        <?php
                            $enabled = ($this->order_info['processed']!="canceled") ? true : false;
                            self::get_run_status_select($this->order_info['run_status'], $enabled);
                        ?>
                        </p>
                    </div>
                    
                    <div class="detail">
                        <p>
                            <span class="detail_title">Daily Budget</span>
                            <span id="daily_budget_display">
                                $<?php echo $this->order_info['daily_budget']; ?>
                                <a href="#" class="nogo" id="edit_daily_budget">edit</a>
                            </span>
                        </p>
                        <div id="edit_daily_budget_block" class="hidden">
                            $<input type="text" id="daily_budget" name="daily_budget" value="<?php echo $this->order_info['daily_budget']; ?>" />
                            <input type="submit" value="save" a0="save_daily_budget" />
                            <input type="submit" class="nogo cancel" value="cancel" />
                        </div>
                    </div>
                    
                    <!--
                    <div class="detail">
                        <p>
                            <span class="detail_title">Duration</span>
                            <?php echo $this->order_info['start_date']." - ".$this->order_info['end_date']; ?>
                        </p>
                    </div>
                    -->
                    
                    <div class="detail">
                        <p>
                            <span class="detail_title">Edit Status</span>
                            <?php echo $this->order_info['edit_status']; ?>
                        </p>
                    </div>
                    
                    <!--
                    <div class="detail">
                        <p>
                            <span class="detail_title">Total Imps</span>
                            <?php //echo $group_data['imps']; ?>
                        </p>
                    </div>
                    <div class="detail">
                        <p>
                            <span class="detail_title">Total Spend</span>
                            <?php //echo "$".$group_data['cost']; ?>
                        </p>
                    </div>
                    -->
                    
                    <div class="detail">
                        <p>
                            <span class="detail_title">Cancel/Activate</span>

                            <?php if($this->order_info['processed']=="new"){ ?>
                                <input type="submit" value="Activate Order" id="activate_order" a0="activate_order" />
                                <input type="submit" value="Decline Order" id="decline_order" a0="decline_order" />
                            <?php } else if($this->order_info['processed']=="canceled") { ?>
                                Cancel Date: <?php echo $this->order_info['cancel_date']; ?>
                                <input type="submit" value="Activate Order" id="activate_order" a0="reactivate_order" />
                            <?php } else if($this->order_info['processed']=="declined") { ?>
                                <input type="submit" value="Renew Order" id="renew_order" a0="renew_order" />
                            <?php } else { ?>
                                <input type="text" value="" class="date_input" name="cancel_date"/>
                                <input type="submit" value="Cancel Order" id="cancel_order" a0="cancel_order" />
                            <?php } ?>
                        </p>
                    </div>
                    <div class="detail">
                        <p>
                            <span class="detail_title">Action</span>
                            <input type="submit" value="Export Ad Group" id="export_one" />
                            <input type="submit" value="Download Images Zip" id="dl_images" a0="download_images" />
                        </p>
                    </div>
                    <div class="clear"></div>
                </div>

                
                <div id="group_urls">
                    <div id="order_url">
                        <p>
                            <b>URL: </b>
                            <span id="group_url_display">
                                <a href="<?php echo $safe_link; ?>" target="_blank"><?php echo $this->order_info['url']; ?></a>
                                <a href="#" class="nogo edit_link" id="edit_group_url">edit</a>
                            </span>
                            <div id="edit_url_block" class="hidden">
                                <input type="text" id="group_url" name="group_url" value="<?php echo $this->order_info['url']; ?>" />
                                <input type="submit" value="save" a0="save_group_url" />
                                <input type="submit" class="nogo cancel" value="cancel" />
                            </div>
                        </p>
                    </div>

                    <?php if($this->order_info['is_likepage']){
                        $likepage = db::select_row(
                            "SELECT * FROM sb_likepages
                            WHERE group_id = ".g::$group_id,
                            "ASSOC"
                        );
                    ?>
                    <div id="order_likepage">
                        <input type="hidden" name="likepage_id" value="<?php echo $likepage['id']; ?>" />
                        <p>
                            <b>Likepage URL: </b>
                            <span id="likepage_url_display">
                                <?php echo $likepage['url']; ?>
                                <a href="#" class="nogo" id="edit_likepage_url">edit</a>
                            </span>
                            <div id="edit_likepage_url_block" class="hidden">
                                <input type="text" id="likepage_url" name="likepage_url" value="<?php echo $likepage['url']; ?>" />
                                <input type="submit" value="save" a0="save_likepage_url" />
                                <input type="submit" class="nogo cancel" value="cancel" />
                            </div>
                        </p>
                        <!-- <p><a href="" id="likepage_details">View Likepage Details</a></p> -->
                        
                        <?php if(!$this->order_info['is_likepage_done']){ ?>
                            <input type="submit" value="Likepage Created" id="likepage_created" a0="likepage_created" />
                        <?php } ?>
                        
                    </div>
                    <?php } ?>
                </div>

                <div id="group_comments">
                    <div id="client_comments">
                        <p><b>Client Comments: </b><br>
                        <?php echo $this->order_info['comments']; ?></p>
                    </div>

                    <div id="wpro_comments">
                        <p>
                            <b>Wpro Comments: </b><br>
                            <textarea name="wpro_comments" cols="60" rows="5"><?php echo $this->order_info['wpro_comments']; ?></textarea>
                            <br>
                            <input type="submit" id="wpro_comments_submit" a0="save_wpro_comments" value="Update" />
                        </p>
                    </div>
                </div>
                
                <table>
									<tbody>
										<tr>
											<td>Trial Length</td>
											<td><input type="text" name="trial_length" value="<?php echo $this->order_info['trial_length']; ?>" /></td>
										</tr>
										<tr>
											<td>Trial Amount</td>
											<td><input type="text" name="trial_amount" value="<?php echo $this->order_info['trial_amount']; ?>" /></td>
										</tr>
										<tr>
											<td>Trial Auth Amount</td>
											<td><input type="text" name="trial_auth_amount" value="<?php echo $this->order_info['trial_auth_amount']; ?>" /></td>
										</tr>
										<tr>
											<td>Trial Auth ID</td>
											<td><input type="text" name="trial_auth_id" value="<?php echo $this->order_info['trial_auth_id']; ?>" /></td>
										</tr>
										<tr>
											<td></td>
											<td><input type="submit" a0="action_trial_info_submit" value="Update Trial Info" /></td>
										</tr>
									</tbody>
								</table>
                
                <div id="display_ads">
                    <input type="hidden" name="selected_ad" id="selected_ad" value="" />
                    <p><b>Edit Ads:</b><p>

                    <input type="submit" id="new_ad" a0="create_new_ad" value="Create New Ad" />

                    <!--
                    <input type="submit" id="clone_ad" a0="clone_ad" value="Clone Ad" />
                    <select></select>
                    -->

                    <?php
                        $this->display_ads();
                    ?>

                    <div class="clear"></div>


                </div>

            <?php

        }
        
        public function likepage_created()
        {
            db::exec("UPDATE sb_groups SET is_likepage_done = 1 WHERE id = ".g::$group_id);
            feedback::add_success_msg('Likepage has been created.');
            
            $this->order_info['is_likepage_done'] = 1;
            
        }
        
        public function action_trial_info_submit()
        {
					$trial_keys = array('trial_length', 'trial_amount', 'trial_auth_amount', 'trial_auth_id');
					$updates = array();
					foreach ($trial_keys as $key)
					{
						if ($this->order_info[$key] != $_POST[$key])
						{
							$updates[$key] = $_POST[$key];
							$this->order_info[$key] = $_POST[$key];
						}
					}
					db::update("eppctwo.sb_groups", $updates, "id = {$this->order_info['id']}");
					feedback::add_success_msg('Trial Info Updated');
				}
        
        public function display_likepage_details(){
            
            $likepage = db::select_row("SELECT * FROM sb_likepages WHERE id = {$_POST['likepage_id']}", "ASSOC");
            $likepage_links = db::select("SELECT * FROM sb_likepage_links WHERE likepage_id = {$_POST['likepage_id']}", "ASSOC")
            //print_r($likepage);
        ?>
            <input type='hidden' name='oid' value="<?php echo $this->order_info['id']; ?>">

            <div id="breadcrumb">
                <ul>
                    <li><a href="/sbs/sb/clients">All Clients</a> &raquo;</li>
                    <li><a href="/sbs/sb/client_info/?cid=<?php echo $this->order_info['client_id']; ?>">Client Profile</a> &raquo;</li>
                    <li><a href="/sbs/sb/order_details/?oid=<?php echo $this->order_info['id']; ?>">Group Deatils</a> &raquo;</li>
                </ul>
                <div class="clear"></div>
            </div>

            <h2><span>Group: </span><?php echo $this->order_info['display_name'] ?></h2>

            <h3>Likepage Details</h3>
            <small><a href="<?php echo $likepage['url']; ?>" target="_blank">view on facebook</a></small>

            <p>
                <b>Business Type: </b><?php echo $likepage['business_type']; ?>
            </p>
            <p>
                <b>Address: </b><?php echo $likepage['address']; ?><br>
                <b>City: </b><?php echo $likepage['city']; ?><br>
                <b>Zip: </b><?php echo $likepage['zip']; ?><br>
                <b>Phone: </b><?php echo $likepage['phone']; ?><br>
            </p>
            <p>
                <b>Business Hours: </b><?php echo $likepage['hour_open']." - ".$likepage['hour_closed']; ?>
            </p>
            <p>
                <b>Additional Information: </b><?php echo $likepage['details'] ?>
            </p>
            <p>
                <b>Links to include: </b><br>
                <?php
                    foreach($likepage_links as $link){
                        echo $link['url']."<br>";
                    }
                ?>
            </p>
        <?php
        }

        private function image_box(){

            echo '<label>Upload Image: </label>';
            echo '<input type="file" name="new_image" id="upload">';
            echo '<br>';

            $images = array();
            
            $result = util::wpro_post('account', 'sb_get_group_images', array(
                   'id' => g::$group_id
            ));
            
            //cgi::print_r($result);
            
            if($result['images']){
                $images = $result['images'];
                echo '<div id="image_box">';
                foreach($images as $image){
                    $image = ltrim($image, '/');
                    echo '<div class="image_frame draggable">';
                    echo '<img src="http://'.\epro\WPRO_DOMAIN.'/'.$image.'" />';
                    echo '<div class="remove_container"><div class="remove"></div></div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            echo '<div class="clear"></div>';
        }

        public function destroy_image(){

            $filename = $_POST['destroy_image'];
            
            $result = util::wpro_post('account', 'sb_destroy_ad_image', array(
                   'id' => g::$group_id,
                   'filename' => $filename
            ));
                
        }

        private function get_run_status_select($selected, $enabled=true){
            $options = array(
                "active" => "Active",
                "paused" => "Paused",
            );
            echo "<select id=\"run_status_select\" name=\"run_status\"";
            if(!$enabled){
                echo " disabled";
            }
            echo ">";
            foreach($options as $value => $text){
                echo "<option value=\"{$value}\"";
                if((string)$value == $selected){
                    echo " selected";
                }
                echo ">{$text}</option>";
            }
            echo "</select>";
        }

        private function get_group_select($client_id){
            $sql = "SELECT * FROM sb_groups WHERE contact_id = {$client_id}";
            $groups = db::select($sql,"ASSOC");
            //cgi::print_r($groups);
            echo "<select id='groups'>";
            foreach($groups as $group){
                echo "<option value=\"{$group['id']}\"";
                if(g::$group_id == $group['id']){
                    echo " selected";
                }
                echo ">{$group['display_name']}</option>";
            }
            echo "</select>";
        }

        protected static function edit_menu($selected_action = ""){
            $menu = array(
                'bids' => 'Bid Management',
                'keywords' => 'Keywords',
                'target' => 'Target Users',
                'image' => 'Ad Image',
                'text' => 'Ad Text'
            );

            $output = '<ul>';
            foreach($menu as $action => $label){
                $active = ($selected_action == $action) ? ' class="active"' : '';
                $output .= '<li target="'.$action.'"'.$active.">";
                $output .= $label;
                $output .= '</li>';
            }
            $output .= '</ul>';
            return $output;
        }

        public function display_edit_ad(){
            $ad_id = $_REQUEST['selected_ad'];
            if($ad_id){
                $sql = "SELECT * from sb_ads WHERE id = {$ad_id} LIMIT 1";
                $ad = db::select_row($sql, "ASSOC");
                if(empty($ad)) {
                    exit("Error: Ad with {$ad_id} could not be found!");
                } else {
                    $sql = "SELECT * from sb_data_ads WHERE ad_id = {$ad_id}";
                    $ad_data = db::select_row($sql, "ASSOC");

                    $client_ad = db::select_row("SELECT * from sbs_client_update WHERE account_id = {$ad_id} AND type = 'sb-ad' AND processed_dt = '0000-00-00 00:00:00' ORDER BY dt DESC LIMIT 1", "ASSOC");
                }

            } else {
                exit("Error: Empty ad id!");
            }
            $edit_type = (isset($_REQUEST['edit_type'])) ? $_REQUEST['edit_type'] : 'text';

            
            
        ?>
            <input name="oid" id="oid" value="<?php echo g::$group_id ?>" type="hidden" />

            <div id="breadcrumb">
                <ul>
                    <li><a href="/sbs/sb/client_info/?cid=<?php echo g::$client_id; ?>">Client Profile</a> &raquo;</li>
                    <li><a href="/sbs/sb/order_details/?oid=<?php echo $this->order_info['id']; ?>"><?php echo $this->order_info['display_name']; ?></a> &raquo;</li>
                </ul>
                <div class="clear"></div>
            </div>

            <h2><span>Ad: </span>
                    <?php
                        echo ($ad['name']) ? $ad['name'] : 'Untitled';
                        echo " ({$ad['id']})";
                    ?>
            </h2>

            <div id="ad_details">
                <div class="detail">
                    <p>
                        <span class="detail_title">Group Name</span>
                        <?php echo $this->order_info['display_name']; ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Ad Name</span>
                        <span id="ad_name_display">
                            <?php echo ($ad['name']) ? $ad['name'] : 'Untitled'; ?>
                            <a href="#" class="nogo" id="edit_ad_name">edit</a>
                        </span>
                        
                    </p>
                    <div id="edit_ad_name_block" class="hidden">
                        <input type="text" id="ad_name" name="ad_name" value="<?php echo $ad['name']; ?>" />
                        <input type="submit" value="save" a0="save_ad_name" />
                        <input type="submit" class="nogo cancel" value="cancel" />
                    </div>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Facebook Run Status</span>
                    <?php
                        if($this->order_info['run_status']=='paused'){
                            echo "Campaign paused";
                        } else {
                            $this->get_run_status_select($ad['status'], $enabled=true);
                        }
                    ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title"><?php echo $ad['bid_type']; ?> Bid</span>
                        $<?php echo $ad['max_bid']; ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Edit Status</span>
                        <?php echo $ad['edit_status']; ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Impressions</span>
                        <?php echo $ad_data['imps']; ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Clicks</span>
                        <?php echo $ad_data['clicks']; ?>
                    </p>
                </div>
                <div class="detail">
                    <p>
                        <span class="detail_title">Spend</span>
                        <?php echo "$".$ad_data['cost']; ?>
                    </p>
                </div>
                <div class="clear"></div>
            </div>

            <div class="ad_block">
                <?php self::get_ad_creative($ad); ?>
            </div>

            <div id="edit_bay">
                <input id="selected_ad" name="selected_ad" type="hidden" value="<?php echo $ad_id; ?>" />
                <input id="edit_type" type="hidden" name="edit_type" value="<?php echo $edit_type; ?>" />
                <div id="edit_nav">
                    <?php echo self::edit_menu($edit_type); ?>
                    <div class="clear"></div>
                </div>
                <div id="edit_content">
                <?php
                    switch($edit_type){
                        case 'text':
                            self::edit_ad_text($ad);
                            break;
                        case 'image':
                            self::edit_ad_image($ad);
                            break;
                        case 'keywords':
                            self::edit_ad_keywords($ad);
                            break;
                        case 'target':
                            self::edit_target_users($ad);
                            break;
                        case 'bids':
                            self::edit_target_bids($ad);
                            break;
                        default:
                            self::edit_ad_text($ad);
                            break;
                    }
                ?>
                <div id="edit_actions">
                    <input id="save_changes" type='submit' a0='save_ad' value='Save Changes' />
                </div>
                
                </div>
            </div>

            <?php if($client_ad){ ?>
                <div id="client_changes">
                    <h3>Client Submitted</h3>
                    <ul>
                    <?php
                        $client_updates = json_decode($client_ad['data']);
                        foreach($client_updates as $field => $value){
                            if($field=="Image") $value = '<img src="http://'.\epro\WPRO_DOMAIN.'/uploads/sb/'.g::$group_id.'/'.$value.'" alt="" />';
                            echo "<li>{$field}: {$value}</li>";
                        }
                    ?>
                    </ul>
                    <input type="submit" value="Clear Ticket" a0='clear_client_changes' />
                </div>
            <?php } ?>

            <div class="clear"></div>

        <?php
        }
        
        private static function edit_ad_text($ad){
        ?>
            <h3>Design Your Ad</h3>

            <div id="edit_ad_text" class="edit_section">

                <div class="edit_block">
                    <label>Link <span id="link_char_count" class="note"></span></label>
                    <input type="text" id="link" name="ad[link]" value="<?php echo $ad['link']; ?>" />
                    <span class="note">Example: http://www.yourwebsite.com/</span>
                </div>

                <div class="edit_block">
                    <label>Ad Title <span id="title_char_count" class="note"></span></label>
                    <input type="text" id="title" name="ad[title]" value="<?php echo $ad['title']; ?>" maxlength="25" />
                </div>

                <div class="edit_block">
                    <label>Ad Body <span id="body_text_char_count" class="note"></span></label>
                    <textarea max_chars="90" cols="25" rows="4" id="body_text" name="ad[body_text]" onkeydown="update_chars_left(this)" onkeyup="update_chars_left(this)"><?php echo $ad['body_text']; ?></textarea>
                </div>

            </div>
        <?php
        }

        private static function edit_ad_image($ad){
        ?>
            <h3>Design Your Ad - Image</h3>
            <div id="edit_ad_image" class="edit_section">
                <input name="ad[image]" id="image_file" value="<?php echo $ad['image']; ?>" type="hidden" />
                <input name="destroy_image" id="destroy_image" value="" type="hidden" />
                <?php self::image_box(); ?>

            </div>
        <?php
        }

        private static function edit_ad_keywords($ad){
            $keywords = array();
            if(!empty($ad['id'])){
                $sql = "select * from sb_keywords WHERE ad_id = {$ad['id']}
                    ORDER BY id ASC";
                $keywords = db::select($sql, 'ASSOC');
            }

            $min_keywords = 3;
            $keyword_count = (count($keywords)>$min_keywords) ? count($keywords) : $min_keywords;
         ?>
            <h3>Targeting - Keywords</h3>
            <div id="edit_ad_keywords" class="edit_section">
                <div class="edit_block">
                    <ul>
                        <?php
                        for($j=0;$j<$keyword_count;$j++){ ?>
                            <li>
                            <label>Keyword: <?php echo ($j+1); ?></label>
                            <input type="hidden" name="ad_keywords[<?php echo $j; ?>][id]"
                                   value="<?php echo $keywords[$j]['id']; ?>" />
                            <input type="text"
                                   name="ad_keywords[<?php echo $j; ?>][text]"
                                   value="<?php echo $keywords[$j]['text']; ?>" />
                            </li>
                        <?php } ?>
                    </ul>
                    <input type="submit" class="add_keyword nogo" value="Add a Keyword" /> 
                </div>
             </div>
        <?php
        }

        private static function edit_target_users($ad){
            // possible states/cities
            $countries = db::select("select * from countries", "ASSOC");
            $states = db::select("select * from states", "ASSOC");

            // initialize all set variables
            //
            //states
                $ad_states = array();
                $ad_states_count = 1;
            //cities
                $ad_cities = array();
                $ad_cities_count = 1;
            //colleges
                $ad_colleges = array();
                $ad_colleges_count = 1;
            //majors
                $ad_majors = array();
                $ad_majors_count = 1;
            //companies
                $ad_companies = array();
                $ad_companies_count = 1;

            if(!empty($ad['id'])){
                //get state locations
                $sql = "select * from sb_ad_location WHERE ad_id = {$ad['id']}
                    AND city = '' ORDER BY state ASC";
                @$ad_states = db::select($sql, 'ASSOC');
                if(!empty($ad_states)){
                    $ad_states_count = count($ad_states);
                }

                //get city locations
                $sql = "select * from sb_ad_location WHERE ad_id = {$ad['id']}
                    AND city <> '' ORDER BY state ASC, city ASC";
                @$ad_cities = db::select($sql, 'ASSOC');  
                if(!empty($ad_cities)){
                    $ad_cities_count = count($ad_cities);
                }

                //get set relationship_statuses
                $sql = "select * from sb_ad_relationship WHERE ad_id = {$ad['id']} LIMIT 1";
                $ad_relationships = db::select_row($sql, 'ASSOC');
                $checked_relationships = array();
                if(!empty($ad_relationships)){
                    foreach($ad_relationships as $option => $checked){
                        //echo $option;
                        if($option!='ad_id'){
                            if($checked){
                                $checked_relationships[] = $option;
                            }
                        }
                    }
                }

                //get set colleges
                $sql = "select * from sb_ad_college WHERE ad_id = {$ad['id']}
                    ORDER BY college ASC";
                @$ad_colleges = db::select($sql, 'ASSOC');
                if(!empty($ad_colleges)){
                    $ad_colleges_count = count($ad_colleges);
                }

                //get set majors
                $sql = "select * from sb_ad_major WHERE ad_id = {$ad['id']}
                    ORDER BY major ASC";
                @$ad_majors = db::select($sql, 'ASSOC');
                if(!empty($ad_majors)){
                    $ad_majors_count = count($ad_majors);
                }

                //get set companies
                $sql = "select * from sb_ad_company WHERE ad_id = {$ad['id']}
                    ORDER BY company ASC";
                @$ad_companies = db::select($sql, 'ASSOC');
                if(!empty($ad_companies)){
                    $ad_companies_count = count($ad_companies);
                }
            }

            
            $sex_options = array(
              array('value'=>'All', 'caption'=>'All'),
              array('value'=>'Men', 'caption'=>'Men'),
              array('value'=>'Women', 'caption'=>'Women')
            );
            $interested_in_options = array(
              array('value'=>'All', 'caption'=>'All'),
              array('value'=>'Men', 'caption'=>'Men'),
              array('value'=>'Women', 'caption'=>'Women')
            );
            $relationship_status_options = array(
                array('value'=>'',  'caption'=>'All', 'name'=>'ad_relationship_status[]'),
                array('value'=>'single', 'caption'=>'Single', 'name'=>'ad_relationship_status[]'),
                array('value'=>'relationship', 'caption'=>'Relationship', 'name'=>'ad_relationship_status[]'),
                array('value'=>'engaged', 'caption'=>'Engaged', 'name'=>'ad_relationship_status[]'),
                array('value'=>'married', 'caption'=>'Married', 'name'=>'ad_relationship_status[]')
            );
            $education_status_options = array(
                array('value'=>'', 'caption'=>'All'),
                array('value'=>'College Grad', 'caption'=>'College Grad'),
                array('value'=>'College', 'caption'=>'In College'),
                array('value'=>'High School', 'caption'=>'In High School')
            );
            
            //cgi::print_r($ad);
        
        ?>

            <h3>Targeting</h3>

            <div id="edit_ad_target_users" class="edit_section">
                
                <fieldset>

                    <legend><h4>Location</h4></legend>

                    <label>Country</label>
                    <select id="country" name="ad[country]">
                        <option value=""></option>
                    <?php
                        $selected = ($ad['country']!='') ? $ad['country'] : 'US';
                        foreach($countries as $country){
                            echo "<option value=\"{$country['a2']}\"";
                            if($country['a2']==$selected){
                                echo " selected";
                            }
                            echo ">{$country['country']}</option>";
                        }
                    ?>
                    </select>

                    <div id="location_type_selection" class="hidden edit_block">
                        <label><input type="radio" name="ad[location_type]" value="country"
                            <?php if($ad['location_type']=='country' || $ad['location_type']=='') echo 'checked'; ?>>Everywhere
                        </label>
                        <label><input type="radio" name="ad[location_type]" value="state"
                            <?php if($ad['location_type']=='state') echo 'checked'; ?>>By State/Province
                        </label>
                        <label><input type="radio" name="ad[location_type]" value="city"
                            <?php if($ad['location_type']=='city') echo 'checked'; ?>>By City
                        </label>
                    </div>

                    <div id="state_select" class="hidden">
                        <label>Target state(s)</label>
                        <?php 
                            for($i=0;$i<$ad_states_count;$i++){ ?>

                                <div class="state_block">
                                    <input type="hidden" name="ad_state[<?php echo $i; ?>][id]"
                                           value="<?php echo $ad_states[$i]['id']; ?>" class="state_id"
                                    />
                                    <select class="state" name="ad_state[<?php echo $i; ?>][state]">
                                    <option value="">Select a state</option>
                                    <?php
                                        foreach($states as $state){
                                            echo "<option value=\"{$state['text']}\"";
                                            if($state['text'] == $ad_states[$i]['state']){
                                                echo " selected";
                                            }
                                            echo ">{$state['text']}</option>";
                                        }
                                    ?>
                                    </select>
                                    <input type="submit" class="remove_state nogo" value="Remove state" />
                                    <div class="clear"></div>
                                </div>

                        <?php }
                        
                        ?>
                        <div id="more_states"></div>
                        <input type="submit" id="add_state" class="nogo" value="Add another state" />
                    </div>

                    <div id="city_select" class="hidden">
                         <div class="city_col_l">
                            <label>Enter a city</label>
                         </div>
                         <div class="city_col_r">
                            <label>Select a state</label>
                         </div>

                        <?php for($i=0;$i<$ad_cities_count;$i++){ ?>
                            <div class="city_block">
                                <input class="city_id" name="ad_city[<?php echo $i; ?>][id]" type="hidden" value="<?php echo $ad_cities[$i]['id']; ?>" />
                                <div class="city_col_l">
                                    <input class="city" name="ad_city[<?php echo $i; ?>][city]" value="<?php echo $ad_cities[$i]['city']; ?>" type="text" />
                                </div>
                                <div class="city_col_r">
                                    <select class="city-state" name="ad_city[<?php echo $i; ?>][state]">
                                        <option value=""></option>
                                        <?php
                                            foreach($states as $state){
                                                 echo "<option value=\"{$state['short']}\"";
                                                if($state['short'] == $ad_cities[$i]['state']){
                                                    echo " selected";
                                                }
                                                echo ">{$state['short']}</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="city_col_r">
                                    <input type="submit" class="remove_city nogo" value="Remove city" />
                                </div>
                                <div class="clear"></div>
                            </div>
                        <?php } ?>
                         
                        <div id="more_cities"></div>
                        <input type="submit" id="add_city" class="nogo" value="Add another city" />
                        
                        <div id="radius_select">
                            <input type="checkbox" id="has_radius" name="has_radius" value="1" <?php if($ad['radius'] != '') echo 'checked="checked"'?> />
                            <label>Include cities within <?php self::get_radius_select($ad['radius']); ?> miles.</label>
                        </div>

                    </div>

                </fieldset>

                <fieldset>

                    <legend><h4>Demographics</h4></legend>

                    <div class="field_block">
                        <label class="left_label">Age:</label>
                        <div id="age_select">
                            <?php echo html::select(html::options(self::get_age_options(), $ad['min_age']), array('name' => 'ad[min_age]', 'id' => 'min_age', 'class' => 'ages')); ?> -
                            <?php echo html::select(html::options(self::get_age_options(), $ad['max_age']), array('name' => 'ad[max_age]', 'id' => 'max_age', 'class' => 'ages')); ?>
                        </div>
                        <div id="target_bday">
                            <label><input type="checkbox" name="ad[birthday]" value="yes" />Target people on their birthdays</label>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="field_block">
                        <label class="left_label">Sex:</label>
                        <?php echo html::radios('ad[sex]', $sex_options, $ad['sex'], null, ''); ?>
                        <div class="clear"></div>
                    </div>


                    <div class="field_block">
                        <label class="left_label">Interested In:</label>
                        <?php echo html::radios('ad[interested_in]', $interested_in_options, $ad['interested_in'], null, ''); ?>
                        <div class="clear"></div>
                    </div>


                    <div class="field_block">
                        <label class="left_label">Relationship:</label>
                        <?php echo html::check_boxes($relationship_status_options, $checked_relationships, 'class="relationship_status"', ''); ?>
                        <div class="clear"></div>
                    </div>


                    <div class="field_block">
                        <label class="left_label">Langauge:</label>
                        <?php self::get_language_select($ad['language']); ?>
                        <div class="clear"></div>
                    </div>

                    <div class="clear"></div> 

                </fieldset>

                <fieldset>

                    <legend><h4>Education & Work</h4></legend>

                    <div class="field_block">

                        <label class="left_label">Education:</label>

                        <div id="education_type_selection">
                            <?php echo html::radios('ad[education_status]', $education_status_options, $ad['education_status'], null, '<br>'); ?>
                        </div>

                    </div>

                    <div id="college_fields" class="field_block college_details">

                        <label>College(s)</label>

                        <?php
                            for($i=0;$i<$ad_colleges_count;$i++){ ?>
                                <div class="college_block">
                                    <input type="hidden" name="ad_college[<?php echo $i; ?>][id]"
                                           value="<?php echo $ad_colleges[$i]['id']; ?>" class="college_id"
                                    />
                                    <input class="college" name="ad_college[<?php echo $i; ?>][college]" type="text" value="<?php echo $ad_colleges[$i]['college']; ?>" />
                                    <input type="submit" class="remove_college nogo" value="Remove college" />
                                    <div class="clear"></div>
                                </div>
                        <?php } ?>
                        <div id="more_colleges"></div>
                        <input type="submit" id="add_college" class="nogo" value="Add another college" />

                    </div>

                    <div id="major_fields" class="field_block college_details">

                        <label>Major(s)</label>

                        <?php
                            for($i=0;$i<$ad_majors_count;$i++){ ?>
                                <div class="major_block">
                                    <input type="hidden" name="ad_major[<?php echo $i; ?>][id]"
                                           value="<?php echo $ad_majors[$i]['id']; ?>" class="major_id"
                                    />
                                    <input class="major" name="ad_major[<?php echo $i; ?>][major]" type="text" value="<?php echo $ad_majors[$i]['major']; ?>" />
                                    <input type="submit" class="remove_major nogo" value="Remove major" />
                                    <div class="clear"></div>
                                </div>
                        <?php } ?>
                        <div id="more_majors"></div>
                        <input type="submit" id="add_major" class="nogo" value="Add another major" />

                    </div>

                    <div id="graduation_years" class="field_block college_details">

                        <label class="left_label">Graduation Years</label>
                        <div id="grad_year_options">
                        <?php echo html::select(html::options(self::get_college_year_options(), $ad['college_year_min']), array('name' => 'ad[college_year_min]', 'id' => 'college_year_min', 'class' => 'gradYears')); ?> -
                        <?php echo html::select(html::options(self::get_college_year_options(), $ad['college_year_max']), array('name' => 'ad[college_year_max]', 'id' => 'college_year_max', 'class' => 'gradYears')); ?>
                        </div>
                    </div>

                    <div id="workplace_fields" class="field_block">

                        <label class="left_label">Workplaces:</label>

                        <div id="workplace_details">
                            <label>Enter a company, organization or other workplace</label>
                            <?php
                                for($i=0;$i<$ad_companies_count;$i++){ ?>
                                    <div class="company_block">
                                        <input type="hidden" name="ad_company[<?php echo $i; ?>][id]"
                                               value="<?php echo $ad_companies[$i]['id']; ?>" class="company_id"
                                        />
                                        <input class="company" name="ad_company[<?php echo $i; ?>][company]" type="text" value="<?php echo $ad_companies[$i]['company']; ?>" />
                                        <input type="submit" class="remove_company nogo" value="Remove company" />
                                        <div class="clear"></div>
                                    </div>
                            <?php } ?>
                            <div id="more_companies"></div>
                            <input type="submit" id="add_company" class="nogo" value="Add another company" />
                        </div>
                        
                    </div>

                </fieldset>

            </div>

            <div class="clear"></div>
        <?php
        }

        private static function edit_target_bids($ad){
            $bid_options = array(
              array('value'=>'cpm', 'caption'=>'Pay for Impressions (CPM)'),
              array('value'=>'cpc', 'caption'=>'Pay for Clicks (CPC)')
            );
        ?>
            <h3>Pricing</h3>
            <div id="edit_ad_bids" class="edit_section">
                <div id="bid_options" class="edit_block">
                    <?php echo html::radios('ad[bid_type]', $bid_options, $ad['bid_type'], null, ''); ?>
                </div>
                <div id="edit_max_bid" class="edit_block">
                    <label>Max Bid <span>(min 0.01)</span></label>
                    <input type="text" name="ad[max_bid]" id="max_bid" value="<?php echo $ad['max_bid']; ?>" />
                </div>
             </div>
        <?php
        }

        public function save_ad(){
            
            $group_id = $_REQUEST['oid'];
            $ad_id = $_REQUEST['selected_ad'];

            $ad_name = db::select_one('SELECT name FROM sb_ads WHERE id='.$ad_id);

            //db::dbg();
            //
            //set up the array for data to be sent to wpromote
            $wpro_ad_data = array(
                'id' => $ad_id,
                'group_id' => $group_id
            );
            
            if(isset($_REQUEST['ad'])){
                $_REQUEST['ad']['edit_status'] = 'wpro';

                db::update('sb_ads',$_REQUEST['ad'],"id={$ad_id}");
                
                if($_REQUEST['ad']['title']!='' && empty($ad_name)){
                    db::update('sb_ads', array('name' => $_REQUEST['ad']['title']), "id={$ad_id}");
                }

                if($_REQUEST['ad']['title']){
                    $wpro_ad_data = array_merge($wpro_ad_data, array(
                       'title' => $_REQUEST['ad']['title'],
                       'body_text' => $_REQUEST['ad']['body_text'],
                       'link' => $_REQUEST['ad']['link']
                    ));
                }

                if($_REQUEST['ad']['image']){
                    $wpro_ad_data['image'] = $_REQUEST['ad']['image'];
                }
                
                if($_REQUEST['ad']['sex']){
                    $wpro_ad_data = array_merge($wpro_ad_data, array(
                       'min_age' => $_REQUEST['ad']['min_age'],
                       'max_age' => $_REQUEST['ad']['max_age'],
                       'sex' => $_REQUEST['ad']['sex']
                    ));
                }
            }

            if(isset($_REQUEST['ad_keywords'])){
                $kw_count = 0;
                $wpro_kws = array();
                foreach($_REQUEST['ad_keywords'] as $keyword){
                    $keyword['ad_id'] = $ad_id;
                    $keyword['group_id'] = $group_id;
                    if($keyword['text']!=''){
                        db::insert_update('sb_keywords',array('id'),$keyword);
                        if($kw_count < 3){
                            // display the 1st 3 keywords on wpromote
                            $wpro_kws[] = $keyword['text'];
                            $kw_count++;
                        }
                    } elseif($keyword['text']=='' && $keyword['id']!='') {
                        $sql = "DELETE FROM sb_keywords WHERE id={$keyword['id']}";
                        db::exec($sql);
                    }
                }

                $wpro_ad_data = array_merge($wpro_ad_data, array(
                    'keywords' => implode("\t", $wpro_kws)
                ));
            }

            if(isset($_REQUEST['ad']['location_type'])){

                $location_type = $_REQUEST['ad']['location_type'];

                if($location_type == 'state'){

                    foreach($_REQUEST['ad_state'] as $ad_state){
                        $ad_state['ad_id'] = $ad_id;
                        if($ad_state['state']!=''){
                            db::insert_update('sb_ad_location',array('id'),$ad_state);
                        } else {
                            if($ad_state['id']!=''){
                                db::exec('DELETE FROM sb_ad_location WHERE id='.$ad_state['id']);
                            }
                        }
                    }

                } else if($location_type == 'city'){

                    foreach($_REQUEST['ad_city'] as $ad_city){
                        $ad_city['ad_id'] = $ad_id;
                        if($ad_city['city']!='' && $ad_city['state']!=''){
                            db::insert_update('sb_ad_location',array('id'),$ad_city);
                        } else {
                            if($ad_city['id']!=''){
                                db::exec('DELETE FROM sb_ad_location WHERE id='.$ad_city['id']);
                            }
                        }
                    }
                    //check radius select
                    if(!isset($_REQUEST['ad']['radius'])){
                        db::exec("UPDATE sb_ads SET radius = '' WHERE id = ".$ad_id);
                    }

                }

            } // end isset[ad][location]

            if(isset($_REQUEST['ad_relationship_status'])){
                $relationship_options = db::get_cols('sb_ad_relationship');
                $update_array = array();
                foreach($relationship_options as $option){
                    if(in_array($option, $_REQUEST['ad_relationship_status'])){
                        $update_array[$option] = 1;
                    } else {
                        $update_array[$option] = '';
                    }
                }
                $update_array['ad_id'] = $ad_id;
                db::insert_update('sb_ad_relationship',array('ad_id'),$update_array);
            }

            if(isset($_REQUEST['ad_college'])){
                foreach($_REQUEST['ad_college'] as $ad_college){
                    $ad_college['ad_id'] = $ad_id;
                    if($ad_college['college']!=''){
                        db::insert_update('sb_ad_college',array('id'),$ad_college);
                    } else {
                        if($ad_college['id']!=''){
                            db::exec('DELETE FROM sb_ad_college WHERE id='.$ad_college['id']);
                        }
                    }
                }
            }

            if(isset($_REQUEST['ad_major'])){
                foreach($_REQUEST['ad_major'] as $ad_major){
                    $ad_major['ad_id'] = $ad_id;
                    if($ad_major['major']!=''){
                        db::insert_update('sb_ad_major',array('id'),$ad_major);
                    } else {
                        if($ad_major['id']!=''){
                            db::exec('DELETE FROM sb_ad_major WHERE id='.$ad_major['id']);
                        }
                    }
                }
            }

            if(isset($_REQUEST['ad_company'])){
                foreach($_REQUEST['ad_company'] as $ad_company){
                    $ad_company['ad_id'] = $ad_id;
                    if($ad_company['company']!=''){
                        db::insert_update('sb_ad_company',array('id'),$ad_company);
                    } else {
                        if($ad_company['id']!=''){
                            db::exec('DELETE FROM sb_ad_company WHERE id='.$ad_company['id']);
                        }
                    }
                }
            }

            $this->update_grp_status($group_id);

            //if any data was set besides the ad id, update the ad on wpromote.com
            if(count($wpro_ad_data)>1){
                print_r("Sending data to wpromote.");
                util::wpro_post('account', 'sb_update_ad', $wpro_ad_data);
            }

        }

        public function save_ad_name(){
 
            if($_POST['ad_name']!=''){
                $ad_id = $_REQUEST['selected_ad'];
                $ad = array(
                  'name' => $_POST['ad_name']
                );
                db::update('sb_ads', $ad, "id=".$ad_id);
            } else {
                $msg = "The ad name cannot be blank.";      
                feedback::add_error_msg($msg);
            }
        }

        public function save_group_name(){
          
            if($_POST['group_name']!=''){
                $group = array(
                  'id' => g::$group_id,
                  'display_name' => $_POST['group_name']
                );
                
                if(db::insert_update('sb_groups',array('id'),$group)){
					$this->order_info['display_name'] = $_POST['group_name'];
				}
                
            } else {
                
                    $msg = "The group name cannot be blank.";
              
                feedback::add_error_msg($msg);
            }

        }

        public function save_group_url(){
            $group = array(
              'url' => $_POST['group_url']
            );
            //db::dbg();
            if(db::update('sb_groups',$group,"id=".g::$group_id)){
				$this->order_info['url'] = $_POST['group_url'];
			}
        }

        public function save_group_oid(){
            $group = array(
              'oid' => $_POST['group_oid']
            );
            //db::dbg();
            db::update('sb_groups',$group,"id=".g::$group_id);
        }

        public function save_likepage_url(){
            $likepage = array(
              'url' => $_POST['likepage_url']
            );
            
            if(db::update('sb_likepages',$likepage,"group_id=".g::$group_id)){
				$this->order_info['sb_likepages'] = $_POST['likepage_url'];
			}
        }

        public function save_wpro_comments(){
            $group = array(
              'wpro_comments' => $_POST['wpro_comments']
            );
            
            if(db::update('sb_groups', $group, "id=".g::$group_id)){
				$this->order_info['wpro_comments'] = $_POST['wpro_comments'];
			}
        }

        public function clear_client_changes(){
            db::exec("UPDATE sbs_client_update SET processed_dt = '".date(util::DATE_TIME)."' WHERE processed_dt = '0000-00-00 00:00:00' AND department = 'sb' AND type = 'sb-ad' AND account_id = ".$_POST['selected_ad']);
        }

        public function update_grp_status($group_id){
            $edit_status = 'wpro';
            if($this->get_grp_status($group_id) == 'client'){
                $edit_status = 'client';
            }
            db::exec("UPDATE sb_groups SET edit_status = '{$edit_status}' WHERE id = ".$group_id);
        }

        public function get_grp_status($group_id){
           // check the status of all the ads
           $sql = "SELECT edit_status FROM sb_ads WHERE group_id = ".$group_id;
           $ads = db::select($sql, 'ASSOC');
           $status = 'current';
           foreach($ads as $ad){
               if($ad['edit_status']=='client'){
                   return 'client';
               } else if($ad['edit_status']=='wpro'){
                   $status = 'wpro';
               }
           }

           return $status;

        }

        public function save_daily_budget(){

            if($_POST['daily_budget']!='' && is_numeric($_POST['daily_budget'])){

                $group = array(
                  'id' => g::$group_id,
                  'daily_budget' => $_POST['daily_budget']
                );
                //db::dbg();
                db::insert_update('sb_groups',array('id'),$group);
                $this->update_grp_status(g::$group_id);
            } else {
                if($_POST['daily_budget']==''){
                    $msg = "The daily budget cannot be blank.";
                } else {
                    $msg = "The daily budget must be a number.";
                }
                feedback::add_error_msg($msg);
            }

        }

        private static function get_ad_creative($ad){
        ?>

            <div class="creative_sample_container droppable">
               
                    <span class="creative_sample_title"><?php echo $ad['title']; ?></span>
                    <div class="creative_sample_image">
                        <?php
                            if(!empty($ad['image'])){
                                echo '<img src="http://'.\epro\WPRO_DOMAIN.'/uploads/sb/'.$ad['group_id'].'/'.$ad['image'].'" />';
                            }
                        ?>
                    </div>
                
                <div class="creative_sample_body_text"><?php echo $ad['body_text']; ?></div>
                <div class="creative_sample_like">
                    <p><a class="thumb nogo" href="#">Like</a></p>
                    <p><a href="#" class="nogo">Wpromote</a> likes this.</p>
                </div>
            </div>
        <?php
        }

        private function display_ads() {
            $ads = db::select("
                select * from sb_ads
                WHERE group_id = {$this->order_info['id']}
                ORDER BY id ASC", 'ASSOC'
            );
        ?>

        <?php foreach($ads as $ad){ ?>

        <div class="ad_block" ad_id="<?php echo $ad['id']?>">
            <h4>
                <?php
                echo ($ad['name']) ? $ad['name'] : 'Untitled';
                ?>
            </h4>
            <?php self::get_ad_creative($ad); ?>
            <center>
                Edit Status: <?php echo $ad['edit_status']; ?>
                
            </center>
        </div>

        <?php }

        }



        public function get_age_options()
        {
            $age_options = array();
            $max_age = 64;
            $min_age = 13;

            $age_options[] = array('value' => '', 'caption' => 'Any');
            for ($i=$min_age;$i<=$max_age;$i++){
                $age_options[] = array('value' => $i, 'caption' => $i);
            }
            return $age_options;

        }

        public function get_college_year_options()
        {
            $graduation_year_options = array();
            $graduation_year_options[] = array('value' => '', 'caption' => 'Select Year');
            for($i=date('Y');$i<date('Y')+4;$i++){
                $graduation_year_options[] = array('value' => $i, 'caption' => $i);
            }
            return $graduation_year_options;
        }

        public function get_language_select($selected = "en_US")
        {
            $language_options = db::select("select * from sb_languages", "ASSOC");
            $selected = ($selected!='') ? $selected : "en_US";
            ?>
            <select id="lanuage" name="ad[language]">
                <?php
                    foreach($language_options as $language){
                        echo "<option value=\"{$language['code']}\"";
                        if($selected==$language['code']){
                            echo ' selected';
                        }
                        echo ">{$language['name']}</option>";
                    }
                ?>
            </select>
            <?php
        }

         public function get_radius_select($selected = '10')
        {
            $radius_options = array('','10','25','50');
            $selected = ($selected!='') ? $selected : "10";
            ?>
            <select id="radius" name="ad[radius]">
                <?php
                    foreach($radius_options as $radius){
                        echo "<option value=\"{$radius}\"";
                        if($selected==$radius){
                            echo ' selected';
                        }
                        echo ">{$radius}</option>";
                    }
                ?>
            </select>
            <?php
        }

        public function sts_client_update_ad(){
            sbs_lib::client_update('sb', $_POST['id'], 'sb-ad', array(
               'Link' => $_POST['link'],
               'Title' => $_POST['title'],
               'Text' => $_POST['body_text'],
               'Age Range' => $_POST['min_age'].' - '.$_POST['max_age'],
               'Sex' => $_POST['sex'],
               'Image' => $_POST['image'],
               'Keywords' => str_replace("\t", ", ", $_POST['keywords'])
            ));

            db::exec("UPDATE sb_ads SET edit_status = 'client' WHERE id = ".$_POST['id']);
            db::exec("UPDATE sb_groups SET edit_status = 'client', last_client_update = '".date(util::DATE_TIME)."' WHERE id = ".$_POST['group_id']);
        }

         public function sts_test_email(){
            $subject = "socialboost order test email";
            $from = "socialboost@wpromote.com";
            $to = 'ryan@wpromote.com';


            $body = 'test';


            @mail($to, $subject, $body, 'From: '.$from);

            $e2_response = array(
              "sent" => "email sent",
            );

            echo serialize($e2_response);

         }
         
        public function sts_get_clients_for_ql()
        {
					$clients = db::select("
						select g.client_id, c.email
						from sb_groups g, contacts c
						where g.contact_id = c.id
					");
					echo serialize($clients);
				}

        public function sts_email_submit(){

            $debug = '';
            $is_sb_client = false;

            $email = $_POST['email'];
            
            $prospect_id = db::insert("eppctwo.sb_prospects", array(
            	'email' => $email
            ));
            $debug = db::last_error();
            
            //check if email address already exists
            $sql = "SELECT * FROM contacts where email = '{$email}'";
            $contact = db::select_row($sql, "ASSOC");

            if($contact){
                //check if they are a social boost client
                //they might already be a quicklist client
                $sql = "SELECT * FROM clients_sb WHERE client = ".$contact['client_id'];
                $sb_client = db::select_row($sql, "ASSOC");

                if($sb_client){
                  $is_sb_client = true;
                }

            }

            $e2_response = array(
              "debug" => $debug,
              "is_sb_client" => $is_sb_client
            );
            echo serialize($e2_response);
        }

        public function sts_activate_contact(){
            $data = array('status' => 'active', 'authentication' => '');
            if(isset($_POST['pass_set_key'])){
                db::update("contacts", $data, "authentication='".$_POST['pass_set_key']."'");
            } else {
                db::update("contacts", $data, "client_id=".$_POST['client_id']);
            }
        }

        //recover functions
        public function sts_recover_order_from_wpro(){

            #this will break the response
           db::dbg();

            if(!db::select_one("select id from sb_groups WHERE client_id = {$_POST['client_id']}")){
							
							if ($_POST['signup_date'])
							{
								$signup_date = $_POST['signup_date'];
								$bill_day = $_POST['bill_day'];
                $first_bill_date = $_POST['first_bill_date'];
                $last_bill_date = $_POST['last_bill_date'];
                $next_bill_date = $_POST['next_bill_date'];
                $order_d = $signup_date;
                $order_t = '12:00:00';
							}
							else
							{
                $today = date(util::DATE);
                $signup_date = $today;
                $bill_day = date('j');
                $first_bill_date = $today;
                $last_bill_date = $today;
                $next_bill_date = util::delta_month($today, 1, $bill_day);
                $order_d = $today;
                $order_t = date(util::TIME);
							}
							
							if ($_POST['trial_length'])
							{
								$trial_amount = sbs_lib::get_recurring_amount('sb', $_POST['plan'], $_POST['pay_option']);
							}
							else
							{
								$trial_amount = 0;
							}
                 //get default settings for new orders
                $settings = db::select_row("SELECT * FROM sb_settings WHERE name = 'default'", "ASSOC");
                
                $contact_id = db::select_one("select id from contacts WHERE client_id = {$_POST['client_id']}");

                $cc_id = db::select_one("select id from ccs WHERE foreign_id = {$_POST['client_id']}");

                $group_id = db::insert("sb_groups", array(
                    'client_id' => $_POST['client_id'],
                    'contact_id' => $contact_id,
                    'cc_id' => $cc_id,
                    'd' => $order_d,
                    't' => $order_t,
                    //'wpro_order_id' => $_POST['wpro_cl_id'],
                    'oid' => $_POST['oid'],
                    'display_name' => $_POST['name'],
                    'url' => $_POST['url'],
                    'plan' => $_POST['plan'],
                    'pay_option' => $_POST['pay_option'],
                    //'country' => $_POST['cc_country'],
                    //'latest_payment_status' => $_POST['payment_accepted'],
                    //'comments' => $_POST['comments'],
                    'daily_budget' => $settings[strtolower($_POST['plan']).'_daily_budget'],
                    'processed' => 'new',
                    'edit_status' => 'new order',
                    'run_status' => 'active',
                    'is_likepage' => ($_POST['likepage']) ? 1 : 0,
                    'rdt' => util::unnull($_POST['rdt'], ''),
                    'partner' => $_POST['partner'],
                    'signup_date'     => $signup_date,
                    'bill_day'        => $bill_day,
                    'first_bill_date' => $first_bill_date,
                    'last_bill_date'  => $last_bill_date,
                    'next_bill_date'  => $next_bill_date,
                    'trial_length' => $_POST['trial_length'],
                    'trial_amount' => $trial_amount,
										'trial_auth_amount' => $_POST['trial_auth_amount'],
										'trial_auth_id' => $_POST['trial_auth_id']
                ));


                if($_POST['likepage']){
                    $likepage_id = db::insert("sb_likepages", array(
                        'group_id' => $group_id,
                    ));
                }

                //create the ads and insert files
                $num_ads = 1;
                if($_POST['plan']=="Premier"){
                    $num_ads = 3;
                    db::insert("sb_client_files", array(
                        "client_id" => $_POST['client_id'],
                        "file_id" => FACEBOOK_GUIDE_ID
                    ));
                    db::insert("sb_client_files", array(
                        "client_id" => $_POST['client_id'],
                        "file_id" => TWITTER_GUIDE_ID
                    ));
                } else if($_POST['plan']=="Core"){
                    $num_ads = 2;
                    db::insert("sb_client_files", array(
                        "client_id" => $_POST['client_id'],
                        "file_id" => FACEBOOK_GUIDE_ID
                    ));
                }

                //save ad ids to pass back to wpro
                $ad_ids= array();
                for($i=1;$i<=$num_ads;$i++){
                    $ad_id = db::insert("sb_ads", array(
                        'group_id' => $group_id,
                        'edit_status' => 'client',
                        'status' => 'active',
                        'bid_type' => 'cpc',
                        'name' => "Ad {$i}",
                        'link' => $_POST['url'],
                        'location_type' => 'country',
                        'country' => 'US',
                        'create_date' => date(util::DATE_TIME),
                        'max_bid' => $settings['max_bid']
                    ));
                    $ad_ids[] = $ad_id;
                }

                $response = array(
                    'client_id' => $_POST['client_id'],
                    'group_id' => $group_id,
                    'ad_ids' => $ad_ids
                );

            } else {
                $response['result'] = "failure";
            }

            echo serialize($response);
        }

        public function move_payment_data(){

            $payments = db::select("SELECT * FROM sb_payments", "ASSOC");

            foreach($payments as $payment){

                if(db::select_one("SELECT id FROM sbs_payment WHERE sb_payment_id = ".$payment['id'])){
                    //print_r('Duplicate Found');
                    continue;
                }
                
                $group = db::select_row("SELECT * FROM sb_groups WHERE id = {$payment['group_id']}", "ASSOC");
                
                if($payment['status']=='PENDING'){
				
					db::update('sb_groups', array('next_bill_date' => $payment['due_date']), 'id='.$payment['group_id']);
					echo "Updated group with pending payment. Group {$payment['group_id']}<br />";
					continue;
					
				}

                switch($payment['type']){
                    case 'ORDER':
                        $type = 'Order';
                        break;
                    case 'PREPAY':
                        $type = 'Order';
                        break;
                    case 'RECURRING':
                        $type = 'Recurring';
                        break;
                    case 'PREPAY_RECURRING':
                        $type = 'Recurring';
                        break;
                    case 'REFUND':
                        $type = 'Refund Old';
                        break;
                    case 'UPGRADE':
                        $type = 'Upgrade';
                        break;
                    default:
                        $type = 'Other';
                        break;
                }

                
                //cgi::print_r($group);
                switch($group['plan']){
                    case 'silver':
                        $pay_option = '1_0';
                        break;
                    case 'gold':
                        $pay_option = '6_1';
                        break;
                    case 'platinum':
                        $pay_option = '12_3';
                        break;
                    default:
                        $pay_option = '1_0';
                }

                $payment_data = array(
                    'client_id' => $payment['client_id'],
                    'account_id' => $payment['group_id'],
                    'pay_id' => $payment['cc_id'],
                    'pay_method' => 'cc',
                    'd' => $payment['paid_date'],
                    'department' => 'sb',
                    'type' => $type,
                    'pay_option' => $pay_option,
                    'amount' => $payment['amount'],
                    'notes' => $payment['notes'],
                    'sb_payment_id' => $payment['id']
                );

                //cgi::print_r($payment_data);
                $payment_id = db::insert('sbs_payment', $payment_data);
                echo "Inserted new payment. {$payment_id}<br />";
                
                db::update('sb_groups', array(
					'last_bill_date' => $payment['paid_date']
                ), 'id='.$payment['group_id']); 
            }
        }

		public function set_next_bill_dates(){
			db::dbg();
			$groups = db::select("SELECT * FROM sb_groups WHERE next_bill_date = '0000-00-00' AND (processed = 'new' OR processed = 'processed')", "ASSOC");
			
			foreach($groups as $group){
				list($months_paid, $months_free) = sbs_lib::get_pay_option_months($group['pay_option']);
				$next_bill_date = util::delta_month($group['last_bill_date'], $months_paid + $months_free, $group['bill_day']);
				
				db::update("sb_groups", array(
					'next_bill_date' => $next_bill_date
				), "id={$group['id']}");
			}
			
		}

        public function sync_ad_groups(){
			if($_GET['group_id']){
				$groups = db::select("SELECT * FROM sb_groups WHERE id = ".$_GET['group_id'], "ASSOC");
			} else {
				$groups = db::select("SELECT * FROM sb_groups", "ASSOC");
			}
            foreach($groups as $group){
                //send all e2 group ids to wpro
                $r = util::wpro_post('account', 'sb_sync_ad_groups', $group);
                if($r['update']){
                    //ad group was saved on wpromote
                    //send the ads to be saved
                    $ads = db::select("SELECT * FROM sb_ads where group_id=".$group['id'], "ASSOC");
                    foreach($ads as $ad){
                        $kwds = db::select("SELECT text FROM sb_keywords WHERE ad_id=".$ad['id']." ORDER BY id ASC LIMIT 3", "ASSOC");
                        $keywords = array();
                        foreach($kwds as $kwd){
                            $keywords[] = $kwd['text'];
                        }
                        $ad['keywords'] = implode("\t", $keywords);
                        util::wpro_post('account', 'sb_sync_ad', $ad);
                    }
                }
            }
            
        }

        public function update_bill_day(){

            $groups = db::select("SELECT * FROM sb_groups WHERE bill_day = 0", "ASSOC");

            foreach($groups as $group){
                list($year, $month, $day) = explode('-', $group['d']);
                $last_bill_date = db::select_one("SELECT d FROM sbs_payment WHERE account_id = {$group['id']} && (type = 'Order' || type = 'Recurring') ORDER BY d DESC LIMIT 1");
                $data = array(
                    'signup_date' => $group['d'],
                    'bill_day' => $day,
                    'first_bill_date' => $group['d'],
                    'last_bill_date' => $last_bill_date
                );
                //cgi::print_r($data);
                //db::dbg();
                db::update('sb_groups', $data, 'id = '.$group['id']);
            }

        }

        public function update_clients(){
            db::dbg();

            $clients = db::select("SELECT * FROM clients_sb", "ASSOC");

            foreach($clients as $client){
                db::update('clients', array('data_id' => 'sb'), 'id = '.$client['client']);
            }
        }

        //helper functions
        private static function calculate_next_due_payment($pay_option, $plan_cost, $base_date_string = 'today'){
            //calculate next due payment
            $next_payment = array();
            $next_payment['type'] = "PREPAY_RECURRING";
            $base_date = strtotime($base_date_string);
            switch($pay_option){
                //date format Y-m-d
                case 'buy 3 months':
                    //3 months paid for
                    $next_payment['due_date'] = date('Y-m-d', strtotime("+3 months", $base_date));
                    $next_payment['amount'] = $plan_cost*3;
                    break;
                case 'buy 6 months':
                    //6 months paid for, +1 free month
                    $next_payment['due_date'] = date('Y-m-d', strtotime("+7 months", $base_date));
                    $next_payment['amount'] = $plan_cost*6;
                    break;
                case 'buy 12 months':
                    //12 months paid for, +3 free month
                    $next_payment['due_date'] = date('Y-m-d', strtotime("+15 months", $base_date));
                    $next_payment['amount'] = $plan_cost*12;
                    break;
                default:
                    //month to month
                    $next_payment['due_date'] = date('Y-m-d', strtotime("+1 month", $base_date));
                    $next_payment['amount'] = $plan_cost;
                    $next_payment['type'] = "RECURRING";
                    break;
            }
            return $next_payment;
        }

        private static function random_string()
	{
            $length = 32;
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';

            // Length of character list
            $chars_length = (strlen($chars) - 1);

            // Start our string
            $string = $chars{rand(0, $chars_length)};

            // Generate random string
            for ($i = 1; $i < $length; $i = strlen($string))
            {
                // Grab a random character from our list
                $r = $chars{rand(0, $chars_length)};
                $string .=  $r;
            }

            // make sure the string is unique
            if(self::found_duplicate_auth($string)){
                $string = self::random_string();
            }

            // Return the string
            return $string;
	}
        
        private static function found_duplicate_auth($string){
            
            $sql = "SELECT * from contacts
                    WHERE authentication = '".$string."'
                    LIMIT 1";
            $dup_auth = db::select_row($sql, 'ASSOC');
            if(empty($dup_auth)){
                return false;
            }
            return true;
         }

        private static function check_duplicate_creditcard($client_id, $number, $code){
            $cc_num = util::encrypt($number);
            $cc_code = util::encrypt($code);
            $sql = "SELECT * from ccs
                    WHERE cc_number = '".$cc_num."'
                    AND cc_code = '".$cc_code."'
                    AND foreign_id = ".$client_id."
                    LIMIT 1";
            $dup_card = db::select_row($sql, 'ASSOC');
            return $dup_card['id'];
        }

        public function sendClientEmail($msg_type, $addr, $activate_str){

            $to = $addr;
            $body = "";

            switch($msg_type){
                case 'new_user':
                    $body .= "Thank you for signing up for SocialBoost with Wpromote!
                                Please click the link below to activate your account\n\n";
                    $body .= "http://clients.wpromote.com/home/activate/?code=".$activate_str;
                    break;

                case 'current_user':
                    $body .= "Thank you for your SocailBoost order with Wpromote!\n\n
                                View your account at http://clients.wpromote.com";
                    break;

                default:
                    break;
            }


            $from = "Wpromote";
            $subject = "Order Confirmation";

            @mail($to, $subject, $body, 'From: '.$from);
        
        }

        public function sendInternalEmail($group_id=0,$payment_id=0){
            $group = db::select_row("SELECT * FROM sb_groups WHERE id=".$group_id,"ASSOC");
            $contact = db::select_row("SELECT * FROM contacts WHERE id=".$group['contact_id'],"ASSOC");
            $payment = db::select_row("SELECT * FROM sb_payments WHERE id=".$payment_id,"ASSOC");

            $to = "sborders@wpromote.com";

            $likepage = ($group['is_likepage']) ? 'yes' : 'no';

            if($group['pay_option']=="buy 3 months"){
                $num_months = " - 3";
            } else if($group['pay_option']=="buy 6 months") {
                $num_months = " - 6";
            } else if($group['pay_option']=="buy 12 months") {
                $num_months = " - 12";
            } else {
                $num_months = "";
            }

            $subject = "SocialBoost Order - ({$group['plan']})";
            if($group['partner']!=""){
                $subject .= " - ".$group['partner'];
                $to .= ", bmsborders@wpromote.com";
            }
            $subject .= $num_months;

            $body  = "Order ID: ".$group['oid']."\n\n";
            $body .= "Date/Time: ".$group['d'].' '.$group['t']."\n\n";
            $body .= "Package: ".$group['plan']."\n\n";
            $body .= "Pay Option: ".$group['pay_option']."\n\n";
            $body .= "Amount: $".$payment['amount']."\n\n";
            $body .= "Name: ".$contact['name']."\n\n";
            $body .= "E-mail: ".$contact['email']."\n\n";
            $body .= "URL: ".$group['url']."\n\n";
            $body .= "Likepage: {$likepage}\n\n";

            $from = $contact['email'];
            @mail($to, $subject, $body, 'From: '.$from);

        }

        public function is_payment_signup($type){
         return ($type == 'ORDER' || $type == 'PREPAY');
     }

        public function get_rev_stats($partner, $data){
        list($signups, $cancels, $rev_signup, $rev_signup_order, $rev_all) = util::list_assoc($data, 'signups', 'cancels', 'rev_signup', 'rev_signup_order', 'rev_all');
        if (empty($cancels)) $cancels = 0;
        $num_still_active = $signups - $cancels;

        $rev_signup_per = util::safe_div($rev_signup, $signups);
        $percent_survived = util::safe_div($num_still_active, $signups);

        $output = "<tr>";
        $output .= "<td>".$partner."</td>";
        $output .= "<td>".$signups."</td>";
        $output .= "<td>".$cancels."</td>";
        $output .= "<td>".$num_still_active."</td>";
        $output .= "<td>".util::format_percent($percent_survived * 100)."</td>";
        $output .= "<td>".util::format_dollars($rev_signup_order)."</td>";
        $output .= "<td>".util::format_dollars($rev_signup - $rev_signup_order)."</td>";
        $output .= "<td>".util::format_dollars($rev_signup_per)."</td>";
        $output .= "<td>".util::format_dollars($rev_signup)."</td>";
        $output .= "<td>$".$rev_all."</td>";
        $output .= "</tr>";

        return $output;
     }

        public function revenue_analysis() {
        if(isset($_POST['submit'])){
            $signup_start = $_POST['signup_start'];
            if (empty($signup_start)){

            }

            $signup_end = $_POST['signup_end'];
            $analysis_end = $_POST['analysis_end'];

            $all = db::select("
                    select id, partner
                    from sb_groups
            ", 'NUM', 0);

             $signups = db::select("
                    select id, partner
                    from sb_groups
                    where
                            d >= '$signup_start' &&
                            d <= '$signup_end'
            ");

            @$cancels = db::select("
                    select id, partner
                    from sb_groups
                    where
                            d >= '$signup_start' &&
                            d <= '$signup_end' &&
                            cancel_date <> '0000-00-00' &&
                            cancel_date <= '$analysis_end'
            ", 'NUM', 0);

            $tmp_payments = db::select("
                    select group_id, amount, type
                    from sb_payments
                    where paid_date >= '$signup_start' && paid_date <= '$analysis_end'
            ");

            $totals = array();
            $by_partner = array();
            $payments = array();

            for ($i = 0; list($group_id, $amount, $type) = $tmp_payments[$i]; ++$i)
            {
                    $payments[$group_id][] = array($amount, $type);

                    $partner = $all[$group_id];
                    if (empty($partner)) $partner = '(none)';

                    $by_partner[$partner]['rev_all'] += $amount;
                    $totals['rev_all'] += $amount;
            }

            for ($i = 0; list($group_id, $partner) = $signups[$i]; ++$i)
            {
                    if (empty($partner)) $partner = '(none)';

                    $by_partner[$partner]['signups']++;
                    $totals['signups']++;

                    if (array_key_exists($group_id, $cancels))
                    {
                            $by_partner[$partner]['cancels']++;
                            $totals['cancels']++;
                    }
                    
                    if (array_key_exists($group_id, $payments))
                    {
                        $url_payments = &$payments[$group_id];
                        for ($j = 0; list($amount, $type) = $url_payments[$j]; ++$j)
                        {
                                $by_partner[$partner]['rev_signup'] += $amount;
                                $totals['rev_signup'] += $amount;

                                if ($this->is_payment_signup($type))
                                {
                                        $by_partner[$partner]['rev_signup_order'] += $amount;
                                        $totals['rev_signup_order'] += $amount;
                                }
                        }
                    }



            }

            //print_r($by_partner);
            //print_r($totals);
            
            ksort($by_partner);

            $output = "";
            foreach ($by_partner as $partner => $data)
            {
                $output .= $this->get_rev_stats($partner, $data);
            }
        }

     ?>
        <label>Signup Start</label>
        <input type="text" id="signup_start" name="signup_start" class="date_input" value="<?php echo $signup_start; ?>" /><br>
        <label>Signup End</label>
        <input type="text" id="signup_end" name="signup_end" class="date_input" value="<?php echo $signup_end; ?>" /><br>
        <label>Analysis End</label>
        <input type="text" id="analysis_end" name="analysis_end" class="date_input" value="<?php echo ((empty($_POST['analysis_end'])) ? date('Y-m-d') : $_POST['analysis_end']); ?>" /><br>

        <input type="submit" name="submit" value="Submit" />

        <table id="stats" cellpadding=0 cellspacing=0>
            <thead>
                <tr>
                    <th>Partner</th>
                    <th>Signups</th>
                    <th>Cancels</th>
                    <th>#Survived</th>
                    <th>%Survived</th>
                    <th>Rev Signup</th>
                    <th>Rev Signup Recur</th>
                    <th>Rev Signup Per</th>
                    <th>Rev Signup Total</th>
                    <th>Rev All Total</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $output; ?>
            </tbody>
            <tfoot>
                <?php echo $this->get_rev_stats("<b>Totals</b>", $totals); ?>
            </tfoot>
        </table>

     <?php
     }

        public function display_total_revenue(){

        if(isset($_POST['submit'])){

            $conditions = array();
            if(!empty($_POST['start'])){
                $conditions[] = "paid_date >= '{$_POST['start']}'";
            }
            if(!empty($_POST['end'])){
                $conditions[] = "paid_date <= '{$_POST['end']}'";
            }
            $sql =  "select amount, type from sb_payments";
            if(!empty($conditions)){
                $sql .= " WHERE ".implode(" AND ", $conditions);
            }
            $payments = db::select($sql);
            
            $results= array();
            foreach($payments as $payment){
				
                list($amount, $type) = $payment;
                
                if($type=="ORDER" || $type=="PREPAY"){
                    $results['signup'] += $amount;
                } else if($type=="RECURRING" || $type=="PREPAY_RECURRING"){
                    $results['recur'] += $amount;
                } else if($type=="REFUND") {
                    $results['refund'] += $amount;
                }
                
                $results['total'] += $amount;
            }
            
            
            $conditions = array();
            if(!empty($_POST['start'])){
                $conditions[] = "d >= '{$_POST['start']}'";
            }
            if(!empty($_POST['end'])){
                $conditions[] = "d <= '{$_POST['end']}'";
            }
            $conditions[] = "client_id <> 48940"; //wpromotes client_id
            $sql =  "select SUM(cost) from sb_data_ads";
            if(!empty($conditions)){
                $sql .= " WHERE ".implode(" AND ", $conditions);
            }
            $client_spend = db::select($sql);
            
            
            $conditions = array();
            if(!empty($_POST['start'])){
                $conditions[] = "d >= '{$_POST['start']}'";
            }
            if(!empty($_POST['end'])){
                $conditions[] = "d <= '{$_POST['end']}'";
            }
            $conditions[] = "client_id = 48940"; //wpromotes client_id
            $sql =  "select SUM(cost) from sb_data_ads";
            if(!empty($conditions)){
                $sql .= " WHERE ".implode(" AND ", $conditions);
            }
            $internal_spend = db::select($sql);
            
            
            $conditions = array();
            if(!empty($_POST['start'])){
                $conditions[] = "d >= '{$_POST['start']}'";
            }
            if(!empty($_POST['end'])){
                $conditions[] = "d <= '{$_POST['end']}'";
            }
            $sql =  "select * from sb_groups";
            if(!empty($conditions)){
                $sql .= " WHERE ".implode(" AND ", $conditions);
            }
            $groups = db::select($sql);
            $new_groups = count($groups);
            

            $output = "<tr>";
            $output .= "<td>".util::format_dollars($results['signup'])."</td>";
            $output .= "<td>".util::format_dollars($results['recur'])."</td>";
            $output .= "<td>".util::format_dollars($results['refund'])."</td>";
            $output .= "<td>".util::format_dollars($results['total'])."</td>";
            $output .= "<td>".util::format_dollars($internal_spend[0])."</td>";
            $output .= "<td>".util::format_dollars($client_spend[0])."</td>";
            $output .= "<td>".$new_groups."</td>";
            $output .= "</tr>";

        }
     ?>
        <label>Start</label>
        <input type="text" id="start" name="start" class="date_input" value="<?php echo $start; ?>" /><br><br>
        <label>End</label>
        <input type="text" id="end" name="end" class="date_input" value="<?php echo $end; ?>" /><br><br>
        <input type="submit" name="submit" value="Submit" />


        <table id="stats" cellpadding=0 cellspacing=0>
            <thead>
                <tr>
                    <th>Rev Signup</th>
                    <th>Rev Recur</th>
                    <th>Refunds</th>
                    <th>Rev All Total</th>
                    <th>Facebook Internal Spend</th>
                    <th>Client Spend</th>
                    <th>New Clients</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $output; ?>
            </tbody>
        </table>

     <?php
     }

	public function display_fb_data_upload()
	{
		?>
		<h1>Facebook Data Upload
		<table>
			<tbody>
				<tr>
					<td>File</td>
					<td><input type="file" id="ads_file" name="ads_file" /></td>
				</tr>
				<tr>
					<td colspan="2"> - OR - </td>
				</tr>
				<tr>
					<td>Text</td>
					<td><textarea id="ads_text" name="ads_text"><?php echo $this->upload_str; ?></textarea></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" id="leads_submit" a0="action_fb_data_upload" value="Upload" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_fb_data_upload()
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		
		if ($_FILES['ads_file']['tmp_name'])
		{
			$temp_name = $_FILES['ads_file']['tmp_name'];
			$separator = ',';
		}
		else
		{
			$temp_name = tempnam(sys_get_temp_dir(), 'fbu');
			file_put_contents($temp_name, $_POST['ads_text']);
			$separator = "\t";
		}
		
		$api = new f_api(1, '');
		if (!$api->import_report($temp_name, 0, $separator))
		{
			feedback::add_error_msg($api->get_error());
		}
		else
		{
			list($start_date, $end_date) = $api->get_import_dates();
			$row_count = 0;
			for ($i = $start_date; $i <= $end_date; $i = date(util::DATE, strtotime("$i +1 day")))
			{
				sbs_lib::update_all_active_urls_data($this->dept, 'f', $i);
				$row_count += db::select_one("select count(*) from f_data_tmp.".str_replace('-', '_', $i));
			}
			feedback::add_success_msg("Facebook data upload complete. {$row_count} data points over date range {$start_date} to {$end_date}");
		}
	}
	
	public function display_spend()
	{
		list($start_date, $end_date) = util::list_assoc($_POST, 'start_date', 'end_date');
		if (util::empty_date($start_date) || util::empty_date($end_date) || $start_date > $end_date)
		{
			$end_date = date(util::DATE, time() - 86400);
			$start_date = date(util::DATE, time() - 2678400);
		}
		
		$spend = db::select("
			select data_date date, sum(imps) imps, sum(clicks) clicks, sum(cost) cost
			from f_data.clients_sb
			where data_date between '$start_date' and '$end_date'
			group by date
		", 'ASSOC', 'date');
		
		?>
		<h1>Spend</h1>
		<table>
			<tbody>
				<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
				<tr>
					<td></td>
					<td><input type="submit" value="Set Dates" /></td>
				</tr>
			</tbody>
		</table>
		<div id="spend_wrapper"></div>
		<?php
		cgi::add_js_var('spend', $spend);
	}
}



?>