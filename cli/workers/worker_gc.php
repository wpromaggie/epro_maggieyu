<?php

class worker_gc extends worker
{
	const ADMIN_EMAIL = 'chimdi@wpromote.com';

	const PPC_REPORT_LIFETIME = '16 months';

	private $do_simulate;

	public function run()
	{
		$this->do_simulate = (array_key_exists('s', cli::$args));
		$this->gc_ppc_reports();
	}
	
	public function gc_ppc_reports()
	{
		$cutoff = date(util::DATE_TIME, strtotime("now -".self::PPC_REPORT_LIFETIME));
		$report_path = \epro\REPORTS_PATH.'ppc_report/';
		foreach (glob("{$report_path}*") as $path) {
			$mtime = date(util::DATE_TIME, filemtime($path));
			if ($mtime < $cutoff) {
				if ($this->dbg || $this->do_simulate) {
					echo "garbage: $path ($mtime < $cutoff)\n";
				}
				if (!$this->do_simulate) {
					unlink($path);
				}
			}
		}
	}
}

?>