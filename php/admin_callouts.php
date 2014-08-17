<?php
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
ini_set('display_errors', 'On');
error_reporting(E_ALL);
 
sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Protected Page</title>
        <link rel="stylesheet" href="styles/main.css" />
        <script type="text/JavaScript" src="js/forms.js"></script>
        <style type="text/css">
		.wrap {
		    width: 100%; 
		}
		
		.wrap table {
		    width: 100%;
		    table-layout: fixed;
		}
		
		table tr td {
		    padding: 5px;
		    border: 1px solid #eee;
		    width: 100%;
		    word-wrap: break-word;
		}
		
		table.head tr td {
		    background: #eee;
		}
		
		.inner_table {
		    height: 500px;
		    overflow-y: auto;
		}		
        </style>
    </head>
    <body>

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
		
		<div class="wrap">
			<table border="1" class="head">
				<tr>
				<?php
				if(isset($colNames)) {
					//print the header
					foreach($colNames as $colName)
					{
						echo "<td>$colName</td>";
					}
				}
				?>
			 	</tr>
			</table>
			
			<div class="inner_table">
	        	<table>			
			    <?php
			    if(isset($colNames)) {
					//print the rows
					foreach($data as $row) {
						echo "<tr>";
						$col_num = 0;
					    foreach($colNames as $colName) {
							if($col_num == 0) {
								echo '<td><a href="admin_callout_response.php?cid=' . $row[$colName] . '">'.$row[$colName].'</a></td>';
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
			</div>
		</div>
		
        <p>Return to <a href="admin_index.php">main page</a><br />
        Return to <a href="logout.php">login page</a></p>
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
    </body>
</html>		