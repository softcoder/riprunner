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
	$callOutDateTimeString = $callout->getDateTimeAsString();
	
	// See if this is a duplicate callout?
	$sql = "SELECT id,call_key,status " .
			"FROM callouts WHERE calltime = '" . 
			$db_connection->real_escape_string( $callOutDateTimeString ) . 
			"'" .
			" AND calltype = '" . 
			$db_connection->real_escape_string( $callout->getCode() ) . 
			"'" .
			" AND (address = '" . 
			$db_connection->real_escape_string( $callout->getAddress() ) . 
			"'" .
			" OR (latitude = " . 
			$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat())) ) . 
	        "     AND longitude = " . 
	        $db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong())) ). 
	        "));";
	
	$sql_result = $db_connection->query( $sql );
	if($sql_result == false) {
		printf("Error: %s\n", mysqli_error($db_connection));
		throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
	}
	
	if($row = $sql_result->fetch_object()) {
		$callout->setId($row->id);
		$callout->setKeyId($row->call_key);
		$callout->setStatus($row->status);
	}
	$sql_result->close();
	
	// Found duplicate callout so update some fields on original callout
	if($callout->getId() != null) {
		// Insert the new callout
		$sql = 'UPDATE callouts' .
				' SET address = \'' . 
				$db_connection->real_escape_string( $callout->getAddress() )           . 
				'\', ' .
				' latitude = ' . 
				$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat())) )  . 
				', ' .
				' longitude = ' . 
				$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong())) ) . 
				', ' .
				' units = \'' . 
				$db_connection->real_escape_string( $callout->getUnitsResponding() ) . 
				'\'' .
				' WHERE id = ' . $callout->getId() . 
				' AND (address <> \'' . 
				$db_connection->real_escape_string( $callout->getAddress() )           . 
				'\'' . 
				' OR latitude <> ' . 
				$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat())) )  .
				' OR longitude <> ' . 
				$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong())) ) .
				' OR units <> \'' . 
				$db_connection->real_escape_string( $callout->getUnitsResponding() ) . 
				'\');';
		
		$sql_result = $db_connection->query( $sql );
		
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}
		
		$affected_rows = $db_connection->affected_rows;
		
		if($affected_rows > 0) {
			$update_callout_prefix_msg = "*UPDATED* ";
			
			signalCalloutToSMSPlugin($callout, $update_callout_prefix_msg);
			
			$gcmMsg = getGCMCalloutMessage($callout);
			
			signalCallOutRecipientsUsingGCM($callout,null,
					$update_callout_prefix_msg . $gcmMsg, $db_connection);
		}
	}
	else {
		// Insert the new callout
		$callout->setKeyId(uniqid('', true));
		
		$sql = 'INSERT INTO callouts (calltime,calltype,address,latitude,longitude,units,call_key) ' .
				' values(' .
				'\'' . $db_connection->real_escape_string( $callout->getDateTimeAsString() ) . '\', ' .
				'\'' . $db_connection->real_escape_string( $callout->getCode() )              . '\', ' .
				'\'' . $db_connection->real_escape_string( $callout->getAddress() )           . '\', ' .
				$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat())) )  . ', ' .
				$db_connection->real_escape_string( floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong())) ) . ', ' .
				'\'' . $db_connection->real_escape_string( $callout->getUnitsResponding() ) . '\', ' .   
				'\'' . $db_connection->real_escape_string($callout->getKeyId()) . '\');';
		
		$sql_result = $db_connection->query( $sql );
		
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}
		
		$callout->setId($db_connection->insert_id);
		$callout->setStatus(CalloutStatusType::Paged);
		
		signalCalloutToSMSPlugin($callout,null);
	
		$gcmMsg = getGCMCalloutMessage($callout);
		
		signalCallOutRecipientsUsingGCM($callout,null,$gcmMsg,$db_connection);

		// Only update status if not cancelled or completed already
		$sql_update = 'UPDATE callouts SET status=' . 
					  CalloutStatusType::Notified . 
					  ' WHERE id = ' .
					  $db_connection->real_escape_string( $callout->getId() ) . 
					  ' AND status NOT in(3,10);';
			
		$sql_result = $db_connection->query( $sql_update );
		
		if($sql_result == false) {
			printf("SQL #2 Error: %s\n", mysqli_error($db_connection));
			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql_update . "]");
		}
	}
	
	if($db_connection != null) {
		db_disconnect( $db_connection );
	}
}

?>