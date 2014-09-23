<?php

/**
 * SAP Stuff
 */
class mod_sap extends module_base
{
	public function get_menu()
	{
		$sap_menu = array();
		$sap_menu[] = new MenuItem('Prospect', array('sap','prospect'));
		$sap_menu[] = new MenuItem('Proposal', array('sap','edit'));
		$sap_menu[] = new MenuItem('Admin Default', array('sap','edit','default'));
		$sap_menu[] = new MenuItem('Admin Packages', array('sap','package'));
		return $sap_menu;
	}

	public function display_index()
	{
		echo 'Hello there <br />';
	}
}
?>