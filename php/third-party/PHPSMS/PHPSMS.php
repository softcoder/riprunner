<?php

namespace PHPSMS;

require_once 'Providers.php';
  
class PHPSMS {
    private $result = '';
    /**
     * Main constructor for PHPSMS
     * @param string $number  Number you sending the message to
     * @param string $message Message you are sending
     * @param string $from    Number you are sending the message from
     * @param string $region  Region the message is being sent from
     */
    public function __construct($number,$message,$from=null,$region='us') {
      $providers = new \PHPSMS\Providers();
      $providerList;
      switch($region) {
        case 'us' :
          $providerList = $providers->us;
        break;
        case 'canada':
          $providerList = $providers->canada;
        break;
        case 'intl':
          $providerList = $providers->intl;
        break;
      }
      if(!$number || !$message ) {
        return '';
      }
      $this->result = '';
      foreach($providerList as $provider) {
        $to = str_replace('%s',$number,$provider);
        $headers = null;
        if ($from) {
            $headers = 'From: '.$from . "\r\n" .
            'Reply-To: '.$from . "\r\n";
        }
        if(mail($to, PRODUCT_NAME, $message, $headers)) {
            //echo 'success to '.$to.'\n';
            $this->result .= 'success to: '.$to.PHP_EOL;
        }
        else {
            $this->result .= 'fail to: '.$to.PHP_EOL;
        }
      }
    }
    public function getResult() {
        return $this->result;
    }
}