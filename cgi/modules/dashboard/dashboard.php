<?php
class mod_dashboard extends module_base
{
	protected $m_name = 'home';
	
	public function pre_output()
	{
	}
	
	public function output()
	{
		if (user::is_logged_in()) $this->e2_home();
		else $this->guest_home();
	}
	
	protected function e2_home()
	{
		$nav = user::get_banner_nav_options();
		for ($i = 0, $ci = count($nav); $i < $ci; ++$i) {
			list($url, $text, $depth) = $nav[$i];
			echo '<p>'.str_repeat('&nbsp; &nbsp;', $depth * 2).'<a href="'.$url.'">'.$text.'</a></p>'."\n";
		}
	}
	
	protected function guest_home()
	{
		?>
		welcome guest
		<?php
	}
}









?>