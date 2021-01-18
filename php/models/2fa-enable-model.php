<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

use \OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

// The model class handling variable requests dynamically
class TwoFAEnableViewModel extends BaseViewModel {
	
	private $otp = null;
	private $otp_secret = null;
	private $selfedit_mode = null;

	protected function getVarContainerName() { 
		return "twofaenablevm";
	}

	public function __get($name) {
		if('hasError' === $name) {
			return isset($_GET['error']);
		}
		else if('otp_secret' === $name) {
			return $this->getOTPSecret();
		}
		else if('otp_code' === $name) {
			return $this->getOTPCode();
		}
		else if('otp_provurl' === $name) {
			return $this->getOTPProvisionUrl();
		}
		else if('user_id' === $name) {
			return $this->getUserId();
		}
		else if('firehall_id' === $name) {
			return $this->getFirehallId();
		}
		else if('selfedit_mode' === $name) {
			return $this->getIsSelfEditMode();
		}

		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('hasError','otp_secret','otp_code', 'otp_provurl', 'user_id','firehall_id', 'selfedit_mode')) === true) {
			return true;
		}
		return parent::__isset($name);
	}

	public function setOTPSecret($secret) {
		global $log;
		if ($log != null) $log->trace("IN setOTPSecret secret: $secret");
		$this->otp_secret = $secret;
	}

	public function getOTPSecret() {
		global $log;

		$this->getOTPObject();
		$secret = $this->otp->getSecret();
		
		if ($log != null) $log->trace("IN getOTPSecret secret: $secret");

		return $secret;
	}

	public function getOTPCode() {
		return $this->getOTPObject()->getSecret();
	}

//	public function getOTPQRCode() {
//		global $log;

//		$prov_url = $this->getOTPProvisionUrl();
//		if ($log != null) $log->trace("IN getOTPQRCode() provurl: [$prov_url]");

		// $qrCodeUri = $this->otp->getQrCodeUri(
		// 	"https://api.qrserver.com/v1/create-qr-code/?data=$prov_url&size=300x300&ecc=M",
		// 	"$prov_url"
		// );

		//$qrCode = new QrCode($prov_url);
		//$qrCode->setSize(300);
		//$qrCode->setWriterByName('png');
		//$qrCode->setEncoding('UTF-8');
		//$qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::LOW());
		//$qrCode->setValidateResult(true).
		//$qrCodeUri = $qrCode->writeDataUri();
//		return $qrCodeUri;
//	}

	public function getOTPProvisionUrl() {
		return $this->getOTPObject()->getProvisioningUri(); // Will return otpauth://totp/alice%40google.com?secret=JBSWY3DPEHPK3PXP
	}

	private function getOTPObject() {
        if ($this->otp === null) {
			if (null === $this->otp_secret) {
				$this->otp_secret = Base32::encodeUpper(random_bytes(32));
				$this->otp_secret = trim(mb_strtoupper($this->otp_secret), '=');
			}

			$this->otp = TOTP::create($this->otp_secret);
			// Note: You must set label before generating the QR code
			$this->otp->setLabel($this->getUserId().'@FHID'.$this->getFirehallId());
			$this->otp->setIssuer('Rip Runner');
		}
		return $this->otp;
	}
	
	private function getUserId() {
		return get_query_param('edit_user_id');
	}

	private function getFirehallId() {
		return get_query_param('fhid');
	}
	
	private function getIsSelfEditMode() {
		if($this->selfedit_mode == null) {
			$this->selfedit_mode = get_query_param('se');
			$this->selfedit_mode = (isset($this->selfedit_mode) === true && $this->selfedit_mode != null && $this->selfedit_mode == true);
		}
		return $this->selfedit_mode;
	}

}
