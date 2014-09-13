<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'firehall_parsing.php' );

// These lines are mandatory.
require_once 'Mobile_Detect.php';
$detect = new Mobile_Detect;

ini_set('display_errors', 'On');
error_reporting(E_ALL);
 
sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Protected Page</title>
        <?php if ($detect->isMobile()) : ?>
        <link rel="stylesheet" href="styles/mobile.css" />
        <?php else : ?>
        <link rel="stylesheet" href="styles/main.css" />
        <?php endif; ?>
        
        <script type="text/JavaScript" src="js/jquery-2.1.1.min.js"></script>
        <!--Load the AJAX API-->
    	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    	
    	<script type="text/javascript">
		function addJSONDataToChartData(json_data, chart_data) {
			var table_data = new Array();
	        for (var data_item in json_data) {
	        	var row_data = json_data[data_item];

	        	var row_item = new Array();
	        	if( Object.prototype.toString.call( row_data ) === '[object Array]' ) {
	        		row_item = row_data;
	        	}
	        	else {
		        	for(var propertyName in row_data) {
		        		name = propertyName;
			        	val = row_data[propertyName];
			        	row_item.push(name);
			        	row_item.push(val);
		        	}
	        	}
	        		        	
	        	table_data.push(row_item);
	        }
	        chart_data.addRows(table_data);
		}
    	</script>
    </head>
    <body>
    	<div class="container_center">
    			
        <?php 
        //$_SESSION['firehall_id'] = 0;
        $db_connection = null;
        if (isset($_SESSION['firehall_id'])) {
        	$firehall_id = $_SESSION['firehall_id'];
        	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
        	if($FIREHALL != null) {
        		$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
        				$FIREHALL->MYSQL->MYSQL_USER,
        				$FIREHALL->MYSQL->MYSQL_PASSWORD,
        				$FIREHALL->MYSQL->MYSQL_DATABASE);
        	}
        }
                
        function getCallTypeStatsForDateRange($db_connection,$startDate,$endDate) {
			$jsOutput = '';
			
			// Read from the database
			$sql = 'SELECT calltype, count(*) count FROM callouts WHERE calltime BETWEEN \'' . 
					$startDate .'\' AND \'' . $endDate . '\' GROUP BY calltype ORDER BY calltype;';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			
			// Build the data array
			$data_results = array();
			while($row = $sql_result->fetch_object()) {
				$row_result = array();
				
				$callTypeDesc = convertCallOutTypeToText($row->calltype);
				$row_result[$row->calltype . ' - ' . $callTypeDesc] = $row->count + 0;
				array_push($data_results,$row_result);
			}
			$sql_result->close();
			
			// Convert the data array to JSON
			$jsOutput = json_encode($data_results);
			return $jsOutput;
		}
        
		function getCallVolumeStatsForDateRange($db_connection,$startDate,$endDate, 
				&$dynamicColumnTitles) {
			$jsOutput = '';
				
			/*
			(SELECT "ALL" as datalabel)
			UNION
			(SELECT calltype as datalabel
					FROM callouts WHERE calltime BETWEEN '2014-01-01' AND '2014-12-31'
					GROUP BY datalabel) ORDER BY datalabel;
			*/
			$sql_titles = '(SELECT "ALL" as datalabel)' .
			        ' UNION (SELECT calltype as datalabel ' .
					'        FROM callouts WHERE calltime BETWEEN \'' . 
							 $startDate .'\' AND \'' . $endDate . '\'' .
					'        GROUP BY datalabel) ORDER BY datalabel;';
			$sql_titles_result = $db_connection->query( $sql_titles );
			if($sql_titles_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_titles . "]");
			}
			
			// Build the data array
			$titles_results = array();
			while($row_titles = $sql_titles_result->fetch_object()) {
				$callTypeDesc = $row_titles->datalabel;
				if($callTypeDesc != 'ALL') {
					$callTypeDesc = $callTypeDesc . ' - ' . convertCallOutTypeToText($row_titles->datalabel);
				}
				array_push($titles_results,$callTypeDesc);
				array_push($dynamicColumnTitles,$callTypeDesc);
			}
			$sql_titles_result->close();

			// Read from the database
			/*
			(SELECT MONTH(calltime) as month, "ALL" as datalabel, count(*) as count
			FROM callouts WHERE calltime BETWEEN '2014-01-01' AND '2014-12-31'
			GROUP BY month, datalabel ORDER BY month)
			UNION
			(SELECT MONTH(calltime) as month, calltype as datalabel, count(*) as count
					FROM callouts WHERE calltime BETWEEN '2014-01-01' AND '2014-12-31'
					GROUP BY datalabel, month ORDER BY month) ORDER BY month, datalabel;			
			*/
			$sql = '(SELECT MONTH(calltime) AS month, "ALL" AS datalabel, count(*) AS count ' .
					' FROM callouts WHERE calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' GROUP BY month ORDER BY month)' .
					'UNION (SELECT MONTH(calltime) AS month, calltype AS datalabel, count(*) AS count ' .
					' FROM callouts WHERE calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' GROUP BY datalabel, month ORDER BY month) ORDER BY month, datalabel;';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
				
			// Build the data array
			$data_results = array();			
			while($row = $sql_result->fetch_object()) {
				$callTypeDesc = $row->datalabel;
				if($callTypeDesc != 'ALL') {
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
				
				$datalabel = $row[1];
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
				
			// Convert the data array to JSON
			$jsOutput = json_encode($formatted_data);
			
			return $jsOutput;
		}

		function getCallResponseVolumeStatsForDateRange($db_connection,$startDate,$endDate,
				&$dynamicColumnTitles) {
			$jsOutput = '';
		
			/*
			(SELECT "ALL" as user_id)
			 UNION (SELECT b.user_id 
			        FROM callouts_response a
			        LEFT JOIN user_accounts b ON a.useracctid = b.id 
			        WHERE responsetime BETWEEN '2014-01-01' AND '2014-12-31'
			        GROUP BY user_id) ORDER BY user_id;			*/
			$sql_titles = '(SELECT "ALL" as datalabel)' .
					' UNION (SELECT b.user_id AS datalabel ' .
					'        FROM callouts_response a' .
					'        LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
					'        WHERE responsetime BETWEEN \'' .
					         $startDate .'\' AND \'' . $endDate . '\'' .
					'        GROUP BY datalabel) ORDER BY datalabel;';
			$sql_titles_result = $db_connection->query( $sql_titles );
			if($sql_titles_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_titles . "]");
			}
				
			// Build the data array
			$titles_results = array();
			while($row_titles = $sql_titles_result->fetch_object()) {
				array_push($titles_results,$row_titles->datalabel);
				array_push($dynamicColumnTitles,$row_titles->datalabel);
			}
			$sql_titles_result->close();
		
			// Read from the database
			/*
			(SELECT MONTH(calltime) AS month, 'ALL' AS datalabel, count(*) AS count
			FROM callouts 
			WHERE calltime BETWEEN '2014-01-01' AND '2014-12-31'
			GROUP BY month)
			UNION
			(SELECT MONTH(a.responsetime) AS month, b.user_id AS datalabel, count(*) AS count
			FROM callouts_response a LEFT JOIN user_accounts b ON a.useracctid = b.id
			WHERE responsetime BETWEEN '2014-01-01' AND '2014-12-31'
			GROUP BY month,datalabel); 
			*/
			$sql = '(SELECT MONTH(calltime) AS month, "ALL" AS datalabel, count(*) AS count ' .
					' FROM callouts WHERE calltime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' GROUP BY month ORDER BY month)' .
					'UNION (SELECT MONTH(responsetime) AS month, b.user_id AS datalabel, count(*) AS count ' .
					' FROM callouts_response a LEFT JOIN user_accounts b ON a.useracctid = b.id' .
					' WHERE responsetime BETWEEN \'' . $startDate .'\' AND \'' . $endDate . '\'' .
					' GROUP BY month, datalabel ORDER BY month, datalabel) ORDER BY month, datalabel;';
			$sql_result = $db_connection->query( $sql );
					if($sql_result == false) {
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
		
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
				
				$datalabel = $row[1];
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
			
			// Convert the data array to JSON
			$jsOutput = json_encode($formatted_data);
				
			return $jsOutput;
		}
		
        if (login_check($db_connection) == true) : ?>
            <p>Welcome <?php echo htmlentities($_SESSION['user_id']); ?>!</p>
            
            <?php checkForLiveCallout($FIREHALL,$db_connection); ?>
		
			<div class="menudiv_wrapper">
			  <nav class="vertical">
			    <ul>
			      <li>
			        <label for="main_page">Return to ..</label>
			        <input type="radio" name="verticalMenu" id="main_page" />
			        <div>
			          <ul>
			            <li><a href="admin_index.php">Main Menu</a></li>
			          </ul>
			        </div>
			      </li>
			      <li>
			        <label for="logout">Exit</label>
			        <input type="radio" name="verticalMenu" id="logout" />
			        <div>
			          <ul>
			            <li><a href="logout.php">Logout</a></li>
			          </ul>
			        </div>
			      </li>
			    </ul>
			  </nav>
			</div>
            
			<script type="text/javascript">
		
		      // Load the Visualization API and the piechart package.
		      google.load('visualization', '1.0', {'packages':['corechart']});
		      // Set a callback to run when the Google Visualization API is loaded.
		      google.setOnLoadCallback(drawChart);
		
		      // Callback that creates and populates a data table,
		      // instantiates the pie chart, passes in the data and
		      // draws it.
		      function drawChart() {

		    	// ------------------------------------------
		    	// Pie chart of call types for current month
		        // Set chart options
		        var options = {'title':'Call Types - Current Month',
		                       'width':400,
		                       'height':300};
		      
		        // Create the data table.
		        var data = new google.visualization.DataTable();
		        data.addColumn('string', 'Call Type');
		        data.addColumn('number', 'Call Count');

		        var json_data_month = jQuery.parseJSON('<?php 
		        $current_month_start = date('Y-m-01');
		        $current_month_end = date('Y-m-t');
		        echo getCallTypeStatsForDateRange($db_connection,$current_month_start,$current_month_end); 
		        ?>');

		        addJSONDataToChartData(json_data_month, data);
		        
		        // Instantiate and draw our chart, passing in some options.
		        var chart = new google.visualization.PieChart(document.getElementById('chart_month_div'));
		        chart.draw(data, options);

		     	// ------------------------------------------
		     	// Pie chart of call types for current year
		        var options_year = {'title':'Call Types - Current Year',
				                       'width':400,
				                       'height':300};
		        
		        // Create the data table.
		        var data_year = new google.visualization.DataTable();
		        data_year.addColumn('string', 'Call Type');
		        data_year.addColumn('number', 'Call Count');
		        var json_data_year = jQuery.parseJSON('<?php 
		        
		        $year_start = strtotime('first day of January', time());
		        $year_end = strtotime('last day of December', time());
		        		        
		        $current_year_start = date('Y-m-d',$year_start);
		        $current_year_end = date('Y-m-d',$year_end);
		        echo getCallTypeStatsForDateRange($db_connection,$current_year_start,$current_year_end);
		        ?>');

		        addJSONDataToChartData(json_data_year, data_year);
		        
		        // Instantiate and draw our chart, passing in some options.
		        var chart_year = new google.visualization.PieChart(document.getElementById('chart_year_div'));
		        chart_year.draw(data_year, options_year);

		        // ------------------------------------------
		        // Line chart of call volume for current year
		        var options_year_volume = {'title':'Total Call Volume - Current Year by Month',
		        		                    'curveType': 'function',
		        		                    'explorer' : {},
									        'width':802,
									        'height':300};
		        
		        // Create the data table.
		        var data_year_volume = new google.visualization.DataTable();
		        data_year_volume.addColumn('string', 'Month');
		        //data_year_volume.addColumn('string', 'Call Type');
		        //data_year_volume.addColumn('number', 'Call Count');

		        var json_data_year_volume = jQuery.parseJSON('<?php
		        
		        $year_start = strtotime('first day of January', time());
		        $year_end = strtotime('last day of December', time());
		        
		        $current_year_start = date('Y-m-d',$year_start);
		        $current_year_end = date('Y-m-d',$year_end);
		        $dynamicColumnTitles = array();
		        echo getCallVolumeStatsForDateRange($db_connection,
												$current_year_start,
												$current_year_end,
												$dynamicColumnTitles);
		        ?>');
		        <?php 
		        foreach($dynamicColumnTitles as $title) {
		        	echo "data_year_volume.addColumn('number', '" . $title ."');" . PHP_EOL;
		        } 
		        ?>
		        
		        //debugger;
		        addJSONDataToChartData(json_data_year_volume, data_year_volume);
		        
		        // Instantiate and draw our chart, passing in some options.
		        var chart_year_volume = new google.visualization.LineChart(document.getElementById('chart_year_volume_div'));
		        chart_year_volume.draw(data_year_volume, options_year_volume);

		        // ------------------------------------------
		        // Line chart of call response volume for current year
		        var options_year_response_volume = {'title':'Total Call Response Volume - Current Year by Month',
		        		                    'curveType': 'function',
		        		                    'explorer' : {},
									        'width':802,
									        'height':300};
		        
		        // Create the data table.
		        var data_year_response_volume = new google.visualization.DataTable();
		        data_year_response_volume.addColumn('string', 'Month');

		        var json_data_year_response_volume = jQuery.parseJSON('<?php
		        
		        $year_start = strtotime('first day of January', time());
		        $year_end = strtotime('last day of December', time());
		        
		        $current_year_start = date('Y-m-d',$year_start);
		        $current_year_end = date('Y-m-d',$year_end);
		        $dynamicColumnTitles_response = array();
		        echo getCallResponseVolumeStatsForDateRange($db_connection,
												$current_year_start,
												$current_year_end,
												$dynamicColumnTitles_response);
		        ?>');
		        <?php 
		        foreach($dynamicColumnTitles_response as $title) {
		        	echo "data_year_response_volume.addColumn('number', '" . $title ."');" . PHP_EOL;
		        } 
		        ?>
		        
		        //debugger;
		        addJSONDataToChartData(json_data_year_response_volume, data_year_response_volume);
		        
		        // Instantiate and draw our chart, passing in some options.
		        var chart_year_response_volume = new google.visualization.LineChart(document.getElementById('chart_year_response_volume_div'));
		        chart_year_response_volume.draw(data_year_response_volume, options_year_response_volume);
		      }
		    </script>
			
			<!--Div that will hold the pie chart-->
			<table class="center">
				<tr>
				<td>
		    	<div id="chart_month_div"></div>
		    	</td>
		    	<td>
		    	<div id="chart_year_div"></div>
		    	</td>
		    	</tr>
		    	<tr>
		    	<td colspan="2">
		    	<div id="chart_year_volume_div"></div>
		    	</td>
		    	</tr>
		    	<tr>
		    	<td colspan="2">
		    	<div id="chart_year_response_volume_div"></div>
		    	</td>
		    	</tr>
		    </table>
    
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
        </div>
    </body>
</html>