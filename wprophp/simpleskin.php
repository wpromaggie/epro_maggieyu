<?php
/* ----
// Description:
	mLib Simple Skin
// Programmers:
 *  cp C-P
 * 	kk Koding Kevin
 * 	mc Merlin Corey
 *	vy Vyrus001
// History:
	0.0.3 2007September07 Updated with new wpro module based framework
	0.0.2 2005December13 Small changes
	0.0.1 2005December08 Initial version
---- */

if (class_exists('mod_simpleskin'))
{
	trigger_error('Class "simpleskin" already exists!', E_USER_WARNING);
}
else
{
	class mod_simpleskin_start extends module_base
	{
		protected $m_name = 'simpleskin_start';
		private $m_nav = array();
		
		public function pre_output()
		{
			global $wpro;
			$wpro['skin']->Head->Add_Css($wpro['paths']->get('css').'simple.css');
		}
		
		public function output()
		{
			echo("\t\t<div id = \"container\">\n");
			$this->navigation();
			echo("\t\t\t<div id = \"content\">\n");
		}
		
		function add_nav($sHref, $sCaption, $vSubItems = array())
		{
			$this->vNav[] = array('href' => $sHref, 'caption' => $sCaption, 'subitems' => $vSubItems);
		}
		
		function add_nav_sub($sHref, $sCaption)
		{
			$this->vNav[count($this->vNav) - 1]['subitems'][] = array('href' => $sHref, 'caption' => $sCaption);
		}	
		
		public function navigation()
		{
			if (count($this->vNav))
			{
				echo("\n\t\t<div id = \"nav\">".
					"\t\t\t<ul>\n");

				$nCount = count($this->vNav);
				for ($nCur = 0; $nCur < $nCount; $nCur++)
				{
					echo("\t\t\t\t<li><a href = \"".$this->vNav[$nCur]['href'].'">'.$this->vNav[$nCur]['caption']."</a>\n");
					$nSubCount = count($this->vNav[$nCur]['subitems']);
					if ($nSubCount)
					{
						echo("\t\t\t\t<li>\n\t\t\t\t\t<ul>\n");
						for ($nCurSub = 0; $nCurSub < $nSubCount; $nCurSub++)
						{
							echo("\t\t\t\t<li><a href = \"".$this->vNav[$nCur]["subitems"][$nCurSub]["href"].'">'.$this->vNav[$nCur]["subitems"][$nCurSub]["caption"]."</a>\n");		
						}
						echo("\t\t\t\t\t</ul></li>\n");
					}
					echo("\t\t\t\t</li>\n");
					
				}
				
				echo("\t\t\t</ul>\n\t\t</div> <!-- /nav -->\n");
			}	
		} // Navigation

		function PostHeaderStart()
		{
			echo("\n\t\t\t<div>\n\t\t\t\t<h1>");
		}
		
		function PostHeaderEnd()
		{
			echo("</h1>\n");
		}
		
		function PostHeader($sSubject)
		{
			$this->PostHeaderStart();
			echo($sSubject);
			$this->PostHeaderEnd();
		}

		function PostBodyStart()
		{
			echo("\t\t\t\t<p>");
		}
		
		function PostBodyEnd()
		{
			echo("</p>\n\t\t\t</div>\n");
		}
		
		function PostBody($sBody)
		{
			$this->PostBodyStart();
			echo($sBody);
			$this->PostBodyEnd();
		}

		function PostHeaderBodyStart($sSubject)
		{
			$this->PostHeader($sSubject);
			$this->PostBodyStart();
		}
		
		function Post($sSubject, $sBody)
		{
			$this->PostHeader($sSubject);
			$this->PostBody($sBody);
		}	
	};

	class mod_simpleskin_end extends module_base
	{	
		protected $m_name = 'simpleskin_end';
		
		public function output()
		{
			global $wpro;
			
			echo("\t\t\t</div> <!-- /content -->\n");
			echo("\t\t\t<div class = \"clearer\"></div> <!-- /clearer -->\n");
			
			echo("\t\t\t<div id = \"footer\">\n");
			echo("\t\t\t\t<p>wpro::php ".$wpro["version"]." using simpleskin</p>\n");		
			echo("\t\t\t</div> <!-- /footer -->\n");
			
			echo("\t\t</div> <!-- /container -->\n");
		}
	};
}

?>