<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

// The model class containing callout information
class CalloutDetails {
	
	private $firehall; 
	private $id;
	private $keyId;
	private $dateTime;
	private $code;
	private $address;
	private $GPSLat;
	private $GPSLong;
	private $unitsResponding;
	private $status;
	
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
			if($this->dateTime instanceof DateTime) {
				return $this->dateTime->format('Y-m-d H:i:s');
			}
			return $this->dateTime;
		}
		return '';
	}
	public function getDateTimeAsNative() {
		if(isset($this->dateTime) === true) {
			if($this->dateTime instanceof DateTime) {
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

	public function getAddress() {
		return $this->address;
	}
	public function getAddressForMap() {
		if(isset($this->address) === true) {
			return getAddressForMapping($this->firehall, $this->address);
		}
		return '';
	}
	
	public function setAddress($value) {
		$this->address = $value;
	}

	public function getGPSLat() {
		return $this->GPSLat;
	}
	public function setGPSLat($value) {
		$this->GPSLat = $value;
	}

	public function getGPSLong() {
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
	
	private function convertCallOutCodeToText($code) {
		global $CALLOUT_CODES_LOOKUP;
		$codeText = 'UNKNOWN ['.$code.']';
		if (isset($CALLOUT_CODES_LOOKUP) && array_key_exists($code, $CALLOUT_CODES_LOOKUP) === true) {
			$codeText = $CALLOUT_CODES_LOOKUP[$code];
		}
		return $codeText;
	}
}
?>
