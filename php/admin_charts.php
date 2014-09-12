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
        
		function array2str($array, $pre = '', $pad = '', $sep = ', ') {
			$str = '';
			if(is_array($array)) {
				if(count($array)) {
					foreach ($array as $item_name => $item_value) {
						if(is_array($item_value)) {
							$str_val = array2str($item_value, $pre, $pad, $sep);
						}
						else {
							$str_val = $pre . '\'' . $item_name . '\'' . $sep . $item_value . $pad;
						}
						
						if(is_array($item_value)) {
							if(empty($str) == false) {
								$str .= $sep;
							}
						}
						$str .= $str_val;
					}
				}
			}
			else {
				$str .= $pre.$array.$pad;
			}
		
			return $str;
		}
                
        function getCallTypeStatsForDateRange($db_connection,$startDate,$endDate) {
			/*[
				['Mushrooms', 3],
				['Onions', 1],
				['Olives', 1],
				['Zucchini', 1],
				['Pepperoni', 2]
			]*/
				
			$jsOutput = '';
			
			// Read from the database info about this callout
			$sql = 'SELECT calltype, count(*) count FROM callouts WHERE calltime BETWEEN \'' . 
					$startDate .'\' AND \'' . $endDate . '\' GROUP BY calltype ORDER BY calltype;';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			
			$data_results = array();
			while($row = $sql_result->fetch_object()) {
				//$data[] = $row;
				$row_result = array();
				
				$callTypeDesc = convertCallOutTypeToText($row->calltype);
				$row_result[$row->calltype . ' - ' . $callTypeDesc] = $row->count;
				array_push($data_results,$row_result);
			}
			$sql_result->close();
			
			//$jsOutput = json_encode($data_results);
			$jsOutput = '[' . array2str($data_results,'[',']',',') . ']';
			return $jsOutput;
		}
        
		function getCallVolumeStatsForDateRange($db_connection,$startDate,$endDate) {
			/*[
			 ['Mushrooms', 3],
			['Onions', 1],
			['Olives', 1],
			['Zucchini', 1],
			['Pepperoni', 2]
			]*/
		
			$jsOutput = '';
				
			// Read from the database info about this callout
			$sql = 'SELECT MONTH(calltime) month, count(*) count FROM callouts WHERE calltime BETWEEN \'' .
					$startDate .'\' AND \'' . $endDate . '\' GROUP BY MONTH(calltime) ORDER BY MONTH(calltime);';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
				
			$data_results = array();			
			while($row = $sql_result->fetch_object()) {
				$row_result = array();
				$row_result[$row->month] = $row->count;
				array_push($data_results,$row_result);
			}
			$sql_result->close();

			for($index=1;$index <= 12; $index++) {
				$found_index = false;
				foreach($data_results as $data) {
					if(array_key_exists($index,$data)) {
						$found_index = true;
						break;
					}
				}
				if($found_index == false) {
					$row_result = array();
					$row_result[$index] = 0;
					array_push($data_results,$row_result);
				}
			}
			
			// Sort by month #
			$sorted_data = array();
			foreach ($data_results as $key => $row) {
				$sorted_data[$key] = key($row);
			}
			array_multisort($sorted_data, SORT_ASC, $data_results);
			
			$formatted_data = array();
			foreach ($data_results as $key => $row) {
				foreach ($row as $monthNumber => $monthCount) {
					$monthName = date("F", mktime(0, 0, 0, $monthNumber, 10));
					$row_result = array();
					$row_result[$monthName] = $monthCount;
					array_push($formatted_data,$row_result);
				}
			}
				
			//$jsOutput = json_encode($data_results);
			$jsOutput = '[' . array2str($formatted_data,'[',']',',') . ']';
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

		    	// Pie chart of call types for current month
		        // Set chart options
		        var options = {'title':'Call Types - Current Month',
		                       'width':400,
		                       'height':300};
		      
		        // Create the data table.
		        var data = new google.visualization.DataTable();
		        data.addColumn('string', 'Call Type');
		        data.addColumn('number', 'Call Count');

		        data.addRows(<?php 
		        $current_month_start = date('Y-m-01');
		        $current_month_end = date('Y-m-t');
		        echo getCallTypeStatsForDateRange($db_connection,$current_month_start,$current_month_end); 
		        ?>);
		
		        // Instantiate and draw our chart, passing in some options.
		        var chart = new google.visualization.PieChart(document.getElementById('chart_month_div'));
		        chart.draw(data, options);

		     	// Pie chart of call types for current year
		        var options_year = {'title':'Call Types - Current Year',
	                       'width':400,
	                       'height':300};
		        
		        // Create the data table.
		        var data_year = new google.visualization.DataTable();
		        data_year.addColumn('string', 'Call Type');
		        data_year.addColumn('number', 'Call Count');
		        data_year.addRows(<?php 
		        
		        $year_start = strtotime('first day of January', time());
		        $year_end = strtotime('last day of December', time());
		        		        
		        $current_year_start = date('Y-m-d',$year_start);
		        $current_year_end = date('Y-m-d',$year_end);
		        echo getCallTypeStatsForDateRange($db_connection,$current_year_start,$current_year_end);
		        ?>);

		        // Instantiate and draw our chart, passing in some options.
		        var chart_year = new google.visualization.PieChart(document.getElementById('chart_year_div'));
		        chart_year.draw(data_year, options_year);
		        
		        // Line chart of call volume for current year
		        var options_year_volume = {'title':'Total Call Volume - Current Year by Month',
									        'width':400,
									        'height':300};
		        
		        // Create the data table.
		        var data_year_volume = new google.visualization.DataTable();
		        data_year_volume.addColumn('string', 'Month');
		        data_year_volume.addColumn('number', 'Call Count');
		        data_year_volume.addRows(<?php
		        
		        $year_start = strtotime('first day of January', time());
		        $year_end = strtotime('last day of December', time());
		        
		        $current_year_start = date('Y-m-d',$year_start);
		        $current_year_end = date('Y-m-d',$year_end);
		        echo getCallVolumeStatsForDateRange($db_connection,$current_year_start,$current_year_end);
		        ?>);
		
		        // Instantiate and draw our chart, passing in some options.
		        var chart_year_volume = new google.visualization.LineChart(document.getElementById('chart_year_volume_div'));
		        chart_year_volume.draw(data_year_volume, options_year_volume);
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
		    </table>
    
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
        </div>
    </body>
</html>