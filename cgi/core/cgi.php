<?php

// where should these go?
define('TO_JS_INCLUDE_TAGS', 1);
define('TO_JS_NO_JSON', 2);

define('CLIENT_HISTORY_LEN', 5);
define('ENDL', "<br>\n");

require('../common/env.php');

require('globals.php');
require('dbg.php');
require('session.php');
require('user.php');
require('feedback.php');
require('menu.php');
require('imaging.php');
require('module_base.php');

require(\epro\COMMON_PATH.'db.php');
require(\epro\COMMON_PATH.'rs/rs.php');
require(\epro\COMMON_PATH.'util.php');

class cgi
{
	const VIEW_DIR = 'views';
	
	// set in init_module
	public static $ip, $url_path;
	public static $view_file = '';
	private static $dynamic_content = array();

	// todo: minify js and css that is included on every request
	private static $js = array(
	    'jquery.js', 'jquery-ui.js', 'jquery.wpro.util.js', 'jquery.wpro.table.js', 'jquery.wpro.date_picker.js', 'jquery.wpro.time_picker.js', 'jquery.wpro.date_range.js',
	    'global.js',
	    'e2.js', 
	    'ability.post.js',
	    'core.window.js', 'core.strings.js', 'core.regexp.js', 'core.array.js', 'core.date.js', 'core.math.js',
	    'lib.json.js', 'lib.format.js', 'lib.types.js', 'lib.html.js', 'lib.options.js', 'lib.feedback.js', 'lib.cookie.js', 'lib.effects.js',
	    'ajax.js'
	);
	private static $css = array('global.css', 'e2.css', 'jquery-ui.css', 'jquery.wpro.util.css', 'wpro_table.css');
	private static $js_paths = array('', 'js/');
	private static $css_paths = array('', 'css/');
	private static $js_vars = array();
	private static $file_versions = array();
	private static $url_params_for_removal = false;

	private static $is_ajax = false;

	public static function init()
	{
		util::load_lib('e2', 'log', 'delly', 'account');
		log::init();
		set_time_limit(0);

		db::connect(\epro\DB_HOST, \epro\DB_USER, \epro\DB_PASS, \epro\DB_NAME);
		cgi::init_session();
		dbg::init();
		feedback::init();
		cgi::init_common_vars();
		cgi::init_pages();
		g::$module = cgi::init_module(g::$pages);
		cgi::set_default_view_file();

		if (cgi::process_ajax_request()) exit(0);
		if (cgi::process_sts_request()) exit(0);
		
		g::$module->check_action();
		cgi::init_head();
	}

	public function set_action($new_action)
	{
		cgi::init_module(g::$p1.'/'.$new_action);
	}

	private static function init_session()
	{
		cgi::$ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		// php hangs on session_start for multiple requests for same session
		// so to run ajax requests in parallel, cannot start session
		if (cgi::get_ajax_request() !== false && array_key_exists('_parallel_', $_POST)){
			return;
		}

		Session::init('eppctwo.user_session');

		if ($_REQUEST['a0'] == 'login') {
			if (user::login($_REQUEST['username'], $_REQUEST['password'])) {
				user::init($_SESSION['id']);
			}
			else {
				feedback::add_error_msg('Failure logging in with username/password combination');
			}
		}
		else if ($_REQUEST['go'] == 'logout') {
			user::logout();
		}
		// check for persistent cookie
		else if (!user::is_logged_in() && user::is_epro_cookie_set()) {
			$user_id = user::get_id_from_cookie();
			user::init($user_id);
		}
		else if (user::is_logged_in()) {
			user::init($_SESSION['id']);
		}
	}

	private static function init_common_vars()
	{
		// bleh die
		g::$company = 1;
		g::$client_id = $_REQUEST['client'];
		if (empty(g::$client_id)) {
			if (!empty($_REQUEST['cl_id'])) g::$client_id = $_REQUEST['cl_id'];
			else if (!empty($_REQUEST['client'])) g::$client_id = $_REQUEST['client'];
		}
	}
	
	private static function init_pages()
	{
		// get rid of leading slash
		$url = preg_replace("/^\\//", '', $_SERVER['REQUEST_URI']);

		// don't want query string
		if (($i = strpos($url, '?')) !== false) $url = substr($url, 0, $i);

		// trim "/" from end
		cgi::$url_path = rtrim($url, '/');

		g::$pages = explode('/', trim($url, '/'));
		list(g::$p1, g::$p2, g::$p3, g::$p4, g::$p5) = g::$pages;

		if (
			(empty(g::$p1) || !file_exists(\epro\CGI_PATH.'modules/'.g::$p1.'/'.g::$p1.'.php') || !user::has_module_access()) &&
			(cgi::get_sts_request() === false)
		) {
			$default_module = 'dashboard';
			g::$p1 = $default_module;
			g::$pages = array($default_module);
		}
	}
	
 	public static function init_module($pages)
	{
		// build class names, require all we find, instantiate the most specific
		$classes = array();
		$base_path = \epro\CGI_PATH.'modules/';
		$dir_test = $base_path;
		$base_page_name = '';
		$class_name = 'mod';
		$index = 0;
		for ($i = 0, $ci = count($pages); $i < $ci; ++$i) {
			$page = $pages[$i];
			$base_page_name .= (($i) ? '.' : '').$page;
			$class_name .= '_'.$page;
			$dir_test .= $page.'/';

			// directories take precedence over dots
			// found a subdir! update base path and reset base_page_name
			if (is_dir($dir_test)) {
				$base_path = $dir_test;
				$base_page_name = $page;
				
				// load lib
				util::load_lib($page);
			}

			$filepath = $base_path.$base_page_name.'.php';
			if (file_exists($filepath)) {
				require_once($filepath);
				$classes[] = $class_name;
				$index = $i;
			}
		}
		
		if ($classes) {
			$class_name = array_pop($classes);
			$mod = new $class_name($index);
			$mod->pre_output_base();
			return $mod;
		}
		else {
			return false;
		}
	}

	public static function is_global_module($mod = null)
	{
		if (!$mod)
		{
			$mod = g::$p1;
		}
		return ($mod == 'dashboard' || $mod == 'my_account' || $mod == 'timecard' || $mod == 'delly');
	}

	public static function get_modules()
	{
		$args = @func_get_arg(0);
		
		// always exclude these
		$exclude = array(cgi::VIEW_DIR, 'sbs');
		if (@array_key_exists('exclude', $args))
		{
			$exclude = array_merge($exclude, $args['exclude']);
		}
		
		// get modules from files in content directory
		$modules = array();
		cgi::get_modules_recur($modules, \epro\CGI_PATH.'modules/', $exclude);
		return $modules;
	}
	
	private static function get_modules_recur(&$modules, $path, $exclude)
	{
		$path_len = strlen($path);
		foreach (glob($path.'*') as $child_path)
		{
			if (is_dir($child_path))
			{
				$mod_name = substr($child_path, $path_len);
				if (!in_array($mod_name, $exclude))
				{
					$modules[$mod_name] = array();
					cgi::get_modules_recur($modules[$mod_name], $child_path.'/', $exclude);
				}
			}
		}
		ksort($modules);
	}

	private static function init_head()
	{
		// check for file containing js and css version numbers
		if (file_exists(\epro\NO_CACHE_FILEPATH))
		{
			require(\epro\NO_CACHE_FILEPATH);
		}
		$mod_path = \epro\CGI_PATH.'modules/';
		$file_basename = '';
		
		$paths_added = array();
		for ($i = 0, $ci = count(g::$pages); $i < $ci; ++$i)
		{
			$mod_path_test = $mod_path.g::$pages[$i].'/';
			if (is_dir($mod_path_test))
			{
				$mod_path = $mod_path_test;
				$file_basename = g::$pages[$i];
			}
			else
			{
				$file_basename .= (($i) ? '.' : '').g::$pages[$i];
			}
			
			if (file_exists($mod_path.$file_basename.'.css')) cgi::add_css($file_basename.'.css', $mod_path);
			if (file_exists($mod_path.$file_basename.'.js'))  cgi::add_js($file_basename.'.js', $mod_path);
		}
	}
	
	public static function include_widget($base)
	{
		// @todo what if a widget has the same name as a module? should this be allowed?
		// maybe prefix widget files with wid. eg example.css -> wid.example.css
		$widget_path = \epro\CGI_PATH.'widgets/'.$base.'/';
		if (file_exists($widget_path.$base.'.css'))
		{
			cgi::$css_paths[] = 'widgets/'.$base.'/';
			cgi::add_css($base.'.css');
		}
		if (file_exists($widget_path.$base.'.js'))
		{
			cgi::$js_paths[] = 'widgets/'.$base.'/';
			cgi::add_js($base.'.js');
		}
		return cgi::include_widget_php($base);
	}

	public static function include_widget_php($base)
	{
		$widget_path = \epro\CGI_PATH.'widgets/'.$base.'/';
		if (file_exists($widget_path.$base.'.php'))
		{
			require_once(\epro\CGI_PATH.'core/widget_base.php');
			require_once($widget_path.$base.'.php');

			$widget_name = 'wid_'.$base;
			if (method_exists($widget_name, 'init')) call_user_func(array($widget_name, 'init'));
			return true;
		}
		return false;
	}

	public static function add_js($file_name, $path = '')  { cgi::add_external('js', $file_name, $path); }
	public static function add_css($file_name, $path = '') { cgi::add_external('css', $file_name, $path); }
	
	private static function add_external($file_type, $file_name, $path = '')
	{
		//prevent duplicate includes
		if (in_array($file_name, cgi::$$file_type)) return;

		//if its a cameo file, replace the original version
		if (strpos($file_name, 'cameo.')===0){
			$og_file_name = str_replace('cameo.', '', $file_name);
			if(($key = array_search($og_file_name, cgi::$js)) !== false) {
			    cgi::$js[$key] = $file_name;
			    return;
			}
		}

		array_push(cgi::$$file_type, $file_name);

		if ($path)
		{
			$path = str_replace(\epro\CGI_PATH, '', $path);
			
			$path_key = $file_type.'_paths';
			if (!in_array($path, cgi::$$path_key))
			{
				array_push(cgi::$$path_key, $path);
			}
		}
	}
	
	public static function add_js_jquery_ui($file_name)
	{
		cgi::add_js('jquery-ui.js');
		cgi::add_js('jquery.ui.core.js');
		cgi::add_js('jquery.ui.widget.js');
		cgi::add_js($file_name);
	}

	private static function get_external_file_paths($type)
	{
		$key = $type.'_paths';
		return cgi::$$key;
	}

	public static function print_css()
	{
		cgi::print_externals('css', '<link type="text/css" rel="stylesheet" href="', '" />');
	}

	public static function print_js()
	{
		cgi::print_externals('js', '<script type="text/javascript" src="', '"></script>');
	}
	
	private static function print_externals($type, $pre, $post)
	{
		$paths = cgi::get_external_file_paths($type);

		foreach (cgi::$$type as $file_name)
		{
			$file_path = null;
			if (preg_match("/^http(s|):\/\//", $file_name))
			{
				$file_path = $file_name;
			}
			else
			{
				foreach ($paths as $path)
				{
					$test_path = $path.$file_name;
					if (file_exists(\epro\CGI_PATH.$test_path))
					{
						if (array_key_exists($test_path, cgi::$file_versions))
						{
							$file_path = $path.cgi::$file_versions[$test_path].'.'.$file_name;
						}
						else
						{
							$file_path = $test_path;
						}
						break;
					}
				}
				if ($file_path)
				{
					$file_path = '/'.$file_path;
				}
			}
			if ($file_path)
			{
				echo $pre.$file_path.$post."\n";
			}
		}
	}

	public static function error($msg)
	{
		echo '<div class="error">'.$msg.'</div>';
	}

	public static function ajax_error($msg, $is_fatal = true)
	{
		echo json_encode(array(
			'msg' => array(
				'type' => 'error',
				'text' => $msg
			)
		));
		if ($is_fatal) exit(0);
	}

	public static function ajax_success($msg)
	{
		echo json_encode(array(
			'msg' => array(
				'type' => 'success',
				'text' => $msg
			)
		));
	}

	private static function get_ajax_request()
	{
		$ajax_func = $_REQUEST['_ajax_func_'];
		if (empty($ajax_func)) return false;
		return $ajax_func;
	}

	private static function process_ajax_request()
	{
		$ajax_func = cgi::get_ajax_request();
		if ($ajax_func === false || empty(g::$module)) return false;

		cgi::$is_ajax = true;
		header("Cache-Control: no-cache");

		// see if it is a widget ajax call
		if (array_key_exists('_widget_', $_REQUEST))
		{
			$widget_name = $_REQUEST['_widget_'];

			if (preg_match("/^data\d+$/", $widget_name))
			{
				$widget_name = 'data';
			}

			if (cgi::include_widget_php($widget_name))
			{
				$widget_class = 'wid_'.$widget_name;
				$widget = new $widget_class();
				if (method_exists($widget, 'pre_output')) call_user_func(array($widget, 'pre_output'));
				$widget->$ajax_func();
			}
		}
		else
		{
			g::$module->process_ajax($ajax_func);
		}
		return true;
	}

	public static function is_ajax()
	{
		return cgi::$is_ajax;
	}

	// we should have a list of ips that can send sts requests
	private static function process_sts_request()
	{
		$sts_func = cgi::get_sts_request();
		if ($sts_func === false || empty(g::$module)) return false;
		
		if (
			util::is_dev() ||
			// wold
			cgi::$ip == '208.109.208.170' ||
			// wnew cluster
			cgi::$ip == '198.101.240.250' ||
			cgi::$ip == '198.61.220.248' ||
			cgi::$ip == '198.61.220.177'
		) {
			if (method_exists(g::$module, $sts_func)) {
				g::$module->$sts_func();
			}
		}
		return true;
	}

	// we should have a list of ips that can send sts requests
	private static function get_sts_request()
	{
		$sts_func = $_REQUEST['_sts_func_'];
		if (empty($sts_func)) return false;
		return $sts_func;
	}

	public static function redirect($url, $do_exit = true)
	{
		header('Location: '.((preg_match("#^http(s|)://#", $url) || $url[0] == '/') ? '' : '/').$url);
		if ($do_exit) exit(0);
	}
	
	public static function to_js($name, $var, $flags = TO_JS_INCLUDE_TAGS)
	{
		if ($flags & TO_JS_INCLUDE_TAGS) echo '<script type="text/javascript">';
		echo 'var '.$name.'='.(($flags & TO_JS_NO_JSON) ? $var : json_encode($var)).';';
		if ($flags & TO_JS_INCLUDE_TAGS) echo '</script>';
		echo "\n";
	}

	// generate absolute epro url
	// q is of type "mixed"
	// false to not append
	// true to append whatever query was part of current request
	// array can also be passed in which merges with request query
	public static function href($href, $q = false)
	{
		$url = (strpos($href, '/') === 0) ? $href : '/'.$href;
		if (!empty($q))
		{
			if (is_array($q))
			{
				parse_str($_SERVER['QUERY_STRING'], $q_request);
				$q_merged = array_merge($q_request, $q);
				$q_str = '';
				foreach ($q_merged as $k => $v)
				{
					if (!empty($q_str)) $q_str .= '&';
					$q_str .= $k.'='.urlencode($v);
				}
				$url .= '?'.$q_str;
			}
			else if (!empty($_SERVER['QUERY_STRING']))
			{
				$url .=  '?'.$_SERVER['QUERY_STRING'];
			}
		}
		return $url;
	}

	public static function add_js_var($key, $value, $json_encode = true)
	{
		if ($json_encode)
		{
			if (rs::is_rs_object($value))
			{
				$value = $value->json_encode();
			}
			else
			{
				$value = json_encode($value);
			}
		}
		cgi::$js_vars[] = array($key, $value);
	}

	public static function remove_url_param($p)
	{
		if (empty(self::$url_params_for_removal)) {
			self::$url_params_for_removal = array();
		}
		self::$url_params_for_removal[] = $p;
	}
	
	public static function print_js_vars()
	{
		if (!empty(self::$url_params_for_removal)) {
			self::$js_vars[] = array('url_params_for_removal', json_encode(self::$url_params_for_removal));
		}

		$js = '';
		for ($i = 0; list($key, $value) = cgi::$js_vars[$i]; ++$i)
		{
			$js .= 'window.globals[\''.$key.'\'] = '.$value.";\n";
		}
		?>
<script type="text/javascript">
/* <![CDATA[ */
window.globals = {};
<?php echo $js; ?>
/* ]]> */
</script>
		<?php
	}

	// select, checkboxes and radio are cool, but..
	// they should only output data, html should be done in js
	public function html_select($name, $options, $selected = null, $attrs = null)
	{
		if (!is_array($options[0]))
		{
			$tmp_options = array();
			foreach ($options as $option)
				$tmp_options[] = array($option, $option);
			$options = $tmp_options;
		}
		$ml_options = '';
		$found_selected = false;
		for ($i = 0, $ci = count($options); $i < $ci; ++$i)
		{
			list($value, $text) = array_values($options[$i]);
			if (empty($text)) $text = $value;
			if ($value == $selected && !$found_selected)
			{
				$ml_selected = ' selected';
				$found_selected = true;
			}
			else
			{
				$ml_selected = '';
			}
			$ml_options .= '<option value="'.$value.'"'.$ml_selected.'>'.$text."</option>\n";
		}

		$ml_attrs = '';
		if (is_array($attrs))
		{
			foreach ($attrs as $k => $v)
			{
				$ml_attrs .= ' '.$k.'="'.htmlentities($v).'"';
			}
		}

		return '
			<select id="'.$name.'" name="'.$name.'"'.$ml_attrs.'>
				'.$ml_options.'
			</select>
		';
	}

	public static function html_checkboxes($name, $options, $selected = array(), $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'toggle_all' => true,
			'separator' => "<br />",
			'box_first' => false,
			'sequester_all' => false,
			'grouped' => false,
			'no_star' => false
		));
		if (!is_array($options[0])) {
			$tmp_options = array();
			foreach ($options as $option) {
				$tmp_options[] = array($option, $option);
			}
			$options = $tmp_options;
		}
		if (empty($selected) && !is_array($selected)) {
			$selected = false;
		}
		else if (is_string($selected) && $selected != '*') {
			$selected = explode("\t", $selected);
		}
		$ml_options = '';
		$grouped = array();
		for ($i = 0, $ci = count($options); $i < $ci; ++$i) {
			$option = $options[$i];
			if (isset($option[0])) {
				list($value, $text, $group) = $option;
			}
			else {
				list($value, $text, $group) = util::list_assoc($option, 'value', 'text', 'group');
			}
			if (empty($group)) $group = 'all';
			if (empty($text)) $text = $value;
			if ($selected !== false && ($selected == '*' || in_array($value, $selected))) {
				$ml_checked = ' checked';
				$span_class = ' class="on"';
			}
			else {
				$ml_checked = '';
				$span_class = '';
			}
			$id = "$name-$value";
			$ml_box = '<input type="checkbox" id="'.$id.'" name="'.$id.'" value="'.$value.'"'.$ml_checked.' />';
			$grouped[$group][] = '
				<span'.$span_class.'>
					'.(($opts['box_first']) ? $ml_box : '').'
					<label for="'.$id.'">'.$text.'</label>
					'.((!$opts['box_first']) ? $ml_box : '').'
				</span>
			';
		}
		foreach ($grouped as $group => $group_options) {
			$ml_options .= '<div>'.implode($opts['separator'], $group_options).'</div>';
		}

		if ($opts['toggle_all']) {
			$id = "$name-__toggle_all__";
			$ml_box = '<input type="checkbox" id="'.$id.'" value="1" class="toggle_all" />';
			$ml_options = '
				'.(($opts['sequester_all']) ? '<div>' : '').'
				<span>
					'.(($opts['box_first']) ? $ml_box : '').'
					<label for="'.$id.'">Toggle</label>
					'.((!$opts['box_first']) ? $ml_box : '').'
				</span>
				'.(($opts['sequester_all']) ? '</div><div>' : $opts['separator']).'
				'.$ml_options.'
				'.(($opts['sequester_all']) ? '</div>' : '').'
			';
		}

		return '
			<div id="'.$name.'" class="cboxes_wrapper"'.(($opts['no_star']) ? ' no_star="1"' : '').'>
				'.$ml_options.'
			</div>
		';
	}

	public static function get_posted_checkbox_value($x, $all_vals = false)
	{
		if ($x == '*') {
			return ($all_vals) ? $all_vals : $x;
		}
		else {
			$tmp = explode("\t", $x);
			return (count($tmp) == 1 && empty($tmp[0])) ? array() : $tmp;
		}
	}

	public static function html_radio($name, $options, $selected = null, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'separator' => '<br />',
			'wrapper' => 'span'
		));
		if (!is_array($options[0]))
		{
			$tmp_options = array();
			foreach ($options as $option)
			{
				$tmp_options[] = array($option, $option);
			}
			$options = $tmp_options;
		}
		$ml_options = '';
		for ($i = 0; list($value, $text) = $options[$i]; ++$i)
		{
			if (empty($text)) $text = $value;
			$ml_checked = ($selected == $value) ? ' checked' : '';
			$id = "$name-$value";
			$ml_options .= '
				<'.$opts['wrapper'].' class="w_radio_inner">
					<label for="'.$id.'">'.$text.'</label>
					<input type="radio" id="'.$id.'" name="'.$name.'" value="'.$value.'"'.$ml_checked.' />
				</'.$opts['wrapper'].'>
				'.$opts['separator'].'
			';
		}

		return '
			<div id="'.$name.'" class="w_radio_outer">
				'.$ml_options.'
			</div>
		';
	}

	public static function loading($hide = false)
	{
		return '<img class="loading'.(($hide) ? ' hide' : '').'" src="'.cgi::href('img/loading.gif').'" />';
	}

	public static function textarea_form($x)
	{
		$x = preg_replace("/<br.*?>/", "\n", $x);
		$x = str_replace(array('<', '>'), array('&lt;', '&gt;'), $x);
		return $x;
	}

	public static function textarea_display($x)
	{
		return str_replace(array('&lt;', '&gt;', "\n", "\r"), array('<', '>', "<br />\n", ''), $x);
	}

	public static function textarea_db($x)
	{
		return str_replace(array('&lt;', '&gt;', "\r"), array('<', '>', ''), $x);
	}

	public static function get_client_list_user_query()
	{
		if (user::is_admin())
		{
			return '';
		}
		else
		{
			$username = db::select_one("select username from users where id = :id", array('id' => $_SESSION['id']));;
			$guild = g::$p1;

			$query = array();
			$query[] = "{$guild}.manager = '{$username}'";
			if (g::$p1 == 'seo')
			{
				$query[] = "{$guild}.link_builder_manager = '{$username}'";
			}
		}
		return " && (".implode(" || ", $query).")";
	}

	public static function date_range_picker($start_date, $end_date, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'table' => true,
			'start_date_key' => 'start_date',
			'end_date_key' => 'end_date',
			'action' => null
		));
		$ml = '
			<tr>
				<td>'.util::display_text($opts['start_date_key']).'</td>
				<td><input type="text" class="date_input" name="'.$opts['start_date_key'].'" id="'.$opts['start_date_key'].'" value="'.$start_date.'" /></td>
			</tr>
			<tr>
				<td>'.util::display_text($opts['end_date_key']).'</td>
				<td><input type="text" class="date_input" name="'.$opts['end_date_key'].'" id="'.$opts['end_date_key'].'" value="'.$end_date.'" /></td>
			</tr>
		';

		if ($opts['table'])
		{
			$ml = '
				<table>
					<tbody>
						'.$ml.'
						<tr>
							<td></td>
							<td><input type="submit" value="Set Dates"'.(($opts['action']) ? ' a0="'.$opts['action'].'"' : '').' /></td>
						</tr>
					</tbody>
				</table>
			';
		}

		return $ml;
	}

	private static function set_default_view_file()
	{
		$pages_str = implode('.', g::$pages);

		// if mod class is same as pages str, see what default display page is
		// even better: add display default to g::$pages when this is the case
		if (str_replace('.', '_', $pages_str) == preg_replace("/^mod_/", '', get_class(g::$module)))
		{
			$pages_str .= '.'.g::$module->get_display_default();
		}
		cgi::$view_file = $pages_str.'.phtml';
	}

	public static function display_page_body()
	{
		g::$module->call_member('head');
		g::$module->output();
		$view_file_path = \epro\CGI_PATH.'modules/'.g::$p1.'/'.cgi::VIEW_DIR.'/'.cgi::$view_file;
		if (file_exists($view_file_path))
		{
			extract(cgi::$dynamic_content);
			require($view_file_path);
		}
	}

	public static function set($k, $v)
	{
		cgi::$dynamic_content[$k] = $v;
	}

	/**
	 * Checks $_POST to find the values of the given variable names if they are set.
	 * @param array $var_names An array of Strings representing the variable names to check for.
	 * @param boolean $include_blank_missing If true, sets blank and missing variables to ''.
	 * @return mixed An array of values with var_names (or some subset of var_names) as keys.
	 */
	public static function get_post_vars($var_names, $include_blank_missing = FALSE)
	{
		$values = array();

		if ($include_blank_missing) {
			foreach ($var_names as $var_name) {
				if (isset($_POST[$var_name])) {
					$values[$var_name] = $_POST[$var_name];
				} else {
					$values[$var_name] = '';
				}
			}
		} else {
			foreach ($var_names as $var_name) {
				if (isset($_POST[$var_name]) && $_POST[$var_name] != '') {
					$values[$var_name] = $_POST[$var_name];
				}
			}
		}

		return $values;
	}
	
	public static function load_chain_mod($path, $extends)
	{
		// include js file if it exists
		$js_test = str_replace('.chn.php', '.js', $path);
		if (file_exists($js_test))
		{
			$js_file = basename($js_test);
			$js_path = str_replace($js_file, '', $js_test);
			cgi::add_js($js_file, $js_path);
		}
		
		// get rid of .chn.php at the end
		// and then replace "." with "_" as per normal modules
		$mod_name = 'mod_'.str_replace('.', '_', str_replace('.chn.php', '', basename($path)));
		
		// get rid of everything in $path up to and including the class definition
		// so that we can replace with out own
		$code = file_get_contents($path);
		if (!preg_match("/.*?^class.*?$/ms", $code, $matches))
		{
			echo "could not load chain mod: $path, $extends\n";
			exit(1);
		}
		$code = "class {$mod_name} extends {$extends}\n".substr($code, strlen($matches[0]));
		
		// "require" our chain class
		eval($code);
		return $mod_name;
	}
}

?>