<?php
/* ----
 * Description:
 * 	Base affiliate api object
 * Programmers:
 *	cp C-P
 *	km K-Money
 *	mc Merlin Corey
 *	vy1 Vyrus001
 * History:
 *	0.0.1 2008February18 Initial version
 * ---- */

if (class_exists('base'))
{
	trigger_error('Error: "base" already exists!', E_USER_WARNING);
}
else
{
	abstract class base_affiliate extends base_soap
	{
		protected $m_name;
		
	}; // base_affiliate	
}

?>