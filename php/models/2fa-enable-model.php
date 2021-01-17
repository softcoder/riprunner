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
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;

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
		else if('otp_qrcode' === $name) {
			return $this->getOTPQRCode();
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
			array('hasError','otp_secret','otp_code','otp_qrcode', 'user_id','firehall_id', 'selfedit_mode')) === true) {
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

	public function getOTPQRCode() {
		global $log;
		
		$prov_url = $this->getOTPProvisionUrl();
		//$prov_url = urldecode($prov_url);
		// otpauth://totp/Rip%20Runner%20Firehall%200%3Amark.vejvoda?issuer=Rip%20Runner%20Firehall%200&secret=CHONUI4V5BOLF3YJA6GZAKNZI42HQ3N6TPNCAMQJWVMIW367XFMA
		// otpauth://totp/Rip_Runner_Firehall%200%3Amark.vejvoda?issuer=Rip_Runner_Firehall%200&secret=T2XDOZPS5FMSBHABFJ5JYYXVMJK4HV5ZQBF2IGJMYWBSAAJOQ2YQ
		// otpauth://totp/Rip_Runner_Firehall_0%3Amark.vejvoda?issuer=Rip_Runner_Firehall_0&secret=QSI6IQSUGCCJGBJ44QX3Z4KUV3SMCJFNISM3SYZZACMXXIAELM4A
		// otpauth://totp/Rip_Runner_Firehall_0:mark.vejvoda?issuer=Rip_Runner_Firehall_0&secret=55IEKDLBX7XOD4CQMIYZCIRAGQ46RWNYN3WFKUXKMGADTCWZHUEQ
		// otpauth://totp/Rip%20Runner%3Amark.vejvoda%40FHID0?issuer=Rip%20Runner&secret=D7KRJPIC65NH4S6JLK3TMKRECRU3G6VGVSYEWZYGVMJXPDQXAMTA
		if ($log != null) $log->warn("IN getOTPQRCode() provurl: [$prov_url]");

		$qrCodeUri = $this->otp->getQrCodeUri(
			"https://api.qrserver.com/v1/create-qr-code/?data=$prov_url&size=300x300&ecc=M",
			"$prov_url"
		);

		//$qrCode = new QrCode($prov_url);
		//$qrCode->setSize(300);
		//$qrCode->setWriterByName('png');
		//$qrCode->setEncoding('UTF-8');
		//$qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::LOW());
		//$qrCode->setValidateResult(true).
		//$qrCodeUri = $qrCode->writeDataUri();
		return $qrCodeUri;
	}

	private function getOTPProvisionUrl() {
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
