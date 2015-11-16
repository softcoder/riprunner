<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';

// The model class handling variable requests dynamically
class ReportsChartsViewModel extends BaseViewModel {
	
	private $callvoltypes_currentyear;
	private $callvoltypes_currentyear_cols;

	private $callresponsevol_currentyear;
	private $callresponsevol_currentyear_cols;

	private $callresponse_hours_currentyear;
	private $callresponse_hours_currentyear_cols;
	
		
	protected function getVarContainerName() { 
		return "reportscharts_vm";
	}
	
	public function __get($name) {
		if('calltypes_currentmonth' === $name) {
			$current_month_start = date('Y-m-01');
			$current_month_end = date('Y-m-t');
				
			return $this->getCallTypeStatsForDateRange(
					$current_month_start, $current_month_end);
		}
		if('calltypes_currentyear' === $name) {
			$year_start = strtotime('first day of January', time());
            $year_end = strtotime('last day of December', time());
                                
            $current_year_start = date('Y-m-d', $year_start);
            $current_year_end = date('Y-m-d', $year_end);
					
			return $this->getCallTypeStatsForDateRange(
						$current_year_start, $current_year_end);
		}
		if('calltypes_allyears' === $name) {
			return $this->getCallTypeStatsForAllDates();
		}
		if('callvoltypes_currentyear' === $name) {
			$this->getCallVolTypesCurrentyear();
			return $this->callvoltypes_currentyear;
		}
		if('callvoltypes_currentyear_cols' === $name) {
			$this->getCallVolTypesCurrentyear();
			return $this->callvoltypes_currentyear_cols;
		}
		if('callresponsevol_currentyear' === $name) {
			$this->getCallResponseVolCurrentyear();
			return $this->callresponsevol_currentyear;
		}
		if('callresponsevol_currentyear_cols' === $name) {
			$this->getCallResponseVolCurrentyear();
			return $this->callresponsevol_currentyear_cols;
		}

		if('callresponse_hours_currentyear' === $name) {
		    $this->getCallResponseHoursCurrentyear();
		    return $this->callresponse_hours_currentyear;
		}
		if('callresponse_hours_currentyear_cols' === $name) {
		    $this->getCallResponseHoursCurrentyear();
		    return $this->callresponse_hours_currentyear_cols;
		}

		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('calltypes_currentmonth','calltypes_currentyear','calltypes_allyears',
  				  'callvoltypes_currentyear', 'callvoltypes_currentyear_cols',
				  'callresponsevol_currentyear', 'callresponsevol_currentyear_cols',
				  'callresponse_hours_currentyear', 'callresponse_hours_currentyear_cols'
			)) === true) {
			return true;
		}
		return parent::__isset($name);
	}

	private function getCallResponseVolCurrentyear() {
		if(isset($this->callresponsevol_currentyear) === false) {
			$year_start = strtotime('first day of January', time());
			$year_end = strtotime('last day of December', time());
	
			$current_year_start = date('Y-m-d', $year_start);
			$current_year_end = date('Y-m-d', $year_end);
			$this->callresponsevol_currentyear_cols = array();
	
			$this->callresponsevol_currentyear =
					$this->getCallResponseVolumeStatsForDateRange(
							$current_year_start, $current_year_end, 
							$this->callresponsevol_currentyear_cols);
		}
	
	}
	
	
	private function getCallResponseHoursCurrentyear() {
	    if(isset($this->callresponse_hours_currentyear) === false) {
	        $year_start = strtotime('first day of January', time());
	        $year_end = strtotime('last day of December', time());
	
	        $current_year_start = date('Y-m-d', $year_start);
	        $current_year_end = date('Y-m-d', $year_end);
	        $this->callresponse_hours_currentyear_cols = array();
	
	        $this->callresponse_hours_currentyear =
	        $this->getCallResponseHoursStatsForDateRange(
	                $current_year_start, $current_year_end, 
	                $this->callresponse_hours_currentyear_cols);
	    }
	
	}
	
	
	private function getCallVolTypesCurrentyear() {
		if(isset($this->callvoltypes_currentyear) === false) {
			$year_start = strtotime('first day of January', time());
			$year_end = strtotime('last day of December', time());
			 
			$current_year_start = date('Y-m-d', $year_start);
			$current_year_end = date('Y-m-d', $year_end);
			$this->callvoltypes_currentyear_cols = array();
		
			$this->callvoltypes_currentyear =
			$this->getCallVolumeStatsForDateRange(
					$current_year_start, $current_year_end, 
					$this->callvoltypes_currentyear_cols);
		}
	}
	
/*
 * 
The SQL below gets a rough estimate of hours spent per person on calls

select a.id,MONTH(calltime) AS month,b.useracctid,time_to_sec(timediff(max(a.updatetime), LEAST(a.calltime,a.updatetime) )) / 3600 as hours_spent 
from callouts a left join callouts_response b on a.id = b.calloutid 
where a.status in (3,10)  
group by a.id,month,b.useracctid order by a.id,b.useracctid,hours_spent;

Total Hours spent on all callouts for the year:

select sum(time_to_sec(timediff(updatetime, LEAST(calltime,updatetime) )) / 3600) as hours_spent 
from callouts 
where calltime between '2015-01-01' AND '2015-12-31 23:59:59' AND status in (3,10)  

 */	
	private function getCallTypeStatsForDateRange($startDate, $endDate) {
		// Read from the database
	    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
	    $sql = $sql_statement->getSqlStatement('reports_calltype_by_daterange');
	    
// 		$sql = "SELECT calltype, COUNT(*) count FROM callouts " .
// 			   " WHERE calltime BETWEEN :start AND :end " .
// 			   " AND calltype NOT IN ('TRAINING','TESTONLY') " .
// 			   " GROUP BY calltype ORDER BY calltype;";

		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->bindParam(':start', $startDate);
		$qry_bind->bindParam(':end', $endDate);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
		$qry_bind->closeCursor();
		
		// Build the data array
		$data_results = array();
		foreach($rows as $row) {
			$row_result = array();
	
			$callTypeDesc = convertCallOutTypeToText($row->calltype);
			$row_result[$callTypeDesc] = ($row->count + 0);
			array_push($data_results, $row_result);
		}
	
		return $data_results;
	}
	private function getCallTypeStatsForAllDates() {
	    
	    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
	    $sql = $sql_statement->getSqlStatement('reports_calltype_all');
	     
// 		$sql = " SELECT calltype, COUNT(*) count FROM callouts " .
// 			   " WHERE calltype NOT IN ('TRAINING','TESTONLY') "			   .
// 			   " GROUP BY calltype ORDER BY count DESC;";

		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
		$qry_bind->closeCursor();
		
		$data_results = array();
		foreach($rows as $row) {
			$row_result = array();
			$callTypeDesc = convertCallOutTypeToText($row->calltype);
			$row_result[$callTypeDesc] = ($row->count + 0);
			array_push($data_results, $row_result);
		}
		return $data_results;
	}
	
	private function getCallVolumeStatsForDateRange($startDate, $endDate, 
													&$dynamicColumnTitles) {
	
		$MAX_MONTHLY_LABEL = "*MONTH TOTAL";
	
		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		$sql_titles = $sql_statement->getSqlStatement('reports_callvolume_titles_by_daterange');
		$sql_titles = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql_titles);
		
// 		$sql_titles = '(SELECT "' .$MAX_MONTHLY_LABEL . '" as datalabel)' .
// 				' UNION (SELECT calltype as datalabel ' .
// 				' FROM callouts WHERE calltime BETWEEN :start AND :end ' .
// 				' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
// 				' GROUP BY datalabel) ORDER BY (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';

		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_titles);
		$qry_bind->bindParam(':start', $startDate);
		$qry_bind->bindParam(':end', $endDate);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
		$qry_bind->closeCursor();
		
		// Build the data array
		$titles_results = array();
		foreach($rows as $row_titles) {
			$callTypeDesc = $row_titles->datalabel;
			if($callTypeDesc !== $MAX_MONTHLY_LABEL) {
				$callTypeDesc = $callTypeDesc . ' - ' . convertCallOutTypeToText($row_titles->datalabel);
			}
			array_push($titles_results, $callTypeDesc);
			array_push($dynamicColumnTitles, $callTypeDesc);
		}
	
		// Read from the database
		// This routine counts the number of calls in a given month. it will be displayed on the graphs page
		// 'Total Call Volume by Type (All Calls) - Current Year by Month'
		// TRAINING and TESTONLY records are filtered out
		
		$sql = $sql_statement->getSqlStatement('reports_callvolume_by_daterange');
		$sql = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql);
		
// 		$sql = '(SELECT MONTH(calltime) AS month, "' . $MAX_MONTHLY_LABEL . '" AS datalabel, count(*) AS count ' .
// 				' FROM callouts WHERE calltime BETWEEN :start AND :end ' .
// 				' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                 ' GROUP BY month ORDER BY month)' .
//                 'UNION (SELECT MONTH(calltime) AS month, calltype AS datalabel, count(*) AS count ' .
//                 ' FROM callouts WHERE calltime BETWEEN :start AND :end ' .
// 				' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                 ' GROUP BY datalabel, month ORDER BY month) ORDER BY month, (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';

		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->bindParam(':start', $startDate);
		$qry_bind->bindParam(':end', $endDate);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
		$qry_bind->closeCursor();
		
	   // Build the data array
	   $data_results = array();
	   foreach($rows as $row) {
	   		$callTypeDesc = $row->datalabel;
	   		if($callTypeDesc !== $MAX_MONTHLY_LABEL) {
	   			$callTypeDesc = $callTypeDesc . ' - ' . convertCallOutTypeToText($row->datalabel);
	   		}
	
	   		$row_result = array($row->month,$callTypeDesc,$row->count + 0);
	   		array_push($data_results, $row_result);
	   }
	
	   // Ensure every month of the year exists in the results for each calltype
	   for($index=1; $index <= 12; $index++) {
	   		foreach($titles_results as $title) {
	   			$found_index = false;
	            foreach($data_results as $data) {
	            	$monthNumber = ($data[0] + 0);
	                $labelName = $data[1];
	                if($index === $monthNumber && $title === $labelName) {
	                	$found_index = true;
	                	break;
	                }
	            }
	            if($found_index === false) {
	            	$row_result = array($index,$title,0);
	                array_push($data_results, $row_result);
	            }
	        }
	   }
	
	   // Sort by month # then by calltype
	   usort($data_results, make_comparer(0, 1));
	
	   // Replace month # with month name and build array for each unique calltype
	   $formatted_data = array();

       $current_month_number = -1;
       $current_month_array = null;

       foreach ($data_results as $key => $row) {
       		$monthNumber = ($row[0] + 0);
       		$monthName = date("F", mktime(0, 0, 0, $monthNumber, 10));

       		$monthCount = $row[2];
	
       		if($current_month_number !== $monthNumber) {
       			if(isset($current_month_array) === true) {
       				array_push($formatted_data, $current_month_array);
       			}
	
	            $current_month_array = array();
	            array_push($current_month_array, $monthName);
	            array_push($current_month_array, $monthCount);
	
	            $current_month_number = $monthNumber;
	        }
	        else {
	        	array_push($current_month_array, $monthCount);
	        }
	   }
	   if(isset($current_month_array) === true) {
	   		array_push($formatted_data, $current_month_array);
	   }
	
	   return $formatted_data;
	}
	
	private function getCallResponseVolumeStatsForDateRange($startDate, $endDate, 
	                			&$dynamicColumnTitles) {
	
		global $log;
		$log->trace("Call getCallResponseVolumeStatsForDateRange START");
		
		$MAX_MONTHLY_LABEL = "*MONTHLY TOTAL";

		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		
		if($this->getGvm()->firehall->LDAP->ENABLED === true) {
			create_temp_users_table_for_ldap($this->getGvm()->firehall, 
												$this->getGvm()->RR_DB_CONN);
			// Search the database
			// Find all occourences of calls that are completed(10) or canceled(3).  
			// TRAINING and TESTONLY records are excluded
			$sql_titles = $sql_statement->getSqlStatement('ldap_reports_callresponse_volume_titles_by_daterange');
			$sql_titles = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql_titles);
				
// 			$sql_titles = '(SELECT "'. $MAX_MONTHLY_LABEL .'" as datalabel)' .
//             			  ' UNION (SELECT b.user_id AS datalabel ' .
//                 		  '        FROM callouts_response a' .
//                 		  '        LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id ' .
//                 		  '        LEFT JOIN callouts c ON a.calloutid = c.id ' .
//                 		  '        WHERE c.status IN (3,10) AND a.responsetime BETWEEN :start AND :end ' .
// 						  '     AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                 		  '        GROUP BY datalabel) ORDER BY (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';
        }
        else {
            $sql_titles = $sql_statement->getSqlStatement('reports_callresponse_volume_titles_by_daterange');
            $sql_titles = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql_titles);
            
//         	$sql_titles = '(SELECT "'. $MAX_MONTHLY_LABEL .'" as datalabel)' .
//           				  ' UNION (SELECT b.user_id AS datalabel ' .
// 		                '        FROM callouts_response a' .
// 		           		'        LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
// 		           		'        LEFT JOIN callouts c ON a.calloutid = c.id ' .
// 	                    '        WHERE c.status IN (3,10) AND a.responsetime BETWEEN :start AND :end ' .
// 						  '     AND calltype NOT IN ("TRAINING","TESTONLY") ' .
// 	       				'        GROUP BY datalabel) ORDER BY (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';
        }

        $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_titles);
        $qry_bind->bindParam(':start', $startDate);
        $qry_bind->bindParam(':end', $endDate);
        $qry_bind->execute();
        
        $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
        $qry_bind->closeCursor();
        
	    $log->trace("Calling getCallResponseVolumeStatsForDateRange sql_titles [" . $sql_titles . "]");
	    
        // Build the data array
        $titles_results = array();
        foreach($rows as $row_titles) {
        	array_push($titles_results, $row_titles->datalabel);
            array_push($dynamicColumnTitles, $row_titles->datalabel);
        }
	
        if($this->getGvm()->firehall->LDAP->ENABLED === true) {
        	create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);

			// Find all occourences of calls that are completed(10) or canceled(3).  
			// TRAINING and TESTONLY records are excluded
        	$sql = $sql_statement->getSqlStatement('ldap_reports_callresponse_volume_by_daterange');
        	$sql = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql);
        	 
//             $sql = 	'(SELECT MONTH(calltime) AS month, "'. $MAX_MONTHLY_LABEL .'" AS datalabel, count(*) AS count ' .
//  	            	' FROM callouts WHERE status IN (3,10) AND calltime BETWEEN :start AND :end ' .
// 					' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                 	' GROUP BY month ORDER BY month)' .
//                 	' UNION (SELECT MONTH(responsetime) AS month, b.user_id AS datalabel, count(*) AS count ' .
//                     ' FROM callouts_response a ' .
//                     ' LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id' .
//                     ' LEFT JOIN callouts c ON a.calloutid = c.id ' .
//                     ' WHERE c.status IN (3,10) AND a.responsetime BETWEEN :start AND :end ' .
// 					' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                     ' GROUP BY month, datalabel ORDER BY month, datalabel) ORDER BY month, (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';
        }
        else {
            $sql = $sql_statement->getSqlStatement('reports_callresponse_volume_by_daterange');
            $sql = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql);
            
//            	$sql =	'(SELECT MONTH(calltime) AS month, "'. $MAX_MONTHLY_LABEL .'" AS datalabel, count(*) AS count ' .
// 					' FROM callouts WHERE status IN (3,10) AND calltime BETWEEN :start AND :end ' .
// 					' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
// 					' GROUP BY month ORDER BY month)' .
// 					'UNION (SELECT MONTH(responsetime) AS month, b.user_id AS datalabel, count(*) AS count ' .
// 					' FROM callouts_response a ' .
// 					' LEFT JOIN user_accounts b ON a.useracctid = b.id' .
// 					' LEFT JOIN callouts c ON a.calloutid = c.id ' .
// 					' WHERE c.status IN (3,10) AND a.responsetime BETWEEN :start AND :end ' .
// 					' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
// 					' GROUP BY month, datalabel ORDER BY month, datalabel) ORDER BY month, (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';
       }

       $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
       $qry_bind->bindParam(':start', $startDate);
       $qry_bind->bindParam(':end', $endDate);
       $qry_bind->execute();
       
       $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
       $qry_bind->closeCursor();
        
       $log->trace("Calling getCallResponseVolumeStatsForDateRange sql [$sql] for date range: $startDate - $endDate");
       
       // Build the data array
       $data_results = array();
       foreach($rows as $row){
       		$row_result = array($row->month,$row->datalabel,$row->count + 0);
         	array_push($data_results, $row_result);
       }

       // Ensure every month of the year exists in the results for each calltype
       for($index=1; $index <= 12; $index++) {
       		foreach($titles_results as $title) {
       			$found_index = false;
            	foreach($data_results as $data) {
            		$monthNumber = ($data[0] + 0);
            		$labelName = $data[1];
            		if($index === $monthNumber && $title === $labelName) {
            			$found_index = true;
            			break;
            		}
            	}
            	if($found_index === false) {
            		$row_result = array($index,$title,0);
            		array_push($data_results, $row_result);
            	}
            }
        }
	
	    // Sort by month # then by calltype
	    usort($data_results, make_comparer(0, 1));
	
      	// Replace month # with month name and build array for each unique calltype
       	$formatted_data = array();
	
       	$current_month_number = -1;
       	$current_month_array = null;
	
       	foreach ($data_results as $key => $row) {
       		$monthNumber = ($row[0] + 0);
	        $monthName = date("F", mktime(0, 0, 0, $monthNumber, 10));
	
            $monthCount = $row[2];
	
           if($current_month_number !== $monthNumber) {
           		if(isset($current_month_array) === true) {
           			array_push($formatted_data, $current_month_array);
	            }
	
	            $current_month_array = array();
	            array_push($current_month_array, $monthName);
	            array_push($current_month_array, $monthCount);
	
	            $current_month_number = $monthNumber;
	       }
	       else {
	       		array_push($current_month_array, $monthCount);
	       }
       	}

       	if(isset($current_month_array) === true) {
       		array_push($formatted_data, $current_month_array);
	    }
	
	    $log->trace("Call getCallResponseVolumeStatsForDateRange END");
	    
	    return $formatted_data;
	}

    private function getCallResponseHoursStatsForDateRange($startDate, $endDate, 
            &$dynamicColumnTitles) {
    
        global $log;
        $log->trace("Call getCallResponseHoursStatsForDateRange START");
    
        $MAX_MONTHLY_LABEL = "*MONTHLY TOTAL";
    
        $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
        if($this->getGvm()->firehall->LDAP->ENABLED === true) {
            create_temp_users_table_for_ldap($this->getGvm()->firehall,
            $this->getGvm()->RR_DB_CONN);
            // Search the database
            // Find all occourences of calls that are completed(10) or canceled(3).
            // TRAINING and TESTONLY records are excluded
            $sql_titles = $sql_statement->getSqlStatement('ldap_reports_callresponse_hours_titles_by_daterange');
            $sql_titles = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql_titles);
            
//             $sql_titles = '(SELECT "'. $MAX_MONTHLY_LABEL .'" as datalabel)' .
//                     ' UNION (SELECT b.user_id AS datalabel ' .
//                     '        FROM callouts_response a' .
//                     '        LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id ' .
//                     '        LEFT JOIN callouts c ON a.calloutid = c.id ' .
//                     '        WHERE c.status IN (3,10) AND a.responsetime BETWEEN :start AND :end ' .
//                     '     AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                     '        GROUP BY datalabel) ORDER BY (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';
        }
        else {
            $sql_titles = $sql_statement->getSqlStatement('reports_callresponse_hours_titles_by_daterange');
            $sql_titles = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql_titles);
            
//             $sql_titles = '(SELECT "'. $MAX_MONTHLY_LABEL .'" as datalabel)' .
//               				  ' UNION (SELECT b.user_id AS datalabel ' .
//               				  '        FROM callouts_response a' .
//               				  '        LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
//               				  '        LEFT JOIN callouts c ON a.calloutid = c.id ' .
//               				  '        WHERE c.status IN (3,10) AND a.responsetime BETWEEN :start AND :end ' .
//               				  '     AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//               				  '        GROUP BY datalabel) ORDER BY (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel;';
        }
    
        $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_titles);
        $qry_bind->bindParam(':start', $startDate);
        $qry_bind->bindParam(':end', $endDate);
        $qry_bind->execute();
    
        $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
        $qry_bind->closeCursor();
    
        $log->trace("Calling getCallResponseHoursStatsForDateRange sql_titles [" . $sql_titles . "]");
         
        // Build the data array
        $titles_results = array();
        foreach($rows as $row_titles) {
            array_push($titles_results, $row_titles->datalabel);
            array_push($dynamicColumnTitles, $row_titles->datalabel);
        }
    
        if($this->getGvm()->firehall->LDAP->ENABLED === true) {
            create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
    
            // Find all occourences of calls that are completed(10) or canceled(3).
            // TRAINING and TESTONLY records are excluded
            $sql = $sql_statement->getSqlStatement('ldap_reports_callresponse_hours_by_daterange');
            $sql = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql);
            
//             $sql = 	'(SELECT MONTH(calltime) AS month, "'. $MAX_MONTHLY_LABEL .'" AS datalabel, (time_to_sec(timediff(updatetime, LEAST(calltime,updatetime) )) / 3600) as hours_spent, id as cid ' .
//                     ' FROM callouts WHERE status IN (3,10) AND calltime BETWEEN :start AND :end ' .
//                     ' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                     ' GROUP BY id, month ORDER BY month, id)' .
//                     ' UNION (SELECT MONTH(c.calltime) AS month, b.user_id AS datalabel, (time_to_sec(timediff(c.updatetime, LEAST(c.calltime,c.updatetime) )) / 3600) as hours_spent, c.id as cid ' .
//                     ' FROM callouts_response a ' .
//                     ' LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id' .
//                     ' LEFT JOIN callouts c ON a.calloutid = c.id ' .
//                     ' WHERE c.status IN (3,10) AND c.calltime BETWEEN :start AND :end ' .
//                     ' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                     ' GROUP BY c.id, month, datalabel ORDER BY month, datalabel, cid) ORDER BY month, (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel, cid;';
        }
        else {
            $sql = $sql_statement->getSqlStatement('reports_callresponse_hours_by_daterange');
            $sql = preg_replace_callback('(:max_monthly_label)', function ($m) use ($MAX_MONTHLY_LABEL) { return $MAX_MONTHLY_LABEL; }, $sql);
            
//             $sql =	'(SELECT MONTH(calltime) AS month, "'. $MAX_MONTHLY_LABEL .'" AS datalabel, (time_to_sec(timediff(updatetime, LEAST(calltime,updatetime) )) / 3600) as hours_spent, id as cid ' .
//                     ' FROM callouts WHERE status IN (3,10) AND calltime BETWEEN :start AND :end ' .
//                     ' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                     ' GROUP BY id, month ORDER BY month, id)' .
//                     'UNION (SELECT MONTH(c.calltime) AS month, b.user_id AS datalabel, (time_to_sec(timediff(c.updatetime, LEAST(c.calltime,c.updatetime) )) / 3600) as hours_spent, c.id as cid ' .
//                     ' FROM callouts_response a ' .
//                     ' LEFT JOIN user_accounts b ON a.useracctid = b.id' .
//                     ' LEFT JOIN callouts c ON a.calloutid = c.id ' .
//                     ' WHERE c.status IN (3,10) AND c.calltime BETWEEN :start AND :end ' .
//                     ' AND calltype NOT IN ("TRAINING","TESTONLY") ' .
//                     ' GROUP BY c.id, month, datalabel ORDER BY month, datalabel, c.id) ORDER BY month, (datalabel="'. $MAX_MONTHLY_LABEL .'") DESC,datalabel, cid;';
        }
    
        $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
        $qry_bind->bindParam(':start', $startDate);
        $qry_bind->bindParam(':end', $endDate);
        $qry_bind->execute();
         
        $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
        $qry_bind->closeCursor();
    
        //print_r ($rows);
        //var_dump ($rows, true);
        
        $log->trace("Calling getCallResponseHoursStatsForDateRange sql [" . $sql . "]");
         
        // Build the data array
        $data_results = array();
        foreach($rows as $row) {
            $row_result = array($row->month,$row->datalabel,$row->hours_spent + 0.0);
            array_push($data_results, $row_result);
        }

        // Sum the values
        $sumArray = array();
        foreach ($data_results as $k=>$subArray) {
            $month_key = null;
            $user_key = null;
            foreach ($subArray as $id=>$value) {
                //$sumArray[$id]+=$value;
                //echo "id [$id] value [$value]" . PHP_EOL;
                
                if($id === 0) {
                    $month_key = $value + 0;
                }
                if($id === 1) {
                    $user_key = $value;
                }
                if($id === 2) {
                    if(array_key_exists($month_key,$sumArray) === false) {
                        //echo "#1 month_key [$month_key] user_key [$user_key] value [$value]<BR>" . PHP_EOL;
                        
                        $row = array();
                        $row[$user_key] = ($value + 0.0);
                        $sumArray[$month_key] = $row;
                        
                        //echo "#1.1 month_key [$month_key] user_key [$user_key] cur [" .$sumArray[$month_key][$user_key]."] value [$value]<BR>" . PHP_EOL;
                    }
                    else if(array_key_exists($user_key,$sumArray[$month_key]) === false) {
                        //echo "#2 month_key [$month_key] user_key [$user_key] value [$value]<BR>" . PHP_EOL;

                        $sumArray[$month_key][$user_key] = ($value + 0.0);
                        
                        //echo "#2.1 month_key [$month_key] user_key [$user_key] cur [" .$sumArray[$month_key][$user_key]."] value [$value]<BR>" . PHP_EOL;
                    }
                    else {
                        //echo "#3 month_key [$month_key] user_key [$user_key] cur [" .$sumArray[$month_key][$user_key]."] value [$value]<BR>" . PHP_EOL;
                        
                        $sumArray[$month_key][$user_key] += ($value + 0.0);
                        
                        //echo "#3.1 month_key [$month_key] user_key [$user_key] cur [" .$sumArray[$month_key][$user_key]."] value [$value]<BR>" . PHP_EOL;
                    }
                }
            }
        }
        //echo "DUMP ARRAY<BR>" . PHP_EOL;
        //var_dump ($sumArray, true);
        
        $newArray = array();
        foreach ($sumArray as $month_key=>$subArray) {
            foreach ($subArray as $user_key=>$value) {
                
                //echo "MERGING [$month_key] [$user_key] [$value]<BR>" . PHP_EOL;
                
                $row_result = array($month_key,$user_key,$value);
                array_push($newArray, $row_result);
            }
        }
        //echo "DUMP ARRAY #2<BR>" . PHP_EOL;
        //var_dump ($newArray, true);
        
        $data_results = $newArray;
        
        // Ensure every month of the year exists in the results for each calltype
        for($index=1; $index <= 12; $index++) {
            foreach($titles_results as $title) {
                $found_index = false;
                foreach($data_results as $data) {
                    $monthNumber = ($data[0] + 0);
                    $labelName = $data[1];
                    if($index === $monthNumber && $title === $labelName) {
                        $found_index = true;
                        break;
                    }
                }
                if($found_index === false) {
                    $row_result = array($index,$title,0);
                    array_push($data_results, $row_result);
                }
            }
        }
    
        // Sort by month # then by calltype
        usort($data_results, make_comparer(0, 1));
    
        // Replace month # with month name and build array for each unique calltype
        $formatted_data = array();
    
        $current_month_number = -1;
        $current_month_array = null;
    
        foreach ($data_results as $key => $row) {
            $monthNumber = ($row[0] + 0);
            $monthName = date("F", mktime(0, 0, 0, $monthNumber, 10));
    
            $monthCount = $row[2];
    
            if($current_month_number !== $monthNumber) {
                if(isset($current_month_array) === true) {
                    array_push($formatted_data, $current_month_array);
                }
    
                $current_month_array = array();
                array_push($current_month_array, $monthName);
                array_push($current_month_array, $monthCount);
    
                $current_month_number = $monthNumber;
            }
            else {
                array_push($current_month_array, $monthCount);
            }
        }
    
        if(isset($current_month_array) === true) {
            array_push($formatted_data, $current_month_array);
        }
    
        $log->trace("Call getCallResponseHoursStatsForDateRange END");
         
        return $formatted_data;
    }
}
?>
