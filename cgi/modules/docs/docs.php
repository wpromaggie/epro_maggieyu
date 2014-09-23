<?php

class mod_docs extends module_base
{
	public function display_index()
	{
		?>
		<h1>Docs</h1>

		<!-- directories -->
		<h2>Directory Structure</h2>
		<ul>
			<li>
				<b>cgi</b> - HTTP stuff
				<ul>
					<li>
						<b>modules</b> - Bulk of CGI. In MVC terms, sort of controllers and views in one.
						Simple view file framework started, but not used yet.
					</li>
					<li>
						<b>core</b> - Some shared cgi stuff.
					</li>
					<li>
						<b>widgets</b> - Self contained objects that can be used in multiple places.
						Can be php/js/css or just js/css or probably any subset
					</li>
				</ul>
			</li>
			<li>
				<b>cli</b> - Run things from command line. The mail file here is delly.php.
				A lot is obsolete.
				<ul>
					<li>
						<b>workers</b> - Does all the cli work. Not run directly, launched by delly.php.
					</li>
				</ul>
			</li>
			<li>
				<b>common</b> - Libraries and whatever else shared between cgi and cli
				<ul>
					<li>
						<b>tables</b> - In MVC terms, the models.
					</li>
				</ul>
			</li>
			<li>
				<b>local</b> - Non-versioned stuff
				<ul>
					<li>
						<b>database</b> - Was meant for database state files to help with syncing between dev/prod
					</li>
					<li>
						<b>misc</b> - Whatever you want!
					</li>
					<li>
						<b>reports</b> - Stuff that doesn't need to be shared should have a directory with a .gitignore file.
						Stuff that does need to be shared should have a line in the root .gitignore file so dev/prod/etc can handle independently, eg ppc_reports
					</li>
				</ul>
			</li>
			<li>
				<b>vcs</b> - Move to cli?
			</li>
			<li>
				<b>wprophp</b> - Legacy stuff. Most is not used anymore and never was.
				See below for things still in use. Should all be moved to common and wprophp deleted.
				<ul>
					<li>
						<b>apis</b> - Google and Bing pay per click interfaces.
						Also basic soap implementation which is not really used anymore.
					</li>
					<li>
						<b>excel</b> - Mostly used for PPC reports. Should be moved to common.
					</li>
				</ul>
			</li>
		</ul>

		<!-- databases -->
		<h2>Databases</h2>
		<ul>
			<li>
				<b>account_tasks</b> - Simple task management related to clients
			</li>
			<li>
				<b>contracts</b> - Client contract info
			</li>
			<li>
				<b>delly</b> - Scheduler/job information/management
			</li>
			<li>
				<b>eac</b> - New client account structure
			</li>
			<li>
				<b>eppctwo</b> - Oldest database. Old client structure. Lots of miscellaneous tables
			</li>
			<li>
				<b>log</b> - Logs
			</li>
			<li>
				<b>sales_leads</b> - Not sure this is really used anymore
			</li>
			<li>
				<b>social</b> - Social media data
			</li>
			<li>
				<b>surveys</b> - Client survey data
			</li>
			<li>
				<b>wikidb</b> - Our wiki media db
			</li>
			<li>
				<b>*_objects</b> - 3rd party data.
			</li>
		</ul>

		<!-- style -->
		<h2>Style</h2>
		<ul>
			<li>
				<b>Indenting</b> - k&amp;r style with the end/start of compound control structures each on their own line
			</li>
			<li>
				<b>Tabs</b> - hard
			</li>
			<li>
				<b>Functions vs control</b> - Never space between function name and parenthesis, always space between native keywords and parenthesis
			</li>
		</ul>
		<h3>Example</h3>
		<pre>
		private function example_function($x, $y)
		{
			switch ($x) {
				case ('a'):
					$z = 10;
					break;
				case ('b'):
					$z = 11;
					break;
			}
			for ($i = 0; $i < count($y); ++$i) {
				if ($z < 100) {
					$bam = $this->magic($x, $y);
				}
				else if ($z < 200) {
					$bam = $this->better_magic($x, $y);
				}
				else {
					$bam = 15;
				}
			}
			return $bam;
		}
		</pre>

		<?php
	}
}

?>