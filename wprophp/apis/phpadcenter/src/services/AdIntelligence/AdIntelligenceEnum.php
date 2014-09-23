<?php
namespace phpadcenter\services\AdIntelligence;

class AdIntelligenceEnum
{
	public static $AdPosition       = array('All','MainLine1','MainLine2','MainLine3','MainLine4','SideBar1','SideBar2','SideBar3','SideBar4','SideBar5','SideBar6','SideBar7','SideBar8','SideBar9','SideBar10','Aggregate');
	public static $Currency         = array('CanadianDollar','EURO','SingaporeDollar','UKPound','USDollar','IndianRupee','ArgentinePeso','Bolivar','ChileanPeso','ColombianPeso','DanishKrone','MexicanPeso','NorwegianKrone','NuevoSol','SwedishKrona','SwissFranc');
	public static $MatchType        = array('Exact','Phrase','Broad','Content','Aggregate');
	public static $Scale            = array('Minimal','Low','Medium','High','VeryHigh');
	public static $TargetAdPosition = array('MainLine1','MainLine','SideBar');
	public static $TimeInterval     = array('Last30Days','Last7Days','LastDay');
}

?>