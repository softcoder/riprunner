<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );
if(defined('__RIPRUNNER_ROOT__') == false) define('__RIPRUNNER_ROOT__', dirname(dirname(dirname(__FILE__))));

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/plugins_loader.php';
require_once __RIPRUNNER_ROOT__ . '/object_factory.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/twilio-php/Services/Twilio.php';

$SMS_AUTO_CMD_BULK = 'ALL:';
$SMS_AUTO_CMD_RESPONDING = array('RE','RP','RESPOND');
$SMS_AUTO_CMD_COMPLETED = array('FI','CO','COMPLETE');
$SMS_AUTO_CMD_CANCELLED = array('X','Q','CANCEL');
$SMS_AUTO_CMD_HELP = array('?','H', 'LIST');

$SPECIAL_MOBILE_PREFIX = '+1';

class SmSCommandResult {
	private $sms_caller;
	private $firehall;
	private $useracctid;
	private $user_id;
	private $cmd;
	private $processed;
	private $sms_recipients;
	private $callout_list;

	public function __construct() {
	}

	public function getSmsCaller() {
		return $this->sms_caller;
	}
	public function setSmsCaller($value) {
		$this->sms_caller = $value;
	}

	public function getFirehall() {
		return $this->firehall;
	}
	public function setFirehall($value) {
		$this->firehall = $value;
	}

	public function getUserAccountId() {
		return $this->useracctid;
	}
	public function setUserAccountId($value) {
		$this->useracctid = $value;
	}

	public function getUserId() {
		return $this->user_id;
	}
	public function setUserId($value) {
		$this->user_id = $value;
	}

	public function getCmd() {
		return $this->cmd;
	}
	public function setCmd($value) {
		$this->cmd = $value;
	}

	public function getIsProcessed() {
		return $this->processed;
	}
	public function setIsProcessed($value) {
		$this->processed = $value;
	}

	public function getSmsRecipients() {
		return $this->sms_recipients;
	}
	public function setSmsRecipients($value) {
		$this->sms_recipients = $value;
	}

	public function getLiveCallouts() {
		return $this->callout_list;
	}
	public function setLiveCallouts($value) {
		$this->callout_list = $value;
	}
}
// Check if Twilio is calling us, if not 401
if(validateTwilioHost($FIREHALLS) == false) {
	header('HTTP/1.1 401 Unauthorized');
	exit;
}
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$result = handle_sms_command($FIREHALLS);
?>
<Response>
<?php if($result->getFirehall() != null && $result->getUserId() != null): ?>
    <Message>
Hello <?php echo $result->getUserId() ?> 
<?php if($result->getIsProcessed()): ?>
Processed SMS CMD: [<?php echo $result->getCmd() ?>]
Body [<?php echo isset($_REQUEST['Body']) ? $_REQUEST['Body'] : '' ?>]
<?php elseif(in_array(strtoupper($result->getCmd()),$SMS_AUTO_CMD_HELP)): ?>
Available commands:
Respond to live callout, any of: <?php echo implode(', ', $SMS_AUTO_CMD_RESPONDING) . PHP_EOL ?>
Complete current callout, any of: <?php echo implode(', ', $SMS_AUTO_CMD_COMPLETED) . PHP_EOL ?>
Cancel current callout, any of: <?php echo implode(', ', $SMS_AUTO_CMD_CANCELLED) . PHP_EOL ?>
Broadcast message to all: <?php echo $SMS_AUTO_CMD_BULK . PHP_EOL ?>
Show help, any of: <?php echo implode(', ', $SMS_AUTO_CMD_HELP) . PHP_EOL ?>
<?php else: ?>
Received SMS
From [<?php echo isset($_REQUEST['From']) ? $_REQUEST['From'] : '' ?>]
To [<?php echo isset($_REQUEST['To']) ? $_REQUEST['To'] : '' ?>]
MessageSid [<?php echo isset($_REQUEST['MessageSid']) ? $_REQUEST['MessageSid'] : '' ?>]
SmsSid [<?php echo isset($_REQUEST['SmsSid']) ? $_REQUEST['SmsSid'] : '' ?>]
NumMedia [<?php echo isset($_REQUEST['NumMedia']) ? $_REQUEST['NumMedia'] : '' ?>]
Body [<?php echo isset($_REQUEST['Body']) ? $_REQUEST['Body'] : '' ?>]
<?php if(in_array(strtoupper($result->getCmd()),$SMS_AUTO_CMD_RESPONDING) && count($result->getLiveCallouts()) <= 0): ?>
Cannot respond, no callouts active!
<?php elseif(in_array(strtoupper($result->getCmd()),$SMS_AUTO_CMD_COMPLETED) && count($result->getLiveCallouts()) <= 0): ?>
Cannot complete the callout, no callouts active!
<?php elseif(in_array(strtoupper($result->getCmd()),$SMS_AUTO_CMD_CANCELLED) && count($result->getLiveCallouts()) <= 0): ?>
Cannot cancel the callout, no callouts active!
<?php endif; ?>
<?php endif; ?>
    </Message>
<?php echo process_bulk_sms_command($result) ?>
<?php endif; ?>    
</Response>
<?php 

function startsWith($haystack, $needle){
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}
function find_sms_match($sms_user, $recipient_list_array) {
	global $SPECIAL_MOBILE_PREFIX;
	if (in_array($sms_user, $recipient_list_array)) {
		return $sms_user;
	}
//	echo "NOT found [$sms_user]";
	if (in_array($SPECIAL_MOBILE_PREFIX . $sms_user, $recipient_list_array)) {
		return $SPECIAL_MOBILE_PREFIX . $sms_user;
	}
//	echo "NOT found [$SPECIAL_MOBILE_PREFIX$sms_user]";
	if( startsWith($sms_user,$SPECIAL_MOBILE_PREFIX) &&
		in_array(substr($sms_user,strlen($SPECIAL_MOBILE_PREFIX)), $recipient_list_array)){
		return substr($sms_user,strlen($SPECIAL_MOBILE_PREFIX));
	}
//	echo "NOT found [" . substr($sms_user,strlen($SPECIAL_MOBILE_PREFIX)) ."]";
//	echo "Starts with: " . startsWith($sms_user,$SPECIAL_MOBILE_PREFIX) . " [" . $SPECIAL_MOBILE_PREFIX . "]";
	return null;
}

function getLiveCalloutModelList($FIREHALL, $db_connection) {
	global $log;
	// Check if there is an active callout (within last 48 hours) and if so send the details
	$sql = 'SELECT * FROM callouts' .
			' WHERE status NOT IN (3,10) AND ' .
			' TIMESTAMPDIFF(HOUR,`calltime`,CURRENT_TIMESTAMP()) <= ' .
			DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD .
			' ORDER BY id DESC LIMIT 5;';
	
	$sql_result = $db_connection->query( $sql );
	if($sql_result == false) {
		$log->error("Call checkForLiveCalloutModelList SQL error for sql [$sql] error: " . mysqli_error($db_connection));
		throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
	}
	$log->trace("Call checkForLiveCalloutModelList SQL success for sql [$sql] row count: " . $sql_result->num_rows);

	$callout_list = array();
	while($row = $sql_result->fetch_assoc()) {
	//if($row = $sql_result->fetch_object()) {
		//$calloutModel = new \riprunner\CalloutViewModel();
		//$this->getCalloutModel()->id = $row->id;
		//$this->getCalloutModel()->callkey = $row->call_key;
		$callout_list[] = $row;
	}
	$sql_result->close();
	return $callout_list;
}

function clean_mobile_number( $text )	{
	$code_entities_match   = array('$','%','^','&','_','{','}','|','"','<','>','?','[',']','\\',';',"'",'/','~','`','=',' ');
	$code_entities_replace = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '',  '', '', '', '', '', '', '');
	 
	$text = str_replace( $code_entities_match, $code_entities_replace, $text );
	return $text;
}

function handle_sms_command($FIREHALLS_LIST) {
	global $log;
	global $SMS_AUTO_CMD_RESPONDING;
	global $SMS_AUTO_CMD_COMPLETED;
	global $SMS_AUTO_CMD_CANCELLED;

	$result = new \riprunner\SmSCommandResult();
	$result->setIsProcessed(false);

	if(isset($_REQUEST['From'])) {
		$sms_user = clean_mobile_number($_REQUEST['From']);
		$result->setSmsCaller($sms_user);

		//echo 'Loop count: ' . sizeof($FIREHALLS_LIST) .PHP_EOL;
		# Loop through all Firehalls
		foreach ($FIREHALLS_LIST as &$FIREHALL) {
			if($FIREHALL->ENABLED && $FIREHALL->SMS->SMS_SIGNAL_ENABLED) {
				//echo "Twilio trigger checking firehall: [" . $FIREHALL->WEBSITE->FIREHALL_NAME . "] looking for $sms_user";
				$log->trace("Twilio trigger checking firehall: [" . $FIREHALL->WEBSITE->FIREHALL_NAME . "]");

				$db_connection = null;
				try {
					$db_connection = db_connect_firehall($FIREHALL);
					
					if($FIREHALL->LDAP->ENABLED) {
						$recipients = get_sms_recipients_ldap($FIREHALL,null);
						$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);
	
						$recipient_list = explode(';',$recipients);
						$recipient_list_array = $recipient_list;
					}
					else {
						$recipient_list_type = ($FIREHALL->SMS->SMS_RECIPIENTS_ARE_GROUP ?
								\riprunner\RecipientListType::GroupList : \riprunner\RecipientListType::MobileList);
						if($recipient_list_type == \riprunner\RecipientListType::GroupList) {
							$recipients_group = $FIREHALL->SMS->SMS_RECIPIENTS;
							$recipient_list_array = explode(';',$recipients_group);
						}
						else if($FIREHALL->SMS->SMS_RECIPIENTS_FROM_DB) {
							$recipient_list = getMobilePhoneListFromDB($FIREHALL,$db_connection);
							$recipient_list_array = $recipient_list;
						}
						else {
							$recipients = $FIREHALL->SMS->SMS_RECIPIENTS;
							$recipient_list = explode(';',$recipients);
							$recipient_list_array = $recipient_list;
						}
					}
	
					$matching_sms_user = find_sms_match($sms_user, $recipient_list_array);
					if ($matching_sms_user != null) {
						$result->setSmsCaller($matching_sms_user);
						$result->setSmsRecipients($recipient_list_array);
						
						//echo "Twilio trigger FOUND MATCH for [$matching_sms_user]";
						$result->setFirehall($FIREHALL);
						// Find matching user for mobile #
						if($FIREHALL->LDAP->ENABLED) {
							create_temp_users_table_for_ldap($FIREHALL, $db_connection);
							$sql = "SELECT id,user_id FROM ldap_user_accounts WHERE firehall_id = '" .
									$db_connection->real_escape_string( $FIREHALL->FIREHALL_ID ) . "'" .
									" AND mobile_phone = '" . $db_connection->real_escape_string( $matching_sms_user ) . "';";
						}
						else {
							$sql = "SELECT id,user_id FROM user_accounts WHERE firehall_id = '" .
									$db_connection->real_escape_string( $FIREHALL->FIREHALL_ID ) . "'" .
									" AND mobile_phone = '" . $db_connection->real_escape_string( $matching_sms_user ) . "';";
	
						}
						$sql_result = $db_connection->query( $sql );
						if($sql_result == false) {
							$log->error("Twilio userlist SQL error for sql [$sql] error: " . mysqli_error($db_connection));
							throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
						}
	
						//echo "Twilio got firehall_id [$FIREHALL->FIREHALL_ID] mobile [$matching_sms_user] got count: " . $sql_result->num_rows;
						$log->trace("Twilio got firehall_id [$FIREHALL->FIREHALL_ID] mobile [$matching_sms_user] got count: " . $sql_result->num_rows);
							
						if($row = $sql_result->fetch_object()) {
							$result->setUserAccountId($row->id);
							$result->setUserId($row->user_id);
						}
						$sql_result->close();
	
						//echo 'GOT HERE 1 ' . $result->getUserId();
						// Account is valid
						if($result->getUserId() != null) {
							// Now check which command the user wants to process
							$sms_cmd = isset($_REQUEST['Body']) ? $_REQUEST['Body'] : '';
							$result->setCmd($sms_cmd);
							//echo 'GOT HERE 2 ' . $result->getUserId() . $sms_cmd;
							
							if( in_array(strtoupper($sms_cmd),$SMS_AUTO_CMD_RESPONDING)) {
								$live_callout_list = getLiveCalloutModelList($FIREHALL, $db_connection);
								$result->setLiveCallouts($live_callout_list);
								
								if($live_callout_list != null && empty($live_callout_list) == false) {
									$most_current_callout = reset($live_callout_list);
									$site_root = getFirehallRootURLFromRequest(null,$FIREHALLS_LIST);
									$URL = $site_root . "cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) . 
									    "&cid=" . urlencode($most_current_callout['id']) .
									    "&uid=" . urlencode($result->getUserId()) . 
									    "&ckid=" . urlencode($most_current_callout['call_key']);
									
									$log->error("Calling URL for twilio Call Response [$URL]");
									$cmd_result = file_get_contents($URL);
									$log->error("Called URL returned [$cmd_result]");
										
									$result->setIsProcessed(true);
								}
							}
							else if( in_array(strtoupper($sms_cmd),$SMS_AUTO_CMD_COMPLETED)) {
								$live_callout_list = getLiveCalloutModelList($FIREHALL, $db_connection);
								$result->setLiveCallouts($live_callout_list);
									
								if($live_callout_list != null && empty($live_callout_list) == false) {
									$most_current_callout = reset($live_callout_list);
									$site_root = getFirehallRootURLFromRequest(null,$FIREHALLS_LIST);
									$URL = $site_root . "cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
											"&cid=" . urlencode($most_current_callout['id']) .
											"&uid=" . urlencode($result->getUserId()) .
											"&ckid=" . urlencode($most_current_callout['call_key']) .
											"&status=" . urlencode(\CalloutStatusType::Complete);
									
									$log->error("Calling URL for twilio Call Response [$URL]");
									$cmd_result = file_get_contents($URL);
									$log->error("Called URL returned [$cmd_result]");
												
									$result->setIsProcessed(true);
								}
							}
							else if( in_array(strtoupper($sms_cmd),$SMS_AUTO_CMD_CANCELLED)) {
								$live_callout_list = getLiveCalloutModelList($FIREHALL, $db_connection);
								$result->setLiveCallouts($live_callout_list);
									
								if($live_callout_list != null && empty($live_callout_list) == false) {
									$most_current_callout = reset($live_callout_list);
									$site_root = getFirehallRootURLFromRequest(null,$FIREHALLS_LIST);
									$URL = $site_root . "cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
									"&cid=" . urlencode($most_current_callout['id']) .
									"&uid=" . urlencode($result->getUserId()) .
									"&ckid=" . urlencode($most_current_callout['call_key']) .
									"&status=" . urlencode(\CalloutStatusType::Cancelled);
										
									$log->error("Calling URL for twilio Call Response [$URL]");
									$cmd_result = file_get_contents($URL);
									$log->error("Called URL returned [$cmd_result]");
									
									$result->setIsProcessed(true);
								}
							}
							break;
						}
						else {
							$log->error("Internal failure DB matching for sms user [$sms_user] matching name [$matching_sms_user].");
						}
					}
					else {
						$log->error("FAILED sms matching authentication for sms user [$sms_user].");
					}
				} 
				catch (Exception $ex) {
					db_disconnect( $db_connection );
					$db_connection = null;
					throw($ex);
				}
				db_disconnect( $db_connection );
			}
		}
	}
	return $result;
}
function process_bulk_sms_command($cmd_result) {
	global $SPECIAL_MOBILE_PREFIX;
	global $SMS_AUTO_CMD_BULK;
	$result = "";
	if (startsWith(strtoupper($cmd_result->getCmd()),$SMS_AUTO_CMD_BULK)) {
		$recipient_list = $cmd_result->getSmsRecipients();
		foreach ($recipient_list as &$sms_user) {
			$result .= "<Message to='$SPECIAL_MOBILE_PREFIX$sms_user'>Group SMS from " . $cmd_result->getUserId() . 
				": " . substr($cmd_result->getCmd(),strlen($SMS_AUTO_CMD_BULK)) . "</Message>";
		}
		return $result;
	}
	return $result;
}
function validateTwilioHost($FIREHALLS_LIST) {
	global $log;
	foreach ($FIREHALLS_LIST as &$FIREHALL) {
		if($FIREHALL->ENABLED && $FIREHALL->SMS->SMS_SIGNAL_ENABLED && 
				isset($FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN)) {
			// Load auth token
			$authToken = explode(":",$FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN);
			// You'll need to make sure the Twilio library is included
			$validator = new \Services_Twilio_RequestValidator($authToken[1]);
			//$url = $_SERVER["SCRIPT_URI"];
			//$site_root = getFirehallRootURLFromRequest(null,$FIREHALLS_LIST);
			$site_root = $FIREHALL->WEBSITE->WEBSITE_ROOT_URL;
			$url = $site_root . "plugins/sms-provider-hook/twilio-webhook.php";
				
			ksort($_POST);
			$vars = $_POST;
			$signature = isset($_SERVER["HTTP_X_TWILIO_SIGNATURE"]) ? $_SERVER["HTTP_X_TWILIO_SIGNATURE"] : null;

			$log->trace("About to validate twilio host url [$url] vars [" . implode(', ', $vars) . "] sig [$signature] auth [$authToken[1]]");
			$validate_result = $validator->validate($signature, $url, $vars);
			if ($validate_result) {
				// This request definitely came from Twilio
				return true;
			}

			$sms_user = isset($_REQUEST['From']) ? $_REQUEST['From'] : '';
			$log->error("Validate twilio host failed for sms user [$sms_user], returned [$validate_result] url [$url] vars [" . implode(', ', $vars) . "] sig [$signature] auth [$authToken[1]]");
		}
	}
	return false;
}
?>