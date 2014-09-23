<?php

class Cameo
{
	private static $asset_path = '/skins/cameo/';

	public function draw()
	{               
?>
<!DOCTYPE html>
<html class="no-js">

<head>
    <!-- meta -->
    <meta charset="utf-8">
    <meta name="description" content="Flat, Clean, Responsive, admin template built with bootstrap 3">
    <meta name="viewport" content="width=device-width, user-scalable=1, initial-scale=1, maximum-scale=1">

    <title>You Fancy Now | Essential PPC</title>

    <!-- bootstrap -->
    <link rel="stylesheet" href="<?= self::$asset_path; ?>bootstrap/css/bootstrap.min.css">
    <!-- /bootstrap -->

    <!-- core styles -->
    <link rel="stylesheet" href="<?= self::$asset_path; ?>css/palette.1.css" id="skin">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>css/font.style.1.css" id="font">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>css/main.css">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>css/animate.min.css">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>vendor/offline/theme.css">
    <!-- /core styles -->

    <!-- page level styles -->
    <link rel="stylesheet" href="<?= self::$asset_path; ?>vendor/bootstrap-select/bootstrap-select.css">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>vendor/dropzone/dropzone.css">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>vendor/slider/slider.css">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>vendor/bootstrap-datepicker/datepicker.css">
    <link rel="stylesheet" href="<?= self::$asset_path; ?>vendor/timepicker/jquery.timepicker.css">
    <!-- /page level styles -->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- load modernizer -->
    <script src="<?= self::$asset_path; ?>vendor/modernizr.js"></script>
</head>

<!-- body -->

<body>

	<form id="f" method="post" enctype="multipart/form-data" class="parsley-form" data-parsley-validate>

	    <div class="app">

	        <!-- top header -->
	        <header class="header header-fixed navbar">

	            <div class="brand">
	                <a href="javascript:;" class="fa fa-bars off-left visible-xs" data-toggle="off-canvas" data-move="ltr"></a>

	                <a href="/" class="navbar-brand text-white">
	                    <i class="fa fa-stop mg-r-sm"></i>
	                    <span class="heading-font">
	                        ESSENTIAL<b>PPC</b> 
	                    </span>
	                </a>
	            </div>

	            <div class="collapse navbar-collapse pull-left no-padding" id="hor-menu">
	                <ul class="nav navbar-nav">
	                    <? $this->headnav() ?>
	                </ul>
	            </div>
	           
	           <div class="navbar-form navbar-left hidden-xs" role="search">
	                <div class="form-group">
	                    <button class="btn no-border no-margin bg-none no-pd-l" a0href="/client/search" a0="action_search" type="submit">
	                        <i class="fa fa-search"></i>
	                    </button>
	                    <input type="text" id="cs" name="cs" class="form-control no-border no-padding search" placeholder="Client Search" data-parsley-ui-enabled="false">
	                </div>
	            </div>

	            <ul class="nav navbar-nav navbar-right off-right">
	                <li class="hidden-xs">
	                    <a href="<?= cgi::href('/my_account') ?>">
	                        <?= user::$realname ?>
	                    </a>
	                </li>

	                <li class="quickmenu mg-r-md">
	                    <a href="javascript:;" data-toggle="dropdown">
	                        <img src="<?= user::gravatar_path() ?>?s=30" class="avatar pull-left img-circle" alt="user" title="user">
	                        <i class="caret mg-l-xs hidden-xs no-margin"></i>
	                    </a>
	                    <ul class="dropdown-menu dropdown-menu-right mg-r-xs">
	                    	
	                        <li>
	                            <a href="<?= cgi::href('/my_account/change_password') ?>">Edit Password</a>
	                        </li>
	                        <li>
	                            <a href="<?= cgi::href('/my_account/change_phone') ?>">Edit Phone Number</a>
	                        </li>
	                        <li>
	                            <a href="<?= cgi::href('/help') ?>">Help</a>
	                        </li>
	                        <li class="divider"></li>
	                        <li>
	                            <a href="javascript:;" class="logout">Logout</a>
	                        </li>
	                    </ul>
	                </li>
	            </ul>
	        </header>
	        <!-- /top header -->

	        <section class="layout">
	            <!-- sidebar menu -->
	            <aside class="sidebar collapsible canvas-left">
                	<div class="scroll-menu">
		                <!-- main navigation -->
		                <nav class="main-navigation slimscroll" data-height="auto" data-size="4px" data-color="#ddd" data-distance="0">

		                    <ul>
		                    	<? $this->sidenav() ?>
		                    </ul>
		                </nav>
		                <!-- /main navigation -->
		            </div>
	                <!-- footer -->
	                <footer>
	                    <div class="footer-toolbar pull-left">

	                        <a href="javascript:;" class="toggle-sidebar pull-right hidden-xs">
	                            <i class="fa fa-angle-left"></i>
	                        </a>
	                    </div>
	                </footer>
	                <!-- /footer -->

	                
	            </aside>
	            <!-- /sidebar menu -->

	            <!-- main content -->
	            <section class="main-content">

	                <!-- content wrapper -->
	                <div class="content-wrap">
	                    
	                	<?php $this->print_page_body(); ?>

	                </div>
	                <!-- /content wrapper -->

	            </section>
	            <!-- /main content -->

	        </section>

	    </div>

	    <input type="hidden" name="cl_type" value="<?php echo g::$cl_type; ?>" />

	</form>

	<?php 
		// overwite old js files with the cameo versions
		cgi::add_js('cameo.lib.feedback.js');
	?>
	<?php cgi::print_js(); ?>

    <!-- core scripts -->
    <!--<script src="<?= self::$asset_path; ?>vendor/jquery-1.11.1.min.js"></script>-->
    <script src="<?= self::$asset_path; ?>bootstrap/js/bootstrap.js"></script>



    <!-- /core scripts -->

    <!-- page level scripts -->
    <script src="<?= self::$asset_path; ?>vendor/jquery.slimscroll.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/bootstrap-select/bootstrap-select.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/dropzone/dropzone.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/parsley.min.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/jquery.maskedinput.min.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/fuelux/checkbox.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/fuelux/radio.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/fuelux/wizard.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/fuelux/pillbox.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/fuelux/spinner.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/slider/bootstrap-slider.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/wysiwyg/jquery.hotkeys.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/wysiwyg/bootstrap-wysiwyg.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/switchery/switchery.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/timepicker/jquery.timepicker.js"></script>
    <!-- /page level scripts -->

    <!-- theme scripts -->
    <script src="<?= self::$asset_path; ?>js/off-canvas.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/jquery.placeholder.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/offline/offline.min.js"></script>
    <script src="<?= self::$asset_path; ?>vendor/pace/pace.min.js"></script>
    <script src="<?= self::$asset_path; ?>js/main.js"></script>
    <!-- /theme scripts -->

    <? $this->print_page_foot(); ?>
	
</body>
<!-- /body -->

</html>


<?php
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

	private function headnav()
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
					$ml_base = '<li><a class="mod_base_link" href="'.$menu->base_link[0].'"><span>'.$menu->base_link[1].'</a></span></li>';
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
							$ml_base .= '<li><a class="mod_base_link" href="'.cgi::href($ml_base_url).'"><span>'.util::display_text($part).'</span></a></li>';
						}
					}
				}
				else {
					$ml_base = '<li><a class="mod_base_link" href="'.cgi::href(g::$p1).'"><span>'.util::display_text(g::$p1).'</span></a></li>';
				}
			}
			// </link to the left of menu>

			$ml_links = $menu->to_headnav();
			if ($ml_base && $ml_links) {
				//$ml_base .= ' &nbsp;&bull;&nbsp; ';
			}
		}
		else
		{
			$ml_base = '<li><a class="mod_base_link" href="/'.g::$p1.'/"><span>'.util::display_text(g::$p1).'</span></a>';
			$ml_links = '';
		}
		echo $ml_base;
		echo $ml_links;
	}

	private function sidenav()
	{
		$nav = '';
		$dropdown_menu = '';
		$class = '';

		$options = user::get_sidebar_links();

		if (count($options) > 1) {
			for ($i = 0, $ci = count($options); $i < $ci; ++$i) {
				list($path, $text, $depth, $sub) = $options[$i];

				$formatted_text = '<span>'.$text.'</span>';

				if(!empty($sub)){

					//e($sub);

					for ($j = 0; $j < count($sub); ++$j) {

						list($sub_path, $sub_text) = $sub[$j];

						$dropdown_menu .= '
                            <li>
                                <a href="'.cgi::href($sub_path).'">
                                    <span>'.$sub_text.'</span>
                                </a>
                            </li>
						';

					}

					$dropdown_menu = '<ul class="dropdown-menu">'.$dropdown_menu.'</ul>';

					$class = 'dropdown show-on-hover';

					$formatted_text .= '<i class="toggle-accordion"></i>';

				}

				
				$nav .= '
					<li class="'.$class.'">
                        <a href="'.cgi::href($path).'">
                            <i class="fa '.self::icon_map($text).'"></i>
                            '.$formatted_text.'
                        </a>
                        '.$dropdown_menu.'
                    </li>';

                $dropdown_menu = '';
				
			}
			echo $nav;
		}
	}

	private static function icon_map($text="")
	{
		switch(strtolower($text)){
			case 'accounting': return 'fa-dollar';
			case 'agency services': return 'fa-database';
			case 'camp dev': return 'fa-wrench';
			case 'client': return 'fa-user';
			case 'client reports': return 'fa-file-excel-o';
			case 'contracts': return 'fa-list';
			case 'hr': return 'fa-users';
			case 'marketing': return 'fa-image';
			case 'sap': return 'fa-list';
			case 'sales': return 'fa-phone-square';
			case 'sbr': return 'fa-building';
			case 'small business': return 'fa-building';
			case 'surveys': return 'fa-list-alt';
		}

		return 'fa-coffee';
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