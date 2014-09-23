<?php

class Blue_Dragon
{
	public function draw()
	{
		?>
<!doctype html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<title>Essential Strikes Back</title>
		<link rel="icon" type="image/ico" href="/favicon.ico" />
		<?php cgi::print_css(); ?>
		<?php cgi::print_js(); ?>
	</head>
	<body>
		<form id="f" method="post" enctype="multipart/form-data">
		<div id="container">
			<div id="page_head">
				<?php $this->print_page_head(); ?>
			</div>
			<div id="spacer"></div>
			<div id="page_body" class="cha">
				<?php $this->print_page_body(); ?>
				<img class="corner_tl" src="/img/body_tl.png" />
				<img class="corner_tr" src="/img/body_tr.png" />
				<img class="corner_br" src="/img/body_br.png" />
				<img class="corner_bl" src="/img/body_bl.png" />
			</div>
			<div id="page_foot">
				<div id="footer">&copy; <?php echo date('Y'); ?> | <?php echo gethostname(); ?></div>
				<?php $this->print_page_foot(); ?>
			</div>
		</div>
		<input type="hidden" name="cl_type" value="<?php echo g::$cl_type; ?>" />
		</form>
		<div class="clear"></div>
	</body>
</html>

<?php
	}
	
	private function print_page_head()
	{
		$this->head_row_1();
		$this->head_row_2();
		$this->head_row_3();
	}
	
	private function head_row_1()
	{
		$row_1_func = (user::is_logged_in()) ? 'head_row_1_logged_in' : 'head_row_1_login_form';
		?>
		<div id="header_1">
			<span id="e2_logo"><a href="/"><img src="/img/logo.jpg" /></a></span>
			<?php $this->$row_1_func(); ?>
		</div>
		<?php
	}
	
	private function head_row_2()
	{
		if (!user::is_logged_in()) return;
		
		echo '<div id="header_2">'."\n";
		if (user::is_admin() && strpos(cgi::$url_path, 'client/search') !== 0) {
			$this->print_row_2_client_search();
		}
		$guild = g::$p1;
		if ($guild == 'ppc') {
			// recent client history
			if (!is_array(@$_SESSION['client_history'])) {
				$_SESSION['client_history'] = array_fill(0, CLIENT_HISTORY_LEN, 0);
			}
			else if (g::$client_id) {
				// if client is already in our history, start at that index and work to beginning, otherwise start at the end
				$start_index = (($cl_index = array_search(g::$client_id, $_SESSION['client_history'])) !== false) ? ($cl_index - 1) : (CLIENT_HISTORY_LEN - 2);
				for ($i = $start_index; $i > -1; --$i) {
					$_SESSION['client_history'][$i + 1] = $_SESSION['client_history'][$i];
				}
				$_SESSION['client_history'][0] = g::$client_id;
			}
			
			$tmp_clients = db::select("
				select c.id, c.name
				from clients c, clients_{$guild} {$guild}
				where
					c.id = {$guild}.client &&
					c.status = 'On'
					".cgi::get_client_list_user_query()."
				order by name asc
			", 'NUM', 0);
			
			$clients = array(array('', 'Jump to Client'));
			$cur_history_len = 0;
			for ($i = 0; $i < CLIENT_HISTORY_LEN; ++$i) {
				$cl_id = $_SESSION['client_history'][$i];
				$cl_name = $tmp_clients[$cl_id];
				if ($cl_name) {
					++$cur_history_len;
					$clients[] = array($cl_id, $cl_name);
				}
			}
			if ($cur_history_len > 0) {
				$clients[] = array('', '-------------------');
			}
			foreach ($tmp_clients as $cl_id => $cl_name) {
				$clients[] = array($cl_id, $cl_name);
			}
			
			?>
			<?php echo cgi::html_select('client', $clients, g::$client_id); ?>
			<?php
		}
		else {
			g::$module->call_member('print_header_row_2');
		}
		echo '</div>'."\n";
	}

	private function print_row_2_client_search()
	{
		?>
		<span>
			<label for="cs">Client Search</label>
			<input type="text" id="cs" name="cs" />
			<input type="submit" a0href="/client/search" a0="action_search" value="Submit" />
		</span>
		<?php
	}
	
	private function head_row_3()
	{
		if (!user::is_logged_in()) return;
		
		// can either be an array of menu items or a menu object
		// if we get an array of menu itmes, create a menu object
		$menu = (!empty(g::$module) && method_exists(g::$module, 'get_menu')) ? g::$module->get_menu() : '';
		if ($menu) {
			if (is_array($menu)) {
				$menu = new Menu($menu);
			}
			// <link to the left of menu>
			if (isset($menu->base_link) && $menu->base_link === false) {
				$ml_base = '';
			}
			else {
				if (isset($menu->base_link)) {
					$ml_base = '<a class="mod_base_link" href="'.$menu->base_link[0].'">'.$menu->base_link[1].'</a>';
				}
				else if ($menu->base) {
					if (empty($ml_base)) {
						$parts = explode('/', $menu->base);
						$ml_base_url = '';
						$ml_base = '';
						foreach ($parts as $part) {
							if ($ml_base) {
								$ml_base .= ' / ';
								$ml_base_url .= '/';
							}
							$ml_base_url .= $part;
							$ml_base .= '<a class="mod_base_link" href="'.cgi::href($ml_base_url).'">'.util::display_text($part).'</a>';
						}
					}
				}
				else {
					$ml_base = '<a class="mod_base_link" href="'.cgi::href(g::$p1).'">'.util::display_text(g::$p1).'</a>';
				}
			}
			// </link to the left of menu>

			$ml_links = $menu->to_ml();
			if ($ml_base && $ml_links) {
				$ml_base .= ' &nbsp;&bull;&nbsp; ';
			}
		}
		else
		{
			$ml_base = '<a class="mod_base_link" href="/'.g::$p1.'/">'.util::display_text(g::$p1).'</a>';
			$ml_links = '';
		}
		?>
		<div id="header_3">
			<?php echo $ml_base; ?>
			<?php echo $ml_links; ?>
			<img class="corner_br" src="/img/header_br.jpg" />
			<img class="corner_bl" src="/img/header_bl.jpg" />
		</div>
		<?php
	}
	
	private function head_row_1_logged_in()
	{
		?>
		<span id="h1_links">
		<?= user::get_banner_link() ?>
		<?php
		self::head_row_1_show_modules();
		echo ((user::is_developer(1)) ? '<a href="'.cgi::href('administrate/').'">Admin</a>' : '');
		echo (((user::is_developer(1)) && !dbg::is_on()) ? '<a id="dbg_start_link" href="'.$_SERVER['REQUEST_URI'].'">dbg</a>' : '');
		?>
		<a target="_blank" href="<?php echo cgi::href('wiki'); ?>">Wiki</a>
		<a href="<?php echo cgi::href('my_account'); ?>">My Account</a>
		<a href="<?php echo cgi::href('timecard'); ?>">Timecard</a>
		<a href="<?php echo cgi::href('help'); ?>">Help</a>
		</span>
		<?php
		echo (((user::is_developer(1)) && dbg::is_on()) ? '<div id="dbg_stop_div"><a id="dbg_stop_link" href="'.$_SERVER['REQUEST_URI'].'">dbg stop</a></div>' : '');
	}
	
	private function head_row_1_show_modules()
	{
		// cases where we need to show user module select
		$options = user::get_banner_nav_options();
		//print_r($options);
		if (count($options) > 1) {
			array_unshift($options, array('', ' - Go To - '));
			for ($i = 0, $ci = count($options); $i < $ci; ++$i) {
				list($path, $text, $depth) = $options[$i];
				if ($depth > 0) {
					$options[$i][1] = str_repeat(' -- ', $depth).$options[$i][1];
				}
			}
			echo cgi::html_select('module_select', $options);
		}
	}
	
	private function head_row_1_login_form()
	{
		?>
		<div id="login_div">
			user <input id="login_user" type="text" name="username" focus_me=1 />
			pass <input id="login_pass" type="password" name="password" />
			<input type="submit" a0="login" value="Go" />
		</div>
		<img class="corner_br" src="/img/header_dark_br.jpg" />
		<img class="corner_bl" src="/img/header_dark_bl.jpg" />
		<?php
	}
	
	private function print_page_body()
	{
		// this should be done earlier, eg cgi::init_module
		if (!g::$module->is_url_display_func())
		{
			g::$pages[] = g::$module->get_display_default();
		}
		$num_pages = count(g::$pages);
		for ($i = 0; $i < $num_pages; ++$i)
		{
			$page = g::$pages[$i];
			if (empty($page)) break;
			echo '<div id="'.$page.'">'."\n";
		}
		
		cgi::display_page_body();
		
		for ($i = 0; $i < $num_pages; ++$i)
		{
			$page = g::$pages[$i];
			if (empty($page)) break;
			echo "</div>\n";
		}
	}
	
	private function print_page_foot()
	{
		feedback::output();
		cgi::add_js_var('pages', g::$pages);
		cgi::print_js_vars();
	}
}

?>