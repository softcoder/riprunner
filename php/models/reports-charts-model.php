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
	
	protected function getVarContainerName() { 
		return "reportscharts_vm";
	}
	
	public function __get($name) {
		if('calltypes_currentmonth' == $name) {
			$current_month_start = date('Y-m-01');
			$current_month_end = date('Y-m-t');
				
			return $this->getCallTypeStatsForDateRange(
					$current_month_start,$current_month_end);
		}
		if('calltypes_currentyear' == $name) {
			$year_start = strtotime('first day of January', time());
            $year_end = strtotime('last day of December', time());
                                
            $current_year_start = date('Y-m-d',$year_start);
            $current_year_end = date('Y-m-d',$year_end);
					
			return $this->getCallTypeStatsForDateRange(
						$current_year_start,$current_year_end);
		}
		if('calltypes_allyears' == $name) {
			return $this->getCallTypeStatsForAllDates();
		}
		if('callvoltypes_currentyear' == $name) {
			$this->getCallVolTypesCurrentyear();
			return $this->callvoltypes_currentyear;
		}
		if('callvoltypes_currentyear_cols' == $name) {
			$this->getCallVolTypesCurrentyear();
			return $this->callvoltypes_currentyear_cols;
		}
		if('callresponsevol_currentyear' == $name) {
			$this->getCallResponseVolCurrentyear();
			return $this->callresponsevol_currentyear;
		}
		if('callresponsevol_currentyear_cols' == $name) {
			$this->getCallResponseVolCurrentyear();
			return $this->callresponsevol_currentyear_cols;
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('calltypes_currentmonth','calltypes_currentyear','calltypes_allyears',
  				  'callvoltypes_currentyear', 'callvoltypes_currentyear_cols',
				  'callresponsevol_currentyear', 'callresponsevol_currentyear_cols'
			))) {
			return true;
		}
		return parent::__isset($name);
	}

	private function getCallResponseVolCurrentyear() {
		if(isset($this->callresponsevol_currentyear) == false) {
			$year_start = strtotime('first day of January', time());
			$year_end = strtotime('last day of December', time());
	
			$current_year_start = date('Y-m-d',$year_start);
			$current_year_end = date('Y-m-d',$year_end);
			$this->callresponsevol_currentyear_cols = array();
	
			$this->callresponsevol_currentyear =
					$this->getCallResponseVolumeStatsForDateRange(
							$current_year_start,$current_year_end,
							$this->callresponsevol_currentyear_cols);
		}
	
	}
	
	private function getCallVolTypesCurrentyear() {
		if(isset($this->callvoltypes_currentyear) == false) {
			$year_start = strtotime('first day of January', time());
			$year_end = strtotime('last day of December', time());
			 
			$current_year_start = date('Y-m-d',$year_start);
			$current_year_end = date('Y-m-d',$year_end);
			$this->callvoltypes_currentyear_cols = array();
		
			$this->callvoltypes_currentyear =
			$this->getCallVolumeStatsForDateRange(
					$current_year_start,$current_year_end,
					$this->callvoltypes_currentyear_cols);
		}
	}
	
	private function getCallTypeStatsForDateRange($startDate,$endDate) {
		// Read from the database
		$sql = "SELECT calltype, COUNT(*) count FROM callouts " .
			   " WHERE calltime BETWEEN '$startDate' AND '$endDate'" .
			   " AND calltype NOT IN ('TRAINING','TESTONLY') " .
			   " GROUP BY calltype ORDER BY calltype;";
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
	
		// Build the data array
		$data_results = array();
		while($row = $sql_result->fetch_object()) {
			$row_result = array();
	
			$callTypeDesc = convertCallOutTypeToText($row->calltype);
			$row_result[$callTypeDesc] = $row->count + 0;
			array_push($data_results,$row_result);
		}
		$sql_result->close();
	
		return $data_results;
	}
	private function getCallTypeStatsForAllDates() {
		$sql = " SELECT calltype, COUNT(*) count FROM callouts " .
			   " WHERE calltype NOT IN ('TRAINING','TESTONLY') "			   .
			   " GROUP BY calltype ORDER BY count DESC;";
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
		$data_results = array();
		while($row = $sql_result->fetch_object()) {
			$row_result = array();
			$callTypeDesc = convertCallOutTypeToText($row->calltype);
			$row_result[$callTypeDesc] = $row->count + 0;
			array_push($data_results,$row_result);
		}
		$sql_result->close();
		return $data_results;
	}
	
	private function getCallVolumeStatsForDateRange($startDate,$endDate,
													&$dynamicColumnTitles) {
	
		$MAX_MONTHLY_LABEL = "*MONTH TOTAL";
	
		/*
		 (SELECT "ALL" as datalabel)
		 UNION
		 (SELECT calltype as datalabel
		 FROM callouts WHERE calltime BETWEEN '2014-01-01' AND '2014-12-31'
		 GROUP BY datalabel) ORDER BY datalabel;
		 */
		$sql_titles = '(SELECT "' .$MAX_MONTHLY_LABEL . '" as datalabel)' .
				' UNION (SELECT calltype as datalabel ' .
				' FROM callouts WHERE calltime BETWEEN \'' .
				$startDate .'\' AND \'' . $endDate . '\'' .
				' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
				' GROUP BY datalabel) ORDER BY datalabel;';
		$sql_titles_result = $this->getGvm()->RR_DB_CONN->query( $sql_titles );
		if($sql_titles_result == false) {
			printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql_titles . "]");
		}
	
		// Build the data array
		$titles_results = array();
		while($row_titles = $sql_titles_result->fetch_object()) {
			$callTypeDesc = $row_titles->datalabel;
			if($callTypeDesc != $MAX_MONTHLY_LABEL) {
				$callTypeDesc = $callTypeDesc . ' - ' . convertCallOutTypeToText($row_titles->datalabel);
			}
			array_push($titles_results,$callTypeDesc);
			array_push($dynamicColumnTitles,$callTypeDesc);
		}
		$sql_titles_result->close();
	
		// Read from the database
		// This routine counts the number of calls in a given month. it will be displayed on the graphs page
		// 'Total Call Volume by Type (All Calls) - Current Year by Month'
		// TRAINING and TESTONLY records are filtered out
		$sql = '(SELECT MONTH(calltime) AS month, "' . $MAX_MONTHLY_LABEL . '" AS datalabel, count(*) AS count ' .
				' FROM callouts WHERE calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
				' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
                ' GROUP BY month ORDER BY month)' .
                'UNION (SELECT MONTH(calltime) AS month, calltype AS datalabel, count(*) AS count ' .
                ' FROM callouts WHERE calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
				' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
                ' GROUP BY datalabel, month ORDER BY month) ORDER BY month, datalabel;';
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
	
	   // Build the data array
	   $data_results = array();
	   while($row = $sql_result->fetch_object()) {
	   		$callTypeDesc = $row->datalabel;
	   		if($callTypeDesc != $MAX_MONTHLY_LABEL) {
	   			$callTypeDesc = $callTypeDesc . ' - ' . convertCallOutTypeToText($row->datalabel);
	   		}
	
	   		$row_result = array($row->month,$callTypeDesc,$row->count + 0);
	   		array_push($data_results,$row_result);
	   }
	   $sql_result->close();
	
	   // Ensure every month of the year exists in the results for each calltype
	   for($index=1;$index <= 12; $index++) {
	   		foreach($titles_results as $title) {
	   			$found_index = false;
	            foreach($data_results as $data) {
	            	$monthNumber = $data[0];
	                $labelName = $data[1];
	                if($index == $monthNumber && $title == $labelName) {
	                	$found_index = true;
	                	break;
	                }
	            }
	            if($found_index == false) {
	            	$row_result = array($index,$title,0);
	                array_push($data_results,$row_result);
	            }
	        }
	   }
	
	   // Sort by month # then by calltype
	   usort($data_results, make_comparer(0,1));
	
	   // Replace month # with month name and build array for each unique calltype
	   $formatted_data = array();

       $current_month_number = -1;
       $current_month_array = null;

       foreach ($data_results as $key => $row) {
       		$monthNumber = $row[0];
       		$monthName = date("F", mktime(0, 0, 0, $monthNumber, 10));

       		$monthCount = $row[2];
	
       		if($current_month_number != $monthNumber) {
       			if(isset($current_month_array)) {
       				array_push($formatted_data,$current_month_array);
       			}
	
	            $current_month_array = array();
	            array_push($current_month_array,$monthName);
	            array_push($current_month_array,$monthCount);
	
	            $current_month_number = $monthNumber;
	        }
	        else {
	        	array_push($current_month_array,$monthCount);
	        }
	   }
	   if(isset($current_month_array)) {
	   		array_push($formatted_data,$current_month_array);
	   }
	
	   return $formatted_data;
	}
	
	private function getCallResponseVolumeStatsForDateRange($startDate,$endDate,
	                			&$dynamicColumnTitles) {
	
		global $log;
		$log->trace("Call getCallResponseVolumeStatsForDateRange START");
		
		$MAX_MONTHLY_LABEL = "*MONTHLY TOTAL";

		if($this->getGvm()->firehall->LDAP->ENABLED) {
			create_temp_users_table_for_ldap($this->getGvm()->firehall, 
												$this->getGvm()->RR_DB_CONN);
			// Search the database
			// Find all occourences of calls that are completed(10) or canceled(3).  
			// TRAINING and TESTONLY records are excluded
			$sql_titles = '(SELECT "'. $MAX_MONTHLY_LABEL .'" as datalabel)' .
            			  ' UNION (SELECT b.user_id AS datalabel ' .
                		  '        FROM callouts_response a' .
                		  '        LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id ' .
                		  '        LEFT JOIN callouts c ON a.calloutid = c.id ' .
                		  '        WHERE c.status IN (3,10) AND a.responsetime BETWEEN \'' .
                				$startDate .'\' AND \'' . $endDate . '\'' .
						  '     AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
                		  '        GROUP BY datalabel) ORDER BY datalabel;';
        }
        else {
        	$sql_titles = '(SELECT "'. $MAX_MONTHLY_LABEL .'" as datalabel)' .
          				  ' UNION (SELECT b.user_id AS datalabel ' .
		                '        FROM callouts_response a' .
		           		'        LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
		           		'        LEFT JOIN callouts c ON a.calloutid = c.id ' .
	                    '        WHERE c.status IN (3,10) AND a.responsetime BETWEEN \'' .
		                   		$startDate .'\' AND \'' . $endDate . '\'' .
						  '     AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
	       				'        GROUP BY datalabel) ORDER BY datalabel;';
        }
        $sql_titles_result = $this->getGvm()->RR_DB_CONN->query( $sql_titles );
	    if($sql_titles_result == false) {
	      	printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
	        throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql_titles . "]");
	    }
	
	    $log->trace("Calling getCallResponseVolumeStatsForDateRange sql_titles [" . $sql_titles . "]");
	    
        // Build the data array
        $titles_results = array();
        while($row_titles = $sql_titles_result->fetch_object()) {
        	array_push($titles_results,$row_titles->datalabel);
            array_push($dynamicColumnTitles,$row_titles->datalabel);
        }
        $sql_titles_result->close();
	
        if($this->getGvm()->firehall->LDAP->ENABLED) {
        	create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);

			// Find all occourences of calls that are completed(10) or canceled(3).  
			// TRAINING and TESTONLY records are excluded
            $sql = 	'(SELECT MONTH(calltime) AS month, "'. $MAX_MONTHLY_LABEL .'" AS datalabel, count(*) AS count ' .
 	            	' FROM callouts WHERE status IN (3,10) AND calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
                	' GROUP BY month ORDER BY month)' .
                	' UNION (SELECT MONTH(responsetime) AS month, b.user_id AS datalabel, count(*) AS count ' .
                    ' FROM callouts_response a ' .
                    ' LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id' .
                    ' LEFT JOIN callouts c ON a.calloutid = c.id ' .
                    ' WHERE c.status IN (3,10) AND a.responsetime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
                    ' GROUP BY month, datalabel ORDER BY month, datalabel) ORDER BY month, datalabel;';
        }
        else {
           	$sql =	'(SELECT MONTH(calltime) AS month, "'. $MAX_MONTHLY_LABEL .'" AS datalabel, count(*) AS count ' .
					' FROM callouts WHERE status IN (3,10) AND calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
					' GROUP BY month ORDER BY month)' .
					'UNION (SELECT MONTH(responsetime) AS month, b.user_id AS datalabel, count(*) AS count ' .
					' FROM callouts_response a ' .
					' LEFT JOIN user_accounts b ON a.useracctid = b.id' .
					' LEFT JOIN callouts c ON a.calloutid = c.id ' .
					' WHERE c.status IN (3,10) AND a.responsetime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' AND calltype NOT IN (\'TRAINING\',\'TESTONLY\') ' .
					' GROUP BY month, datalabel ORDER BY month, datalabel) ORDER BY month, datalabel;';
       }
       $sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
       if($sql_result == false) {
       		printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
            throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
       }

       $log->trace("Calling getCallResponseVolumeStatsForDateRange sql [" . $sql . "]");
       
       // Build the data array
       $data_results = array();
       while($row = $sql_result->fetch_object()) {
       		$row_result = array($row->month,$row->datalabel,$row->count + 0);
         	array_push($data_results,$row_result);
       }
       $sql_result->close();

       // Ensure every month of the year exists in the results for each calltype
       for($index=1;$index <= 12; $index++) {
       		foreach($titles_results as $title) {
       			$found_index = false;
            	foreach($data_results as $data) {
            		$monthNumber = $data[0];
            		$labelName = $data[1];
            		if($index == $monthNumber && $title == $labelName) {
            			$found_index = true;
            			break;
            		}
            	}
            	if($found_index == false) {
            		$row_result = array($index,$title,0);
            		array_push($data_results,$row_result);
            	}
            }
        }
	
	    // Sort by month # then by calltype
	    usort($data_results, make_comparer(0,1));
	
      	// Replace month # with month name and build array for each unique calltype
       	$formatted_data = array();
	
       	$current_month_number = -1;
       	$current_month_array = null;
	
       	foreach ($data_results as $key => $row) {
       		$monthNumber = $row[0];
	        $monthName = date("F", mktime(0, 0, 0, $monthNumber, 10));
	
            $monthCount = $row[2];
	
           if($current_month_number != $monthNumber) {
           		if(isset($current_month_array)) {
           			array_push($formatted_data,$current_month_array);
	            }
	
	            $current_month_array = array();
	            array_push($current_month_array,$monthName);
	            array_push($current_month_array,$monthCount);
	
	            $current_month_number = $monthNumber;
	       }
	       else {
	       		array_push($current_month_array,$monthCount);
	       }
       	}

       	if(isset($current_month_array)) {
       		array_push($formatted_data,$current_month_array);
	    }
	
	    $log->trace("Call getCallResponseVolumeStatsForDateRange END");
	    
	    return $formatted_data;
	}
}
