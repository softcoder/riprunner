<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once 'config.php';
require_once 'models/callout-details.php';
require_once 'functions.php';
require_once 'firehall_signal_sms.php';
require_once 'firehall_signal_gcm.php';

function signalFireHallCallout($callout) {

	// Connect to the database
	$db_connection = db_connect_firehall($callout->getFirehall());
	
	// update database info about this callout
	$callout_dt_str = $callout->getDateTimeAsString();
	
	// See if this is a duplicate callout?
	$sql = "SELECT id,call_key,status " .
			"FROM callouts WHERE calltime = :ctime AND calltype = :ctype AND " .
			" (address = :caddress OR (latitude = :lat AND longitude = :long));";
	
// 	$sql_result = $db_connection->query( $sql );
// 	if($sql_result == false) {
// 		printf("Error: %s\n", mysqli_error($db_connection));
// 		throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
// 	}

	$ctype = $callout->getCode();
	$caddress = $callout->getAddress();
	$lat = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat()));
	$long = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong()));
	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->bindParam(':ctime',$callout_dt_str);
	$qry_bind->bindParam(':ctype',$ctype);
	$qry_bind->bindParam(':caddress',$caddress);
	$qry_bind->bindParam(':lat',$lat);
	$qry_bind->bindParam(':long',$long);
	$qry_bind->execute();
	
	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
	$qry_bind->closeCursor();
	
	
	//if($row = $sql_result->fetch_object()) {
	if(!empty($rows)) {
		$row = $rows[0];
		$callout->setId($row->id);
		$callout->setKeyId($row->call_key);
		$callout->setStatus($row->status);
	}
	//$sql_result->close();
	
	// Found duplicate callout so update some fields on original callout
	if($callout->getId() != null) {
		// Insert the new callout
		$sql = 'UPDATE callouts' .
				' SET address = :caddress, latitude = :lat, longitude = :long, ' .
				' units = :units' .
				' WHERE id = :id AND (address <> :caddress OR latitude <> :lat OR longitude <> :long OR units <> :units);';
		
// 		$sql_result = $db_connection->query( $sql );
// 		if($sql_result == false) {
// 			printf("Error: %s\n", mysqli_error($db_connection));
// 			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
// 		}

		$caddress = $callout->getAddress();
		$lat = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat()));
		$long = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong()));
		$units = $callout->getUnitsResponding();
		$cid = $callout->getId();
		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':caddress',$caddress);
		$qry_bind->bindParam(':lat',$lat);
		$qry_bind->bindParam(':long',$long);
		$qry_bind->bindParam(':units',$units);
		$qry_bind->bindParam(':id',$cid);
		$qry_bind->execute();
		
		$affected_rows = $qry_bind->rowCount();
		
		if($affected_rows > 0) {
			$update_prefix_msg = "*UPDATED* ";
			
			signalCalloutToSMSPlugin($callout, $update_prefix_msg);
			
			$gcmMsg = getGCMCalloutMessage($callout);
			
			signalCallOutRecipientsUsingGCM($callout,null,
					$update_prefix_msg . $gcmMsg, $db_connection);
		}
	}
	else {
		// Insert the new callout
		$callout->setKeyId(uniqid('', true));
		
		$sql = 'INSERT INTO callouts (calltime,calltype,address,latitude,longitude,units,call_key) ' .
				' values(:cdatetime, :ctype, :caddress, :lat, :long, :units, :ckid);';
		
// 		$sql_result = $db_connection->query( $sql );
// 		if($sql_result == false) {
// 			printf("Error: %s\n", mysqli_error($db_connection));
// 			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
// 		}

		$cdatetime = $callout->getDateTimeAsString();
		$ctype =  (isset($callout->getCode()) && $callout->getCode() != null ? $callout->getCode() : "");
		$caddress = (isset($callout->getAddress()) && $callout->getAddress() != null ? $callout->getAddress() : "");
		$lat = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat()));
		$long = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong()));
		$units = (isset($callout->getUnitsResponding()) && $callout->getUnitsResponding() != null ? $callout->getUnitsResponding() : "");
		$ckid = $callout->getKeyId();
		
		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':cdatetime',$cdatetime);
		$qry_bind->bindParam(':ctype',$ctype);
		$qry_bind->bindParam(':caddress',$caddress);
		$qry_bind->bindParam(':lat',$lat);
		$qry_bind->bindParam(':long',$long);
		$qry_bind->bindParam(':units',$units);
		$qry_bind->bindParam(':ckid',$ckid);
		$qry_bind->execute();
		
		$callout->setId($db_connection->lastInsertId());
		$callout->setStatus(CalloutStatusType::Paged);
		
		signalCalloutToSMSPlugin($callout,null);
	
		$gcmMsg = getGCMCalloutMessage($callout);
		
		signalCallOutRecipientsUsingGCM($callout,null,$gcmMsg,$db_connection);

		// Only update status if not cancelled or completed already
		$sql_update = 'UPDATE callouts SET status = :status WHERE id = :id AND status NOT in(3,10);';
			
// 		$sql_result = $db_connection->query( $sql_update );
// 		if($sql_result == false) {
// 			printf("SQL #2 Error: %s\n", mysqli_error($db_connection));
// 			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql_update . "]");
// 		}
		$cid = $callout->getId();
		$status_notified = CalloutStatusType::Notified;
		$qry_bind = $db_connection->prepare($sql_update);
		$qry_bind->bindParam(':status',$status_notified);
		$qry_bind->bindParam(':id',$cid);
		$qry_bind->execute();
	}
	
	if($db_connection != null) {
		db_disconnect( $db_connection );
	}
}

?>