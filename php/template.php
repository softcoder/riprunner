<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once 'config_constants.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/Twig/Autoloader.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/Twig/Extension/Twig/Extensions/Autoloader.php';

\Twig_Autoloader::register();
\Twig_Extensions_Autoloader::register();

class RiprunnerTwig {
    
    private $twig_template_loader = null;
    private $twig = null;
    
    public function getLoader() {
        if($this->twig_template_loader === null) {
            $this->twig_template_loader = new \Twig_Loader_Filesystem(
		__RIPRUNNER_ROOT__ . '/views');
// This allows customized views to be placed in the folder below
if(file_exists(__RIPRUNNER_ROOT__ . '/views-custom') === true) {
                $this->twig_template_loader->addPath(__RIPRUNNER_ROOT__ . '/views-custom', 'custom');
}
            return $this->twig_template_loader;
        }
    }
    public function getEnvironment() {
        if($this->twig === null) {
            $this->twig = new \Twig_Environment($this->getLoader(), array(
	'cache' => __RIPRUNNER_ROOT__ . '/temp/twig',
	'debug' => true,
	'strict_variables' => true
));
        }
        return $this->twig;
    }
}

$riprunner_twig = new \riprunner\RiprunnerTwig();
$twig = $riprunner_twig->getEnvironment();
//$twig->addExtension(new \Twig_Extension_Debug());
$twig->addExtension(new \Twig_Extensions_Extension_Text());
$twig->addExtension(new \Twig_Extensions_Extension_Date());

