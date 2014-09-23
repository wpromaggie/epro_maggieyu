<?php

class worker_backup extends worker
{
	const ADMIN_EMAIL = 'chimdi@wpromote.com';
	
	// sed exp to get rid of mysqldump timestamp so we can compare files
	const SED_REMOVE_MYSQLDUMP_TIMESTAMP = 'sed -r \'s/^-- Dump completed on .*$//\'';
	
	public static $db_backup_path = 'bak/mysql/';
	
	public static $dbs_for_backup = array('eppctwo', 'contracts', 'sales_leads', 'wikidb', 'surveys', 'account_tasks', 'eac', 'social', 'delly');
	
	public function run()
	{
		$this->backup_databases();
		$this->backup_nas();
	}
	
	public function backup_databases()
	{
		$src_base_path = \epro\CLI_PATH.self::$db_backup_path;
		foreach (self::$dbs_for_backup as $i => $db) {
			// check db exists, fail silently
			if (!db::use_db($db)) {
				continue;
			}
			
			chdir($src_base_path);
			if (!is_dir($db)) {
				mkdir($db);
			}
			chdir($db);

			$this->pdbg("\n---\n$db ($i / ".count(self::$dbs_for_backup).")\n---\n");
			
			$tables = db::select("show tables");
			foreach ($tables as $table) {
				$dst = $db.'.'.$table.'.mysql';
				$dst_prev = $dst.'.1';
				
				$is_new = (!file_exists($dst));
				
				// move the file currently on disk to prev
				if (!$is_new) {
					rename($dst, $dst_prev);
				}
				
				// dump table
				cli::exec_verbose('mysqldump -u'.\epro\DB_USER.' -p'.\epro\DB_PASS.' '.$db.' '.$table.' | '.self::SED_REMOVE_MYSQLDUMP_TIMESTAMP.' > '.$dst);
				
				// drive files are gzipped
				$dst_gz = $dst.'.gz';

				if ($is_new || $this->are_files_different($dst, $dst_prev)) {
					$this->pdbg("compress\n");
					$this->gzip($dst, $dst_gz);
				}
			}
		}	
	}


	public function backup_nas()
	{
		$this->rsync('/media/nas0', '/mnt/backups/enet/');
	}


	private function pdbg($msg)
	{
		if ($this->dbg) {
			echo $msg;
		}
	}

	private function rsync($src_path, $dst_path, $include_exclude = '')
	{
		$cmd_start = 'rsync --recursive --times --links --compress --rsh="ssh -i '.\epro\BACKUP_KEYFILE_PATH.'"'.$include_exclude;
//		$cmd_end = "{$src_path} cynta.wpromote.com:{$dst_path}";
		$cmd_end = "{$src_path} ubuntu@bkup411.wpromote.com:{$dst_path}";

		if (util::is_dev() || array_key_exists('d', cli::$args)) {
			$cmd_start .= ' --dry-run';
		}
		if ($this->dbg) {
			$cmd = "{$cmd_start} --progress --stats --verbose {$cmd_end}";
			echo "$cmd\n";
			passthru($cmd);
		}
		else {
			exec("{$cmd_start} {$cmd_end}");
		}
	}
	
	private function gzip($src, $dst)
	{
		exec('gzip -c '.$src.' > '.$dst);
	}
	
	private function are_files_different($f1, $f2)
	{
		exec('diff -q '.$f1.' '.$f2.' 2>/dev/null', $output, $r);
		return ($r !== 0);
	}
	
	// get backup db file
	public function get()
	{
		$f = cli::$args['f'];
		if ($f) {
			list($db, $table) = explode('.', $f);
			$tables = array($table);
		}
		else {
			list($db, $table) = util::list_assoc(cli::$args, 'd', 't');
			if (!$db || !$table) {
				cli::usage('[-f file|-d db -t table]');
			}
			$tables = explode(',', $table);
		}

		foreach ($tables as $table) {
			$f = "{$db}.{$table}.mysql.gz";
			
			// could use any of the production hosts
			$tmp_dir = sys_get_temp_dir();
			$dst = $tmp_dir.'/'.$f;
			$uncompressed = "{$tmp_dir}/{$db}.{$table}.mysql";
			// try to use local file
			if (array_key_exists('l', cli::$args) && file_exists($uncompressed)) {
				echo "using local file: $uncompressed\n";
			}
			else {
				$src = '/media/nas0/bak/mysql/'.$db.'/'.$f;
				cli::exec_verbose('scp megaman:'.$src.' '.$dst);
				if (!file_exists($dst)) {
					cli::error("error getting file $f");
				}

				cli::exec_verbose('gunzip -f '.$dst);
			}
			
			// also import file
			if (array_key_exists('i', cli::$args)) {
				$contents = file_get_contents($uncompressed);
				if (strpos($contents, 'ENGINE=MyISAM') !== false) {
					$contents = str_replace('ENGINE=MyISAM', 'ENGINE=InnoDB', $contents);
					file_put_contents($uncompressed, $contents);
				}
				echo "importing table..\n";
				exec('mysql -u'.\epro\DB_USER.' -p'.\epro\DB_PASS.' '.$db.' < '.$uncompressed);
				
				// delete by default, capital U to keep file
				if (!array_key_exists('U', cli::$args)) {
					echo "removing file..\n";
					unlink($uncompressed);
				}
			}
			echo "\n";
		}
	}
}

?>