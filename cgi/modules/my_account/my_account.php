<?php

class mod_my_account extends module_base
{
	//public $skin = 'cameo';
	
	public function get_menu()
	{
		return new Menu(array(
			new MenuItem('Change Password', array('change_password')),
		    new MenuItem('Change Phone', array('change_phone'))
		), 'my_account');
	}
	
	public function pre_output()
	{
		
	}

	public function display_index()
	{
	?>
		<div class="profile pd-l-md pd-r-md pd-b-lg">

            <div class="row">
                <div class="col-xs-12 profile-cover">
                    <div class="profile-avatar text-center">
                        <img src="<?= user::gravatar_path(); ?>?s=128" class="avatar avatar-lg bordered-avatar img-circle" alt="">
                    </div>
                </div>
                <div class="col-xs-12 text-center mg-t-md">
                    <div class="h4 no-margin"><?= user::$realname ?></div>
                    <small>Wpromote Employee</small>
                </div>
                <div class="col-xs-12 mg-t-md mg-b-md">
                    <div class="btn-group btn-group-justified btn-rounded">
                        <a class="btn btn-success" role="button">Follow</a>
                        <a class="btn btn-primary" role="button">Send Message</a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 mg-b-sm">
                    Cheers
                    <span class="pull-right">1323</span>
                </div>
                <hr>
            </div>

            <div class="row mg-b mg-t">
                <div class="col-xs-12">
                    <h6>About <?= user::$realname ?></h6>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sed dui tincidunt, gravida ipsum rhoncus, blandit leo. In hac habitasse platea dictumst. Sed et sem vitae justo molestie molestie elementum in ipsum. Quisque bibendum arcu sit amet augue malesuada, vitae imperdiet nibh adipiscing. Nulla tincidunt libero pellentesque ligula pharetra, vitae porttitor sem dapibus. Quisque commodo sodales varius. In hac habitasse platea dictumst. Integer feugiat augue ac pharetra tempor. Nullam lorem odio, dictum sed fermentum eget, aliquam quis felis. Sed sed ligula vel tellus facilisis imperdiet. Integer nulla dui, condimentum sed congue eget, vehicula vitae justo. Ut sit amet quam quam.</p>
                </div>
            </div>

            <a href="javascript:;" class="text-muted">
                <i class="fa fa-globe mg-r-md"></i>www.wpromote.com
            </a>
        </div>
	<?php
	}
	
	public function display_change_phone()
	{
		$phone_ext = db::select_one("select phone_ext from users where id = :id",array('id'=>user::$id));
		if(!empty($phone_ext)){
			if(strlen($phone_ext)>7){
				$area_code = substr($phone_ext,0,3);
				$phone1 =  substr($phone_ext,3,3);
				$phone2 =  substr($phone_ext,6,4);
			} else {
				$area_code = '310';
				$phone1 =  substr($phone_ext,0,3);
				$phone2 =  substr($phone_ext,3,4);
			}
		}
		?>
			<h1>Change Number</h1>
			(<input type="text" name="phone[1]" size="2" maxlength="3" value="<?php if(isset($area_code)) echo $area_code ?>" />)
			<input type="text" name="phone[2]" size="2" maxlength="3" value="<?php if(isset($phone1)) echo $phone1 ?>" /> -
			<input type="text" name="phone[3]" size="2" maxlength="4" value="<?php if(isset($phone2)) echo $phone2 ?>" />
			<br /><br />
			<input type="submit" value="Submit" a0="action_change_phone" />
		<?php
	}
	
	protected function action_change_phone(){
		foreach($_POST['phone'] as $num){
			if(!is_numeric($num)){
				feedback::add_error_msg("Invalid.");
				return;
			}
		}
		db::update(
			"`eppctwo`.`users`",
			array('phone_ext' => implode('', $_POST['phone'])),
			"`id` = :id",
			array('id'=>user::$id)
		);

		feedback::add_success_msg("Phone Successfully Updated.");
	}
	
	public function display_change_password()
	{
		?>
			<h1>Change Password</h1>
			<p>Please use at least 6 characters.</p>
			<table id="change_password_table">
				<tbody>
					<tr>
						<td>New Password</td>
						<td><input type="password" name="new_pass" /></td>
					</tr>
					<tr>
						<td>Confirm New Password</td>
						<td><input type="password" name="new_pass_confirm" /></td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" value="Submit" a0="action_change_password" /></td>
					</tr>
				</tbody>
			</table>
		<?php
	}

	public function pre_output_asana_oauth()
	{
		util::load_lib('asana_lib');

		$asana = new AsanaLib();

		$code =& $_REQUEST['code'];

		$access_token = $asana->oauth($code);

	    if (isset($_COOKIE['redirect_url'])){
	    	cgi::redirect($_COOKIE['redirect_url'].'?access_token='.$access_token);
	    }
	}

	public function display_asana_oauth()
	{
		
	}

	public function sts_asana_oauth_refresh()
	{
		if(empty($_REQUEST['username'])) return;

		$username =& $_REQUEST['username'];

		util::load_lib('asana_lib');

		$asana = new AsanaLib();
		$access_token = $asana->oauth_refresh($username);

		echo $access_token;
	}

	public function sts_asana_oauth()
	{
		if(empty($_REQUEST['username'])) return;

		$username =& $_REQUEST['username'];

		//1st, we look up the record for this users
		$user_id = db::select_one("SELECT id FROM users WHERE username='$username' LIMIT 1");

		util::load_lib('asana_lib');

		$asana = new AsanaLib();
		$access_token = $asana->get_token($user_id);

		if(!$access_token){
			echo 'FALSE';
		} else {
			echo $access_token;
		}
	}
	
	protected function action_change_password()
	{
		$new_pass = $_POST['new_pass'];
		$new_pass_confirm = $_POST['new_pass_confirm'];
		if (strlen($new_pass) < 6)
		{
			feedback::add_error_msg("Password is too short");
			return;
		}
		if ($new_pass != $new_pass_confirm)
		{
			feedback::add_error_msg("Passwords do not match");
			return;
		}
		db::update(
			"eppctwo.users",
			array("password" => util::passhash($new_pass, user::$name)),
			"id = '".user::$id."'"
		);
		feedback::add_success_msg("Password Successfully Updated.");
	}
	
}









?>