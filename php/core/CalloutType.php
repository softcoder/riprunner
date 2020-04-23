<?php
// ==============================================================
//	Copyright (C) 2017 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once 'CalloutTypeDef.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Types of callout codes

class CalloutType {

    static public function getTypeList($FIREHALL) {
        global $log;
        static $typeList = null;
        static $typeListFromDB = false;
        if($typeList == null || $typeListFromDB == false) {

            //$must_close_db = true;
            $db = new \riprunner\DbConnection($FIREHALL);
            $db_connection = $db->getConnection();

            $sql_statement = new \riprunner\SqlStatement($db_connection);
            $sql = $sql_statement->getSqlStatement('type_list_select');

            $qry_bind = $db_connection->prepare($sql);
            $qry_bind->execute();

            $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
            $qry_bind->closeCursor();
            \riprunner\DbConnection::disconnect_db( $db_connection );

            if($log !== null) $log->trace("Call get type codes SQL success for sql [$sql] row count: " . 
            safe_count($rows));

            $typeList = array();
            foreach($rows as $row) {
                $typeDef = new CalloutTypeDef($row['id'],$row['code'],$row['name'],$row['description'],$row['custom_tag'],$row['effective_date'],$row['expiration_date'],$row['updatetime']);
                //$typeList[$typeDef->getCode()] = $typeDef;
                $typeList[$typeDef->getCode()][] = $typeDef;
            }

            $typeListFromDB = true;
            //}
        }
        return $typeList;
    }
    
    static public function getTypeByCode($code, $FIREHALL, $asOfDate) {
        if($code != null) {
            $typeList = self::getTypeList($FIREHALL, $asOfDate);
            if(array_key_exists($code, $typeList)) {
                $codeTypes = $typeList[$code];
                foreach($codeTypes as $codeType) {
                    if($codeType->isActiveForDate($asOfDate) == true) {
                        return $codeType;
                    }
                }
            }
            return new CalloutTypeDef(-1,$code,'UNKNOWN CODE ['.$code.']','UNKNOWN CODE','',null,null,null);
        }
        return null;
    }
    
}