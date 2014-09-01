<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );

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
        <link rel="stylesheet" href="styles/table-styles-mobile.css" />
        <!--   <link rel="stylesheet" href="styles/main.css" /> -->
        <?php else : ?>
        <link rel="stylesheet" href="styles/main.css" />
        <link rel="stylesheet" href="styles/table-styles.css" />
        <?php endif; ?>
        
        <link rel="stylesheet" href="styles/freeze-header.css" />
        
        
        <script type="text/JavaScript" src="js/forms.js"></script>
        <script type="text/JavaScript" src="js/freeze-header.js"></script>
        <script type="text/JavaScript" src="js/jquery-2.1.1.min.js"></script>
    </head>
    <body>

    <div class="container_center">
<?php

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
	
    if (login_check($db_connection) == true) : ?>
    	<p>Welcome <?php echo htmlentities($_SESSION['user_id']); ?>!</p>

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
    				    	
		<?php
	
		// Read from the database info about this callout
		$sql = 'SELECT * FROM callouts ORDER BY calltime DESC;';
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}
		
		$data = array();
		while($row = $sql_result->fetch_assoc())
		{
			$data[] = $row;
		}
		
		if(sizeof($data) == 0) {
			echo "<b>NO Data.</b>" . PHP_EOL;
		}
		else {
			$colNames = array_keys(reset($data));
		}
		?>
		
		<center>		
        <table id="box-table-a">
		<?php
	    if(isset($colNames)) {

			echo "<thead>";
			echo "<tr>";
			//print the header
			foreach($colNames as $colName) {
				if($colName == "address") {
					echo '<th scope="col" class="column_nowrap">'.$colName.'</th>';
				}
				else {
					echo '<th scope="col">'.$colName.'</th>';
				}
			}
			echo "</tr>";
			echo "</thead>";
			
			//print the rows
			foreach($data as $row) {
				echo "<tr>";
				$col_num = 0;
			    foreach($colNames as $colName) {
					//if($col_num == 0) {
					if($colName == "id") {
						echo '<td><a href="admin_callout_response.php?cid=' . $row[$colName] . '">'.$row[$colName].'</a></td>';
					}
					else if($colName == "calltime") {
						echo '<td class="column_nowrap">'. $row[$colName] .'</td>';
					}
					else if($colName == "address") {
						
						$callOrigin = urlencode($FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS);
						$callDest = getAddressForMapping($FIREHALL,$row[$colName]);
							
						$mapUrl = '<a target="_blank" href="http://maps.google.com/maps?saddr='.$callOrigin.'&daddr=' . $callDest.' ('.$row[$colName].')">'.$row[$colName].'</a>' . PHP_EOL;
						echo '<td class="column_nowrap">'. $mapUrl .'</td>';
					}
					else if($colName == "updatetime") {
						echo '<td class="column_nowrap">'. $row[$colName] .'</td>';
					}
					else if($colName == "status") {
						echo "<td>". getCallStatusDisplayText($row[$colName])."</td>";
					}
					else {
			    		echo "<td>".$row[$colName]."</td>";
			    	}
			    	$col_num++;
			    }
			    echo "</tr>";
			}
		}
		?>
		</table>
		</center>
		
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
      </div>
    </body>
</html>		