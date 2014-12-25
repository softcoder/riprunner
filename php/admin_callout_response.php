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
        <?php else : ?>
        <link rel="stylesheet" href="styles/main.css" />
        <link rel="stylesheet" href="styles/table-styles.css" />
        <?php endif; ?>
        
        <link rel="stylesheet" href="styles/freeze-header.css" />
        
        <script type="text/JavaScript" src="js/forms.js"></script>
        <script type="text/JavaScript" src="js/freeze-header.js"></script>
    </head>
    <body>

    <div class="container_center">
<?php

	$db_connection = null;
	if (isset($_SESSION['firehall_id'])) {
		$firehall_id = $_SESSION['firehall_id'];
		$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
		$db_connection = db_connect_firehall($FIREHALL);
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
		            <li><a href="admin_callouts.php">Callouts</a></li>
		          </ul>
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
		$callout_id = get_query_param('cid');
		
		if($FIREHALL->LDAP->ENABLED) {
			create_temp_users_table_for_ldap($FIREHALL, $db_connection);
			$sql = 'SELECT b.user_id,a.responsetime,a.latitude,a.longitude,a.status,a.updatetime,c.address FROM callouts_response a LEFT JOIN ldap_user_accounts b on a.useracctid = b.id LEFT JOIN callouts c on a.calloutid = c.id WHERE calloutid = ' . $callout_id . ';';
		}
		else {
			$sql = 'SELECT b.user_id,a.responsetime,a.latitude,a.longitude,a.status,a.updatetime,c.address FROM callouts_response a LEFT JOIN user_accounts b on a.useracctid = b.id LEFT JOIN callouts c on a.calloutid = c.id WHERE calloutid = ' . $callout_id . ';';
		}
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
					
        	<div class="inner_table">
        	<table id="box-table-a" style="margin-left:auto;margin-right:auto;text-align:left">			
		    <?php
		    if(isset($colNames)) {
				echo '<thead>';
				echo '<tr>';
				foreach($colNames as $colName) {
					// skip address field
					if($colName == "address") {
					}
					else {
						echo '<th scope="col">'.$colName.'</th>';
					}
				}
				echo '</tr>';
				echo '</thead>';

				//print the rows
				foreach($data as $row) {
					echo "<tr>";
				    foreach($colNames as $colName) {
						// skip address field
						if($colName == "address") {
						}
						else if($colName == "responsetime") {
							echo '<td class="column_nowrap">'.$row[$colName].'</td>';
						}
						else if($colName == "latitude" || $colName == "longitude") {
							if($row["latitude"] != 0.0 && $row["longitude"] != 0.0) {
								$callOrigin = urlencode($row["latitude"]) . ',' . urlencode($row["longitude"]);
								$callDest = getAddressForMapping($FIREHALL,$row["address"]);
									
								$mapUrl = '<a target="_blank" href="http://maps.google.com/maps?saddr='.$callOrigin.'&daddr=' . $callDest.' ('.$callDest.')">'.$row[$colName].'</a>' . PHP_EOL;
							}
							else {
								$mapUrl = $row[$colName] . PHP_EOL;
							}
							echo "<td>". $mapUrl ."</td>";
						}
						else if($colName == "status") {
							echo "<td>". getCallStatusDisplayText($row[$colName])."</td>";
						}
						else if($colName == "updatetime") {
							echo '<td class="column_nowrap">'.$row[$colName].'</td>';
						}
						else {
			    			echo "<td>".$row[$colName]."</td>";
			    		}
				    }
				    echo "</tr>";
				}
			}
			?>
			</table>
			</div>
					
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
        </div>
    </body>
</html>		