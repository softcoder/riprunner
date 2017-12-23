<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';

// The model class handling variable requests dynamically
class MainMenuViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "mainmenu_vm";
	}
	
	public function __get($name) {
		if('hasApplicationUpdates' === $name) {
			return $this->checkApplicationUpdatesAvailable();
		}
		if('LOCAL_VERSION' === $name) {
			return CURRENT_VERSION;
		}
		if('REMOTE_VERSION' === $name) {
			$ini = $this->getApplicationUpdateSettings();
			return file_get_contents($ini['local_path']);
		}
		if('REMOTE_VERSION_NOTES' === $name) {
			$ini = $this->getApplicationUpdateSettings();
			return $ini["distant_path_notes"];
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('hasApplicationUpdates','LOCAL_VERSION',
				  'REMOTE_VERSION','REMOTE_VERSION_NOTES')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getApplicationUpdateSettings() {
	    # Configuration array
	    //$ini = array('local_path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.version',
	    $ini = array('local_path' => '../.check_version',
    	             'distant_path' => 'https://raw.githubusercontent.com/softcoder/riprunner/master/files/latest_version',
    	             'distant_path_notes' => 'https://github.com/softcoder/riprunner',
    	             'time_between_check' => 15*24*60*60);
	    return $ini;
	}
	
	/**
	 * Notes:
	 * It stores a local file named .version where the latest version available is stored (Plain text like "1.2.0")
	 * Each time the local file is older than 15 day, it goes to code.google.com to check for the latest version (Again, the file only contains version number "1.2.0")
	 * It wrote the latest version available in the local file
	 * If the file is younger than 15 day, it open the local file, and compare the version number stored in it with the application current version.
	 * So with this small technique, only one HTTP request is done every 15 day, the rest of the time it's only a local file, being the least intrusive in the user system.
	 *
	 * Check for the latest version, from local cache or via http
	 * Return true if a newer version is available, false otherwise
	 *
	 * @return boolean
	 */
	private function checkApplicationUpdatesAvailable() {
	    # Configuration array
	    $ini = $this->getApplicationUpdateSettings();
	
	    # Checking if file was modified for less than $ini['time_between_check'] ago
	    $stats = @stat($ini['local_path']);
	    if(is_array($stats) === true && isset($stats['mtime']) === true &&
	            ($stats['mtime'] > (time() - $ini['time_between_check']))) {
	                # Opening file and checking for latest version
	                return (version_compare(CURRENT_VERSION, file_get_contents($ini['local_path'])) == -1);
	            }
	            else {
	                # Getting last version from Google Code
	                $latest = @file_get_contents($ini['distant_path']);
	                if($latest !== null) {
	                    # Saving latest version in file
	                    file_put_contents($ini['local_path'], $latest);
	
	                    # Checking for latest version
	                    return (version_compare(CURRENT_VERSION, $latest) == -1);
	                }
	                # Can't connect to Github
	                //else {
	                # In case user does not have access to githubusercontent.com !!!
	                    # Here it's up to you, you can write nothing in the file to display an alert
	                    # leave it to check google every time this function is called
	                # or write again the file to advance it's modification date for the next HTTP call.
	                //}
	            }
	}
	
	private function checkApplicationUpdates() {
	    if($this->checkApplicationUpdatesAvailable() === true) {
	        $ini = $this->getApplicationUpdateSettings();
	
	        $updates_html = "<br />" . PHP_EOL;
	        $updates_html .= "<span class='notice'>Current Version [". CURRENT_VERSION ."]" .
	                " New Version [". file_get_contents($ini['local_path']) . "]</span>" . PHP_EOL;
	        $updates_html .= "<br />" . PHP_EOL;
	        $updates_html .= "<a target='_blank' href='" . $ini["distant_path_notes"] .
	        "' class='notice'>Click here for update information</a>" . PHP_EOL;
	        echo $updates_html;
	    }
	}
	
}
