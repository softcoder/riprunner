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
    </head>
    <body>
    
<?php

	function getHeaderRow($edit_mode) {
		$html_row = '<thead>';
		$html_row .= '<tr>';
		$html_row .= '<th scope="col">Record Id</th>' . PHP_EOL;
		$html_row .= '<th scope="col">Firehall Id</th>' . PHP_EOL;
		$html_row .= '<th scope="col">User Id</th>' . PHP_EOL;
		if($edit_mode == true) {
			$html_row .= '<th scope="col">Password</th>' . PHP_EOL;
			$html_row .= '<th scope="col">Re-type Password</th>' . PHP_EOL;
		}
		$html_row .= '<th scope="col">Mobile Phone</th>' . PHP_EOL;
		$html_row .= '<th scope="col">Admin Access</th>' . PHP_EOL;
		$html_row .= '<th scope="col" class="column_nowrap">Update Date/Time</th>' . PHP_EOL;
		$html_row .= '<th scope="col" colspan="2" align="Center">Modify Data</th>' . PHP_EOL;
		$html_row .= '<tr>' . PHP_EOL;
		$html_row .= '</thead>';
		
		return $html_row;
	}

	function getFooterRow($edit_mode, $self_edit) {
		$html_row = '';
		if($self_edit == false) {
			$html_row = '<tr>' . PHP_EOL;
			if($edit_mode == true) {
				$html_row .= '<td colspan="10">' . PHP_EOL;
			}
			else {
				$html_row .= '<td colspan="8">' . PHP_EOL;
			}
			$html_row .= '<input type="button" value="Add New" onclick="edit_user(this.form, -1);" />';
			$html_row .= '</td>' . PHP_EOL;
			$html_row .= '</tr>' . PHP_EOL;
		}
		
		return $html_row;
	}
	
	function addRow($row, $edit_row, $edit_mode, $self_edit) {
        	$html_row = "<tr>" . PHP_EOL;
        
        	$html_row .= "<td>" . PHP_EOL;
        	$html_row .= (isset($row) ? $row->id : '') . PHP_EOL;
        	$html_row .= "</td>" . PHP_EOL;
        
        	$html_row .= "<td>" . PHP_EOL;
        	if($edit_row == true && $self_edit == false) {
        		$html_row .= '<input id="edit_firehall_id" name="edit_firehall_id" type="input" value="' . (isset($row) ? $row->firehall_id : '') . '"/>' . PHP_EOL;
        	}
        	else {
        		$html_row .= (isset($row) ? $row->firehall_id : '') . PHP_EOL;
        	}
        	$html_row .= "</td>" . PHP_EOL;
        
        	$html_row .= "<td>" . PHP_EOL;
        	if($edit_row == true) {
        		$html_row .= '<input id="edit_user_id_name" name="edit_user_id_name" type="input" value="' . (isset($row) ? $row->user_id : '') . '"/>' . PHP_EOL;
        	}
        	else {
        		$html_row .= (isset($row) ? $row->user_id : '') . PHP_EOL;
        	}
        	$html_row .= "</td>" . PHP_EOL;

        	if($edit_row == true) {
	        	$html_row .= "<td>" . PHP_EOL;
        		$html_row .= '<input id="edit_user_password_1" name="edit_user_password_1" type="password" value=""/>' . PHP_EOL;
	        	$html_row .= "</td>" . PHP_EOL;
	        	
	        	$html_row .= "<td>" . PHP_EOL;
	        	$html_row .= '<input id="edit_user_password_2" name="edit_user_password_2" type="password" value=""/>' . PHP_EOL;
	        	$html_row .= "</td>" . PHP_EOL;
        	}
        	else if($edit_mode) {
        		$html_row .= "<td></td>" . PHP_EOL;
        		$html_row .= "<td></td>" . PHP_EOL;
        	}
        	 
        	$html_row .= "<td>" . PHP_EOL;
        	if($edit_row == true) {
        		$html_row .= '<input id="edit_mobile_phone" name="edit_mobile_phone" type="input" value="' . (isset($row) ? $row->mobile_phone : '') . '"/>' . PHP_EOL;
        	}
        	else {
        		$html_row .= (isset($row) ? $row->mobile_phone : '') . PHP_EOL;
        	}
        	$html_row .= "</td>" . PHP_EOL;

        	$html_row .= "<td>" . PHP_EOL;
        	if($edit_row == true && $self_edit == false) {
        		$html_row .= '<input id="edit_admin_access" name="edit_admin_access" type="checkbox" '.(isset($row) && userHasAcess($row->access,USER_ACCESS_ADMIN) ? 'checked="checked"' : '').' />' . PHP_EOL;
        	}
        	else {
        		$html_row .= (isset($row) && userHasAcessValueDB($row->access,USER_ACCESS_ADMIN) ? 'yes' : 'no') . PHP_EOL;
        	}
        	$html_row .= "</td>" . PHP_EOL;
        	 
        	$html_row .= '<td class="column_nowrap">' . PHP_EOL;
        	$html_row .= (isset($row) ? $row->updatetime : '') . PHP_EOL;
        	$html_row .= "</td>" . PHP_EOL;
        
        	if($edit_row == true) {
        		$html_row .= '<td colspan="2"><input type="button" value="Save" onclick="save_user(this.form, ' . (isset($row) ? $row->id : '-1') . ');" />' . PHP_EOL;
        		
        		$self_edit_query_param = '';
        		if($self_edit) {
        			$self_edit_query_param = '?se=true';
        		}
        		$html_row .= '<input type="button" value="Cancel" onclick="window.location.href = \'admin_users.php' . $self_edit_query_param . '\'; return false;" />' . PHP_EOL;
        		$html_row .= '</td>' . PHP_EOL;
        	}
        	else {
        		
        		$edit_colspan = '';
        		if($self_edit) {
        			$edit_colspan = ' colspan="2" ';
        		}
        		$html_row .= '<td' . $edit_colspan . '><input type="button" value="Edit" onclick="edit_user(this.form, ' . (isset($row) ? $row->id : '-1') . ');" /></td>' . PHP_EOL;
        		
        		if($self_edit == false) {
        			$html_row .= '<td><input type="button" value="Delete" onclick="return delete_user(this.form, ' . (isset($row) ? $row->id : '-1') . ',\'' . (isset($row) ? $row->user_id : '') . '\');" /></td>' . PHP_EOL;
        		}
        	}
        
        	$html_row .= "</tr>" . PHP_EOL;
        
        	return $html_row;
	}
	
	function handleEditAccount($force_edit) {
		$edit_user_id = null;
		$form_action = get_query_param('form_action');
		if($force_edit == true || 
			(isset($form_action) && $form_action == 'edit') ) {
			$edit_user_id = get_query_param('edit_user_id');
		}
		return $edit_user_id;
	}
	function isInsertAccount($force_edit, $edit_user_id) {
		$insert_new_account = false;
		$form_action = get_query_param('form_action');
		if($force_edit == true || 
			(isset($form_action) && $form_action == 'edit') ) {
			if(isset($edit_user_id) && $edit_user_id < 0) {
				$insert_new_account = true;
			}
		}
		return $insert_new_account;
	}
	function handleSaveAccount($db_connection, $self_edit) {
		$result = true;
		
		$form_action = get_query_param('form_action');
		if(isset($form_action) && $form_action == 'save' ) {

			if($self_edit) {
				$edit_user_id = $_SESSION['user_db_id'];
				$edit_firehall_id = $_SESSION['firehall_id'];
				$edit_admin_access = userHasAcess(USER_ACCESS_ADMIN);
			}
			else {
				$edit_user_id = get_query_param('edit_user_id');
				$edit_firehall_id = get_query_param('edit_firehall_id');
				$edit_admin_access = get_query_param('edit_admin_access');
			}
			$edit_user_id_name = get_query_param('edit_user_id_name');
			$edit_mobile_phone = get_query_param('edit_mobile_phone');
			 
			if(isset($edit_user_id)) {
				// PASSWORD
				$new_pwd = null;
				$edit_pwd1 = get_query_param('edit_user_password_1');
				$edit_pwd2 = get_query_param('edit_user_password_2');
				if(isset($edit_pwd1) && isset($edit_pwd2) && 
					(strlen($edit_pwd1) > 0 || strlen($edit_pwd2) > 0)) {
					
					if(strlen($edit_pwd1) >= 5 && $edit_pwd1 == $edit_pwd2) {
						$new_pwd = encryptPassword($edit_pwd1);
					}
					else {
						echo '<b><font color="red">Invalid password! Passwords must match and be at least 5 characters.</font></b>' .PHP_EOL;
						$result = false;
					}
				}
				if(isset($edit_firehall_id) == false || $edit_firehall_id == '') {
					echo '<b><font color="red">You must enter a Firehall Id</font></b>' .PHP_EOL;
					$result = false;
				}
				if(isset($edit_user_id_name) == false || $edit_user_id_name == '') {
					echo '<b><font color="red">You must enter a User Id</font></b>' .PHP_EOL;
					$result = false;
				}
				
				if($result == true) {
					// UPDATE
					if($edit_user_id >= 0) {
						$sql_pwd = '';
						if(isset($new_pwd)) {
							$sql_pwd = ', user_pwd = \'' . $db_connection->real_escape_string($new_pwd) . '\'';
						}
						
						$sql_user_access = '';
						if($self_edit == false) {
							if(isset($edit_admin_access) && $edit_admin_access == 'on') {
								//. ', access = access | ' . USER_ACCESS_ADMIN
								//echo "ENABLED edit_admin_access = [$edit_admin_access]" . PHP_EOL;
								$sql_user_access = ', access = access | ' . USER_ACCESS_ADMIN;
							}
							else {
								//echo "DISABLED edit_admin_access = [$edit_admin_access]" . PHP_EOL;
								$sql_user_access = ', access = access & ~' . USER_ACCESS_ADMIN;
							}
						}
						
						$sql = 'UPDATE user_accounts'
								. ' SET firehall_id = ' . $db_connection->real_escape_string( $edit_firehall_id )
								. ', user_id = \'' . $db_connection->real_escape_string( $edit_user_id_name ) . '\''
								. $sql_pwd
								. ', mobile_phone = \'' . $db_connection->real_escape_string( $edit_mobile_phone ) . '\''
								. $sql_user_access
								. ', updatetime = CURRENT_TIMESTAMP()'
								. ' WHERE id = ' . $db_connection->real_escape_string($edit_user_id) . ';';
						 
						$sql_update_result = $db_connection->query( $sql );
						 
						if($sql_update_result == false) {
							printf("Error: %s\n", mysqli_error($db_connection));
							throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
						}
						$edit_user_id = null;
					}
					// INSERT
					else if($self_edit == false) {
						$sql_pwd_fieldvalue = '';
						if(isset($new_pwd)) {
							$new_pwd_value = $db_connection->real_escape_string($new_pwd);
						}
						else {
							$new_pwd_value = '';
						}
						
						$sql_user_access = '';
						if(isset($edit_admin_access) && $edit_admin_access == 'on') {
							//echo "ENABLED edit_admin_access = [$edit_admin_access]" . PHP_EOL;
							$new_user_access = USER_ACCESS_ADMIN;
						}
						else {
							//echo "DISABLED edit_admin_access = [$edit_admin_access]" . PHP_EOL;
							$new_user_access = 0;
						}
						
						$sql = 'INSERT INTO user_accounts'
								. ' (firehall_id, user_id, mobile_phone, user_pwd, access)'
								. ' VALUES('
								. $db_connection->real_escape_string( $edit_firehall_id )
								. ', \'' . $db_connection->real_escape_string( $edit_user_id_name ) . '\''
								. ', \'' . $db_connection->real_escape_string( $edit_mobile_phone ) . '\''
								. ', \'' . $db_connection->real_escape_string($new_pwd_value) 		. '\'' 
								. ', '   . $new_user_access . ');';
			
						$sql_insert_result = $db_connection->query( $sql );
			
						//echo "SQL = [$sql]" . PHP_EOL;
						
						if($sql_insert_result == false) {
							printf("Error: %s\n", mysqli_error($db_connection));
							throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
						}
						$edit_user_id = null;
					}
				}
			}
		}
		return $result;
	}
	function handleDeleteAccount($db_connection, $self_edit) {
		$form_action = get_query_param('form_action');
		if(isset($form_action) && $form_action == 'delete' && $self_edit == false) {
			$edit_user_id = get_query_param('edit_user_id');
		
			if(isset($edit_user_id)) {
				// UPDATE
				if($edit_user_id >= 0) {
					$sql = 'DELETE FROM user_accounts WHERE id = ' .
							$db_connection->real_escape_string($edit_user_id) . ';';
		
					$sql_update_result = $db_connection->query( $sql );
		
					if($sql_update_result == false) {
						printf("Error: %s\n", mysqli_error($db_connection));
						throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
					}
					$edit_user_id = null;
				}
			}
		}
	}	
?>

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
        
        $self_edit = get_query_param('se');
        $self_edit = (isset($self_edit) && $self_edit);
                
        if (login_check($db_connection) && 
				(userHasAcess(USER_ACCESS_ADMIN) || $self_edit)) : ?>
            <p>Welcome <?php echo htmlentities($_SESSION['user_id']); ?>!</p>

            <?php echo '<input type="hidden" id="se" name="se" value="true"/>' . PHP_EOL; ?>
            
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
            
            <form action="admin_users.php" method="post" name="user_edit_form">
            <?php

            $insert_new_account = false;
            $edit_user_id = null;
            $form_action = get_query_param('form_action');
            
            // Handle CRUD operations
            $edit_user_id = handleEditAccount(false);
            $insert_new_account = isInsertAccount(false,$edit_user_id);
            $save_ok = handleSaveAccount($db_connection, $self_edit);
            if($save_ok == false) {
				$edit_user_id = handleEditAccount(true);
				$insert_new_account = isInsertAccount(true,$edit_user_id);
			}
			else {
            	handleDeleteAccount($db_connection, $self_edit);
            }
            //
            
            // Read from the database info about this callout
            $sql_where_clause = '';
            if($self_edit) {
            	$sql_where_clause = 'WHERE id=' . $_SESSION['user_db_id'];	
            }
            $sql = 'SELECT * FROM user_accounts ' . $sql_where_clause . ';';
            $sql_result = $db_connection->query( $sql );
            if($sql_result == false) {
            	printf("Error: %s\n", mysqli_error($db_connection));
            	throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
            }
            
            $edit_mode = isset($edit_user_id);
            
            echo '<center>'. PHP_EOL;
            $html_row = '<table id="box-table-a">' . PHP_EOL;
            $row_number = 1;
            while($row = $sql_result->fetch_object()) {
				if($row_number == 1) {
					$html_row .= getHeaderRow($edit_mode);
				}
				$edit_row = (isset($edit_user_id) && $edit_user_id == $row->id);
				$html_row .= addRow($row,$edit_row, $edit_mode, $self_edit);
				$row_number++;
            }
            $sql_result->close();
            
            if($insert_new_account == true && $self_edit == false) {
				$html_row .= addRow(null,true,true);
			}
			
            $html_row .= getFooterRow($edit_mode, $self_edit);
            $html_row .= "</table>" . PHP_EOL;
            
            echo $html_row;
            echo '</center>'. PHP_EOL;
        	?>
        	
            </form>
            
<script type="text/javascript">
   	function edit_user(form, user_id) {
	   addformhiddenfield(form, 'form_action', 'edit');
	   addformhiddenfield(form, 'edit_user_id', user_id);
	   <?php if($self_edit) echo "addformhiddenfield(form, 'se', 'true');" . PHP_EOL; ?>
	   form.submit();
   	}

   	function save_user(form, user_id) {
 	   addformhiddenfield(form, 'form_action', 'save');
 	   addformhiddenfield(form, 'edit_user_id', user_id);
 	   <?php if($self_edit) echo "addformhiddenfield(form, 'se', 'true');" . PHP_EOL; ?>
 	   form.submit();
    }

   	function delete_user(form, user_id, user_id_name) {
   		if(confirm('Confirm DELETE for user: ' + user_id_name + '?')) {
  	   		addformhiddenfield(form, 'form_action', 'delete');
  	   		addformhiddenfield(form, 'edit_user_id', user_id);
  	   		<?php if($self_edit) echo "addformhiddenfield(form, 'se', 'true');" . PHP_EOL; ?>
  	   		form.submit();
  	   		return true;
   		}
   		return false;
     }
    
   	var item = document.getElementById("edit_firehall_id");
	if(item) {
		item.focus();
	}
</script>
            
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
        </div>
    </body>
</html>