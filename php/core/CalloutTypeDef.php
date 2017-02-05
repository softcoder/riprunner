<?php
// ==============================================================
//	Copyright (C) 2017 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    ( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
        die( 'This file must not be invoked directly.' );
    }

require_once 'JsonSerializable.php';

class CalloutTypeDef implements JsonSerializable {

    private $id;
    private $code;
    private $name;
    private $desc;
    private $custom_tag;
    
    private $effective_date;
    private $expiration_date;
    private $update_datetime;
      
    public function __construct($id,$code,$name,$desc,$custom_tag,$effective_date,$expiration_date,$update_datetime) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->desc = $desc;
        $this->custom_tag = $custom_tag;
        $this->effective_date = $effective_date;
        $this->expiration_date = $expiration_date;
        $this->update_datetime = $update_datetime;
    }
    
    public function jsonSerialize() {
        return array(
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'desc' => $this->desc,
            'custom_tag' => $this->custom_tag,
            'effective_date' => $this->effective_date,
            'expiration_date' => $this->expiration_date,
            'update_datetime' => $this->update_datetime,
        );
    }
    
    public function getProperties() {
        return get_object_vars($this);
    }
    
    public function getId() {
        return $this->id;
    }
    public function getCode() {
        return $this->code;
    }
    public function getName() {
        return $this->name;
    }
    public function getDescription() {
        return $this->desc;
    }
    public function getCustomTag() {
        return $this->custom_tag;
    }
    public function getEffectiveDate() {
        return $this->effective_date;
    }
    public function getExpirationDate() {
        return $this->expiration_date;
    }
    public function getUpdateDateTime() {
        return $this->update_datetime;
    }
    
}
