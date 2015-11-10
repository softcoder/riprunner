<?php 
// ==============================================================
//    Copyright (C) 2014 Mark Vejvoda
//    Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';

// The model class handling variable requests dynamically
class CalloutDetailsViewModel extends BaseViewModel {
    
    private $callout_details_list;
    private $callout_details_responding_list;
    private $callout_details_not_responding_list;
    private $callout_details_end_responding_list;
    
    protected function getVarContainerName() { 
        return "callout_details_vm";
    }
    
    public function __get($name) {
        if('firehall_id' === $name) {
            return $this->getFirehallId();
        }
        if('firehall' === $name) {
            return $this->getFirehall();
        }
        if('callout_id' === $name) {
            return $this->getCalloutId();
        }
        if('calloutkey_id' === $name) {
            return $this->getCalloutKeyId();
        }
        if('member_id' === $name) {
            return $this->getMemberId();
        }
        if('callout_responding_user_id' === $name) {
            return $this->getCalloutRespondingId();
        }
        if('callout_status_complete' === $name) {
            return \CalloutStatusType::Complete;
        }
        if('callout_status_cancel' === $name) {
            return \CalloutStatusType::Cancelled;
        }
        if('callout_details_list' === $name) {
            return $this->getCalloutDetailsList();
        }
        if('callout_details_responding_list' === $name) {
            return $this->getCalloutDetailsRespondingList();
        }
        if('callout_details_not_responding_list' === $name) {
            return $this->getCalloutDetailsNotRespondingList();
        }
        if('callout_details_end_responding_list' === $name) {
            return $this->getCalloutDetailsEndRespondingList();
        }
        if('google_map_type' === $name) {
            return GOOGLE_MAP_TYPE;
        }
        if('MAP_AUTO_REFRESH_TIMER' === $name) {
            return MAP_AUTO_REFRESH_TIMER;
        }
        if('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED' === $name) {
            return ALLOW_CALLOUT_UPDATES_AFTER_FINISHED;
        }
        if('ALLOW_OFFICER_CALLOUT_UPDATES_AFTER_FINISHED' === $name) {
            return ALLOW_OFFICER_CALLOUT_UPDATES_AFTER_FINISHED;
        }
        if('STREAM_AUDIO_ENABLED' === $name) {
            return STREAM_AUDIO_ENABLED;
        }
        if('STREAM_MOBILE' === $name) {
            return STREAM_MOBILE;
        }
        if('STREAM_DESKTOP' === $name) {
            return STREAM_DESKTOP;
        }
        if('STREAM_URL' === $name) {
            return STREAM_URL;
        }
        if('STREAM_TYPE' === $name) {
            return STREAM_TYPE;
        }
        if('STREAM_AUTOPLAY_MOBILE' === $name) {
            return STREAM_AUTOPLAY_MOBILE;
        }
        if('STREAM_AUTOPLAY_DESKTOP' === $name) {
            return STREAM_AUTOPLAY_DESKTOP;
        }
        if('MOBILE_LARGE_ZOOM_BUTTONS' === $name) {
            return MOBILE_LARGE_ZOOM_BUTTONS;
        }
        if('DESKTOP_LARGE_ZOOM_BUTTONS' === $name) {
            return DESKTOP_LARGE_ZOOM_BUTTONS;
        }
        if('map_callout_geo_dest' === $name) {
            return get_query_param('map_callout_geo_dest');
        }
        if('map_callout_address_dest' === $name) {
            return get_query_param('map_callout_address_dest');
        }
        if('map_fh_geo_lat' === $name) {
            return get_query_param('map_fh_geo_lat');
        }
        if('map_fh_geo_long' === $name) {
            return get_query_param('map_fh_geo_long');
        }
        if('map_webroot' === $name) {
            return get_query_param('map_webroot');
        }
        
        return parent::__get($name);
    }

    public function __isset($name) {
        if(in_array($name,
            array('firehall_id','firehall','callout_id','calloutkey_id', 'member_id',
                'callout_responding_user_id', 'callout_status_complete', 'callout_status_cancel',
                'callout_details_list','callout_details_responding_list',
                'callout_details_not_responding_list','callout_details_end_responding_list','google_map_type',
                'MAP_AUTO_REFRESH_TIMER','ALLOW_CALLOUT_UPDATES_AFTER_FINISHED','ALLOW_OFFICER_CALLOUT_UPDATES_AFTER_FINISHED',
                'STREAM_AUDIO_ENABLED','STREAM_MOBILE','STREAM_DESKTOP','STREAM_URL','STREAM_TYPE','STREAM_AUTOPLAY_MOBILE','STREAM_AUTOPLAY_DESKTOP',
                'MOBILE_LARGE_ZOOM_BUTTONS','DESKTOP_LARGE_ZOOM_BUTTONS',
                'map_callout_geo_dest','map_callout_address_dest','map_fh_geo_lat','map_fh_geo_long','map_webroot'
            )) === true) {
            return true;
        }
        return parent::__isset($name);
    }
    
    private function getFirehallId() {
        $firehall_id = get_query_param('fhid');
        return $firehall_id;
    }
    
    private function getFirehall() {
        $firehall = null;
        if($this->getFirehallId() !== null) {
            $firehall = findFireHallConfigById($this->getFirehallId(), 
                                            $this->getGvm()->firehall_list);
        }
        return $firehall;
    }

    private function getCalloutId() {
        $callout_id = get_query_param('cid');
        if ( isset($callout_id) === true && $callout_id !== null ) {
            $callout_id = (int)$callout_id;
        }
        else {
            $callout_id = -1;
        }
        return $callout_id;
    }
    
    private function getCalloutKeyId() {
        $callkey_id = get_query_param('ckid');
        return $callkey_id;
    }

    private function getMemberId() {
        $member_id = get_query_param('member_id');
        return $member_id;
    }
    
    private function getCalloutRespondingId() {
        $cruid = get_query_param('cruid');
        return $cruid;
    }
    
    private function getCalloutDetailsList() {
        if(isset($this->callout_details_list) === false) {
            global $log;
            
            $firehall_id = get_query_param('fhid');
            $callkey_id = get_query_param('ckid');
            $user_id = get_query_param('member_id');
            $callout_id = get_query_param('cid');
            
            if(isset($callout_id) === true && $callout_id !== null) {
                $callout_id = (int)$callout_id;
            }
            else {
                $callout_id = -1;
            }
            
            $log->trace("Call Info for firehall_id [$firehall_id] callout_id [$callout_id] callkey_id [$callkey_id] member_id [". ((isset($user_id) === true) ? $user_id : "null") ."]");
            
            if($callout_id !== -1 && isset($callkey_id) === true) {
                // Read from the database info about this callout
                $sql = "SELECT * FROM callouts ";
                if(isset($callout_id) === true && $callout_id !== null) {
                    $sql .= " WHERE id = :cid";
                    if(isset($callkey_id) === true && $callkey_id !== null) {
                        $sql .= " AND call_key = :ckid";
                    }
                }

                $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
                if(isset($callout_id) ===true && $callout_id !== null) {
                    $qry_bind->bindParam(':cid', $callout_id);
                    if(isset($callkey_id) === true && $callkey_id !== null) {
                        $qry_bind->bindParam(':ckid', $callkey_id);
                    }
                }
                $qry_bind->execute();
                
                $log->trace("Call Info callouts SQL success for sql [$sql] row count: " . $qry_bind->rowCount());
                
                $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
                $qry_bind->closeCursor();
                
                $results = array();
                foreach($rows as $row){
                    // Add any custom fields with values here
                     $row['callout_type_desc'] = convertCallOutTypeToText($row['calltype']);
                     $row['callout_status_desc'] = getCallStatusDisplayText($row['status']);
                     $row['callout_status_completed'] = ((int)$row['status'] === \CalloutStatusType::Complete);
                     $row['callout_status_cancelled'] = ((int)$row['status'] === \CalloutStatusType::Cancelled);
                     
                     if(isset($row['address']) === false || $row['address'] === '') {
                         $row['callout_address_dest'] = $row['latitude'] . ',' . $row['longitude'];
                     }
                     else {
                         $row['callout_address_dest'] = getAddressForMapping($this->getFirehall(), $row['address']);
                     }
                     $row['callout_geo_dest'] = $row['latitude'] . ',' . $row['longitude'];
                     
                    $results[] = $row;
                }

                $this->callout_details_list = $results;
            }
        }
        return $this->callout_details_list;
    }
    
    private function getCalloutDetailsRespondingList() {
        if(isset($this->callout_details_responding_list) === false) {
            global $log;
            
            $callouts = $this->getCalloutDetailsList();
            foreach($callouts as $row) {
                if($this->getFirehall()->LDAP->ENABLED === true) {
                    create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
                    $sql_response = 'SELECT a.*, b.user_id ' .
                                    ' FROM callouts_response a ' .
                                    ' LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id ' .
                                    ' WHERE calloutid = :cid;';
                }
                else {
                    $sql_response = 'SELECT a.*, b.user_id ' .
                                    ' FROM callouts_response a ' .
                                    ' LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
                                    ' WHERE calloutid = :cid;';
                }

                $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_response);
                $qry_bind->bindParam(':cid', $row['id']);
                $qry_bind->execute();
                
                $log->trace("Call Info callouts responders SQL success for sql [$sql_response] row count: " . $qry_bind->rowCount());

                $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
                $qry_bind->closeCursor();
                
                $results = array();
                foreach($rows as $row_r){
                    // Add any custom fields with values here
                    $row_r['responder_location'] = urlencode($row_r['latitude']) . ',' . urlencode($row_r['longitude']);
                    $row_r['firehall_location'] = urlencode($this->getGvm()->firehall->WEBSITE->FIREHALL_HOME_ADDRESS);

                    $results[] = $row_r;
                }
                $this->callout_details_responding_list = $results;
            }
        }
        return $this->callout_details_responding_list;
    }

    private function getCalloutDetailsNotRespondingList() {
        if(isset($this->callout_details_not_responding_list) === false) {
            global $log;
            
            // Select all user accounts for the firehall that did not yet respond
            if($this->getFirehall()->LDAP->ENABLED === true) {
                create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
                $sql_no_response = 'SELECT id, user_id FROM ldap_user_accounts ' .
                                   ' WHERE id NOT IN (SELECT useracctid ' .
                                   ' FROM callouts_response WHERE calloutid = :cid);';
            }
            else {
                $sql_no_response = 'SELECT id, user_id FROM user_accounts ' .
                                   ' WHERE id NOT IN (SELECT useracctid ' .
                                   ' FROM callouts_response WHERE calloutid = :cid);';
            }

            $cid = $this->getCalloutId();
            $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_no_response);
            $qry_bind->bindParam(':cid', $cid);
            $qry_bind->execute();
                
            $log->trace("Call Info callouts no responses SQL success for sql [$sql_no_response] row count: " . $qry_bind->rowCount());

            $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
            $qry_bind->closeCursor();
                
            $results = array();
            foreach($rows as $row){
                // Add any custom fields with values here
                $results[] = $row;
            }
            
            $this->callout_details_not_responding_list = $results;
        }
        return $this->callout_details_not_responding_list;
    }
    
    private function getCalloutDetailsEndRespondingList() {
        if(isset($this->callout_details_end_responding_list) === false) {
            global $log;
            
            // Select all user accounts for the firehall that did respond to the call
            if($this->getFirehall()->LDAP->ENABLED === true) {
                create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
                $sql_yes_response = 'SELECT id,user_id FROM ldap_user_accounts ' .
                                    ' WHERE id IN (SELECT useracctid ' .
                                    ' FROM callouts_response WHERE calloutid = :cid);';
            }
            else {
                $sql_yes_response = 'SELECT id,user_id FROM user_accounts ' .
                                    ' WHERE id IN (SELECT useracctid ' .
                                    ' FROM callouts_response WHERE calloutid = :cid);';
            }
            
            $cid = $this->getCalloutId();
            $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_yes_response);
            $qry_bind->bindParam(':cid', $cid);
            $qry_bind->execute();
                
            $log->trace("Call Info callouts yes responses SQL success for sql [$sql_yes_response] row count: " . $qry_bind->rowCount());
            
            $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
            $qry_bind->closeCursor();
                
            $results = array();
            foreach($rows as $row){
                // Add any custom fields with values here
                $results[] = $row;
            }
            
            $this->callout_details_end_responding_list = $results;
        }
        return $this->callout_details_end_responding_list;
    }
}
?>
