<?php
// ==============================================================
//  Copyright (C) 2014 Mark Vejvoda
//  Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );

require_once 'config_constants.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'logging.php';

sec_session_start();

global $log;
$db_connection = null;
if (isset($_SESSION['firehall_id']) === true) {
	$firehall_id = $_SESSION['firehall_id'];
	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	$db_connection = db_connect_firehall($FIREHALL);
}

if (login_check($db_connection) === true) {
	$file_path = get_query_param('file');
	if(isset($file_path) === true && empty($file_path) === false) {
		$path_parts = pathinfo($file_path);
		$file_name  = $path_parts['basename'];
		$file_path  = './' . $file_name;
		
		// allow a file to be streamed instead of sent as an attachment
		$is_attachment = ((isset($_REQUEST['stream']) === true) ? false : true);
		
		// make sure the file exists
		if (is_file($file_path) === true) {
			$file_size  = filesize($file_path);
			$file = @fopen($file_path, "rb");
			if ($file !== false) {
				
				//check if http_range is sent by browser (or download manager)
				if(isset($_SERVER['HTTP_RANGE']) === true) {
					list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
					if ($size_unit === 'bytes') {
						//multiple ranges could be specified at the same time, but for simplicity only serve the first range
						//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
						if(strpos($range_orig, ',') !== false) {
							$range = explode(',', $range_orig, 2);
						}
						else {
							$range = $range_orig;
						}
					}
					else {
						$range = '';
						header('HTTP/1.1 416 Requested Range Not Satisfiable');
						exit;
					}
				}
				else {
					$range = '';
				}
	
				// set the headers, prevent caching
				header("Pragma: public");
				header("Expires: -1");
				header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
								
				// set appropriate headers for attachment or streamed file
				if ($is_attachment === true) {
					header("Content-Disposition: attachment; filename=\"$file_name\"");
				}
				else {
					header('Content-Disposition: inline;');
				}
				
				// set the mime type based on extension, add yours if needed.
				// 			$ctype_default = "application/octet-stream";
				// 			$content_types = array(
				// 					"exe" => "application/octet-stream",
				// 					"zip" => "application/zip",
				// 					"mp3" => "audio/mpeg",
				// 					"mpg" => "video/mpeg",
				// 					"avi" => "video/x-msvideo",
				// 			);
				// 			$ctype = isset($content_types[$file_ext]) ? $content_types[$file_ext] : $ctype_default;
				
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$ctype = finfo_file($finfo, $file_path);
				finfo_close($finfo);
					
				header("Content-Type: " . $ctype);
					
				//figure out download piece from range (if set)
				if(isset($range) === true && strlen($range) > 0) {
					list($seek_start, $seek_end) = explode('-', $range, 2);
				}
				else {
					$seek_start = "";
					$seek_end = "";
				}
		
				//set start and end based on range (if set), else set defaults
				//also check for invalid ranges.
				$seek_end   = (empty($seek_end) === true) ? ($file_size - 1) : min(abs(intval($seek_end)), ($file_size - 1));
				$seek_start = (empty($seek_start) === true || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);
		
				//Only send partial content header if downloading a piece of the file (IE workaround)
				if ($seek_start > 0 || $seek_end < ($file_size - 1)) {
					header('HTTP/1.1 206 Partial Content');
					header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
					header('Content-Length: '.($seek_end - $seek_start + 1));
				}
				else {
					header("Content-Length: $file_size");
				}
		
				header('Accept-Ranges: bytes');
				
				// This line turns off the web server's gzip compression
				// which messes up our result.
				header("Content-Encoding: none");
		
				set_time_limit(0);
				if($seek_start > 0) {
					fseek($file, $seek_start);
				}
		
				ob_start();
				while(!feof($file)) {
					echo @fread($file, (1024*8));
					ob_flush();
					flush();
					if (connection_status() !== 0) {
						@fclose($file);
						exit;
					}
				}
		
				// file save was a success
				@fclose($file);
			}
			else {
				// file couldn't be opened
				$log->error("HTTP 500 detected for file get request for file [$file_path]");
				header("HTTP/1.0 500 Internal Server Error");
			}
		}
		else {
			// file does not exist
			$log->error("HTTP 404 detected for file get request for file [$file_path]");
			header("HTTP/1.0 404 Not Found");
		}	
	}
	else {
		$log->error("HTTP 400 detected for file get request for file [$file_path]");
		header("HTTP/1.0 400 Bad Request");
	}
}
else {
	$file_path = get_query_param('file');
	$log->error("Invalid session for file get request for file [$file_path]");
	
	echo "<body>" . PHP_EOL;
	echo "<p>" . PHP_EOL;
	echo "<span class='error'>You are not authorized to access this page.</span> Please <a href='login/'>login</a>." . PHP_EOL;
	echo "</p>" . PHP_EOL;
	echo "</body>" . PHP_EOL;
}
?>
