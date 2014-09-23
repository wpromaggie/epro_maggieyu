<?php

class mod_client_reports extends module_base
{
	protected $m_name = 'client_reports';
	
	public function get_menu(){
		$menu = array();
		$menu[] = new MenuItem('View All',array('client_reports','view_all'));
		$menu[] = new MenuItem('Create New',array('client_reports','create'));
		return $menu;
	}

	public function pre_output(){

	}

	public function output(){

	}

	public function display_index(){

	}
}
?>