<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
	die( 'This file must not be invoked directly.' );
}
require_once 'config_constants.php';
try {
	if (!file_exists('config.php' )) {
	  throw new Exception ('Config script does not exist!');
	  }
	else {
		require_once 'config.php';
	}
}
catch(Exception $e) {    
  echo '<!DOCTYPE html>'.PHP_EOL.
       '<html>'.PHP_EOL.
       '<head>'.PHP_EOL.
       '<meta charset="UTF-8">'.PHP_EOL.
       '<title>Installation Page for: '.PRODUCT_NAME.'</title>'.PHP_EOL.
       '<link rel="stylesheet" href="styles/main.css" />'.PHP_EOL.
       '</head>'.PHP_EOL.
       '<body>'.PHP_EOL.
       '<p style="font-size:40px; color: white">'.
	   PRODUCT_NAME.' v'.CURRENT_VERSION.' - '.PRODUCT_URL.'<br>'.
	   '<hr></p>'.PHP_EOL.
       '<p style="font-size:35px; color: red">'.PHP_EOL.
       'Error detected, message : ' . $e->getMessage().', '.'Code : ' . $e->getCode().
       '<br><span style="font-size:35px; color: yellow">Please create a config.php script.</span>'.PHP_EOL.
       '</p><hr>'.PHP_EOL.
       '</body>'.PHP_EOL.
       '</html>';
  return;
}
	
require_once 'db/db_connection.php';
require_once 'db/sql_statement.php';
require_once 'authentication/authentication.php';
require_once 'functions.php';

function install($FIREHALL, &$db_connection) {
    $sql_statement = new \riprunner\SqlStatement($db_connection);
    $db_exist = $sql_statement->db_exists($FIREHALL->DB->DATABASE, null);
    $db_table_exist = $sql_statement->db_exists($FIREHALL->DB->DATABASE, 'user_accounts');
    
	if($db_exist === false) {
	    $sql = $sql_statement->getSqlStatement('database_create');
	    $dbname = $FIREHALL->DB->DATABASE;
	    $sql = preg_replace_callback('(:db)', function ($m) use ($dbname) { $m; return $dbname; }, $sql);
		$db_connection->query($sql);
		
		echo '<p style="font-size:35px; color: lime"><b>Successfully created database [' . $FIREHALL->DB->DATABASE . ']</b></p><br />' . PHP_EOL;
	}
	
	\riprunner\DbConnection::disconnect_db( $db_connection );
	$db_connection = null;
	
	// Connect to the database
	$db = new \riprunner\DbConnection($FIREHALL);
	$db_connection = $db->getConnection();
	
	if($db_table_exist === false) {
	    $sql_statement = new \riprunner\SqlStatement($db_connection);
	    $schema_results = $sql_statement->installSchema();
		echo '<p style="font-size:35px; color: lime"><b>SCHEMA Import Information, Success: ' . $schema_results["success"] . ' Total: ' . $schema_results["total"] . '</b></p><br />' . PHP_EOL;

		$random_password = uniqid('', true);
		$new_pwd = \riprunner\Authentication::encryptPassword($random_password);
		
		$sql = $sql_statement->getSqlStatement('admin_user_create');
		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':fhid', $FIREHALL->FIREHALL_ID);
		$qry_bind->bindParam(':pwd', $new_pwd);
		$qry_bind->execute();
		
		echo '<b>A default admin account has been created, with the following information:<br />'.
		     'Firehall Id: <font color="red">'.$FIREHALL->FIREHALL_ID.'</font><br />'.
		     'User id: <font color="red">admin</font><br />'. 
		     'Password: <font color="red">'.$random_password . '</font></b><br />' . PHP_EOL;
		echo '<b><a href="login">Login Page</a></b>' . PHP_EOL;
	}
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Installation Page for: <?php echo PRODUCT_NAME ?></title>
        <link rel="stylesheet" href="styles/main.css" />
    </head>
    <body>
        <?php 
        $firehall_id = get_query_param('fhid');
        $db_connection = null;
        if (isset($firehall_id) === true) {
			try {
				$form_action = get_query_param('form_action');
				$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
				if($FIREHALL !== null) {
					if($form_action === "install") {
						$db = new \riprunner\DbConnection($FIREHALL, true);
						$db_connection = $db->getConnection();
						//die('Test Master connection!');
					}
					else {
						$db = new \riprunner\DbConnection($FIREHALL);
						$db_connection = $db->getConnection();
					}
				}
			}	
			catch(Exception $e) {    
			  echo '<!DOCTYPE html>'.PHP_EOL.
				   '<html>'.PHP_EOL.
				   '<head>'.PHP_EOL.
				   '<meta charset="UTF-8">'.PHP_EOL.
				   '<title>Installation Page for: '.PRODUCT_NAME.'</title>'.PHP_EOL.
				   '<link rel="stylesheet" href="styles/main.css" />'.PHP_EOL.
				   '</head>'.PHP_EOL.
				   '<body>'.PHP_EOL.
				   '<p style="font-size:40px; color: white">'.
				   PRODUCT_NAME.' v'.CURRENT_VERSION.' - '.PRODUCT_URL.'<br>'.
				   '<hr></p>'.PHP_EOL.
				   '<p style="font-size:35px; color: red">'.PHP_EOL.
				   'Error detected, message : ' . $e->getMessage().', '.'Code : ' . $e->getCode().
				   '<br><span style="font-size:35px; color: yellow">Please edit your config.php script and correct any errors.</span>'.PHP_EOL.
				   '</p><hr>'.PHP_EOL.
				   '</body>'.PHP_EOL.
				   '</html>';
			  return;
			}
        }
        else {
			$html_select = '<select style="font-size:35px" id="fhid" name="fhid">' . PHP_EOL;
			foreach($FIREHALLS as $FIREHALL) {
				$html_select .= '<option value="'.$FIREHALL->FIREHALL_ID.'">'
							 . $FIREHALL->WEBSITE->FIREHALL_NAME 
							 . ' - ' . $FIREHALL->FIREHALL_ID
							 . '</option>' . PHP_EOL;
			}
			$html_select .= '</select>' . PHP_EOL;
		}
        
		if (isset($firehall_id) === true) : ?>
			<div style="font-size:35px; color: yellow">
            <p>Checking to see if the Firehall is already installed:</br>
              <span style="font-size:35px; color: white"><?php echo $FIREHALL->WEBSITE->FIREHALL_NAME; ?></span>
            </p>
            <?php
            if($form_action === 'install') {
                $sql_statement = new \riprunner\SqlStatement($db_connection);
                $db_exist = $sql_statement->db_exists($FIREHALL->DB->DATABASE, 'user_accounts');
				if($db_exist === true) {
					echo '<p style="font-size:35px; color: red">The Firehall already exists.</p><hr>' . PHP_EOL;
					echo '<b><a href="install.php">Back to Install Page</a></b><br />' . PHP_EOL;
					echo '<b><a href="login">Login Page</a></b><br />' . PHP_EOL;
				}
				else {
					echo '<p style="font-size:35px; color: lime">The Firehall DOES NOT exist, starting installation...</p>' . PHP_EOL;
					install($FIREHALL, $db_connection);
				}
			}
            ?>
			</div>
        <?php else : ?>
		<center>
        <form action="install.php" method="post" name="install_form">
            <p style="font-size:40px; color: white">
                <?php echo PRODUCT_NAME.' v'.CURRENT_VERSION.' - '.PRODUCT_URL; ?><br>
                <hr>
            </p>
            <p style="font-size:35px; color: yellow">
                Please select the firehall config to check if installation is required.<br>
                <?php echo $html_select; ?>
            </p>
			<input style="font-size:35px;background-color:lime;width: 200px" type="submit" value="Install"/>
			<input type="hidden" id="form_action" name="form_action" value="install"/>
        </form>
		</center>
        <?php endif; ?>
<?php
        if($db_connection !== null) {
        	\riprunner\DbConnection::disconnect_db( $db_connection );
        }
?>
    </body>
</html>
