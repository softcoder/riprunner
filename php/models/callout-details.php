<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/core/CalloutType.php';

// The model class containing callout information
class CalloutDetails {
	
    private $firehall; 
	private $id;
	private $keyId;
	private $dateTime;
	private $code;
	private $code_type;
	private $address;
	private $GPSLat;
	private $GPSLong;
	private $unitsResponding;
	private $status;
	private $comments;
	private $callout_info_processed = false;
	private $supress_echo_text = false;
	
	public function __construct() {
	}

	public function isValid() {
		$validItemCount = 0;
		if(isset($this->dateTime) === true) {
			$validItemCount++;
		}
		if(isset($this->code) === true) {
			$validItemCount++;
		}
		if(isset($this->address) === true) {
			$validItemCount++;
		}
		if(isset($this->GPSLat) === true) {
			$validItemCount++;
		}
		if(isset($this->GPSLong) === true) {
			$validItemCount++;
		}
		if(isset($this->unitsResponding) === true) {
			$validItemCount++;
		}
		
		return ($validItemCount >= 3);
	}
	
	public function getFirehall() {
		return $this->firehall;
	}
	public function setFirehall($value) {
		$this->firehall = $value;
	}

	public function getId() {
		return $this->id;
	}
	public function setId($value) {
		$this->id = $value;
	}

	public function getKeyId() {
		return $this->keyId;
	}
	public function setKeyId($value) {
		$this->keyId = $value;
	}

	public function getDateTimeAsString() {
		if(isset($this->dateTime) === true) {
			if($this->dateTime instanceof \DateTime) {
				return $this->dateTime->format('Y-m-d H:i:s');
			}
			return $this->dateTime;
		}
		return '';
	}
	public function getDateTimeAsNative() {
		if(isset($this->dateTime) === true) {
			if($this->dateTime instanceof \DateTime) {
				return $this->dateTime;
			}
			return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateTime);
		}
		return null;
	}
	
	public function setDateTime($value) {
		$this->dateTime = $value;
	}

	public function getCode() {
		if(isset($this->code) === true) {
			return $this->code;
		}
		return '';
	}
	public function getCodeDescription() {
		if(isset($this->code) === true) {
			return $this->convertCallOutCodeToText($this->code);
		}
		return '';
	}
	public function setCode($value) {
		$this->code = $value;
	}

	public function getCodeType() {
	    if($this->code_type == null) {
	        $this->code_type = \riprunner\CalloutType::getTypeByCode($this->code, $this->getFirehall(), $this->getDateTimeAsNative());
	    }
	    return $this->code_type;
	}
	public function setCodeType($value) {
	    $this->code_type = $value;
	}
	
	
	public function getAddress() {
	    $this->processCalloutInfo();
		return $this->address;
	}
	public function getAddressForMap() {
	    $this->processCalloutInfo();
		if(isset($this->address) === true) {
			return getAddressForMapping($this->firehall, $this->address);
		}
		return '';
	}
	
	public function setAddress($value) {
		$this->address = $value;
	}

	public function getGPSLat() {
	    $this->processCalloutInfo();
		return $this->GPSLat;
	}
	public function setGPSLat($value) {
		$this->GPSLat = $value;
	}

	public function getGPSLong() {
	    $this->processCalloutInfo();
		return $this->GPSLong;
	}
	public function setGPSLong($value) {
		$this->GPSLong = $value;
	}
	
	public function getUnitsResponding() {
		return $this->unitsResponding;
	}
	public function setUnitsResponding($value) {
		$this->unitsResponding = $value;
	}

	public function getStatus() {
		return $this->status;
	}
	public function setStatus($value) {
		$this->status = $value;
	}

	public function getComments() {
	    $this->processCalloutInfo();
	    return $this->comments;
	}
	public function setComments($value) {
	    $this->comments= $value;
	}
	
	public function getSupressEchoText() {
	    return $this->supress_echo_text;
	}
	public function setSupressEchoText($value) {
	    $this->supress_echo_text= $value;
	}
	
	private function convertCallOutCodeToText($code) {
	    $calloutType = \riprunner\CalloutType::getTypeByCode($code, $this->getFirehall(), $this->getDateTimeAsNative());
		$codeText = 'UNKNOWN ['.$code.']';
		if (isset($calloutType) === true) {
			$codeText = $calloutType->getName();
		}
		return $codeText;
	}
	
	private function processCalloutInfo() {
	    if($this->callout_info_processed == false && $this->getFirehall() != null) {
	        $this->callout_info_processed = true;
	        
	        $caddress = $this->address;
	        $lat = $this->GPSLat;
	        $long = $this->GPSLong;
	        
	        if(($caddress != null && $caddress != '') || 
	           ($lat != null && $long != null && $lat != '' && $long != '')) {
    	        $db = new \riprunner\DbConnection($this->getFirehall());
    	        $db_connection = $db->getConnection();
    	        
    	        $sql_statement = new \riprunner\SqlStatement($db_connection);
    	        if($caddress != null && $caddress != '') {
    	            $sql = $sql_statement->getSqlStatement('callouts_info_select_by_address');
    	        }
    	        else {
    	            $sql = $sql_statement->getSqlStatement('callouts_info_select_by_geolocation');
    	        }
    	        
    	        $qry_bind = $db_connection->prepare($sql);
    	        if($caddress != null && $caddress != '') {
    	            $qry_bind->bindParam(':caddress', $caddress);
    	        }
    	        else {
        	        $qry_bind->bindParam(':lat', $lat);
        	        $qry_bind->bindParam(':long', $long);
    	        }
    	        $qry_bind->execute();
    	        
    	        $row = $qry_bind->fetch(\PDO::FETCH_OBJ);
    	        $qry_bind->closeCursor();
    	        \riprunner\DbConnection::disconnect_db( $db_connection );
    	        
    	        if($row !== null && $row !== false) {
    	            if($row->latitude != null && $row->latitude != 0 && $row->latitude != '') {
    	                $this->GPSLat = $row->latitude;
    	            }
    	            if($row->longitude != null && $row->longitude != 0 && $row->longitude != '') {
    	                $this->GPSLong = $row->longitude;
    	            }
    	            if($row->address != null && $row->address != '') {
    	                $this->address= $row->address;
    	            }
    	            if($this->comments == null && $row->comments != null && $row->comments!= '') {
    	                $this->comments = $row->comments;
    	            }
    	        }
	        }	        
	    }
	}
}
