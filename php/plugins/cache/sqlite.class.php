<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
/* Portions of code used from:
 * 
*  khoaofgod@gmail.com
*  Website: http://www.phpfastcache.com
*  Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
*/
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/plugins/cache/plugin_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SqliteCachePlugin implements ICachePlugin {

	private $indexing = null;
	private $path = null;
	private $max_size = 50; // 50 mb
	private $instant = array();
	private $currentDB = 1;
	
	/*
	 Constructor
	 */
	public function __construct() {
		global $log;
		try {
		    if($log !== null) $log->trace("Cache plugin check installed sqlite.");
		    
			if($this->isInstalled() === true) {
			    if($log !== null) $log->trace("Cache plugin init about to connect to sqlite.");
			    
				if(file_exists($this->getCachePath()."/sqlite") === false) {
				    if($log !== null) $log->warn("Cache plugin init about to mkdir for sqlite.");
				    
					if(@mkdir($this->getCachePath()."/sqlite", 0777) === false) {
						throw new \Exception("Sqlite cache cannot create temp folder: " . $this->getCachePath()."/sqlite");
					}
				}
				$this->path = $this->getCachePath() . "/sqlite";
								
				if($log !== null) $log->trace("Cache plugin init SUCCESS using sqlite on this host!");
			}
			else {
			    if($log !== null) $log->trace("Cache plugin init FAILED cannot use sqlite on this host!");
			}
		}
		catch(\Exception $ex) {
		    $this->path = null;
		    if($log !== null) $log->error("Cache proxy init error [" . $ex->getMessage() . "]");
		}
	}
	
	private function getCachePath() {
		return __RIPRUNNER_ROOT__ . "/temp/cache/";
	}
	
	public function getPluginType() {
		return 'SQLITECACHE';
	}
	
	public function isInstalled() {
		// php-sqlite3
		return (extension_loaded('pdo_sqlite') && $this->path != null);
	}
	
	public function getItem($keyword) {
		// return null if no caching
		// return value if in caching
		try {
			$stm = $this->db($keyword)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
			$stm->execute(array(
					":keyword"  =>  $keyword
			));
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
			
		} 
		catch(\PDOException $e) {
			$stm = $this->db($keyword, true)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
			$stm->execute(array(
					":keyword"  =>  $keyword
			));
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
		}
		if($this->isExpired($row) === true) {
			$this->deleteRow($row);
			return null;
		}
		if(isset($row['id']) === true) {
			$data = $this->decode($row['object']);
			return $data;
		}
		return null;
	}

	public function setItem($keyword, $value, $cache_seconds=null) {

		if(isset($cache_seconds) === false) {
			$cache_seconds = (60 * 10);
		}
		
		try {
			echo "SQLITE set #1 " .PHP_EOL;
			$stm = $this->db($keyword)->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
			$stm->execute(array(
					":keyword"  => $keyword,
					":object"   =>  $this->encode($value),
					":exp"      => @date("U") + (Int)$cache_seconds,
			));
			
			echo "SQLITE set #2 " .PHP_EOL;
		} 
		catch(\PDOException $e) {
			echo "SQLITE set #3 " .PHP_EOL;
			
			$stm = $this->db($keyword, true)->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
			$stm->execute(array(
					":keyword"  => $keyword,
					":object"   =>  $this->encode($value),
					":exp"      => @date("U") + (Int)$cache_seconds,
			));
		}
	}

	public function deleteItem($keyword) {
		$stm = $this->db($keyword)->prepare("DELETE FROM `caching` WHERE (`keyword`=:keyword) OR (`exp` <= :U)");
		$stm->execute(array(
				":keyword"   => $keyword,
				":U"    =>  @date("U"),
		));		
	}
	public function hasItem($keyword) {
		// return null if no caching
		// return value if in caching
		try {
			$stm = $this->db($keyword)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
			$stm->execute(array(
					":keyword"  =>  $keyword
			));
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
			
		} 
		catch(\PDOException $e) {
			$stm = $this->db($keyword, true)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
			$stm->execute(array(
					":keyword"  =>  $keyword
			));
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
		}
		if($this->isExpired($row) === true) {
			$this->deleteRow($row);
			return false;
		}
		if(isset($row['id']) === true) {
			return true;
		}
		return false;
	}
	
	public function clear() {
		global $log;
		try {
			if($this->isInstalled() === true) {
		
				if(file_exists($this->getCachePath()."/sqlite") === true) {
					$log->trace("Cache plugin re-init using sqlite on this host deleting existing cached data");
					deleteDir($this->getCachePath()."/sqlite");
				}
				if(file_exists($this->getCachePath()."/sqlite") === false) {
					if(@mkdir($this->getCachePath()."/sqlite", 0777) === false) {
						throw new \Exception("Sqlite cache re-init cannot create temp folder: " . $this->getCachePath()."/sqlite");
					}
				}
				
				$this->path = $this->getCachePath() . "/sqlite";
		
				$log->trace("Cache plugin re-init SUCCESS using sqlite on this host!");
			}
			else {
				$log->trace("Cache plugin re-init FAILED cannot use sqlite on this host!");
			}
		}
		catch(Exception $ex) {
			$log->error("Cache proxy re-init error [" . $ex->getMessage() . "]");
		}
	}
	
	public function getStats() {
		$stats = '';
		return $stats;
	}	
	public static function deleteDir($dirPath) {
		if (! is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (substr($dirPath, (strlen($dirPath) - 1), 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file) === true) {
				self::deleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}
	
	private function db($keyword, $reset=false) {
		/*
		 * Default is fastcache
		 */
		$instant = $this->indexing($keyword);
		/*
		 * init instant
		*/
		if(isset($this->instant[$instant]) === false) {
			// check DB Files ready or not
			$createTable = false;
			if(file_exists($this->path."/db".$instant) === false || $reset === true) {
				$createTable = true;
			}
			$PDO = new \PDO("sqlite:".$this->path."/db".$instant);
			$PDO->setAttribute(\PDO::ATTR_ERRMODE,
					\PDO::ERRMODE_EXCEPTION);
			if($createTable === true) {
				$this->initDB($PDO);
			}
			$this->instant[$instant] = $PDO;
			unset($PDO);
		}
		return $this->instant[$instant];
	}	
	
	/*
	 * INIT Instant DB
	 * Return Database of Keyword
	 */
	private function indexing() {
		if($this->indexing === null) {
			$createTable = false;
			if(file_exists($this->path."/indexing") === false) {
				$createTable = true;
			}
			$PDO = new \PDO("sqlite:".$this->path."/indexing");
			$PDO->setAttribute(\PDO::ATTR_ERRMODE,
					\PDO::ERRMODE_EXCEPTION);
			if($createTable === true) {
				$this->initIndexing($PDO);
			}
			$this->indexing = $PDO;
			unset($PDO);
			$stm = $this->indexing->prepare("SELECT MAX(`db`) as `db` FROM `balancing`");
			$stm->execute();
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
			if(isset($row['db']) === false) {
				$db = 1;
			} else if($row['db'] <= 1) {
				$db = 1;
			} else {
				$db = $row['db'];
			}
			// check file size
			$size = ((file_exists($this->path."/db".$db) === true) ? filesize($this->path."/db".$db) : 1);
			$size = round(($size / 1024 / 1024), 1);
			if($size > $this->max_size) {
				$db++;
			}
			$this->currentDB = $db;
		}	
	}
	
	/*
	 * INIT NEW DB
	 */
	private function initDB(\PDO $db) {
		$db->exec('drop table if exists "caching"');
		$db->exec('CREATE TABLE "caching" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "keyword" VARCHAR UNIQUE, "object" BLOB, "exp" INTEGER)');
		$db->exec('CREATE UNIQUE INDEX "cleaup" ON "caching" ("keyword","exp")');
		$db->exec('CREATE INDEX "exp" ON "caching" ("exp")');
		$db->exec('CREATE UNIQUE INDEX "keyword" ON "caching" ("keyword")');
	}
	/*
	 * INIT Indexing DB
	 */
	private function initIndexing(\PDO $db) {
		// delete everything before reset indexing
		$dir = opendir($this->path);
		while($file = readdir($dir)) {
			if($file !== "." && $file !== ".." && $file !== "indexing" && $file !== "dbfastcache") {
				@unlink($this->path."/".$file);
			}
		}
		$db->exec('drop table if exists "balancing"');
		$db->exec('CREATE TABLE "balancing" ("keyword" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "db" INTEGER)');
		$db->exec('CREATE INDEX "db" ON "balancing" ("db")');
		$db->exec('CREATE UNIQUE INDEX "lookup" ON "balacing" ("keyword")');
	}	
	
	private function isExpired($row) {
		if(isset($row['exp']) === true && @date("U") >= $row['exp']) {
			return true;
		}
		return false;
	}
	private function deleteRow($row) {
		$stm = $this->db($row['keyword'])->prepare("DELETE FROM `caching` WHERE (`id`=:id) OR (`exp` <= :U) ");
		$stm->execute(array(
				":id"   => $row['id'],
				":U"    =>  @date("U"),
		));
	}	
	
	/*
	* Object for Files & SQLite
	*/
	private function encode($data) {
		return serialize($data);
	}
	private function decode($value) {
		$x = @unserialize($value);
		if($x === false) {
			return $value;
		} 
		else {
			return $x;
		}
	}	
}
